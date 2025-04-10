<?php

namespace App\Form;

use App\Entity\Offre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('poste')
            ->add('description')
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'Active' => 'Active',
                    'En Attente' => 'En Attente',
                    'Brouillon' => 'Brouillon',
                ],
                'data' => 'Brouillon', // Valeur par défaut
                'placeholder' => 'Choisir un statut',
                'required' => true,
            ])
            ->add('dateCreation', DateTimeType::class, [
                'widget' => 'single_text',
                'disabled' => true, // Non modifiable
                'data' => new \DateTime(), // Par défaut aujourd'hui
            ])
            ->add('modeTravail', ChoiceType::class, [
                'choices' => [
                    'Télétravail' => 'Télétravail',
                    'Présentiel' => 'Présentiel',
                    'Hybride' => 'Hybride',
                ],
                'placeholder' => 'Choisir un mode de travail',
                'required' => false,
            ])
            ->add('typeContrat', ChoiceType::class, [
                'choices' => [
                    'CDD' => 'CDD',
                    'CDI' => 'CDI',
                    'Freelance' => 'Freelance',
                    'Stage' => 'Stage',
                ],
                'placeholder' => 'Choisir un type de contrat ',
                'required' => false,
            ])
            ->add('localisation')
            ->add('niveauExperience', ChoiceType::class, [
                'choices' => [
                    'Junior' => 'Junior',
                    'Senior' => 'Senior',
                    'Débutant' => 'Débutant',

                ],
                'placeholder' => 'Choisir un niveau d’expérience',
                'required' => false,
            ])
            ->add('nbPostes')
            // OffreType.php - Modifiez le champ dateExpiration
            ->add('dateExpiration', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'html5' => false, // Désactive le datetime-local natif
                'format' => 'dd/MM/yyyy HH:mm', // Format compatible avec Flatpickr
                'attr' => [
                    'class' => 'flatpickr',
                    'data-date-format' => 'd/m/Y H:i', // Format d'affichage
                    'data-enable-time' => 'true'
                ]
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
