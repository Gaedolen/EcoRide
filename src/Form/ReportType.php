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
        if ($options['reported_user_fixed']) {
            $builder->add('reportedUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => false,
                'data' => $options['reported_user'],
                'disabled' => true,
                'attr' => ['hidden' => true],
                'required' => true,
            ]);
        } else {
            $builder
            ->add('reportedUser', EntityType::class, [
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
            ]);
        }

        // Champ message
        $builder->add('message', TextareaType::class, [
            'label' => 'Raison du signalement',
            'attr' => ['rows' => 4, 'placeholder' => 'Décrivez la situation...']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver):void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
            'reported_user_fixed' => false,
            'reported_user' => null,
        ]);
    }
}
