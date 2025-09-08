<?php

namespace App\Controller;

use App\Entity\Voiture;
use App\Form\VoitureType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VoitureController extends AbstractController
{
    #[Route('/ajouter-voiture', name: 'ajouter_voiture', methods: ['GET', 'POST'])]
    public function ajouterVoiture(Request $request): Response
    {
        $voiture = new Voiture();
        $form = $this->createForm(VoitureType::class, $voiture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \App\Entity\User $utilisateur */
            $utilisateur = $this->getUser();
            if (!$utilisateur) {
                $this->addFlash('error', 'Vous devez être connecté pour ajouter une voiture.');
                return $this->redirectToRoute('app_login');
            }

            $preferences = $voiture->getPreferences(); // tableau JSON
            $preferencesJson = !empty($preferences) ? json_encode($preferences, JSON_UNESCAPED_UNICODE) : null;

            try {
                $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $stmt = $pdo->prepare("
                    INSERT INTO voiture 
                        (immatriculation, date_premiere_immatriculation, marque, modele, nb_places, fumeur, animaux, couleur, energie, utilisateur_id, preferences)
                    VALUES 
                        (:immatriculation, :date_premiere_immatriculation, :marque, :modele, :nb_places, :fumeur, :animaux, :couleur, :energie, :utilisateur_id, :preferences)
                ");

                $stmt->execute([
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
                    'preferences' => $preferencesJson,
                ]);

                $this->addFlash('success', 'Voiture ajoutée avec succès !');
                return $this->redirectToRoute('app_profil');

            } catch (\PDOException $e) {
                $this->addFlash('error', 'Erreur lors de l\'insertion : ' . $e->getMessage());
                return $this->redirectToRoute('ajouter_voiture');
            }
        }

        return $this->render('voiture/ajouter.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/voiture/modifier/{id}', name: 'modifier_voiture', methods: ['GET', 'POST'])]
    public function modifierVoiture(Request $request, int $id): Response
    {
        $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Récupérer la voiture
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            throw $this->createNotFoundException('Voiture introuvable');
        }

        $voiture = new Voiture();
        $voiture
            ->setMarque($row['marque'])
            ->setModele($row['modele'])
            ->setImmatriculation($row['immatriculation'])
            ->setDatePremiereImmatriculation(new \DateTime($row['date_premiere_immatriculation']))
            ->setNbPlaces($row['nb_places'])
            ->setFumeur((bool) $row['fumeur'])
            ->setAnimaux((bool) $row['animaux'])
            ->setCouleur($row['couleur'])
            ->setEnergie($row['energie'])
            ->setPreferences($row['preferences'] ? json_decode($row['preferences'], true) : []);

        $form = $this->createForm(VoitureType::class, $voiture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $preferences = $voiture->getPreferences();
            $preferencesJson = !empty($preferences) ? json_encode($preferences, JSON_UNESCAPED_UNICODE) : null;

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
                    energie = :energie,
                    preferences = :preferences
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
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
                'preferences' => $preferencesJson,
            ]);

            $this->addFlash('success', 'Voiture modifiée avec succès !');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('voiture/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/voiture/supprimer/{id}', name: 'supprimer_voiture', methods: ['POST'])]
    public function supprimerVoiture(int $id, MailerInterface $mailer): Response
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // Récupérer les covoiturages liés à la voiture
        $stmt = $pdo->prepare("SELECT id, lieu_depart, lieu_arrivee, date_depart FROM covoiturage WHERE voiture_id = :id");
        $stmt->execute(['id' => $id]);
        $covoiturages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($covoiturages as $covoiturage) {
            $covoiturageId = $covoiturage['id'];

            // Récupérer les passagers de ce covoiturage
            $stmtPassagers = $pdo->prepare("
                SELECT u.email, u.pseudo 
                FROM reservation r
                JOIN user u ON r.utilisateur_id = u.id
                WHERE r.covoiturage_id = :covoiturage_id
            ");
            $stmtPassagers->execute(['covoiturage_id' => $covoiturageId]);
            $passagers = $stmtPassagers->fetchAll(\PDO::FETCH_ASSOC);

            // Envoyer un mail à chaque passager
            foreach ($passagers as $passager) {
                $email = (new TemplatedEmail())
                    ->from('contact@ecoride.com')
                    ->to($passager['email'])
                    ->subject('Annulation de votre covoiturage')
                    ->htmlTemplate('emails/annulation_covoiturage.html.twig')
                    ->context([
                        'pseudo' => $passager['pseudo'],
                        'trajet' => $covoiturage
                    ]);

                $mailer->send($email);
            }

            // Supprimer les réservations liées
            $stmtDeleteReservations = $pdo->prepare("DELETE FROM reservation WHERE covoiturage_id = :covoiturage_id");
            $stmtDeleteReservations->execute(['covoiturage_id' => $covoiturageId]);

            // Supprimer le covoiturage
            $stmtDeleteCovoiturage = $pdo->prepare("DELETE FROM covoiturage WHERE id = :id");
            $stmtDeleteCovoiturage->execute(['id' => $covoiturageId]);
        }

        // Supprimer la voiture
        $stmtDeleteVoiture = $pdo->prepare("DELETE FROM voiture WHERE id = :id AND utilisateur_id = :utilisateur_id");
        $stmtDeleteVoiture->execute([
            'id' => $id,
            'utilisateur_id' => $utilisateur->getId(),
        ]);

        $this->addFlash('success', 'La voiture et ses covoiturages ont été supprimés. Les passagers ont été prévenus.');
        return $this->redirectToRoute('app_profil');
    }
}
