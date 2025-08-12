<?php

namespace App\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;
use App\Entity\Report;
use App\Repository\UserRepository;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
                ->add('reportedUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => 'Utilisateur signalé',
                'placeholder' => 'Sélectionnez un utilisateur',
                'query_builder' => function (UserRepository $ur) {
                    return $ur->createQueryBuilder('u')
                        ->where('u.role = 3')
                        ->orderBy('u.pseudo', 'ASC');
                },
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Raison du signalement',
                'attr' => ['rows' => 4, 'placeholder' => 'Décrivez la situation...']
            ]);
    }
}
