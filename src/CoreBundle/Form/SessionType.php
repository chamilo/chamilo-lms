<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form;

use Chamilo\CoreBundle\Entity\Session;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text')
            ->add(
                'general_coach',
                'entity',
                [
                    'class' => 'ChamiloCoreBundle:User',
                    'property' => 'username',
                ]
            )
            ->add(
                'session_admin_id',
                'entity',
                [
                    'class' => 'ChamiloCoreBundle:User',
                    'property' => 'username',
                ]
            )
            ->add(
                'visibility',
                'choice',
                ['choices' => Session::getStatusList()]
            )
            ->add(
                'session_category_id',
                'entity',
                [
                    'class' => 'ChamiloCoreBundle:SessionCategory',
                    'property' => 'name',
                ]
            )
            ->add(
                'promotion_id',
                'entity',
                [
                    'class' => 'ChamiloCoreBundle:Promotion',
                    'property' => 'name',
                ]
            )
            ->add('display_start_date', 'sonata_type_datetime_picker')
            ->add('display_end_date', 'sonata_type_datetime_picker')
            ->add('access_start_date', 'sonata_type_datetime_picker')
            ->add('access_end_date', 'sonata_type_datetime_picker')
            ->add('coach_access_start_date', 'sonata_type_datetime_picker')
            ->add('coach_access_end_date', 'sonata_type_datetime_picker')
            ->add('save', 'submit', ['label' => 'Update']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\Session',
            ]
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
