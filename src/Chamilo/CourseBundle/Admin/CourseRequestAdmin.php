<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;

/**
 * Class CourseRequestAdmin
 * @package Chamilo\CoreBundle\Admin
 */
class CourseRequestAdmin extends Admin
{
    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('title')
            ->add('description', 'textarea', array('attr' => array('class'=> 'ckeditor')))
            ->add('code')
            ->add('user')
            ->add('status')
            ->add('info')
            ->add('directory')
            ->add('exemplaryContent', 'checkbox')
            ->add('courseLanguage', 'language')
        ;
    }

    /**
     * @param ShowMapper $showMapper
     */
    protected function configureShowField(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('title')
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('title')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('title')
        ;
    }
}
