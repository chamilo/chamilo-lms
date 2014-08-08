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
                    'title'            => 'Sylius - Modern ecommerce for Symfony2',
                    'meta_keywords'    => 'symfony, sylius, ecommerce, webshop, shopping cart',
                    'meta_description' => 'Sylius is modern ecommerce solution for PHP. Based on the Symfony2 framework.',
                ))
            ->setAllowedTypes(array(
                    'title'            => array('string'),
                    'meta_keywords'    => array('string'),
                    'meta_description' => array('string'),
                ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('title')
            ->add('meta_keywords')
            ->add('meta_description', 'textarea')
        ;
    }
}
