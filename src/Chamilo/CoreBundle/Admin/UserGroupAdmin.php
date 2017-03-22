<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chamilo\CoreBundle\Entity\Course;


/**
 * Class UserGroupAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class UserGroupAdmin extends AbstractAdmin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name')
            ->add('description', 'ckeditor')
            ->add('users', 'sonata_type_collection', array(
                    'cascade_validation' => true,
                ), array(
                   // 'allow_delete' => true,
                    'by_reference' => false,
                    'edit'              => 'inline',
                    'inline'            => 'table',
                    //'btn_add' => true,
                    //'multiple' => true
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('content' => $users),
                    'admin_code'        => 'sonata.admin.user_group_rel_user'
                )
            )
        ;
    }

    /**
     * Very important in order to save the related entities!
     * @param Course $userGroup
     * @return mixed|void
     */
    public function preUpdate($userGroup)
    {
        //$userGroup->setUsers($userGroup->getUsers());
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text', array('label' => 'Usergroup'))
            ->add('name')
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
        ;
    }
}
