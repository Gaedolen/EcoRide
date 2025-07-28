<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Avis;
use App\Entity\Report;
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

    #[Route('/employe/moderation-avis', name: 'employe_moderation_avis')]
    public function moderationAvis(EntityManagerInterface $em): Response
    {
        $avisEnAttente = $em->getRepository(Avis::class)->findBy(['isValidated' => false]);

        return $this->render('employe/moderation_avis.html.twig', [
            'avis' => $avisEnAttente
        ]);
    }

    #[Route('/employe/avis/{id}/valider', name: 'employe_valider_avis', methods: ['POST'])]
    public function validerAvis(Avis $avis, EntityManagerInterface $em): RedirectResponse
    {
        $avis->setIsValidated(true);
        $em->flush();

        return $this->redirectToRoute('employe_moderation_avis');
    }

    #[Route('/employe/avis/{id}/refuser', name: 'employe_refuser_avis', methods: ['POST'])]
    public function refuserAvis(Avis $avis, EntityManagerInterface $em): RedirectResponse
    {
        $em->remove($avis);
        $em->flush();

        return $this->redirectToRoute('employe_moderation_avis');
    }

    #[Route('/employe/covoiturages-signales', name: 'employe_covoiturages_signales')]
    public function covoituragesSignales(EntityManagerInterface $em): Response
    {
        $signalements = $em->getRepository(Report::class)->findAll();

        return $this->render('employe/covoiturages_signales.html.twig', [
            'signalements' => $signalements
        ]);
    }

    #[Route('/employe/signaler/{id}', name: 'employe_signaler_utilisateur')]
    public function signalerUtilisateur(Request $request, User $user, Security $security, EntityManagerInterface $em): Response {
        $report = new Report();
        $report->setReportedUser($user);
        $report->setReportedBy($security->getUser());

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($report);
            $em->flush();

            $this->addFlash('success', 'Signalement envoyé à l\'administrateur.');
            return $this->redirectToRoute('employe_gestion_utilisateurs');
        }

        return $this->render('employe/signaler.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }
}
