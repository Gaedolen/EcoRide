<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Voiture;
use App\Entity\User;
use App\Form\ProfilType;
use App\Form\VoitureType;
use App\Repository\AvisRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use DateTime;
use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function profil(Request $request, FormFactoryInterface $formFactory,EntityManagerInterface $em, AvisRepository $avisRepository): Response
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir votre profil.');
        }

        $photoBase64 = $utilisateur->getPhotoData();

        $pdo = new PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

        // Récupération des voitures
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :id");
        $stmt->execute(['id' => $utilisateur->getId()]);
        $voituresData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $voitureForms = [];
        foreach ($voituresData as $index => $row) {
            $voiture = new Voiture();
            $voiture
                ->setId((int) $row['id'])
                ->setMarque($row['marque'])
                ->setModele($row['modele'])
                ->setImmatriculation($row['immatriculation'])
                ->setDatePremiereImmatriculation(new DateTime($row['date_premiere_immatriculation']))
                ->setNbPlaces((int)$row['nb_places'])
                ->setEnergie($row['energie'])
                ->setCouleur($row['couleur'])
                ->setFumeur((bool) $row['fumeur'])
                ->setAnimaux((bool) $row['animaux']);

            $form = $formFactory->createNamed("voiture_{$index}", VoitureType::class, $voiture);
            $voitureForms[] = $form->createView();
        }

        // Récupération des covoiturages
        $covoiturages = [];
        if ($utilisateur->isChauffeur()) {
            $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE utilisateur_id = :id");
            $stmt->execute(['id' => $utilisateur->getId()]);
            $covoiturages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Récupération des avis
        $avisValides = $avisRepository->findBy([
            'cible'=> $utilisateur,
            'isValidated'=> true,
        ]);

        //Ajouter la photo en base64 dans les auteurs
        foreach($avisValides as $avis) {
            $auteur = $avis->getAuteur();
            if ($auteur) {
                $photo = $auteur->getPhoto();
                $auteur->photoBase64 = $photo ? base64_encode(stream_get_contents($photo)) : null;
            }
        }

        // Récupération des réservations de l'utilisateur
        $stmt = $pdo->prepare("
            SELECT 
                r.id AS reservation_id,
                c.id AS covoiturage_id,
                c.utilisateur_id,
                c.voiture_id,
                c.date_depart,
                c.heure_depart,
                c.lieu_depart,
                c.date_arrivee,
                c.heure_arrivee,
                c.lieu_arrivee,
                c.statut,
                c.nb_place,
                c.prix_personne,
                c.etat,
                u.pseudo AS chauffeur_pseudo,
                u.photo AS chauffeur_photo,
                u.note AS chauffeur_note,
                u.id AS chauffeur_id,
                v.marque,
                v.modele,
                v.energie AS voiture_energie,
                v.couleur,
                v.nb_places,
                v.fumeur,
                v.animaux
            FROM reservation r
            JOIN covoiturage c ON r.covoiturage_id = c.id
            JOIN user u ON u.id = c.utilisateur_id
            JOIN voiture v ON v.id = c.voiture_id
            WHERE r.utilisateur_id = :user_id
        ");

        $stmt->execute(['user_id' => $utilisateur->getId()]);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reservations as &$resa) {
            if (!empty($resa['chauffeur_photo'])) {
                $resa['chauffeur_photo'] = base64_encode($resa['chauffeur_photo']);
            } else {
                $resa['chauffeur_photo'] = null;
            }

            // Conversion dates/heures
            $resa['date_depart'] = new DateTime($resa['date_depart']);
            $resa['heure_depart'] = new DateTime($resa['heure_depart']);
            $resa['date_arrivee'] = new DateTime($resa['date_arrivee']);
            $resa['heure_arrivee'] = new DateTime($resa['heure_arrivee']);

            $finTrajet = clone $resa['date_arrivee'];
            $heureArrivee = $resa['heure_arrivee']->format('H:i:s');
            $finTrajet->setTime(...explode(':', $heureArrivee));
            $resa['fin_trajet'] = $finTrajet;

            $resa['est_passe'] = $finTrajet < new DateTime();

            $chauffeurId = $resa['chauffeur_id'];
            $chauffeur = $em->getRepository(User::class)->find($chauffeurId);

            $stmtAvis = $pdo->prepare("
                SELECT * FROM avis 
                WHERE auteur_id = :auteur_id AND cible_id = :cible_id
                LIMIT 1
            ");
            $stmtAvis->execute([
                'auteur_id' => $utilisateur->getId(),
                'cible_id' => $resa['chauffeur_id']
            ]);

            $avis = $stmtAvis->fetch(PDO::FETCH_ASSOC);
            $resa['avis_existant'] = $avis ?: null;
        }

        $avisDonnes = $utilisateur->getAvisDonnes();

        return $this->render('profil/profil.html.twig', [
            'user' => $utilisateur, 
            'voitureForms' => $voitureForms,
            'voituresData' => $voituresData,
            'photoBase64' => $photoBase64, 
            'covoiturages' => $covoiturages,
            'reservations' => $reservations,
            'avisDonnes' => $avisDonnes,
            'avisValides' => $avisValides,
        ]);
    }


    #[Route('/profil/modifier', name: 'modifier_profil')]
    public function modifierProfil(Request $request): Response
    {
        /** @var \App\Entity\User $sessionUser */
        $sessionUser = $this->getUser(); 

        if (!$sessionUser) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour modifier votre profil.');
        }

        $pdo = new PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $form = $this->createForm(ProfilType::class, $sessionUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData(); 
            $deletePhoto = $form->get('deletePhoto')->getData(); 

            $binaryPhotoContent = null; 

            if ($deletePhoto) {
                $binaryPhotoContent = null; 
            } elseif ($photoFile) {
                $binaryPhotoContent = file_get_contents($photoFile->getPathname()); 
            } else {
                $currentPhoto = $sessionUser->getPhoto();
                if ($currentPhoto && is_resource($currentPhoto)) {
                    $binaryPhotoContent = stream_get_contents($currentPhoto);
                } elseif ($currentPhoto) {
                    $binaryPhotoContent = $currentPhoto;
                }
            }

            $sessionUser->setPhoto($binaryPhotoContent);

            // Préparer la requête UPDATE pour PDO.
            $setClauses = [
                'pseudo = :pseudo',
                'nom = :nom',
                'prenom = :prenom',
                'adresse = :adresse',
                'telephone = :telephone',
                'is_chauffeur = :isChauffeur',
                'is_passager = :isPassager',
                'date_naissance = :dateNaissance',
                'photo = :photo',
            ];

            $sql = "UPDATE user SET " . implode(", ", $setClauses) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            if ($binaryPhotoContent === null) {
                $stmt->bindValue(':photo', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':photo', $binaryPhotoContent, PDO::PARAM_LOB);
            }

            // Lie les autres paramètres
            $stmt->bindValue(':pseudo', $sessionUser->getPseudo());
            $stmt->bindValue(':nom', $sessionUser->getNom());
            $stmt->bindValue(':prenom', $sessionUser->getPrenom());
            $stmt->bindValue(':adresse', $sessionUser->getAdresse());
            $stmt->bindValue(':telephone', $sessionUser->getTelephone());
            $stmt->bindValue(':isChauffeur', $sessionUser->isChauffeur() ? 1 : 0);
            $stmt->bindValue(':isPassager', $sessionUser->isPassager() ? 1 : 0);
            $stmt->bindValue(':dateNaissance', $sessionUser->getDateNaissance()?->format('Y-m-d'));
            $stmt->bindValue(':id', $sessionUser->getId());

            $stmt->execute(); // Exécute la requête après avoir lié tous les paramètres

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profil');
        }

        $currentPhotoBase64 = null;
        $currentPhotoBase64 = $sessionUser->getPhotoData();

        return $this->render('profil/modifier.html.twig', [
            'form' => $form->createView(),
            'user' => $sessionUser,
            'currentPhotoBase64' => $sessionUser->getPhotoData(),
        ]);
    }

    #[Route('/reservation/{id}/annuler', name: 'annuler_reservation', methods: ['POST'])]
    public function annulerReservation(int $id): RedirectResponse
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur) {
            throw $this->createAccessDeniedException("Vous devez être connecté.");
        }

        $pdo = new PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Récupérer la réservation
        $stmt = $pdo->prepare("SELECT * FROM reservation WHERE id = :id AND utilisateur_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $utilisateur->getId()]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée.');
        }

        $covoiturageId = $reservation['covoiturage_id'];

        // Supprimer la réservation
        $stmt = $pdo->prepare("DELETE FROM reservation WHERE id = :id");
        $stmt->execute(['id' => $id]);

        // Réaugmenter le nombre de places disponibles du covoiturage
        $stmt = $pdo->prepare("UPDATE covoiturage SET nb_place = nb_place + 1 WHERE id = :id");
        $stmt->execute(['id' => $covoiturageId]);

        return $this->redirectToRoute('app_profil');
    }


    #[Route('/laisser-avis', name: 'laisser_avis', methods: ['POST'])]
    public function laisserAvis(Request $request): Response
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            throw $this->createAccessDeniedException("Vous devez être connecté.");
        }

        $cibleId = $request->request->get('cible_id');
        $covoiturageId = $request->request->get('covoiturage_id');
        $note = (int) $request->request->get('note');
        $commentaire = $request->request->get('commentaire');

        if (!$cibleId || !$covoiturageId || !$note || !$commentaire) {
            throw new \Exception("Champs manquants.");
        }

        // Connexion PDO (adapté à ta config)
        $pdo = new PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Vérifie si l'avis existe déjà pour cet utilisateur et ce covoiturage
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM avis WHERE auteur_id = :auteur_id AND covoiturage_id = :covoiturage_id");
        $stmtCheck->execute([
            ':auteur_id' => $utilisateur->getId(),
            ':covoiturage_id' => $covoiturageId,
        ]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new \Exception("Vous avez déjà laissé un avis pour ce covoiturage.");
        }

        // Insertion de l'avis
        $stmt = $pdo->prepare("INSERT INTO avis (auteur_id, cible_id, covoiturage_id, note, commentaire, date_avis, statut, is_validated) VALUES (:auteur_id, :cible_id, :covoiturage_id, :note, :commentaire, NOW(), :statut, :is_validated)");
        $stmt->execute([
            ':auteur_id' => $utilisateur->getId(),
            ':cible_id' => $cibleId,
            ':covoiturage_id' => $covoiturageId,
            ':note' => $note,
            ':commentaire' => $commentaire,
            ':statut' => 'en_attente_validation',
            ':is_validated' => 0,
        ]);

        return $this->redirectToRoute('app_profil');
    }
}