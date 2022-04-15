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

    public function verifyUrl(string $url)
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
     * @throws \ErrorException
     */
    public function translate($data) {
        $translator = new GoogleTranslate('en');

        return [
            'title' => $translator->translate($data['title']),
            'text' => $translator->translate($data['text'])
        ];
    }

    /**
     * @throws \ErrorException
     */
    public function saveContent(string $url)
    {
        $result = $this->extractContent($url);

        $data = [
            "title" => json_decode($result)->{'article title'},
            "text" => json_decode($result)->{'text'}
        ];

        $dataTranslated = $this->translate($data);
        if (!$this->articleRepository->findArticleByUrl($url)) {
            $article = new ExtractedArticle();
            $article->setOriginalContent(json_decode($result)->{'text'});
            $article->setOriginalTitle(json_decode($result)->{'article title'});
            $article->setTranslatedContent($dataTranslated['text']);
            $article->setTranslatedTitle($dataTranslated['title']);
            $article->setUrl($url);

            $this->entityManager->persist($article);
            $this->entityManager->flush();
        }

        return $this->getUrlStats($url);
    }

    /**
     * @Route("/", name="homepage")
     * @throws \ErrorException
     */
    public function new(Request $request)
    {
        $form = $this->createForm(ArticleFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Articolul a fost extras cu succes.');

            return $this->saveContent($form->getData()->getUrl());
        }

        return $this->render('index.html.twig', [
            'articleForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/get-url", name="get_url_from_extension")
     * @throws \ErrorException
     */
    public function getUrlFromExtension()
    {
        return $this->saveContent($_POST['url']);
    }

    public function getUrlStats(string $url) {
        $result = $this->verifyUrl($url);

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

        return [
            'fake' => $fake,
            'real' => $real,
            'bias' => $bias,
            'conspiracy' => $conspiracy,
            'propaganda' => $propaganda,
            'pseudoscience' => $pseudoscience,
            'irony' => $irony
        ];
    }
}
