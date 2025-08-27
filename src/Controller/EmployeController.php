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
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
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

            // Recalculer la moyenne du chauffeur
            $cible = $avis->getCible(); // le chauffeur
            $qb = $em->getRepository(Avis::class)->createQueryBuilder('a')
                ->select('AVG(a.note) as moyenne')
                ->where('a.cible = :user')
                ->andWhere('a.statut = :statut')
                ->setParameter('user', $cible)
                ->setParameter('statut', Avis::STATUT_APPROUVE);

            $moyenne = $qb->getQuery()->getSingleScalarResult();
            $cible->setNote($moyenne !== null ? (float) $moyenne : null);

            $em->persist($cible);
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
    public function covoituragesProblematiques(Request $request, EntityManagerInterface $em, Security $security, UserRepository $userRepository): Response {
        // Récupération des covoiturages signalés
        $reports = $em->getRepository(Report::class)->findPendingReportsFromUsers();

        // Création d'un formulaire de signalement vide
        $report = new Report();
        $report->setReportedBy($security->getUser());

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $reportedUser = $report->getReportedUser();

            $lastCovoiturage = $em->getRepository(Covoiturage::class)->findOneBy(
            ['utilisateur' => $reportedUser],
            ['date_arrivee' => 'DESC']
            );

            $report->setReportedBy($this->getUser());
            $report->setStatut('en_attente');
            $report->setCovoiturage($lastCovoiturage);

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

    #[Route('/employe/contacter/{id}', name: 'employe_contacter_utilisateur', methods: ['POST'])]
    public function contacterUtilisateur(Request $request, User $user, MailerInterface $mailer): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOYE'); // sécurité

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('contacter_utilisateur', $token)) {
                throw $this->createAccessDeniedException('Token CSRF invalide.');
            }

            $subject = $request->request->get('subject');
            $message = $request->request->get('message');

            $email = (new TemplatedEmail())
                ->from('contact@ecoride.com')
                ->to($user->getEmail())
                ->subject($subject)
                ->html($message);

            $mailer->send($email);

            $this->addFlash('success', 'Email envoyé à ' . $user->getPseudo());
        }

        return $this->redirectToRoute('employe_covoiturages_problematiques');
    }

    #[Route('/employe/signalement/historique', name: 'employe_historique_signalements')]
    public function historiqueSignalements(EntityManagerInterface $em): Response
    {
        $reports = $em->getRepository(Report::class)->findBy(
            ['statut' => ['traite', 'ignore']],
            ['createdAt' => 'DESC']
        );

        return $this->render('employe/historique_signalements.html.twig', [
            'reports' => $reports
        ]);
    }

    #[Route('/employe/search-user', name: 'employe_search_user')]
    public function searchUser(Request $request, UserRepository $userRepository): JsonResponse
    {
        $term = $request->query->get('q');
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.pseudo LIKE :term')
            ->setParameter('term', '%'.$term.'%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'text' => $user->getPseudo()
            ];
        }

        return new JsonResponse($results);
    }
}
