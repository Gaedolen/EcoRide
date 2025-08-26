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

        // Total des crédits gagnés
        $totalCredits = array_sum(array_column($creditsParJour, 'credits_plateforme'));

        //dd($covoituragesParJour, $creditsParJour, $totalCredits);

        return $this->render('admin/dashboard.html.twig', [
            'covoituragesParJour' => $covoituragesParJour,
            'creditsParJour' => $creditsParJour,
            'totalCredits' => $totalCredits ?? 0
        ]);
    }

    #[Route('/admin/utilisateurs', name: 'admin_gestion_utilisateurs')]
    public function gestionUtilisateurs(EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        // Récupérer uniquement les utilisateurs avec le rôle USER
        $users = $userRepository->findByRoleLibelle('USER');

        // Récupérer tous les signalements faits par les employés
        $reports = $em->getRepository(Report::class)->findAll();

        $reportsByUser = [];
        foreach ($reports as $report) {
            $reportedBy = $report->getReportedBy();
            if ($reportedBy && $reportedBy->getRole()->getLibelle() === 'EMPLOYE') {
                $userId = $report->getReportedUser()->getId();
                if (!isset($reportsByUser[$userId])) {
                    $reportsByUser[$userId] = [];
                }
                $reportsByUser[$userId][] = $report;
            }
        }

        return $this->render('admin/gestion_utilisateurs.html.twig', [
            'users' => $users,
            'reportsByUser' => $reportsByUser,
        ]);
    }

    #[Route('/admin/utilisateur/suspendre/{id}', name: 'admin_suspendre_utilisateur', methods: ['POST'])]
    public function suspendreUtilisateur(Request $request, User $user, EntityManagerInterface $em, MailerInterface $mailer): Response {
        // Récupérer les données JSON envoyées par fetch
        $data = json_decode($request->getContent(), true);

        // Vérifier que les données existent
        if (!$data || !isset($data['_token'])) {
            return $this->json(['success' => false, 'error' => 'Données manquantes'], 400);
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('suspend_user_' . $user->getId(), $data['_token'])) {
            return $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 400);
        }

        // Déterminer la raison complète de la suspension
        $reason = $data['reason'] ?? null;
        if ($reason === 'autres') {
            $reason = $data['otherReason'] ?? '';
        }

        // Enregistrer la suspension et la raison
        $user->setIsSuspended(true);
        $user->setSuspendReason($reason);
        $em->flush();

        // Envoyer le mail à l'utilisateur
        $email = (new TemplatedEmail())
            ->from('no-reply@ecoride.com')
            ->to($user->getEmail())
            ->subject('Votre compte a été suspendu')
            ->htmlTemplate('emails/suspension.html.twig')
            ->context([
                'user' => $user,
                'reason' => $reason
            ]);
        $mailer->send($email);

        // Répondre au front-end
        return $this->json([
            'success' => true,
            'userId' => $user->getId(),
            'reason' => $reason
        ]);
    }

    #[Route('/admin/utilisateur/{id}/unsuspendre', name: 'admin_unsuspendre_utilisateur', methods: ['POST'])]
    public function unsuspendUser(Request $request, User $user, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if (!$this->isCsrfTokenValid('unsuspend_user_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_gestion_utilisateurs');
        }

        $user->setIsSuspended(false);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

         // Envoi du mail de réactivation
        $email = (new TemplatedEmail())
            ->from('no-reply@ecoride.com')
            ->to($user->getEmail())
            ->subject('Votre compte a été réactivé')
            ->htmlTemplate('emails/reactivation_utilisateur.html.twig')
            ->context([
                'user' => $user,
            ]);

        $mailer->send($email);

        return $this->redirectToRoute('admin_gestion_utilisateurs');
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
