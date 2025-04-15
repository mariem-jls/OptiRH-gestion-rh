<?php

namespace App\Form\GsProjet;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


use App\Entity\GsProjet\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom', null, [
            'attr' => [
                'pattern' => '[a-zA-Z0-9À-ÿ\s\-_.,]{3,100}',
                'title' => '3-100 caractères, alphanumériques et -_,.'
            ]
        ])
        ->add('description', TextareaType::class, [
            'attr' => [
                'minlength' => 20,
                'maxlength' => 1000,
                'data-parsley-pattern' => '/^[a-zA-Z0-9À-ÿ\s\-_.,!\?\'"]+$/'
            ]
        ])
        ->add('status', ChoiceType::class, [
            'label' => 'Statut',
            'choices' => [
                'Actif' => Project::STATUS_ACTIVE,
                'En Cour' => Project::STATUS_INACTIVE,
                'Terminé' => Project::STATUS_COMPLETED,
                'En retard' => Project::STATUS_DELAYED
            ],
            'placeholder' => 'Sélectionnez un statut',
            'required' => true, // Rendre obligatoire si besoin
        ])
            ->add('submit', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
