<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class)
            ->add('prenom', TextType::class)
            ->add('nom', TextType::class)
            ->add('adresse', TextType::class, [
                'required' => false,
            ])
            ->add('telephone', TelType::class, [
                'required' => false,
            ])
            ->add('isChauffeur', CheckboxType::class, [
                'label' => 'Je suis chauffeur',
                'required' => false,
            ])
            ->add('isPassager', CheckboxType::class, [
                'label' => 'Je suis passager',
                'required' => false,
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil (JPG, PNG)',
                'mapped' => false, 
                'required' => false, 
                'constraints' => [
                    new File([
                        'maxSize' => '5M', 
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG ou PNG).',
                    ])
                ],
            ])
            ->add('deletePhoto', CheckboxType::class, [ 
                'label' => 'Supprimer la photo actuelle',
                'required' => false,
                'mapped' => false, 
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d'),
                    'class' => 'form-input'
                ]
                ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
