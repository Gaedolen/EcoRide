<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\CovoiturageType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Covoiturage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use \PDO;

class CovoiturageController extends AbstractController
{
    #[Route('/covoiturage', name: 'app_covoiturage')]
    public function rechercher(Request $request): Response
    {
        $depart = $request->query->get('lieu_depart');
        $arrivee = $request->query->get('lieu_arrivee');
        $date = $request->query->get('date_depart');
        $heure = $request->query->get('heure_depart');

        // Connexion PDO
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Requête avec jointure sur la table "user" et "voiture
       $stmt = $pdo->prepare("
            SELECT 
                c.*, 
                u.nom AS user_pseudo,
                u.nom AS user_nom,
                u.prenom AS user_prenom,
                u.photo AS user_photo,
                u.note AS user_note,
                v.energie AS voiture_energie
            FROM covoiturage c
            JOIN user u ON u.id = c.utilisateur_id
            JOIN voiture v ON v.id = c.voiture_id
            WHERE c.lieu_depart = :depart 
            AND c.lieu_arrivee = :arrivee 
            AND c.date_depart = :date 
            AND c.heure_depart >= :heure
        ");

        $stmt->execute([
            ':depart' => $depart,
            ':arrivee' => $arrivee,
            ':date' => $date,
            ':heure' => $heure,
        ]);

        $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Encoder les photos BLOB en base64
        foreach ($trajets as &$trajet) {
            if (!empty($trajet['user_photo'])) {
                $trajet['user_photo'] = base64_encode($trajet['user_photo']);
            } else {
                $trajet['user_photo'] = null;
            }
            // Conversion des heures et dates en objets DateTime pour Twig
            $trajet['heure_depart'] = new DateTime($trajet['heure_depart']);
            $trajet['heure_arrivee'] = new DateTime($trajet['heure_arrivee']);
            $trajet['date_depart'] = new DateTime($trajet['date_depart']);
            $trajet['date_arrivee'] = new DateTime($trajet['date_arrivee']);
        }

        return $this->render('covoiturage/resultats.html.twig', [
            'trajets' => $trajets
        ]);
    }

    #[Route('/covoiturage/{id}', name: 'details_trajet')]
    public function details(int $id): Response
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            SELECT c.*, u.nom AS user_nom, u.prenom AS user_prenom, u.photo AS user_photo, v.energie AS voiture_energie
            FROM covoiturage c
            JOIN user u ON u.id = c.utilisateur_id
            JOIN voiture v ON v.id = c.voiture_id
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trajet) {
            throw $this->createNotFoundException('Trajet introuvable.');
        }

