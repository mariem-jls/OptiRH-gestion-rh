<?php

namespace App\Form\Transport;

use App\Entity\Transport\Vehicule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class VehiculeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Bus' => 'Bus',
                    'Minibus' => 'Minibus',
                    'Voiture' => 'Voiture'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le type est obligatoire'])
                ]
            ])
            ->add('disponibilite', ChoiceType::class, [
                'choices' => [
                    'Disponible' => 'disponible',
                    'Indisponible' => 'indisponible'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La disponibilité est obligatoire'])
                ]
            ])
            ->add('nbrplace', IntegerType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Le nombre de places est obligatoire']),
                    new Positive(['message' => 'Le nombre de places doit être positif'])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
        ]);
    }
}