<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class DisplaySettingsSchema implements SchemaInterface
{
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
                'time_limit_whosonline' => '',
                'allow_email_editor' => '',
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
                'user_name_order' => '',
                'user_name_sort_by' => '',
                'use_virtual_keyboard' => '',
                'disable_copy_paste' => '',
                'breadcrumb_navigation_display' => '',
                'bug_report_link' => ''


            ))
            ->setAllowedTypes(array(
                'enable_help_link' => array('string'),
                'show_administrator_data' => array('string'),
                'show_tutor_data' => array('string'),
                'show_teacher_data' => array('string'),
                'showonline' => array('string'),
                'allow_user_headings' => array('string'),
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('enable_help_link', 'choice', array('choices' =>
                    array('true' => 'Yes', 'no' => 'No'))
            )
            ->add('show_administrator_data')
            ->add('show_tutor_data')
            ->add('show_teacher_data')
            ->add('showonline')
            ->add('allow_user_headings')
        ;
    }
}
