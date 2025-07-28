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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\User;
use App\Entity\Role;
use App\Entity\Report;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/utilisateurs', name: 'admin_gestion_utilisateurs')]
    public function gestionUtilisateurs(EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        // Récupérer uniquement les utilisateurs avec le rôle USER
        $users = $userRepository->findByRoleLibelle('USER');

        // Pareil pour les signalements
        $reports = $em->getRepository(Report::class)->findAll();

        $reportsByUser = [];
        foreach ($reports as $report) {
            $userId = $report->getReportedUser()->getId();
            if (!isset($reportsByUser[$userId])) {
                $reportsByUser[$userId] = [];
            }
            $reportsByUser[$userId][] = $report;
        }

        return $this->render('admin/gestion_utilisateurs.html.twig', [
            'users' => $users,
            'reportsByUser' => $reportsByUser,
        ]);
    }

    #[Route('/admin/employes', name: 'admin_employes')]
    public function employes(UserRepository $userRepository): Response
    {
        $employes = $userRepository->findByRole('EMPLOYE');

        return $this->render('admin/employes.html.twig', [
            'employes' => $employes
        ]);
    }

    #[Route('/admin/employes/creer', name: 'admin_creer_employe')]
    public function creerEmploye(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $employe = new User();
        $form = $this->createForm(EmployeType::class, $employe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $em->getRepository(Role::class)->findOneBy(['libelle' => 'EMPLOYE']);
            if (!$role) {
                $role = new Role();
                $role->setLibelle('EMPLOYE');
                $em->persist($role);
            }

            $employe->setRole($role);
            $employe->setIsVerified(true);
            $hashedPassword = $hasher->hashPassword($employe, $employe->getPassword());
            $employe->setPassword($hashedPassword);

            $em->persist($employe);
            $em->flush();

            return $this->redirectToRoute('admin_employes');
        }

        return $this->render('admin/nouveau_employe.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/utilisateur/suspendre/{id}', name: 'admin_suspendre_utilisateur', methods: ['POST'])]
    public function suspendreUtilisateur(Request $request, User $user, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): RedirectResponse
    {
        $submittedToken = $request->request->get('_token');

        if (!$csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken('suspend_user_' . $user->getId(), $submittedToken))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_gestion_utilisateurs');
        }

        $user->setIsSuspended(true);
        $em->flush();

        // Envoyer un email à l'utilisateur pour informer de la suspension

        $this->addFlash('success', 'Utilisateur suspendu avec succès.');

        return $this->redirectToRoute('admin_gestion_utilisateurs');
    }

    #[Route('/admin/utilisateur/supprimer/{id}', name: 'admin_supprimer_utilisateur')]
    public function supprimerUser(int $id, UserRepository $userRepository, EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé');
        }

        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin_gestion_utilisateurs', ['filtre' => 'EMPLOYE']);
    }

   #[Route('/admin/employe/supprimer/{id}', name: 'admin_supprimer_employe', methods: ['POST'])]
    public function supprimerEmploye(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em, CsrfTokenManagerInterface $csrfTokenManager): RedirectResponse {
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

        return $this->redirectToRoute('admin_employes');
    }
}
