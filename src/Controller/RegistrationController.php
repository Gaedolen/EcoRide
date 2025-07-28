<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Form\RegistrationFormType;
use App\Security\UsersAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Photo de profil
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $binaryData = file_get_contents($photoFile->getPathname());
                $user->setPhoto($binaryData);
            }

            // Affectation du rôle "USER"
            $roleUser = $entityManager->getRepository(Role::class)->findOneBy(['libelle' => 'USER']);
            if (!$roleUser) {
                $roleUser = new Role();
                $roleUser->setLibelle('USER');
                $entityManager->persist($roleUser);
            }
            $user->setRole($roleUser);

            // Encodage du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Activation directe du compte :
            $user->setIsVerified(true);

            // Enregistrement en base
            $entityManager->persist($user);
            $entityManager->flush();

            // Connexion automatique
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
