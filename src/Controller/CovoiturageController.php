<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mime\Email;
use App\Service\PdoService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use App\Form\CovoiturageType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Covoiturage;
use App\Entity\User;
use App\Entity\Reservation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use \PDO;

class CovoiturageController extends AbstractController
{
    private PDO $pdo;
    public function __construct(PdoService $pdoService)
    {
        $this->pdo = $pdoService->getConnection();
    }

    #[Route('/covoiturage', name: 'app_covoiturage')]
    public function rechercher(Request $request): Response
    {
        // Paramètres de recherche
        $depart = $request->query->get('lieu_depart');
        $arrivee = $request->query->get('lieu_arrivee');
        $date = $request->query->get('date_depart');
        $heure = $request->query->get('heure_depart');

        if ($date) {
            $dateDepart = DateTime::createFromFormat('Y-m-d', $date);
            $aujourdhui = new DateTime('today');
            if ($dateDepart < $aujourdhui) {
                $this->addFlash('error', 'Vous ne pouvez pas rechercher un covoiturage pour une date passée.');
                return $this->redirectToRoute('app_covoiturage');
            }
        }

        $noteMin = $request->query->get('note_min');
        $ecologique = $request->query->get('ecologique');
        $prixMin = $request->query->get('prix_min');
        $prixMax = $request->query->get('prix_max');
        $heuresMax = (int) $request->query->get('temps_max_heures');
        $minutesMax = (int) $request->query->get('temps_max_minutes');
        $tempsMaxTotal = ($heuresMax * 60) + $minutesMax;

        $pdo = $this->pdo;

        // Construction SQL
        $sql = "
            SELECT 
                c.*, 
                u.pseudo AS user_pseudo,
                u.photo AS user_photo,
                u.note AS user_note,
                v.energie AS voiture_energie,
                (c.nb_place - COUNT(r.id)) AS places_disponibles
            FROM covoiturage c
            JOIN utilisateurs u ON u.id = c.utilisateur_id
            JOIN voiture v ON v.id = c.voiture_id
            LEFT JOIN reservation r ON r.covoiturage_id = c.id
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

        // Filtres dynamiques (avant GROUP BY)
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

        // Groupement + limite finale
        $sql .= "
            GROUP BY c.id, u.pseudo, u.photo, u.note, v.energie
            LIMIT 50
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($trajets as &$trajet) {
            if (!empty($trajet['user_photo'])) {
                $photoData = is_resource($trajet['user_photo'])
                    ? stream_get_contents($trajet['user_photo'])
                    : $trajet['user_photo'];
                
                $firstChar = substr($photoData, 0, 1);
                $isBase64 = in_array($firstChar, ['/', 'i', 'R', 'U', 'A', 'Q']);
                $trajet['user_photo'] = $isBase64 ? $photoData : base64_encode($photoData);
            } else {
                $trajet['user_photo'] = null;
            }

            $trajet['heure_depart'] = new DateTime($trajet['heure_depart']);
            $trajet['heure_arrivee'] = new DateTime($trajet['heure_arrivee']);
            $trajet['date_depart'] = new DateTime($trajet['date_depart']);
            $trajet['date_arrivee'] = new DateTime($trajet['date_arrivee']);

            $datetimeDepart = new DateTime(
                $trajet['date_depart']->format('Y-m-d') . ' ' . $trajet['heure_depart']->format('H:i:s')
            );
            $datetimeArrivee = new DateTime(
                $trajet['date_arrivee']->format('Y-m-d') . ' ' . $trajet['heure_arrivee']->format('H:i:s')
            );
            $diff = $datetimeDepart->diff($datetimeArrivee);

            $trajet['duree_heures'] = $diff->days * 24 + $diff->h;
            $trajet['duree_minutes'] = $diff->i;
        }

        return $this->render('covoiturage/resultats.html.twig', [
            'trajets' => $trajets
        ]);
    }

