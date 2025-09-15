<?php

namespace App\Form;

use App\Entity\Voiture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;

class VoitureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('modele', TextType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex : 207'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez indiquer le modèle de la voiture.']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('couleur', HiddenType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir une couleur.'])
                ]
            ])
            ->add('immatriculation', TextType::class, [
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'AA-123-AA',
                    'pattern' => '[A-Z]{2}-\d{3}-[A-Z]{2}',
                    'title' => 'Format attendu : AA-123-AA'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir l’immatriculation.']),
                    new Regex([
                        'pattern' => '/^[A-Z]{2}-\d{3}-[A-Z]{2}$/',
                        'message' => 'Le format attendu est AA-123-AA.'
                    ])
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
                'attr' => ['class' => 'select-custom'],
                'constraints' => [new NotBlank(['message' => 'Veuillez sélectionner le type d’énergie.'])]
            ])
            ->add('datePremiereImmatriculation', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir la date de première immatriculation.']),
                    new LessThanOrEqual([
                        'value' => (new \DateTime())->format('Y-m-d'),
                        'message' => 'La date ne peut pas être dans le futur.'
                    ])
                ]
            ])
            ->add('marque', TextType::class, [
                'required' => true,
                'label' => 'Marque',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex : Peugeot'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir la marque de la voiture.']),
                    new Length(['max' => 100])
                ]
            ])
            ->add('nbPlaces', IntegerType::class, [
                'required' => true,
                'label' => 'Nombre de places',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Ex : 5'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez indiquer le nombre de places.']),
                    new Range([
                        'min' => 1,
                        'max' => 10,
                        'notInRangeMessage' => 'Le nombre de places doit être entre {{ min }} et {{ max }}.'
                    ])
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
            ->add('preferences', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new Length(['max' => 255])
                    ]
                ],
                'attr' => [
                    'placeholder' => 'Ex : musique forte, climatisation, silence demandé'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voiture::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'voiture_item',
        ]);
    }
}
