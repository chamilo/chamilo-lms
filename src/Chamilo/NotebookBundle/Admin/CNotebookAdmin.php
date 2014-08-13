<?php

namespace Chamilo\NotebookBundle\Admin;

use Chamilo\CoreBundle\Entity\Listener\CourseListener;
use Chamilo\CourseBundle\Entity\CTool;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chamilo\CourseBundle\ToolChain;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class CourseAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class CNotebookAdmin extends Admin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title')
            ->add('description', 'textarea', array('attr' => array('class'=> 'ckeditor')))
            ->add('cId')
            ->add('notebookId')
            ->add('userId')
            ->add('course')
            ->add('sessionId')
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('title')
        ;
    }
}
