<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20191101132000 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        // Update iso
        $sql = 'UPDATE course SET course_language = (SELECT isocode FROM language WHERE english_name = course_language);';
        $this->addSql($sql);

        if (false === $schema->hasTable('course_rel_category')) {
            $this->addSql('CREATE TABLE course_rel_category (course_id INT NOT NULL, course_category_id INT NOT NULL, INDEX IDX_8EB34CC5591CC992 (course_id), INDEX IDX_8EB34CC56628AD36 (course_category_id), PRIMARY KEY(course_id, course_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
            $this->addSql('ALTER TABLE course_rel_category ADD CONSTRAINT FK_8EB34CC5591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE course_rel_category ADD CONSTRAINT FK_8EB34CC56628AD36 FOREIGN KEY (course_category_id) REFERENCES course_category (id) ON DELETE CASCADE');

            $courseTable = $schema->getTable('course');
            if ($courseTable->hasForeignKey('FK_169E6FB912469DE2')) {
                $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB912469DE2');
            }
            if ($courseTable->hasForeignKey('IDX_169E6FB912469DE2')) {
                $this->addSql('DROP INDEX IDX_169E6FB912469DE2 ON course');
            }
            if ($courseTable->hasColumn('category_id')) {
                $this->addSql('ALTER TABLE course DROP category_id');
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
