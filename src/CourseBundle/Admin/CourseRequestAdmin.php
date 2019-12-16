<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Class CourseRequestAdmin.
 */
class CourseRequestAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title')
            ->add('description', TextareaType::class, ['attr' => ['class' => 'ckeditor']])
            ->add('code')
            ->add('user')
            ->add('status')
            ->add('info')
            ->add('directory')
            ->add('exemplaryContent', 'checkbox')
            ->add('courseLanguage', 'language')
        ;
    }

    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('title')
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title')
        ;
    }
}
