<?php
namespace App\Form\Transport;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de voyage',
                'attr' => ['class' => 'form-control']
            ])
            ->add('passengers', IntegerType::class, [
                'label' => 'Nombre de passagers',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 10
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\Transport\ReservationTrajet',
        ]);
    }
}