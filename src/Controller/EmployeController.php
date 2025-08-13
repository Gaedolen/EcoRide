<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Avis;
use App\Entity\Covoiturage;
use App\Entity\Report;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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

    #[Route('/employe/covoiturages_problematiques', name: 'employe_covoiturages_problematiques')]
    public function covoituragesProblematiques(Request $request, EntityManagerInterface $em, Security $security): Response {
        // Récupération des covoiturages signalés
        $reports = $em->getRepository(Report::class)->findAll();

        // Création d'un formulaire de signalement vide
        $report = new Report();
        $report->setReportedBy($security->getUser());

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        // Si le formulaire est soumis
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($report);
            $em->flush();

            $this->addFlash('success', 'Signalement enregistré.');
            return $this->redirectToRoute('employe_covoiturages_problematiques');
        }

        return $this->render('employe/covoiturages_problematiques.html.twig', [
            'reports' => $reports,
            'form' => $form->createView()
        ]);
    }

    #[Route('/employe/signalement/traiter/{id}', name: 'employe_traiter_signalement', methods: ['POST'])]
    public function traiterSignalement(int $id, EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $report = $em->getRepository(Report::class)->find($id);
        if (!$report || !$this->isCsrfTokenValid('traiter_report_' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $report->setStatut('traite');
        $em->flush();

        $this->addFlash('success', 'Signalement traité avec succès.');

        return $this->redirectToRoute('employe_covoiturages_problematiques');
    }

    #[Route('/employe/signalement/ignorer/{id}', name: 'employe_ignorer_signalement', methods: ['POST'])]
    public function ignorerSignalement(int $id, EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $report = $em->getRepository(Report::class)->find($id);
        if (!$report || !$this->isCsrfTokenValid('ignorer_report_' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $report->setStatut('ignore');
        $em->flush();

        $this->addFlash('info', 'Signalement ignoré.');

        return $this->redirectToRoute('employe_covoiturages_problematiques');
    }

    #[Route('/employe/contacter/{id}', name: 'employe_contacter_utilisateur', methods: ['GET', 'POST'])]
    public function contacterUtilisateur(Request $request, User $user, MailerInterface $mailer): Response
    {
        $form = $this->createFormBuilder()
            ->add('subject', TextType::class, ['label' => 'Sujet'])
            ->add('message', TextareaType::class, ['label' => 'Message'])
            ->add('send', SubmitType::class, ['label' => 'Envoyer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new TemplatedEmail())
                ->from('contact@ecoride.com')
                ->to($user->getEmail())
                ->subject($data['subject'])
                ->html($data['message']);

            $mailer->send($email);

            $this->addFlash('success', 'Email envoyé à '.$user->getPseudo());
            return $this->redirectToRoute('employe_covoiturages_problematiques');
        }

        return $this->render('employe/contacter.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
