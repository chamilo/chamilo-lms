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
 *
 * @package Chamilo\CoreBundle\Admin
 */
class ResourceNodeAdmin extends AbstractAdmin
{
    /**
     * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('name')
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name')
            ->add('description')
            ->add('resourceType', ModelType::class, ['property' => 'name', 'btn_add' => false])
            ->add('creator', ModelAutocompleteType::class, ['property' => 'username'])
            ->add('resourceFile', ModelType::class, ['property' => 'id', 'btn_add' => 'link_add'])
            ->add(
                'resourceLinks',
                ModelAutocompleteType::class,
                ['property' => 'id', 'multiple' => true]
            )
            ->end()
        ;
    }

//    /**
//     * @param DatagridMapper $datagridMapper
//     */
//    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
//    {
//        $datagridMapper
//            ->add('url')
//        ;
//    }
//

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
