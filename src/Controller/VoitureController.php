<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Voiture;
use App\Form\VoitureType;
use App\Service\PdoService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VoitureController extends AbstractController
{
    private PdoService $pdoService;

    public function __construct(PdoService $pdoService)
    {
        $this->pdoService = $pdoService;
    }

    #[Route('/ajouter-voiture', name: 'ajouter_voiture', methods: ['GET', 'POST'])]
    public function ajouterVoiture(Request $request): Response
    {
        /** @var User $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter une voiture.');
            return $this->redirectToRoute('app_login');
        }

        $voiture = new Voiture();
        $form = $this->createForm(VoitureType::class, $voiture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $preferencesJson = $voiture->getPreferences() ? json_encode($voiture->getPreferences(), JSON_UNESCAPED_UNICODE) : null;

            try {
                $sql = "
                    INSERT INTO voiture
                        (immatriculation, date_premiere_immatriculation, marque, modele, nb_places, fumeur, animaux, couleur, energie, utilisateur_id, preferences)
                    VALUES
                        (:immatriculation, :date_premiere_immatriculation, :marque, :modele, :nb_places, :fumeur, :animaux, :couleur, :energie, :utilisateur_id, :preferences)
                ";

                $this->pdoService->execute($sql, [
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

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'insertion : ' . $e->getMessage());
            }
        }

        return $this->render('voiture/ajouter.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/voiture/modifier/{id}', name: 'modifier_voiture', methods: ['GET', 'POST'])]
    public function modifierVoiture(Request $request, int $id): Response
    {
        /** @var User $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $row = $this->pdoService->fetchOne("SELECT * FROM voiture WHERE id = :id", ['id' => $id]);
        if (!$row) {
            throw $this->createNotFoundException('Voiture introuvable.');
        }

        // Vérification que l'utilisateur est le propriétaire
        if ($row['utilisateur_id'] != $utilisateur->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette voiture.');
        }

        $voiture = new Voiture();
        $voiture
            ->setMarque($row['marque'])
            ->setModele($row['modele'])
            ->setImmatriculation($row['immatriculation'])
            ->setDatePremiereImmatriculation(new \DateTime($row['date_premiere_immatriculation']))
            ->setNbPlaces($row['nb_places'])
            ->setFumeur((bool)$row['fumeur'])
            ->setAnimaux((bool)$row['animaux'])
            ->setCouleur($row['couleur'])
            ->setEnergie($row['energie'])
            ->setPreferences($row['preferences'] ? json_decode($row['preferences'], true) : []);

        $form = $this->createForm(VoitureType::class, $voiture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $preferencesJson = $voiture->getPreferences() ? json_encode($voiture->getPreferences(), JSON_UNESCAPED_UNICODE) : null;

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

            $this->pdoService->execute($sql, [
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
    public function supprimerVoiture(Request $request, int $id, MailerInterface $mailer): Response
    {
        /** @var User $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('supprimer_voiture_'.$id, $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        try {
            $this->pdoService->beginTransaction();

            // Récupérer tous les covoiturages liés à cette voiture
            $covoiturages = $this->pdoService->fetchAll(
                "SELECT * FROM covoiturage WHERE voiture_id = :id AND utilisateur_id = :user_id",
                ['id' => $id, 'user_id' => $utilisateur->getId()]
            );

            foreach ($covoiturages as $covoiturage) {
                $covoiturageId = $covoiturage['id'];
                $statut = $covoiturage['statut'];

                if (in_array($statut, ['ouvert','complet'])) {
                    // Supprimer les réservations et le covoiturage uniquement si ouvert/complet
                    $passagers = $this->pdoService->fetchAll(
                        "SELECT u.email, u.pseudo 
                        FROM reservation r
                        JOIN utilisateurs u ON r.utilisateur_id = u.id
                        WHERE r.covoiturage_id = :covoiturage_id",
                        ['covoiturage_id' => $covoiturageId]
                    );

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

                    $this->pdoService->execute(
                        "DELETE FROM reservation WHERE covoiturage_id = :covoiturage_id",
                        ['covoiturage_id' => $covoiturageId]
                    );

                    $this->pdoService->execute(
                        "DELETE FROM covoiturage WHERE id = :id",
                        ['id' => $covoiturageId]
                    );

                } else {
                    // Covos passés ou en cours : on ne touche pas aux réservations
                    $this->pdoService->execute(
                        "UPDATE covoiturage SET voiture_id = NULL WHERE id = :id",
                        ['id' => $covoiturageId]
                    );
                }
            }

            // Supprimer la voiture elle-même
            $this->pdoService->execute(
                "DELETE FROM voiture WHERE id = :id AND utilisateur_id = :user_id",
                ['id' => $id, 'user_id' => $utilisateur->getId()]
            );

            $this->pdoService->commit();
            $this->addFlash('success', message: 'La voiture et ses covoiturages à venir ont été supprimés. Les passagers ont été prévenus.');

        } catch (\Exception $e) {
            $this->pdoService->rollBack();
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_profil');
    }
}
