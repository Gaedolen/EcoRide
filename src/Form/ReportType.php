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
            // Champ reportedUser caché, pas modifiable, juste valeur fixe
            $builder->add('reportedUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => false,
                'data' => $options['reported_user'], // injecté depuis controller
                'disabled' => true,
                'attr' => ['hidden' => true],
                'required' => true,
            ]);
        } else {
            $builder->add('reportedUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => 'Utilisateur signalé',
                'placeholder' => 'Sélectionnez un utilisateur',
                'query_builder' => function (UserRepository $ur) {
                    return $ur->createQueryBuilder('u')
                        ->where('u.role = 3')
                        ->orderBy('u.pseudo', 'ASC');
                },
            ]);
        }

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
