<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20170627122900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'settings_current changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE settings_current CHANGE access_url access_url INT DEFAULT NULL');
        $this->addSql("UPDATE settings_current SET selected_value = 'true' WHERE variable = 'decode_utf8'");

        // Use .env APP_ENV setting to change server type
        //$this->addSql("DELETE FROM settings_current WHERE variable = 'server_type'");

        $table = $schema->getTable('settings_current');
        if (false === $table->hasForeignKey('FK_62F79C3B9436187B')) {
            $this->addSql(
                'ALTER TABLE settings_current ADD CONSTRAINT FK_62F79C3B9436187B FOREIGN KEY (access_url) REFERENCES access_url (id);'
            );
        }
        $this->addSql(
            'ALTER TABLE settings_current CHANGE variable variable VARCHAR(190) NOT NULL, CHANGE subkey subkey VARCHAR(190) DEFAULT NULL, CHANGE selected_value selected_value LONGTEXT DEFAULT NULL;'
        );

        $this->addSql('ALTER TABLE settings_options CHANGE value value VARCHAR(190) DEFAULT NULL');

        $connection = $this->getEntityManager()->getConnection();

        $result = $connection
            ->executeQuery(
                "SELECT COUNT(1) FROM settings_current WHERE variable = 'exercise_invisible_in_session' AND category = 'Session'"
            )
        ;
        $count = $result->fetchNumeric()[0];
        if (empty($count)) {
            $this->addSql(
                "INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('exercise_invisible_in_session',NULL,'radio','Session','false','ExerciseInvisibleInSessionTitle','ExerciseInvisibleInSessionComment','',NULL, 1)"
            );
            $this->addSql(
                "INSERT INTO settings_options (variable, value, display_text) VALUES ('exercise_invisible_in_session','true','Yes')"
            );
            $this->addSql(
                "INSERT INTO settings_options (variable, value, display_text) VALUES ('exercise_invisible_in_session','false','No')"
            );
        }

        $result = $connection->executeQuery(
            "SELECT COUNT(1) FROM settings_current WHERE variable = 'configure_exercise_visibility_in_course' AND category = 'Session'"
        );
        $count = $result->fetchNumeric()[0];

        if (empty($count)) {
            $this->addSql(
                "INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('configure_exercise_visibility_in_course',NULL,'radio','Session','false','ConfigureExerciseVisibilityInCourseTitle','ConfigureExerciseVisibilityInCourseComment','',NULL, 1)"
            );
            $this->addSql(
                "INSERT INTO settings_options (variable, value, display_text) VALUES ('configure_exercise_visibility_in_course','true','Yes')"
            );
            $this->addSql(
                "INSERT INTO settings_options (variable, value, display_text) VALUES ('configure_exercise_visibility_in_course','false','No')"
            );
        }

        // Fixes missing options show_glossary_in_extra_tools
        $this->addSql("DELETE FROM settings_options WHERE variable = 'show_glossary_in_extra_tools'");
        $this->addSql(
            "INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'none', 'None')"
        );
        $this->addSql(
            "INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'exercise', 'Exercise')"
        );
        $this->addSql(
            "INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'lp', 'LearningPath')"
        );
        $this->addSql(
            "INSERT INTO settings_options (variable, value, display_text) VALUES ('show_glossary_in_extra_tools', 'exercise_and_lp', 'ExerciseAndLearningPath')"
        );

        // Update settings variable name
        $settings = [
            'Institution' => 'institution',
            'SiteName' => 'site_name',
            'InstitutionUrl' => 'institution_url',
            'registration' => 'required_profile_fields',
            'profile' => 'changeable_options',
            'timezone_value' => 'timezone',
            'stylesheets' => 'theme',
            'platformLanguage' => 'platform_language',
            'languagePriority1' => 'language_priority_1',
            'languagePriority2' => 'language_priority_2',
            'languagePriority3' => 'language_priority_3',
            'languagePriority4' => 'language_priority_4',
            'gradebook_score_display_coloring' => 'my_display_coloring',
            'document_if_file_exists_option' => 'if_file_exists_option',
            'ProfilingFilterAddingUsers' => 'profiling_filter_adding_users',
            'course_create_active_tools' => 'active_tools_on_create',
            'EmailAdministrator' => 'administrator_email',
            'administratorSurname' => 'administrator_surname',
            'administratorName' => 'administrator_name',
            'administratorTelephone' => 'administrator_phone',
            'registration.soap.php.decode_utf8' => 'decode_utf8',
            'show_toolshortcuts' => 'show_tool_shortcuts',
        ];

        foreach ($settings as $oldSetting => $newSetting) {
            $sql = "UPDATE settings_current SET variable = '{$newSetting}'
                    WHERE variable = '{$oldSetting}'";
            $this->addSql($sql);
        }

        // Update settings category
        $settings = [
            'cookie_warning' => 'platform',
            'donotlistcampus' => 'platform',
            'administrator_email' => 'admin',
            'administrator_surname' => 'admin',
            'administrator_name' => 'admin',
            'administrator_phone' => 'admin',
            'exercise_max_ckeditors_in_page' => 'exercise',
            'allow_hr_skills_management' => 'skill',
            'accessibility_font_resize' => 'display',
            'account_valid_duration' => 'profile',
            'allow_global_chat' => 'chat',
            'allow_lostpassword' => 'registration',
            'allow_registration' => 'registration',
            'allow_registration_as_teacher' => 'registration',
            'allow_skills_tool' => 'skill',
            'allow_students_to_browse_courses' => 'display',
            'allow_terms_conditions' => 'registration',
            'allow_users_to_create_courses' => 'course',
            'auto_detect_language_custom_pages' => 'language',
            'course_validation' => 'course',
            'course_validation_terms_and_conditions_url' => 'course',
            'display_categories_on_homepage' => 'display',
            'display_coursecode_in_courselist' => 'course',
            'display_teacher_in_courselist' => 'course',
            'drh_autosubscribe' => 'registration',
            'drh_page_after_login' => 'registration',
            'enable_help_link' => 'display',
            'example_material_course_creation' => 'course',
            'login_is_email' => 'profile',
            'noreply_email_address' => 'mail',
            'page_after_login' => 'registration',
            'pdf_export_watermark_by_course' => 'document',
            'pdf_export_watermark_enable' => 'document',
            'pdf_export_watermark_text' => 'document',
            'platform_unsubscribe_allowed' => 'registration',
            'send_email_to_admin_when_create_course' => 'course',
            'show_admin_toolbar' => 'display',
            'show_administrator_data' => 'display',
            'show_back_link_on_top_of_tree' => 'display',
            'show_closed_courses' => 'display',
            'show_email_addresses' => 'display',
            'show_empty_course_categories' => 'display',
            'show_full_skill_name_on_skill_wheel' => 'skill',
            'show_hot_courses' => 'display',
            'show_link_bug_notification' => 'display',
            'show_number_of_courses' => 'display',
            'show_teacher_data' => 'display',
            'showonline' => 'display',
            'student_autosubscribe' => 'registration',
            'student_page_after_login' => 'registration',
            'student_view_enabled' => 'course',
            'teacher_autosubscribe' => 'registration',
            'teacher_page_after_login' => 'registration',
            'time_limit_whosonline' => 'display',
            'user_selected_theme' => 'profile',
            'hide_global_announcements_when_not_connected' => 'announcement',
            'hide_home_top_when_connected' => 'display',
            'hide_logout_button' => 'display',
            'institution_address' => 'platform',
            'redirect_admin_to_courses_list' => 'admin',
            'decode_utf8' => 'webservice',
            'use_custom_pages' => 'platform',
            'allow_group_categories' => 'group',
            'allow_user_headings' => 'display',
            'default_document_quotum' => 'document',
            'default_forum_view' => 'forum',
            'default_group_quotum' => 'document',
            'enable_quiz_scenario' => 'exercise',
            'exercise_max_score' => 'exercise',
            'exercise_min_score' => 'exercise',
            'pdf_logo_header' => 'platform',
            'show_glossary_in_documents' => 'document',
            'show_glossary_in_extra_tools' => 'glossary',
            //'show_toolshortcuts' => '',
            'survey_email_sender_noreply' => 'survey',
            'allow_coach_feedback_exercises' => 'exercise',
            'sessionadmin_autosubscribe' => 'registration',
            'sessionadmin_page_after_login' => 'registration',
            'show_tutor_data' => 'display',
            'chamilo_database_version' => 'platform',
            'add_gradebook_certificates_cron_task_enabled' => 'gradebook',
            'icons_mode_svg' => 'display',
            'server_type' => 'platform',
            'show_official_code_whoisonline' => 'profile',
            'show_terms_if_profile_completed' => 'ticket',
            'enable_record_audio' => 'course',
            'add_users_by_coach' => 'session',
            'allow_captcha' => 'security',
            'allow_coach_to_edit_course_session' => 'session',
            'allow_delete_attendance' => 'attendance',
            'allow_download_documents_by_api_key' => 'webservice',
            'allow_email_editor' => 'editor',
            'allow_message_tool' => 'message',
            'allow_send_message_to_all_platform_users' => 'message',
            'allow_personal_agenda' => 'agenda',
            'allow_show_linkedin_url' => 'profile',
            'allow_show_skype_account' => 'profile',
            'allow_social_tool' => 'social',
            'allow_students_to_create_groups_in_social' => 'social',
            'allow_use_sub_language' => 'language',
            'allow_user_course_subscription_by_course_admin' => 'course',
            'allow_users_to_change_email_with_no_password' => 'profile',
            'display_groups_forum_in_general_tool' => 'forum',
            'documents_default_visibility_defined_in_course' => 'document',
            'dropbox_allow_group' => 'dropbox',
            'dropbox_allow_just_upload' => 'dropbox',
            'dropbox_allow_mailing' => 'dropbox',
            'dropbox_allow_overwrite' => 'dropbox',
            'dropbox_allow_student_to_student' => 'dropbox',
            'dropbox_hide_course_coach' => 'dropbox',
            'dropbox_hide_general_coach' => 'dropbox',
            'dropbox_max_filesize' => 'dropbox',
            'email_alert_manager_on_new_quiz' => 'exercise',
            'enable_webcam_clip' => 'document',
            'enabled_support_pixlr' => 'editor',
            'enabled_support_svg' => 'editor',
            'enabled_text2audio' => 'document',
            'extend_rights_for_coach' => 'session',
            'extend_rights_for_coach_on_survey' => 'survey',
            'hide_course_group_if_no_tools_available' => 'group',
            'hide_dltt_markup' => 'language',
            'if_file_exists_option' => 'document',
            'language_priority_1' => 'language',
            'language_priority_2' => 'language',
            'language_priority_3' => 'language',
            'language_priority_4' => 'language',
            'lp_show_reduced_report' => 'course',
            'message_max_upload_filesize' => 'message',
            'messaging_allow_send_push_notification' => 'webservice',
            'messaging_gdc_api_key' => 'webservice',
            'messaging_gdc_project_number' => 'webservice',
            'permanently_remove_deleted_files' => 'document',
            'permissions_for_new_directories' => 'document',
            'permissions_for_new_files' => 'document',
            'platform_language' => 'language',
            'registered' => 'platform',
            'show_chat_folder' => 'chat',
            'show_default_folders' => 'document',
            'show_different_course_language' => 'language',
            'show_documents_preview' => 'document',
            'show_link_ticket_notification' => 'display',
            'show_official_code_exercise_result_list' => 'exercise',
            'show_users_folders' => 'document',
            'split_users_upload_directory' => 'profile',
            'students_download_folders' => 'document',
            'students_export2pdf' => 'document',
            'tool_visible_by_default_at_creation' => 'document',
            'upload_extensions_blacklist' => 'document',
            'upload_extensions_list_type' => 'document',
            'upload_extensions_replace_by' => 'document',
            'upload_extensions_skip' => 'document',
            'upload_extensions_whitelist' => 'document',
            'use_users_timezone' => 'profile',
            'users_copy_files' => 'document',
            'timezone' => 'platform',
            'enable_profile_user_address_geolocalization' => 'profile',
            'theme' => 'platform',
            'exercise_hide_label' => 'exercise',
        ];

        foreach ($settings as $variable => $category) {
            $sql = "UPDATE settings_current SET category = '{$category}'
                    WHERE variable = '{$variable}'";
            $this->addSql($sql);
        }

        // Update settings value
        $settings = [
            'upload_extensions_whitelist' => 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf;webm;oga;ogg;ogv;h264',
        ];

        foreach ($settings as $variable => $value) {
            $sql = "UPDATE settings_current SET selected_value = '{$value}'
                    WHERE variable = '{$variable}'";
            $this->addSql($sql);
        }

        $this->addSql("UPDATE settings_current SET selected_value = ''
                           WHERE variable = 'platform_language' AND selected_value IS NULL");

        // Delete settings
        $settings = [
            'use_session_mode',
            'show_toolshortcuts',
            'show_tabs',
            'display_mini_month_calendar',
            'number_of_upcoming_events',
            'facebook_description',
            'ldap_description',
            'openid_authentication',
            'platform_charset',
            'shibboleth_description',
            'sso_authentication',
            'sso_authentication_domain',
            'sso_authentication_auth_uri',
            'sso_authentication_unauth_uri',
            'sso_authentication_protocol',
            'sso_force_redirect',
            'activate_email_template',
            'sso_authentication_subclass',
        ];

        foreach ($settings as $setting) {
            $sql = "DELETE FROM settings_current WHERE variable = '{$setting}'";
            $this->addSql($sql);
        }

        $this->addSql('UPDATE settings_current SET category = LOWER(category)');

        // ticket configuration
        $ticketProjectUserRoles = $this->getConfigurationValue('ticket_project_user_roles');

        if ($ticketProjectUserRoles && isset($ticketProjectUserRoles['permissions'])) {
            $selectedValue = array_map(
                fn ($projectId, $roles) => "$projectId:".implode(',', $roles),
                array_keys($ticketProjectUserRoles['permissions']),
                array_values($ticketProjectUserRoles['permissions'])
            );

            $selectedValue = implode(PHP_EOL, $selectedValue);

            $this->addSql(
                "INSERT INTO settings_current (access_url, variable, category, selected_value, title, access_url_changeable, access_url_locked) VALUES (1, 'ticket_project_user_roles', 'Ticket', '$selectedValue', 'ticket_project_user_roles', 1, 1)"
            );
        }

        // social configurations
        if ($this->getConfigurationValue('social_enable_messages_feedback')) {
            $this->addSql(
                "INSERT INTO settings_current (access_url, variable, category, selected_value, title, access_url_changeable, access_url_locked) VALUES (1, 'social_enable_messages_feedback', 'Social', 'true', 'social_enable_messages_feedback', 1, 1)"
            );
        }

        if ($this->getConfigurationValue('disable_dislike_option')) {
            $this->addSql(
                "INSERT INTO settings_current (access_url, variable, category, selected_value, title, access_url_changeable, access_url_locked) VALUES (1, 'disable_dislike_option', 'Social', 'true', 'disable_dislike_option', 1, 1)"
            );
        }
    }

    public function down(Schema $schema): void
    {
    }
}
