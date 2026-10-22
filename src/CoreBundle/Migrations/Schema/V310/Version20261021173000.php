<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Repair the incorrectly prefixed settings.variable written by the initial
 * certificate-credential migration. Settings use the category in a separate
 * column; only SettingsManager callers use the category.variable notation.
 */
final class Version20261021173000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Repair the signed-certificate setting name without changing its value';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        // Preserve an already-correct row if one exists for the same URL/subkey.
        // This is safe to execute again and never changes selected_value.
        $this->addSql(<<<'SQL'
UPDATE settings AS invalid_name
LEFT JOIN settings AS existing
    ON existing.variable = 'blockchain_verification_enabled'
    AND existing.category = 'certificate'
    AND existing.access_url <=> invalid_name.access_url
    AND existing.subkey <=> invalid_name.subkey
SET invalid_name.variable = 'blockchain_verification_enabled'
WHERE invalid_name.variable = 'certificate.blockchain_verification_enabled'
  AND invalid_name.category = 'certificate'
  AND existing.id IS NULL
SQL);
    }

    public function down(Schema $schema): void
    {
        // Keep the corrected setting name and configured value.
    }
}