    #[Route('/covoiturage/{id}', name: 'details_trajet', requirements: ['id' => '\d+'])]
    public function details(int $id, Security $security): Response
    {
        if (!$security->getUser()) {
            $this->addFlash('warning', 'Vous devez être connecté pour voir ce covoiturage.');
            return $this->redirectToRoute('app_login');
        }

        $pdo = $this->pdo;

        // Récupération du covoiturage et des infos liées
        $stmt = $pdo->prepare("
            SELECT 
                c.*, 
                u.pseudo AS chauffeur_pseudo,
                u.note AS chauffeur_note,
                u.photo AS chauffeur_photo,
                v.marque, v.modele, v.energie, v.nb_places, v.couleur, v.animaux, v.fumeur, v.preferences
            FROM covoiturage c
            JOIN utilisateurs u ON u.id = c.utilisateur_id
            JOIN voiture v ON v.id = c.voiture_id
            WHERE c.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $trajet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trajet) {
            throw $this->createNotFoundException("Ce covoiturage n'existe pas.");
        }

        // Décodage des préférences
        if (!empty($trajet['preferences'])) {
            $decoded = json_decode($trajet['preferences'], true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $trajet['preferences'] = is_array($decoded) ? $decoded : [];
        } else {
            $trajet['preferences'] = [];
        }

        // Encodage photo si nécessaire
        if (!empty($trajet['chauffeur_photo'])) {
            $photoData = is_resource($trajet['chauffeur_photo'])
                ? stream_get_contents($trajet['chauffeur_photo'])
                : $trajet['chauffeur_photo'];

            $firstChar = substr($photoData, 0, 1);
            $isBase64 = in_array($firstChar, ['/', 'i', 'R', 'U', 'A', 'Q']);
            $trajet['chauffeur_photo'] = $isBase64 ? $photoData : base64_encode($photoData);
        } else {
            $trajet['chauffeur_photo'] = null;
        }

        // Convertir les dates et heures
        $trajet['date_depart'] = new DateTime($trajet['date_depart']);
        $trajet['heure_depart'] = new DateTime($trajet['heure_depart']);
        $trajet['date_arrivee'] = new DateTime($trajet['date_arrivee']);
        $trajet['heure_arrivee'] = new DateTime($trajet['heure_arrivee']);

        // Calcul durée
        $datetimeDepart = new DateTime($trajet['date_depart']->format('Y-m-d') . ' ' . $trajet['heure_depart']->format('H:i:s'));
        $datetimeArrivee = new DateTime($trajet['date_arrivee']->format('Y-m-d') . ' ' . $trajet['heure_arrivee']->format('H:i:s'));
        $diff = $datetimeDepart->diff($datetimeArrivee);
        $trajet['duree_heures'] = $diff->days * 24 + $diff->h;
        $trajet['duree_minutes'] = $diff->i;

        // Récupération des avis sur le conducteur
        $stmt = $pdo->prepare("
            SELECT a.note, a.commentaire, u.pseudo AS auteur_pseudo
            FROM avis a
            JOIN utilisateurs u ON u.id = a.auteur_id
            WHERE a.cible_id = :conducteur_id
            ORDER BY a.id DESC
        ");
        $stmt->execute(['conducteur_id' => $trajet['utilisateur_id']]);
        $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Vérification si l'utilisateur connecté a déjà réservé
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();
        $aDejaReserve = false;

        if ($utilisateur) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM reservation 
                WHERE utilisateur_id = :uid AND covoiturage_id = :cid
            ");
            $stmt->execute([
                'uid' => $utilisateur->getId(),
                'cid' => $id
            ]);
            $aDejaReserve = $stmt->fetchColumn() > 0;
        }

        // Calculer le nombre de réservations existantes
        $stmtRes = $pdo->prepare("SELECT COUNT(*) FROM reservation WHERE covoiturage_id = :id");
        $stmtRes->execute(['id' => $id]);
        $nbReservations = (int) $stmtRes->fetchColumn();

        // Calculer les places disponibles
        $trajet['places_disponibles'] = $trajet['nb_place'] - $nbReservations;

        return $this->render('covoiturage/details.html.twig', [
            'trajet' => $trajet,
            'avis' => $avis,
            'aDejaReserve' => $aDejaReserve,
        ]);
    }

    #[Route('/covoiturage/{id}/reserver', name: 'reserver_trajet', methods: ['POST'])]
    public function reserver(int $id, Request $request, MailerInterface $mailer): Response
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('reserver_covoiturage_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('details_trajet');
        }

        $pdo = $this->pdo;

        try {
            $pdo->beginTransaction();

            // Vérifier existence et places disponibles
            $stmt = $pdo->prepare("SELECT nb_place FROM covoiturage WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $covoiturage = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$covoiturage || $covoiturage['nb_place'] <= 0) {
                $pdo->rollBack();
                $this->addFlash('error', 'Ce covoiturage n’est plus disponible.');
                return $this->redirectToRoute('details_trajet', ['id' => $id]);
            }

            // Vérifier crédits
            if ($utilisateur->getCredits() < 2) {
                $pdo->rollBack();
                $this->addFlash('error', 'Vous n’avez pas assez de crédits.');
                return $this->redirectToRoute('details_trajet', ['id' => $id]);
            }

            // Vérifier si déjà réservé
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservation WHERE utilisateur_id = :uid AND covoiturage_id = :cid");
            $stmt->execute([
                'uid' => $utilisateur->getId(),
                'cid' => $id
            ]);
            if ($stmt->fetchColumn() > 0) {
                $pdo->rollBack();
                $this->addFlash('error', 'Vous avez déjà réservé ce covoiturage.');
                return $this->redirectToRoute('details_trajet', ['id' => $id]);
            }

            // Insérer réservation
            $stmt = $pdo->prepare("INSERT INTO reservation (utilisateur_id, covoiturage_id, date_reservation) VALUES (:uid, :cid, :date_reservation)");
            $stmt->execute([
                'uid' => $utilisateur->getId(),
                'cid' => $id,
                'date_reservation' => (new DateTime())->format('Y-m-d H:i:s')
            ]);

            // Décrémenter places
            $stmt = $pdo->prepare("UPDATE covoiturage SET nb_place = nb_place - 1 WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Mise à jour statut complet
            if ((int)$covoiturage['nb_place'] === 1) { // nb_place va passer à 0
                $stmt = $pdo->prepare("UPDATE covoiturage SET statut = 'complet' WHERE id = :id");
                $stmt->execute(['id' => $id]);
            }

            // Décrémenter crédits de l’utilisateur
            $utilisateur->setCredits($utilisateur->getCredits() - 2);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET credits = :credits WHERE id = :id");
            $stmt->execute([
                'credits' => $utilisateur->getCredits(),
                'id' => $utilisateur->getId()
            ]);

            $pdo->commit();

            $this->addFlash('success', 'Réservation confirmée !');

            // Envoi du mail au chauffeur
            $stmt = $pdo->prepare("SELECT u.email, u.pseudo FROM covoiturage c JOIN utilisateurs u ON c.utilisateur_id = u.id WHERE c.id = :id");
            $stmt->execute(['id' => $id]);
            $chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($chauffeur) {
                $email = (new TemplatedEmail())
                    ->from('noreply@ecoride.fr')
                    ->to($chauffeur['email'])
                    ->subject('Nouvelle réservation')
                    ->htmlTemplate('emails/nouvelle_reservation.html.twig')
                    ->context([
                        'chauffeurPseudo' => $chauffeur['pseudo'],
                        'passagerPseudo' => $utilisateur->getPseudo()
                    ]);
                $mailer->send($email);
            }

        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->addFlash('error', 'Impossible de réserver ce covoiturage. Réessayez.');
        }

        return $this->redirectToRoute('app_profil', ['id' => $id]);
    }

    #[Route('/covoiturage/ajouter', name: 'ajouter_covoiturage', methods: ['GET','POST'])]
    public function ajouterCovoiturage(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user || !$user->isChauffeur()) {
            throw $this->createAccessDeniedException('Vous devez être chauffeur pour ajouter un covoiturage.');
        }

        if ($user->getCredits() < 2) {
            $this->addFlash('error', 'Vous n’avez pas assez de crédits pour créer ce covoiturage.');
            return $this->redirectToRoute('covoiturage_new');
        }

        $covoiturage = new Covoiturage();
        $covoiturage->setHeureDepart(new DateTime('12:00'));
        $covoiturage->setHeureArrivee(new DateTime('12:00'));

        $form = $this->createForm(CovoiturageType::class, $covoiturage, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $errors = [];

            // Validation serveur
            if ($covoiturage->getNbPlace() < 1) $errors[] = 'Le nombre de places doit être au moins de 1.';
            if ($covoiturage->getPrixPersonne() < 0) $errors[] = 'Le prix par personne ne peut pas être négatif.';
            if (empty($covoiturage->getLieuDepart()) || empty($covoiturage->getLieuArrivee())) $errors[] = 'Les lieux de départ et d’arrivée doivent être renseignés.';
            if ($covoiturage->getDateDepart() < new DateTime('today')) $errors[] = 'La date de départ doit être aujourd’hui ou dans le futur.';
            if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $covoiturage->getHeureDepart()->format('H:i:s'))) $errors[] = 'L’heure de départ n’est pas valide.';
            if (!$covoiturage->getVoiture()?->getId()) $errors[] = 'Vous devez sélectionner une voiture.';

