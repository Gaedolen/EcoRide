<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use App\Form\CovoiturageType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Covoiturage;
use App\Entity\Reservation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use \PDO;

class CovoiturageController extends AbstractController
{
    #[Route('/covoiturage', name: 'app_covoiturage')]
    public function rechercher(Request $request): Response
    {
        // Paramètres de recherche
        $depart = $request->query->get('lieu_depart');
        $arrivee = $request->query->get('lieu_arrivee');
        $date = $request->query->get('date_depart');
        $heure = $request->query->get('heure_depart');

        // Paramètres de filtre
        $noteMin = $request->query->get('note_min');
        $ecologique = $request->query->get('ecologique');
        $prixMin = $request->query->get('prix_min');
        $prixMax = $request->query->get('prix_max');
        $heuresMax = (int) $request->query->get('temps_max_heures');
        $minutesMax = (int) $request->query->get('temps_max_minutes');
        $tempsMaxTotal = ($heuresMax * 60) + $minutesMax;

        // Connexion PDO
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Construction dynamique de la requête SQL
        $sql = "
            SELECT 
                c.*, 
                u.pseudo AS user_pseudo,
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
            AND c.statut = 'ouvert'
        ";

        $params = [
            ':depart' => $depart,
            ':arrivee' => $arrivee,
            ':date' => $date,
            ':heure' => $heure,
        ];

        // Filtres dynamiques
        if ($noteMin !== null && $noteMin !== '') {
            $sql .= " AND u.note >= :note_min";
            $params[':note_min'] = (int)$noteMin;
        }

        if ($ecologique === '1') {
            $sql .= " AND v.energie IN ('Électrique', 'Electrique')";
        }

        if ($prixMin !== null && $prixMin !== '') {
            $sql .= " AND c.prix_personne >= :prix_min";
            $params[':prix_min'] = (float)$prixMin;
        }

        if ($prixMax !== null && $prixMax !== '') {
            $sql .= " AND c.prix_personne <= :prix_max";
            $params[':prix_max'] = (float)$prixMax;
        }

        if ($tempsMaxTotal > 0) {
            $sql .= " AND TIME_TO_SEC(TIMEDIFF(c.heure_arrivee, c.heure_depart)) <= :temps_max_sec";
            $params[':temps_max_sec'] = $tempsMaxTotal * 60;
        }

        // Préparation et exécution
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Traitement des trajets
        foreach ($trajets as &$trajet) {
            $trajet['user_photo'] = !empty($trajet['user_photo']) ? base64_encode($trajet['user_photo']) : null;
            $trajet['heure_depart'] = new DateTime($trajet['heure_depart']);
            $trajet['heure_arrivee'] = new DateTime($trajet['heure_arrivee']);
            $trajet['date_depart'] = new DateTime($trajet['date_depart']);
            $trajet['date_arrivee'] = new DateTime($trajet['date_arrivee']);
        }

        return $this->render('covoiturage/resultats.html.twig', [
            'trajets' => $trajets
        ]);
    }


    #[Route('/covoiturage/{id}', name: 'details_trajet', requirements: ['id' => '\d+'])]
    public function details(int $id): Response
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            SELECT 
                c.*, 
                u.pseudo AS chauffeur_pseudo,
                u.note AS chauffeur_note,
                u.photo AS chauffeur_photo,
                v.marque, v.modele, v.energie, v.nb_places, v.couleur, v.animaux, v.fumeur
            FROM covoiturage c
            JOIN user u ON u.id = c.utilisateur_id
            JOIN voiture v ON v.id = c.voiture_id
            WHERE c.id = :id
        ");

