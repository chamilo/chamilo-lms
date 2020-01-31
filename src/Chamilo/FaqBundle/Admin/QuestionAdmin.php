<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Class QuestionAdmin.
 *
 * @package Chamilo\FaqBundle\Admin
 */
class QuestionAdmin extends Admin
{
    protected $datagridValues = [
        '_page' => 1,
        '_sort_by' => 'issueDate',
        '_sort_order' => 'Desc',
    ];

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('isActive')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('translations', null, ['identifier' => true])
            ->add('Category')
            ->add('rank')
            ->add(
                '_action',
                'actions',
                [
                    'actions' => [
                        //'show' => array(),
                        'preview' => ['template' => 'ChamiloFaqBundle:Faq:preview_question_partial.html.twig'],
                        'edit' => [],
                        'delete' => [],
                    ],
                ]
            );
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('translations', 'a2lix_translations', [])
            ->add(
                'category',
                null,
                [
                    'expanded' => true,
                    'required' => true,
                    'attr' => ['class' => 'radio-list vertical'],
                ]
            )
            ->add('rank', null, ['required' => false])
            ->add('onlyAuthUsers')
            ->add('isActive')
            ->end();
    }
}
