<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chamilo\CoreBundle\Entity\CourseRelUser;

/**
 * Class CourseAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class CourseRelUserAdmin extends Admin
{
    /**
     * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
     *
     * @return void
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('user')
            //->add('group', 'text')
            ->add('status', 'choice', array(
                    'choices' => CourseRelUser::getStatusList()
                )
            )
            ->add('relation_type', 'sonata_type_translatable_choice', array(
                'choices' => CourseRelUser::getRelationTypeList()
                )
            )
            ->end()
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('course')
            ->add('user')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('user')
            ->addIdentifier('course')
            //->addIdentifier('group')
            ->add('status', 'sonata_type_translatable_choice', array(
                    'choices' => CourseRelUser::getStatusList()
                )
            )
        ;
    }
}
