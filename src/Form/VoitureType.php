<?php

namespace App\Form;

use App\Entity\Voiture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoitureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('modele', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex : 207'
                ]
            ])
            ->add('couleur', \Symfony\Component\Form\Extension\Core\Type\HiddenType::class, [
                'constraints' => [new \Symfony\Component\Validator\Constraints\NotBlank(message: 'Veuillez choisir une couleur.')]
            ])
            ->add('immatriculation', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'AA-123-AA',
                    'pattern' => '[A-Z]{2}-\d{3}-[A-Z]{2}',
                    'title' => 'Format attendu : AA-123-AA'
                ]
            ])
            ->add('energie', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
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
            ->add('datePremiereImmatriculation', \Symfony\Component\Form\Extension\Core\Type\DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input',
                    'max' => (new \DateTime())->format('Y-m-d'),
                    'autocomplete' => 'off'
                ]
            ])
            ->add('marque', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => true,
                'label' => 'Marque',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex : Peugeot'
                ]
            ])
            ->add('nbPlaces', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => true,
                'label' => 'Nombre de places',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex : 5'
                ]
            ])
            ->add('fumeur', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'required' => false,
                'label' => 'Fumeur'
            ])
            ->add('animaux', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'required' => false,
                'label' => 'Animaux'
            ])
            ->add('preferences', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'entry_type' => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : musique forte, climatisation, silence demandé',
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voiture::class,
            // Activation de la protection CSRF
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'voiture_item',
        ]);
    }
}
