<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261005120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Replace course.course_language with a foreign key to language.id';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course') || !$schema->hasTable('language')) {
            throw new RuntimeException('The course and language tables are required.');
        }

        $courseTable = $schema->getTable('course');
        if (!$courseTable->hasColumn('course_language')) {
            if ($courseTable->hasColumn('language_id')) {
                return;
            }

            throw new RuntimeException('The course.course_language column was not found.');
        }

        if ($courseTable->hasColumn('language_id')) {
            throw new RuntimeException('The course table already contains language_id while course_language still exists.');
        }

        $invalidLanguages = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT c.course_language, COUNT(DISTINCT l.id) AS language_matches
            FROM course c
            LEFT JOIN language l ON l.isocode = c.course_language
            GROUP BY c.course_language
            HAVING COUNT(DISTINCT l.id) <> 1
            ORDER BY c.course_language
            SQL);

        if ([] !== $invalidLanguages) {
            $details = array_map(
                static fn (array $row): string => \sprintf(
                    '%s (%d matches)',
                    '' !== (string) ($row['course_language'] ?? '') ? (string) $row['course_language'] : '<empty>',
                    (int) ($row['language_matches'] ?? 0),
                ),
                $invalidLanguages,
            );

            throw new RuntimeException('Cannot migrate course languages because every course language must match exactly one language.isocode: '.implode(', ', $details));
        }

        $this->addSql('ALTER TABLE course ADD language_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE course c
            INNER JOIN language l ON l.isocode = c.course_language
            SET c.language_id = l.id
            SQL);
        $this->addSql('ALTER TABLE course CHANGE language_id language_id INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_169E6FB982F1BAF4 ON course (language_id)');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB982F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
        $this->addSql('ALTER TABLE course DROP course_language');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('course')) {
            return;
        }

        $courseTable = $schema->getTable('course');
        if (!$courseTable->hasColumn('language_id')) {
            return;
        }

        if (!$courseTable->hasColumn('course_language')) {
            $this->addSql('ALTER TABLE course ADD course_language VARCHAR(20) DEFAULT NULL');
            $this->addSql(<<<'SQL'
                UPDATE course c
                INNER JOIN language l ON l.id = c.language_id
                SET c.course_language = l.isocode
                SQL);
            $this->addSql('ALTER TABLE course CHANGE course_language course_language VARCHAR(20) NOT NULL');
        }

        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB982F1BAF4');
        $this->addSql('DROP INDEX IDX_169E6FB982F1BAF4 ON course');
        $this->addSql('ALTER TABLE course DROP language_id');
    }
}
