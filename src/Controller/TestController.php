<?php

namespace App\Controller;

use App\Entity\ExtractedArticle;
use App\Form\ArticleFormType;
use App\Repository\ExtractedArticleRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Translator\GoogleTranslate;
use Symfony\Component\HttpFoundation\Response;


class TestController extends AbstractController
{
    private ObjectManager $entityManager;

    private ManagerRegistry $doctrine;

    private ExtractedArticleRepository $articleRepository;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine, ExtractedArticleRepository $articleRepository)
    {
        $this->doctrine = $doctrine;
        $this->entityManager = $this->doctrine->getManager();
        $this->articleRepository = $articleRepository;
    }

    /**
     * @param string $url
     * @return bool|string
     */
    public function extractContent(string $url)
    {
        $body = '{"text":"'. $url . '","tab":"ae","options":{}}';

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
        $ch = curl_init();
        $body = '{"text":"'. $url . '","tab":"fn","options":{}}';

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
     * @throws \ErrorException
     */
    public function translate($data) {
        $translator = new GoogleTranslate('en');

        return [
            'title' => $translator->translate($data['title']),
            'text'  => $translator->translate($data['text'])
        ];
    }

    /**
     * @param string $url
     * @return Response
     * @throws \ErrorException
     */
    public function saveContentFromUrl(string $url): Response
    {
        $result = $this->extractContent($url);

        $data = [
            "title" => json_decode($result)->{'article title'},
            "text"  => json_decode($result)->{'text'}
        ];

        $dataTranslated = $this->translate($data);
        $article = $this->articleRepository->findArticleByUrl($url);

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

        return $this->redirectToRoute('generated_url', ['id' => $article->getId()]);
    }

    /**
     * @param string $text
     * @return Response
     * @throws \ErrorException
     */
    public function verifyContentFromText(string $text)
    {
        $data = [
            "title" => "",
            "text"  => $text
        ];

        $dataTranslated = $this->translate($data);

        $article = new ExtractedArticle();
        $article->setTranslatedContent($dataTranslated['text']);

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $this->redirectToRoute('generated_url', ['id' => $article->getId()]);
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
                'text'  => $article->getTranslatedContent()
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

            if ($data->getUrl()) {;
                return $this->saveContentFromUrl($data->getUrl());
            } else if ($data->getText()) {
                return $this->verifyContentFromText($data->getText());
            }
        }

        return $this->render('index.html.twig', [
            'articleForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/get-url", name="get_url_from_extension")
     */
    public function getUrlFromExtension()
    {
        return $this->saveContentFromUrl($_GET['url']);
    }

    /**
     * @param string|null $url
     * @return Response
     */
    public function getUrlStats(?string $url) {
        $result = $this->verifyUrl($url);
        $real = 0;
        $fake = 0;
        $bias = 0;
        $conspiracy = 0;
        $propaganda = 0;
        $pseudoscience = 0;
        $irony = 0;

        foreach (json_decode($result)->{'predictions'} as $type) {
            $fake = $type->{'type'} == 'fake' ? $type->{'confidence'}: 1.0 - $type->{'confidence'};
            $real = $type->{'type'} == 'real' ? $type->{'confidence'}: 1.0 - $type->{'confidence'};
            $categories = $type->{'type'} == 'fake' ? $type->{'categories'}: [];
        }

        foreach ($categories as $category) {
            $bias = $category->{'type'} == 'bias' ? $category->{'confidence'}: 0;;
            $conspiracy = $category->{'type'} == 'conspiracy' ? $category->{'confidence'}: 0;;
            $propaganda = $category->{'type'} == 'propaganda' ? $category->{'confidence'}: 0;;
            $pseudoscience = $category->{'type'} == 'pseudoscience' ? $category->{'confidence'}: 0;;
            $irony = $category->{'type'} == 'irony' ? $category->{'confidence'}: 0;;
        }

        $dates = [
            'fake' => $fake,
            'real' => $real,
            'bias' => $bias,
            'conspiracy' => $conspiracy,
            'propaganda' => $propaganda,
            'pseudoscience' => $pseudoscience,
            'irony' => $irony
        ];

        return new Response('asd');
        // ii trimiti aici metoda ta cu dates
//        return $dates;
    }
}
