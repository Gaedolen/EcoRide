<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use \PDO;

class CovoiturageController extends AbstractController
{
    #[Route('/covoiturage', name: 'app_covoiturage')]
    public function rechercher(Request $request): Response
    {
        $depart = $request->query->get('lieu_depart');
        $arrivee = $request->query->get('lieu_arrivee');
        $date = $request->query->get('date_depart');
        $heure = $request->query->get('heure_depart');

        //Connexion PDO manuelle
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //Requête préparée sécurisée
        $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE lieu_depart = :depart AND lieu_arrivee = :arrivee AND date_depart = :date AND heure_depart >= :heure");

        $stmt->execute([
            ':depart' => $depart,
            ':arrivee' => $arrivee,
            ':date' => $date,
            ':heure' => $heure,
        ]);

        $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
        return $this->render('covoiturage/resultats.html.twig', [
            'controller_name' => 'CovoiturageController',
            'trajets' => $trajets,
        ]);
    }
}
