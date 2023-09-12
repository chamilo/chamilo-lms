<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20230216122900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate configuration values to settings_current';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $configurationValues = [
            'Session' => [
                'allow_redirect_to_session_after_inscription_about',
                'remove_session_url',
                'session_list_show_count_users',
                'session_admins_access_all_content',
                'session_admins_edit_courses_content',
                'limit_session_admin_list_users',
                'hide_search_form_in_session_list',
                'allow_delete_user_for_session_admin',
                'allow_disable_user_for_session_admin',
                'session_multiple_subscription_students_list_avoid_emptying',
                'hide_reporting_session_list',
                'allow_session_admin_read_careers',
                'session_list_order',
                'allow_user_session_collapsable',
                'allow_session_admin_login_as_teacher',
                'catalog_course_subscription_in_user_s_session',
                'default_session_list_view',
                'session_automatic_creation_user_id',
                'user_s_session_duration',
                'my_courses_session_order',
                'session_courses_read_only_mode',
                'session_import_settings',
                'catalog_settings',
                'allow_session_status',
                'tracking_columns',
                'my_progress_session_show_all_courses',
                'assignment_base_course_teacher_access_to_all_session',
                'allow_session_admin_extra_access',
                'hide_session_graph_in_my_progress',
                'show_users_in_active_sessions_in_tracking',
                'session_coach_access_after_duration_end',
                'session_course_users_subscription_limited_to_session_users',
                'session_classes_tab_disable',
                'email_template_subscription_to_session_confirmation_username',
                'email_template_subscription_to_session_confirmation_lost_password',
                'session_creation_user_course_extra_field_relation_to_prefill',
                'session_creation_form_set_extra_fields_mandatory',
            ],
            'Security' => [
                'allow_online_users_by_status',
                'password_requirements',
                'security_strict_transport',
                'security_content_policy',
                'security_content_policy_report_only',
                'security_public_key_pins',
                'security_public_key_pins_report_only',
                'security_x_frame_options',
                'security_xss_protection',
                'security_x_content_type_options',
                'security_referrer_policy',
                'security_block_inactive_users_immediately',
                'security_session_cookie_samesite_none',
            ],
            'Course' => [
                'view_grid_courses',
                'show_simple_session_info',
                'my_courses_show_courses_in_user_language_only',
                'allow_public_course_with_no_terms_conditions',
                'show_all_sessions_on_my_course_page',
                'disabled_edit_session_coaches_course_editing_course',
                'allow_base_course_category',
                'hide_course_sidebar',
                'allow_course_extra_field_in_catalog',
                'multiple_access_url_show_shared_course_marker',
                'course_category_code_to_use_as_model',
                'enable_unsubscribe_button_on_my_course_page',
                'course_creation_donate_message_show',
                'course_creation_donate_link',
                'courses_list_session_title_link',
                'hide_course_rating',
                'course_log_hide_columns',
                'course_student_info',
                'course_catalog_settings',
                'resource_sequence_show_dependency_in_course_intro',
                'block_registered_users_access_to_open_course_contents',
                'course_catalog_display_in_home',
                'course_creation_form_set_course_category_mandatory',
                'course_creation_form_hide_course_code',
                'course_about_teacher_name_hide',
                'course_visibility_change_only_admin',
                'catalog_hide_public_link',
                'course_log_default_extra_fields',
                'show_courses_in_catalogue',
                'courses_catalogue_show_only_category',
                'course_creation_by_teacher_extra_fields_to_show',
                'course_creation_form_set_extra_fields_mandatory',
                'course_configuration_tool_extra_fields_to_show_and_edit',
                'course_creation_user_course_extra_field_relation_to_prefill',
            ],
            'Language' => [
                'show_language_selector_in_menu',
                'language_flags_by_country',
                'allow_course_multiple_languages',
                'template_activate_language_filter',
            ],
            'Platform' => [
                'table_row_list',
                'video_features',
                'proxy_settings',
                'theme_fallback',
                'unoconv_binaries',
                'packager',
                'sync_db_with_schema',
                'hide_main_navigation_menu',
                'pdf_img_dpi',
                'tracking_skip_generic_data',
                'hide_complete_name_in_whoisonline',
                'table_default_row',
                'allow_double_validation_in_registration',
                'block_my_progress_page',
                'generate_random_login',
                'timepicker_increment',
                'allow_portfolio_tool',
                'session_stored_in_db_as_backup',
                'memcache_server',
                'session_stored_after_n_times',
                'default_template',
                'aspell_bin',
                'aspell_opts',
                'aspell_temp_dir',
                'webservice_return_user_field',
                'multiple_url_hide_disabled_settings',
                'login_max_attempt_before_blocking_account',
                'force_renew_password_at_first_login',
                'hide_breadcrumb_if_not_allowed',
                'extldap_config',
                'update_student_expiration_x_date',
                'user_status_show_options_enabled',
                'user_status_show_option',
                'user_number_of_days_for_default_expiration_date_per_role',
                'user_edition_extra_field_to_check',
                'user_hide_never_expire_option',
                'platform_logo_url',
                'use_career_external_id_as_identifier_in_diagrams',
                'disable_webservices',
                'webservice_enable_adminonly_api',
                'plugin_settings',
                'allow_working_time_edition',
                'ticket_project_user_roles',
                'disable_user_conditions_sender_id',
                'portfolio_advanced_sharing',
                'redirect_index_to_url_for_logged_users',
            ],
            'Profile' => [
                'linkedin_organization_id',
                'career_diagram_disclaimer',
                'career_diagram_legend',
                'allow_career_diagram',
                'hide_username_with_complete_name',
                'disable_change_user_visibility_for_public_courses',
                'my_space_users_items_per_page',
                'add_user_course_information_in_mailto',
                'pass_reminder_custom_link',
                'registration_add_helptext_for_2_names',
                'disable_gdpr',
                'data_protection_officer_name',
                'data_protection_officer_role',
                'data_protection_officer_email',
                'hide_user_field_from_list',
                'send_notification_when_user_added',
                'show_conditions_to_user',
                'allow_teachers_to_classes',
                'profile_fields_visibility',
                'user_import_settings',
                'user_search_on_extra_fields',
                'allow_career_users',
                'community_managers_user_list',
                'allow_social_map_fields',
                'hide_username_in_course_chat',
            ],
            'Admin' => [
                'user_status_option_only_for_admin_enabled',
                'show_link_request_hrm_user',
                'max_anonymous_users',
                'send_inscription_notification_to_general_admin_only',
                'plugin_redirection_enabled',
                'usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe',
                'usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe',
                'usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe',
                'drh_allow_access_to_all_students',
            ],
            'Agenda' => [
                'personal_agenda_show_all_session_events',
                'allow_agenda_edit_for_hrm',
                'agenda_legend',
                'agenda_colors',
                'agenda_on_hover_info',
                'personal_calendar_show_sessions_occupation',
                'agenda_collective_invitations',
                'agenda_event_subscriptions',
                'agenda_reminders',
                'agenda_reminders_sender_id',
                'fullcalendar_settings',
            ],
            'Lp' => [
                'add_all_files_in_lp_export',
                'show_prerequisite_as_blocked',
                'hide_lp_time',
                'lp_category_accordion',
                'lp_view_accordion',
                'disable_js_in_lp_view',
                'allow_teachers_to_access_blocked_lp_by_prerequisite',
                'allow_lp_chamilo_export',
                'hide_accessibility_label_on_lp_item',
                'lp_minimum_time',
                'validate_lp_prerequisite_from_other_session',
                'show_hidden_exercise_added_to_lp',
                'lp_menu_location',
                'lp_score_as_progress_enable',
                'lp_prevents_beforeunload',
                'disable_my_lps_page',
                'scorm_api_username_as_student_id',
                'scorm_api_extrafield_to_use_as_student_id',
                'allow_import_scorm_package_in_course_builder',
                'allow_htaccess_import_from_scorm',
                'allow_session_lp_category',
                'ticket_lp_quiz_info_add',
                'lp_subscription_settings',
                'lp_view_settings',
                'download_files_after_all_lp_finished',
                'allow_lp_subscription_to_usergroups',
                'lp_fixed_encoding',
                'lp_prerequisite_use_last_attempt_only',
                'show_invisible_exercise_in_lp_list',
                'force_edit_exercise_in_lp',
                'student_follow_page_add_LP_subscription_info',
                'lp_show_max_progress_instead_of_average',
                'lp_show_max_progress_or_average_enable_course_level_redefinition',
                'lp_allow_export_to_students',
                'show_invisible_lp_in_course_home',
                'lp_start_and_end_date_visible_in_student_view',
                'scorm_lms_update_sco_status_all_time',
                'scorm_upload_from_cache',
                'lp_prerequisit_on_quiz_unblock_if_max_attempt_reached',
                'student_follow_page_hide_lp_tests_average',
                'student_follow_page_add_LP_acquisition_info',
                'student_follow_page_add_LP_invisible_checkbox',
                'student_follow_page_include_not_subscribed_lp_students',
                'my_progress_course_tools_order',
                'lp_enable_flow',
                'lp_item_prerequisite_dates',
            ],
            'Gradebook' => [
                'gradebook_enable_best_score',
                'gradebook_hide_graph',
                'gradebook_hide_pdf_report_button',
                'hide_gradebook_percentage_user_result',
                'gradebook_use_exercise_score_settings_in_categories',
                'gradebook_use_apcu_cache',
                'gradebook_report_score_style',
                'gradebook_score_display_custom_standalone',
                'gradebook_use_exercise_score_settings_in_total',
                'gradebook_dependency',
                'gradebook_dependency_mandatory_courses',
                'gradebook_badge_sidebar',
                'gradebook_multiple_evaluation_attempts',
                'allow_gradebook_stats',
                'gradebook_flatview_extrafields_columns',
                'gradebook_pdf_export_settings',
                'allow_gradebook_comments',
                'gradebook_display_extra_stats',
                'gradebook_hide_table',
                'gradebook_hide_link_to_item_for_student',
                'gradebook_enable_subcategory_skills_independant_assignement',
            ],
            'Exercise' => [
                'block_quiz_mail_notification_general_coach',
                'allow_quiz_question_feedback',
                'allow_quiz_show_previous_button_setting',
                'allow_teacher_comment_audio',
                'quiz_prevent_copy_paste',
                'quiz_show_description_on_results_page',
                'quiz_generate_certificate_ending',
                'quiz_open_question_decimal_score',
                'quiz_check_button_enable',
                'allow_notification_setting_per_exercise',
                'hide_free_question_score',
                'hide_user_info_in_quiz_result',
                'exercise_attempts_report_show_username',
                'allow_exercise_auto_launch',
                'disable_clean_exercise_results_for_teachers',
                'show_exercise_question_certainty_ribbon_result',
                'quiz_results_answers_report',
                'send_score_in_exam_notification_mail_to_manager',
                'show_exercise_expected_choice',
                'exercise_hide_label',
                'exercise_category_round_score_in_export',
                'exercises_disable_new_attempts',
                'show_question_id',
                'show_question_pagination',
                'question_pagination_length',
                'limit_exercise_teacher_access',
                'block_category_questions',
                'exercise_score_format',
                'exercise_additional_teacher_modify_actions',
                'quiz_confirm_saved_answers',
                'allow_exercise_categories',
                'allow_quiz_results_page_config',
                'quiz_image_zoom',
                'quiz_answer_extra_recording',
                'allow_mandatory_question_in_category',
                'add_exercise_best_attempt_in_report',
                'exercise_category_report_user_extra_fields',
                'score_grade_model',
                'allow_time_per_question',
                'my_courses_show_pending_exercise_attempts',
                'allow_quick_question_description_popup',
                'exercise_hide_ip',
                'tracking_my_progress_show_deleted_exercises',
                'show_exercise_attempts_in_all_user_sessions',
                'show_exercise_session_attempts_in_base_course',
                'quiz_check_all_answers_before_end_test',
                'quiz_discard_orphan_in_course_export',
                'exercise_result_end_text_html_strict_filtering',
                'question_exercise_html_strict_filtering',
                'quiz_question_delete_automatically_when_deleting_exercise',
                'quiz_question_allow_inter_course_linking',
                'quiz_hide_attempts_table_on_start_page',
                'quiz_hide_question_number',
                'quiz_keep_alive_ping_interval',
            ],
            'Glossary' => [
                'default_glossary_view',
                'allow_remove_tags_in_glossary_export',
            ],
            'Forum' => [
                'global_forums_course_id',
                'hide_forum_post_revision_language',
                'allow_forum_post_revisions',
                'forum_fold_categories',
                'allow_forum_category_language_filter',
                'subscribe_users_to_forum_notifications_also_in_base_course',
            ],
            'Message' => [
                'private_messages_about_user',
                'private_messages_about_user_visible_to_user',
                'social_enable_messages_feedback',
                'disable_dislike_option',
                'enable_message_tags',
                'allow_user_message_tracking',
                'filter_interactivity_messages',
            ],
            'Display' => [
                'hide_social_media_links',
            ],
            'Social' => [
                'social_show_language_flag_in_profile',
                'social_make_teachers_friend_all',
            ],
            'Editor' => [
                'save_titles_as_html',
                'full_ckeditor_toolbar_set',
                'ck_editor_block_image_copy_paste',
                'translate_html',
                'editor_driver_list',
                'enable_uploadimage_editor',
                'editor_settings',
                'video_context_menu_hidden',
                'video_player_renderers',
            ],
            'Chat' => [
                'hide_chat_video',
                'course_chat_restrict_to_coach',
            ],
            'Survey' => [
                'allow_required_survey_questions',
                'hide_survey_reporting_button',
                'allow_survey_availability_datetime',
                'survey_mark_question_as_required',
                'survey_anonymous_show_answered',
                'survey_question_dependency',
                'survey_allow_answered_question_edit',
                'survey_duplicate_order_by_name',
                'survey_backwards_enable',
                'allow_mandatory_survey',
                'hide_survey_edition',
                'survey_additional_teacher_modify_actions',
                'allow_survey_tool_in_lp',
                'show_surveys_base_in_sessions',
            ],
            'Document' => [
                'send_notification_when_document_added',
                'thematic_pdf_orientation',
                'certificate_pdf_orientation',
                'allow_general_certificate',
                'group_document_access',
                'group_category_document_access',
                'allow_compilatio_tool',
                'compilatio_tool',
                'documents_hide_download_icon',
                'enable_x_sendfile_headers',
                'documents_custom_cloud_link_list',
            ],
            'Announcement' => [
                'disable_delete_all_announcements',
                'admin_chamilo_announcements_disable',
                'disable_announcement_attachment',
                'allow_scheduled_announcements',
                'hide_announcement_sent_to_users_info',
                'send_all_emails_to',
                'allow_careers_in_global_announcements',
                'announcements_hide_send_to_hrm_users',
                'allow_coach_to_edit_announcements',
                'course_announcement_scheduled_by_date',
            ],
            'Skill' => [
                'allow_private_skills',
                'allow_teacher_access_student_skills',
                'skills_teachers_can_assign_skills',
                'hide_skill_levels',
                'table_of_hierarchical_skill_presentation',
                'skill_levels_names',
                'allow_skill_rel_items',
            ],
            'Mail' => [
                'update_users_email_to_dummy_except_admins',
                'hosting_total_size_limit',
                'mail_header_style',
                'mail_content_style',
                'allow_email_editor_for_anonymous',
                'messages_hide_mail_content',
                'send_two_inscription_confirmation_mail',
                'show_user_email_in_notification',
                'send_notification_score_in_percentage',
                'mail_template_system',
                'cron_notification_mails',
                'cron_notification_help_desk',
                'notifications_extended_footer_message',
            ],
            'Work' => [
                'block_student_publication_edition',
                'block_student_publication_add_documents',
                'block_student_publication_score_edition',
                'allow_only_one_student_publication_per_user',
                'allow_my_student_publication_page',
                'assignment_prevent_duplicate_upload',
                'considered_working_time',
                'force_download_doc_before_upload_work',
                'allow_redirect_to_main_page_after_work_upload',
                'my_courses_show_pending_work',
            ],
            'Wiki' => [
                'wiki_categories_enabled',
                'wiki_html_strict_filtering',
            ],
            'Certificate' => [
                'hide_my_certificate_link',
                'add_certificate_pdf_footer',
            ],
            'Attendance' => [
                'enable_sign_attendance_sheet',
                'attendance_calendar_set_duration',
                'attendance_allow_comments',
            ],
            'Registration' => [
                'required_extra_fields_in_inscription',
                'allow_fields_inscription',
                'send_inscription_msg_to_inbox',
            ],
        ];
        foreach ($configurationValues as $category => $variables) {
            foreach ($variables as $variable) {
                $category = strtolower($category);
                $result = $connection
                    ->executeQuery(
                        "SELECT COUNT(1) FROM settings_current WHERE variable = '$variable' AND category = '{$category}'"
                    )
                ;
                $count = $result->fetchNumeric()[0];
                $selectedValue = $this->getConfigurationSelectedValue($variable);
                error_log('Migration: Setting variable '.$variable.' category '.$category.' value '.$selectedValue);

                // To use by default courses page if this option is not empty.
                if ('redirect_index_to_url_for_logged_users' === $variable && !empty($selectedValue)) {
                    $selectedValue = 'courses';
                }
                if (empty($count)) {
                    $this->addSql(
                        "INSERT INTO settings_current (access_url, variable, category, selected_value, title, access_url_changeable, access_url_locked) VALUES (1, '{$variable}', '{$category}', '{$selectedValue}', '{$variable}', 1, 1)"
                    );
                } else {
                    $this->addSql(
                        "UPDATE settings_current SET selected_value = '{$selectedValue}', category = '{$category}' WHERE variable = '$variable' AND category = '{$category}'"
                    );
                }
            }
        }

        // Rename setting for hierarchical skill presentation.
        $this->addSql(
            "UPDATE settings_current SET variable = 'skills_hierarchical_view_in_user_tracking', title = 'skills_hierarchical_view_in_user_tracking' WHERE variable = 'table_of_hierarchical_skill_presentation'"
        );

        // Insert extra fields required.
        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'session_courses_read_only_mode' AND item_type = 2 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (2, 13, 'session_courses_read_only_mode', 'Lock Course In Session', 1, 1, 1, NOW())"
            );
        }

        // Insert extra fields required.
        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'is_mandatory' AND item_type = 12 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (12, 13, 'is_mandatory', 'IsMandatory', 1, 1, 1, NOW())"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'show_in_catalogue' AND item_type = 2 AND value_type = 3"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (2, 3, 'show_in_catalogue', 'Show in catalogue', 1, 1, 0, NOW())"
            );
            $this->addSql(
                'SET @ef_id = LAST_INSERT_ID()'
            );
            $this->addSql(
                "INSERT INTO extra_field_options (field_id, option_value, display_text, priority, priority_message, option_order) VALUES (@ef_id, '1', 'Yes', NULL, NULL, 1), (@ef_id, '0', 'No', NULL, NULL, 2)"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'multiple_language' AND item_type = 2 AND value_type = 5"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (2, 5, 'multiple_language', 'Multiple Language', 1, 1, 1, NOW())"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'send_notification_at_a_specific_date' AND item_type = 21 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (21, 13, 'send_notification_at_a_specific_date', 'Send notification at a specific date', 1, 1, 1, NOW())"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'date_to_send_notification' AND item_type = 21 AND value_type = 6"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (21, 6, 'date_to_send_notification', 'Date to send notification', 1, 1, 1, NOW())"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'send_to_users_in_session' AND item_type = 21 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (21, 13, 'send_to_users_in_session', 'Send to users in session', 1, 1, 1, NOW())"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'tags' AND item_type = 22 AND value_type = 10"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (22, 10, 'tags', 'Tags', 1, 1, 1, NOW())"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'acquisition' AND item_type = 20 AND value_type = 3"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (20, 3, 'acquisition', 'Acquisition', 1, 1, 0, NOW())"
            );
            $this->addSql(
                'SET @ef_id = LAST_INSERT_ID()'
            );
            $this->addSql(
                "INSERT INTO extra_field_options (field_id, option_value, display_text, priority, priority_message, option_order) VALUES (@ef_id, '1', 'Acquired', NULL, NULL, 1), (@ef_id, '2', 'In the process of acquisition', NULL, NULL, 2), (@ef_id, '3', 'Not acquired', NULL, NULL, 3)"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'invisible' AND item_type = 20 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (20, 13, 'invisible', 'Invisible', 1, 1, 1, NOW())"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'start_date' AND item_type = 7 AND value_type = 7"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (7, 7, 'start_date', 'StartDate', 1, 1, 1, NOW())"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'end_date' AND item_type = 7 AND value_type = 7"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, visible_to_self, changeable, filter, created_at) VALUES (7, 7, 'end_date', 'EndDate', 1, 1, 1, NOW())"
            );
        }
    }

    public function down(Schema $schema): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $configurationValues = [
            'Registration' => [
                'send_inscription_msg_to_inbox',
                'allow_fields_inscription',
                'required_extra_fields_in_inscription',
            ],
            'Attendance' => [
                'attendance_allow_comments',
                'attendance_calendar_set_duration',
                'enable_sign_attendance_sheet',
            ],
            'Certificate' => [
                'add_certificate_pdf_footer',
                'hide_my_certificate_link',
            ],
            'Wiki' => [
                'wiki_html_strict_filtering',
                'wiki_categories_enabled',
            ],
            'Work' => [
                'my_courses_show_pending_work',
                'allow_redirect_to_main_page_after_work_upload',
                'force_download_doc_before_upload_work',
                'considered_working_time',
                'assignment_prevent_duplicate_upload',
                'allow_my_student_publication_page',
                'allow_only_one_student_publication_per_user',
                'block_student_publication_score_edition',
                'block_student_publication_add_documents',
                'block_student_publication_edition',
            ],
            'Mail' => [
                'notifications_extended_footer_message',
                'cron_notification_help_desk',
                'cron_notification_mails',
                'mail_template_system',
                'send_notification_score_in_percentage',
                'show_user_email_in_notification',
                'send_two_inscription_confirmation_mail',
                'send_inscription_msg_to_inbox',
                'messages_hide_mail_content',
                'allow_email_editor_for_anonymous',
                'mail_content_style',
                'mail_header_style',
                'hosting_total_size_limit',
                'update_users_email_to_dummy_except_admins',
            ],
            'Skill' => [
                'allow_skill_rel_items',
                'skill_levels_names',
                'table_of_hierarchical_skill_presentation',
                'hide_skill_levels',
                'skills_teachers_can_assign_skills',
                'allow_teacher_access_student_skills',
                'allow_private_skills',
            ],
            'Announcement' => [
                'course_announcement_scheduled_by_date',
                'allow_coach_to_edit_announcements',
                'announcements_hide_send_to_hrm_users',
                'allow_careers_in_global_announcements',
                'send_all_emails_to',
                'hide_announcement_sent_to_users_info',
                'allow_scheduled_announcements',
                'disable_announcement_attachment',
                'admin_chamilo_announcements_disable',
                'disable_delete_all_announcements',
            ],
            'Document' => [
                'documents_custom_cloud_link_list',
                'enable_x_sendfile_headers',
                'documents_hide_download_icon',
                'compilatio_tool',
                'allow_compilatio_tool',
                'group_category_document_access',
                'group_document_access',
                'allow_general_certificate',
                'certificate_pdf_orientation',
                'thematic_pdf_orientation',
                'send_notification_when_document_added',
            ],
            'Survey' => [
                'show_surveys_base_in_sessions',
                'allow_survey_tool_in_lp',
                'survey_additional_teacher_modify_actions',
                'hide_survey_edition',
                'allow_mandatory_survey',
                'survey_backwards_enable',
                'survey_duplicate_order_by_name',
                'survey_allow_answered_question_edit',
                'survey_question_dependency',
                'survey_anonymous_show_answered',
                'survey_mark_question_as_required',
                'allow_survey_availability_datetime',
                'hide_survey_reporting_button',
                'allow_required_survey_questions',
            ],
            'Chat' => [
                'course_chat_restrict_to_coach',
                'hide_chat_video',
            ],
            'Editor' => [
                'video_player_renderers',
                'video_context_menu_hidden',
                'editor_settings',
                'enable_uploadimage_editor',
                'editor_driver_list',
                'translate_html',
                'ck_editor_block_image_copy_paste',
                'full_ckeditor_toolbar_set',
                'save_titles_as_html',
            ],
            'Social' => [
                'social_make_teachers_friend_all',
                'social_show_language_flag_in_profile',
            ],
            'Display' => [
                'hide_social_media_links',
            ],
            'Message' => [
                'filter_interactivity_messages',
                'allow_user_message_tracking',
                'enable_message_tags',
                'disable_dislike_option',
                'social_enable_messages_feedback',
                'private_messages_about_user_visible_to_user',
                'private_messages_about_user',
            ],
            'Forum' => [
                'subscribe_users_to_forum_notifications_also_in_base_course',
                'allow_forum_category_language_filter',
                'forum_fold_categories',
                'allow_forum_post_revisions',
                'hide_forum_post_revision_language',
                'global_forums_course_id',
            ],
            'Glossary' => [
                'allow_remove_tags_in_glossary_export',
                'default_glossary_view',
            ],
            'Exercise' => [
                'quiz_keep_alive_ping_interval',
                'quiz_hide_question_number',
                'quiz_hide_attempts_table_on_start_page',
                'quiz_question_allow_inter_course_linking',
                'quiz_question_delete_automatically_when_deleting_exercise',
                'question_exercise_html_strict_filtering',
                'exercise_result_end_text_html_strict_filtering',
                'quiz_discard_orphan_in_course_export',
                'quiz_check_all_answers_before_end_test',
                'show_exercise_session_attempts_in_base_course',
                'show_exercise_attempts_in_all_user_sessions',
                'tracking_my_progress_show_deleted_exercises',
                'exercise_hide_ip',
                'allow_quick_question_description_popup',
                'my_courses_show_pending_exercise_attempts',
                'allow_time_per_question',
                'score_grade_model',
                'exercise_category_report_user_extra_fields',
                'add_exercise_best_attempt_in_report',
                'allow_mandatory_question_in_category',
                'quiz_answer_extra_recording',
                'quiz_image_zoom',
                'allow_quiz_results_page_config',
                'allow_exercise_categories',
                'quiz_confirm_saved_answers',
                'exercise_additional_teacher_modify_actions',
                'exercise_score_format',
                'block_category_questions',
                'limit_exercise_teacher_access',
                'question_pagination_length',
                'show_question_pagination',
                'show_question_id',
                'exercises_disable_new_attempts',
                'exercise_category_round_score_in_export',
                'exercise_hide_label',
                'show_exercise_expected_choice',
                'send_score_in_exam_notification_mail_to_manager',
                'quiz_results_answers_report',
                'show_exercise_question_certainty_ribbon_result',
                'disable_clean_exercise_results_for_teachers',
                'allow_exercise_auto_launch',
                'exercise_attempts_report_show_username',
                'hide_user_info_in_quiz_result',
                'hide_free_question_score',
                'allow_notification_setting_per_exercise',
                'quiz_check_button_enable',
                'quiz_open_question_decimal_score',
                'quiz_generate_certificate_ending',
                'quiz_show_description_on_results_page',
                'quiz_prevent_copy_paste',
                'allow_teacher_comment_audio',
                'allow_quiz_show_previous_button_setting',
                'allow_quiz_question_feedback',
                'block_quiz_mail_notification_general_coach',
            ],
            'Gradebook' => [
                'gradebook_enable_subcategory_skills_independant_assignement',
                'gradebook_hide_link_to_item_for_student',
                'gradebook_hide_table',
                'gradebook_display_extra_stats',
                'allow_gradebook_comments',
                'gradebook_pdf_export_settings',
                'gradebook_flatview_extrafields_columns',
                'allow_gradebook_stats',
                'gradebook_multiple_evaluation_attempts',
                'gradebook_badge_sidebar',
                'gradebook_dependency_mandatory_courses',
                'gradebook_dependency',
                'gradebook_use_exercise_score_settings_in_total',
                'gradebook_score_display_custom_standalone',
                'gradebook_report_score_style',
                'gradebook_use_apcu_cache',
                'gradebook_use_exercise_score_settings_in_categories',
                'hide_gradebook_percentage_user_result',
                'gradebook_hide_pdf_report_button',
                'gradebook_hide_graph',
                'gradebook_enable_best_score',
            ],
            'Lp' => [
                'lp_item_prerequisite_dates',
                'lp_enable_flow',
                'my_progress_course_tools_order',
                'student_follow_page_include_not_subscribed_lp_students',
                'student_follow_page_add_LP_invisible_checkbox',
                'student_follow_page_add_LP_acquisition_info',
                'student_follow_page_hide_lp_tests_average',
                'lp_prerequisit_on_quiz_unblock_if_max_attempt_reached',
                'scorm_upload_from_cache',
                'scorm_lms_update_sco_status_all_time',
                'lp_start_and_end_date_visible_in_student_view',
                'show_invisible_lp_in_course_home',
                'lp_allow_export_to_students',
                'lp_show_max_progress_or_average_enable_course_level_redefinition',
                'lp_show_max_progress_instead_of_average',
                'student_follow_page_add_LP_subscription_info',
                'force_edit_exercise_in_lp',
                'show_invisible_exercise_in_lp_list',
                'lp_prerequisite_use_last_attempt_only',
                'lp_fixed_encoding',
                'allow_lp_subscription_to_usergroups',
                'download_files_after_all_lp_finished',
                'lp_view_settings',
                'lp_subscription_settings',
                'ticket_lp_quiz_info_add',
                'allow_session_lp_category',
                'allow_htaccess_import_from_scorm',
                'allow_import_scorm_package_in_course_builder',
                'scorm_api_extrafield_to_use_as_student_id',
                'scorm_api_username_as_student_id',
                'disable_my_lps_page',
                'lp_prevents_beforeunload',
                'lp_score_as_progress_enable',
                'lp_menu_location',
                'show_hidden_exercise_added_to_lp',
                'validate_lp_prerequisite_from_other_session',
                'lp_minimum_time',
                'hide_accessibility_label_on_lp_item',
                'allow_lp_chamilo_export',
                'allow_teachers_to_access_blocked_lp_by_prerequisite',
                'disable_js_in_lp_view',
                'lp_view_accordion',
                'lp_category_accordion',
                'hide_lp_time',
                'show_prerequisite_as_blocked',
                'add_all_files_in_lp_export',
            ],
            'Agenda' => [
                'fullcalendar_settings',
                'agenda_reminders_sender_id',
                'agenda_reminders',
                'agenda_event_subscriptions',
                'agenda_collective_invitations',
                'personal_calendar_show_sessions_occupation',
                'agenda_on_hover_info',
                'agenda_colors',
                'agenda_legend',
                'allow_agenda_edit_for_hrm',
                'personal_agenda_show_all_session_events',
            ],
            'Admin' => [
                'drh_allow_access_to_all_students',
                'usergroup_do_not_unsubscribe_users_from_session_on_session_unsubscribe',
                'usergroup_do_not_unsubscribe_users_from_course_on_course_unsubscribe',
                'usergroup_do_not_unsubscribe_users_from_course_nor_session_on_user_unsubscribe',
                'plugin_redirection_enabled',
                'send_inscription_notification_to_general_admin_only',
                'max_anonymous_users',
                'show_link_request_hrm_user',
                'user_status_option_only_for_admin_enabled',
            ],
            'Profile' => [
                'hide_username_in_course_chat',
                'allow_social_map_fields',
                'community_managers_user_list',
                'allow_career_users',
                'user_search_on_extra_fields',
                'user_import_settings',
                'profile_fields_visibility',
                'allow_teachers_to_classes',
                'show_conditions_to_user',
                'send_notification_when_user_added',
                'hide_user_field_from_list',
                'data_protection_officer_email',
                'data_protection_officer_role',
                'data_protection_officer_name',
                'disable_gdpr',
                'registration_add_helptext_for_2_names',
                'pass_reminder_custom_link',
                'add_user_course_information_in_mailto',
                'my_space_users_items_per_page',
                'disable_change_user_visibility_for_public_courses',
                'hide_username_with_complete_name',
                'allow_career_diagram',
                'career_diagram_legend',
                'career_diagram_disclaimer',
                'linkedin_organization_id',
            ],
            'Platform' => [
                'redirect_index_to_url_for_logged_users',
                'portfolio_advanced_sharing',
                'disable_user_conditions_sender_id',
                'ticket_project_user_roles',
                'allow_working_time_edition',
                'plugin_settings',
                'webservice_enable_adminonly_api',
                'disable_webservices',
                'use_career_external_id_as_identifier_in_diagrams',
                'platform_logo_url',
                'user_hide_never_expire_option',
                'user_edition_extra_field_to_check',
                'user_number_of_days_for_default_expiration_date_per_role',
                'user_status_show_option',
                'user_status_show_options_enabled',
                'update_student_expiration_x_date',
                'extldap_config',
                'hide_breadcrumb_if_not_allowed',
                'force_renew_password_at_first_login',
                'login_max_attempt_before_blocking_account',
                'multiple_url_hide_disabled_settings',
                'webservice_return_user_field',
                'aspell_temp_dir',
                'aspell_opts',
                'aspell_bin',
                'default_template',
                'session_stored_after_n_times',
                'memcache_server',
                'session_stored_in_db_as_backup',
                'allow_portfolio_tool',
                'timepicker_increment',
                'generate_random_login',
                'block_my_progress_page',
                'allow_double_validation_in_registration',
                'table_default_row',
                'hide_complete_name_in_whoisonline',
                'tracking_skip_generic_data',
                'pdf_img_dpi',
                'hide_main_navigation_menu',
                'sync_db_with_schema',
                'packager',
                'unoconv_binaries',
                'theme_fallback',
                'proxy_settings',
                'video_features',
                'table_row_list',
            ],
            'Language' => [
                'template_activate_language_filter',
                'allow_course_multiple_languages',
                'language_flags_by_country',
                'show_language_selector_in_menu',
            ],
            'Course' => [
                'course_creation_user_course_extra_field_relation_to_prefill',
                'course_configuration_tool_extra_fields_to_show_and_edit',
                'course_creation_form_set_extra_fields_mandatory',
                'course_creation_by_teacher_extra_fields_to_show',
                'courses_catalogue_show_only_category',
                'show_courses_in_catalogue',
                'course_log_default_extra_fields',
                'catalog_hide_public_link',
                'course_visibility_change_only_admin',
                'course_about_teacher_name_hide',
                'course_creation_form_hide_course_code',
                'course_creation_form_set_course_category_mandatory',
                'course_catalog_display_in_home',
                'block_registered_users_access_to_open_course_contents',
                'resource_sequence_show_dependency_in_course_intro',
                'course_catalog_settings',
                'course_student_info',
                'course_log_hide_columns',
                'hide_course_rating',
                'courses_list_session_title_link',
                'course_creation_donate_link',
                'course_creation_donate_message_show',
                'enable_unsubscribe_button_on_my_course_page',
                'course_category_code_to_use_as_model',
                'multiple_access_url_show_shared_course_marker',
                'allow_course_extra_field_in_catalog',
                'hide_course_sidebar',
                'allow_base_course_category',
                'disabled_edit_session_coaches_course_editing_course',
                'show_all_sessions_on_my_course_page',
                'allow_public_course_with_no_terms_conditions',
                'my_courses_show_courses_in_user_language_only',
                'show_simple_session_info',
                'view_grid_courses',
            ],
            'Security' => [
                'security_session_cookie_samesite_none',
                'security_block_inactive_users_immediately',
                'security_referrer_policy',
                'security_x_content_type_options',
                'security_xss_protection',
                'security_x_frame_options',
                'security_public_key_pins_report_only',
                'security_public_key_pins',
                'security_content_policy_report_only',
                'security_content_policy',
                'security_strict_transport',
                'password_requirements',
                'allow_online_users_by_status',
            ],
            'Session' => [
                'session_creation_form_set_extra_fields_mandatory',
                'session_creation_user_course_extra_field_relation_to_prefill',
                'email_template_subscription_to_session_confirmation_lost_password',
                'email_template_subscription_to_session_confirmation_username',
                'session_classes_tab_disable',
                'session_course_users_subscription_limited_to_session_users',
                'session_coach_access_after_duration_end',
                'show_users_in_active_sessions_in_tracking',
                'hide_session_graph_in_my_progress',
                'allow_session_admin_extra_access',
                'assignment_base_course_teacher_access_to_all_session',
                'my_progress_session_show_all_courses',
                'tracking_columns',
                'allow_session_status',
                'catalog_settings',
                'session_import_settings',
                'session_courses_read_only_mode',
                'my_courses_session_order',
                'user_s_session_duration',
                'session_automatic_creation_user_id',
                'default_session_list_view',
                'catalog_course_subscription_in_user_s_session',
                'allow_session_admin_login_as_teacher',
                'allow_user_session_collapsable',
                'session_list_order',
                'allow_session_admin_read_careers',
                'hide_reporting_session_list',
                'session_multiple_subscription_students_list_avoid_emptying',
                'allow_disable_user_for_session_admin',
                'allow_delete_user_for_session_admin',
                'hide_search_form_in_session_list',
                'limit_session_admin_list_users',
                'session_admins_edit_courses_content',
                'session_admins_access_all_content',
                'session_list_show_count_users',
                'remove_session_url',
                'allow_redirect_to_session_after_inscription_about',
            ],
        ];
        foreach ($configurationValues as $category => $variables) {
            foreach ($variables as $variable) {
                $result = $connection
                    ->executeQuery(
                        "SELECT COUNT(1) FROM settings_current WHERE variable = '$variable' AND category = '$category'"
                    )
                ;
                $count = $result->fetchNumeric()[0];
                if (!empty($count)) {
                    $this->addSql(
                        "DELETE FROM settings_current WHERE variable = '{$variable}' AND category = '$category'"
                    );
                }
            }
        }

        // Delete extra fields required.

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'end_date' AND item_type = 7 AND value_type = 7"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'end_date' AND item_type = 7 AND value_type = 7"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'start_date' AND item_type = 7 AND value_type = 7"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'start_date' AND item_type = 7 AND value_type = 7"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'invisible' AND item_type = 20 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'invisible' AND item_type = 20 AND value_type = 13"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'acquisition' AND item_type = 20 AND value_type = 3"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'acquisition' AND item_type = 20 AND value_type = 3"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'tags' AND item_type = 22 AND value_type = 10"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'tags' AND item_type = 22 AND value_type = 10"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'multiple_language' AND item_type = 2 AND value_type = 5"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'multiple_language' AND item_type = 2 AND value_type = 5"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'show_in_catalogue' AND item_type = 2 AND value_type = 3"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'show_in_catalogue' AND item_type = 2 AND value_type = 3"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'session_courses_read_only_mode' AND item_type = 2 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'session_courses_read_only_mode' AND item_type = 2 AND value_type = 13"
            );
        }

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM extra_field WHERE variable = 'is_mandatory' AND item_type = 12 AND value_type = 13"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (!empty($count)) {
            $this->addSql(
                "DELETE FROM extra_field WHERE variable = 'is_mandatory' AND item_type = 12 AND value_type = 13"
            );
        }
    }

    public function getConfigurationSelectedValue(string $variable): string
    {
        global $_configuration;
        $container = $this->getContainer();
        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        $oldConfigPath = $rootPath.'/config/configuration.php';
        $configFileLoaded = \in_array($oldConfigPath, get_included_files(), true);
        if (!$configFileLoaded) {
            include_once $oldConfigPath;
        }

        $selectedValue = '';
        $settingValue = $this->getConfigurationValue($variable, $_configuration);
        if (\is_array($settingValue)) {
            $selectedValue = json_encode($settingValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (\is_bool($settingValue)) {
            $selectedValue = var_export($settingValue, true);
        } else {
            $selectedValue = (string) $settingValue;
        }

        return $selectedValue;
    }
}
