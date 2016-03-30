<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CoreBundle\Entity\Course;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class SettingsCurrentAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class SettingsCurrentAdmin extends Admin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title')
            ->add('variable')
            ->add('subkey')
            ->add('type')
            ->add('category')
            ->add('selectedValue')
            ->add('comment', 'ckeditor')
            ->add('accessUrl')
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
            ->add('variable')
            ->add('category')
            ->add('accessUrl')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('variable')
            ->add('selected_value')
            ->add('category')
        ;
    }
}
