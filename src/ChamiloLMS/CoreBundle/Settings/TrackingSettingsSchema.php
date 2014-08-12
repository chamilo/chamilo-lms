<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class TrackingSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'header_extra_content' => '',
                'footer_extra_content' => ''

            ))
            ->setAllowedTypes(array(
                'header_extra_content' => array('string'),
                'footer_extra_content' => array('string')
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('header_extra_content', 'textarea')
            ->add('footer_extra_content', 'textarea')
        ;
    }
}
