<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelAutocompleteType;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class ResourceFileAdmin.
 */
class ResourceFileAdmin extends AbstractAdmin
{
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('media', ModelAutocompleteType::class, ['property' => 'name', 'btn_add' => 'link_add'])
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('media')
        ;
    }
}
