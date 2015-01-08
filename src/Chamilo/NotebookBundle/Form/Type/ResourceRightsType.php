<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Form\Type;

use Chamilo\CoreBundle\Entity\ToolResourceRights;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ResourceRightsType
 * @package Chamilo\NotebookBundle\Form\Type
 */
class ResourceRightsType extends AbstractType
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
                array('choices' => ToolResourceRights::getDefaultRoles())
            )
            ->add(
                'mask',
                'choice',
                array('choices' => ToolResourceRights::getMaskList())
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Chamilo\CoreBundle\Entity\Resource\ResourceRights'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'chamilo_resource_rights_type';
    }
}
