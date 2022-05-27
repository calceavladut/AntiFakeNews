<?php

namespace App\Controller;

use App\Entity\ExtractedArticle;
use App\Form\ArticleFormType;
use App\Repository\ExtractedArticleRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use ErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Translator\GoogleTranslate;
use Symfony\Component\HttpFoundation\Response;
use function Symfony\Component\String\u;


class TestController extends AbstractController
{
    private ObjectManager $entityManager;

    private ManagerRegistry $doctrine;

    private ExtractedArticleRepository $articleRepository;

    private string $dns = 'http://roundearthsociety.zapto.org:81';

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine, ExtractedArticleRepository $articleRepository)
    {
        $this->doctrine          = $doctrine;
        $this->entityManager     = $this->doctrine->getManager();
        $this->articleRepository = $articleRepository;
    }

    /**
     * @param string $url
     * @return bool|string
     */
    public function extractContent(string $url)
    {
        $body = '{"text":"'.$url.'","tab":"ae","options":{}}';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.summarizebot.com/scripts/analysis.py');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        return curl_exec($ch);
    }

    /**
     * @param string $url
     * @return bool|string
     */
    public function verifyUrl(string $url): bool|string
    {
        $ch   = curl_init();
        $body = '{"text":"'.$url.'","tab":"fn","options":{}}';

        curl_setopt($ch, CURLOPT_URL, 'https://www.summarizebot.com/scripts/analysis.py');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        return curl_exec($ch);
    }

    /**
     * @param $data
     * @return array
     * @throws ErrorException
     */
    public function translate($data)
    {
        $translator = new GoogleTranslate('en');

        return [
            'title' => $translator->translate($data['title']),
            'text'  => $translator->translate($data['text']),
        ];
    }

    /**
     * @param string $url
     * @param bool $isForSite
     * @return Response
     * @throws ErrorException
     */
    public function saveContentFromUrl(string $url, bool $isForSite = false): Response
    {
        $result = $this->extractContent($url);

        $data = [
            "title" => json_decode($result)->{'article title'},
            "text"  => json_decode($result)->{'text'},
        ];

        $dataTranslated = $this->translate($data);
        $article        = $this->articleRepository->findArticleByUrl($url);

        if (!$article) {
            $article = new ExtractedArticle();
            $article->setOriginalContent(json_decode($result)->{'text'});
            $article->setOriginalTitle(json_decode($result)->{'article title'});
            $article->setTranslatedContent($dataTranslated['text']);
            $article->setTranslatedTitle($dataTranslated['title']);
            $article->setUrl($url);

            $this->entityManager->persist($article);
            $this->entityManager->flush();
        }

        $url = $this->dns . $this->generateUrl('generated_url', ['id' => $article->getId()]);

        return $this->getUrlStats($url, true);
    }

    /**
     * @param string $text
     * @return Response
     * @throws ErrorException
     */
    public function verifyContentFromText(string $text): Response
    {
        $data = [
            "title" => "",
            "text"  => $text,
        ];

        $dataTranslated = $this->translate($data);
        $article        = $this->articleRepository->findArticleByTranslatedText($dataTranslated);

        if ($article){
            dd($article);
        }

        if (!$article) {
            //TODO: de facut verificare daca exista un articol deja in baza de date cu acelasi text sa nu mai adauge un articol nou
            $article = new ExtractedArticle();
            $article->setTranslatedContent($dataTranslated['text']);

            $this->entityManager->persist($article);
            $this->entityManager->flush();

        }

        $url = $this->dns . $this->generateUrl('generated_url', ['id' => $article->getId()]);

        return $this->getUrlStats($url, true);
    }

    /**
     * @Route("/posts/{id}", name="generated_url")
     */
    public function generateTranslatedTextUrl($id)
    {
        $article = $this->articleRepository->find($id);
        if (is_object($article)) {
            return $this->render('text_page.html.twig', [
                'title' => $article->getTranslatedTitle() ?: '',
                'text'  => $article->getTranslatedContent(),
            ]);
        }

        return new Response('Could not find nothin\'');
    }

    /**
     * @Route("/", name="homepage")
     */
    public function new(Request $request)
    {
        $form = $this->createForm(ArticleFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Articolul a fost extras cu succes.');

            /** @var ExtractedArticle $data */
            $data = $form->getData();

            if ($data->getText()) {
                return $this->verifyContentFromText($data->getText(), true);
            } else {
                if ($data->getUrl()) {
                    return $this->saveContentFromUrl($data->getUrl(), true);
                }
            }
        }

        return $this->render('index.html.twig', [
            'articleForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/get-url", name="get_url_from_extension")
     * @throws ErrorException
     */
    public function getUrlFromExtension()
    {
        return $this->saveContentFromUrl($_POST['url']);
    }

    /**
     * @param string|null $url
     * @param bool $isForSite
     * @return Response
     */
    public function getUrlStats(?string $url, bool $isForSite = false): Response
    {
        $result        = $this->verifyUrl($url);
        $real          = 0;
        $fake          = 0;
        $bias          = 0;
        $conspiracy    = 0;
        $propaganda    = 0;
        $pseudoscience = 0;
        $irony         = 0;

        $decoded       = json_decode($result, true);
        $arrayDecoded  = (array)$decoded;

        foreach (json_decode($result)->{'predictions'} as $type) {
            $fake = $type->{'type'} == 'fake' ? $type->{'confidence'} : 1.0 - $type->{'confidence'};
            $real = $type->{'type'} == 'real' ? $type->{'confidence'} : 1.0 - $type->{'confidence'};
//            $categories = $type->{'type'} == 'fake' ? $type->{'categories'}: [];
        }

        if ($real < 0.5) {
            $bias          = $arrayDecoded['predictions'][1]['categories'][0]['confidence'];
            $conspiracy    = $arrayDecoded['predictions'][1]['categories'][1]['confidence'];
            $propaganda    = $arrayDecoded['predictions'][1]['categories'][2]['confidence'];
            $pseudoscience = $arrayDecoded['predictions'][1]['categories'][3]['confidence'];
            $irony         = $arrayDecoded['predictions'][1]['categories'][4]['confidence'];
        }

        $data = [
            'fake'          => $fake,
            'real'          => $real,
            'bias'          => $bias,
            'conspiracy'    => $conspiracy,
            'propaganda'    => $propaganda,
            'pseudoscience' => $pseudoscience,
            'irony'         => $irony,
        ];

        if ($isForSite === false) {
            return new Response('{"real":"'.$real.'", "fake":"'.$fake.'"}');
        } else {
            return $this->render('resultsurl.html.twig', [
                'bias'          => $data['bias'] * 100,
                'conspiracy'    => $data['conspiracy'] * 100,
                'propaganda'    => $data['propaganda'] * 100,
                'pseudoscience' => $data['pseudoscience'] * 100,
                'irony'         => $data['irony'] * 100,
                'url'           => $url,
                'fake'          => $fake,
                'real'          => $real,
            ]);
        }
    }
}
