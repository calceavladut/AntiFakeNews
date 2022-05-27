<?php

namespace App\Controller;

use App\Entity\ExtractedArticle;
use App\Entity\TrustedSites;
use App\Form\ArticleFormType;
use App\Repository\ExtractedArticleRepository;
use App\Repository\TrustedSitesRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use ErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Translator\GoogleTranslate;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class TestController extends AbstractController
{
    private ObjectManager $entityManager;

    private ManagerRegistry $doctrine;

    private ExtractedArticleRepository $articleRepository;
    private TrustedSitesRepository $trustedSitesRepository;

    /**
     * @param ManagerRegistry $doctrine
     * @param ExtractedArticleRepository $articleRepository
     * @param TrustedSitesRepository $trustedSitesRepository
     */
    public function __construct(ManagerRegistry $doctrine, ExtractedArticleRepository $articleRepository, TrustedSitesRepository $trustedSitesRepository)
    {
        $this->doctrine          = $doctrine;
        $this->entityManager     = $this->doctrine->getManager();
        $this->articleRepository = $articleRepository;
        $this->trustedSitesRepository = $trustedSitesRepository;
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
     *
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
     * @param $url
     *
     * @return array
     */
    public function getDataFromUrl($url): array
    {
        $result = $this->extractContent($url);

        $data = [];
        $articleDecoded = json_decode($result, true);
        if (!empty($articleDecoded['article title'])) {
            $data['title'] = $articleDecoded['article title'];
        }
        else {
            $data['title'] = 'title';
        }

        if (!empty($articleDecoded['text'])) {
            $data['text'] = $articleDecoded['text'];
        }
        else {
            $data['text'] = 'text';
        }

        return $data;
    }

    /**
     * @param $data
     * @param $dataTranslated
     * @param $url
     *
     * @return ExtractedArticle
     */
    public function createArticleIfNotExists ($data, $dataTranslated, $url): ExtractedArticle
    {
        $article = new ExtractedArticle();
        $article->setOriginalContent($data['text']);
        $article->setOriginalTitle($data['title']);
        $article->setTranslatedContent($dataTranslated['text']);
        $article->setTranslatedTitle($dataTranslated['title']);
        $article->setUrl($url);

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $article;
    }

    /**
     * @param $article
     */
    public function populateTrustedSitesTable ($article) {
        $real = 0;
        $fake = 0;

        $parsedDomain = parse_url($article->getUrl())['host'];

        $domain = $this->trustedSitesRepository->findTrustedSiteByDomain($parsedDomain);
        $url = $this->generateUrl('generated_url', ['id' => $article->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $urlStats = $this->verifyUrl($url);

        $result = json_decode($urlStats, true);
        foreach ($result['predictions'] as $type) {
            $fake = $type['type'] == 'fake' ? $type['confidence'] : 1.0 - $type['confidence'];
            $real = $type['type'] == 'real' ? $type['confidence'] : 1.0 - $type['confidence'];
        }

        if (!$domain) {
            $trustedSite = new TrustedSites($parsedDomain);
            $trustedSite->setTotalHits(1);

            $fake > $real ? $trustedSite->setPercentage(0) : $trustedSite->setPercentage(100);
            $fake > $real ? $trustedSite->setFakeHits(1) : $trustedSite->setRealHits(1);

            $this->entityManager->persist($trustedSite);
        } else {
            $totalHits = $domain->getTotalHits();
            $falseHits = $domain->getFakeHits();
            $trueHits = $domain->getRealHits();
            $percentage = $trueHits / $totalHits * 100;

            $domain->setTotalHits($totalHits + 1);
            $domain->setPercentage($percentage);
            $fake > $real ? $domain->setFakeHits($falseHits + 1) : $domain->setRealHits($trueHits + 1);

            $this->entityManager->persist($domain);
        }
    }

    /**
     * @param string $url
     * @param bool $isForSite
     * @return Response
     * @throws ErrorException
     */
    public function saveContentFromUrl(string $url, bool $isForSite): Response
    {
        $data = $this->getDataFromUrl($url);

        $dataTranslated = $this->translate($data);
        $article        = $this->articleRepository->findArticleByUrl($url);

        if (!$article) {
            $article = $this->createArticleIfNotExists($data, $dataTranslated, $url);
            $this->populateTrustedSitesTable($article);
        }
        else {
            $url = $this->generateUrl('generated_url', ['id' => $article->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        $this->entityManager->flush();

        return $this->getUrlStats($url, $isForSite, $article->getUrl());
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
        $article        = $this->articleRepository->findArticleByTranslatedText($dataTranslated['text']);

        if (!$article) {
            $article = new ExtractedArticle();
            $article->setTranslatedContent($dataTranslated['text']);

            $this->entityManager->persist($article);
            $this->entityManager->flush();

        }

        $url = $this->generateUrl('generated_url', ['id' => $article->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

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
                return $this->verifyContentFromText($data->getText());
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
        return $this->saveContentFromUrl($_POST['url'], false);
    }

    /**
     * @param string|null $url
     * @param bool $isForSite
     * @param string|null $articleUrl
     * @return Response
     */
    public function getUrlStats(?string $url, bool $isForSite = false, ?string $articleUrl = null): Response
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
                'fake'          => $data['fake'] * 100,
                'real'          => $data['real'] * 100,
                'url'           => $articleUrl,
            ]);
        }
    }


    /**
     * @Route("/trusted-sites", name="trusted_sited")
     * @return Response
     */
    public function getTrustedSites(): Response
    {
        return $this->render('trusted_sites.html.twig',
        [
            'trusted_sites' => $this->trustedSitesRepository->getTopTrustedSites()
        ]);
    }
}
