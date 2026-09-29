<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260618123000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add progressive adaptive exercise category destinations.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_quiz_rel_category')) {
            $table = $schema->getTable('c_quiz_rel_category');
            if (!$table->hasColumn('destinations')) {
                $this->addSql('ALTER TABLE c_quiz_rel_category ADD destinations LONGTEXT DEFAULT NULL');
            }
        }

        if (!$schema->hasTable('c_quiz_destination_result')) {
            $this->addSql('CREATE TABLE c_quiz_destination_result (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, exe_id INT DEFAULT NULL, achieved_level VARCHAR(255) NOT NULL, hash VARCHAR(255) NOT NULL, INDEX IDX_7A2F95B6A76ED395 (user_id), INDEX IDX_7A2F95B6B5A18F57 (exe_id), UNIQUE INDEX UNIQ_7A2F95B6D1B862B8 (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE c_quiz_destination_result ADD CONSTRAINT FK_7A2F95B6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE c_quiz_destination_result ADD CONSTRAINT FK_7A2F95B6B5A18F57 FOREIGN KEY (exe_id) REFERENCES track_e_exercises (exe_id) ON DELETE CASCADE');
        }

        if ($schema->hasTable('settings')) {
            $this->upsertProgressiveAdaptiveSetting('settings');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            $this->deleteProgressiveAdaptiveSetting('settings');
        }

        if ($schema->hasTable('c_quiz_destination_result')) {
            $this->addSql('DROP TABLE c_quiz_destination_result');
        }

        if ($schema->hasTable('c_quiz_rel_category')) {
            $table = $schema->getTable('c_quiz_rel_category');
            if ($table->hasColumn('destinations')) {
                $this->addSql('ALTER TABLE c_quiz_rel_category DROP destinations');
            }
        }
    }

    private function upsertProgressiveAdaptiveSetting(string $table): void
    {
        $this->addSql(
            'INSERT INTO '.$table.' (variable, category, title, comment, selected_value, access_url_changeable) '.
            'SELECT ?, ?, ?, ?, ?, 0 WHERE NOT EXISTS (SELECT 1 FROM '.$table.' WHERE variable = ?)',
            [
                'quiz_question_category_destinations',
                'exercise',
                'Enable progressive adaptive tests by category destination',
                'Enable progressive adaptive tests where each question category can redirect learners to another category depending on their score.',
                'false',
                'quiz_question_category_destinations',
            ]
        );
    }

    private function deleteProgressiveAdaptiveSetting(string $table): void
    {
        $this->addSql(
            'DELETE FROM '.$table.' WHERE variable = ? AND category = ?',
            [
                'quiz_question_category_destinations',
                'exercise',
            ]
        );
    }
}
