<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Chamilo\CoreBundle\Entity\Session;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class SessionAdmin.
 *
 * @package Chamilo\CoreBundle\Admin
 */
class SessionAdmin extends AbstractAdmin
{
    /**
     * Very important in order to save the related entities!
     *
     * @param Session $session
     *
     * @return mixed|void
     */
    public function preUpdate($session)
    {
        $session->setCourses($session->getCourses());
    }

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
                ['choices' => Session::getStatusList()]
            )
            ->add(
                'courses',
                'sonata_type_collection',
                [
                    'cascade_validation' => true,
                ],
                [
                    'edit' => 'inline',
                    'inline' => 'table',
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('context' => $context),
                    'admin_code' => 'sonata.admin.session_rel_course',
                ]
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

    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text', ['label' => 'Session'])
            ->add('name')
            ->add('display_start_date', 'sonata_type_date_picker')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add(
                'visibility',
                null,
                [],
                'choice',
                ['choices' => Session::getStatusList()]
            )
            //->add('display_start_date', 'sonata_type_date_picker')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
            ->add('generalCoach')
            ->add('visibility', 'choice', [
                'choices' => Session::getStatusList(),
            ])
        ;
    }
}
