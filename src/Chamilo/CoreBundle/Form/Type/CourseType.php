<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Chamilo\CoreBundle\Entity\Course;

/**
 * Class CourseType
 * @package Chamilo\CoreBundle\Form\type
 */
class CourseType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'text')
            //->add('code', 'text')
            ->add('course_language', 'locale')
            ->add('visibility', 'choice',
                array('choices' => Course::getStatusList())
            )
            ->add('department_name', 'text', array('required' => false))
            ->add('department_url', 'url', array('required' => false))
            //->add('disk_quota', 'text')
            ->add('expiration_date', 'sonata_type_datetime_picker', array('required' => false))

           /* ->add('general_coach', 'entity', array(
                'class' => 'ChamiloUserBundle:User',
                'property' => 'username',
            ))
            ->add('session_admin_id',  'entity', array(
                'class' => 'ChamiloUserBundle:User',
                'property' => 'username',
            ))
            ->add('visibility', 'choice',
                array('choices' => Session::getStatusList())
            )
            ->add('session_category_id', 'entity', array(
                'class' => 'ChamiloCoreBundle:SessionCategory',
                'property' => 'name',
            ))
            ->add('promotion_id', 'entity', array(
                'class' => 'ChamiloCoreBundle:Promotion',
                'property' => 'name',
            ))*/
            /*
            ->add('coach_access_end_date', 'sonata_type_datetime_picker')*/

            ->add('save', 'submit', array('label' => 'Update'))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Chamilo\CoreBundle\Entity\Course'
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'course';
    }
}

