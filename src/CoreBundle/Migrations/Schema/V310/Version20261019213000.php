<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261019213000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Store chronological learner answer changes without replacing exercise attempt results.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('track_e_attempt_log')) {
            return;
        }

        $this->addSql('CREATE TABLE track_e_attempt_log (
            id INT AUTO_INCREMENT NOT NULL,
            exe_id INT NOT NULL,
            question_id INT NOT NULL,
            event_type VARCHAR(16) NOT NULL,
            answer LONGTEXT NOT NULL,
            seconds_spent INT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_track_e_attempt_log_exe_question (exe_id, question_id),
            PRIMARY KEY(id),
            CONSTRAINT fk_track_e_attempt_log_exercise FOREIGN KEY (exe_id)
                REFERENCES track_e_exercises (exe_id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Removing answer history would destroy learner activity records.');
    }
}
