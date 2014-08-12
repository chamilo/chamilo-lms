<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class GlossarySettingsSchema
 * @package ChamiloLMS\CoreBundle\Settings
 */
class GlossarySettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'show_glossary_in_extra_tools'

            ))
            ->setAllowedTypes(array(
                'show_glossary_in_extra_tools' => array('string'),

            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('show_glossary_in_extra_tools', 'yes_no')
        ;

    }
}
