<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\EmployeType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\User;
use App\Entity\Role;
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

    #[Route('/admin/utilisateur/suspendre/{id}', name: 'admin_suspendre_utilisateur')]
    public function suspendreUser(User $user, EntityManagerInterface $em): RedirectResponse
    {
        $user->setIsSuspended(!$user->isSuspended());
        $em->flush();

        return $this->redirectToRoute('admin_utilisateurs');
    }

    #[Route('/admin/utilisateur/supprimer/{id}', name: 'admin_supprimer_utilisateur')]
    public function supprimerUser(int $id, EntityManagerInterface $em, UserRepository $userRepository, Request $request): RedirectResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw new NotFoundHttpException('Utilisateur non trouvé');
        }

        $em->remove($user);
        $em->flush();

        $filtre = $request->query->get('filtre');

        if ($filtre) {
            return $this->redirectToRoute('admin_utilisateurs', ['filtre' => $filtre]);
        }

        return $this->redirectToRoute('admin_utilisateurs');
    }
}
