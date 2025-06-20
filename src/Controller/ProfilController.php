<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Voiture;
use App\Form\CovoiturageType;
use App\Form\ProfilType;
use App\Form\VoitureType;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function profil(Request $request, FormFactoryInterface $formFactory): Response
    {
        /** @var \App\Entity\User $utilisateur */
        $utilisateur = $this->getUser();

        if (!$utilisateur) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir votre profil.');
        }

        // Préparation de la photo
        $photoData = null;
        if ($utilisateur->getPhoto() && is_resource($utilisateur->getPhoto())) {
            $photo = $utilisateur->getPhoto();

            if ($photo !== null) {
                $photoData = base64_encode($photo);
            } else {
                $photoData = null;
            }

            $utilisateur->setPhoto($photoData); 
        }

        $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');

        // Récupération des voitures
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :id");
        $stmt->execute(['id' => $utilisateur->getId()]);
        $voituresData = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $voitureForms = [];
        foreach ($voituresData as $index => $row) {
            $voiture = new Voiture();
            $voiture
                ->setId((int) $row['id'])
                ->setMarque($row['marque'])
                ->setModele($row['modele'])
                ->setImmatriculation($row['immatriculation'])
                ->setDatePremiereImmatriculation(new DateTime($row['date_premiere_immatriculation']))
                ->setNbPlaces($row['nb_places'])
                ->setEnergie($row['energie'])
                ->setCouleur($row['couleur'])
                ->setFumeur((bool) $row['fumeur'])
                ->setAnimaux((bool) $row['animaux']);

            $form = $formFactory->createNamed("voiture_{$index}", VoitureType::class, $voiture);
            $voitureForms[] = $form->createView();
        }

        // Récupérer les covoiturages du chauffeur
        $covoiturages = [];
        if ($utilisateur->isChauffeur()) {
            $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE utilisateur_id = :id");
            $stmt->execute(['id' => $utilisateur->getId()]);
            $covoiturages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $this->render('profil/profil.html.twig', [
            'user' => $utilisateur,
            'voitureForms' => $voitureForms,
            'voituresData' => $voituresData,
            'photoBase64' => $photoData,
            'covoiturages' => $covoiturages,
        ]);
    }

    #[Route('/profil/modifier', name: 'modifier_profil')]
    public function modifierProfil(Request $request): Response
    {
        /** @var \App\Entity\User $sessionUser */
        $sessionUser = $this->getUser();

        if (!$sessionUser) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour modifier votre profil.');
        }

        $pdo = new \PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $stmt = $pdo->prepare("SELECT * FROM user WHERE id = :id");
        $stmt->execute(['id' => $sessionUser->getId()]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $sessionUser->setPseudo($data['pseudo']);
        $sessionUser->setNom($data['nom']);
        $sessionUser->setPrenom($data['prenom']);
        $sessionUser->setDateNaissance(new DateTime($data['date_naissance']));
        $sessionUser->setAdresse($data['adresse']);
        $sessionUser->setTelephone($data['telephone']);
        $sessionUser->setIsChauffeur((bool) $data['is_chauffeur']);
        $sessionUser->setIsPassager((bool) $data['is_passager']);

        $photo = $data['photo'];
        if (is_resource($photo)) {
            $photo = stream_get_contents($photo);
        }
        $sessionUser->setPhoto($photo);

        $form = $this->createForm(ProfilType::class, $sessionUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();
            $deletePhoto = $form->get('deletePhoto')->getData();
            $photoData = null;

            if ($deletePhoto) {
                $photoData = null;
                $sessionUser->setPhoto(null);
            } elseif ($photoFile) {
                $photoData = file_get_contents($photoFile->getPathname());
                $sessionUser->setPhoto($photoData);
            }

            $setClauses = [
                'pseudo = :pseudo',
                'nom = :nom',
                'prenom = :prenom',
                'adresse = :adresse',
                'telephone = :telephone',
                'is_chauffeur = :isChauffeur',
                'is_passager = :isPassager',
                'date_naissance = :dateNaissance',
            ];

            if ($photoData !== null || $deletePhoto) {
                $setClauses[] = 'photo = :photo';
            }

            $sql = "UPDATE user SET " . implode(", ", $setClauses) . " WHERE id = :id";

            $stmt = $pdo->prepare($sql);

            $params = [
                'pseudo' => $sessionUser->getPseudo(),
                'nom' => $sessionUser->getNom(),
                'prenom' => $sessionUser->getPrenom(),
                'adresse' => $sessionUser->getAdresse(),
                'telephone' => $sessionUser->getTelephone(),
                'isChauffeur' => $sessionUser->isChauffeur() ? 1 : 0,
                'isPassager' => $sessionUser->isPassager() ? 1 : 0,
                'dateNaissance' => $sessionUser->getDateNaissance()?->format('Y-m-d'),
                'id' => $sessionUser->getId(),
            ];

            if ($photoData !== null || $deletePhoto) {
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
