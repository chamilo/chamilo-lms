<?php

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AdminSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class AdminSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'administrator_email' => '',
                'administrator_name' => '',
                'administrator_surname' => '',
                'administrator_phone' => ''
            ))
            ->setAllowedTypes(array(
                'administrator_email' => array('string'),
                'administrator_name' => array('string'),
                'administrator_surname' => array('string'),
                'administrator_phone' => array('integer'),
                //'default_calendar_view' => array('string'),

            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('administrator_name')
            ->add('administrator_surname')
            ->add('administrator_email', 'email')
            ->add('administrator_phone')
            //->add('default_calendar_view', 'yes_no')
        ;
    }
}
