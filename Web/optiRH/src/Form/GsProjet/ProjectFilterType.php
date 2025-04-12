<?php

namespace App\Form\GsProjet;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\GsProjet\Project;


class ProjectFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'required' => false,
                'attr' => ['placeholder' => 'Rechercher...']
            ])
           
            ->add('status', ChoiceType::class, [
                'choices' => array_flip(Project::getStatusChoices()), // Label => valeur
                'required' => false,
                'placeholder' => 'Tous les statuts',
                'label' => false
            ])
            
            ->add('sort', ChoiceType::class, [
                'required' => false,
                'label' => 'Trier par',
                'choices' => [
                    'Date de création' => 'createdAt',
                    'Nom' => 'nom',
                    'Statut' => 'status'
                ],
                'empty_data' => 'createdAt' // Valeur par défaut si non soumis
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ]);
    }
}