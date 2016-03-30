<?php

namespace Chamilo\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Chamilo\CoreBundle\Entity\CourseRelUser;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class SessionRelCourseAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class SessionRelCourseAdmin extends Admin
{
    /**
     * @inheritdoc
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('course')
            //->add('session')
            ->end()
        ;
    }

    /**
     * @inheritdoc
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
        ;
    }
}
