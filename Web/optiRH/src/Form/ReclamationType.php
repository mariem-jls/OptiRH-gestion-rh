<?php
// src/Form/ReclamationType.php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description de votre réclamation',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre problème en détail (10 caractères minimum)...',
                    'class' => 'form-control',
                ],
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de réclamation',
                'choices' => Reclamation::getTypeChoices(),
                'placeholder' => 'Sélectionnez un type',
                'attr' => [
                    'class' => 'form-select',
                ],
                'required' => true,
            ]);

        if ($options['is_admin']) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => Reclamation::getStatusChoices(),
                'attr' => [
                    'class' => 'form-select',
                ],
                'required' => true,
            ]);
            
            $builder->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de la réclamation',
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
            'is_admin' => false,
        ]);
    }
}