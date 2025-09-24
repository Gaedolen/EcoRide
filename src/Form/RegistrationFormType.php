<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L’email est requis.']),
                    new Assert\Email(['message' => 'Veuillez entrer un email valide.']),
                ],
                'attr' => [
                    'placeholder' => 'Email',
                    'class' => 'champ'
                ]
            ])
            ->add('nom', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est requis.']),
                    new Assert\Length(['max' => 50]),
                ],
                'attr' => ['placeholder' => 'Nom', 'class' => 'champ']
            ])
            ->add('prenom', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est requis.']),
                    new Assert\Length(['max' => 50]),
                ],
                'attr' => ['placeholder' => 'Prénom', 'class' => 'champ']
            ])
            ->add('telephone', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le numéro de téléphone est requis.']),
                    new Assert\Regex([
                        'pattern' => '/^(\+33|0)[1-9](\d{2}){4}$/',
                        'message' => 'Veuillez entrer un numéro de téléphone français valide.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Numéro de téléphone', 'class' => 'champ']
            ])
            ->add('adresse', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L’adresse est requise.']),
                    new Assert\Length(['max' => 255]),
                ],
                'attr' => ['placeholder' => 'Adresse', 'class' => 'champ']
            ])
            ->add('date_naissance', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de naissance est requise.']),
                    new Assert\LessThan([
                        'value' => (new \DateTime())->modify('-18 years'),
                        'message' => 'Vous devez avoir au moins 18 ans.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Date de naissance', 'class' => 'champ']
            ])
            ->add('pseudo', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le pseudo est requis.']),
                    new Assert\Length(['min' => 3, 'max' => 50]),
                ],
                'attr' => ['placeholder' => 'Pseudo', 'class' => 'champ']
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => false,
                'constraints' => [
                    new Assert\IsTrue(['message' => 'Vous devez accepter les conditions.']),
                ],
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil (JPG, PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg','image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG ou PNG).',
                    ])
                ],
                'attr' => ['accept' => 'image/*', 'class' => 'champ']
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => true,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Assert\Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit faire au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                        'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'registration_form',
        ]);
    }
}
