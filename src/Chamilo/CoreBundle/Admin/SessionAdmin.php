<?php

namespace Chamilo\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class SessionAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class SessionAdmin extends Admin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('id', 'text', array('label' => 'Session'))
            ->add('name') //if no type is specified, SonataAdminBundle tries to guess it
            ->add('display_start_date', 'sonata_type_date_picker')
            ->add('generalCoach')
            ->add('visibility')
            //->add('courses')
            ->add('courses', 'sonata_type_collection', array(
                    'cascade_validation' => true,
                ), array(
                    'edit'              => 'inline',
                    'inline'            => 'table',
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('context' => $context),
                    'admin_code'        => 'sonata.admin.session_rel_course'
                )
            )
        ;

        /*->add('student', 'sonata_type_model', array(),
        array(
            'admin_code' => 'application.subscriber.admin.student'
        ))*/
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text', array('label' => 'Session'))
            ->add('name') //if no type is specified, SonataAdminBundle tries to guess it
            ->add('display_start_date', 'sonata_type_date_picker')
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
            //->add('display_start_date', 'sonata_type_date_picker')
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
            //->add('display_start_date', 'sonata_type_date_picker')
        ;
    }

    /**
     * Very important in order to save the related entities!
     * @param \Chamilo\CoreBundle\Entity\Session $session
     * @return mixed|void
     */
    public function preUpdate($session)
    {
        $session->setCourses($session->getCourses());
    }
}
