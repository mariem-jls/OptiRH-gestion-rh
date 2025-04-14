<?php

namespace App\Form\Evenement;

use App\Entity\Evenement\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre',)
            ->add('lieu')
            ->add('description')
            ->add('prix')
            ->add('date_debut', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'form-control form-control-sm'],
                'required' => false,  // Permet de laisser vide
                'empty_data' => null,  // Définit la valeur par défaut à null
                'invalid_message' => 'Merci de mettre une date valide.',
            ])
            ->add('date_fin', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'form-control form-control-sm'],
                'required' => false,  // Permet de laisser vide
                'empty_data' => null,  // Définit la valeur par défaut à null
                'invalid_message' => 'Merci de mettre une date valide.',
            ])
            ->add('image', FileType::class, [
                'required' => false,
                'label' => 'Image',
                'mapped' => true,
                'attr' => [
                    'class' => 'form-control form-control-sm file-input',
                    'accept' => 'image/*',
                    'hidden' => true
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Seules les images (JPEG, PNG, GIF) sont autorisées',
                    ])
                ],
            ])
            
            ->add('heure', TimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => false,
                'empty_data' => null,
                'label' => 'Heure',
                'attr' => [
                    'class' => 'form-control form-control-sm flatpickr-time',
                ],
            ])
            
         
            
            ->add('longitude')
            ->add('latitude')
            ->add('type', ChoiceType::class, [
                'label' => 'Type*',
                'choices' => [
                    'RH' => 'RH',
                    'Marketing' => 'Marketing',
                    'Finance' => 'Finance',
                    'Management' => 'Management',
                    'Technologie & Innovation' => 'Technologie & Innovation',
                    'Loisir' => 'Loisir',
                    'Soft Skills' => 'Soft Skills'
                ],
                'attr' => ['class' => 'form-select form-select-sm select2'],
                'placeholder' => 'Sélectionnez...',
                'required' => true
            ])
            ->add('modalite', ChoiceType::class, [
                'label' => 'Modalité*',
                'choices' => [
                    'En ligne' => 'En ligne',
                    'Présentiel' => 'Présentiel'
                ],
                'attr' => ['class' => 'form-select form-select-sm select2'],
                'placeholder' => 'Sélectionnez...',
                'required' => true
            ]);

        // Transformer pour gérer le chemin de l'image
        $builder->get('image')
            ->addModelTransformer(new CallbackTransformer(
                function ($path) {
                    // Transforme le chemin en File pour l'affichage
                    return null; // On ne charge pas le fichier existant dans l'input
                },
                function ($file) {
                    // Laisse passer le fichier uploadé
                    return $file instanceof UploadedFile ? $file : null;
                }
            ));

            

           
            
           
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
