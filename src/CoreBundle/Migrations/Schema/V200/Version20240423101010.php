<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240423101010 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Clean up settings table from settings if those were previously added (during development). Does not affect production environment migrating properly.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            // Adding author_id and setting the foreign key
            $this->addSql('DELETE FROM settings WHERE variable = "announcements_hide_send_to_hrm_users"');
            $this->addSql('DELETE FROM settings WHERE variable = "display_upcoming_events"');
            $this->addSql('DELETE FROM settings WHERE variable = "hide_header_footer"');
            $this->addSql('DELETE FROM settings WHERE variable = "homepage_view"');
            $this->addSql('DELETE FROM settings WHERE variable = "show_tool_shortcuts"');
            $this->addSql('DELETE FROM settings WHERE variable = "enable_wami_record"');
            $this->addSql('DELETE FROM settings WHERE variable = "allow_personal_user_files"');
            $this->addSql('DELETE FROM settings WHERE variable = "if_file_exists_option"');
            $this->addSql('DELETE FROM settings WHERE variable = "math_mimetex"');
            $this->addSql('DELETE FROM settings WHERE variable = "quiz_question_allow_inter_course_linking"');
            $this->addSql('DELETE FROM settings WHERE variable = "gradebook_show_percentage_in_reports"');
            $this->addSql('DELETE FROM settings WHERE variable = "gradebook_enable_best_score"');
            $this->addSql('DELETE FROM settings WHERE variable = "fixed_encoding"');
            $this->addSql('DELETE FROM settings WHERE variable = "hosting_total_size_limit"');
            $this->addSql('DELETE FROM settings WHERE variable = "mail_template_system"');
            $this->addSql('DELETE FROM settings WHERE variable = "cron_notification_mails"');
            $this->addSql('DELETE FROM settings WHERE variable = "enable_message_tags"');
            $this->addSql('DELETE FROM settings WHERE variable = "keep_old_images_after_delete"');
            $this->addSql('DELETE FROM settings WHERE variable = "theme_fallback"');
            $this->addSql('DELETE FROM settings WHERE variable = "sync_db_with_schema"');
            $this->addSql('DELETE FROM settings WHERE variable = "allow_portfolio_tool"');
            $this->addSql('DELETE FROM settings WHERE variable = "session_stored_in_db_as_backup"');
            $this->addSql('DELETE FROM settings WHERE variable = "memcache_server"');
            $this->addSql('DELETE FROM settings WHERE variable = "session_stored_after_n_times"');
            $this->addSql('DELETE FROM settings WHERE variable = "default_template"');
            $this->addSql('DELETE FROM settings WHERE variable = "aspell_bin"');
            $this->addSql('DELETE FROM settings WHERE variable = "aspell_opts"');
            $this->addSql('DELETE FROM settings WHERE variable = "aspell_temp_dir"');
            $this->addSql('DELETE FROM settings WHERE variable = "plugin_settings"');
            $this->addSql('DELETE FROM settings WHERE variable = "is_editable"');
            $this->addSql('DELETE FROM settings WHERE variable = "number_of_upcoming_events"');
            $this->addSql('DELETE FROM settings WHERE variable = "allow_browser_sniffer"');
            $this->addSql('DELETE FROM settings WHERE variable = "session_tutor_reports_visibility"');
            $this->addSql('DELETE FROM settings WHERE variable = "session_page_enabled"');
            $this->addSql('DELETE FROM settings WHERE variable = "allow_session_status"');
            $this->addSql('DELETE FROM settings WHERE variable = "allow_required_survey_questions"');
            $this->addSql('DELETE FROM settings WHERE variable = "allow_survey_availability_datetime"');
            $this->addSql('DELETE FROM settings WHERE variable = "survey_question_dependency"');
            $this->addSql('DELETE FROM settings WHERE variable = "allow_mandatory_survey"');
            $this->addSql('DELETE FROM settings WHERE variable = "allow_survey_tool_in_lp"');
            $this->addSql('DELETE FROM settings WHERE variable = "decode_utf8"');
            $this->addSql('DELETE FROM settings WHERE variable = "admin_chamilo_announcements_disable"');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            // There is no reversion because this is a clean-up of issues fixed later during development
        }
    }
}
