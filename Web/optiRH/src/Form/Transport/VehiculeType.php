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
                
            ])
            ->add('disponibilite', ChoiceType::class, [
                'choices' => [
                    'Disponible' => 'disponible',
                    'Indisponible' => 'indisponible'
                ],
                
            ])
            ->add('nbrplace', IntegerType::class, [
  
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vehicule::class,
        ]);
    }
}