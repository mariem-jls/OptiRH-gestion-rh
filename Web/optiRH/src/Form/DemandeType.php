<?php

namespace App\Form;

use App\Entity\Demande;
use App\Entity\Offre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'choices' => [
                    'En Attente' => Demande::STATUT_EN_ATTENTE,
                    'Acceptée' => Demande::STATUT_ACCEPTEE,
                    'Refusée' => Demande::STATUT_REFUSEE,
                ],
                'placeholder' => 'Choisir un statut',
                'required' => true,
            ])
            ->add('date', DateTimeType::class, [
                'widget' => 'single_text',
                'disabled' => true, // Non modifiable
            ])
            ->add('description')
            ->add('fichierPieceJointe', FileType::class, [
                'label' => 'Pièce jointe (PDF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ],
                        'maxSizeMessage' => 'Le fichier est trop volumineux (max {{ limit }}).',
                        'mimeTypesMessage' => 'Veuillez uploader un fichier de type PDF ou Word.',
                    ]),
                ],
            ])
            ->add('nomComplet')
            ->add('email')
            ->add('telephone')
            ->add('adresse')
            ->add('dateDebutDisponible', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Date de disponibilité',
                'attr' => [
                    'class' => 'form-control',
                    'min' => (new \DateTime())->format('Y-m-d') // Optionnel : date minimum
                ]
            ])
            ->add('situationActuelle', ChoiceType::class, [
                'choices' => [
                    'Etudiant' => Demande::SITUATION_ETUDIANT,
                    'Employé' => Demande::SITUATION_EMPLOYE,
                    'Autre' => Demande::SITUATION_AUTRE,
                ],
                'placeholder' => 'Choisir une situation',
                'required' => false,
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Demande::class,
        ]);
    }
}
