<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DisplaySettingsSchema.
 *
 * @package Chamilo\CoreBundle\Settings
 */
class DisplaySettingsSchema extends AbstractSettingsSchema
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(AbstractSettingsBuilder $builder)
    {
        $builder
            ->setDefaults(
                [
                    'enable_help_link' => 'true',
                    'show_administrator_data' => 'true',
                    'show_tutor_data' => 'true',
                    'show_teacher_data' => 'true',
                    'showonline' => 'world',
                    'allow_user_headings' => 'false',
                    'time_limit_whosonline' => '30',
                    'show_email_addresses' => 'false',
                    'show_number_of_courses' => 'false',
                    'show_empty_course_categories' => 'true',
                    'show_back_link_on_top_of_tree' => 'false',
                    'display_categories_on_homepage' => 'false',
                    'show_closed_courses' => 'false',
                    'allow_students_to_browse_courses' => 'true',
                    'show_link_bug_notification' => 'false',
                    'accessibility_font_resize' => 'false',
                    'show_admin_toolbar' => 'do_not_show',
                    'show_hot_courses' => 'true',
                    'user_name_order' => '', // ?
                    'user_name_sort_by' => '', // ?
                    'use_virtual_keyboard' => '', //?
                    'disable_copy_paste' => '', //?
                    //'breadcrumb_navigation_display' => '',//?
                    'bug_report_link' => '', //?
                    'hide_home_top_when_connected' => 'false',
                    'hide_logout_button' => 'false',
                    'show_link_ticket_notification' => 'false',
                    'icons_mode_svg' => 'false',
                    'default_template' => 'default',
                    'hide_social_media_links' => 'false',
                ]
            );

        $allowedTypes = [
            'time_limit_whosonline' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
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
            ->add('allow_user_headings', YesNoType::class)
            ->add('time_limit_whosonline')
            ->add('show_email_addresses', YesNoType::class)
            ->add('show_number_of_courses', YesNoType::class)
            ->add('show_empty_course_categories', YesNoType::class)
            ->add('show_back_link_on_top_of_tree', YesNoType::class)
            ->add('show_empty_course_categories', YesNoType::class)
            ->add('display_categories_on_homepage', YesNoType::class)
            ->add('show_closed_courses', YesNoType::class)
            ->add('allow_students_to_browse_courses', YesNoType::class)
            ->add('show_link_bug_notification', YesNoType::class)
            ->add('accessibility_font_resize', YesNoType::class)
            ->add(
                'show_admin_toolbar',
                ChoiceType::class,
                [
                    'choices' => [
                        'DoNotShow' => 'do_not_show',
                        'ShowToAdminsOnly' => 'show_to_admin',
                        'ShowToAdminsAndTeachers' => 'show_to_admin_and_teachers',
                        'ShowToAllUsers' => 'show_to_all',
                    ],
                ]
            )
            ->add('show_hot_courses', YesNoType::class)
            ->add('use_virtual_keyboard', YesNoType::class)
            ->add('disable_copy_paste', YesNoType::class)
            //->add('breadcrumb_navigation_display', YesNoType::class)
            ->add('bug_report_link', YesNoType::class)
            ->add('hide_home_top_when_connected', YesNoType::class)
            ->add('hide_logout_button', YesNoType::class)
            ->add('show_link_ticket_notification', YesNoType::class)
            ->add('icons_mode_svg', YesNoType::class)
            ->add('default_template')
            ->add('hide_social_media_links', YesNoType::class)
        ;
    }
}
