<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

final class Version20261012160000 extends AbstractMigrationChamilo
{
    private const string FOREIGN_KEY = 'FK_COURSE_REQUEST_CATEGORY';
    private const string INDEX = 'IDX_COURSE_REQUEST_CATEGORY';

    public function getDescription(): string
    {
        return 'Replace course_request.category_code with a foreign key to course_category.id';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course_request')) {
            return;
        }

        $table = $schema->getTable('course_request');
        if (!$table->hasColumn('category_code')) {
            return;
        }

        $orphanCount = (int) $this->connection->fetchOne(
            "SELECT COUNT(*)
             FROM course_request cr
             LEFT JOIN course_category cc ON cc.code = cr.category_code
             WHERE cr.category_code IS NOT NULL
               AND cr.category_code <> ''
               AND cc.id IS NULL"
        );

        if ($orphanCount > 0) {
            throw new RuntimeException(\sprintf('Cannot migrate course_request: %d row(s) reference a category_code that does not exist in course_category.code.', $orphanCount));
        }

        if (!$table->hasColumn('category_id')) {
            $this->addSql('ALTER TABLE course_request ADD category_id INT DEFAULT NULL');
        }

        $this->addSql(
            "UPDATE course_request cr
             INNER JOIN course_category cc ON cc.code = cr.category_code
             SET cr.category_id = cc.id
             WHERE cr.category_code IS NOT NULL
               AND cr.category_code <> ''"
        );
        $this->addSql('ALTER TABLE course_request DROP COLUMN category_code');

        if (!$table->hasIndex(self::INDEX)) {
            $this->addSql('CREATE INDEX '.self::INDEX.' ON course_request (category_id)');
        }

        if (!$table->hasForeignKey(self::FOREIGN_KEY)) {
            $this->addSql(
                'ALTER TABLE course_request
                 ADD CONSTRAINT '.self::FOREIGN_KEY.' FOREIGN KEY (category_id) REFERENCES course_category (id) ON DELETE SET NULL'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('course_request')) {
            return;
        }

        $table = $schema->getTable('course_request');
        if (!$table->hasColumn('category_id') || $table->hasColumn('category_code')) {
            return;
        }

        $this->addSql('ALTER TABLE course_request ADD category_code VARCHAR(40) DEFAULT NULL');
        $this->addSql(
            'UPDATE course_request cr
             INNER JOIN course_category cc ON cc.id = cr.category_id
             SET cr.category_code = cc.code'
        );

        if ($table->hasForeignKey(self::FOREIGN_KEY)) {
            $this->addSql('ALTER TABLE course_request DROP FOREIGN KEY '.self::FOREIGN_KEY);
        }

        if ($table->hasIndex(self::INDEX)) {
            $this->addSql('DROP INDEX '.self::INDEX.' ON course_request');
        }

        $this->addSql('ALTER TABLE course_request DROP COLUMN category_id');
    }
}
