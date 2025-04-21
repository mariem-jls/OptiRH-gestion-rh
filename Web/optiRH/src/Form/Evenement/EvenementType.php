<?php

namespace App\Form\Evenement;

use App\Entity\Evenement\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('lieu')
            ->add('description')
            ->add('nbr_personnes')
            ->add('prix')
            ->add('date_debut', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'form-control form-control-sm'],
                'required' => false,
                'empty_data' => null,
                'invalid_message' => 'Merci de mettre une date valide.',
            ])
            ->add('date_fin', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => ['class' => 'form-control form-control-sm'],
                'required' => false,
                'empty_data' => null,
                'invalid_message' => 'Merci de mettre une date valide.',
            ])
            ->add('image', FileType::class, [
                'required' => false,
                'label' => 'Image',
                'mapped' => true,
                'attr' => [
                    'class' => 'form-control form-control-sm file-input',
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new FileConstraint([
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

        // Transformer pour l'image
        $builder->get('image')
            ->addModelTransformer(new CallbackTransformer(
                function ($imagePath) {
                    if (!$imagePath) {
                        return null;
                    }

                    $fullPath = __DIR__ . '/../../../public/uploads/' . $imagePath;

                    if (file_exists($fullPath)) {
                        return new File($fullPath);
                    }

                    return null;
                },
                function ($file) use ($builder) {
                    if (!$file instanceof UploadedFile) {
                        return $builder->getForm()->getData()->getImage();
                    }

                    return $file;
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
