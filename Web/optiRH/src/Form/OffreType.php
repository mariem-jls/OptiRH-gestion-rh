<?php

namespace App\Form;

use App\Entity\Offre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('poste', TextType::class, [
                'attr' => ['placeholder' => 'Ex: Développeur Symfony Senior'],
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
            ])
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Active' => 'Active',
                    'En Attente' => 'En Attente',
                    'Brouillon' => 'Brouillon',
                ],
                'data' => 'Brouillon',
                'placeholder' => 'Choisir un statut',
                'required' => true,
            ])
            ->add('dateCreation', DateTimeType::class, [
                'widget' => 'single_text',
                'disabled' => true,
                'data' => new \DateTime(),
            ])
            ->add('modeTravail', ChoiceType::class, [
                'choices' => [
                    'Télétravail' => 'Télétravail',
                    'Présentiel' => 'Présentiel',
                    'Hybride' => 'Hybride',
                ],
                'placeholder' => 'Choisir un mode de travail',
                'required' => true,
            ])
            ->add('typeContrat', ChoiceType::class, [
                'choices' => [
                    'CDD' => 'CDD',
                    'CDI' => 'CDI',
                    'Freelance' => 'Freelance',
                    'Stage' => 'Stage',
                ],
                'placeholder' => 'Choisir un type de contrat',
                'required' => true,
            ])
            ->add('localisation', TextType::class, [
                'required' => true,
            ])
            ->add('niveauExperience', ChoiceType::class, [
                'choices' => [
                    'Junior' => 'Junior',
                    'Senior' => 'Senior',
                    'Débutant' => 'Débutant',
                ],
                'placeholder' => 'Choisir un niveau d’expérience',
                'required' => true,
            ])
            ->add('nbPostes', NumberType::class, [
                'required' => true,
            ])
            ->add('dateExpiration', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
                'html5' => false,
                'format' => 'yyyy-MM-dd HH:mm',
                'attr' => [
                    'class' => 'flatpickr',
                    'data-date-format' => 'Y-m-d H:i',
                    'data-enable-time' => 'true',
                    'data-time_24hr' => 'true',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offre::class,
        ]);
    }
}