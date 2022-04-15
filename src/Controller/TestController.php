<?php

namespace App\Controller;

use App\Entity\ExtractedArticle;
use App\Entity\ExtractedArticles;
use App\Form\ArticleFormType;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Translator\GoogleTranslate;
use Doctrine\ORM\EntityManagerInterface;


class TestController extends AbstractController
{

    public function extractContent(string $url = '')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.summarizebot.com/scripts/analysis.py');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"text\":\"https://www.elmundo.es/deportes/futbol/premier-league/2022/04/09/6251b7dafdddff50718b45a1.html\",\"tab\":\"ae\",\"options\":{}}");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        return curl_exec($ch);
    }

    public function verifyUrl(string $url = '')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.summarizebot.com/scripts/analysis.py');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"text\":\"https://www.elmundo.es/deportes/futbol/premier-league/2022/04/09/6251b7dafdddff50718b45a1.html\",\"tab\":\"fn\",\"options\":{}}");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        return [
            'result' => curl_exec($ch),
            'url' => 'https://www.elmundo.es/deportes/futbol/premier-league/2022/04/09/6251b7dafdddff50718b45a1.html'
        ];
    }

    /**
     * @throws \ErrorException
     */
    public function translate($data) {
        $translator = new GoogleTranslate(' en');

        return [
            'title' => $translator->translate($data['title']),
            'text' => $translator->translate($data['text'])
        ];
    }

    /**
     * @Route("/save", name="save_content")
     * @throws \ErrorException
     */
    public function saveContent(ManagerRegistry $doctrine): Response
    {
        $url = $_GET['url'];
        $result = $this->extractContent($url);

        $data = [
            "title" => json_decode($result)->{'article title'},
            "text" => json_decode($result)->{'text'}
        ];

        $dataTranslated = $this->translate($data);

        $entityManager = $doctrine->getManager();

        $product = new ExtractedArticle();
        $product->setOriginalContent(json_decode($result)->{'text'});
        $product->setOriginalTitle(json_decode($result)->{'article title'});
        $product->setTranslatedContent($dataTranslated['text']);
        $product->setTranslatedTitle($dataTranslated['title']);
        $product->setUrl($url);

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($product);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();


        return $this->redirectToRoute('verify_url', [
            'url' => $url
        ]);
    }

    /**
     * @Route("/new", name="new_article_extracted")
     */
    public function new(EntityManagerInterface $em, Request $request)
    {
        $form = $this->createForm(ArticleFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Articolul a fost extras cu succes.');

            return $this->redirectToRoute('save_content', [
                'url' => $form->getData()->getUrl()
            ]);
        }

        return $this->render('index.html.twig', [
            'articleForm' => $form->createView()
        ]);
    }

    /**
     * @Route("/get-url", name="get_url_from_extension")
     */
    public function getUrlFromExtension() {
        return $this->redirectToRoute('save_content', [
            'url' => $_POST['url']
        ]);
    }

    /**
     * @Route("/verify-url", name="verify_url")
     */
    public function getUrlStats() {
        $result = $this->verifyUrl();

        $rand = rand(0, 100) / 100;
        $real = $rand;
        $fake = 1 - $rand;
        dump($real . '      ');
        dump($fake);
//        dd(json_decode($result));
        return new Response('{\'real\':\'' . $real . '\', \'fake\':\'' . $fake . '\'}');
    }
}
