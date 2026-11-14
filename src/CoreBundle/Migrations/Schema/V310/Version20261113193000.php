<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds an opt-in secret marker to platform settings without changing their values.
 */
final class Version20261113193000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Mark confidential settings for safe configuration diagnostics.';
    }

    public function up(Schema $schema): void
    {
        foreach (['settings', 'settings_current'] as $tableName) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }

            if (!$schema->getTable($tableName)->hasColumn('confidential')) {
                $this->addSql('ALTER TABLE '.$tableName.' ADD confidential TINYINT(1) NOT NULL DEFAULT 0');
            }

            // Values remain unchanged. Avoid overwriting an explicit confidential marker.
            $this->addSql(
                'UPDATE '.$tableName." SET confidential = 1 WHERE LOWER(variable) REGEXP '(email|e_mail|password|passwd|passphrase|secret|token|credential|api_key|apikey|client_secret|private_key|smtp|dsn|dkim|access_key|authorization|auth_key)'",
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Do not remove secret classification if diagnostics have been enabled.
    }
}
