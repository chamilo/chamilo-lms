<?php

namespace ChamiloLMS\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Entity;

class CurriculumCategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builderData = $builder->getData();
        /*$parentIdDisabled = false;
        if (!empty($builderData)) {
            $parentIdDisabled = true;
        }*/

        $builder->add('title', 'text');
        $builder->add('max_score', 'text');
        $builder->add('min_chars', 'text');
        $builder->add('min_chars', 'text');
        $builder->add('parent', 'entity', array(
            'class' => 'Entity\CurriculumCategory',
            'query_builder' => function($repository) {
                return $repository->createQueryBuilder('p')
                    ->orderBy('p.title', 'ASC');
            },
            'property' => 'title',
            'required' => false
        ));

        $builder->add('submit', 'submit');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Entity\CurriculumCategory'
            )
        );
    }

    public function getName()
    {
        return 'curriculumCategory';
    }
}
