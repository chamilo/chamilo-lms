<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PlatformSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class PlatformSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(
                array(
                    'institution' => 'Chamilo.org',
                    'institution_url' => 'http://www.chamilo.org',
                    'institution_address' => '',
                    'site_name' => 'Chamilo site',
//                    'administrator_email' => 'admin@example.org',
//                    'administrator_name' => 'Jane',
//                    'administrator_surname' => 'Doe',
//                    'administrator_phone' => '123456',
                    'timezone' => 'Europe/Paris',
                    'theme' => 'chamilo',
                    'gravatar_enabled' => 'false',
                    'gravatar_type' => 'mm',
                    'gamification_mode' => ' ',
                    'order_user_list_by_official_code' => 'false',
                    'cookie_warning' => 'false',
                    'donotlistcampus' => 'false',
                    'catalog_show_courses_sessions' => '0',
                    'course_catalog_hide_private' => 'false',
                    'use_custom_pages' => 'false',
                    'pdf_logo_header' => '',
                    'allow_my_files' => 'true'
                    //
//('catalog_show_courses_sessions', '0', 'CatalogueShowOnlyCourses'),
//('catalog_show_courses_sessions', '1', 'CatalogueShowOnlySessions'),
//('catalog_show_courses_sessions', '2', 'CatalogueShowCoursesAndSessions'),
                )
            );
        $allowedTypes = array(
            'institution' => array('string'),
            'institution_url' => array('string'),
            'site_name' => array('string'),
//                    'administrator_email' => array('string'),
//                    'administrator_name' => array('string'),
//                    'administrator_surname' => array('string'),
//                    'administrator_phone' => array('string'),
            'timezone' => array('string'),
            'gravatar_enabled' => array('string'),
            'gravatar_type' => array('string'),
            //'gamification_mode' => array('string'),
        );
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('institution')
            ->add('institution_url', 'url')
            ->add('institution_address')
            ->add('site_name')
//            ->add('administrator_email', 'email')
//            ->add('administrator_name')
//            ->add('administrator_surname')
//            ->add('administrator_phone')
            ->add('timezone', 'timezone')
            ->add('theme')
            ->add('gravatar_enabled', YesNoType::class)
            ->add('gravatar_type')
            ->add('gamification_mode')
            ->add('order_user_list_by_official_code', YesNoType::class)
            ->add('cookie_warning', YesNoType::class)
            ->add('donotlistcampus', YesNoType::class)
            ->add('course_catalog_hide_private', YesNoType::class)
            ->add(
                'catalog_show_courses_sessions',
                'choice',
                ['choices' => [
                    '0' => 'CatalogueShowOnlyCourses',
                    '1' => 'CatalogueShowOnlySessions',
                    '2' => 'CatalogueShowCoursesAndSessions',
                ]]
            )
            ->add('use_custom_pages', YesNoType::class)
            ->add('pdf_logo_header')
            ->add('allow_my_files', YesNoType::class)
        ;
    }
}
