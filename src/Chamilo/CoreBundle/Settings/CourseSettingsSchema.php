<?php

namespace Chamilo\CoreBundle\Settings;

use Sylius\Bundle\SettingsBundle\Schema\SchemaInterface;
use Sylius\Bundle\SettingsBundle\Schema\SettingsBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CourseSettingsSchema
 * @package Chamilo\CoreBundle\Settings
 */
class CourseSettingsSchema implements SchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildSettings(SettingsBuilderInterface $builder)
    {
        $builder
            ->setDefaults(array(
                'homepage_view' => 'activity_big',
                'show_tool_shortcuts' => '',
                'course_create_active_tools' => array('course_progress'),
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
                'course_hide_tools' => array(),
                'scorm_cumulative_session_time' => '',
                'courses_default_creation_visibility' => '',
                'allow_public_certificates' => '',
            ))
            ->setAllowedTypes(array(
                'homepage_view' => array('string'),
                'show_tool_shortcuts' => array('string'),
                'course_create_active_tools' => array('array'),
                'display_coursecode_in_courselist' => array('string'),
                'display_teacher_in_courselist' => array('string'),
                'student_view_enabled' => array('string'),
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder)
    {
        $tools = array(
            'course_progress',
            'attendances',
            'notebook',
            'glossary',
            'survey',
            'gradebook',
            'course_description',
            'agenda',
            'documents',
            'learning_path',
            'links',
            'announcements',
            'forums',
            'dropbox',
            'quiz',
            'users',
            'groups',
            'chat',
            'online_conference',
            'student_publications',
            'wiki'
        );

        $builder
            ->add('homepage_view', 'choice', array(
                'choices' => array('activity' => 'activity', 'activity_big' => 'activity_big'))
            )
            ->add('show_tool_shortcuts', 'yes_no')
            ->add('course_create_active_tools', 'choice', array(
                'choices' => $tools,
                'multiple' => true,
                'expanded' => true
            ))
            ->add('display_coursecode_in_courselist', 'yes_no')
            ->add('display_teacher_in_courselist', 'yes_no')
            ->add('student_view_enabled', 'yes_no')
            ->add('go_to_course_after_login', 'yes_no')
            ->add('show_navigation_menu', 'choice', array(
                'choices' => array(
                    'false' => 'No',
                    'icons' => 'Icons',
                    'text' => 'text',
                    'iconstext' => 'iconstext',
                )
            ))
            ->add('enable_tool_introduction', 'yes_no')
            ->add('breadcrumbs_course_homepage', 'choice', array(
                'choices' => array(
                'course_home' => 'Course home',
                    'course_code' => 'Course code',
                    'course_title' => 'Course title',
                    'session_name_and_course_title' => 'Session and course name'
                )
            ))
            ->add('example_material_course_creation', 'yes_no')
            ->add('allow_course_theme', 'yes_no')
            ->add('allow_users_to_create_courses', 'yes_no')
            ->add('show_courses_descriptions_in_catalog', 'yes_no')
            ->add('send_email_to_admin_when_create_course', 'yes_no')
            ->add('allow_user_course_subscription_by_course_admin', 'yes_no')
            ->add('course_validation', 'yes_no')
            ->add('course_validation_terms_and_conditions_url', 'url')
            ->add('course_hide_tools', 'choice', array(
                'choices' => $tools,
                'multiple' => true,
                'expanded' => true
            ))
            ->add('scorm_cumulative_session_time', 'yes_no')
            ->add('courses_default_creation_visibility', 'choice', array(
                'choices' => array(
                    '3' => 'Public',
                    '2' => 'Open',
                    '1' => 'Private',
                    '0' => 'Closed'
                )
            ))
            ->add('allow_public_certificates', 'yes_no')
        ;
    }
}
