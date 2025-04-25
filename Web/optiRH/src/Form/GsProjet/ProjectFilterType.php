<?php

namespace App\Form\GsProjet;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\GsProjet\Project;
use Symfony\Component\Form\Extension\Core\Type\SubmitType; // Ajoutez cette ligne
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProjectFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => 'Recherche',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Nom ou description...',
                    'class' => 'form-control'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'choices' => [
                    'Actif' => Project::STATUS_ACTIVE,
                    'En Cour' => Project::STATUS_INACTIVE,
                    'Terminé' => Project::STATUS_COMPLETED,
                    'En retard' => Project::STATUS_DELAYED
                ],
                'placeholder' => 'Tous les statuts',
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'data_class' => null // Important pour un formulaire sans entité
        ]);
    }
}