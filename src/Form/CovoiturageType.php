<?php

namespace App\Form;

use App\Entity\Covoiturage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
                'label' => 'Date de départ'
            ])
            ->add('heure_depart', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de départ'
            ])
            ->add('lieu_depart', TextType::class, [
                'label' => 'Lieu de départ'
            ])
            ->add('date_arrivee', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d’arrivée'
            ])
            ->add('heure_arrivee', TextType::class, [
                'label' => 'Heure d’arrivée (HH:MM)'
            ])
            ->add('lieu_arrivee', TextType::class, [
                'label' => 'Lieu d’arrivée'
            ])
            ->add('nb_place', NumberType::class, [
                'label' => 'Nombre de places disponibles'
            ])
            ->add('prix_personne', NumberType::class, [
                'label' => 'Prix par personne (€)',
                'scale' => 2
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
        ]);
    }
}
