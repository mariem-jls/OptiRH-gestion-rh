<?php

namespace App\Form\Evenement;

use App\Entity\Evenement\Evenement;
use App\Entity\Evenement\FavorisEvenement;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FavorisEvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id_user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
            ->add('id_evenement', EntityType::class, [
                'class' => Evenement::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FavorisEvenement::class,
        ]);
    }
}
