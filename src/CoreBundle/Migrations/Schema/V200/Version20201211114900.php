<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201211114900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate access_url, users';
    }

    public function up(Schema $schema): void
    {
        error_log('MIGRATIONS :: FILE -- Version20201211114900 ...');

        if ($schema->hasTable('gradebook_category')) {
            $table = $schema->getTable('gradebook_category');
            if (!$table->hasColumn('allow_skills_by_subcategory')) {
                $this->addSql(
                    'ALTER TABLE gradebook_category ADD allow_skills_by_subcategory INT DEFAULT 1'
                );
            }
        }

        if ($schema->hasTable('c_survey_answer')) {
            $table = $schema->getTable('c_survey_answer');
            if (!$table->hasColumn('session_id')) {
                $this->addSql(
                    'ALTER TABLE c_survey_answer ADD session_id INT NOT NULL'
                );
            }
            if (!$table->hasColumn('c_lp_item_id')) {
                $this->addSql(
                    'ALTER TABLE c_survey_answer ADD c_lp_item_id INT NOT NULL'
                );
            }
        }

        if ($schema->hasTable('c_survey_invitation')) {
            $table = $schema->getTable('c_survey_invitation');
            if (!$table->hasColumn('c_lp_item_id')) {
                $this->addSql(
                    'ALTER TABLE c_survey_invitation ADD c_lp_item_id INT NOT NULL'
                );
            }
        }

        if ($schema->hasTable('c_quiz')) {
            $table = $schema->getTable('c_quiz');
            if (!$table->hasColumn('hide_attempts_table')) {
                $this->addSql(
                    'ALTER TABLE c_quiz ADD hide_attempts_table TINYINT(1) NOT NULL'
                );
            }
        }

        if ($schema->hasTable('c_attendance_calendar')) {
            $table = $schema->getTable('c_attendance_calendar');
            if (!$table->hasColumn('blocked')) {
                $this->addSql(
                    'ALTER TABLE c_attendance_calendar ADD blocked TINYINT(1) DEFAULT NULL'
                );
            }
        }

        if ($schema->hasTable('c_attendance_sheet')) {
            $table = $schema->getTable('c_attendance_sheet');
            if (!$table->hasColumn('signature')) {
                $this->addSql(
                    'ALTER TABLE c_attendance_sheet ADD signature VARCHAR(255) DEFAULT NULL'
                );
            }
        }

        if ($schema->hasTable('c_lp')) {
            $table = $schema->getTable('c_lp');
            if (!$table->hasColumn('published_on')) {
                $this->addSql(
                    'ALTER TABLE c_lp CHANGE publicated_on published_on datetime NULL;'
                );
            }
        }

        if ($schema->hasTable('c_lp')) {
            $table = $schema->getTable('c_lp');
            if (!$table->hasColumn('next_lp_id')) {
                $this->addSql(
                    'ALTER TABLE c_lp ADD next_lp_id INT DEFAULT 0 NOT NULL'
                );
            }
        }

        if ($schema->hasTable('c_lp_item')) {
            $table = $schema->getTable('c_lp_item');
            if (!$table->hasColumn('item_root')) {
                $this->addSql(
                    'ALTER TABLE c_lp_item CHANGE c_id item_root INT DEFAULT NULL'
                );
                $this->addSql(
                    'ALTER TABLE c_lp_item ADD CONSTRAINT FK_CCC9C1EDDEC4BDA0 FOREIGN KEY (item_root) REFERENCES c_lp_item (iid) ON DELETE CASCADE'
                );
                $this->addSql(
                    'CREATE INDEX IDX_CCC9C1EDDEC4BDA0 ON c_lp_item (item_root)'
                );
            }
        }

        if (!$schema->hasTable('c_wiki_category')) {
            $this->addSql(
                'CREATE TABLE c_wiki_category (id INT AUTO_INCREMENT NOT NULL, c_id INT NOT NULL, session_id INT DEFAULT NULL, tree_root INT DEFAULT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, lft INT NOT NULL, lvl INT NOT NULL, rgt INT NOT NULL, INDEX IDX_17F1099A91D79BD3 (c_id), INDEX IDX_17F1099A613FECDF (session_id), INDEX IDX_17F1099AA977936C (tree_root), INDEX IDX_17F1099A727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_category ADD CONSTRAINT FK_17F1099A91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_category ADD CONSTRAINT FK_17F1099A613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_category ADD CONSTRAINT FK_17F1099AA977936C FOREIGN KEY (tree_root) REFERENCES c_wiki_category (id) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_category ADD CONSTRAINT FK_17F1099A727ACA70 FOREIGN KEY (parent_id) REFERENCES c_wiki_category (id) ON DELETE CASCADE'
            );
        }

        if (!$schema->hasTable('c_wiki_rel_category')) {
            $this->addSql(
                'CREATE TABLE c_wiki_rel_category (wiki_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_AC88945BAA948DBE (wiki_id), INDEX IDX_AC88945B12469DE2 (category_id), PRIMARY KEY(wiki_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_rel_category ADD CONSTRAINT FK_AC88945BAA948DBE FOREIGN KEY (wiki_id) REFERENCES c_wiki (iid) ON DELETE CASCADE'
            );
            $this->addSql(
                'ALTER TABLE c_wiki_rel_category ADD CONSTRAINT FK_AC88945B12469DE2 FOREIGN KEY (category_id) REFERENCES c_wiki_category (id) ON DELETE CASCADE'
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_wiki_category')) {
            $this->addSql(
                'DROP TABLE c_wiki_category'
            );
        }

        if ($schema->hasTable('c_wiki_rel_category')) {
            $this->addSql(
                'DROP TABLE c_wiki_rel_category'
            );
        }

        if ($schema->hasTable('c_lp_item')) {
            $table = $schema->getTable('c_lp_item');
            if ($table->hasColumn('item_root')) {
                $this->addSql(
                    'ALTER TABLE c_lp_item CHANGE item_root c_id  INT DEFAULT NULL'
                );
            }
        }

        $table = $schema->getTable('c_lp');
        if ($table->hasColumn('next_lp_id')) {
            $this->addSql('ALTER TABLE c_lp DROP next_lp_id');
        }

        if ($schema->hasTable('c_lp')) {
            $table = $schema->getTable('c_lp');
            if ($table->hasColumn('published_on')) {
                $this->addSql(
                    'ALTER TABLE c_lp CHANGE published_on publicated_on datetime NULL;'
                );
            }
        }
        $table = $schema->getTable('c_attendance_sheet');
        if ($table->hasColumn('signature')) {
            $this->addSql('ALTER TABLE c_attendance_sheet DROP signature');
        }

        $table = $schema->getTable('c_attendance_calendar');
        if ($table->hasColumn('blocked')) {
            $this->addSql('ALTER TABLE c_attendance_calendar DROP blocked');
        }

        $table = $schema->getTable('c_quiz');
        if ($table->hasColumn('hide_attempts_table')) {
            $this->addSql('ALTER TABLE c_quiz DROP hide_attempts_table');
        }

        $table = $schema->getTable('c_survey_answer');
        if ($table->hasColumn('session_id')) {
            $this->addSql('ALTER TABLE c_survey_answer DROP session_id');
        }

        if ($table->hasColumn('c_lp_item_id')) {
            $this->addSql('ALTER TABLE c_survey_answer DROP c_lp_item_id');
        }

        $table = $schema->getTable('c_survey_invitation');
        if ($table->hasColumn('c_lp_item_id')) {
            $this->addSql('ALTER TABLE c_survey_invitation DROP c_lp_item_id');
        }

        $table = $schema->getTable('gradebook_category');
        if ($table->hasColumn('allow_skills_by_subcategory')) {
            $this->addSql('ALTER TABLE gradebook_category DROP allow_skills_by_subcategory');
        }
    }
}
