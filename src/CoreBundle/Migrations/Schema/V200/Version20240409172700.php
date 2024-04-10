<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240409172700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename c_exercise_category table to c_quiz_category and update c_quiz table accordingly.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_exercise_category')) {
            $this->addSql('RENAME TABLE c_exercise_category TO c_quiz_category');
        }

        if ($schema->hasTable('c_quiz')) {
            $quizTable = $schema->getTable('c_quiz');

            if ($quizTable->hasColumn('exercise_category_id')) {
                $this->addSql('ALTER TABLE c_quiz DROP FOREIGN KEY FK_B7A1C35FB48D66');
                $this->addSql('DROP INDEX IDX_B7A1C35FB48D66 ON c_quiz');
                $this->addSql('ALTER TABLE c_quiz CHANGE exercise_category_id quiz_category_id INT DEFAULT NULL');
            }

            $this->addSql('ALTER TABLE c_quiz ADD CONSTRAINT FK_B7A1C33D608E42 FOREIGN KEY (quiz_category_id) REFERENCES c_quiz_category(id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_B7A1C33D608E42 ON c_quiz (quiz_category_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_quiz_category')) {
            $this->addSql('RENAME TABLE c_quiz_category TO c_exercise_category');
        }

        if ($schema->hasTable('c_quiz')) {
            $quizTable = $schema->getTable('c_quiz');

            if ($quizTable->hasColumn('quiz_category_id')) {
                $this->addSql('ALTER TABLE c_quiz DROP FOREIGN KEY FK_B7A1C33D608E42');
                $this->addSql('DROP INDEX IDX_B7A1C33D608E42 ON c_quiz');
                $this->addSql('ALTER TABLE c_quiz CHANGE quiz_category_id exercise_category_id INT DEFAULT NULL');
            }

            $this->addSql('ALTER TABLE c_quiz ADD CONSTRAINT FK_B7A1C35FB48D66 FOREIGN KEY (exercise_category_id) REFERENCES c_exercise_category(id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_B7A1C35FB48D66 ON c_quiz (exercise_category_id)');
        }
    }
}
