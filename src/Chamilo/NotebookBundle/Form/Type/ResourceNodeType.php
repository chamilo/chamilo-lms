<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ResourceNodeType
 * @package Chamilo\NotebookBundle\Form\Type
 */
class ResourceNodeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tool')
            ->add('links',  'collection', array(
                'type' =>new ResourceLinkType())
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Chamilo\CoreBundle\Entity\Resource\ResourceNode'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo_resource_node_type';
    }
}
