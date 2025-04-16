<?php
// src/Form/Transport/TrajetType.php
namespace App\Form\Transport;

use App\Entity\Transport\Trajet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class TrajetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de trajet',
                'choices' => [
                    'Urbain' => 'Urbain',
                    'Interurbain' => 'Interurbain'
                ],
            ])
            ->add('station', TextType::class, [
                'label' => 'Station',
                'empty_data' => '', // Force une chaîne vide si null
                'required' => false
                        
            ])
            ->add('depart', TextType::class, [
                'label' => 'Point de départ',
                'empty_data' => '', // Force une chaîne vide si null
                'required' => false // Si le champ peut être vide
                        
            ])
            ->add('arrive', TextType::class, [
                'label' => 'Point d\'arrivée',
                'empty_data' => '', // Force une chaîne vide si null
                'required' => false // Si le champ peut être vide
                        
            ])
            ->add('longitudeDepart', NumberType::class, [
                'label' => 'Longitude départ',
                'invalid_message' => 'La longitude doit être un nombre ', // Message personnalisé
                'empty_data' => '', // Force une chaîne vide si null
                'required' => false // Si le champ peut être vide
                        
            ])
            ->add('latitudeDepart', NumberType::class, [
                'label' => 'Latitude départ',
                'invalid_message' => 'La latitude doit être un nombre ', // Message personnalisé
                'empty_data' => '', // Force une chaîne vide si null
                'required' => false // Si le champ peut être vide
                        

            ])
            ->add('longitudeArrivee', NumberType::class, [
                'label' => 'Longitude arrivée',
                'invalid_message' => 'La longitude doit être un nombre ', // Message personnalisé
                'empty_data' => '', // Force une chaîne vide si null
                'required' => false // Si le champ peut être vide
                        

            ])
            ->add('latitudeArrivee', NumberType::class, [
                'label' => 'Latitude arrivée',
                'invalid_message' => 'La longitude doit être un nombre ', // Message personnalisé
                'empty_data' => '', // Force une chaîne vide si null
                'required' => false // Si le champ peut être vide
                        
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
        ]);
    }
}