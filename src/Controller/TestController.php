<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Translator\GoogleTranslate;

class TestController extends AbstractController
{
    /**
     * @Route("/list", name="admin_group_list")
     */
    public function list()
    {
        $translator = new GoogleTranslate(' en');
        $text = $translator->translate('Ana are mere si pere.');
        return $this->render('base.html.twig', [
            'text' => $text,
        ]);
    }
}
