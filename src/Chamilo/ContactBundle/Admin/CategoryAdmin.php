<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;

/**
 * Class UserAdmin
 * @package Chamilo\ContactBundle\Admin
 */
class CategoryAdmin extends Admin
{
    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            //->add('headline', null, array('identifier' => true))
            //->add('name', null, array('identifier' => true))
            ->add('translations', null, array('identifier' => true))
            ->add('email', null, array('identifier' => true))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            //->add('name')
            ->add('translations', 'a2lix_translations', array())
            ->add('email')
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('translations', null, array('identifier' => true))
            ->add('email', null, array('identifier' => true))
        ;
    }
}

