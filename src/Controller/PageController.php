<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('accueil/contact.html.twig');
    }

    #[Route('/a-propos', name: 'app_a_propos')]
    public function aPropos(): Response
    {
        return $this->render('accueil/a_propos.html.twig');
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('accueil/mentions_legales.html.twig');
    }
}
