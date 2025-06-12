<?php

namespace App\Controller;

use Doctrine\DBAL\Types\DateType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ProfilType;
use DateTime;
use Symfony\Component\Routing\Attribute\Route;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
        public function profil(): Response
    {
        $utilisateur = $this->getUser();
        /** @var \App\Entity\User $utilisateur */

        // Connexion PDO
        $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :id");
        $stmt->execute(['id' => $utilisateur->getId()]);
        $voitures = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->render('profil/profil.html.twig', [
            'user' => $utilisateur,
            'voitures' => $voitures,
        ]);
    }

    #[Route('/profil/modifier', name: 'modifier_profil')]
    public function modifierProfil(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour modifier votre profil.');
        }

        $sessionUser = $this->getUser();
        /** @var \App\Entity\User $sessionUser */

        // Recharge les données à jour depuis la BDD avec PDO
        $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $stmt = $pdo->prepare("SELECT * FROM user WHERE id = :id");
        $stmt->execute(['id' => $sessionUser->getId()]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        // Remplir les champs de l’objet User avec les valeurs de la BDD
        $sessionUser->setPseudo($data['pseudo']);
        $sessionUser->setNom($data['nom']);
        $sessionUser->setPrenom($data['prenom']);
        $sessionUser->setDateNaissance(new DateTime($data['date_naissance']));
        $sessionUser->setAdresse($data['adresse']);
        $sessionUser->setTelephone($data['telephone']);
        $sessionUser->setIsChauffeur((bool) $data['is_chauffeur']);
        $sessionUser->setIsPassager((bool) $data['is_passager']);
        $sessionUser->setPhoto($data['photo']);

        // On crée le formulaire
        $form = $this->createForm(ProfilType::class, $sessionUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            $photoData = null;

            if ($photoFile) {
                $photoData = file_get_contents($photoFile->getPathname());
            }

            $sql = "
                UPDATE user
                SET pseudo = :pseudo,
                    nom = :nom,
                    prenom = :prenom,
                    adresse = :adresse,
                    telephone = :telephone,
                    is_chauffeur = :isChauffeur,
                    is_passager = :isPassager,
                    date_naissance = :dateNaissance
                    ". ($photoData !== null ? ", photo = :photo" : "") . "
                WHERE id = :id";

            $stmt = $pdo->prepare($sql);

            $params = [
                'pseudo' => $sessionUser->getPseudo(),
                'nom' => $sessionUser->getNom(),
                'prenom' => $sessionUser->getPrenom(),
                'adresse' => $sessionUser->getAdresse(),
                'telephone' => $sessionUser->getTelephone(),
                'isChauffeur' => $sessionUser->isChauffeur() ? 1 : 0,
                'isPassager' => $sessionUser->isPassager() ? 1 : 0,
                'id' => $sessionUser->getId(),
                'dateNaissance' => $sessionUser->getDateNaissance()?->format('Y-m-d'),
            ];

            if ($photoData !== null) {
                $params['photo'] = $photoData;
            }

            $stmt->execute($params);

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/modifier.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}