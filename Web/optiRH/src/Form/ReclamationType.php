<?php
// src/Form/ReclamationType.php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description de votre réclamation',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre problème en détail...',
                    'minlength' => 5
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de réclamation',
                'choices' => array_flip(Reclamation::getTypeChoices()),
                'placeholder' => 'Sélectionnez un type',
                'attr' => [
                    'class' => 'form-select',
                    'required' => true
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => array_flip(Reclamation::getStatusChoices()),
                'data' => Reclamation::STATUS_PENDING,
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}