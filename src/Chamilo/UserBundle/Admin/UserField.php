<?php

namespace Chamilo\UserBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class UserAdmin.
 *
 * @package Chamilo\UserBundle\Admin
 */
class UserField extends Admin
{
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

    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text')
            ->add('field_type', 'text')
            ->add('field_variable', 'text');
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        /*$datagridMapper
            ->add('field_type')
        ;*/
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('field_variable', 'text')
            ->add('field_type', 'text');
    }
}
