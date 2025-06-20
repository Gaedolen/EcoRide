<?php

namespace App\Controller;

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

        //Connexion PDO manuelle
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //Requête préparée sécurisée
        $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE lieu_depart = :depart AND lieu_arrivee = :arrivee AND date_depart = :date AND heure_depart >= :heure");

        $stmt->execute([
            ':depart' => $depart,
            ':arrivee' => $arrivee,
            ':date' => $date,
            ':heure' => $heure,
        ]);

        $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
        return $this->render('covoiturage/resultats.html.twig', [
            'controller_name' => 'CovoiturageController',
            'trajets' => $trajets,
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

        $form = $this->createForm(CovoiturageType::class, $covoiturage, [
            'user' => $user
        ]);
        $form->handleRequest($request);

        

        if ($form->isSubmitted() && $form->isValid()) {
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $now = new \DateTime();
            $date = $covoiturage->getDateDepart();
            $heure = $covoiturage->getHeureDepart();

           $isPast = ($date < $now) || ($date == $now && $heure < $now);
           $places = $covoiturage->getNbPlace();

            if ($isPast) {
               $statut = 'ferme';
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
                'heure_arrivee' => $covoiturage->getHeureArrivee(),
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

    #[Route('/covoiturage/{id}/modifier', name: 'modifier_covoiturage')]
    public function modifierCovoiturage(Request $request, int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        /** @var \App\Entity\User $user */

        if (!$user || !$user->isChauffeur()) {
            throw $this->createAccessDeniedException('Vous devez être chauffeur pour modifier un covoiturage.');
        }

        // Connexion PDO
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Récupération des données existantes
        $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            throw $this->createNotFoundException('Covoiturage introuvable.');
        }

        // Création de l'objet Covoiturage
        $covoiturage = new Covoiturage();
        $covoiturage->setDateDepart(new \DateTime($data['date_depart']));
        $covoiturage->setHeureDepart(new \DateTime($data['heure_depart']));
        $covoiturage->setLieuDepart($data['lieu_depart']);
        $covoiturage->setDateArrivee(new \DateTime($data['date_arrivee']));
        $covoiturage->setHeureArrivee($data['heure_arrivee']);
        $covoiturage->setLieuArrivee($data['lieu_arrivee']);
        $covoiturage->setNbPlace((int) $data['nb_place']);
        $covoiturage->setPrixPersonne((float) $data['prix_personne']);

        // Récupération de la voiture via Doctrine
        if (!empty($data['voiture_id'])) {
            $voiture = $em->getRepository(\App\Entity\Voiture::class)->find($data['voiture_id']);
            if ($voiture) {
                $covoiturage->setVoiture($voiture);
            }
        }

        // Création du formulaire
        $form = $this->createForm(CovoiturageType::class, $covoiturage, [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Déterminer automatiquement le nouveau statut
            $now = new \DateTime();
            $date = $covoiturage->getDateDepart();
            $heure = $covoiturage->getHeureDepart();

            $isPast = ($date < $now) || ($date == $now && $heure < $now);
            $places = $covoiturage->getNbPlace();

            if ($isPast) {
                $statut = 'ferme';
            } elseif ($places <= 0) {
                $statut = 'complet';
            } else {
                $statut = 'ouvert';
            }

            // Mise à jour des données
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
                'date_depart'   => $date->format('Y-m-d'),
                'heure_depart'  => $heure->format('H:i:s'),
                'lieu_depart'   => $covoiturage->getLieuDepart(),
                'date_arrivee'  => $covoiturage->getDateArrivee()->format('Y-m-d'),
                'heure_arrivee' => $covoiturage->getHeureArrivee(),
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
            'covoiturage' => $covoiturage,
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
