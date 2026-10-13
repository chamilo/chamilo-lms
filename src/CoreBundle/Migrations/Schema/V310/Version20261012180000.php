<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261012180000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Convert URL columns named url to LONGTEXT';
    }

    public function up(Schema $schema): void
    {
        $this->convertToLongText($schema, 'access_url', false);
        $this->convertToLongText($schema, 'plugin_buycourses_cecabank_account', false);
        $this->convertToLongText($schema, 'plugin_user_remote_service', false);
        $this->convertToLongText($schema, 'usergroup', true);
        $this->convertToLongText($schema, 'xapi_cmi5_item', true);
    }

    public function down(Schema $schema): void
    {
        $this->restoreVarchar($schema, 'access_url', false);
        $this->restoreVarchar($schema, 'plugin_buycourses_cecabank_account', false);
        $this->restoreVarchar($schema, 'plugin_user_remote_service', false);
        $this->restoreVarchar($schema, 'usergroup', true);
        $this->restoreVarchar($schema, 'xapi_cmi5_item', true);
    }

    private function convertToLongText(Schema $schema, string $tableName, bool $nullable): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('url')) {
            return;
        }

        $this->addSql(
            \sprintf(
                'ALTER TABLE %s MODIFY url LONGTEXT %s',
                $tableName,
                $nullable ? 'DEFAULT NULL' : 'NOT NULL'
            )
        );
    }

    private function restoreVarchar(Schema $schema, string $tableName, bool $nullable): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('url')) {
            return;
        }

        $this->assertMaxLength($tableName, 255);
        $this->addSql(
            \sprintf(
                'ALTER TABLE %s MODIFY url VARCHAR(255) %s',
                $tableName,
                $nullable ? 'DEFAULT NULL' : 'NOT NULL'
            )
        );
    }

    private function assertMaxLength(string $tableName, int $maxLength): void
    {
        $actualMaxLength = (int) $this->connection->fetchOne(
            \sprintf('SELECT COALESCE(MAX(CHAR_LENGTH(url)), 0) FROM %s', $tableName)
        );

        if ($actualMaxLength > $maxLength) {
            throw new RuntimeException(\sprintf('Cannot revert %s.url to VARCHAR(%d): existing data reaches %d characters.', $tableName, $maxLength, $actualMaxLength));
        }
    }
}
