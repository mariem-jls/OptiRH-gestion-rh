<?php

namespace App\Form\GsProjet;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('status', ChoiceType::class, [
            'label' => 'Statut',
            'choices' => [
                'En cours' => 'En cours',
                'Terminé' => 'Terminé',
                'Annulé' => 'Annulé',
            ]
        ])
            ->add('sort', ChoiceType::class, [
                'label' => 'Trier par',
                'choices' => [
                    'Date de création (récent)' => 'createdAt DESC',
                    'Date de création (ancien)' => 'createdAt ASC',
                    'Nom (A-Z)' => 'nom ASC',
                ],
                'required' => false,
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