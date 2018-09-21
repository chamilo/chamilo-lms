<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Form\Type;

use Chamilo\CoreBundle\Entity\ToolResourceRight;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ResourceRightType.
 *
 * @package Chamilo\CoreBundle\Form\Type
 */
class ResourceRightType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'role',
                'choice',
                ['choices' => ToolResourceRight::getDefaultRoles()]
            )
            ->add(
                'mask',
                'choice',
                ['choices' => ToolResourceRight::getMaskList()]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Chamilo\CoreBundle\Entity\Resource\ResourceRight',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo_resource_rights_type';
    }
}
