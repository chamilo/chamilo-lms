<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class ResourceNodeAdmin.
 */
class ResourceNodeAdmin extends AbstractAdmin
{
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('slug')
            ->add('path')
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('slug')
            ->add('resourceType', ModelType::class, ['property' => 'name', 'btn_add' => false])
            ->add('creator', ModelAutocompleteType::class, ['property' => 'username'])
            ->add('resourceFile', ModelType::class, ['property' => 'name', 'btn_add' => 'link_add'])
            ->add(
                'resourceLinks',
                ModelAutocompleteType::class,
                ['property' => 'id', 'multiple' => true]
            )
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('resourceType')
            ->add('creator')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('slug')
            ->add('resourceType')
            ->add('resourceFile')
            ->add('creator')
        ;
    }
}
