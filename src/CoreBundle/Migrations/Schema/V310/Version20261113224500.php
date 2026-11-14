<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Correct over-classification introduced by the initial configuration dump migration.
 * Only reviewed boolean switches and numeric limits can lose the marker.
 */
final class Version20261113224500 extends AbstractMigrationChamilo
{
    private const array PUBLIC_BOOLEAN_SETTINGS_BY_CATEGORY = [
        'course' => ['send_email_to_admin_when_create_course'],
        'display' => ['show_email_addresses'],
        'editor' => ['allow_email_editor'],
        'exercise' => ['email_alert_manager_on_new_quiz'],
        'mail' => [
            'allow_email_editor_for_anonymous',
            'enable_email_open_tracking',
            'messages_hide_mail_content',
            'show_user_email_in_notification',
            'update_users_email_to_dummy_except_admins',
        ],
        'profile' => ['allow_users_to_change_email_with_no_password', 'login_is_email'],
        'registration' => ['allow_lostpassword'],
        'security' => ['check_password', 'force_renew_password_at_first_login', 'user_reset_password'],
        'webservice' => ['allow_download_documents_by_api_key'],
    ];

    private const array PUBLIC_INTEGER_SETTINGS_BY_CATEGORY = [
        'platform' => ['hosting_limit_identical_email'],
        'security' => ['password_rotation_days', 'user_reset_password_token_limit'],
    ];

    public function getDescription(): string
    {
        return 'Refine configuration dump confidentiality for safe diagnostic switches.';
    }

    public function up(Schema $schema): void
    {
        foreach (['settings', 'settings_current'] as $tableName) {
            if (!$schema->hasTable($tableName) || !$schema->getTable($tableName)->hasColumn('confidential')) {
                continue;
            }

            foreach (self::PUBLIC_BOOLEAN_SETTINGS_BY_CATEGORY as $category => $variables) {
                $placeholders = implode(', ', array_fill(0, \count($variables), '?'));
                $this->addSql(
                    'UPDATE '.$tableName.' SET confidential = 0'
                    .' WHERE confidential = 1 AND category = ? AND variable IN ('.$placeholders.')'
                    ." AND LOWER(TRIM(selected_value)) IN ('true', 'false', '0', '1')",
                    array_merge([$category], $variables),
                );
            }

            foreach (self::PUBLIC_INTEGER_SETTINGS_BY_CATEGORY as $category => $variables) {
                $placeholders = implode(', ', array_fill(0, \count($variables), '?'));
                $this->addSql(
                    'UPDATE '.$tableName.' SET confidential = 0'
                    .' WHERE confidential = 1 AND category = ? AND variable IN ('.$placeholders.')'
                    ." AND TRIM(selected_value) REGEXP '^[0-9]{1,10}$'",
                    array_merge([$category], $variables),
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Do not overwrite administrator confidentiality decisions on rollback.
    }
}
