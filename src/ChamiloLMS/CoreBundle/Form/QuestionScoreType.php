<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Entity;

class QuestionScoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text');
        $builder->add('submit', 'submit');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'ChamiloLMS\CoreBundle\Entity\QuestionScore'
            )
        );
    }

    public function getName()
    {
        return 'questionScore';
    }
}
