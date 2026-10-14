<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261012200000 extends AbstractMigrationChamilo
{
    /**
     * @var array<string, bool>
     */
    private const array TITLE_COLUMNS = [
        'contact_form_contact_category' => false,
        'plugin_h5p_import_library' => true,
        'plugin_notebook_teacher' => false,
        'plugin_oel_tools_logs' => true,
        'plugin_oel_tools_teachdoc' => false,
        'plugin_user_remote_service' => false,
        'resource_format' => false,
        'sfu_post' => false,
        'toplinks_link' => false,
    ];

    public function getDescription(): string
    {
        return 'Convert remaining non-indexed legacy and plugin title columns to LONGTEXT';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TITLE_COLUMNS as $tableName => $nullable) {
            $this->convertToLongText($schema, $tableName, $nullable);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TITLE_COLUMNS as $tableName => $nullable) {
            $this->restoreVarchar($schema, $tableName, $nullable);
        }
    }

    private function convertToLongText(Schema $schema, string $tableName, bool $nullable): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('title')) {
            return;
        }

        foreach ($table->getIndexes() as $index) {
            if (\in_array('title', $index->getColumns(), true)) {
                throw new RuntimeException(\sprintf('Refusing to convert indexed column %s.title to LONGTEXT.', $tableName));
            }
        }

        $this->addSql(
            \sprintf(
                'ALTER TABLE %s MODIFY title LONGTEXT %s',
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
        if (!$table->hasColumn('title')) {
            return;
        }

        $this->assertMaxLength($tableName, 255);
        $this->addSql(
            \sprintf(
                'ALTER TABLE %s MODIFY title VARCHAR(255) %s',
                $tableName,
                $nullable ? 'DEFAULT NULL' : 'NOT NULL'
            )
        );
    }

    private function assertMaxLength(string $tableName, int $maxLength): void
    {
        $actualMaxLength = (int) $this->connection->fetchOne(
            \sprintf('SELECT COALESCE(MAX(CHAR_LENGTH(title)), 0) FROM %s', $tableName)
        );

        if ($actualMaxLength > $maxLength) {
            throw new RuntimeException(\sprintf('Cannot revert %s.title to VARCHAR(%d): existing data reaches %d characters.', $tableName, $maxLength, $actualMaxLength));
        }
    }
}
