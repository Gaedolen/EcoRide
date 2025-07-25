<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;
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

    #[Route('/admin/utilisateurs', name: 'admin_utilisateurs')]
    public function utilisateurs(UserRepository $userRepository, Request $request): Response
    {
        $filtre = $request->query->get('filtre'); // EMPLOYE ou USER

        if ($filtre) {
            $users = $userRepository->findByRole($filtre);
        } else {
            $users = $userRepository->findAll();
        }

        return $this->render('admin/utilisateurs.html.twig', [
            'utilisateurs' => $users,
            'filtre' => $filtre
        ]);
    }

    #[Route('/admin/utilisateur/suspendre/{id}', name: 'admin_suspendre_utilisateur')]
    public function suspendreUser(User $user, EntityManagerInterface $em): RedirectResponse
    {
        $user->setIsSuspended(!$user->isSuspended());
        $em->flush();

        return $this->redirectToRoute('admin_utilisateurs');
    }

    #[Route('/admin/utilisateur/supprimer/{id}', name: 'admin_supprimer_utilisateur')]
    public function supprimerUser(User $user, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin_utilisateurs');
    }
}
