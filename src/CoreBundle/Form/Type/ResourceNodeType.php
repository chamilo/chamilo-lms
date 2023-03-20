<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResourceNodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tool')
            ->add(
                'links',
                'collection',
                [
                    'type' => new ResourceLinkType(),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ResourceNode::class,
            ]
        );
    }

    public function getName(): string
    {
        return 'chamilo_resource_node_type';
    }
}
