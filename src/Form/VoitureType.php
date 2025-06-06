<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Voiture;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Config\Framework\Workflows\WorkflowsConfig\PlaceConfig;

class VoitureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('modele', TextType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex : 207'
                ]
            ])
            ->add('immatriculation', TextType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'AA-123-AA',
                    'pattern' => '[A-Z]{2}-\d{3}-[A-Z]{2}',
                    'title' => 'Format attendu : AA-123-AA'
                ]
            ])
            ->add('energie', ChoiceType::class, [
                'choices' => [
                    'Essence' => 'Essence',
                    'Diesel' => 'Diesel',
                    'Électrique' => 'Électrique',
                    'Hybride essence' => 'Hybride essence',
                    'Hybride diesel' => 'Hybride diesel',
                    'GPL' => 'GPL',
                    'Bioéthanol (E85)' => 'Bioéthanol',
                    'Hydrogène' => 'Hydrogène',
                ],
                'placeholder' => 'Sélectionnez le type d\'énergie',
                'required' => true,
                'attr' => [
                    'class' => 'select-custom'
                ]
            ])
            ->add('datePremiereImmatriculation', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input',
                    'max' => (new \DateTime())->format('Y-m-d'),
                    'placeholder' => 'jj/mm/aaaa',
                    'autocomplete' => 'off'
                ]
            ])
            ->add('marque', TextType::class, [
                'required' => true,
                'label' => 'Marque',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex : Peugeot'
                ]
            ])
            ->add('nbPlaces', IntegerType::class, [
                'required' => true,
                'label' => 'Nombre de places',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex : 5'
                ]
            ])
            ->add('fumeur', CheckboxType::class, [
                'required' => false,
                'label' => 'Fumeur'
            ])
            ->add('animaux', CheckboxType::class, [
                'required' => false,
                'label' => 'Animaux'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voiture::class,
        ]);
    }
}
