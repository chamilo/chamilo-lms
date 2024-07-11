<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201205102020 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate skills';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('skill');

        $this->addSql('UPDATE skill SET updated_at = NOW() WHERE CAST(updated_at AS CHAR(20)) = "0000-00-00 00:00:00"');

        if (!$table->hasColumn('asset_id')) {
            $this->addSql("ALTER TABLE skill ADD asset_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)'");
            $this->addSql('CREATE INDEX IDX_5E3DE4775DA1941 ON skill (asset_id)');
        }

        if (!$table->hasForeignKey('FK_5E3DE4775DA1941')) {
            $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE4775DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id)');
        }

        if (!$schema->hasTable('skill_rel_item_rel_user')) {
            $this->addSql(
                'CREATE TABLE skill_rel_item_rel_user (id INT AUTO_INCREMENT NOT NULL, skill_rel_item_id INT NOT NULL, user_id INT NOT NULL, result_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by INT NOT NULL, updated_by INT NOT NULL, INDEX IDX_D1133E0DFD4B12DC (skill_rel_item_id), INDEX IDX_D1133E0DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'CREATE TABLE skill_rel_item (id INT AUTO_INCREMENT NOT NULL, skill_id INT DEFAULT NULL, item_type INT NOT NULL, item_id INT NOT NULL, obtain_conditions VARCHAR(255) DEFAULT NULL, requires_validation TINYINT(1) NOT NULL, is_real TINYINT(1) NOT NULL, c_id INT DEFAULT NULL, session_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, created_by INT NOT NULL, updated_by INT NOT NULL, INDEX IDX_EB5B2A0D5585C142 (skill_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_item_rel_user ADD CONSTRAINT FK_D1133E0DFD4B12DC FOREIGN KEY (skill_rel_item_id) REFERENCES skill_rel_item (id);'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_item_rel_user ADD CONSTRAINT FK_D1133E0DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_item ADD CONSTRAINT FK_EB5B2A0D5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
            );
        }

        if (!$schema->hasTable('skill_rel_course')) {
            $this->addSql(
                'CREATE TABLE skill_rel_course (id INT AUTO_INCREMENT NOT NULL, skill_id INT DEFAULT NULL, c_id INT NOT NULL, session_id INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_E7CEC7FA5585C142 (skill_id), INDEX IDX_E7CEC7FA91D79BD3 (c_id), INDEX IDX_E7CEC7FA613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
            );
        } else {
            $table = $schema->getTable('skill_rel_course');
            if (!$table->hasForeignKey('FK_E7CEC7FA5585C142')) {
                $this->addSql('DELETE FROM skill_rel_course WHERE skill_id NOT IN (SELECT id FROM skill)');
                $this->addSql(
                    'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
                );
            }

            if (!$table->hasForeignKey('FK_E7CEC7FA91D79BD3')) {
                $this->addSql('DELETE FROM skill_rel_course WHERE c_id NOT IN (SELECT id FROM course)');
                $this->addSql(
                    'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
                );
            }

            if (!$table->hasForeignKey('FK_E7CEC7FA613FECDF')) {
                $this->addSql('DELETE FROM skill_rel_course WHERE session_id NOT IN (SELECT id FROM session)');
                $this->addSql(
                    'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
                );
            }
        }

        $table = $schema->getTable('skill_rel_user');
        if (!$table->hasColumn('validation_status')) {
            $this->addSql('ALTER TABLE skill_rel_user ADD validation_status INT NOT NULL');
        }

        if (!$table->hasForeignKey('FK_79D3D95A5585C142')) {
            $this->addSql('DELETE FROM skill_rel_user WHERE skill_id NOT IN (SELECT id FROM skill)');
            $this->addSql(
                'ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_79D3D95A591CC992')) {
            $this->addSql('UPDATE skill_rel_user SET course_id = NULL WHERE course_id NOT IN (SELECT id FROM course)');
            $this->addSql(
                'ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE SET NULL'
            );
        }

        if (!$table->hasForeignKey('FK_79D3D95A613FECDF')) {
            $this->addSql('UPDATE skill_rel_user SET session_id = NULL WHERE session_id NOT IN (SELECT id FROM session)');
            $this->addSql(
                'ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE SET NULL'
            );
        }

        if (!$table->hasForeignKey('FK_79D3D95AA76ED395')) {
            $this->addSql('DELETE FROM skill_rel_user WHERE user_id NOT IN (SELECT id FROM user)');
            $this->addSql(
                'ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        $table = $schema->getTable('skill_rel_profile');
        $this->addSql('ALTER TABLE skill_rel_profile CHANGE skill_id skill_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill_rel_profile CHANGE profile_id profile_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_6E73EA8D5585C142')) {
            $this->addSql('DELETE FROM skill_rel_profile WHERE skill_id NOT IN (SELECT id FROM skill)');
            $this->addSql(
                'ALTER TABLE skill_rel_profile ADD CONSTRAINT FK_6E73EA8D5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_6E73EA8DCCFA12B8')) {
            $this->addSql('DELETE FROM skill_rel_profile WHERE profile_id NOT IN (SELECT id FROM skill_profile)');
            $this->addSql(
                'ALTER TABLE skill_rel_profile ADD CONSTRAINT FK_6E73EA8DCCFA12B8 FOREIGN KEY (profile_id) REFERENCES skill_profile (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_6E73EA8D5585C142')) {
            $this->addSql('CREATE INDEX IDX_6E73EA8D5585C142 ON skill_rel_profile (skill_id)');
        }

        if (!$table->hasIndex('IDX_6E73EA8DCCFA12B8')) {
            $this->addSql('CREATE INDEX IDX_6E73EA8DCCFA12B8 ON skill_rel_profile (profile_id)');
        }

        $table = $schema->getTable('skill_rel_gradebook');

        $this->addSql('ALTER TABLE skill_rel_gradebook CHANGE gradebook_id gradebook_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill_rel_gradebook CHANGE skill_id skill_id INT DEFAULT NULL');

        $this->addSql('DELETE FROM skill_rel_gradebook WHERE skill_id NOT IN (SELECT id FROM skill)');
        if (!$table->hasForeignKey('FK_4AC0B45E5585C142')) {
            $this->addSql(
                'ALTER TABLE skill_rel_gradebook ADD CONSTRAINT FK_4AC0B45E5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
            );
        }

        $this->addSql('DELETE FROM skill_rel_gradebook WHERE gradebook_id NOT IN (SELECT id FROM gradebook_category)');
        if (!$table->hasForeignKey('FK_4AC0B45EAD3ED51C')) {
            $this->addSql(
                'ALTER TABLE skill_rel_gradebook ADD CONSTRAINT FK_4AC0B45EAD3ED51C FOREIGN KEY (gradebook_id) REFERENCES gradebook_category (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasIndex('IDX_4AC0B45E5585C142')) {
            $this->addSql('CREATE INDEX IDX_4AC0B45E5585C142 ON skill_rel_gradebook (skill_id)');
        }
        if (!$table->hasIndex('IDX_4AC0B45EAD3ED51C')) {
            $this->addSql('CREATE INDEX IDX_4AC0B45EAD3ED51C ON skill_rel_gradebook (gradebook_id)');
        }

        $table = $schema->getTable('skill_rel_skill');

        $this->addSql('ALTER TABLE skill_rel_skill CHANGE skill_id skill_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill_rel_skill CHANGE parent_id parent_id INT DEFAULT NULL');

        $this->addSql('UPDATE skill_rel_skill SET parent_id = NULL WHERE parent_id = 0');

        if (!$table->hasForeignKey('FK_DA77E5A65585C142')) {
            $this->addSql('ALTER TABLE skill_rel_skill ADD CONSTRAINT FK_DA77E5A65585C142 FOREIGN KEY (skill_id) REFERENCES skill (id);');
        }

        if (!$table->hasForeignKey('FK_DA77E5A6727ACA70')) {
            $this->addSql('ALTER TABLE skill_rel_skill ADD CONSTRAINT FK_DA77E5A6727ACA70 FOREIGN KEY (parent_id) REFERENCES skill (id) ON DELETE SET NULL;');
        }

        if (!$table->hasIndex('IDX_DA77E5A65585C142')) {
            $this->addSql('CREATE INDEX IDX_DA77E5A65585C142 ON skill_rel_skill (skill_id);');
        }

        if (!$table->hasIndex('IDX_DA77E5A6727ACA70')) {
            $this->addSql('CREATE INDEX IDX_DA77E5A6727ACA70 ON skill_rel_skill (parent_id);');
        }
    }
}
