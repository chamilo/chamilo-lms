<?php

namespace ChamiloLMS\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class SessionAdmin
 * @package ChamiloLMS\CoreBundle\Admin
 */
class SessionAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('id', 'text', array('label' => 'Session'))
            ->add('name') //if no type is specified, SonataAdminBundle tries to guess it
            ->add('display_start_date', 'sonata_type_date_picker')
            ->add('generalCoach')
        ;

        /*->add('student', 'sonata_type_model', array(),
        array(
            'admin_code' => 'application.subscriber.admin.student'
        ))*/
    }

    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text', array('label' => 'Session'))
            ->add('name') //if no type is specified, SonataAdminBundle tries to guess it
            ->add('display_start_date', 'sonata_type_date_picker')
        ;
    }


    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            //->add('display_start_date', 'sonata_type_date_picker')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('name')
            //->add('display_start_date', 'sonata_type_date_picker')
        ;
    }
}
