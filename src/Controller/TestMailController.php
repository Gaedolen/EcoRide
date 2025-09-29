<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class TestMailController extends AbstractController
{
    #[Route('/test-mail', name: 'test_mail')]
    public function index(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('ecoride.covoiturage@gmail.com')
            ->to('test@example.com') // peu importe, Mailtrap va le capter
            ->subject('Test Mailtrap')
            ->text('Ceci est un mail de test envoyé depuis Symfony.');

        $mailer->send($email);

        return new Response('Email envoyé (ou tenté)');
    }
}