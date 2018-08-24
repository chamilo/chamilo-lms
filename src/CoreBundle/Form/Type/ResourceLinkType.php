<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Chamilo\CoreBundle\Entity\ToolResourceRights;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ResourceLinkType.
 * @package Chamilo\CoreBundle\Form\Type
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
                ChoiceType::class,
                array(
                    'choices' => array(
                        'everyone' => 'Everyone',
                        'course' => 'Course',
                        'user' => 'User',
                        'group' => 'Group',
                    ),
                    'attr' => array('class' => 'sharing_options'),
                    'mapped' => false
                )
            )
            ->add(
                'search',
                'hidden',
                array(
                    'attr' => array('class' => 'extra_hidden'),
                    'mapped' => false
                )
            )
            ->add(
                'role',
                ChoiceType::class,
                array(
                    'choices' => ToolResourceRights::getDefaultRoles(),
                    'mapped' => false
                )
            )
            ->add(
                'mask',
                ChoiceType::class,
                array(
                    'choices' => ToolResourceRights::getMaskList(),
                    'mapped' => false
                )
            )/*->add(
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
        $resolver->setDefaults(
            array(
                'data_class' => 'Chamilo\CoreBundle\Entity\Resource\ResourceLink',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo_resource_link_type';
    }
}
