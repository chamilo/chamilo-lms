<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;


use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;


/**
 * Class SessionAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class SessionRelUserAdmin extends AbstractAdmin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('session') //if no type is specified, SonataAdminBundle tries to guess it
            ->add('user')
            ->add('relation_type', 'text')
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
            ->add('session')
            ->add('user')
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('session')
            ->add('user')
            //->add('display_start_date', 'sonata_type_date_picker')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('session')
            ->addIdentifier('user')
        ;
    }
}
