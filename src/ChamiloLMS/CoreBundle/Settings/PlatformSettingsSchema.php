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
                'portal_name' => 'Campus Chamilo',
                'company_title'    => 'Chamilo Association',
                'company_url' => 'http://www.chamilo.org',
                'enable_help_link' => ''
            ))
            ->setAllowedTypes(array(
                'portal_name' => array('string'),
                'company_title' => array('string'),
                'company_url' => array('string'),
                'enable_help_link' => array('string'),
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('portal_name')
            ->add('company_title')
            ->add('company_url')
            ->add('enable_help_link', 'choice', array('choices' =>
                array('true' => 'Yes', 'no' => 'No'))
            )
        ;
    }
}
