<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Admin;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class CourseAdmin.
 *
 * @package Chamilo\CoreBundle\Admin
 */
class CourseRelUserAdmin extends Admin
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
                'choice',
                [
                    'choices' => CourseRelUser::getStatusList(),
                ]
            )
            ->add(
                'relation_type',
                'sonata_type_translatable_choice',
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
