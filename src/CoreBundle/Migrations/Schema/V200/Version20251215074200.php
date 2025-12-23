<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251215074200 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'MultiURL: lock selected global settings (access_url_locked = 1).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE settings
            SET access_url_locked = 1
            WHERE variable IN (
                'permissions_for_new_directories',
                'permissions_for_new_files',
                'course_creation_form_set_extra_fields_mandatory',
                'access_url_specific_files',
                'cron_remind_course_finished_activate',
                'cron_remind_course_expiration_frequency',
                'cron_remind_course_expiration_activate',
                'donotlistcampus',
                'server_type',
                'chamilo_database_version',
                'unoconv_binaries',
                'session_admin_access_to_all_users_on_all_urls',
                'split_users_upload_directory',
                'multiple_url_hide_disabled_settings',
                'login_is_email',
                'proxy_settings',
                'login_max_attempt_before_blocking_account',
                'permanently_remove_deleted_files',
                'allow_use_sub_language'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        // Unlock back (sub-URLs editable) for the same list.
        $this->addSql("
            UPDATE settings
            SET access_url_locked = 0
            WHERE variable IN (
                'permissions_for_new_directories',
                'permissions_for_new_files',
                'course_creation_form_set_extra_fields_mandatory',
                'access_url_specific_files',
                'cron_remind_course_finished_activate',
                'cron_remind_course_expiration_frequency',
                'cron_remind_course_expiration_activate',
                'donotlistcampus',
                'server_type',
                'chamilo_database_version',
                'unoconv_binaries',
                'session_admin_access_to_all_users_on_all_urls',
                'split_users_upload_directory',
                'multiple_url_hide_disabled_settings',
                'login_is_email',
                'proxy_settings',
                'login_max_attempt_before_blocking_account',
                'permanently_remove_deleted_files',
                'allow_use_sub_language'
            )
        ");
    }
}
