<?php

namespace Chamilo\UserBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;


/**
 * Class UserAdmin
 * @package Chamilo\UserBundle\Admin
 */
class UserField extends Admin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('field_type', 'text')
            ->add('field_variable', 'text')
            ->add('field_display_text', 'text')
            ->add('field_default_value', 'text')
            ->add('field_order', 'text')
            ->add('field_visible', 'text')
            ->add('field_changeable', 'text')
            ->add('field_filter', 'text')
            ->add('field_loggeable', 'text')
            ->add('configuration');
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text')
            ->add('field_type', 'text')
            ->add('field_variable', 'text');
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        /*$datagridMapper
            ->add('field_type')
        ;*/
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('field_variable', 'text')
            ->add('field_type', 'text');
    }
}
