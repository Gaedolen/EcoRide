<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\User;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints as Assert;

class EmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('prenom', TextType::class)
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de naissance',
                'attr' => [
                    'max' => (new \DateTime('-18 years'))->format('Y-m-d'), // limite côté navigateur
                ],
                'constraints' => [
                    new LessThan([
                        'value' => new \DateTime('-18 years'),
                        'message' => "L'employé doit avoir au moins 18 ans.",
                    ])
                ]
            ])
            ->add('email', EmailType::class)
            ->add('telephone', TextType::class)
            ->add('adresse', TextType::class)
            ->add('password', PasswordType::class)
            ->add('pseudo', TextType::class)
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if ($data && $data->getDateNaissance()) {
                $age = (new \DateTime())->diff($data->getDateNaissance())->y;

                if ($age < 18) {
                    $form->get('dateNaissance')->addError(new FormError("L'employé doit avoir au moins 18 ans."));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
