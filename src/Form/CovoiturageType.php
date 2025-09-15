<?php

namespace App\Form;

use App\Entity\Covoiturage;
use App\Entity\Voiture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

class CovoiturageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_depart', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de départ',
                'html5' => true,
                'attr' => ['min' => (new \DateTime())->format('Y-m-d'), 'class' => 'form-input'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThanOrEqual('today', message: 'La date de départ doit être aujourd’hui ou plus tard.'),
                ],
            ])
            ->add('heure_depart', TimeType::class, [
                'widget' => 'single_text',
                'label' => 'Heure de départ',
                'attr' => ['class' => 'form-input'],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('lieu_depart', TextType::class, [
                'label' => 'Lieu de départ',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Entrez la ville de départ'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['max' => 255])],
            ])
            ->add('date_arrivee', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d’arrivée',
                'html5' => true,
                'attr' => ['min' => (new \DateTime())->format('Y-m-d'), 'class' => 'form-input'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThanOrEqual('today', message: 'La date d’arrivée doit être aujourd’hui ou plus tard.'),
                ],
            ])
            ->add('heureArrivee', TimeType::class, [
                'label' => 'Heure d\'arrivée',
                'widget' => 'single_text',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('lieu_arrivee', TextType::class, [
                'label' => 'Lieu d’arrivée',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Entrez la ville d\'arrivée'],
                'constraints' => [new Assert\NotBlank(), new Assert\Length(['max' => 255])],
            ])
            ->add('nb_place', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => ['min' => 1, 'step' => 1],
                'data' => 1,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                    new Assert\LessThanOrEqual(10, message: 'Le nombre de places ne peut pas dépasser 10.'),
                ],
            ])
            ->add('prixPersonne', NumberType::class, [
                'label' => 'Prix par personne',
                'scale' => 2,
                'html5' => true,
                'attr' => ['class' => 'form-input prix-input'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\PositiveOrZero(),
                    new Assert\LessThanOrEqual(1000, message: 'Le prix est trop élevé.'),
                ],
            ])
            ->add('voiture', EntityType::class, [
                'class' => Voiture::class,
                'label' => 'Voiture utilisée',
                'choice_label' => fn(Voiture $v) => $v->getMarque() . ' ' . $v->getModele() . ' (' . $v->getImmatriculation() . ')',
                'placeholder' => 'Choisissez une voiture',
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($options) {
                    if (!isset($options['user'])) return $er->createQueryBuilder('v')->where('1=0');
                    return $er->createQueryBuilder('v')
                        ->where('v.utilisateur = :user')
                        ->setParameter('user', $options['user']);
                },
                'constraints' => [new Assert\NotNull(message: 'Veuillez choisir une voiture.')],
            ]);

        // Validation logique date/heure
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if ($data->getDateArrivee() < $data->getDateDepart() ||
                ($data->getDateArrivee() == $data->getDateDepart() && $data->getHeureArrivee() <= $data->getHeureDepart())
            ) {
                $form->get('date_arrivee')->addError(new FormError('La date et l’heure d’arrivée doivent être après la date et l’heure de départ.'));
            }
        });
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
