<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Chamilo\CoreBundle\Entity\Session;

class SessionType extends AbstractType
{
    /**
     * Builds the form
     * For form type details see:
     * http://symfony.com/doc/current/reference/forms/types.html
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builderData = $builder->getData();
        $builder
            ->add('name', 'text')
            ->add('general_coach', 'entity', array(
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
            ))
            ->add('display_start_date', 'sonata_type_datetime_picker')
            ->add('display_end_date', 'sonata_type_datetime_picker')
            ->add('access_start_date', 'sonata_type_datetime_picker')
            ->add('access_end_date', 'sonata_type_datetime_picker')
            ->add('coach_access_start_date', 'sonata_type_datetime_picker')
            ->add('coach_access_end_date', 'sonata_type_datetime_picker')

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
                'data_class' => 'Chamilo\CoreBundle\Entity\Session'
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'session';
    }
}

