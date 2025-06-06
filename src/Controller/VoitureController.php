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
        $form    = $this->createForm(VoitureType::class, $voiture);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            // couleur envoyée par l’input hidden « voiture[couleur] »
            $couleur = $request->request->get('couleur_personnalisee');
            if (empty($couleur)) {
                $this->addFlash('error', 'Veuillez choisir une couleur.');
                return $this->redirectToRoute('ajouter_voiture');
            }
            $voiture->setCouleur($couleur);

            // revalide que la couleur est dans l’entité
            if ($form->isValid()) {

                /* Vérifie si l'utilisateur est connecté */
                /** @var \App\Entity\User|null $utilisateur */
                $utilisateur = $this->getUser();
                if (!$utilisateur) {
                    $this->addFlash('error', 'Vous devez être connecté pour ajouter une voiture.');
                    return $this->redirectToRoute('app_login');
                }

                /* Préparation des données*/
                $data = [
                    'immatriculation' => $voiture->getImmatriculation(),
                    'date_premiere_immatriculation' => $voiture->getDatePremiereImmatriculation()?->format('Y-m-d'),
                    'marque' => $voiture->getMarque(),
                    'modele' => $voiture->getModele(),
                    'nb_places' => $voiture->getNbPlaces(),
                    'fumeur' => $voiture->isFumeur()  ? 1 : 0,
                    'animaux' => $voiture->isAnimaux() ? 1 : 0,
                    'couleur' => $voiture->getCouleur(),
                    'energie' => $voiture->getEnergie(),
                    'utilisateur_id'=> $utilisateur->getId(),
                ];

                /* PDO  */
                $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
                $sql = "
                    INSERT INTO voiture
                    (immatriculation, date_premiere_immatriculation, marque, modele,
                     nb_places, fumeur, animaux, couleur, energie, utilisateur_id)
                    VALUES
                    (:immatriculation, :date_premiere_immatriculation, :marque, :modele,
                     :nb_places, :fumeur, :animaux, :couleur, :energie, :utilisateur_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);

                $this->addFlash('success', 'Voiture ajoutée avec succès !');
                return $this->redirectToRoute('app_profil');
            }

            /* Formulaire non valide : on affiche les erreurs */
            foreach ($form->getErrors(true, false) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        /* AFFICHAGE INITIAL OU ERREUR */
        return $this->render('voiture/ajouter.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
