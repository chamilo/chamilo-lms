<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\NotebookBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class NotebookSettingsSchema
 * Global notebook settings for all the platform
 * @package Chamilo\NotebookBundle\Settings
 */
class NotebookSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'enabled' => '',
            ))
            ->setAllowedTypes(array(
                'enabled' => array('string'),
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        // yes_no is a new FormType located here:
        // Chamilo\CoreBundle\Form\Type\YesNoType
        $builder
            ->add('enabled', 'yes_no')
        ;
    }
}
