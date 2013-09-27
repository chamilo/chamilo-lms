<?php

namespace ChamiloLMS\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Entity;

class CQuizDistributionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('active', 'checkbox', array('required'  => false));
        $builder->add('exerciseId', 'hidden');
        $builder->add('submit', 'submit');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Entity\CQuizDistribution'
            )
        );
    }

    public function getName()
    {
        return 'quiz_distribution';
    }
}
