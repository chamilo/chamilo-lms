<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Chamilo\CoreBundle\Transformer\ArrayToIdentifierTransformer;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class PlatformSettingsSchema extends AbstractSettingsSchema
{
    private static array $tabs = [
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

    public function buildSettings(AbstractSettingsBuilder $builder): void
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
                    'server_type' => 'prod',
                    // Chamilo mode
                    'show_tabs' => array_values(self::$tabs),
                    'chamilo_database_version' => '2.0.0',
                    //
                    //('catalog_show_courses_sessions', '0', 'CatalogueShowOnlyCourses'),
                    //('catalog_show_courses_sessions', '1', 'CatalogueShowOnlySessions'),
                    //('catalog_show_courses_sessions', '2', 'CatalogueShowCoursesAndSessions'),
                    'theme_fallback' => 'chamilo',
                    'unoconv_binaries' => '/usr/bin/unoconv',
                    'packager' => 'chamilo',
                    'sync_db_with_schema' => 'false',
                    'hide_main_navigation_menu' => 'false',
                    'pdf_img_dpi' => '96',
                    'tracking_skip_generic_data' => 'false',
                    'hide_complete_name_in_whoisonline' => 'false',
                    'table_default_row' => '0',
                    'allow_double_validation_in_registration' => 'false',
                    'block_my_progress_page' => 'false',
                    'generate_random_login' => 'false',
                    'timepicker_increment' => '5',
                    'proxy_settings' => '',
                    'video_features' => '',
                    'table_row_list' => '',
                    'allow_portfolio_tool' => 'false',
                    'session_stored_in_db_as_backup' => 'false',
                    'memcache_server' => '',
                    'session_stored_after_n_times' => '10',
                    'default_template' => 'default',
                    'aspell_bin' => '/usr/bin/hunspell',
                    'aspell_opts' => '-a -d en_GB -H -i utf-8',
                    'aspell_temp_dir' => './',
                    'webservice_return_user_field' => 'oauth2_id',
                    'multiple_url_hide_disabled_settings' => 'false',
                    'login_max_attempt_before_blocking_account' => '0',
                    'force_renew_password_at_first_login' => 'false',
                    'hide_breadcrumb_if_not_allowed' => 'false',
                    'extldap_config' => '',
                    'update_student_expiration_x_date' => '',
                    'user_status_show_options_enabled' => 'false',
                    'user_status_show_option' => '',
                    'user_number_of_days_for_default_expiration_date_per_role' => '',
                    'user_edition_extra_field_to_check' => 'ExtrafieldLabel',
                    'user_hide_never_expire_option' => 'false',
                    'platform_logo_url' => 'https://chamilo.org',
                    'use_career_external_id_as_identifier_in_diagrams' => 'false',
                    'disable_webservices' => 'false',
                    'webservice_enable_adminonly_api' => 'false',
                    'plugin_settings' => '',
                    'allow_working_time_edition' => 'false',
                    'disable_user_conditions_sender_id' => '0',
                    'portfolio_advanced_sharing' => 'false',
                    'redirect_index_to_url_for_logged_users' => '',
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
            'show_tabs' => ['array', 'null'],
            //'gamification_mode' => array('string'),
        ];

        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
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
                [
                    'choices' => [
                        'CatalogueShowOnlyCourses' => '0',
                        'CatalogueShowOnlySessions' => '1',
                        'CatalogueShowCoursesAndSessions' => '2',
                    ],
                ]
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
                        'Login' => 'login',
                        'Course' => 'course',
                    ],
                ]
            )
            ->add(
                'show_tabs',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => self::$tabs,
                    'label' => 'ShowTabsTitle',
                    'help' => 'ShowTabsComment',
                ],
            )
            ->add(
                'theme_fallback',
                TextType::class,
                [
                    'label' => 'ThemeFallbackTitle',
                    'help' => 'ThemeFallbackComment',
                ]
            )
            ->add(
                'unoconv_binaries',
                TextType::class,
                [
                    'label' => 'UnoconvBinariesTitle',
                    'help' => 'UnoconvBinariesComment',
                ]
            )
            ->add(
                'packager',
                TextType::class,
                [
                    'label' => 'PackagerTitle',
                    'help' => 'PackagerComment',
                ]
            )
            ->add('sync_db_with_schema', YesNoType::class)
            ->add('hide_main_navigation_menu', YesNoType::class)
            ->add('pdf_img_dpi', TextType::class)
            ->add('tracking_skip_generic_data', YesNoType::class)
            ->add('hide_complete_name_in_whoisonline', YesNoType::class)
            ->add(
                'table_default_row',
                TextType::class,
                [
                    'label' => 'TableDefaultRowTitle',
                    'help' => 'TableDefaultRowComment',
                ]
            )
            ->add('allow_double_validation_in_registration', YesNoType::class)
            ->add('block_my_progress_page', YesNoType::class)
            ->add('generate_random_login', YesNoType::class)
            ->add('timepicker_increment', TextType::class)
            ->add(
                'proxy_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Proxy settings for access external services').
                        $this->settingArrayHelpValue('proxy_settings'),
                ]
            )
            ->add(
                'video_features',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Enable speed controller in video player').
                        $this->settingArrayHelpValue('video_features'),
                ]
            )
            ->add(
                'table_row_list',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Changes the row list when using jqgrid/sortable tables').
                        $this->settingArrayHelpValue('table_row_list'),
                ]
            )
            ->add('allow_portfolio_tool', YesNoType::class)
            ->add('session_stored_in_db_as_backup', YesNoType::class)
            ->add(
                'memcache_server',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Define the different memcache servers available').
                        $this->settingArrayHelpValue('memcache_server'),
                ]
            )
            ->add('session_stored_after_n_times', TextType::class)
            ->add('default_template', TextType::class)
            ->add('aspell_bin', TextType::class)
            ->add('aspell_opts', TextType::class)
            ->add('aspell_temp_dir', TextType::class)
            ->add('webservice_return_user_field', TextType::class)
            ->add('multiple_url_hide_disabled_settings', YesNoType::class)
            ->add('login_max_attempt_before_blocking_account', TextType::class)
            ->add('force_renew_password_at_first_login', YesNoType::class)
            ->add('hide_breadcrumb_if_not_allowed', YesNoType::class)
            ->add(
                'extldap_config',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Overwrites the app/config/auth.conf.php settings').
                        $this->settingArrayHelpValue('extldap_config'),
                ]
            )
            ->add(
                'update_student_expiration_x_date',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Update user expiration in x days or months when login the first time').
                        $this->settingArrayHelpValue('update_student_expiration_x_date'),
                ]
            )
            ->add('user_status_show_options_enabled', YesNoType::class)
            ->add(
                'user_status_show_option',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('The user status is hidden when is false, it requires user_status_show_options_enabled = true').
                        $this->settingArrayHelpValue('user_status_show_option'),
                ]
            )
            ->add(
                'user_number_of_days_for_default_expiration_date_per_role',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Set the default expiration date when a user is created by role and days').
                        $this->settingArrayHelpValue('user_number_of_days_for_default_expiration_date_per_role'),
                ]
            )
            ->add('user_edition_extra_field_to_check', TextType::class)
            ->add('user_hide_never_expire_option', YesNoType::class)
            ->add('platform_logo_url', TextType::class)
            ->add('use_career_external_id_as_identifier_in_diagrams', YesNoType::class)
            ->add('disable_webservices', YesNoType::class)
            ->add('webservice_enable_adminonly_api', YesNoType::class)
            ->add(
                'plugin_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Disables the following BBB plugin settings in the plugin form and use them in priority').
                        $this->settingArrayHelpValue('plugin_settings'),
                ]
            )
            ->add('allow_working_time_edition', YesNoType::class)
            ->add('disable_user_conditions_sender_id', TextType::class)
            ->add('portfolio_advanced_sharing', TextType::class)
            ->add('redirect_index_to_url_for_logged_users', TextType::class)
        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'proxy_settings' => "<pre>
                [
                    'stream_context_create' => [
                        'http' => [
                            'proxy' => 'tcp://example.com:8080',
                            'request_fulluri' => true
                        ]
                    ],
                    'curl_setopt_array' => [
                        'CURLOPT_PROXY' => 'http://example.com',
                        'CURLOPT_PROXYPORT' => '8080'
                    ]
                ]
                </pre>",
            'video_features' => "<pre>
                ['features' => ['speed']]
                </pre>",
            'table_row_list' => "<pre>
                ['options' => [50, 100, 200, 500]]
                </pre>",
            'memcache_server' => "<pre>
                [
                    0 => [
                        'host' => 'chamilo8',
                        'port' => '11211',
                    ],
                    1 => [
                        'host' => 'chamilo9',
                        'port' => '11211',
                    ],
                ]
                </pre>",
            'extldap_config' => "<pre>
                ['host' => '', 'port' => '']
                </pre>",
            'update_student_expiration_x_date' => "<pre>
                [
                    'days' => 0,
                    'months' => 0,
                ]
                </pre>",
            'user_status_show_option' => "<pre>
                [
                    'COURSEMANAGER' => true,
                    'STUDENT' => true,
                    'DRH' => false,
                    'SESSIONADMIN' => false,
                    'STUDENT_BOSS' => false,
                    'INVITEE' => false
                ]
                </pre>",
            'user_number_of_days_for_default_expiration_date_per_role' => "<pre>
                [
                    'COURSEMANAGER' => 365,
                    'STUDENT' => 31,
                    'DRH' => 31,
                    'SESSIONADMIN' => 60,
                    'STUDENT_BOSS' => 60,
                    'INVITEE' => 31
                ]
                </pre>",
            'plugin_settings' => "<pre>
                [
                    'bbb' => [
                        'tool_enable' => 'true', // string value
                        'host' => 'https://www.example.com',
                        'salt' => 'abc123'
                    ]
                ]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
