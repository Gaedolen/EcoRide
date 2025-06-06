<?php

namespace App\Controller;

use App\Entity\Voiture;
use App\Form\VoitureType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VoitureController extends AbstractController
{
    #[Route('/ajouter-voiture', name: 'ajouter_voiture', methods: ['GET', 'POST'])]
    public function ajouterVoiture(Request $request): Response
    {
        $voiture = new Voiture();
        $form = $this->createForm(VoitureType::class, $voiture);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Récupération de la couleur depuis le champ caché dans "voiture[couleur]"
            $voiturePost = $request->request->get('voiture');
            $couleur = $voiturePost['couleur'] ?? null;

            if (empty($couleur)) {
                $this->addFlash('error', 'Veuillez choisir une couleur.');
                return $this->redirectToRoute('ajouter_voiture');
            }

            $voiture->setCouleur($couleur);

            if ($form->isValid()) {
                // Vérifie que l'utilisateur est connecté
                $utilisateur = $this->getUser();
                /** @var \App\Entity\User $utilisateur */
                if (!$utilisateur) {
                    $this->addFlash('error', 'Vous devez être connecté pour ajouter une voiture.');
                    return $this->redirectToRoute('app_login');
                }

                // Préparation des données pour l’insertion PDO
                $data = [
                    'immatriculation' => $voiture->getImmatriculation(),
                    'date_premiere_immatriculation' => $voiture->getDatePremiereImmatriculation()?->format('Y-m-d'),
                    'marque' => $voiture->getMarque(),
                    'modele' => $voiture->getModele(),
                    'nb_places' => $voiture->getNbPlaces(),
                    'fumeur' => $voiture->isFumeur() ? 1 : 0,
                    'animaux' => $voiture->isAnimaux() ? 1 : 0,
                    'couleur' => $voiture->getCouleur(),
                    'energie' => $voiture->getEnergie(),
                    'utilisateur_id' => $utilisateur->getId(),
                ];

                // Connexion PDO et insertion
                $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
                $sql = "
                    INSERT INTO voiture 
                    (immatriculation, date_premiere_immatriculation, marque, modele, nb_places, fumeur, animaux, couleur, energie, utilisateur_id)
                    VALUES 
                    (:immatriculation, :date_premiere_immatriculation, :marque, :modele, :nb_places, :fumeur, :animaux, :couleur, :energie, :utilisateur_id)
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);

                $this->addFlash('success', 'Voiture ajoutée avec succès !');
                return $this->redirectToRoute('app_profil');
            }

            // Affichage des erreurs si le formulaire est invalide
            foreach ($form->getErrors(true, false) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('voiture/ajouter.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}