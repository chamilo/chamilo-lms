<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20181025064351 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate gradebook_category';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('gradebook_result_log');
        if ($table->hasColumn('id_result')) {
            $this->addSql('DELETE FROM gradebook_result_log WHERE id_result IS NULL');
            $this->addSql('ALTER TABLE gradebook_result_log CHANGE id_result result_id INT DEFAULT NULL');
        }

        $this->addSql('UPDATE gradebook_result_log SET evaluation_id = NULL WHERE evaluation_id = 0');
        $this->addSql('ALTER TABLE gradebook_result_log CHANGE evaluation_id evaluation_id INT DEFAULT NULL');

        $this->addSql('UPDATE gradebook_result_log SET user_id = NULL WHERE user_id = 0');
        $this->addSql('ALTER TABLE gradebook_result_log CHANGE user_id user_id INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_C5C4CABB7A7B643')) {
            $this->addSql(
                'ALTER TABLE gradebook_result_log ADD CONSTRAINT FK_C5C4CABB7A7B643 FOREIGN KEY (result_id) REFERENCES gradebook_result (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE INDEX IDX_C5C4CABB7A7B643 ON gradebook_result_log (result_id)');
        }

        if (false === $table->hasForeignKey('FK_C5C4CABB456C5646')) {
            $this->addSql(
                'ALTER TABLE gradebook_result_log ADD CONSTRAINT FK_C5C4CABB456C5646 FOREIGN KEY (evaluation_id) REFERENCES gradebook_evaluation (id) ON DELETE CASCADE;'
            );
            $this->addSql('CREATE INDEX IDX_C5C4CABB456C5646 ON gradebook_result_log (evaluation_id);');
        }

        if (false === $table->hasForeignKey('FK_C5C4CABBA76ED395')) {
            $this->addSql(
                'ALTER TABLE gradebook_result_log ADD CONSTRAINT FK_C5C4CABBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (false === $table->hasIndex('IDX_C5C4CABBA76ED395')) {
            $this->addSql('CREATE INDEX IDX_C5C4CABBA76ED395 ON gradebook_result_log (user_id)');
        }

        $table = $schema->getTable('gradebook_category');

        $this->addSql('ALTER TABLE gradebook_category CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql(
            'DELETE FROM gradebook_category WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM user)'
        );

        if ($table->hasIndex('idx_gb_cat_parent')) {
            $this->addSql(' DROP INDEX idx_gb_cat_parent ON gradebook_category;');
        }

        $this->addSql('UPDATE gradebook_category SET session_id = NULL WHERE session_id = 0');
        $this->addSql('UPDATE gradebook_category SET parent_id = NULL WHERE parent_id = 0');

        if (false === $table->hasForeignKey('FK_96A4C705727ACA70')) {
            $this->addSql('ALTER TABLE gradebook_category ADD CONSTRAINT FK_96A4C705727ACA70 FOREIGN KEY (parent_id) REFERENCES gradebook_category (id);');
        }

        if (false === $table->hasForeignKey('FK_96A4C705613FECDF')) {
            $this->addSql('ALTER TABLE gradebook_category ADD CONSTRAINT FK_96A4C705613FECDF FOREIGN KEY (session_id) REFERENCES session (id) ON DELETE CASCADE;');
        }

        if (false === $table->hasIndex('IDX_96A4C705613FECDF')) {
            $this->addSql('CREATE INDEX IDX_96A4C705613FECDF ON gradebook_category (session_id)');
        }

        if (false === $table->hasIndex('IDX_96A4C705727ACA70')) {
            $this->addSql('CREATE INDEX IDX_96A4C705727ACA70 ON gradebook_category (parent_id);');
        }

        if (false === $table->hasColumn('c_id')) {
            $this->addSql('ALTER TABLE gradebook_category ADD c_id INT DEFAULT NULL');
            $this->addSql('UPDATE gradebook_category SET c_id = (SELECT id FROM course WHERE code = course_code)');
            $this->addSql('ALTER TABLE gradebook_category DROP course_code');
            $this->addSql(
                'ALTER TABLE gradebook_category ADD CONSTRAINT FK_96A4C70591D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE'
            );
            $this->addSql('CREATE INDEX IDX_96A4C70591D79BD3 ON gradebook_category (c_id);');
        }
        if (false === $table->hasColumn('depends')) {
            $this->addSql('ALTER TABLE gradebook_category ADD depends LONGTEXT DEFAULT NULL');
        }
        if (false === $table->hasColumn('minimum_to_validate')) {
            $this->addSql('ALTER TABLE gradebook_category ADD minimum_to_validate INT DEFAULT NULL');
        }
        if (false === $table->hasColumn('gradebooks_to_validate_in_dependence')) {
            $this->addSql('ALTER TABLE gradebook_category ADD gradebooks_to_validate_in_dependence INT DEFAULT NULL');
        }

        if (false === $table->hasForeignKey('FK_96A4C705A76ED395')) {
            $this->addSql(
                'ALTER TABLE gradebook_category ADD CONSTRAINT FK_96A4C705A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)'
            );
        }
        if (false === $table->hasIndex('IDX_96A4C705A76ED395')) {
            $this->addSql('CREATE INDEX IDX_96A4C705A76ED395 ON gradebook_category (user_id)');
        }

        // Evaluation.
        $table = $schema->getTable('gradebook_evaluation');
        if (false === $table->hasColumn('c_id')) {
            $this->addSql('ALTER TABLE gradebook_evaluation ADD c_id INT DEFAULT NULL');
            $this->addSql('UPDATE gradebook_evaluation SET c_id = (SELECT id FROM course WHERE code = course_code)');
            $this->addSql('ALTER TABLE gradebook_evaluation DROP course_code');
            $this->addSql(
                'ALTER TABLE gradebook_evaluation ADD CONSTRAINT FK_DDDED80491D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);'
            );
            $this->addSql('CREATE INDEX IDX_DDDED80491D79BD3 ON gradebook_evaluation (c_id)');
            //$this->addSql('ALTER TABLE gradebook_evaluation RENAME INDEX fk_ddded80491d79bd3 TO IDX_DDDED80491D79BD3;');
        }
        if (false === $table->hasIndex('idx_ge_cat')) {
            $this->addSql('CREATE INDEX idx_ge_cat ON gradebook_evaluation (category_id)');
        }

        if (false === $table->hasForeignKey('FK_DDDED80412469DE2')) {
            $this->addSql('ALTER TABLE gradebook_evaluation ADD CONSTRAINT FK_DDDED80412469DE2 FOREIGN KEY (category_id) REFERENCES gradebook_category (id) ON DELETE CASCADE');
        }

        if (false === $table->hasColumn('best_score')) {
            $this->addSql('ALTER TABLE gradebook_evaluation ADD best_score DOUBLE PRECISION DEFAULT NULL');
        }
        if (false === $table->hasColumn('average_score')) {
            $this->addSql('ALTER TABLE gradebook_evaluation ADD average_score DOUBLE PRECISION DEFAULT NULL');
        }
        if (false === $table->hasColumn('score_weight')) {
            $this->addSql('ALTER TABLE gradebook_evaluation ADD score_weight DOUBLE PRECISION DEFAULT NULL');
        }
        if (false === $table->hasColumn('user_score_list')) {
            $this->addSql(
                'ALTER TABLE gradebook_evaluation ADD user_score_list LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\''
            );
        }
        $this->addSql('ALTER TABLE gradebook_evaluation CHANGE user_id user_id INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_DDDED804A76ED395')) {
            $this->addSql(
                'ALTER TABLE gradebook_evaluation ADD CONSTRAINT FK_DDDED804A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (false === $table->hasIndex('IDX_DDDED804A76ED395')) {
            $this->addSql('CREATE INDEX IDX_DDDED804A76ED395 ON gradebook_evaluation (user_id)');
        }

        $table = $schema->getTable('gradebook_link');
        if (false === $table->hasColumn('c_id')) {
            $this->addSql('ALTER TABLE gradebook_link ADD c_id INT DEFAULT NULL');
            $this->addSql('UPDATE gradebook_link SET c_id = (SELECT id FROM course WHERE code = course_code)');
            $this->addSql('ALTER TABLE gradebook_link DROP course_code');
            $this->addSql(
                'ALTER TABLE gradebook_link ADD CONSTRAINT FK_4F0F595F91D79BD3 FOREIGN KEY (c_id) REFERENCES course (id);'
            );
            $this->addSql('CREATE INDEX IDX_4F0F595F91D79BD3 ON gradebook_link (c_id);');
        }

        if (false === $table->hasColumn('best_score')) {
            $this->addSql('ALTER TABLE gradebook_link ADD best_score DOUBLE PRECISION DEFAULT NULL');
        }
        if (false === $table->hasColumn('average_score')) {
            $this->addSql(
                'ALTER TABLE gradebook_link ADD average_score DOUBLE PRECISION DEFAULT NULL'
            );
        }

        if (false === $table->hasColumn('score_weight')) {
            $this->addSql('ALTER TABLE gradebook_link ADD score_weight DOUBLE PRECISION DEFAULT NULL');
        }

        if (false === $table->hasColumn('user_score_list')) {
            $this->addSql(
                'ALTER TABLE gradebook_link ADD user_score_list LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\''
            );
        }

        if (false === $table->hasIndex('idx_gl_cat')) {
            $this->addSql('CREATE INDEX idx_gl_cat ON gradebook_link (category_id)');
        }

        $this->addSql('ALTER TABLE gradebook_link CHANGE user_id user_id INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_4F0F595FA76ED395')) {
            $this->addSql(
                'ALTER TABLE gradebook_link ADD CONSTRAINT FK_4F0F595FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (false === $table->hasIndex('IDX_4F0F595FA76ED395')) {
            $this->addSql('CREATE INDEX IDX_4F0F595FA76ED395 ON gradebook_link (user_id)');
        }

        $this->addSql('ALTER TABLE gradebook_link CHANGE category_id category_id INT DEFAULT NULL;');
        $this->addSql('UPDATE gradebook_link SET category_id = NULL WHERE category_id = 0');

        if (false === $table->hasForeignKey('FK_4F0F595F12469DE2')) {
            $this->addSql('ALTER TABLE gradebook_link ADD CONSTRAINT FK_4F0F595F12469DE2 FOREIGN KEY (category_id) REFERENCES gradebook_category (id) ON DELETE CASCADE');
        }

        $table = $schema->getTable('gradebook_result');

        if (false === $table->hasIndex('idx_gb_uid_eid')) {
            $this->addSql('CREATE INDEX idx_gb_uid_eid ON gradebook_result (user_id, evaluation_id);');
        }

        if (false === $table->hasIndex('IDX_B88AEB67456C5646')) {
            $this->addSql('CREATE INDEX IDX_B88AEB67456C5646 ON gradebook_result (evaluation_id);');
        }

        $this->addSql('ALTER TABLE gradebook_result CHANGE evaluation_id evaluation_id INT DEFAULT NULL;');
        $this->addSql('UPDATE gradebook_result SET evaluation_id = NULL WHERE evaluation_id = 0');

        if (false === $table->hasForeignKey('FK_B88AEB67456C5646')) {
            $this->addSql('ALTER TABLE gradebook_result ADD CONSTRAINT FK_B88AEB67456C5646 FOREIGN KEY (evaluation_id) REFERENCES gradebook_evaluation (id) ON DELETE CASCADE');
        }

        $this->addSql('ALTER TABLE gradebook_result CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('UPDATE gradebook_result SET user_id = NULL WHERE user_id = 0');

        if (false === $table->hasForeignKey('FK_B88AEB67A76ED395')) {
            $this->addSql(
                'ALTER TABLE gradebook_result ADD CONSTRAINT FK_B88AEB67A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (false === $table->hasIndex('IDX_B88AEB67A76ED395')) {
            $this->addSql('CREATE INDEX IDX_B88AEB67A76ED395 ON gradebook_result (user_id)');
        }

        $table = $schema->getTable('gradebook_certificate');
        $this->addSql('ALTER TABLE gradebook_certificate CHANGE cat_id cat_id INT DEFAULT NULL;');
        $this->addSql('UPDATE gradebook_certificate SET cat_id = NULL WHERE cat_id = 0');

        if (false === $table->hasForeignKey('FK_650669DE6ADA943')) {
            if ($table->hasIndex('idx_gradebook_certificate_category_id')) {
                $this->addSql('DROP INDEX idx_gradebook_certificate_category_id ON gradebook_certificate;');
            }

            if ($table->hasIndex('idx_gradebook_certificate_category_id_user_id')) {
                $this->addSql('DROP INDEX idx_gradebook_certificate_category_id_user_id ON gradebook_certificate;');
            }
            $this->addSql('ALTER TABLE gradebook_certificate ADD CONSTRAINT FK_650669DE6ADA943 FOREIGN KEY (cat_id) REFERENCES gradebook_category (id) ON DELETE CASCADE;');
        }

        if (false === $table->hasColumn('downloaded_at')) {
            $this->addSql('ALTER TABLE gradebook_certificate ADD downloaded_at DATETIME DEFAULT NULL;');
            $this->addSql(
                'UPDATE gradebook_certificate gc SET downloaded_at = (
                        SELECT value from extra_field e
                        INNER JOIN extra_field_values v on v.field_id = e.id
                        WHERE variable = "downloaded_at" and extra_field_type = 11 and item_id = gc.id
                    )'
            );
        }

        $this->addSql('ALTER TABLE gradebook_certificate CHANGE user_id user_id INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_650669DA76ED395')) {
            $this->addSql(
                'ALTER TABLE gradebook_certificate ADD CONSTRAINT FK_650669DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (false === $table->hasIndex('IDX_650669DE6ADA943')) {
            $this->addSql('CREATE INDEX IDX_650669DE6ADA943 ON gradebook_certificate (cat_id);');
        }

        if (false === $schema->hasTable('gradebook_result_attempt')) {
            $this->addSql(
                'CREATE TABLE gradebook_result_attempt (id INT AUTO_INCREMENT NOT NULL, comment LONGTEXT DEFAULT NULL, score DOUBLE PRECISION DEFAULT NULL, result_id INT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC;'
            );

            $this->addSql('ALTER TABLE gradebook_result_attempt ADD CONSTRAINT FK_28B1CC3F7A7B643 FOREIGN KEY (result_id) REFERENCES gradebook_result (id) ON DELETE CASCADE;');
            $this->addSql('CREATE INDEX IDX_28B1CC3F7A7B643 ON gradebook_result_attempt (result_id);');
        } else {
            $table = $schema->getTable('gradebook_result_attempt');
            $this->addSql('UPDATE gradebook_result_attempt SET result_id = NULL WHERE result_id = 0');
            $this->addSql('ALTER TABLE gradebook_result_attempt CHANGE result_id result_id INT DEFAULT NULL');
            if (!$table->hasForeignKey('FK_28B1CC3F7A7B643')) {
                $this->addSql('ALTER TABLE gradebook_result_attempt ADD CONSTRAINT FK_28B1CC3F7A7B643 FOREIGN KEY (result_id) REFERENCES gradebook_result (id) ON DELETE CASCADE;');
            }

            if (!$table->hasIndex('IDX_28B1CC3F7A7B643')) {
                $this->addSql('CREATE INDEX IDX_28B1CC3F7A7B643 ON gradebook_result_attempt (result_id);');
            }
        }

        if (false === $table->hasForeignKey('FK_1F554C7474C99BA2')) {
        }

        $table = $schema->getTable('gradebook_linkeval_log');

        $this->addSql('UPDATE gradebook_linkeval_log SET user_id_log = NULL WHERE user_id_log = 0');
        $this->addSql('ALTER TABLE gradebook_linkeval_log CHANGE user_id_log user_id_log INT DEFAULT NULL');

        if (false === $table->hasForeignKey('FK_1F554C7474C99BA2')) {
            $this->addSql(
                'ALTER TABLE gradebook_linkeval_log ADD CONSTRAINT FK_1F554C7474C99BA2 FOREIGN KEY (user_id_log) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (false === $table->hasIndex('IDX_1F554C7474C99BA2')) {
            $this->addSql('CREATE INDEX IDX_1F554C7474C99BA2 ON gradebook_linkeval_log (user_id_log)');
        }

        $table = $schema->getTable('gradebook_score_log');

        $this->addSql('ALTER TABLE gradebook_score_log CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE gradebook_score_log CHANGE category_id category_id INT DEFAULT NULL;');

        if (false === $table->hasForeignKey('FK_640C6449A76ED395')) {
            $this->addSql(
                'ALTER TABLE gradebook_score_log ADD CONSTRAINT FK_640C6449A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE'
            );
        }

        if (false === $table->hasForeignKey('FK_640C644912469DE2')) {
            $this->addSql(
                'ALTER TABLE gradebook_score_log ADD CONSTRAINT FK_640C644912469DE2 FOREIGN KEY (category_id) REFERENCES gradebook_category (id) ON DELETE CASCADE;'
            );
        }

        if (false === $table->hasIndex('IDX_640C644912469DE2')) {
            $this->addSql('CREATE INDEX IDX_640C644912469DE2 ON gradebook_score_log (category_id);');
        }

        $table = $schema->hasTable('gradebook_comment');
        if (false === $table) {
            $this->addSql('CREATE TABLE gradebook_comment (id BIGINT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, gradebook_id INT DEFAULT NULL, comment LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_C3B70763A76ED395 (user_id), INDEX IDX_C3B70763AD3ED51C (gradebook_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC;');
            $this->addSql('ALTER TABLE gradebook_comment ADD CONSTRAINT FK_C3B70763A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;');
            $this->addSql('ALTER TABLE gradebook_comment ADD CONSTRAINT FK_C3B70763AD3ED51C FOREIGN KEY (gradebook_id) REFERENCES gradebook_category (id) ON DELETE CASCADE;');
        }

        $table = $schema->getTable('gradebook_score_display');
        $this->addSql('ALTER TABLE gradebook_score_display CHANGE category_id category_id INT DEFAULT NULL;');
        $this->addSql('UPDATE gradebook_score_display SET  category_id = NULL WHERE category_id = 0');

        if (false === $table->hasForeignKey('FK_61F7DC8412469DE2')) {
            $this->addSql('ALTER TABLE gradebook_score_display ADD CONSTRAINT FK_61F7DC8412469DE2 FOREIGN KEY (category_id) REFERENCES gradebook_category (id) ON DELETE CASCADE;');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
