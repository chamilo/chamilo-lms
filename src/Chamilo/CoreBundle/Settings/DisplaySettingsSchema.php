<?php

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DisplaySettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class DisplaySettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'enable_help_link' => '',
                'show_administrator_data' => '',
                'show_tutor_data' => '',
                'show_teacher_data' => '',
                'showonline' => '',
                'allow_user_headings' => '',
                'time_limit_whosonline' => 30,
                'show_email_addresses' => '',
                'show_number_of_courses' => '',
                'show_empty_course_categories' => '',
                'show_back_link_on_top_of_tree' => '',
                'show_different_course_language' => '',
                'display_categories_on_homepage' => '',
                'show_closed_courses' => '',
                'allow_students_to_browse_courses' => '',
                'show_link_bug_notification' => '',
                'accessibility_font_resize' => '',
                'show_admin_toolbar' => '',
                'show_hot_courses' => '',
                'user_name_order' => '', // ?
                'user_name_sort_by' => '', // ?
                'use_virtual_keyboard' => '',
                'disable_copy_paste' => '',
                'breadcrumb_navigation_display' => '',
                'bug_report_link' => ''
            ))
            ->setAllowedTypes(array(
                'time_limit_whosonline' => array('integer')
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('enable_help_link', 'yes_no')
            ->add('show_administrator_data', 'yes_no')
            ->add('show_tutor_data', 'yes_no')
            ->add('show_teacher_data', 'yes_no')
            ->add(
                'showonline',
                'choice',
                array(
                    'choices' =>
                        array('course', 'users', 'world')
                )
            )
            ->add('allow_user_headings', 'yes_no')
            ->add('time_limit_whosonline')
            ->add('show_email_addresses', 'yes_no')
            ->add('show_number_of_courses', 'yes_no')
            ->add('show_empty_course_categories', 'yes_no')
            ->add('show_back_link_on_top_of_tree', 'yes_no')
            ->add('show_empty_course_categories', 'yes_no')
            ->add('show_different_course_language', 'yes_no')
            ->add('display_categories_on_homepage', 'yes_no')
            ->add('show_closed_courses', 'yes_no')
            ->add('allow_students_to_browse_courses', 'yes_no')
            ->add('show_link_bug_notification', 'yes_no')
            ->add('accessibility_font_resize', 'yes_no')
            ->add('show_admin_toolbar', 'yes_no')
            ->add('show_hot_courses', 'yes_no')
            ->add('use_virtual_keyboard', 'yes_no')
            ->add('disable_copy_paste', 'yes_no')
            ->add('breadcrumb_navigation_display', 'yes_no')
            ->add('bug_report_link', 'yes_no')
        ;
    }
}
