<?php

namespace ChamiloLMS\NotebookBundle\Admin;

use ChamiloLMS\CoreBundle\Entity\Listener\CourseListener;
use ChamiloLMS\CourseBundle\Entity\CTool;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use ChamiloLMS\CourseBundle\ToolChain;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class CourseAdmin
 * @package ChamiloLMS\CoreBundle\Admin
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
