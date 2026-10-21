<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261013020000 extends AbstractMigrationChamilo
{
    private const array COLUMNS = [
        ['column' => 'tutor_name', 'oldLength' => 200],
        ['column' => 'department_url', 'oldLength' => 180],
    ];

    public function getDescription(): string
    {
        return 'Normalize retained course VARCHAR columns longer than 50 characters to VARCHAR(255)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course')) {
            return;
        }

        $table = $schema->getTable('course');

        foreach (self::COLUMNS as $config) {
            if (!$table->hasColumn($config['column'])) {
                continue;
            }

            $column = $table->getColumn($config['column']);
            if (255 === $column->getLength()) {
                continue;
            }

            $this->addSql(
                \sprintf(
                    'ALTER TABLE `course` MODIFY `%s` VARCHAR(255) NULL DEFAULT NULL',
                    $config['column']
                )
            );
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('course')) {
            return;
        }

        $table = $schema->getTable('course');

        foreach (self::COLUMNS as $config) {
            if (!$table->hasColumn($config['column'])) {
                continue;
            }

            $this->assertMaxLength($config['column'], $config['oldLength']);

            $this->addSql(
                \sprintf(
                    'ALTER TABLE `course` MODIFY `%s` VARCHAR(%d) NULL DEFAULT NULL',
                    $config['column'],
                    $config['oldLength']
                )
            );
        }
    }

    private function assertMaxLength(string $columnName, int $maxLength): void
    {
        $actualMaxLength = (int) $this->connection->fetchOne(
            \sprintf('SELECT COALESCE(MAX(CHAR_LENGTH(`%s`)), 0) FROM `course`', $columnName)
        );

        if ($actualMaxLength > $maxLength) {
            throw new RuntimeException(\sprintf('Cannot revert course.%s to VARCHAR(%d): existing data reaches %d characters.', $columnName, $maxLength, $actualMaxLength));
        }
    }
}
