<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class PlatformSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'institution' => 'Campus Chamilo',
                'institution_url' => 'http://www.chamilo.org',
                'site_name'    => 'Chamilo Association',
                'administrator_email' => '',
                'administrator_name' => '',
                'administrator_surname' => '',
                'administrator_phone' => '',
                'timezone_value' => '',
                'settings_latest_update' => '',
            ))
            ->setAllowedTypes(array(
                'institution' => array('string'),
                'institution_url' => array('string'),
                'site_name' => array('string'),
                'administrator_email' => array('string'),
                'administrator_name' => array('string'),
                'administrator_surname' => array('string'),
                'administrator_phone' => array('string')
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('institution')
            ->add('institution_url')
            ->add('site_name')
            ->add('administrator_email')
            ->add('administrator_name')
            ->add('administrator_surname')
            ->add('administrator_phone')
        ;
    }
}
