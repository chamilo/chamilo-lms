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
            ],
            'Security' => [
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
            ],
            'Language' => [
                'show_language_selector_in_menu',
                'language_flags_by_country',
            ],
            'Platform' => [
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
            ],
            'Profile' => [
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
            ],
            'Admin' => [
                'show_link_request_hrm_user',
                'max_anonymous_users',
                'send_inscription_notification_to_general_admin_only',
                'plugin_redirection_enabled',
            ],
            'Agenda' => [
                'personal_agenda_show_all_session_events',
                'allow_agenda_edit_for_hrm',
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
            ],
            'Message' => [
                'private_messages_about_user',
                'private_messages_about_user_visible_to_user',
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
            ],
            'Chat' => [
                'hide_username_in_course_chat',
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
            ],
            'Document' => [
                'send_notification_when_document_added',
                'thematic_pdf_orientation',
                'certificate_pdf_orientation',
                'allow_general_certificate',
            ],
            'Announcement' => [
                'disable_announcement_attachment',
                'admin_chamilo_announcements_disable',
                'allow_scheduled_announcements',
                'disable_delete_all_announcements',
                'hide_announcement_sent_to_users_info',
            ],
            'Skill' => [
                'allow_private_skills',
                'allow_teacher_access_student_skills',
                'skills_teachers_can_assign_skills',
                'hide_skill_levels',
                'table_of_hierarchical_skill_presentation',
            ],
            'Mail' => [
                'update_users_email_to_dummy_except_admins',
                'hosting_total_size_limit',
                'mail_header_style',
                'mail_content_style',
                'allow_email_editor_for_anonymous',
                'messages_hide_mail_content',
                'send_inscription_msg_to_inbox',
                'allow_user_message_tracking',
                'send_two_inscription_confirmation_mail',
                'show_user_email_in_notification',
                'send_notification_score_in_percentage',
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
            ],
        ];
        foreach ($configurationValues as $category => $variables) {
            foreach ($variables as $variable) {
                $result = $connection
                    ->executeQuery(
                        "SELECT COUNT(1) FROM settings_current WHERE variable = '$variable' AND category = '{$category}'"
                    )
                ;
                $count = $result->fetchNumeric()[0];
                if (empty($count)) {
                    $selectedValue = $this->getConfigurationValue($variable);
                    $this->addSql(
                        "INSERT INTO settings_current (access_url, variable, category, selected_value, title, access_url_changeable, access_url_locked) VALUES (1, '{$variable}', '{$category}', '{$selectedValue}', '{$variable}', 1, 1)"
                    );
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $configurationValues = [
            'Work' => [
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
                'send_notification_score_in_percentage',
                'show_user_email_in_notification',
                'send_two_inscription_confirmation_mail',
                'allow_user_message_tracking',
                'send_inscription_msg_to_inbox',
                'messages_hide_mail_content',
                'allow_email_editor_for_anonymous',
                'mail_content_style',
                'mail_header_style',
                'hosting_total_size_limit',
                'update_users_email_to_dummy_except_admins',
            ],
            'Skill' => [
                'table_of_hierarchical_skill_presentation',
                'hide_skill_levels',
                'skills_teachers_can_assign_skills',
                'allow_teacher_access_student_skills',
                'allow_private_skills',
            ],
            'Announcement' => [
                'hide_announcement_sent_to_users_info',
                'allow_scheduled_announcements',
                'admin_chamilo_announcements_disable',
                'disable_announcement_attachment',
                'disable_delete_all_announcements'
            ],
            'Document' => [
                'allow_general_certificate',
                'certificate_pdf_orientation',
                'thematic_pdf_orientation',
                'send_notification_when_document_added',
            ],
            'Survey' => [
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
                'hide_username_in_course_chat',
            ],
            'Editor' => [
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
                'private_messages_about_user_visible_to_user',
                'private_messages_about_user',
            ],
            'Forum' => [
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
                'allow_agenda_edit_for_hrm',
                'personal_agenda_show_all_session_events',
            ],
            'Admin' => [
                'plugin_redirection_enabled',
                'send_inscription_notification_to_general_admin_only',
                'max_anonymous_users',
                'show_link_request_hrm_user',
            ],
            'Profile' => [
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
            ],
            'Platform' => [
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
            ],
            'Language' => [
                'language_flags_by_country',
                'show_language_selector_in_menu',
            ],
            'Course' => [
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
            ],
            'Session' => [
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
            ]
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
    }

}
