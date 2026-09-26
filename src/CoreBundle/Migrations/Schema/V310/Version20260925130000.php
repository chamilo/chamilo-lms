<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260925130000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Make the Doctrine migration metadata table compatible with utf8mb4.';
    }

    public function isTransactional(): bool
    {
        // MySQL/MariaDB ALTER TABLE statements commit independently.
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$this->hasVersionColumn()) {
            return;
        }

        // Doctrine Migrations 3 uses this column as the primary key. TEXT would not match
        // Doctrine's expected metadata schema, so keep it as VARCHAR while reducing it to
        // the standard 191 characters before switching the table to utf8mb4.
        $this->addSql(
            'ALTER TABLE `version` MODIFY `version` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL'
        );
        $this->addSql(
            'ALTER TABLE `version` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    public function down(Schema $schema): void
    {
        if (!$this->hasVersionColumn()) {
            return;
        }

        $this->addSql(
            'ALTER TABLE `version` MODIFY `version` VARCHAR(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL'
        );
        $this->addSql(
            'ALTER TABLE `version` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci'
        );
    }

    private function hasVersionColumn(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['version'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('version');

        return isset($columns['version']);
    }
}
