<?php
// src/Form/AddressTestType.php
namespace App\Form;

use Daften\Bundle\AddressingBundle\Form\Type\AddressEmbeddableType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class AddressTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address', AddressEmbeddableType::class, [
                'label' => 'Adresse',
                'allowed_countries' => ['TN'], // Limit to Tunisia
                'default_country' => 'TN',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Soumettre',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }
}