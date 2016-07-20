<?php

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20160405112100 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            'CREATE TABLE skill_level_profile (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE skill_level (id INT AUTO_INCREMENT NOT NULL, profile_id INT NOT NULL, name VARCHAR(255) NOT NULL, position INT, short_name VARCHAR(255), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE skill_rel_user ADD acquired_level INT, ADD argumentation TEXT, ADD argumentation_author_id INT, MODIFY course_id INT, MODIFY session_id INT'
        );
        $this->addSql(
            'CREATE TABLE skill_rel_user_comment (id INT AUTO_INCREMENT NOT NULL, skill_rel_user_id INT NOT NULL, feedback_giver_id INT NOT NULL, feedback_text TEXT, feedback_value INT, feedback_datetime DATETIME, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE skill ADD profile_id INT'
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql(
            'ALTER TABLE skill_rel_user DROP COLUMN acquired_level, DROP COLUMN argumentation, DROP COLUMN argumentation_author_id, MODIFY course_id INT NOT NULL, MODIFY session_id INT NOT NULL'
        );
        $this->addSql(
            'ALTER TABLE skill DROP COLUMN profile_id'
        );
        $this->addSql('DROP TABLE skill_level');
        $this->addSql('DROP TABLE skill_level_profile');
    }
}