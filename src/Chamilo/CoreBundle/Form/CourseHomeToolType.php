<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Entity;
use Symfony\Component\Validator\Constraints as Assert;

class CourseHomeToolType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text');
        $builder->add('link', 'text');
        $builder->add(
            'custom_icon',
            'file',
            array('required' => false, 'data_class' => null)
        );
        $builder->add('target', 'choice', array('choices' => array('_self', '_blank')));
        $builder->add('visibility', 'choice', array('choices' => array('1', '0')));
        $builder->add('c_id', 'hidden');
        $builder->add('session_id', 'hidden');

        $builder->add('description', 'textarea');
        $builder->add('submit', 'submit');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Chamilo\CoreBundle\Entity\CTool'
            )
        );
    }

    public function getName()
    {
        return 'courseHomeTool';
    }
}
