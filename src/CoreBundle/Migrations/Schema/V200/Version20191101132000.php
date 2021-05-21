<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20191101132000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Course changes';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('course');
        if (false === $table->hasColumn('resource_node_id')) {
            $this->addSql('ALTER TABLE course ADD COLUMN resource_node_id INT DEFAULT NULL;');
            $this->addSql(
                'ALTER TABLE course ADD CONSTRAINT FK_169E6FB91BAD783F FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE UNIQUE INDEX UNIQ_169E6FB91BAD783F ON course (resource_node_id);');
        }
        if ($table->hasForeignKey('FK_169E6FB912469DE2')) {
            $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB912469DE2');
        }
        if ($table->hasForeignKey('IDX_169E6FB912469DE2')) {
            $this->addSql('DROP INDEX IDX_169E6FB912469DE2 ON course');
        }
        if ($table->hasIndex('category_code')) {
            $this->addSql('DROP INDEX category_code ON course');
        }

        if ($table->hasIndex('directory')) {
            $this->addSql('DROP INDEX directory ON course;');
        }

        $this->addSql('UPDATE course SET course_language = "en" WHERE course_language IS NULL');
        $this->addSql('ALTER TABLE course CHANGE course_language course_language VARCHAR(20) NOT NULL');

        $this->addSql('UPDATE course SET visibility = "0" WHERE visibility IS NULL');
        $this->addSql('ALTER TABLE course CHANGE visibility visibility INT NOT NULL');

        $this->addSql('UPDATE course SET creation_date = NOW() WHERE creation_date IS NULL');
        $this->addSql('ALTER TABLE course CHANGE creation_date creation_date DATETIME NOT NULL');

        $this->addSql('UPDATE course SET subscribe = 0 WHERE subscribe IS NULL');
        $this->addSql('ALTER TABLE course CHANGE subscribe subscribe TINYINT(1) NOT NULL');

        $this->addSql('UPDATE course SET unsubscribe = 0 WHERE unsubscribe IS NULL');
        $this->addSql('ALTER TABLE course CHANGE unsubscribe unsubscribe TINYINT(1) NOT NULL');

        if (false === $schema->hasTable('course_rel_category')) {
            $this->addSql('CREATE TABLE course_rel_category (course_id INT NOT NULL, course_category_id INT NOT NULL, INDEX IDX_16B33772591CC992 (course_id), INDEX IDX_16B337726628AD36 (course_category_id), PRIMARY KEY(course_id, course_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;');
            $this->addSql('ALTER TABLE course_rel_category ADD CONSTRAINT FK_16B33772591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
            $this->addSql('ALTER TABLE course_rel_category ADD CONSTRAINT FK_16B337726628AD36 FOREIGN KEY (course_category_id) REFERENCES course_category (id);');
        }

        if ($schema->getTable('course')->hasColumn('category_id')) {
            //$this->addSql('ALTER TABLE course DROP category_id');
        }

        $table = $schema->getTable('course_rel_user');

        if (!$table->hasColumn('course_rel_user')) {
            $this->addSql('ALTER TABLE course_rel_user ADD progress INT NOT NULL;');
        }

        if (false === $table->hasIndex('course_rel_user_user_id')) {
            $this->addSql('CREATE INDEX course_rel_user_user_id ON course_rel_user (id, user_id)');
        }
        if (false === $table->hasIndex('course_rel_user_c_id_user_id')) {
            $this->addSql('CREATE INDEX course_rel_user_c_id_user_id ON course_rel_user (id, c_id, user_id)');
        }
        //$this->addSql('ALTER TABLE course DROP category_code');
        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT * FROM course_category';
        $result = $connection->executeQuery($sql);
        $all = $result->fetchAllAssociative();

        $categories = array_column($all, 'parent_id', 'id');
        $categoryCodeList = array_column($all, 'id', 'code');

        foreach ($categories as $categoryId => $parentId) {
            if (empty($parentId)) {
                continue;
            }
            $newParentId = $categoryCodeList[$parentId];
            if (!empty($newParentId)) {
                $this->addSql("UPDATE course_category SET parent_id = {$newParentId} WHERE id = {$categoryId}");
            }
        }

        $this->addSql('ALTER TABLE course_category CHANGE parent_id parent_id INT DEFAULT NULL;');

        $table = $schema->getTable('course_category');
        if (false === $table->hasForeignKey('FK_AFF87497727ACA70')) {
            $this->addSql(
                'ALTER TABLE course_category ADD CONSTRAINT FK_AFF87497727ACA70 FOREIGN KEY (parent_id) REFERENCES course_category (id);'
            );
        }
        if (!$table->hasColumn('image')) {
            $this->addSql('ALTER TABLE course_category ADD image VARCHAR(255) DEFAULT NULL');
        }
        if (!$table->hasColumn('description')) {
            $this->addSql('ALTER TABLE course_category ADD description LONGTEXT DEFAULT NULL');
        }

        $this->addSql(
            'ALTER TABLE course_category CHANGE auth_course_child auth_course_child VARCHAR(40) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
    }
}
