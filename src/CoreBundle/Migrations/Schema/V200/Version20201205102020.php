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

        if (!$table->hasColumn('asset_id')) {
            $this->addSql('ALTER TABLE skill ADD asset_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE4775DA1941 FOREIGN KEY (asset_id) REFERENCES asset (id)');
            $this->addSql('CREATE INDEX IDX_5E3DE4775DA1941 ON skill (asset_id)');
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
                'ALTER TABLE skill_rel_item ADD CONSTRAINT FK_EB5B2A0D5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id);'
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
                $this->addSql(
                    'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
                );
            }

            if (!$table->hasForeignKey('FK_E7CEC7FA91D79BD3')) {
                $this->addSql(
                    'ALTER TABLE skill_rel_course ADD CONSTRAINT FK_E7CEC7FA91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
                );
            }

            if (!$table->hasForeignKey('FK_E7CEC7FA613FECDF')) {
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
            $this->addSql(
                'ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
            );
        }
        if (!$table->hasForeignKey('FK_79D3D95A591CC992')) {
            $this->addSql(
                'ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE'
            );
        }
        if (!$table->hasForeignKey('FK_79D3D95A613FECDF')) {
            $this->addSql(
                'ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95A613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
            );
        }
        if (!$table->hasForeignKey('FK_79D3D95AA76ED395')) {
            $this->addSql(
                'ALTER TABLE skill_rel_user ADD CONSTRAINT FK_79D3D95AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        $table = $schema->getTable('skill_rel_profile');
        $this->addSql('ALTER TABLE skill_rel_profile CHANGE skill_id skill_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill_rel_profile CHANGE profile_id profile_id INT DEFAULT NULL');

        if (!$table->hasForeignKey('FK_6E73EA8D5585C142')) {
            $this->addSql(
                'ALTER TABLE skill_rel_profile ADD CONSTRAINT FK_6E73EA8D5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
            );
        }

        if (!$table->hasForeignKey('FK_6E73EA8DCCFA12B8')) {
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

        if (!$table->hasForeignKey('FK_4AC0B45E5585C142')) {
            $this->addSql(
                'ALTER TABLE skill_rel_gradebook ADD CONSTRAINT FK_4AC0B45E5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE'
            );
        }

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
    }
}
