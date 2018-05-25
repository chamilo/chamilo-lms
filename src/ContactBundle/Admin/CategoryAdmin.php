<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ContactBundle\Admin;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Class UserAdmin.
 *
 * @package Chamilo\ContactBundle\Admin
 */
class CategoryAdmin extends AbstractAdmin
{
    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            //->add('headline', null, array('identifier' => true))
            //->add('name', null, array('identifier' => true))
            ->add('translations', null, ['identifier' => true])
            ->add('email', null, ['identifier' => true])
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            //->add('name')
            ->add('translations', TranslationsType::class, [])
            ->add('email')
        ;
    }

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('translations', null, ['identifier' => true])
            ->add('email', null, ['identifier' => true])
        ;
    }
}
