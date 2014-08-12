<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SocialSettingsSchema
 * @package ChamiloLMS\CoreBundle\Settings
 */
class SocialSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'allow_social_tool' => '',
                'allow_students_to_create_groups_in_social' => ''

            ))
            ->setAllowedTypes(array(
                'allow_social_tool' => array('string'),
                'allow_students_to_create_groups_in_social' => array('string')
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('allow_social_tool', 'yes_no')
            ->add('allow_students_to_create_groups_in_social', 'yes_no')
        ;
    }
}
