<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261012210000 extends AbstractMigrationChamilo
{
    private const array TITLE_INDEXES = [
        'chat_video' => 'idx_chat_video_title',
        'resource_type' => 'idx_title',
    ];

    public function getDescription(): string
    {
        return 'Convert non-unique indexed title columns to LONGTEXT with prefix indexes';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TITLE_INDEXES as $tableName => $indexName) {
            $this->convertToLongText($schema, $tableName, $indexName);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TITLE_INDEXES as $tableName => $indexName) {
            $this->restoreVarchar($schema, $tableName, $indexName);
        }
    }

    private function convertToLongText(Schema $schema, string $tableName, string $indexName): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('title')) {
            return;
        }

        if (!$table->hasIndex($indexName)) {
            throw new RuntimeException(\sprintf('Expected index %s on %s.title was not found.', $indexName, $tableName));
        }

        $index = $table->getIndex($indexName);
        if ($index->isUnique() || ['title'] !== $index->getColumns()) {
            throw new RuntimeException(\sprintf('Index %s on %s does not match the expected non-unique title index.', $indexName, $tableName));
        }

        $this->addSql(\sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $tableName, $indexName));
        $this->addSql(\sprintf('ALTER TABLE `%s` MODIFY title LONGTEXT NOT NULL', $tableName));
        $this->addSql(\sprintf('ALTER TABLE `%s` ADD INDEX `%s` (title(255))', $tableName, $indexName));
    }

    private function restoreVarchar(Schema $schema, string $tableName, string $indexName): void
    {
        if (!$schema->hasTable($tableName)) {
            return;
        }

        $table = $schema->getTable($tableName);
        if (!$table->hasColumn('title')) {
            return;
        }

        $this->assertMaxLength($tableName, 255);

        $this->addSql(\sprintf('ALTER TABLE `%s` DROP INDEX `%s`', $tableName, $indexName));
        $this->addSql(\sprintf('ALTER TABLE `%s` MODIFY title VARCHAR(255) NOT NULL', $tableName));
        $this->addSql(\sprintf('ALTER TABLE `%s` ADD INDEX `%s` (title)', $tableName, $indexName));
    }

    private function assertMaxLength(string $tableName, int $maxLength): void
    {
        $actualMaxLength = (int) $this->connection->fetchOne(
            \sprintf('SELECT COALESCE(MAX(CHAR_LENGTH(title)), 0) FROM `%s`', $tableName)
        );

        if ($actualMaxLength > $maxLength) {
            throw new RuntimeException(\sprintf('Cannot revert %s.title to VARCHAR(%d): existing data reaches %d characters.', $tableName, $maxLength, $actualMaxLength));
        }
    }
}
