<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Lexik\Bundle\FormFilterBundle\Filter\Form\Type as Filters;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Lexik\Bundle\FormFilterBundle\Filter\Query\QueryInterface;

class ReclamationFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', Filters\TextFilterType::class, [
                'label' => 'Recherche',
                'attr' => [
                    'placeholder' => 'Rechercher...',
                    'class' => 'form-control'
                ],
                'apply_filter' => function (QueryInterface $filterQuery, $field, $values) {
                    if (!empty($values['value'])) {
                        $qb = $filterQuery->getQueryBuilder();
                        $qb->andWhere('r.description LIKE :searchTerm')
                            ->setParameter('searchTerm', '%' . $values['value'] . '%');
                    }
                    
                    return $filterQuery;
                }
            ])
            ->add('type', Filters\ChoiceFilterType::class, [
                'label' => 'Type',
                'choices' => Reclamation::getTypeChoices(),
                'placeholder' => 'Tous les types',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('status', Filters\ChoiceFilterType::class, [
                'label' => 'Statut',
                'choices' => Reclamation::getStatusChoices(),
                'placeholder' => 'Tous les statuts',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('date', Filters\DateRangeFilterType::class, [
                'label' => 'Période',
                'left_date_options' => [
                    'label' => 'De',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'form-control'
                    ]
                ],
                'right_date_options' => [
                    'label' => 'À',
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'form-control'
                    ]
                ]
            ])
            ->add('sentimentLabel', Filters\ChoiceFilterType::class, [
                'label' => 'Sentiment',
                'choices' => [
                    'Positif' => 'positive',
                    'Neutre' => 'neutral',
                    'Négatif' => 'negative'
                ],
                'placeholder' => 'Tous les sentiments',
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);
    }

    public function getBlockPrefix()
    {
        return 'reclamation_filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'validation_groups' => ['filtering'],
            'method' => 'GET'
        ]);
    }
}