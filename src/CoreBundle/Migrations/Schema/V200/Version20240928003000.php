<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240928003000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add new fields to session and c_lp tables for handling reinscription and session repetition logic, and insert new settings if not exist.';
    }

    public function up(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Add 'new_subscription_session_id' to the 'session_rel_user' table
        if ($schemaManager->tablesExist('session_rel_user')) {
            $sessionRelUserTable = $schemaManager->listTableColumns('session_rel_user');

            if (!isset($sessionRelUserTable['new_subscription_session_id'])) {
                $this->addSql('ALTER TABLE session_rel_user ADD new_subscription_session_id INT DEFAULT NULL');
            }
        }

        // Add fields to the 'session' table
        if ($schemaManager->tablesExist('session')) {
            $sessionTable = $schemaManager->listTableColumns('session');

            if (!isset($sessionTable['parent_id'])) {
                $this->addSql('ALTER TABLE session ADD parent_id INT DEFAULT NULL');
            }
            if (!isset($sessionTable['days_to_reinscription'])) {
                $this->addSql('ALTER TABLE session ADD days_to_reinscription INT DEFAULT NULL');
            }
            if (!isset($sessionTable['last_repetition'])) {
                $this->addSql('ALTER TABLE session ADD last_repetition TINYINT(1) DEFAULT 0 NOT NULL');
            }
            if (!isset($sessionTable['days_to_new_repetition'])) {
                $this->addSql('ALTER TABLE session ADD days_to_new_repetition INT DEFAULT NULL');
            }
        }

        // Add 'validity_in_days' to the 'session' table
        if ($schemaManager->tablesExist('session')) {
            $sessionTable = $schemaManager->listTableColumns('session');

            if (!isset($sessionTable['validity_in_days'])) {
                $this->addSql('ALTER TABLE session ADD validity_in_days INT DEFAULT NULL');
            }
        }

        // Remove 'validity_in_days' from the 'c_lp' table
        if ($schemaManager->tablesExist('c_lp')) {
            $clpTable = $schemaManager->listTableColumns('c_lp');

            if (isset($clpTable['validity_in_days'])) {
                $this->addSql('ALTER TABLE c_lp DROP COLUMN validity_in_days');
            }
        }

        // Insert new settings if not exist
        $this->addSql("
            INSERT INTO settings (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked)
            SELECT 'enable_auto_reinscription', NULL, NULL, 'session', '0', 'Enable Auto Reinscription', 'Allow users to be automatically reinscribed in new sessions.', '', NULL, 1, 1, 1
            WHERE NOT EXISTS (
                SELECT 1 FROM settings WHERE variable = 'enable_auto_reinscription'
            )
        ");

        $this->addSql("
            INSERT INTO settings (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked)
            SELECT 'enable_session_replication', NULL, NULL, 'session', '0', 'Enable Session Replication', 'Allow replication of session data across instances.', '', NULL, 1, 1, 1
            WHERE NOT EXISTS (
                SELECT 1 FROM settings WHERE variable = 'enable_session_replication'
            )
        ");
    }

    public function down(Schema $schema): void
    {
        $schemaManager = $this->connection->createSchemaManager();

        // Revert 'new_subscription_session_id' in the 'session_rel_user' table
        if ($schemaManager->tablesExist('session_rel_user')) {
            $sessionRelUserTable = $schemaManager->listTableColumns('session_rel_user');

            if (isset($sessionRelUserTable['new_subscription_session_id'])) {
                $this->addSql('ALTER TABLE session_rel_user DROP COLUMN new_subscription_session_id');
            }
        }

        // Revert changes in the 'session' table
        if ($schemaManager->tablesExist('session')) {
            $sessionTable = $schemaManager->listTableColumns('session');

            if (isset($sessionTable['parent_id'])) {
                $this->addSql('ALTER TABLE session DROP COLUMN parent_id');
            }
            if (isset($sessionTable['days_to_reinscription'])) {
                $this->addSql('ALTER TABLE session DROP COLUMN days_to_reinscription');
            }
            if (isset($sessionTable['last_repetition'])) {
                $this->addSql('ALTER TABLE session DROP COLUMN last_repetition');
            }
            if (isset($sessionTable['days_to_new_repetition'])) {
                $this->addSql('ALTER TABLE session DROP COLUMN days_to_new_repetition');
            }
        }

        // Revert 'validity_in_days' in the 'session' table
        if ($schemaManager->tablesExist('session')) {
            $sessionTable = $schemaManager->listTableColumns('session');

            if (isset($sessionTable['validity_in_days'])) {
                $this->addSql('ALTER TABLE session DROP COLUMN validity_in_days');
            }
        }

        // Re-add 'validity_in_days' to the 'c_lp' table
        if ($schemaManager->tablesExist('c_lp')) {
            $clpTable = $schemaManager->listTableColumns('c_lp');

            if (!isset($clpTable['validity_in_days'])) {
                $this->addSql('ALTER TABLE c_lp ADD validity_in_days INT DEFAULT NULL');
            }
        }

        // Remove settings
        $this->addSql("DELETE FROM settings WHERE variable = 'enable_auto_reinscription'");
        $this->addSql("DELETE FROM settings WHERE variable = 'enable_session_replication'");
    }
}
