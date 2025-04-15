<?php
// src/Form/ReponseType.php


namespace App\Form;

use App\Entity\Reponse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextareaType::class, [
                'label' => 'Description de votre réclamation',
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre problème en détail (10 caractères minimum)...',
                    'class' => 'form-control',
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
            'is_admin' => false,
        ]);
    }
}