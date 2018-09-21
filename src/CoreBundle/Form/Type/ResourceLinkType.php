<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Chamilo\CoreBundle\Entity\ToolResourceRight;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ResourceLinkType.
 *
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
                [
                    'choices' => [
                        'everyone' => 'Everyone',
                        'course' => 'Course',
                        'user' => 'User',
                        'group' => 'Group',
                    ],
                    'attr' => ['class' => 'sharing_options'],
                    'mapped' => false,
                ]
            )
            ->add(
                'search',
                'hidden',
                [
                    'attr' => ['class' => 'extra_hidden'],
                    'mapped' => false,
                ]
            )
            ->add(
                'role',
                ChoiceType::class,
                [
                    'choices' => ToolResourceRight::getDefaultRoles(),
                    'mapped' => false,
                ]
            )
            ->add(
                'mask',
                ChoiceType::class,
                [
                    'choices' => ToolResourceRight::getMaskList(),
                    'mapped' => false,
                ]
            )/*->add(
                'rights',
                'collection',
                array(
                    'type' => new ResourceRightType(),
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
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\Resource\ResourceLink',
            ]
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
