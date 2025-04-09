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
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

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
                'constraints' => [
                    new NotBlank(['message' => 'Le type de trajet est obligatoire'])
                ]
            ])
            ->add('station', TextType::class, [
                'label' => 'Station',
                'constraints' => [
                    new NotBlank(['message' => 'La station est obligatoire']),
                    new Length([
                        'max' => 10,
                        'maxMessage' => 'La station ne doit pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('depart', TextType::class, [
                'label' => 'Point de départ',
                'constraints' => [
                    new NotBlank(['message' => 'Le point de départ est obligatoire']),
                    new Length([
                        'max' => 10,
                        'maxMessage' => 'Le point de départ ne doit pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('arrive', TextType::class, [
                'label' => 'Point d\'arrivée',
                'constraints' => [
                    new NotBlank(['message' => 'Le point d\'arrivée est obligatoire']),
                    new Length([
                        'max' => 10,
                        'maxMessage' => 'Le point d\'arrivée ne doit pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('longitudeDepart', NumberType::class, [
                'label' => 'Longitude départ',
                'constraints' => [
                    new NotBlank(['message' => 'La longitude de départ est obligatoire']),
                    new Type([
                        'type' => 'float',
                        'message' => 'La longitude doit être un nombre décimal'
                    ])
                ]
            ])
            ->add('latitudeDepart', NumberType::class, [
                'label' => 'Latitude départ',
                'constraints' => [
                    new NotBlank(['message' => 'La latitude de départ est obligatoire']),
                    new Type([
                        'type' => 'float',
                        'message' => 'La latitude doit être un nombre décimal'
                    ])
                ]
            ])
            ->add('longitudeArrivee', NumberType::class, [
                'label' => 'Longitude arrivée',
                'constraints' => [
                    new NotBlank(['message' => 'La longitude d\'arrivée est obligatoire']),
                    new Type([
                        'type' => 'float',
                        'message' => 'La longitude doit être un nombre décimal'
                    ])
                ]
            ])
            ->add('latitudeArrivee', NumberType::class, [
                'label' => 'Latitude arrivée',
                'constraints' => [
                    new NotBlank(['message' => 'La latitude d\'arrivée est obligatoire']),
                    new Type([
                        'type' => 'float',
                        'message' => 'La latitude doit être un nombre décimal'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trajet::class,
        ]);
    }
}