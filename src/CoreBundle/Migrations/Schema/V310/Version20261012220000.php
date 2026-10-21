<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261012220000 extends AbstractMigrationChamilo
{
    private const array UNIQUE_TITLE_COLUMNS = [
        'plugin' => [
            'index' => 'UNIQ_E96E27942B36786B',
            'maxLength' => 255,
            'check' => 'CHK_PLUGIN_TITLE_LENGTH',
        ],
        'session' => [
            'index' => 'title',
            'maxLength' => 150,
            'check' => 'CHK_SESSION_TITLE_LENGTH',
        ],
        'tool' => [
            'index' => 'UNIQ_20F33ED12B36786B',
            'maxLength' => 255,
            'check' => 'CHK_TOOL_TITLE_LENGTH',
        ],
    ];

    public function getDescription(): string
    {
        return 'Convert retained unique title columns to LONGTEXT while preserving their previous length and uniqueness semantics';
    }

    public function up(Schema $schema): void
    {
        foreach (self::UNIQUE_TITLE_COLUMNS as $tableName => $config) {
            $this->convertToLongText(
                $schema,
                $tableName,
                $config['index'],
                $config['maxLength'],
                $config['check']
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::UNIQUE_TITLE_COLUMNS as $tableName => $config) {
            $this->restoreVarchar(
                $schema,
                $tableName,
                $config['index'],
                $config['maxLength'],
                $config['check']
            );
        }
    }

    private function convertToLongText(
        Schema $schema,
        string $tableName,
        string $indexName,
        int $maxLength,
        string $checkName
    ): void {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('title')) {
            return;
        }

        if (!$table->hasIndex($indexName)) {
            throw new RuntimeException(\sprintf('Expected unique index %s on %s.title was not found.', $indexName, $tableName));
        }

        $index = $table->getIndex($indexName);
        if (!$index->isUnique() || ['title'] !== $index->getColumns()) {
            throw new RuntimeException(\sprintf('Index %s on %s does not match the expected unique title index.', $indexName, $tableName));
        }

        $this->assertMaxLength($tableName, $maxLength);

        $this->addSql(\sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $tableName, $indexName));
        $this->addSql(\sprintf('ALTER TABLE `%s` MODIFY title LONGTEXT NOT NULL', $tableName));
        $this->addSql(\sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` CHECK (CHAR_LENGTH(title) <= %d)',
            $tableName,
            $checkName,
            $maxLength
        ));
        $this->addSql(\sprintf(
            'ALTER TABLE `%s` ADD UNIQUE INDEX `%s` (title(%d))',
            $tableName,
            $indexName,
            $maxLength
        ));
    }

    private function restoreVarchar(
        Schema $schema,
        string $tableName,
        string $indexName,
        int $maxLength,
        string $checkName
    ): void {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('title')) {
            return;
        }

        $this->assertMaxLength($tableName, $maxLength);

        $this->addSql(\sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $tableName, $indexName));
        $this->addSql(\sprintf('ALTER TABLE `%s` DROP CONSTRAINT `%s`', $tableName, $checkName));
        $this->addSql(\sprintf('ALTER TABLE `%s` MODIFY title VARCHAR(%d) NOT NULL', $tableName, $maxLength));
        $this->addSql(\sprintf('ALTER TABLE `%s` ADD UNIQUE INDEX `%s` (title)', $tableName, $indexName));
    }

    private function assertMaxLength(string $tableName, int $maxLength): void
    {
        $actualMaxLength = (int) $this->connection->fetchOne(
            \sprintf('SELECT COALESCE(MAX(CHAR_LENGTH(title)), 0) FROM `%s`', $tableName)
        );

        if ($actualMaxLength > $maxLength) {
            throw new RuntimeException(\sprintf('Cannot preserve %s.title semantics with a %d-character unique prefix: existing data reaches %d characters.', $tableName, $maxLength, $actualMaxLength));
        }
    }
}
