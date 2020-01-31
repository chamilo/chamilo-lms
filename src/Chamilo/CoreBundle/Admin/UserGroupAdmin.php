<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Chamilo\CoreBundle\Entity\Course;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class UserGroupAdmin.
 *
 * @package Chamilo\CoreBundle\Admin
 */
class UserGroupAdmin extends AbstractAdmin
{
    /**
     * Very important in order to save the related entities!
     *
     * @param Course $userGroup
     *
     * @return mixed|void
     */
    public function preUpdate($userGroup)
    {
        //$userGroup->setUsers($userGroup->getUsers());
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name')
            ->add('description', 'ckeditor')
            ->add(
                'users',
                'sonata_type_collection',
                [
                    'cascade_validation' => true,
                ],
                [
                    // 'allow_delete' => true,
                    'by_reference' => false,
                    'edit' => 'inline',
                    'inline' => 'table',
                    //'btn_add' => true,
                    //'multiple' => true
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('content' => $users),
                    'admin_code' => 'sonata.admin.user_group_rel_user',
                ]
            )
        ;
    }

    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id', 'text', ['label' => 'Usergroup'])
            ->add('name')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('name')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('name')
        ;
    }
}
