<?php

namespace Chamilo\FaqBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Class QuestionAdmin
 *
 * @package Genj\FaqAdminBundle\Admin
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
            ->add('headline')
            ->add('body')
            ->add('category')
            ->add('slug');
    }

    /**
     * @param ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('headline', null, array('identifier' => true))
            ->add('Category')
            ->add('rank')
            ->add('_action', 'actions',
                array(
                    'actions' => array(
                        //'show' => array(),
                        'preview' => array('template' => 'ChamiloFaqBundle:Faq:preview_question_partial.html.twig'),
                        'edit' => array(),
                        'delete' => array()
                    )
                )
            );
    }

    /**
     * @param FormMapper $formMapper
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('headline')
            ->add('body', null, array('required' => true))
            ->add('category', null, array(
                    'expanded' => true,
                    'required' => true,
                    'attr' => array('class' => 'radio-list vertical')
                ))
            ->add('rank', null, array('required' => false))
            ->add('slug', null, array('required' => false))
            ->add('onlyAuthUsers')
            ->end()
        ;
    }
}
