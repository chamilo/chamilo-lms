<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Chamilo\CoreBundle\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class CourseType.
 *
 * @package Chamilo\CoreBundle\Form\type
 */
class CourseType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // @todo as a service to load the preferred_choices
        $builder
            ->add('title', 'text')
            //->add('code', 'text')
            ->add(
                'course_language',
                'locale',
                ['preferred_choices' => ['en', 'fr', 'es']]
            )
            ->add(
                'visibility',
                'choice',
                ['choices' => Course::getStatusList()]
            )
            ->add('department_name', 'text', ['required' => false])
            ->add('department_url', 'url', ['required' => false])
            //->add('disk_quota', 'text')
            ->add(
                'expiration_date',
                'sonata_type_datetime_picker',
                ['required' => false]
            )
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

            ->add('save', 'submit', ['label' => 'Update']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\Course',
            ]
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
