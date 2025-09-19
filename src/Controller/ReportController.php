<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Report;
use App\Entity\User;
use App\Entity\Covoiturage;

class ReportController extends AbstractController
{
    #[Route('/report', name: 'report_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('report_user', $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Token CSRF invalide.'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 403);
        }

        $reportedUserId = $request->request->get('reported_user_id');
        $covoiturageId = $request->request->get('covoiturage_id');
        $message = $request->request->get('message');

        if (!$reportedUserId || !$covoiturageId || !$message) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
        }

        $reportedUser = $em->getRepository(User::class)->find($reportedUserId);
        $covoiturage = $em->getRepository(Covoiturage::class)->find($covoiturageId);

        if (!$reportedUser || !$covoiturage) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur ou covoiturage introuvable'], 404);
        }

        // Vérifie si le signalement existe déjà pour ce covoiturage et cet utilisateur
        $existingReport = $em->getRepository(Report::class)->findOneBy([
            'reportedBy' => $user,
            'reportedUser' => $reportedUser,
            'covoiturage' => $covoiturage,
        ]);

        $report = new Report();
        $report->setReportedBy($user)
            ->setReportedUser($reportedUser)
            ->setCovoiturage($covoiturage)
            ->setMessage($message)
            ->setStatut(Report::STATUT_EN_ATTENTE);

        $em->persist($report);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
