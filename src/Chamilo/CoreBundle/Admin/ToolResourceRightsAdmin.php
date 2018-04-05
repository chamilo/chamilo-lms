<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Class ToolResourceRightsAdmin.
 *
 * @package Chamilo\CoreBundle\Admin
 */
class ToolResourceRightsAdmin extends AbstractAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('tool')
            ->add(
                'role',
                'choice',
                ['choices' => ToolResourceRights::getDefaultRoles()]
            )
            ->add(
                'mask',
                'choice',
                ['choices' => ToolResourceRights::getMaskList()]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('role')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('role')
            ->addIdentifier('mask')
        ;
    }
}
