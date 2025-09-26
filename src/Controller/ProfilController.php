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
use App\Service\PdoService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfilController extends AbstractController
{
    private PDO $pdo;
    public function __construct(PdoService $pdoService)
    {
        $this->pdo = $pdoService->getConnection();
    }

    #[Route('/profil', name: 'app_profil')]
    public function profil(Request $request, FormFactoryInterface $formFactory, EntityManagerInterface $em, AvisRepository $avisRepository): Response {

        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir votre profil.');
        }

        $photoBase64 = $utilisateur->getPhotoData();

        $pdo = $this->pdo;

        // Récupération des voitures
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :id");
        $stmt->execute(['id' => $utilisateur->getId()]);
        $voituresData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Décodage des préférences JSON
        foreach ($voituresData as &$row) {
            $preferences = [];
            if (!empty($row['preferences'])) {
                $decoded = json_decode($row['preferences'], true);

                // Cas d’un double encodage JSON
                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }

                if (is_array($decoded)) {
                    $preferences = $decoded;
                }
            }
            $row['preferences'] = $preferences;
        }

        // Récupération des covoiturages actifs
        $covoiturages = [];
        if ($utilisateur->isChauffeur()) {
            $stmt = $pdo->prepare("
                SELECT 
                    c.*, 
                    COUNT(r.id) AS reservations_count
                FROM covoiturage c
                LEFT JOIN reservation r ON r.covoiturage_id = c.id
                WHERE c.utilisateur_id = :id 
                AND c.statut IN ('ouvert', 'en_cours', 'complet')
                GROUP BY c.id
                ORDER BY c.date_depart ASC, c.heure_depart ASC
            ");
            $stmt->execute(['id' => $utilisateur->getId()]);
            $covoiturages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Récupération des avis validés
        $avisValides = $avisRepository->findBy([
            'cible' => $utilisateur,
            'isValidated' => true,
        ]);

        foreach ($avisValides as $avis) {
            $auteur = $avis->getAuteur();
            if ($auteur) {
                $photo = $auteur->getPhoto();
                if ($photo) {
                    if (is_resource($photo)) {
                        rewind($photo);
                        $content = stream_get_contents($photo);
                    } else {
                        $content = $photo;
                    }
                    $auteur->auteurPhotoBase64 = base64_encode($content);
                } else {
                    $auteur->auteurPhotoBase64 = null;
                }
            }
        }

        // Récupération des réservations
        $stmt = $pdo->prepare("
            SELECT 
                r.id AS reservation_id,
                c.id AS covoiturage_id,
                c.utilisateur_id AS chauffeur_id,
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
                v.animaux,
                v.preferences
            FROM reservation r
            JOIN covoiturage c ON r.covoiturage_id = c.id
            JOIN utilisateurs u ON u.id = c.utilisateur_id
            JOIN voiture v ON v.id = c.voiture_id
            WHERE r.utilisateur_id = :user_id
            AND c.statut != 'ferme'
            ORDER BY c.date_depart ASC, c.heure_depart ASC
        ");
        $stmt->execute(['user_id' => $utilisateur->getId()]);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reservations as &$resa) {
            // Photo chauffeur
            $resa['chauffeur_photo'] = !empty($resa['chauffeur_photo'])
                ? base64_encode($resa['chauffeur_photo'])
                : null;

            // Conversion des dates et heures
            $resa['date_depart'] = new DateTime($resa['date_depart']);
            $resa['heure_depart'] = new DateTime($resa['heure_depart']);
            $resa['date_arrivee'] = new DateTime($resa['date_arrivee']);
            $resa['heure_arrivee'] = new DateTime($resa['heure_arrivee']);

            $finTrajet = clone $resa['date_arrivee'];
            $heureArrivee = $resa['heure_arrivee']->format('H:i:s');
            $finTrajet->setTime(...explode(':', $heureArrivee));
            $resa['fin_trajet'] = $finTrajet;

            $resa['est_passe'] = $finTrajet < new DateTime();

            // Vérifier si un avis existe déjà
            $stmtAvis = $pdo->prepare("
                SELECT * FROM avis 
                WHERE auteur_id = :auteur_id 
                AND cible_id = :cible_id
                AND covoiturage_id = :covoiturage_id
                LIMIT 1
            ");
            $stmtAvis->execute([
                'auteur_id' => $utilisateur->getId(),
                'cible_id' => $resa['chauffeur_id'],
                'covoiturage_id' => $resa['covoiturage_id'],
            ]);
            $resa['avis_existant'] = $stmtAvis->fetch(PDO::FETCH_ASSOC) ?: null;

            // Vérifier si un signalement existe déjà
            $stmtReport = $pdo->prepare("
                SELECT * FROM report
                WHERE reported_by_id = :reported_by_id
                AND reported_user_id = :reported_user_id
                AND covoiturage_id = :covoiturage_id
                LIMIT 1
            ");
            $stmtReport->execute([
                'reported_by_id' => $utilisateur->getId(),
                'reported_user_id' => $resa['chauffeur_id'],
                'covoiturage_id' => $resa['covoiturage_id'],
            ]);
            $resa['signalement_existant'] = $stmtReport->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $avisDonnes = $utilisateur->getAvisDonnes();

        return $this->render('profil/profil.html.twig', [
            'user' => $utilisateur,
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
                // Suppression de la photo
                $binaryPhotoContent = null;
            } elseif ($photoFile) {
                // Vérification du type MIME de la photo
                $mimeType = $photoFile->getMimeType();
                $allowedTypes = ['image/jpeg', 'image/png'];

                if (!in_array($mimeType, $allowedTypes, true)) {
                    $this->addFlash('error', 'Format d\'image non autorisé. Seuls JPG et PNG sont acceptés.');
                    return $this->redirectToRoute('modifier_profil');
                }

                // Lecture binaire de la photo
                $binaryPhotoContent = file_get_contents($photoFile->getPathname());
            } else {
                // Conserver la photo actuelle si elle existe
                $currentPhoto = $sessionUser->getPhoto();
                if ($currentPhoto && is_resource($currentPhoto)) {
                    $binaryPhotoContent = stream_get_contents($currentPhoto);
                } elseif ($currentPhoto) {
                    $binaryPhotoContent = $currentPhoto;
                }
            }

            $sessionUser->setPhoto($binaryPhotoContent);

            // Requête UPDATE
            $sql = "UPDATE utilisateurs SET 
                        pseudo = :pseudo,
                        nom = :nom,
                        prenom = :prenom,
                        adresse = :adresse,
                        telephone = :telephone,
                        is_chauffeur = :isChauffeur,
                        is_passager = :isPassager,
                        date_naissance = :dateNaissance,
                        photo = :photo
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);

            if ($binaryPhotoContent === null) {
                $stmt->bindValue(':photo', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':photo', $binaryPhotoContent, PDO::PARAM_LOB);
            }

            $stmt->bindValue(':pseudo', $sessionUser->getPseudo());
            $stmt->bindValue(':nom', $sessionUser->getNom());
            $stmt->bindValue(':prenom', $sessionUser->getPrenom());
            $stmt->bindValue(':adresse', $sessionUser->getAdresse());
            $stmt->bindValue(':telephone', $sessionUser->getTelephone());
            $stmt->bindValue(':isChauffeur', $sessionUser->isChauffeur() ? 1 : 0);
            $stmt->bindValue(':isPassager', $sessionUser->isPassager() ? 1 : 0);
            $stmt->bindValue(':dateNaissance', $sessionUser->getDateNaissance()?->format('Y-m-d'));
            $stmt->bindValue(':id', $sessionUser->getId());

            $stmt->execute();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/modifier.html.twig', [
            'form' => $form->createView(),
            'user' => $sessionUser,
            'currentPhotoBase64' => $sessionUser->getPhotoData(),
        ]);
    }

    #[Route('/laisser-avis', name: 'laisser_avis', methods: ['POST'])]
    public function laisserAvis(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return new JsonResponse(['success' => false, 'message' => 'Vous devez être connecté.'], 403);
        }

        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('laisser-avis', $csrfToken)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
        }

        $cibleId = (int) $request->request->get('cible_id');
        $covoiturageId = (int) $request->request->get('covoiturage_id');
        $note = (int) $request->request->get('note');
        $commentaire = trim($request->request->get('commentaire'));

        if (!$cibleId || !$covoiturageId || !$note || !$commentaire) {
            return new JsonResponse(['success' => false, 'message' => 'Champs manquants.'], 400);
        }

        // Vérifier que l'utilisateur a participé
        $stmtCheckParticipation = $this->pdo->prepare("
            SELECT COUNT(*) FROM reservation 
            WHERE utilisateur_id = :userId AND covoiturage_id = :covoiturageId
        ");
        $stmtCheckParticipation->execute([
            'userId' => $utilisateur->getId(),
            'covoiturageId' => $covoiturageId,
        ]);
        if ($stmtCheckParticipation->fetchColumn() == 0) {
            return new JsonResponse(['success' => false, 'message' => 'Vous ne pouvez pas laisser un avis pour ce covoiturage.'], 403);
        }

        // Vérifie si l'avis existe déjà
                $stmtCheck = $this->pdo->prepare("
            SELECT COUNT(*) FROM avis 
            WHERE auteur_id = :auteur_id AND covoiturage_id = :covoiturage_id
        ");
        $stmtCheck->execute([
            ':auteur_id' => $utilisateur->getId(),
            ':covoiturage_id' => $covoiturageId,
        ]);

        $exists = $stmtCheck->fetchColumn();

        if ($exists > 0) {
            return new JsonResponse(['success' => 'exists']);
        }
        
        // Insertion
        $stmt = $this->pdo->prepare("
            INSERT INTO avis 
            (auteur_id, cible_id, covoiturage_id, note, commentaire, date_avis, statut, is_validated) 
            VALUES (:auteur_id, :cible_id, :covoiturage_id, :note, :commentaire, NOW(), :statut, :is_validated)
        ");
        $stmt->execute([
            ':auteur_id' => $utilisateur->getId(),
            ':cible_id' => $cibleId,
            ':covoiturage_id' => $covoiturageId,
            ':note' => $note,
            ':commentaire' => $commentaire,
            ':statut' => 'en_attente_validation',
            ':is_validated' => 0,
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/historique/covoiturages', name: 'historique_covoiturages')]
    public function historiqueCovoiturages(Connection $connection): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos covoiturages.');
        }

        // Récupération des covoiturages terminés dont l'utilisateur est chauffeur
        $sql = "SELECT c.*, v.marque, v.modele, v.immatriculation, v.energie, v.couleur
            FROM covoiturage c
            LEFT JOIN voiture v ON c.voiture_id = v.id
            WHERE c.utilisateur_id = :userId 
            AND c.statut = 'ferme'
            ORDER BY c.date_depart ASC, c.heure_depart ASC";

        $covoiturages = $connection->fetchAllAssociative($sql, [
            'userId' => $user->getId()
        ]);

        return $this->render('profil/historique_covoiturages.html.twig', [
            'covoiturages' => $covoiturages,
        ]);
    }

   #[Route('/historique/reservations', name: 'historique_reservations')]
    public function historiqueReservations(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos réservations.');
        }

        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                c.lieu_depart, c.lieu_arrivee, c.date_depart, c.heure_depart, 
                c.date_arrivee, c.heure_arrivee, c.nb_place, c.prix_personne, c.statut AS covoiturage_statut,
                v.marque, v.modele, v.energie AS voiture_energie, v.couleur,
                u.pseudo AS chauffeur_pseudo, u.note AS chauffeur_note, u.photo AS chauffeur_photo,
                u.id AS chauffeur_id
            FROM reservation r
            JOIN covoiturage c ON r.covoiturage_id = c.id
            JOIN utilisateurs u ON u.id = c.utilisateur_id
            JOIN voiture v ON v.id = c.voiture_id
            WHERE r.utilisateur_id = :userId
            AND c.statut = 'ferme'
            ORDER BY c.date_depart DESC
        ");
        $stmt->execute(['userId' => $user->getId()]);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reservations as &$resa) {
            $resa['chauffeur_photo'] = $resa['chauffeur_photo'] ? base64_encode($resa['chauffeur_photo']) : null;

            // Conversion des dates et heures
            $resa['date_depart'] = new DateTime($resa['date_depart']);
            $resa['heure_depart'] = new DateTime($resa['heure_depart']);
            $resa['date_arrivee'] = new DateTime($resa['date_arrivee']);
            $resa['heure_arrivee'] = new DateTime($resa['heure_arrivee']);

            // Avis existant
            $stmtAvis = $this->pdo->prepare("
                SELECT * FROM avis 
                WHERE auteur_id = :auteur_id 
                AND covoiturage_id = :covoiturage_id
                LIMIT 1
            ");
            $stmtAvis->execute([
                'auteur_id' => $user->getId(),
                'covoiturage_id' => $resa['covoiturage_id'],
            ]);
            $resa['avis_existant'] = $stmtAvis->fetch(PDO::FETCH_ASSOC) ?: null;

            // Signalement existant
            $stmtReport = $this->pdo->prepare("
                SELECT * FROM report
                WHERE reported_by_id = :reported_by_id
                AND reported_user_id = :reported_user_id
                AND covoiturage_id = :covoiturage_id
                LIMIT 1
            ");
            $stmtReport->execute([
                'reported_by_id' => $user->getId(),
                'reported_user_id' => $resa['chauffeur_id'],
                'covoiturage_id' => $resa['covoiturage_id'],
            ]);
            $resa['signalement_existant'] = $stmtReport->fetch(PDO::FETCH_ASSOC) ?: null;

            // Calculer la note arrondie
            if (isset($resa['chauffeur_note'])) {
                $resa['chauffeur_note_formatted'] = round($resa['chauffeur_note'], 1);
            } else {
                $resa['chauffeur_note_formatted'] = null;
            }
        }

        return $this->render('profil/historique_reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }
}