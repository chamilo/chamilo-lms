<?php
/* For licensing terms, see /license.txt */

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
 * Class CNotebookAdmin (Sonata)
 * @package Chamilo\NotebookBundle\Admin
 */
class CNotebookAdmin extends Admin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text')
            ->add('description', 'ckeditor')
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('name')
        ;
    }
}
