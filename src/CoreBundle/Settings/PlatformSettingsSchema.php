<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\SettingsBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PlatformSettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class PlatformSettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
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
                    'allow_my_files' => 'true',
                    'registered' => 'false',
                    'keep_old_images_after_delete' => 'true',
                    'load_term_conditions_section' => 'login',
                    //'server_type' => 'prod', replaced by value in .env
                    'show_tabs' => [],
                    'chamilo_database_version' => '2.0.0',
                    //
//('catalog_show_courses_sessions', '0', 'CatalogueShowOnlyCourses'),
//('catalog_show_courses_sessions', '1', 'CatalogueShowOnlySessions'),
//('catalog_show_courses_sessions', '2', 'CatalogueShowCoursesAndSessions'),
                ]
            )
            ->setTransformer(
                'show_tabs',
                new ArrayToIdentifierTransformer()
            )
        ;
        $allowedTypes = [
            'institution' => ['string'],
            'institution_url' => ['string'],
            'site_name' => ['string'],
//                    'administrator_email' => array('string'),
//                    'administrator_name' => array('string'),
//                    'administrator_surname' => array('string'),
//                    'administrator_phone' => array('string'),
            'timezone' => ['string'],
            'gravatar_enabled' => ['string'],
            'gravatar_type' => ['string'],
            'show_tabs' => ['array'],
            //'gamification_mode' => array('string'),
        ];

        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $tabs = [
            'TabsCampusHomepage' => 'campus_homepage',
            'TabsMyCourses' => 'my_courses',
            'TabsReporting' => 'reporting',
            'TabsPlatformAdministration' => 'platform_administration',
            'mypersonalopenarea' => 'my_agenda',
            'TabsMyAgenda' => 'my_profile',
            'TabsMyGradebook' => 'my_gradebook',
            'TabsSocial' => 'social',
            'TabsDashboard' => 'dashboard',
        ];

        $builder
            ->add('institution')
            ->add('institution_url', UrlType::class)
            ->add('institution_address')
            ->add('site_name')
//            ->add('administrator_email', 'email')
//            ->add('administrator_name')
//            ->add('administrator_surname')
//            ->add('administrator_phone')
            ->add('timezone', TimezoneType::class)
            ->add('theme')
            ->add('gravatar_enabled', YesNoType::class)
            ->add(
                'gravatar_type',
                ChoiceType::class,
                [
                    'choices' => [
                        'mistery-man' => 'mm',
                        'identicon' => 'identicon',
                        'monsterid' => 'monsterid',
                        'wavatar' => 'wavatar',
                    ],
                ]
            )
            ->add('gamification_mode')
            ->add('order_user_list_by_official_code', YesNoType::class)
            ->add('cookie_warning', YesNoType::class)
            ->add('donotlistcampus', YesNoType::class)
            ->add('course_catalog_hide_private', YesNoType::class)
            ->add(
                'catalog_show_courses_sessions',
                ChoiceType::class,
                ['choices' => [
                    'CatalogueShowOnlyCourses' => '0',
                    'CatalogueShowOnlySessions' => '1',
                    'CatalogueShowCoursesAndSessions' => '2',
                ]]
            )
            ->add('use_custom_pages', YesNoType::class)
            ->add('pdf_logo_header')
            ->add('allow_my_files', YesNoType::class)
            // old settings with no category
            ->add('chamilo_database_version')
            ->add('registered', YesNoType::class)
            ->add('keep_old_images_after_delete', YesNoType::class)
            ->add(
                'load_term_conditions_section',
                ChoiceType::class,
                [
                    'choices' => [
                        'Login' => '0',
                        'Course' => '1',
                    ],
                ]
            )
            ->add(
                'show_tabs',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => $tabs,
                    'label' => 'ShowTabsTitle',
                    'help_block' => 'ShowTabsComment',
                ]
            )
        ;
    }
}
