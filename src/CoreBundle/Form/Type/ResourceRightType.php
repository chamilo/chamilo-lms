<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Chamilo\CoreBundle\Entity\ToolResourceRight;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResourceRightType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'role',
                'choice',
                [
                    'choices' => ToolResourceRight::getDefaultRoles(),
                ]
            )
            ->add(
                'mask',
                'choice',
                [
                    'choices' => ToolResourceRight::getMaskList(),
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class' => ResourceRight::class,
            ]
        );
    }

    public function getName(): string
    {
        return 'chamilo_resource_rights_type';
    }
}
