<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CoreBundle\Entity\Course;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Chamilo\CourseBundle\ToolChain;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class SettingsCurrentAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class SettingsCurrentAdmin extends Admin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('variable')
            ->add('subkey')
            ->add('type')
            ->add('category')
            ->add('selectedValue')
            ->add('title')
            ->add('comment', 'textarea', array('attr' => array('class'=> 'ckeditor')))
            ->add('urls', 'sonata_type_collection', array(
                    'cascade_validation' => true,
                ), array(
                    'allow_delete' => true,
                    'by_reference' => false,
                    'edit'              => 'inline',
                    'inline'            => 'table',
                    //'btn_add' => true,
                    //'multiple' => true
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('content' => $users),
                    'admin_code'        => 'sonata.admin.access_url_rel_course'
                )
            )
            ->add('users', 'sonata_type_collection', array(
                    'cascade_validation' => true,
                ), array(
                    'allow_delete' => true,
                    'by_reference' => false,
                    'edit'              => 'inline',
                    'inline'            => 'table',
                    //'btn_add' => true,
                    //'multiple' => true
                    //'sortable'          => 'position',
                    //'link_parameters'   => array('content' => $users),
                    'admin_code'        => 'sonata.admin.course_rel_user'
                )
            )
            //->add('users', 'entity', array('class' => 'Chamilo\UserBundle\Entity\User', 'label' => 'Cliente'))
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('variable')
            ->add('title')//->add('users')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('variable')
            ->addIdentifier('selected_value')
        ;
    }
}
