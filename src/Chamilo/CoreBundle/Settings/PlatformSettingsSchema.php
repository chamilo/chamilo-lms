<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PlatformSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class PlatformSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'institution' => 'Chamilo.org',
                'institution_url' => 'http://www.chamilo.org',
                'site_name'    => 'Chamilo site',
                'administrator_email' => 'admin@example.org',
                'administrator_name' => 'Jane',
                'administrator_surname' => 'Doe',
                'administrator_phone' => '123456',
                'timezone' => 'Europe/Paris',
                'theme' => 'chamilo'
            ))
            ->setAllowedTypes(array(
                'institution' => array('string'),
                'institution_url' => array('string'),
                'site_name' => array('string'),
                'administrator_email' => array('string'),
                'administrator_name' => array('string'),
                'administrator_surname' => array('string'),
                'administrator_phone' => array('string'),
                'timezone' => array('string'),
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('institution')
            ->add('institution_url', 'url')
            ->add('site_name')
            ->add('administrator_email', 'email')
            ->add('administrator_name')
            ->add('administrator_surname')
            ->add('administrator_phone')
            ->add('timezone', 'timezone')
            ->add('theme')
        ;
    }
}
