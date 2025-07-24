<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UtilisateurController extends AbstractController
{
    #[Route('/utilisateur', name: 'utilisateur_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('utilisateur/dashboard.html.twig');
    }
}
