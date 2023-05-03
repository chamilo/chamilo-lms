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
                    'show_groups_to_users' => 'false',
                    'hide_courses_in_sessions' => 'false',
                    'allow_session_admins_to_manage_all_sessions' => 'false',
                    'session_tutor_reports_visibility' => 'false',
                    'session_page_enabled' => 'true',
                    'allow_teachers_to_create_sessions' => 'false',
                    'prevent_session_admins_to_manage_all_users' => 'false',
                    'session_course_ordering' => 'false',
                    'limit_session_admin_role' => 'false',
                    'allow_tutors_to_assign_students_to_session' => 'false',
                    'drh_can_access_all_session_content' => 'true',
                    'catalog_allow_session_auto_subscription' => 'false',
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
                    'catalog_course_subscription_in_user_s_session' => 'false',
                    'default_session_list_view' => 'all',
                    'session_automatic_creation_user_id' => '1',
                    'user_s_session_duration' => '1095',
                    'my_courses_session_order' => '',
                    'session_courses_read_only_mode' => 'false',
                    'session_import_settings' => '',
                    'catalog_settings' => '',
                    'allow_session_status' => 'false',
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
            ->add(
                'add_users_by_coach',
                YesNoType::class,
                [
                    'label' => 'AddUsersByCoachTitle',
                    'help' => 'AddUsersByCoachComment',
                ]
            )
            ->add('extend_rights_for_coach', YesNoType::class)
            ->add('show_session_coach', YesNoType::class)
            ->add('show_session_data', YesNoType::class)
            ->add('allow_coach_to_edit_course_session', YesNoType::class)
            ->add('show_groups_to_users', YesNoType::class)
            ->add('hide_courses_in_sessions', YesNoType::class)
            ->add('allow_session_admins_to_manage_all_sessions', YesNoType::class)
            ->add('session_tutor_reports_visibility', YesNoType::class)
            ->add('session_page_enabled', YesNoType::class)
            ->add('allow_teachers_to_create_sessions', YesNoType::class)
            ->add('prevent_session_admins_to_manage_all_users', YesNoType::class)
            ->add(
                'session_course_ordering',
                TextType::class,
                [
                    'label' => 'SessionCourseOrderingTitle',
                    'help' => 'SessionCourseOrderingComment',
                ]
            )
            ->add('limit_session_admin_role', YesNoType::class)
            ->add('allow_tutors_to_assign_students_to_session', YesNoType::class)
            ->add('drh_can_access_all_session_content', YesNoType::class)
            ->add('catalog_allow_session_auto_subscription', YesNoType::class)
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
            ->add('catalog_course_subscription_in_user_s_session', YesNoType::class)
            ->add(
                'default_session_list_view',
                ChoiceType::class,
                [
                    'choices' => [
                        'All' => 'all',
                        'Close' => 'close',
                        'Active' => 'active',
                        'Custom' => 'custom',
                    ],
                ]
            )
            ->add('session_automatic_creation_user_id', TextType::class)
            ->add('user_s_session_duration', TextType::class)
            ->add(
                'my_courses_session_order',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('My courses session order. Possible field values: "start_date", "end_date", "name" Order values: "asc" or "desc"').
                        $this->settingArrayHelpValue('my_courses_session_order'),
                ]
            )
            ->add('session_courses_read_only_mode', YesNoType::class)
            ->add(
                'session_import_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('This option sets default parameters in the main/session/session_import.php').
                        $this->settingArrayHelpValue('session_import_settings'),
                ]
            )
            ->add(
                'catalog_settings',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Catalog search settings visibility').
                        $this->settingArrayHelpValue('catalog_settings'),
                ]
            )
            ->add('allow_session_status', YesNoType::class)
            ->add(
                'tracking_columns',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Customize course session tracking columns').
                        $this->settingArrayHelpValue('tracking_columns'),
                ]
            )
            ->add('my_progress_session_show_all_courses', YesNoType::class)
            ->add('assignment_base_course_teacher_access_to_all_session', YesNoType::class)
            ->add('allow_session_admin_extra_access', YesNoType::class)
            ->add('hide_session_graph_in_my_progress', YesNoType::class)
            ->add('show_users_in_active_sessions_in_tracking', YesNoType::class)
            ->add('session_coach_access_after_duration_end', YesNoType::class)
            ->add('session_course_users_subscription_limited_to_session_users', YesNoType::class)
            ->add('email_template_subscription_to_session_confirmation_username', YesNoType::class)
            ->add('email_template_subscription_to_session_confirmation_lost_password', YesNoType::class)
            ->add(
                'session_creation_user_course_extra_field_relation_to_prefill',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Relation to prefill session extra field with user extra field on session creation on main/session/session_add.php').
                        $this->settingArrayHelpValue('session_creation_user_course_extra_field_relation_to_prefill'),
                ]
            )
            ->add(
                'session_creation_form_set_extra_fields_mandatory',
                TextareaType::class,
                [
                    'help_html' => true,
                    'help' => get_lang('Configuration setting to make some extra field required in session creation form on main/session/session_add.php').
                        $this->settingArrayHelpValue('session_creation_form_set_extra_fields_mandatory'),
                ]
            )


        ;
    }

    private function settingArrayHelpValue(string $variable): string
    {
        $values = [
            'my_courses_session_order' => '<pre>
                ["field" => "end_date", "order" => "desc"]
                </pre>',
            'session_import_settings' => "<pre>
                [
                    'options' =>  [
                        'session_exists_default_option' => '1',
                        'send_mail_default_option' => '1',
                    ]
                ]
                </pre>",
            'catalog_settings' => "<pre>
                [
                    'sessions' => [
                        'by_title' => true,
                        'by_date' => true,
                        'by_tag' => true,
                        'show_session_info' => true,
                        'show_session_date' => true,
                    ],
                    'courses' => [
                        'by_title' => true,
                    ],
                ]
                </pre>",
            'tracking_columns' => "<pre>
                [
                    'course_session' => [
                        'course_title' => true,
                        'published_exercises' => true,
                        'new_exercises' => true,
                        'my_average' => true,
                        'average_exercise_result' => true,
                        'time_spent' => true,
                        'lp_progress' => true,
                        'score' => true,
                        'best_score' => true,
                        'last_connection' => true,
                        'details' => true,
                    ],
                    'my_students_lp' => [
                        'lp' => true,
                        'time' => true,
                        'best_score' => true,
                        'latest_attempt_avg_score' => true,
                        'progress' => true,
                        'last_connection' => true,
                    ],
                    'my_progress_lp' => [
                        'lp' => true,
                        'time' => true,
                        'progress' => true,
                        'score' => true,
                        'best_score' => true,
                        'last_connection' => true,
                    ],
                    'my_progress_courses' => [
                        'course_title' => true,
                        'time_spent' => true,
                        'progress' => true,
                        'best_score_in_lp' => true,
                        'best_score_not_in_lp' => true,
                        'latest_login' => true,
                        'details' => true
                    ]
                ]
                </pre>",
            'session_creation_user_course_extra_field_relation_to_prefill' => "<pre>
                [
                    'fields' => [
                        'client' => 'client',
                        'region' => 'region',
                    ]
                ]
                </pre>",
            'session_creation_form_set_extra_fields_mandatory' => "<pre>
                ['fields' => ['client','region']]
                </pre>",
        ];

        $returnValue = [];
        if (isset($values[$variable])) {
            $returnValue = $values[$variable];
        }

        return $returnValue;
    }
}
