<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SessionSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder
            ->setDefaults(
                [
                    'add_users_by_coach' => 'false',
                    'extend_rights_for_coach' => 'false',
                    'show_session_coach' => 'false',
                    'show_session_data' => 'false',
                    'allow_coach_to_edit_course_session' => 'true',
                    'hide_courses_in_sessions' => 'false',
                    'allow_session_admins_to_manage_all_sessions' => 'false',
                    'allow_teachers_to_create_sessions' => 'false',
                    'prevent_session_admins_to_manage_all_users' => 'false',
                    'session_course_ordering' => 'false',
                    'limit_session_admin_role' => 'false',
                    'allow_tutors_to_assign_students_to_session' => 'false',
                    'drh_can_access_all_session_content' => 'true',
                    'allow_session_course_copy_for_teachers' => 'false',
                    'my_courses_view_by_session' => 'false',
                    'session_days_after_coach_access' => '',
                    'session_days_before_coach_access' => '',
                    'show_session_description' => 'false',
                    'remove_session_url' => 'false',
                    'hide_tab_list' => '',
                    'session_admins_edit_courses_content' => 'false',
                    'allow_session_admin_login_as_teacher' => 'false',
                    'allow_search_diagnostic' => 'false',
                    'allow_redirect_to_session_after_inscription_about' => 'false',
                    'session_list_show_count_users' => 'false',
                    'session_admins_access_all_content' => 'false',
                    'limit_session_admin_list_users' => 'false',
                    'hide_search_form_in_session_list' => 'false',
                    'allow_delete_user_for_session_admin' => 'false',
                    'allow_disable_user_for_session_admin' => 'false',
                    'session_multiple_subscription_students_list_avoid_emptying' => 'false',
                    'hide_reporting_session_list' => 'false',
                    'allow_session_admin_read_careers' => 'false',
                    'session_list_order' => 'false',
                    'allow_user_session_collapsable' => 'false',
                    'default_session_list_view' => 'all',
                    'session_automatic_creation_user_id' => '1',
                    'user_s_session_duration' => '1095',
                    'my_courses_session_order' => '',
                    'session_courses_read_only_mode' => 'false',
                    'session_import_settings' => '',
                    'tracking_columns' => '',
                    'my_progress_session_show_all_courses' => 'false',
                    'assignment_base_course_teacher_access_to_all_session' => 'false',
                    'allow_session_admin_extra_access' => 'false',
                    'hide_session_graph_in_my_progress' => 'false',
                    'show_users_in_active_sessions_in_tracking' => 'false',
                    'session_coach_access_after_duration_end' => 'false',
                    'session_course_users_subscription_limited_to_session_users' => 'false',
                    'session_classes_tab_disable' => 'false',
                    'email_template_subscription_to_session_confirmation_username' => 'false',
                    'email_template_subscription_to_session_confirmation_lost_password' => 'false',
                    'session_creation_user_course_extra_field_relation_to_prefill' => '',
                    'session_creation_form_set_extra_fields_mandatory' => '',
                    'session_model_list_field_ordered_by_id' => 'false',
                    'duplicate_specific_session_content_on_session_copy' => 'false',
                    'enable_auto_reinscription' => 'false',
                    'enable_session_replication' => 'false',
                    'session_list_view_remaining_days' => 'false',
                    'user_session_display_mode' => 'list',
                    'show_simple_session_info' => 'true',
                    'show_all_sessions_on_my_course_page' => 'true',
                    'courses_list_session_title_link' => '1',
                    'allow_edit_tool_visibility_in_session' => 'true',
                    'allow_career_diagram' => 'false',
                    'allow_career_users' => 'false',
                    'career_diagram_legend' => 'false',
                    'career_diagram_disclaimer' => 'false',
                ]
            )
        ;

        $allowedTypes = [
            'add_users_by_coach' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('add_users_by_coach', YesNoType::class)
            ->add('extend_rights_for_coach', YesNoType::class)
            ->add('show_session_coach', YesNoType::class)
            ->add('show_session_data', YesNoType::class)
            ->add('allow_coach_to_edit_course_session', YesNoType::class)
            ->add('hide_courses_in_sessions', YesNoType::class)
            ->add('allow_session_admins_to_manage_all_sessions', YesNoType::class)
            ->add('allow_teachers_to_create_sessions', YesNoType::class)
            ->add('prevent_session_admins_to_manage_all_users', YesNoType::class)
            ->add('session_course_ordering', TextType::class)
            ->add('limit_session_admin_role', YesNoType::class)
            ->add('allow_tutors_to_assign_students_to_session', YesNoType::class)
            ->add('drh_can_access_all_session_content', YesNoType::class)
            ->add('allow_session_course_copy_for_teachers', YesNoType::class)
            ->add('my_courses_view_by_session', YesNoType::class)
            ->add('session_days_after_coach_access')
            ->add('session_days_before_coach_access')
            ->add('show_session_description', YesNoType::class)
            ->add('remove_session_url', YesNoType::class)
            ->add('hide_tab_list')
            ->add('session_admins_edit_courses_content', YesNoType::class)
            ->add('allow_session_admin_login_as_teacher', YesNoType::class)
            ->add('allow_search_diagnostic', YesNoType::class)
            ->add('allow_redirect_to_session_after_inscription_about', YesNoType::class)
            ->add('session_list_show_count_users', YesNoType::class)
            ->add('session_admins_access_all_content', YesNoType::class)
            ->add('limit_session_admin_list_users', YesNoType::class)
            ->add('hide_search_form_in_session_list', YesNoType::class)
            ->add('allow_delete_user_for_session_admin', YesNoType::class)
            ->add('allow_disable_user_for_session_admin', YesNoType::class)
            ->add('session_multiple_subscription_students_list_avoid_emptying', YesNoType::class)
            ->add('hide_reporting_session_list', YesNoType::class)
            ->add('allow_session_admin_read_careers', YesNoType::class)
            ->add('session_list_order', YesNoType::class)
            ->add('allow_user_session_collapsable', YesNoType::class)
            ->add('default_session_list_view', ChoiceType::class, [
                'choices' => [
                    'All' => 'all',
                    'Close' => 'close',
                    'Active' => 'active',
                    'Custom' => 'custom',
                ],
            ])
            ->add('session_automatic_creation_user_id', TextType::class)
            ->add('user_s_session_duration', TextType::class)
            ->add('my_courses_session_order', TextareaType::class)
            ->add('session_courses_read_only_mode', YesNoType::class)
            ->add('session_import_settings', TextareaType::class)
            ->add('tracking_columns', TextareaType::class)
            ->add('my_progress_session_show_all_courses', YesNoType::class)
            ->add('assignment_base_course_teacher_access_to_all_session', YesNoType::class)
            ->add('allow_session_admin_extra_access', YesNoType::class)
            ->add('hide_session_graph_in_my_progress', YesNoType::class)
            ->add('show_users_in_active_sessions_in_tracking', YesNoType::class)
            ->add('session_coach_access_after_duration_end', YesNoType::class)
            ->add('session_course_users_subscription_limited_to_session_users', YesNoType::class)
            ->add('email_template_subscription_to_session_confirmation_username', YesNoType::class)
            ->add('email_template_subscription_to_session_confirmation_lost_password', YesNoType::class)
            ->add('session_creation_user_course_extra_field_relation_to_prefill', TextareaType::class)
            ->add('session_creation_form_set_extra_fields_mandatory', TextareaType::class)
            ->add('session_model_list_field_ordered_by_id', YesNoType::class)
            ->add('duplicate_specific_session_content_on_session_copy', YesNoType::class)
            ->add('session_list_view_remaining_days', YesNoType::class)
            ->add('user_session_display_mode', ChoiceType::class, [
                'choices' => [
                    'Card (visual blocks)' => 'card',
                    'List (classic)' => 'list',
                ],
            ])

            ->add('show_simple_session_info', YesNoType::class)
            ->add('show_all_sessions_on_my_course_page', YesNoType::class)
            ->add('courses_list_session_title_link', ChoiceType::class, [
                'choices' => [
                    'No link' => '0',
                    'Default' => '1',
                    'Link' => '2',
                    'Session link' => '3',
                ],
            ])
            ->add('allow_edit_tool_visibility_in_session', YesNoType::class)
            ->add('allow_career_diagram', YesNoType::class)
            ->add('allow_career_users', YesNoType::class)
            ->add('career_diagram_legend', YesNoType::class)
            ->add('career_diagram_disclaimer', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
