<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160405112100
 * @package Application\Migrations\Schema\V111
 */
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

        $this->addSql('ALTER TABLE skill ADD profile_id INT');

        if ($schema->hasTable('skill')) {
            $table = $schema->getTable('skill');
            if ($table->hasForeignKey('FK_5E3DE477CCFA12B8') == false) {
                $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE477CCFA12B8 FOREIGN KEY (profile_id) REFERENCES skill_level_profile (id);');
            }

            if ($table->hasIndex('IDX_5E3DE477CCFA12B8') == false) {
                $this->addSql('CREATE INDEX IDX_5E3DE477CCFA12B8 ON skill (profile_id);');
            }
        }

         // Skill
        if ($schema->hasTable('skill')) {
            $this->addSql('ALTER TABLE skill CHANGE name name VARCHAR(255) NOT NULL, CHANGE short_code short_code VARCHAR(100) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE icon icon VARCHAR(255) NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL;');
        }

        // skill_rel_user
        if ($schema->hasTable('skill_rel_user')) {
            $table = $schema->getTable('skill_rel_user');
            $this->addSql('ALTER TABLE skill_rel_user CHANGE acquired_skill_at acquired_skill_at DATETIME NOT NULL, CHANGE argumentation argumentation LONGTEXT NOT NULL, CHANGE argumentation_author_id argumentation_author_id INT NOT NULL;');

            $this->addSql('UPDATE skill_rel_user SET course_id = NULL WHERE course_id = 0');
            $this->addSql('UPDATE skill_rel_user SET skill_id = NULL WHERE skill_id = 0');
            $this->addSql('UPDATE skill_rel_user SET user_id = NULL WHERE user_id = 0');
            $this->addSql('UPDATE skill_rel_user SET session_id = NULL WHERE session_id = 0');
            $this->addSql('UPDATE skill_rel_user SET acquired_level = NULL WHERE acquired_level = 0');


            if ($table->hasForeignKey('FK_79D3D95AA76ED395') == false) {
                $this->addSql('ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);');
            }
            if ($table->hasForeignKey('FK_79D3D95A5585C142') == false) {
                $this->addSql('ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id);');
            }
            if ($table->hasForeignKey('FK_79D3D95A591CC992') == false) {
                $this->addSql('ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A591CC992 FOREIGN KEY (course_id) REFERENCES course (id);');
            }
            if ($table->hasForeignKey('FK_79D3D95A613FECDF') == false) {
                $this->addSql('ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A613FECDF FOREIGN KEY (session_id) REFERENCES session (id);');
            }
            if ($table->hasForeignKey('FK_79D3D95AF68F11CE') == false) {
                $this->addSql('ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95AF68F11CE FOREIGN KEY (acquired_level) REFERENCES skill_level (id);');
            }
            if ($table->hasIndex('IDX_79D3D95AA76ED395') == false) {
                $this->addSql('CREATE INDEX IDX_79D3D95AA76ED395 ON skill_rel_user(user_id);');
            }
            if ($table->hasIndex('IDX_79D3D95A5585C142') == false) {
                $this->addSql('CREATE INDEX IDX_79D3D95A5585C142 ON skill_rel_user(skill_id);');
            }
            if ($table->hasIndex('IDX_79D3D95A591CC992') == false) {
                $this->addSql('CREATE INDEX IDX_79D3D95A591CC992 ON skill_rel_user(course_id);');
            }
            if ($table->hasIndex('IDX_79D3D95A613FECDF') == false) {
                $this->addSql('CREATE INDEX IDX_79D3D95A613FECDF ON skill_rel_user(session_id);');
            }
            if ($table->hasIndex('IDX_79D3D95AF68F11CE') == false) {
                $this->addSql('CREATE INDEX IDX_79D3D95AF68F11CE ON skill_rel_user (acquired_level);');
            }
            if ($table->hasIndex('IDX_79D3D95AF68F11CE') == false) {
                $this->addSql('CREATE INDEX idx_select_s_c_u ON skill_rel_user (session_id, course_id, user_id);');
            }
            if ($table->hasIndex('IDX_79D3D95AF68F11CE') == false) {
                $this->addSql('CREATE INDEX idx_select_sk_u ON skill_rel_user(skill_id, user_id);');
            }
        }

         // skill_level
        if ($schema->hasTable('skill_level')) {
            $table = $schema->getTable('skill_level');
            $this->addSql('ALTER TABLE skill_level CHANGE profile_id profile_id INT DEFAULT NULL, CHANGE position position INT NOT NULL, CHANGE short_name short_name VARCHAR(255) NOT NULL;');
            if ($table->hasForeignKey('FK_BFC25F2FCCFA12B8') == false) {
                $this->addSql('ALTER TABLE skill_level ADD CONSTRAINT FK_BFC25F2FCCFA12B8 FOREIGN KEY (profile_id) REFERENCES skill_level_profile (id);');
            }
            if ($table->hasIndex('IDX_BFC25F2FCCFA12B8') == false) {
                $this->addSql('CREATE INDEX IDX_BFC25F2FCCFA12B8 ON skill_level (profile_id);');
            }
        }



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