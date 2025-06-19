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
        // Création d'une nouvelle voiture
        $voiture = new Voiture();

        // Création du formulaire associé à l'entité Voiture
        $form = $this->createForm(VoitureType::class, $voiture);

        // Traitement de la requête HTTP
        $form->handleRequest($request);

        // Si l'utilisateur a soumis le formulaire
        if ($form->isSubmitted()) {

            // Si les données sont valides
            if ($form->isValid()) {

                // Récupération de l'utilisateur connecté
                $utilisateur = $this->getUser();
                /** @var \App\Entity\User $utilisateur */

                // Si aucun utilisateur n'est pas connecté, on redirige vers la connexion
                if (!$utilisateur) {
                    $this->addFlash('error', 'Vous devez être connecté pour ajouter une voiture.');
                    return $this->redirectToRoute('app_login');
                }

                // Préparation des données pour l'insertion dans la base
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

                // Insertion en base via PDO
                try {
                    $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
                    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                    $stmt = $pdo->prepare("
                        INSERT INTO voiture 
                        (immatriculation, date_premiere_immatriculation, marque, modele, nb_places, fumeur, animaux, couleur, energie, utilisateur_id)
                        VALUES 
                        (:immatriculation, :date_premiere_immatriculation, :marque, :modele, :nb_places, :fumeur, :animaux, :couleur, :energie, :utilisateur_id)
                    ");

                    $stmt->execute($data);

                    $this->addFlash('success', 'Voiture ajoutée avec succès !');
                    return $this->redirectToRoute('app_profil');

                } catch (\PDOException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'insertion : ' . $e->getMessage());
                    return $this->redirectToRoute('ajouter_voiture');
                }
            }

            // Si le formulaire est invalide, on affiche les erreurs
            foreach ($form->getErrors(true, false) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        // Affichage de la page
        return $this->render('voiture/ajouter.html.twig', [
            'form' => $form->createView(),
            'voiture' => $voiture,
        ]);
    }

    #[Route('/voiture/modifier/{id}', name: 'modifier_voiture')]
    public function modifier(Request $request, int $id): Response
    {
        $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Récupération des données de la voiture
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw $this->createNotFoundException('Voiture introuvable');
        }

        // Crée une entité Voiture
        $voiture = new Voiture();
        $voiture
            ->setId($row['id'])
            ->setMarque($row['marque'])
            ->setModele($row['modele'])
            ->setImmatriculation($row['immatriculation'])
            ->setDatePremiereImmatriculation(new \DateTime($row['date_premiere_immatriculation']))
            ->setNbPlaces($row['nb_places'])
            ->setEnergie($row['energie'])
            ->setCouleur($row['couleur'])
            ->setFumeur((bool) $row['fumeur'])
            ->setAnimaux((bool) $row['animaux']);

        // Crée le formulaire
        $form = $this->createForm(VoitureType::class, $voiture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Met à jour la BDD
            $data = [
                'id' => $id,
                'immatriculation' => $voiture->getImmatriculation(),
                'date' => $voiture->getDatePremiereImmatriculation()->format('Y-m-d'),
                'marque' => $voiture->getMarque(),
                'modele' => $voiture->getModele(),
                'nb_places' => $voiture->getNbPlaces(),
                'fumeur' => $voiture->isFumeur() ? 1 : 0,
                'animaux' => $voiture->isAnimaux() ? 1 : 0,
                'couleur' => $voiture->getCouleur(),
                'energie' => $voiture->getEnergie(),
            ];

            $sql = "
                UPDATE voiture SET
                immatriculation = :immatriculation,
                date_premiere_immatriculation = :date,
                marque = :marque,
                modele = :modele,
                nb_places = :nb_places,
                fumeur = :fumeur,
                animaux = :animaux,
                couleur = :couleur,
                energie = :energie
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            $this->addFlash('success', 'Voiture modifiée avec succès !');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('voiture/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/voiture/supprimer/{id}', name: 'supprimer_voiture', methods: ['POST'])]
public function supprimer(int $id): Response
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("DELETE FROM voiture WHERE id = :id AND utilisateur_id = :utilisateur_id");
        $stmt->execute([
            'id' => $id,
            'utilisateur_id' => $utilisateur->getId(),
        ]);

        $this->addFlash('success', 'Voiture supprimée avec succès.');
        return $this->redirectToRoute('app_profil');
    }


}
