<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Admin;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * Class AccessUrlAdmin.
 */
class AccessUrlAdmin extends AbstractAdmin
{
    /**
     * @param $course
     *
     * @return mixed|void
     */
    public function preUpdate($course)
    {
        //$course->setUsers($course->getUsers());
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('url', UrlType::class)
            ->add('description', CKEditorType::class)
            ->add('active')
            ->add('limitCourses')
            ->add('limitActiveCourses')
            ->add('limitSessions')
            ->add('limitUsers')
            ->add('limitTeachers')
            ->add('limitDiskSpace')
            ->add('email', EmailType::class)
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('url')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('url')
        ;
    }
}
