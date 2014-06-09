<?php

namespace ChamiloLMS\CoreBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

use Knp\Menu\ItemInterface as MenuItemInterface;

/**
 * Class CourseAdmin
 * @package ChamiloLMS\CoreBundle\Admin
 */
class CourseAdmin extends Admin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title')
            ->add('code','text',array(
                'read_only' => true,
            ))
            //->add('code') //if no type is specified, SonataAdminBundle tries to guess it
            ->add('description', 'textarea', array('attr' => array('class'=> 'ckeditor')))
            ->add('departmentName')
            ->add('departmentUrl')
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
            //->add('users', 'entity', array('class' => 'Application\Sonata\UserBundle\Entity\User', 'label' => 'Cliente'))
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
            ->add('code')//->add('users')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->addIdentifier('code')
            ->addIdentifier('title')
        ;
    }

    /**
     * Very important in order to save the related entities!
     * @param \ChamiloLMS\CoreBundle\Entity\Course $course
     * @return mixed|void
     */
    public function preUpdate($course)
    {
        $course->setUsers($course->getUsers());
        $course->setUrls($course->getUrls());
    }
}
