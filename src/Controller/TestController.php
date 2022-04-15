<?php

namespace App\Controller;

use App\Entity\ExtractedArticles;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Translator\GoogleTranslate;

class TestController extends AbstractController
{

    /**
     * @Route("/extract-article", name="admin_group_list")
     */
    public function extractArticle(string $url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.summarizebot.com/scripts/analysis.py');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"text\":\"https://www.elmundo.es/deportes/futbol/premier-league/2022/04/09/6251b7dafdddff50718b45a1.html\",\"tab\":\"ae\",\"options\":{}}");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $result = curl_exec($ch);



        dd($result);
        echo(json_decode($result)->{'article title'});

        $translator = new GoogleTranslate(' en');
        $text = $translator->translate('Ana are mere si pere.');
        return $this->render('base.html.twig', [
            'text' => $text,
        ]);
    }

    /**
     * @Route("/product", name="create_product")
     * @throws \ErrorException
     */
    public function createProduct(ManagerRegistry $doctrine): Response
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.summarizebot.com/scripts/analysis.py');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"text\":\"https://www.elmundo.es/deportes/futbol/premier-league/2022/04/09/6251b7dafdddff50718b45a1.html\",\"tab\":\"ae\",\"options\":{}}");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $result = curl_exec($ch);


//        dd($result);
//        echo(json_decode($result)->{'article title'});

        //        return $this->render('base.html.twig', [
//            'text' => $text,
//        ]);

        $translator = new GoogleTranslate(' en');

        $title = $translator->translate(json_decode($result)->{'article title'});
        $text = $translator->translate(json_decode($result)->{'text'});

        $entityManager = $doctrine->getManager();

        $product = new ExtractedArticles();
        $product->setText($text);
        $product->setTitle($title);
        $product->setUrl('$url');


        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($product);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return new Response('Saved new product with id '.$product->getId());
    }
}