        return $this->render('covoiturage/details.html.twig', [
            'trajet' => $trajet,
        ]);
    }

    #[Route('/covoiturage/ajouter', name: 'ajouter_covoiturage')]
    public function ajouterCovoiturage(Request $request): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */

        if (!$user || !$user->isChauffeur()) {
            throw $this->createAccessDeniedException('Vous devez être chauffeur pour ajouter un covoiturage.');
        }

        $covoiturage = new Covoiturage();

        // Définir l'heure à 12:00 par défaut
        $covoiturage->setHeureDepart(new \DateTime('12:00'));
        $covoiturage->setHeureArrivee(new \DateTime('12:00'));

        $form = $this->createForm(CovoiturageType::class, $covoiturage, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            dump($form->getErrors(true));
        }


        if ($form->isSubmitted() && $form->isValid()) {
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $now = new \DateTime();
            $date = $covoiturage->getDateDepart();
            $heure = $covoiturage->getHeureDepart();

           $isPast = ($date < $now) || ($date == $now && $heure < $now);
           $places = $covoiturage->getNbPlace();

            if ($isPast) {
               $statut = 'fermé';
            } elseif ($places <= 0) {
               $statut = 'complet';
            } else {
               $statut = 'ouvert';
            }


            $stmt = $pdo->prepare("
                INSERT INTO covoiturage (
                    utilisateur_id,
                    date_depart,
                    heure_depart,
                    lieu_depart,
                    date_arrivee,
                    heure_arrivee,
                    lieu_arrivee,
                    statut,
                    nb_place,
                    prix_personne,
                    voiture_id
                ) VALUES (
                    :utilisateur_id,
                    :date_depart,
                    :heure_depart,
                    :lieu_depart,
                    :date_arrivee,
                    :heure_arrivee,
                    :lieu_arrivee,
                    :statut,
                    :nb_place,
                    :prix_personne,
                    :voiture_id
                )
            ");

            $stmt->execute([
                'utilisateur_id' => $user->getId(),
                'date_depart'   => $covoiturage->getDateDepart()?->format('Y-m-d'),
                'heure_depart'  => $covoiturage->getHeureDepart()?->format('H:i:s'),
                'lieu_depart'   => $covoiturage->getLieuDepart(),
                'date_arrivee'  => $covoiturage->getDateArrivee()?->format('Y-m-d'),
                'heure_arrivee' => $covoiturage->getHeureArrivee()?->format('H:i:s'),
                'lieu_arrivee'  => $covoiturage->getLieuArrivee(),
                'nb_place'      => $covoiturage->getNbPlace(),
                'prix_personne' => $covoiturage->getPrixPersonne(),
                'voiture_id' => $covoiturage->getVoiture()->getId(),
                'statut'        => $statut,
            ]);

            $this->addFlash('success', 'Covoiturage enregistré avec succès !');
            return $this->redirectToRoute('app_profil');
        }


        return $this->render('covoiturage/ajouter.html.twig', [
            'form' => $form->createView(),
            'covoiturage' => $covoiturage,
        ]);
    }

    #[Route('/covoiturage/{id}/modifier', name: 'modifier_covoiturage', methods: ['GET', 'POST'])]
    public function modifierCovoiturage(Request $request, int $id, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur || !$utilisateur->isChauffeur()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        // Connexion PDO pour récupérer le covoiturage
        $pdo = new PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE id = :id AND utilisateur_id = :utilisateur_id");
        $stmt->execute([
            'id' => $id,
            'utilisateur_id' => $utilisateur->getId(),
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        // Reconstituer l'entité Covoiturage
        $covoiturage = new Covoiturage();
        $covoiturage
            ->setDateDepart(new \DateTime($row['date_depart']))
            ->setHeureDepart(new \DateTime($row['heure_depart']))
            ->setLieuDepart($row['lieu_depart'])
            ->setDateArrivee(new \DateTime($row['date_arrivee']))
            ->setHeureArrivee(new \DateTime($row['heure_arrivee']))
            ->setLieuArrivee($row['lieu_arrivee'])
            ->setNbPlace((int) $row['nb_place'])
            ->setPrixPersonne((float) $row['prix_personne']);

        // Récupérer la voiture via Doctrine (obligatoire pour EntityType)
        if (!empty($row['voiture_id'])) {
            $voitureEntity = $em->getRepository(\App\Entity\Voiture::class)->find($row['voiture_id']);
            if ($voitureEntity) {
                $covoiturage->setVoiture($voitureEntity);
            }
        }

        // Création du formulaire
        $form = $this->createForm(CovoiturageType::class, $covoiturage, [
            'user' => $utilisateur,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalculer le statut
            $now = new \DateTime();
            $isPast = ($covoiturage->getDateDepart() < $now) || ($covoiturage->getDateDepart() == $now && $covoiturage->getHeureDepart() < $now);
            $statut = $isPast ? 'ferme' : (($covoiturage->getNbPlace() <= 0) ? 'complet' : 'ouvert');

            // Mise à jour BDD
            $stmt = $pdo->prepare("
                UPDATE covoiturage SET
                    date_depart = :date_depart,
                    heure_depart = :heure_depart,
                    lieu_depart = :lieu_depart,
                    date_arrivee = :date_arrivee,
                    heure_arrivee = :heure_arrivee,
                    lieu_arrivee = :lieu_arrivee,
                    nb_place = :nb_place,
                    prix_personne = :prix_personne,
                    voiture_id = :voiture_id,
                    statut = :statut
                WHERE id = :id
            ");

            $stmt->execute([
                'date_depart'   => $covoiturage->getDateDepart()->format('Y-m-d'),
                'heure_depart'  => $covoiturage->getHeureDepart()->format('H:i:s'),
                'lieu_depart'   => $covoiturage->getLieuDepart(),
                'date_arrivee'  => $covoiturage->getDateArrivee()->format('Y-m-d'),
                'heure_arrivee' => $covoiturage->getHeureArrivee()->format('H:i:s'),
                'lieu_arrivee'  => $covoiturage->getLieuArrivee(),
                'nb_place'      => $covoiturage->getNbPlace(),
                'prix_personne' => $covoiturage->getPrixPersonne(),
                'voiture_id'    => $covoiturage->getVoiture()?->getId(),
                'statut'        => $statut,
                'id'            => $id,
            ]);

            $this->addFlash('success', 'Covoiturage modifié avec succès.');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('covoiturage/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    #[Route('/covoiturage/{id}/supprimer', name: 'supprimer_covoiturage', methods: ['POST'])]
    public function supprimer(Request $request, int $id): Response
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("DELETE FROM covoiturage WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $this->addFlash('success', 'Covoiturage supprimé.');
        return $this->redirectToRoute('app_profil');
    }


}
