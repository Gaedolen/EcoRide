<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixture extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $roleRepo = $manager->getRepository(Role::class);
        $userRepo = $manager->getRepository(User::class);

        // Cherche si le rôle ADMIN existe
        $adminRole = $roleRepo->findOneBy(['libelle' => 'ADMIN']);
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->setLibelle('ADMIN');
            $manager->persist($adminRole);
        }

        // Cherche si l'admin existe déjà
        $existingAdmin = $userRepo->findOneBy(['email' => 'admin@ecoride.fr']);
        if (!$existingAdmin) {
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

            $hashedPassword = $this->hasher->hashPassword($admin, 'Admin123!');
            $admin->setPassword($hashedPassword);

            $admin->setRole($adminRole);

            $manager->persist($admin);
        }

        $manager->flush();
    }
}
