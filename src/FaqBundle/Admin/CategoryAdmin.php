<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Admin;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Class QuestionAdmin.
 *
 * @package Chamilo\FaqBundle\Admin
 */
class CategoryAdmin extends AbstractAdmin
{
    protected $datagridValues = [
        '_page' => 1,
        '_sort_by' => 'created_at',
        '_sort_order' => 'Desc',
    ];

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            //->add('headline')
            //->add('body')
            ->add('rank')
            ->add('isActive')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            //->add('headline', null, array('identifier' => true))
            ->add('translations', null, ['identifier' => true])
            ->add('rank')
            ->add(
                '_action',
                'actions',
                [
                    'actions' => [
                        //'show' => array(),
                        'preview' => ['template' => 'ChamiloFaqBundle:Faq:preview_category_partial.html.twig'],
                        'edit' => [],
                        'delete' => [],
                    ],
                ]
            )
        ;
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('translations', TranslationsType::class, [])
            ->add('rank', null, ['required' => false])
//            ->add('slug')
            ->add('isActive')
            ->end()
        ;
    }
}
