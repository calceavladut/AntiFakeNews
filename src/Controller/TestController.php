<?php

namespace App\Controller;

use App\Entity\ExtractedArticle;
use App\Form\ArticleFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Translator\GoogleTranslate;
use Doctrine\ORM\EntityManagerInterface;
use function Symfony\Component\String\b;


class TestController extends AbstractController
{

    private ManagerRegistry $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
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
        $entityManager = $this->doctrine->getManager();

        $data = [
            "title" => json_decode($result)->{'article title'},
            "text" => json_decode($result)->{'text'}
        ];

        $dataTranslated = $this->translate($data);

        $product = new ExtractedArticle();
        $product->setOriginalContent(json_decode($result)->{'text'});
        $product->setOriginalTitle(json_decode($result)->{'article title'});
        $product->setTranslatedContent($dataTranslated['text']);
        $product->setTranslatedTitle($dataTranslated['title']);
        $product->setUrl($url);

        $entityManager->persist($product);
        $entityManager->flush();

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
        var_dump($result);
        foreach (json_decode($result)->{'predictions'} as $type) {
            $fake = $type->{'type'} == 'fake' ? $type->{'confidence'}: 1.0 - $type->{'confidence'};
            $real = $type->{'type'} == 'real' ? $type->{'confidence'}: 1.0 - $type->{'confidence'};
        }
        dd('{"real":"' . $real . '", "fake":"' . $fake . '"}');
        return new Response('{"real":"' . $real . '", "fake":"' . $fake . '"}');
    }
}
