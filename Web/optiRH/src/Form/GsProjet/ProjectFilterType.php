<?php

namespace App\Form\GsProjet;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectFilterType extends AbstractType
{
    
public function buildForm(FormBuilderInterface $builder, array $options)
{
    $builder
        ->add('search', TextType::class, [
            'required' => false,
            'label' => 'Recherche',
            'attr' => [
                'placeholder' => 'Nom ou description...'
            ]
        ])
        ->add('status', ChoiceType::class, [
            'choices' => [
                'Tous les statuts' => null,
                'Actif' => 'active',
                'En Cour' => 'paused',
                'Terminé' => 'completed'
            ],
            'required' => false,
            'label' => 'Statut'
        ])
        ->add('sort', ChoiceType::class, [
            'choices' => [
                'Nouveaux en premier' => 'newest',
                'Anciens en premier' => 'oldest',
                'Ordre alphabétique' => 'name'
            ],
            'required' => false,
            'label' => 'Trier par'
        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Désactiver la validation CSRF pour les filtres
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }
}