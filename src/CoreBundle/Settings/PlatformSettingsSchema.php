<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
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
                    'cookie_warning' => 'false',
                    'donotlistcampus' => 'false',
                    'use_custom_pages' => 'false',
                    'allow_my_files' => 'true',
                    'registered' => 'false',
                    'server_type' => 'prod',
                    'chamilo_database_version' => '2.0.0',
                    'unoconv_binaries' => '/usr/bin/unoconv',
                    'pdf_img_dpi' => '96',
                    'hosting_limit_users_per_course' => '0',
                    'generate_random_login' => 'false',
                    'timepicker_increment' => '15',
                    'user_status_show_options_enabled' => 'false',
                    'user_status_show_option' => '',
                    'platform_logo_url' => 'https://chamilo.org',
                    'use_career_external_id_as_identifier_in_diagrams' => 'false',
                    'portfolio_advanced_sharing' => 'false',
                    'portfolio_show_base_course_post_in_sessions' => 'false',
                    'notification_event' => 'false',
                    'push_notification_settings' => '',
                    'hosting_limit_identical_email' => '0',
                    'session_admin_access_to_all_users_on_all_urls' => 'false',
                    'use_virtual_keyboard' => 'false',
                    'disable_copy_paste' => 'false',
                ]
            )
        ;

        $allowedTypes = [
            'institution' => ['string'],
            'institution_url' => ['string'],
            'site_name' => ['string'],
            'timezone' => ['string'],
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
            ->add('cookie_warning', YesNoType::class)
            ->add('donotlistcampus', YesNoType::class)
            ->add('use_custom_pages', YesNoType::class)
            ->add('allow_my_files', YesNoType::class)
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
            ->add('unoconv_binaries', TextType::class)
            ->add('pdf_img_dpi', TextType::class)
            ->add('hosting_limit_users_per_course', TextType::class)
            ->add('generate_random_login', YesNoType::class)
            ->add('timepicker_increment', TextType::class)
            ->add('user_status_show_options_enabled', YesNoType::class)
            ->add('user_status_show_option', TextareaType::class)
            ->add('platform_logo_url', TextType::class)
            ->add('use_career_external_id_as_identifier_in_diagrams', YesNoType::class)
            ->add('portfolio_advanced_sharing', YesNoType::class)
            ->add('portfolio_show_base_course_post_in_sessions', YesNoType::class)
            ->add('notification_event', YesNoType::class)
            ->add('push_notification_settings', TextareaType::class)
            ->add(
                'hosting_limit_identical_email',
                TextType::class,
                [
                    'label' => 'Limit identical emails',
                    'help' => 'Maximum number of accounts allowed with the same email. Set to 0 to disable limit.',
                ]
            )
            ->add('session_admin_access_to_all_users_on_all_urls', YesNoType::class)
            ->add('use_virtual_keyboard', YesNoType::class)
            ->add('disable_copy_paste', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }

    public function getHiddenSettings(): array
    {
        return [
            'registered',
            'chamilo_database_version',
        ];
    }
}
