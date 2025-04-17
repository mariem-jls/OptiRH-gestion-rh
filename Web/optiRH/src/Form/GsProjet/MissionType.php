<?php

namespace App\Form\GsProjet;

use App\Entity\GsProjet\Mission;
use App\Entity\GsProjet\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints as Assert;

class MissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la mission*',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le titre est obligatoire'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description'
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut*',
                'choices' => [
                    'To Do' => 'To Do',
                    'In Progress' => 'In Progress',
                    'Done' => 'Done'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le statut est obligatoire'
                    ])
                ]
            ])
            ->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'nom',
                'required' => false,
                'label' => 'Assigné à',
                'placeholder' => 'Non assigné'
            ])
            ->add('dateTerminer', DateType::class, [
                'widget' => 'single_text',
                'required' => true,
                'label' => 'Date de fin prévue*',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date de fin est obligatoire'
                    ]),
                    new Assert\GreaterThan([
                        'value' => 'today',
                        'message' => 'La date doit être postérieure à aujourd\'hui'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control flatpickr',
                    'placeholder' => 'Sélectionnez une date',
                    'data-parsley-date-after-today'=> 'true',
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mission::class,
        ]);
    }
}