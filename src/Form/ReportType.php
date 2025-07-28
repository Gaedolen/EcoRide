<?php

namespace App\Form;

use App\Entity\Report;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('message', TextareaType::class, [
                'label' => 'Raison du signalement',
                'attr' => ['rows' => 4, 'placeholder' => 'Décrivez la situation...']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer le signalement',
                'attr' => ['class' => 'btn-submit']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}
