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


class MissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la mission'
            ])
            ->add('description', TextareaType::class, [
                'required' => false
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'To Do' => 'To Do',
                    'In Progress' => 'In Progress',
                    'Done' => 'Done'
                ]
            ])
         
            ->add('assignedTo', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Non assigné'
            ])
            ->add('dateTerminer', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date de fin prévue',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'form-control flatpickr',
                    'placeholder' => 'Date limite*'
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