            if (count($errors) > 0) {
                foreach ($errors as $err) {
                    $this->addFlash('error', $err);
                }
                return $this->render('covoiturage/ajouter.html.twig', [
                    'form' => $form->createView(),
                    'covoiturage' => $covoiturage,
                ]);
            }

            // Traitement PDO
            $pdo = $this->pdo;

            $dateHeureDepart = DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $covoiturage->getDateDepart()->format('Y-m-d') . ' ' . $covoiturage->getHeureDepart()->format('H:i:s')
            );
            $now = new DateTime();
            $statut = ($dateHeureDepart < $now) ? 'ferme' : (($covoiturage->getNbPlace() <= 0) ? 'complet' : 'ouvert');

            try {
                $pdo->beginTransaction();

                // Décrémenter crédits
                $user->removeCredits(2);
                $stmtUpdate = $pdo->prepare("UPDATE utilisateurs SET credits = :credits WHERE id = :id");
                $stmtUpdate->execute([
                    'credits' => $user->getCredits(),
                    'id' => $user->getId()
                ]);

                // Insérer covoiturage
                $stmt = $pdo->prepare("
                    INSERT INTO covoiturage (
                        utilisateur_id, date_depart, heure_depart, lieu_depart,
                        date_arrivee, heure_arrivee, lieu_arrivee, statut,
                        nb_place, prix_personne, voiture_id
                    ) VALUES (
                        :utilisateur_id, :date_depart, :heure_depart, :lieu_depart,
                        :date_arrivee, :heure_arrivee, :lieu_arrivee, :statut,
                        :nb_place, :prix_personne, :voiture_id
                    )
                ");
                $stmt->execute([
                    'utilisateur_id' => $user->getId(),
                    'date_depart'   => $covoiturage->getDateDepart()->format('Y-m-d'),
                    'heure_depart'  => $covoiturage->getHeureDepart()->format('H:i:s'),
                    'lieu_depart'   => $covoiturage->getLieuDepart(),
                    'date_arrivee'  => $covoiturage->getDateArrivee()?->format('Y-m-d'),
                    'heure_arrivee' => $covoiturage->getHeureArrivee()?->format('H:i:s'),
                    'lieu_arrivee'  => $covoiturage->getLieuArrivee(),
                    'nb_place'      => $covoiturage->getNbPlace(),
                    'prix_personne' => $covoiturage->getPrixPersonne(),
                    'voiture_id'    => $covoiturage->getVoiture()->getId(),
                    'statut'        => $statut,
                ]);

                $pdo->commit();

                $this->addFlash('success', 'Covoiturage enregistré avec succès !');
                return $this->redirectToRoute('app_profil');

            } catch (\Exception $e) {
                $pdo->rollBack();
                $this->addFlash('error', 'Erreur lors de l’enregistrement du covoiturage. Veuillez réessayer.');
                return $this->render('covoiturage/ajouter.html.twig', [
                    'form' => $form->createView(),
                    'covoiturage' => $covoiturage,
                ]);
            }
        }

        return $this->render('covoiturage/ajouter.html.twig', [
            'form' => $form->createView(),
            'covoiturage' => $covoiturage,
        ]);
    }

    #[Route('/covoiturage/{id}/modifier', name: 'modifier_covoiturage')]
    public function modifierCovoiturage(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        Security $security
    ): Response {
        $pdo = $this->pdo;

        // Récupérer le covoiturage existant
        $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        // Hydrater l’entité avec Doctrine (utile pour validations)
        $covoiturage = $em->getRepository(Covoiturage::class)->find($id);

        // Vérifier le nombre de réservations existantes
        $stmtRes = $pdo->prepare("SELECT COUNT(*) FROM reservation WHERE covoiturage_id = :id");
        $stmtRes->execute(['id' => $id]);
        $nbReservations = (int) $stmtRes->fetchColumn();

        // Formulaire
        $form = $this->createForm(CovoiturageType::class, $covoiturage, [
            'user' => $security->getUser()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer les données du formulaire
            $dateDepart = $covoiturage->getDateDepart()->format('Y-m-d');
            $heureDepart = $covoiturage->getHeureDepart()->format('H:i:s');
            $lieuDepart = $covoiturage->getLieuDepart();
            $dateArrivee = $covoiturage->getDateArrivee()->format('Y-m-d');
            $heureArrivee = $covoiturage->getHeureArrivee()->format('H:i:s');
            $lieuArrivee = $covoiturage->getLieuArrivee();
            $nbPlace = $covoiturage->getNbPlace();
            $prixPersonne = $covoiturage->getPrixPersonne();
            $voiture = $covoiturage->getVoiture()?->getId();

            // Empêcher de mettre moins de places que le nombre déjà réservé
            if ($nbPlace < $nbReservations) {
                $this->addFlash('error', "Impossible de définir moins de $nbReservations places car il y a déjà $nbReservations réservation(s).");
                return $this->redirectToRoute('modifier_covoiturage', ['id' => $id]);
            }

            // Calculer les places disponibles
            $placesDisponibles = $nbPlace - $nbReservations;

            // Déterminer le statut
            $statut = $placesDisponibles <= 0 ? 'complet' : 'ouvert';

            // Mettre à jour en BDD
            $update = $pdo->prepare("
                UPDATE covoiturage
                SET date_depart = :date_depart,
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

            $update->execute([
                'date_depart' => $dateDepart,
                'heure_depart' => $heureDepart,
                'lieu_depart' => $lieuDepart,
                'date_arrivee' => $dateArrivee,
                'heure_arrivee' => $heureArrivee,
                'lieu_arrivee' => $lieuArrivee,
                'nb_place' => $nbPlace,
                'prix_personne' => $prixPersonne,
                'voiture_id' => $voiture,
                'statut' => $statut,
                'id' => $id
            ]);

            $em->refresh($covoiturage);

            $this->addFlash('success', 'Covoiturage modifié avec succès ✅');
            return $this->redirectToRoute('app_profil');
        }

        // Calculer les places disponibles
        $placesDisponibles = $covoiturage->getNbPlace() - $nbReservations;

        return $this->render('covoiturage/modifier.html.twig', [
            'form' => $form->createView(),
            'covoiturage' => $covoiturage,
            'reservationsCount' => $nbReservations,
            'placesDisponibles' => $placesDisponibles,
        ]);
    }

    #[Route('/covoiturage/{id}/supprimer', name: 'supprimer_covoiturage', methods: ['POST'])]
    public function supprimer(int $id, Request $request, MailerInterface $mailer): Response
    {
        /** @var \App\Entity\User $chauffeur */
        $chauffeur = $this->getUser();

        if (!$chauffeur) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('supprimer_covoiturage_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_profil');
        }

        $pdo = $this->pdo;

        try {
            $pdo->beginTransaction();

            // Vérifier que le covoiturage appartient au chauffeur
            $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE id = :id AND utilisateur_id = :chauffeur_id FOR UPDATE");
            $stmt->execute(['id' => $id, 'chauffeur_id' => $chauffeur->getId()]);
            $covoiturage = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$covoiturage) {
                $pdo->rollBack();
                $this->addFlash('error', 'Covoiturage non trouvé ou accès refusé.');
                return $this->redirectToRoute('app_profil');
            }

            // Récupérer les passagers
            $stmt = $pdo->prepare("
                SELECT u.id, u.email
                FROM reservation r
                JOIN utilisateurs u ON r.utilisateur_id = u.id
                WHERE r.covoiturage_id = :id
            ");
            $stmt->execute(['id' => $id]);
            $passagers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Rembourser passagers
            $updateStmt = $pdo->prepare("UPDATE utilisateurs SET credits = credits + 2 WHERE id = :id");
            foreach ($passagers as $passager) {
                $updateStmt->execute(['id' => $passager['id']]);
            }

            // Rembourser le chauffeur
            $updateStmt->execute(['id' => $chauffeur->getId()]);

            // Supprimer les réservations
            $stmt = $pdo->prepare("DELETE FROM reservation WHERE covoiturage_id = :id");
            $stmt->execute(['id' => $id]);

            // Supprimer le covoiturage
            $stmt = $pdo->prepare("DELETE FROM covoiturage WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $pdo->commit();

            // Envoyer les mails après commit pour éviter de bloquer la transaction
            foreach ($passagers as $passager) {
                $email = (new TemplatedEmail())
                    ->from('noreply@ecoride.com')
                    ->to($passager['email'])
                    ->subject('Annulation de covoiturage')
                    ->htmlTemplate('emails/annulation_covoiturage.html.twig')
                    ->context([
                        'passager' => $passager,
                        'covoiturage' => $covoiturage
                    ]);
                $mailer->send($email);
            }

            $this->addFlash('success', 'Covoiturage supprimé et crédits remboursés.');
        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->addFlash('error', 'Impossible de supprimer le covoiturage : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/reservation/{id}/annuler', name: 'annuler_reservation', methods: ['POST'])]
    public function annulerReservation(int $id, Request $request, MailerInterface $mailer): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('annuler_reservation_'.$id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_profil');
        }

        $pdo = $this->pdo;

        try {
            $pdo->beginTransaction();

            // Récupérer la réservation et verrouiller la ligne
            $stmt = $pdo->prepare("SELECT covoiturage_id, utilisateur_id FROM reservation WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservation) {
                $pdo->rollBack();
                $this->addFlash('error', 'Réservation non trouvée.');
                return $this->redirectToRoute('app_profil');
            }

            if ($user->getId() != $reservation['utilisateur_id']) {
                $pdo->rollBack();
                $this->addFlash('error', 'Vous ne pouvez pas annuler cette réservation.');
                return $this->redirectToRoute('app_profil');
            }

            $covoiturageId = $reservation['covoiturage_id'];

            // Supprimer la réservation
            $stmt = $pdo->prepare("DELETE FROM reservation WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Remboursement du passager
            $stmt = $pdo->prepare("UPDATE utilisateurs SET credits = credits + 2 WHERE id = :id");
            $stmt->execute(['id' => $user->getId()]);

            // Réincrémenter nb_place et mettre statut à 'ouvert' si nécessaire
            $stmt = $pdo->prepare("
                UPDATE covoiturage 
                SET nb_place = nb_place + 1, statut = 'ouvert' 
                WHERE id = :id
            ");
            $stmt->execute(['id' => $covoiturageId]);

            $pdo->commit();

            $this->addFlash('success', 'Votre réservation a été annulée, 2 crédits vous ont été remboursés.');

            // Envoi du mail au chauffeur
            $stmt = $pdo->prepare("SELECT c.utilisateur_id, u.email, u.pseudo FROM reservation r JOIN covoiturage c ON r.covoiturage_id = c.id JOIN utilisateurs u ON c.utilisateur_id = u.id WHERE r.id = :id");
            $stmt->execute(['id' => $id]);
            $chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($chauffeur) {
                $email = (new TemplatedEmail())
                    ->from('noreply@ecoride.fr')
                    ->to($chauffeur['email'])
                    ->htmlTemplate('emails/annulation_reservation.html.twig')
                    ->context([
                        'chauffeurPseudo' => $chauffeur['pseudo'],
                        'passagerPseudo' => $user->getPseudo()
                    ]);
                $mailer->send($email);
            }

        } catch (\Exception $e) {
            $pdo->rollBack();
            $this->addFlash('error', 'Impossible d’annuler la réservation. Réessayez.');
        }

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/chauffeur/covoiturage/{id}/demarrer', name: 'chauffeur_covoiturage_demarrer')]
    public function demarrerCovoiturage(Covoiturage $covoiturage, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        /** @var \App\Entity\User $user */

        // Vérifier que l'utilisateur est chauffeur
        if (!$user->isChauffeur()) {
            throw $this->createAccessDeniedException('Vous devez être chauffeur pour démarrer un covoiturage.');
        }

        // Vérifier que le covoiturage appartient au chauffeur
        if ($covoiturage->getUtilisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n’êtes pas le propriétaire de ce covoiturage.');
        }

        if (!in_array($covoiturage->getStatut(), [Covoiturage::STATUT_OUVERT, Covoiturage::STATUT_COMPLET])) {
            $this->addFlash('warning', 'Ce covoiturage ne peut pas être démarré.');
            return $this->redirectToRoute('app_profil');
        }

        $covoiturage->setStatut(Covoiturage::STATUT_EN_COURS);
        $em->flush();

        $this->addFlash('success', 'Le covoiturage a bien démarré.');
        return $this->redirectToRoute('app_profil');
    }

    #[Route('/chauffeur/covoiturage/{id}/clore', name: 'chauffeur_covoiturage_clore', methods: ['POST'])]
    public function cloreCovoiturage(Covoiturage $covoiturage, Request $request, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger): Response {

        /** @var \App\Entity\User $user */
        
        $user = $this->getUser();
        if (!$user || !$user->isChauffeur()) {
            throw $this->createAccessDeniedException('Vous devez être chauffeur pour clore un covoiturage.');
        }

        if ($covoiturage->getUtilisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n’êtes pas le propriétaire de ce covoiturage.');
        }

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('clore_covoiturage_' . $covoiturage->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_profil');
        }

        if ($covoiturage->getStatut() !== Covoiturage::STATUT_EN_COURS) {
            $this->addFlash('warning', 'Ce covoiturage ne peut pas être clôturé.');
            return $this->redirectToRoute('app_profil');
        }

        $reservations = $covoiturage->getReservations();
        $nbPassagers = count($reservations);
        $chauffeur = $covoiturage->getUtilisateur();
        $creditsGagnes = 2 * $nbPassagers;

        // Transaction Doctrine pour statut + crédits
        $conn = $em->getConnection();
        $conn->beginTransaction();
        try {
            $covoiturage->setStatut(Covoiturage::STATUT_FERME);
            $chauffeur->setCredits($chauffeur->getCredits() + $creditsGagnes);
            $em->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            $this->addFlash('error', 'Impossible de clore le covoiturage. Réessayez.');
            return $this->redirectToRoute('app_profil');
        }

        // Envoi des mails aux passagers (hors transaction)
        foreach ($reservations as $reservation) {
            $participant = $reservation->getUtilisateur();
            if (!$participant || !$participant->getEmail()) continue;

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

        $this->addFlash('success', sprintf(
            'Le covoiturage a été clôturé et vous avez gagné %d crédits.',
            $creditsGagnes
        ));

        return $this->redirectToRoute('app_profil');
    }
}
