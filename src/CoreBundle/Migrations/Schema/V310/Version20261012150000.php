<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261012150000 extends AbstractMigrationChamilo
{
    private const string FOREIGN_KEY = 'FK_COURSE_REL_CLASS_COURSE';

    public function getDescription(): string
    {
        return 'Replace course_rel_class.course_code with a foreign key to course.id';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course_rel_class')) {
            return;
        }

        $table = $schema->getTable('course_rel_class');
        if (!$table->hasColumn('course_code')) {
            return;
        }

        $orphanCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM course_rel_class crc
             LEFT JOIN course c ON c.code = crc.course_code
             WHERE c.id IS NULL'
        );

        if ($orphanCount > 0) {
            throw new RuntimeException(\sprintf('Cannot migrate course_rel_class: %d row(s) reference a course_code that does not exist in course.code.', $orphanCount));
        }

        if (!$table->hasColumn('c_id')) {
            $this->addSql('ALTER TABLE course_rel_class ADD c_id INT DEFAULT NULL');
        }

        $this->addSql(
            'UPDATE course_rel_class crc
             INNER JOIN course c ON c.code = crc.course_code
             SET crc.c_id = c.id'
        );
        $this->addSql(
            'ALTER TABLE course_rel_class
             DROP PRIMARY KEY,
             DROP COLUMN course_code,
             MODIFY c_id INT NOT NULL,
             ADD PRIMARY KEY (c_id, class_id)'
        );
        $this->addSql(
            'ALTER TABLE course_rel_class
             ADD CONSTRAINT '.self::FOREIGN_KEY.' FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('course_rel_class')) {
            return;
        }

        $table = $schema->getTable('course_rel_class');
        if (!$table->hasColumn('c_id') || $table->hasColumn('course_code')) {
            return;
        }

        $this->addSql('ALTER TABLE course_rel_class ADD course_code VARCHAR(40) DEFAULT NULL');
        $this->addSql(
            'UPDATE course_rel_class crc
             INNER JOIN course c ON c.id = crc.c_id
             SET crc.course_code = c.code'
        );

        if ($table->hasForeignKey(self::FOREIGN_KEY)) {
            $this->addSql('ALTER TABLE course_rel_class DROP FOREIGN KEY '.self::FOREIGN_KEY);
        }

        $this->addSql(
            'ALTER TABLE course_rel_class
             DROP PRIMARY KEY,
             DROP COLUMN c_id,
             MODIFY course_code VARCHAR(40) NOT NULL,
             ADD PRIMARY KEY (course_code, class_id)'
        );
    }
}
