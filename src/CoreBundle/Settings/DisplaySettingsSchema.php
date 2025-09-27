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
use Symfony\Component\Form\FormBuilderInterface;

class DisplaySettingsSchema extends AbstractSettingsSchema
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
                    'enable_help_link' => 'true',
                    'show_administrator_data' => 'true',
                    'show_tutor_data' => 'true',
                    'show_teacher_data' => 'true',
                    'showonline' => 'world',
                    'time_limit_whosonline' => '30',
                    'show_email_addresses' => 'false',
                    'show_number_of_courses' => 'false',
                    'show_empty_course_categories' => 'true',
                    'show_back_link_on_top_of_tree' => 'false',
                    'display_categories_on_homepage' => 'false',
                    'show_closed_courses' => 'false',
                    'accessibility_font_resize' => 'false',
                    'show_admin_toolbar' => 'do_not_show',
                    'show_hot_courses' => 'true',
                    'hide_home_top_when_connected' => 'false',
                    'hide_logout_button' => 'false',
                    'icons_mode_svg' => 'false',
                    'hide_social_media_links' => 'false',

                    'gravatar_enabled' => 'false',
                    'gravatar_type' => 'mm',
                    'order_user_list_by_official_code' => 'false',
                    'pdf_logo_header' => '',
                    'show_tabs' => array_values(array_diff(self::$tabs, ['videoconference', 'diagnostics'])),
                    'show_tabs_per_role' => '{}',
                    'hide_main_navigation_menu' => 'false',
                    'hide_complete_name_in_whoisonline' => 'false',
                    'table_default_row' => '20',
                    'table_row_list' => '[10,20,50,100]',
                ]
            )
            ->setTransformer(
                'show_tabs',
                new ArrayToIdentifierTransformer()
            )
        ;

        $allowedTypes = [
            'time_limit_whosonline' => ['string'],
            'show_tabs' => ['array', 'null'],
            'show_tabs_per_role' => ['string', 'null'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('enable_help_link', YesNoType::class)
            ->add('show_administrator_data', YesNoType::class)
            ->add('show_tutor_data', YesNoType::class)
            ->add('show_teacher_data', YesNoType::class)
            ->add(
                'showonline',
                ChoiceType::class,
                [
                    'choices' => [
                        'Course' => 'course',
                        'Users' => 'users',
                        'World' => 'world',
                    ],
                ]
            )
            ->add('time_limit_whosonline')
            ->add('show_email_addresses', YesNoType::class)
            ->add('show_number_of_courses', YesNoType::class)
            ->add('show_empty_course_categories', YesNoType::class)
            ->add('show_back_link_on_top_of_tree', YesNoType::class)
            ->add('show_empty_course_categories', YesNoType::class)
            ->add('display_categories_on_homepage', YesNoType::class)
            ->add('show_closed_courses', YesNoType::class)
            ->add('accessibility_font_resize', YesNoType::class)
            ->add(
                'show_admin_toolbar',
                ChoiceType::class,
                [
                    'choices' => [
                        'Do not show' => 'do_not_show',
                        'Show to admins only' => 'show_to_admin',
                        'Show to admins and teachers' => 'show_to_admin_and_teachers',
                        'Show to all users' => 'show_to_all',
                    ],
                ]
            )
            ->add('show_hot_courses', YesNoType::class)
            ->add('hide_home_top_when_connected', YesNoType::class)
            ->add('hide_logout_button', YesNoType::class)
            ->add('icons_mode_svg', YesNoType::class)
            ->add('hide_social_media_links', YesNoType::class)
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
            ->add('order_user_list_by_official_code', YesNoType::class)
            ->add('pdf_logo_header')
            ->add(
                'show_tabs',
                ChoiceType::class,
                [
                    'multiple' => true,
                    'choices' => self::$tabs,
                ],
            )
            ->add('show_tabs_per_role', TextareaType::class)
            ->add('hide_main_navigation_menu', YesNoType::class)
            ->add('hide_complete_name_in_whoisonline', YesNoType::class)
            ->add('table_default_row', TextType::class)
            ->add('table_row_list', TextareaType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
