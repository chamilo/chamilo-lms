<?php

namespace ChamiloLMS\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class CourseSettingsSchema implements SchemaInterface
{
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'homepage_view' => '',
                'show_toolshortcuts' => '',
                'course_create_active_tools' => '', //course_progress attendances notebook glossary survey 'gradebook course_description, agenda, documents, learning_path,links, announcements, forums  dropbox quiz users groups  chat online_conference student_publications wiki
                'display_coursecode_in_courselist' => '',
                'display_teacher_in_courselist' => '',
                'student_view_enabled' => '',
                'go_to_course_after_login' => '',
                'show_navigation_menu' => '',
                'enable_tool_introduction' => '',
                'breadcrumbs_course_homepage' => '',
                'example_material_course_creation' => '',
                'allow_course_theme' => '',
                'allow_users_to_create_courses' => '',
                'show_courses_descriptions_in_catalog' => '',
                'send_email_to_admin_when_create_course' => '',
                'allow_user_course_subscription_by_course_admin' => '',
                'course_validation' => '',
                'course_validation_terms_and_conditions_url' => '',
                'course_hide_tools' => '',
                'scorm_cumulative_session_time' => '',
                'courses_default_creation_visibility' => '',
                'allow_public_certificates' => '',
            ))
            ->setAllowedTypes(array(
                'homepage_view' => array('string'),
                'show_toolshortcuts' => array('string'),
                'course_create_active_tools' => array('string'),
                'display_coursecode_in_courselist' => array('string'),
                'display_teacher_in_courselist' => array('string'),
                'student_view_enabled' => array('string'),
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('homepage_view')
            ->add('show_toolshortcuts')
            ->add('course_create_active_tools')
            ->add('display_coursecode_in_courselist')
            ->add('display_teacher_in_courselist')
        ;
    }
}
