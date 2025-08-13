<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Report;
use App\Entity\User;
use App\Entity\Covoiturage;
use App\Form\ReportType;
use App\Repository\UserRepository;
use App\Repository\CovoiturageRepository;

class ReportController extends AbstractController
{
    #[Route('/report/new/{covoiturageId}/{reportedUserId}', name: 'report_new', methods: ['GET', 'POST'])]
    public function signalerCovoiturage(int $covoiturageId, int $reportedUserId, Request $request, EntityManagerInterface $em, UserRepository $userRepo, CovoiturageRepository $covoiturageRepo): Response {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            throw $this->createAccessDeniedException();
        }

        $reportedUser = $userRepo->find($reportedUserId);
        $covoiturage = $covoiturageRepo->find($covoiturageId);

        if (!$reportedUser || !$covoiturage) {
            throw $this->createNotFoundException("Utilisateur ou covoiturage introuvable");
        }

        $report = new Report();
        $report->setReportedUser($reportedUser);
        $report->setReportedBy($currentUser);
        $report->setCovoiturage($covoiturage);

        $form = $this->createForm(ReportType::class, $report, [
            'reported_user_fixed' => true,
            'reported_user' => $reportedUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($report);
            $em->flush();

            return $this->json(['success' => true, 'message' => 'Signalement envoyé avec succès']);
        }

        return $this->render('report/_form_popup.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/report', name: 'report_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser(); // utilisateur qui signale
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 403);
        }

        $message = $request->request->get('message');
        $reportedUserId = $request->request->get('reported_user_id');
        $covoiturageId = $request->request->get('covoiturage_id');

        if (!$message) {
            return new JsonResponse(['success' => false, 'message' => 'message manquant'], 400);
        }
        if (!$reportedUserId) {
            return new JsonResponse(['success' => false, 'message' => 'reportedUserId manquant'], 400);
        }
        if (!$covoiturageId) {
            return new JsonResponse(['success' => false, 'message' => 'covoiturageId manquant'], 400);
        }

        // On récupère les références des entités
        $reportedUser = $em->getRepository(User::class)->find($reportedUserId);
        $covoiturage = $em->getRepository(Covoiturage::class)->find($covoiturageId);

        if (!$reportedUser || !$covoiturage) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur ou covoiturage introuvable'], 404);
        }

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