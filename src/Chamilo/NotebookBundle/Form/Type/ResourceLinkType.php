<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Form\Type;

use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ResourceLinkType
 * @package Chamilo\NotebookBundle\Form\Type
 */
class ResourceLinkType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'sharing',
                'choice',
                array(
                    'choices' => array(
                        'public' => 'Public',
                        //'private' => 'Only me',
                        'this_course' => 'This course',
                        'another_course' => 'Another course',
                        'user'=> 'User'
                    ),
                    'attr' => array('class' => 'sharing_options')
                )
            )
            ->add('search', 'hidden', array('attr' => array('class' => 'extra_hidden')))
            ->add(
                'mask',
                'choice',
                array('choices' => ToolResourceRights::getMaskList())
            )
            /*->add(
                'rights',
                'collection',
                array(
                    'type' => new ResourceRightsType(),
                    'allow_add'    => true,
                )
            )*/
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Chamilo\CoreBundle\Entity\Resource\ResourceLink'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo_resource_link_type';
    }
}
