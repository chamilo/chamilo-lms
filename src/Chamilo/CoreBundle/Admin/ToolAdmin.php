<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Class ToolAdmin.
 *
 * @package Chamilo\CoreBundle\Admin
 */
class ToolAdmin extends AbstractAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name')
            ->add('description', 'ckeditor')
            ->add(
                'toolResourceRights',
                'sonata_type_collection',
                [
                'cascade_validation' => true,
            ],
                [
                    //'allow_delete' => true,
                    //'by_reference' => false,
                    'edit' => 'inline',
                    'inline' => 'table',
                    //'btn_add' => true,
                    //'multiple' => true
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('content' => $users),
                    'admin_code' => 'sonata.admin.tool_resource_rights',
                ]
            )
            /*->add('image', 'sonata_media_type', array(
                'provider' => 'sonata.media.provider.image',
                'context'  => 'default'
            ));*/
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('name')
        ;
    }
}
