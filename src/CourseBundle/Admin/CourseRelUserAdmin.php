<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Admin;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class CourseAdmin.
 */
class CourseRelUserAdmin extends AbstractAdmin
{
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('user')
            //->add('group', 'text')
            ->add(
                'status',
                ChoiceType::class,
                [
                    'choices' => CourseRelUser::getStatusList(),
                ]
            )
            ->add(
                'relation_type',
                ChoiceType::class,
                [
                    'choices' => CourseRelUser::getRelationTypeList(),
                ]
            )
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('course')
            ->add('user')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('user')
            ->addIdentifier('course')
            //->addIdentifier('group')
            ->add(
                'status',
                'sonata_type_translatable_choice',
                [
                    'choices' => CourseRelUser::getStatusList(),
                ]
            )
        ;
    }
}
