<?php 

namespace App\Service;

use App\Entity\User;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;

class NoteUpdater
{
    private AvisRepository $avisRepository;
    private EntityManagerInterface $em;

    public function __construct(AvisRepository $avisRepository, EntityManagerInterface $em)
    {
        $this->avisRepository = $avisRepository;
        $this->em = $em;
    }

    public function updateNoteForUser(User $user): void
    {
        $moyenne = $this->avisRepository->getMoyenneNotesPourUser($user);

        $user->setNote($moyenne);
        $this->em->persist($user);
        $this->em->flush();
    }
}
