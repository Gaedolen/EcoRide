<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Avis;
use App\Entity\Covoiturage;
use App\Entity\Report;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Repository\AvisRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ReportType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYE')]
class EmployeController extends AbstractController
{
    #[Route('/employe', name: 'employe_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('employe/dashboard.html.twig');
    }

    #[Route('/employe/avis/moderer', name: 'employe_moderer_avis')]
    public function modererAvis(AvisRepository $reviewRepository): Response
    {
        $avis = $reviewRepository->findEnAttenteValidation();

        return $this->render('employe/moderation_avis.html.twig', [
            'avis' => $avis
        ]);
    }

    #[Route('/employe/avis/moderer/{id}', name: 'employe_moderer_avis_action', methods: ['POST'])]
    public function modererAvisAction(int $id, AvisRepository $reviewRepository, EntityManagerInterface $em, Request $request, MailerInterface $mailer): RedirectResponse {
        $avis = $reviewRepository->find( $id);
        if (!$avis || !$this->isCsrfTokenValid('moderation_avis_' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $action = $request->request->get('action');

        if ($action === 'approuve') {
            $avis->setStatut('approuve');
            $avis->setIsValidated(true);
        } elseif ($action === 'refuse') {
            $avis->setStatut('refuse');
            $avis->setStatut(false);

            // Envoi du mail à l'auteur
            $email = (new TemplatedEmail())
                ->from('noreply@ecoride.fr')
                ->to($avis->getAuteur()->getEmail())
                ->subject('Votre avis a été refusé')
                ->htmlTemplate('emails/avis_refuse.html.twig')
                ->context([
                    'pseudo' => $avis->getAuteur()->getPseudo(),
                    'commentaire' => $avis->getCommentaire()
                ]);
            $mailer->send($email);
        }

        $em->flush();

        return $this->redirectToRoute('employe_moderer_avis');
    }

    #[Route('/employe/covoiturages-signales', name: 'employe_covoiturages_signales')]
    public function covoituragesSignales(EntityManagerInterface $em): Response
    {
        $signalements = $em->getRepository(Report::class)->findAll();

        return $this->render('employe/covoiturages_signales.html.twig', [
            'signalements' => $signalements
        ]);
    }

    #[Route('/employe/signaler/{covoiturageId}/{id}', name: 'employe_signaler_utilisateur')]
    public function signalerUtilisateur(
        Request $request,
        int $covoiturageId,
        User $user,
        Security $security,
        EntityManagerInterface $em
    ): Response {
        // Récupérer le covoiturage
        $covoiturage = $em->getRepository(Covoiturage::class)->find($covoiturageId);
        if (!$covoiturage) {
            throw $this->createNotFoundException('Covoiturage introuvable.');
        }

        // Créer le report
        $report = new Report();
        $report->setReportedUser($user);
        $report->setReportedBy($security->getUser());
        $report->setCovoiturage($covoiturage);

        // Formulaire
        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($report);
            $em->flush();

            $this->addFlash('success', 'Signalement envoyé à l\'administrateur.');
            return $this->redirectToRoute('employe_gestion_utilisateurs');
        }

        return $this->render('employe/signaler.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}
