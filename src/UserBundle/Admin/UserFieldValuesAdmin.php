<?php

namespace Chamilo\UserBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class UserAdmin.
 */
class UserFieldValuesAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('field')
            ->add('user')
            ->add('field_value', 'text')
            ->add('comment', 'textarea')
            ->add('author');
    }

    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text');
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('field');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('field')
            ->addIdentifier('user');
    }
}