        $stmt->execute(['id' => $id]);
        $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trajet) {
            throw $this->createNotFoundException("Ce covoiturage n'existe pas.");
        }

        // Encodage de la photo s'il y en a une
        if (!empty($trajet['chauffeur_photo'])) {
            $trajet['chauffeur_photo'] = base64_encode($trajet['chauffeur_photo']);
        } else {
            $trajet['chauffeur_photo'] = null;
        }

        // Convertir les dates et heures
        $trajet['date_depart'] = new DateTime($trajet['date_depart']);
        $trajet['heure_depart'] = new DateTime($trajet['heure_depart']);
        $trajet['date_arrivee'] = new DateTime($trajet['date_arrivee']);
        $trajet['heure_arrivee'] = new DateTime($trajet['heure_arrivee']);

        // Récupération des avis sur le conducteur
        $stmt = $pdo->prepare("
            SELECT a.note, a.commentaire, u.pseudo AS auteur_pseudo
            FROM avis a
            JOIN user u ON u.id = a.auteur_id
            WHERE a.cible_id = :conducteur_id
            ORDER BY a.id DESC
        ");
        $stmt->execute(['conducteur_id' => $trajet['utilisateur_id']]);
        $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);


        return $this->render('covoiturage/details.html.twig', [
            'trajet' => $trajet,
            'avis' => $avis,
        ]);
    }

    #[Route('/covoiturage/{id}/reserver', name: 'reserver_trajet', methods: ['POST'])]
    public function reserver(int $id): Response
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Vérifier si le covoiturage existe et a des places
        $stmt = $pdo->prepare("SELECT nb_place FROM covoiturage WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $covoiturage = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$covoiturage || $covoiturage['nb_place'] <= 0) {
            $this->addFlash('error', 'Ce covoiturage n’est plus disponible.');
            return $this->redirectToRoute('details_trajet', ['id' => $id]);
        }

        try {
            // Insérer la réservation
            $stmt = $pdo->prepare("
                INSERT INTO reservation (utilisateur_id, covoiturage_id)
                VALUES (:utilisateur_id, :covoiturage_id)
            ");
            $stmt->execute([
                'utilisateur_id' => $utilisateur->getId(),
                'covoiturage_id' => $id
            ]);

            // Décrémenter le nombre de places
            $stmt = $pdo->prepare("UPDATE covoiturage SET nb_place = nb_place - 1 WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Re-vérifier le nombre de places restantes
            $stmt = $pdo->prepare("SELECT nb_place FROM covoiturage WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $updated = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($updated && (int)$updated['nb_place'] === 0) {
                // Mettre à jour le statut en 'complet'
                $stmt = $pdo->prepare("UPDATE covoiturage SET statut = 'complet' WHERE id = :id");
                $stmt->execute(['id' => $id]);
            }

            $this->addFlash('success', 'Réservation confirmée !');

        } catch (\PDOException $e) {
            $this->addFlash('error', 'Vous avez déjà réservé ce covoiturage.');
        }

        return $this->redirectToRoute('details_trajet', [
            'id' => $id,
            'reserved' => 1,
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
        $covoiturage->setHeureDepart(new DateTime('12:00'));
        $covoiturage->setHeureArrivee(new DateTime('12:00'));

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

            $now = new DateTime();
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

            $etat = 'a_venir'; // état initial par défaut du covoiturage

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
                    etat,
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
                    :etat,
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
                'voiture_id'    => $covoiturage->getVoiture()->getId(),
                'statut'        => $statut,
                'etat'          => $etat,
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
            ->setDateDepart(new DateTime($row['date_depart']))
            ->setHeureDepart(new DateTime($row['heure_depart']))
            ->setLieuDepart($row['lieu_depart'])
            ->setDateArrivee(new DateTime($row['date_arrivee']))
            ->setHeureArrivee(new DateTime($row['heure_arrivee']))
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
            $now = new DateTime();
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
    public function supprimer(Covoiturage $covoiturage, EntityManagerInterface $em): Response
    {
        $em->remove($covoiturage);
        $em->flush();

        $this->addFlash('success', 'Covoiturage supprimé.');
        return $this->redirectToRoute('app_profil');
    }

    #[Route('/reservation/{id}/annuler', name: 'annuler_reservation', methods: ['POST'])]
    public function annulerReservation(int $id, ManagerRegistry $doctrine): Response
    {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Récupérer le covoiturage lié à la réservation
        $stmt = $pdo->prepare("SELECT covoiturage_id FROM reservation WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $covoiturageId = $result['covoiturage_id'];

            // Supprimer la réservation
            $stmt = $pdo->prepare("DELETE FROM reservation WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Ré-incrémentation des places
            $stmt = $pdo->prepare("UPDATE covoiturage SET nb_place = nb_place + 1 WHERE id = :id");
            $stmt->execute(['id' => $covoiturageId]);

            // Mettre à jour le statut
            $em = $doctrine->getManager();
            $covoiturageEntity = $em->getRepository(Covoiturage::class)->find($covoiturageId);

            if ($covoiturageEntity) {
                $covoiturageEntity->updateStatutSelonPlaces(); // repasse à "ouvert"
                $em->flush();
            }
        }

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/chauffeur/covoiturage/{id}/demarrer', name: 'chauffeur_covoiturage_demarrer')]
    public function demarrerCovoiturage(Covoiturage $covoiturage, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!in_array($covoiturage->getStatut(), [Covoiturage::STATUT_OUVERT, Covoiturage::STATUT_COMPLET])) {
            $this->addFlash('warning', 'Ce covoiturage ne peut pas être démarré.');
            return $this->redirectToRoute('app_profil');
        }

        $covoiturage->setStatut(Covoiturage::STATUT_EN_COURS);
        $em->flush();

        $this->addFlash('success', 'Le covoiturage a bien démarré.');
        return $this->redirectToRoute('app_profil');
    }

    #[Route('/chauffeur/covoiturage/{id}/clore', name: 'chauffeur_covoiturage_clore')]
    public function cloreCovoiturage(Covoiturage $covoiturage, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        if ($covoiturage->getStatut() !== Covoiturage::STATUT_EN_COURS) {
            $this->addFlash('warning', 'Ce covoiturage ne peut pas être clôturé.');
            return $this->redirectToRoute('app_profil');
        }

        $covoiturage->setStatut(Covoiturage::STATUT_FERME);
        $em->flush();

        $reservations = $covoiturage->getReservations();

        foreach ($reservations as $reservation) {
            $participant = $reservation->getUtilisateur();

            if (!$participant || !$participant->getEmail()) {
                continue;
            }

            $email = (new TemplatedEmail())
                ->from('no-reply@ecoride.com')
                ->to($participant->getEmail())
                ->subject('Merci de confirmer le bon déroulement du trajet')
                ->htmlTemplate('emails/confirmation_trajet.html.twig')
                ->context([
                    'participant' => $participant,
                    'covoiturage' => $covoiturage,
                ]);

            try {
                $mailer->send($email);
            } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                $logger->error('Erreur d\'envoi de mail : ' . $e->getMessage());
            }
        }

        $this->addFlash('success', 'Le covoiturage a été clôturé et les mails ont été envoyés.');
        return $this->redirectToRoute('app_profil');
    }
}
