<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class NotebookType
 * @package Chamilo\NotebookBundle\Form\Type
 */
class NotebookType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description', 'ckeditor')
            ->add(
                'shared',
                'choice',
                array(
                    'choices' => array(
                        'this_course' => 'This course',
                        'only_me' => 'Only me',
                        'shared' => 'Shared'
                    ),
                    'multiple' => false,
                    'expanded' => true,
                    'required' => true,
                    'mapped' => false
                )
            )
            ->add(
                'rights',
                'collection',
                array(
                    'type' => new ResourceLinkType(),
                    'mapped' => false,
                    'allow_add' => true,
                    'by_reference' => false,
                    'allow_delete' => true
                )
            )
            /*->add(
                'rights',
                'collection',
                array(
                    'type' => new ResourceRightsType(),
                    'mapped' => false,
                    'allow_add' => true,
                )
            )*/
            //->add('resourceNode', new ResourceNodeType())
            ->add('save', 'submit');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Chamilo\NotebookBundle\Entity\CNotebook'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo_notebook_notebook';
    }
}
