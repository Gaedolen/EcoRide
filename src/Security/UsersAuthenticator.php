<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use App\Repository\UserRepository;
use Symfony\Component\Form\FormFactoryInterface;
use App\Form\LoginFormType;

class UsersAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UserRepository $userRepository,
        private FormFactoryInterface $formFactory
    ) {}

    public function authenticate(Request $request): Passport
    {
        $form = $this->formFactory->create(LoginFormType::class);
        $form->handleRequest($request);

        $email = $form->get('email')->getData();
        $password = $form->get('password')->getData();

        if (!$email || !$password) {
            throw new CustomUserMessageAuthenticationException('Le formulaire n’a pas été soumis correctement.');
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Email ou mot de passe incorrect.');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?RedirectResponse
    {
       // Vérifie d'abord si l'utilisateur avait une URL cible avant login
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // Récupère l'utilisateur connecté
        $user = $token->getUser();

        // Redirection selon le rôle
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        if (in_array('ROLE_EMPLOYE', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('employe_dashboard'));
        }

        // Redirection par défaut pour les autres utilisateurs
        return new RedirectResponse($this->urlGenerator->generate('app_accueil'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
