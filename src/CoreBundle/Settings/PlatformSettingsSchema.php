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
        'MenuCampusHomepage' => 'campus_homepage',
        'MenuMyCourses' => 'my_courses',
        'MenuReporting' => 'reporting',
        'MenuPlatformAdministration' => 'platform_administration',
        'MenuMyAgenda' => 'my_agenda',
        'MenuSocial' => 'social',
        'MenuVideoConference' => 'videoconference',
        'MenuDiagnostics' => 'diagnostics',
        'MenuCatalogue' => 'catalogue',
        'MenuSessionAdmin' => 'session_admin',
        'TopbarCertificate' => 'topbar_certificate',
        'TopbarSkills' => 'topbar_skills',
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
                    'timezone' => 'Europe/Paris',
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
                    'load_term_conditions_section' => 'login',
                    'server_type' => 'prod',
                    'show_tabs' => array_values(array_diff(self::$tabs, ['videoconference', 'diagnostics'])),
                    'chamilo_database_version' => '2.0.0',
                    'unoconv_binaries' => '/usr/bin/unoconv',
                    'hide_main_navigation_menu' => 'false',
                    'pdf_img_dpi' => '96',
                    'tracking_skip_generic_data' => 'false',
                    'hide_complete_name_in_whoisonline' => 'false',
                    'table_default_row' => '0',
                    'allow_double_validation_in_registration' => 'false',
                    'block_my_progress_page' => 'false',
                    'hosting_limit_users_per_course' => '0',
                    'generate_random_login' => 'false',
                    'timepicker_increment' => '5',
                    'proxy_settings' => '',
                    'video_features' => '',
                    'table_row_list' => '',
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
                    'allow_working_time_edition' => 'false',
                    'disable_user_conditions_sender_id' => '0',
                    'portfolio_advanced_sharing' => 'false',
                    'redirect_index_to_url_for_logged_users' => '',
                    'default_menu_entry_for_course_or_session' => 'my_courses',
                    'notification_event' => 'false',
                    'show_tabs_per_role' => '{}',
                    'session_admin_user_subscription_search_extra_field_to_search' => '',
                    'push_notification_settings' => '',
                    'hosting_limit_identical_email' => '0',
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
            'timezone' => ['string'],
            'gravatar_enabled' => ['string'],
            'gravatar_type' => ['string'],
            'show_tabs' => ['array', 'null'],
            'show_tabs_per_role' => ['string', 'null'],
            'session_admin_user_subscription_search_extra_field_to_search' => ['string', 'null'],
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
            ->add('timezone', TimezoneType::class)
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
                        'Hide catalogue' => '-1',
                        'Show only courses' => '0',
                        'Show only sessions' => '1',
                        'Show courses and sessions' => '2',
                    ],
                ]
            )
            ->add('use_custom_pages', YesNoType::class)
            ->add('pdf_logo_header')
            ->add('allow_my_files', YesNoType::class)
            // old settings with no category
            ->add('chamilo_database_version')
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
                'server_type',
                ChoiceType::class,
                [
                    'label' => 'Server Type',
                    'choices' => [
                        'Production' => 'prod',
                        'Validation' => 'validation',
                        'Test/Development' => 'test',
                    ],
                ]
            )
            ->add(
                'show_tabs',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => self::$tabs,
                ],
            )
            ->add('show_tabs_per_role', TextareaType::class)
            ->add('unoconv_binaries', TextType::class)
            ->add('hide_main_navigation_menu', YesNoType::class)
            ->add('pdf_img_dpi', TextType::class)
            ->add('tracking_skip_generic_data', YesNoType::class)
            ->add('hide_complete_name_in_whoisonline', YesNoType::class)
            ->add('table_default_row', TextType::class)
            ->add('allow_double_validation_in_registration', YesNoType::class)
            ->add('block_my_progress_page', YesNoType::class)
            ->add('hosting_limit_users_per_course', TextType::class)
            ->add('generate_random_login', YesNoType::class)
            ->add('timepicker_increment', TextType::class)
            ->add('proxy_settings', TextareaType::class)
            ->add('video_features', TextareaType::class)
            ->add('table_row_list', TextareaType::class)
            ->add('webservice_return_user_field', TextType::class)
            ->add('multiple_url_hide_disabled_settings', YesNoType::class)
            ->add('login_max_attempt_before_blocking_account', TextType::class)
            ->add('force_renew_password_at_first_login', YesNoType::class)
            ->add('hide_breadcrumb_if_not_allowed', YesNoType::class)
            ->add('extldap_config', TextareaType::class)
            ->add('update_student_expiration_x_date', TextareaType::class)
            ->add('user_status_show_options_enabled', YesNoType::class)
            ->add('user_status_show_option', TextareaType::class)
            ->add('user_number_of_days_for_default_expiration_date_per_role', TextareaType::class)
            ->add('user_edition_extra_field_to_check', TextType::class)
            ->add('user_hide_never_expire_option', YesNoType::class)
            ->add('platform_logo_url', TextType::class)
            ->add('use_career_external_id_as_identifier_in_diagrams', YesNoType::class)
            ->add('disable_webservices', YesNoType::class)
            ->add('webservice_enable_adminonly_api', YesNoType::class)
            ->add('allow_working_time_edition', YesNoType::class)
            ->add('disable_user_conditions_sender_id', TextType::class)
            ->add('portfolio_advanced_sharing', TextType::class)
            ->add('redirect_index_to_url_for_logged_users', TextType::class)
            ->add(
                'default_menu_entry_for_course_or_session',
                ChoiceType::class,
                [
                    'choices' => [
                        'My courses' => 'my_courses',
                        'My sessions' => 'my_sessions',
                    ],
                ]
            )
            ->add('notification_event', YesNoType::class)
            ->add(
                'session_admin_user_subscription_search_extra_field_to_search',
                TextType::class,
                [
                    'required' => false,
                    'empty_data' => '',
                    'help' => 'User extra field key to use when searching and naming sessions from /admin-dashboard/register.',
                ]
            )
            ->add('push_notification_settings', TextareaType::class)
            ->add(
                'hosting_limit_identical_email',
                TextType::class,
                [
                    'label' => 'Limit identical emails',
                    'help' => 'Maximum number of accounts allowed with the same email. Set to 0 to disable limit.',
                ]
            )
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }

    public function getHiddenSettings(): array
    {
        return [
            'registered',
        ];
    }
}
