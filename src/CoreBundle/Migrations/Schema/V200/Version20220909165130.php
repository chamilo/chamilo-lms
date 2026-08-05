<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

final class Version20220909165130 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Widen IP fields to 45 characters, skipping columns already compliant';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $columns = [
            ['track_e_exercises', 'user_ip', 'VARCHAR(45) NOT NULL'],
            ['track_e_course_access', 'user_ip', 'VARCHAR(45) NOT NULL'],
            ['room', 'ip', 'VARCHAR(45) DEFAULT NULL'],
            ['track_e_access', 'user_ip', 'VARCHAR(45) NOT NULL'],
            ['track_e_online', 'user_ip', 'VARCHAR(45) NOT NULL'],
            ['track_e_login', 'user_ip', 'VARCHAR(45) NOT NULL'],
            ['track_e_login_record', 'user_ip', 'VARCHAR(45) NOT NULL'],
            ['c_wiki', 'user_ip', 'VARCHAR(45) NOT NULL'],
        ];

        foreach ($columns as [$table, $column, $definition]) {
            $this->widenColumn($table, $column, $definition, 45);
        }
    }

    public function down(Schema $schema): void
    {
        // Shrinking IP columns can truncate IPv6 values and is intentionally
        // not automated for this production upgrade.
    }

    private function widenColumn(string $table, string $column, string $definition, int $minimumLength): void
    {
        $metadata = $this->connection->fetchAssociative(
            <<<'SQL'
SELECT CHARACTER_MAXIMUM_LENGTH
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = :tableName
  AND COLUMN_NAME = :columnName
LIMIT 1
SQL,
            ['tableName' => $table, 'columnName' => $column]
        );

        if (false === $metadata) {
            $this->getLogger()->info('Skipping missing IP column.', ['table' => $table, 'column' => $column]);

            return;
        }

        if ((int) ($metadata['CHARACTER_MAXIMUM_LENGTH'] ?? 0) >= $minimumLength) {
            $this->getLogger()->info('Skipping IP column already compliant.', ['table' => $table, 'column' => $column]);

            return;
        }

        $sql = \sprintf('ALTER TABLE `%s` MODIFY COLUMN `%s` %s', $table, $column, $definition);
        $this->getLogger()->info('Widening IP column.', ['table' => $table, 'column' => $column]);

        try {
            $this->connection->executeStatement($sql);
        } catch (Throwable $e) {
            throw new RuntimeException(\sprintf('Could not widen %s.%s: %s', $table, $column, $e->getMessage()), 0, $e);
        }
    }
}
