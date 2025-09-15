<?php

namespace App\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;
use App\Entity\Report;
use App\Repository\UserRepository;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\AbstractType;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Champ utilisateur signalé
        if ($options['reported_user_fixed']) {
            $builder->add('reportedUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => false,
                'data' => $options['reported_user'],
                'disabled' => true,
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(['message' => 'L’utilisateur à signaler est requis.']),
                ],
                'attr' => ['hidden' => true],
            ]);
        } else {
            $builder->add('reportedUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'placeholder' => 'Sélectionnez un utilisateur',
                'query_builder' => function(UserRepository $repo) {
                    return $repo->createQueryBuilder('u')
                                ->join('u.role', 'r')
                                ->where('r.libelle = :role')
                                ->setParameter('role', 'USER')
                                ->orderBy('u.pseudo', 'ASC');
                },
                'constraints' => [
                    new Assert\NotNull(['message' => 'Vous devez sélectionner un utilisateur.']),
                ],
            ]);
        }

        // Champ message avec contraintes
        $builder->add('message', TextareaType::class, [
            'label' => 'Raison du signalement',
            'attr' => [
                'rows' => 4,
                'placeholder' => 'Décrivez la situation...'
            ],
            'constraints' => [
                new Assert\NotBlank(['message' => 'Veuillez saisir un message.']),
                new Assert\Length([
                    'min' => 10,
                    'max' => 1000,
                    'minMessage' => 'Le message doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'Le message ne peut pas dépasser {{ limit }} caractères.',
                ]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
            'reported_user_fixed' => false,
            'reported_user' => null,
        ]);
    }
}

