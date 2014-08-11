<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class DisplaySettingsSchema implements SchemaInterface
{
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

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('enable_help_link', 'choice', array('choices' =>
                    array('true' => 'Yes', 'no' => 'No'))
            );

    }
}
