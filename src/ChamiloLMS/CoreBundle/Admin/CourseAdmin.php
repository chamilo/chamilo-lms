<?php

namespace ChamiloLMS\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class CourseAdmin
 * @package ChamiloLMS\CoreBundle\Admin
 */
class CourseAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('id', 'text', array('label' => 'Course'))
            //->add('author', 'entity', array('class' => 'Acme\DemoBundle\Entity\User'))
            ->add('code') //if no type is specified, SonataAdminBundle tries to guess it
            ->add('title')
            ->add('users', 'sonata_type_collection', array(
                    'cascade_validation' => true,
                ), array(
                    'edit'              => 'inline',
                    'inline'            => 'table',
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('context' => $context),
                    //'admin_code'        => 'sonata.media.admin.gallery_has_media'
                )
            )
            //->add('users', 'entity', array('class' => 'Application\Sonata\UserBundle\Entity\User', 'label' => 'Cliente'))

        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('code')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('code')
            ->add('title')
        ;
    }
}
