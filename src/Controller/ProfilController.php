<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Voiture;
use App\Form\ProfilType;
use App\Form\VoitureType;
use DateTime;
use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;

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

        $photoBase64 = $utilisateur->getPhotoData();

        $pdo = new PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

        // Récupération des voitures
        $stmt = $pdo->prepare("SELECT * FROM voiture WHERE utilisateur_id = :id");
        $stmt->execute(['id' => $utilisateur->getId()]);
        $voituresData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $voitureForms = [];
        foreach ($voituresData as $index => $row) {
            $voiture = new Voiture();
            $voiture
                ->setId((int) $row['id'])
                ->setMarque($row['marque'])
                ->setModele($row['modele'])
                ->setImmatriculation($row['immatriculation'])
                ->setDatePremiereImmatriculation(new DateTime($row['date_premiere_immatriculation']))
                ->setNbPlaces((int)$row['nb_places'])
                ->setEnergie($row['energie'])
                ->setCouleur($row['couleur'])
                ->setFumeur((bool) $row['fumeur'])
                ->setAnimaux((bool) $row['animaux']);

            $form = $formFactory->createNamed("voiture_{$index}", VoitureType::class, $voiture);
            $voitureForms[] = $form->createView();
        }

        // Récupération des covoiturages
        $covoiturages = [];
        if ($utilisateur->isChauffeur()) {
            $stmt = $pdo->prepare("SELECT * FROM covoiturage WHERE utilisateur_id = :id");
            $stmt->execute(['id' => $utilisateur->getId()]);
            $covoiturages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->render('profil/profil.html.twig', [
            'user' => $utilisateur, 
            'voitureForms' => $voitureForms,
            'voituresData' => $voituresData,
            'photoBase64' => $photoBase64, 
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

        $pdo = new PDO('mysql:host=localhost;dbname=ecoride;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $form = $this->createForm(ProfilType::class, $sessionUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData(); 
            $deletePhoto = $form->get('deletePhoto')->getData(); 

            $binaryPhotoContent = null; 

            if ($deletePhoto) {
                $binaryPhotoContent = null; 
            } elseif ($photoFile) {
                $binaryPhotoContent = file_get_contents($photoFile->getPathname()); 
            } else {
                $currentPhoto = $sessionUser->getPhoto();
                if ($currentPhoto && is_resource($currentPhoto)) {
                    $binaryPhotoContent = stream_get_contents($currentPhoto);
                } elseif ($currentPhoto) {
                    $binaryPhotoContent = $currentPhoto;
                }
            }

            $sessionUser->setPhoto($binaryPhotoContent);

            // Préparer la requête UPDATE pour PDO.
            $setClauses = [
                'pseudo = :pseudo',
                'nom = :nom',
                'prenom = :prenom',
                'adresse = :adresse',
                'telephone = :telephone',
                'is_chauffeur = :isChauffeur',
                'is_passager = :isPassager',
                'date_naissance = :dateNaissance',
                'photo = :photo',
            ];

            $sql = "UPDATE user SET " . implode(", ", $setClauses) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            if ($binaryPhotoContent === null) {
                $stmt->bindValue(':photo', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindParam(':photo', $binaryPhotoContent, PDO::PARAM_LOB);
            }

            // Lie les autres paramètres
            $stmt->bindValue(':pseudo', $sessionUser->getPseudo());
            $stmt->bindValue(':nom', $sessionUser->getNom());
            $stmt->bindValue(':prenom', $sessionUser->getPrenom());
            $stmt->bindValue(':adresse', $sessionUser->getAdresse());
            $stmt->bindValue(':telephone', $sessionUser->getTelephone());
            $stmt->bindValue(':isChauffeur', $sessionUser->isChauffeur() ? 1 : 0);
            $stmt->bindValue(':isPassager', $sessionUser->isPassager() ? 1 : 0);
            $stmt->bindValue(':dateNaissance', $sessionUser->getDateNaissance()?->format('Y-m-d'));
            $stmt->bindValue(':id', $sessionUser->getId());

            $stmt->execute(); // Exécute la requête après avoir lié tous les paramètres

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profil');
        }

        $currentPhotoBase64 = null;
        $currentPhotoBase64 = $sessionUser->getPhotoData();

        return $this->render('profil/modifier.html.twig', [
            'form' => $form->createView(),
            'user' => $sessionUser,
            'currentPhotoBase64' => $sessionUser->getPhotoData(),
        ]);
    }
}