<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Form\EmployeType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\User;
use App\Entity\Covoiturage;
use App\Entity\Role;
use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $connection = $em->getConnection();

        // Nombre de covoiturages par jour
        $sql1 = "
            SELECT DATE(c.date_depart) as jour, COUNT(c.id) as totalCovoiturages
            FROM covoiturage c
            WHERE c.statut = 'ferme'
            GROUP BY jour
            ORDER BY jour ASC
        ";
        $stmt1 = $connection->prepare($sql1);
        $covoituragesParJour = $stmt1->executeQuery()->fetchAllAssociative();

        foreach ($covoituragesParJour as &$row) {
            $row['totalCovoiturages'] = isset($row['totalCovoiturages']) ? (int) $row['totalCovoiturages'] : 0;
            $row['jour'] = isset($row['jour']) ? (string) $row['jour'] : '';
        }

        // Crédits gagnés par jour
        $sql2 = "
            SELECT DATE(c.date_depart) as jour, 
                SUM(2 + 2 * (
                    SELECT COUNT(r.id) FROM reservation r WHERE r.covoiturage_id = c.id
                )) AS credits_plateforme
            FROM covoiturage c
            WHERE c.statut = 'ferme'
            GROUP BY jour
            ORDER BY jour ASC
        ";
        $stmt2 = $connection->prepare($sql2);
        $creditsParJour = $stmt2->executeQuery()->fetchAllAssociative();

        foreach ($creditsParJour as &$row) {
            $row['credits_plateforme'] = isset($row['credits_plateforme']) ? (int) $row['credits_plateforme'] : 0;
            $row['jour'] = isset($row['jour']) ? (string) $row['jour'] : '';
        }

        // Total des covoiturages
        $totalCovoiturages = array_sum(array_column($covoituragesParJour, 'totalCovoiturages'));

        // Total des crédits gagnés
        $totalCredits = array_sum(array_column($creditsParJour, 'credits_plateforme'));

        // Préparer les données pour Chart.js
        $covoituragesLabels = array_column($covoituragesParJour, 'jour');
        $covoituragesValues = array_map('intval', array_column($covoituragesParJour, 'totalCovoiturages'));

        $creditsLabels = array_column($creditsParJour, 'jour');
        $creditsValues = array_column($creditsParJour, 'credits_plateforme');

        return $this->render('admin/dashboard.html.twig', [
            'covoituragesParJour' => $covoituragesParJour,
            'creditsParJour' => $creditsParJour,
            'totalCredits' => (int) $totalCredits,
            'totalCovoiturages' => (int) $totalCovoiturages,
            'covoituragesLabels' => $covoituragesLabels,
            'covoituragesValues' => $covoituragesValues,
            'creditsLabels' => $creditsLabels,
            'creditsValues' => $creditsValues,
        ]);
    }

    #[Route('/admin/utilisateurs', name: 'admin_gestion_utilisateurs')]
    public function gestionUtilisateurs(EntityManagerInterface $em, UserRepository $userRepository): Response {
        $users = $userRepository->findByRoleLibelle('USER');

        $reports = $em->getRepository(Report::class)->findAll();

        $reportsByUser = [];
        foreach ($reports as $report) {
            $reportedBy = $report->getReportedBy();
            $reportedUser = $report->getReportedUser();

            if (!$reportedBy || !$reportedUser) continue;
            if ($reportedBy->getRole()->getLibelle() !== 'EMPLOYE') continue;

            $userId = $reportedUser->getId();
            $reportsByUser[$userId][] = $report;
        }

        return $this->render('admin/gestion_utilisateurs.html.twig', [
            'users' => $users,
            'reportsByUser' => $reportsByUser,
        ]);
    }

    #[Route('/admin/utilisateur/suspendre/{id}', name: 'admin_suspendre_utilisateur', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function suspendreUtilisateur(Request $request, User $user, EntityManagerInterface $em, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        if (!($data['_token'] ?? null) || !$this->isCsrfTokenValid('suspend_user_' . $user->getId(), $data['_token'])) {
            return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
        }

        $reason = $data['reason'] ?? '';
        if ($reason === 'autres') $reason = $data['otherReason'] ?? '';

        $user->setIsSuspended(true);
        $user->setSuspendReason($reason);
        $em->flush();

        $unsuspendToken = $this->container->get('security.csrf.token_manager')->getToken('unsuspend_user_' . $user->getId())->getValue();

        return $this->json(['success' => true, 'userId' => $user->getId(), 'unsuspendToken' => $unsuspendToken]);
    }

    #[Route('/admin/utilisateur/unsuspendre/{id}', name: 'admin_unsuspendre_utilisateur', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function unsuspendUser(Request $request, User $user, EntityManagerInterface $em, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        if (!($data['_token'] ?? null) || !$this->isCsrfTokenValid('unsuspend_user_' . $user->getId(), $data['_token'])) {
            return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
        }

        $user->setIsSuspended(false);
        $user->setSuspendReason(null);
        $em->flush();

        $suspendToken = $this->container->get('security.csrf.token_manager')->getToken('suspend_user_' . $user->getId())->getValue();

        return $this->json(['success' => true, 'userId' => $user->getId(), 'suspendToken' => $suspendToken]);
    }

    #[Route('/admin/employes', name: 'admin_employes')]
    #[IsGranted('ROLE_ADMIN')]
    public function employes(UserRepository $userRepository): Response
    {
        $employes = $userRepository->findByRole('EMPLOYE');

        return $this->render('admin/employes.html.twig', [
            'employes' => $employes
        ]);
    }

    #[Route('/admin/employes/creer', name: 'admin_creer_employe')]
    #[IsGranted('ROLE_ADMIN')]
    public function creerEmploye(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $employe = new User();
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier ou créer le rôle EMPLOYE
            $role = $em->getRepository(Role::class)->findOneBy(['libelle' => 'EMPLOYE']);
            if (!$role) {
                $role = new Role();
                $role->setLibelle('EMPLOYE');
                $em->persist($role);
            }

            $employe->setRole($role);
            $employe->setIsVerified(true);

            // Récupérer le mot de passe depuis le formulaire (non mappé)
            $plainPassword = $form->get('password')->getData();
            if (!$plainPassword) {
                $this->addFlash('error', 'Le mot de passe est obligatoire.');
                return $this->render('admin/nouveau_employe.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            // Hashage sécurisé
            $hashedPassword = $hasher->hashPassword($employe, $plainPassword);
            $employe->setPassword($hashedPassword);

            $em->persist($employe);
            $em->flush();

            $this->addFlash('success', 'Employé créé avec succès !');

            return $this->redirectToRoute('admin_employes');
        }

        return $this->render('admin/nouveau_employe.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/employe/supprimer/{id}', name: 'admin_supprimer_employe', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function supprimerEmploye(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): RedirectResponse
    {
        $user = $userRepository->find($id);

        if (!$user || $user->getRole()->getLibelle() !== 'EMPLOYE') {
            throw $this->createNotFoundException('Employé non trouvé');
        }

        $submittedToken = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('delete-employe-' . $id, $submittedToken))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Employé supprimé avec succès.');
        return $this->redirectToRoute('admin_employes');
    }
}
