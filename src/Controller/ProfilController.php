<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
        public function profil(): Response
    {
        $utilisateur = $this->getUser();
        /** @var \App\Entity\User $utilisateur */

        // Connexion PDO
        $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :id");
        $stmt->execute(['id' => $utilisateur->getId()]);
        $voitures = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('profil/profil.html.twig', [
            'user' => $utilisateur,
            'voitures' => $voitures, // ← on transmet un tableau de voitures
        ]);
    }
}