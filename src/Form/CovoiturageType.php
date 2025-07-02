<?php

namespace App\Form;

use App\Entity\Covoiturage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Voiture;

class CovoiturageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_depart', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de départ',
                'attr' => [
                    'class' => 'form-input',
                ]
            ])
            ->add('heure_depart', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de départ',
                'attr' => [
                    'class' => 'form-input',
                ]
            ])
            ->add('lieu_depart', TextType::class, [
                'label' => 'Lieu de départ',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Entrez la ville de départ',
                ]
            ])
            ->add('date_arrivee', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d’arrivée'
            ])
            ->add('heureArrivee', TimeType::class, [
                'label' => 'Heure d\'arrivée',
                'widget' => 'single_text',
                'input' => 'datetime',
                'html5' => true,
            ])
            ->add('lieu_arrivee', TextType::class, [
                'label' => 'Lieu d’arrivée',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Entrez la ville d\'arrivée',
                ]
            ])
            ->add('nb_place', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => [
                    'min' => 1,
                    'step' => 1,
                ],
                'data' => 1,
            ])
            ->add('prixPersonne', NumberType::class, [
                'label' => 'Prix par personne',
                'scale' => 2,
                'html5' => true,
                'attr' => ['class' => 'form-input prix-input'],
            ])
            ->add('voiture', EntityType::class, [
                'class' => Voiture::class,
                'label' => 'Voiture utilisée',
                'choice_label' => function (Voiture $v) {
                    return $v->getMarque() . ' ' . $v->getModele() . ' (' . $v->getImmatriculation() . ')';
                },
                'placeholder' => 'Choisissez une voiture',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($options) {
                    if (!isset($options['user'])) {
                        return $er->createQueryBuilder('v')->where('1=0');
                    }
                    return $er->createQueryBuilder('v')
                        ->where('v.utilisateur = :user')
                        ->setParameter('user', $options['user']);
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Covoiturage::class,
            'user' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'covoiturage',
        ]);
    }
}
