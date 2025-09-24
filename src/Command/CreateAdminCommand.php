<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur'
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $roleRepo = $this->entityManager->getRepository(Role::class);

        // Vérifie si le rôle ADMIN existe
        $adminRole = $roleRepo->findOneBy(['libelle' => 'ADMIN']);
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->setLibelle('ADMIN');
            $this->entityManager->persist($adminRole);
        }

        // Vérifie si un admin existe déjà
        $userRepo = $this->entityManager->getRepository(User::class);
        $existingAdmin = $userRepo->findOneBy(['email' => 'admin@ecoride.fr']);

        if ($existingAdmin) {
            $output->writeln('<error>Un administrateur existe déjà avec cet email.</error>');
            return Command::FAILURE;
        }

        // Création de l'admin
        $admin = new User();
        $admin->setEmail('admin@ecoride.fr');
        $admin->setPseudo('SuperAdmin');
        $admin->setNom('Admin');
        $admin->setPrenom('EcoRide');
        $admin->setAdresse('1 Rue du Code');
        $admin->setTelephone('0102030405');
        $admin->setIsVerified(true);
        $admin->setIsChauffeur(false);
        $admin->setIsPassager(false);
        $admin->setDateNaissance(new \DateTime('2000-01-01'));
        $admin->setNote(5);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Admin123!');
        $admin->setPassword($hashedPassword);

        $admin->setRole($adminRole);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $output->writeln('<info>Administrateur créé avec succès !</info>');
        return Command::SUCCESS;
    }
}
