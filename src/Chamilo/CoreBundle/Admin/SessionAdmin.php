<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Chamilo\CoreBundle\Entity\Session;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class SessionAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class SessionAdmin extends AbstractAdmin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name') //if no type is specified, SonataAdminBundle tries to guess it
            ->add('generalCoach')
            ->add('category')
            ->add('displayStartDate', 'sonata_type_datetime_picker')
            ->add(
                'visibility',
                'choice',
                array('choices' => Session::getStatusList())
            )
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
            /*->add('users', 'sonata_type_collection', array(
                    'cascade_validation' => true,
                ), array(
                    'allow_delete' => true,
                    'by_reference' => false,
                    //'edit'              => 'inline',

                    //'sortable'          => 'position',
                    //'link_parameters'   => array('context' => $context),
                    //'admin_code'        => 'sonata.admin.session_rel_user'
                )
            )*/
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text', array('label' => 'Session'))
            ->add('name')
            ->add('display_start_date', 'sonata_type_date_picker')
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add(
                'visibility',
                null,
                array(),
                'choice',
                array('choices' => Session::getStatusList())
            )
            //->add('display_start_date', 'sonata_type_date_picker')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('generalCoach')
            ->add('visibility', 'choice', array(
                'choices' => Session::getStatusList()
            ))
        ;
    }

    /**
     * Very important in order to save the related entities!
     * @param Session $session
     * @return mixed|void
     */
    public function preUpdate($session)
    {
        $session->setCourses($session->getCourses());
    }
}
