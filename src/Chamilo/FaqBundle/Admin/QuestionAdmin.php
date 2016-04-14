<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Class QuestionAdmin
 *
 * @package Chamilo\FaqBundle\Admin
 */
class QuestionAdmin extends Admin
{
    protected $datagridValues = array(
        '_page' => 1,
        '_sort_by' => 'issueDate',
        '_sort_order' => 'Desc'
    );

    /**
     * @param DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('isActive')
        ;
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('translations', null, array('identifier' => true))
            ->add('Category')
            ->add('rank')
            ->add(
                '_action',
                'actions',
                array(
                    'actions' => array(
                        //'show' => array(),
                        'preview' => array('template' => 'ChamiloFaqBundle:Faq:preview_question_partial.html.twig'),
                        'edit' => array(),
                        'delete' => array(),
                    ),
                )
            );
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('translations', 'a2lix_translations', array())
            ->add(
                'category',
                null,
                array(
                    'expanded' => true,
                    'required' => true,
                    'attr' => array('class' => 'radio-list vertical'),
                )
            )
            ->add('rank', null, array('required' => false))
            ->add('onlyAuthUsers')
            ->add('isActive')
            ->end();
    }
}
