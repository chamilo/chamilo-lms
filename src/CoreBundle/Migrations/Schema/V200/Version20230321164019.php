<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20230321164019 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate track_e_attempt_recording with an idempotent INSERT SELECT';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_attempt_recording') || !$schema->hasTable('track_e_attempt_qualify')) {
            return;
        }

        // Preserve decimal marks before copying. The historical source column
        // was converted to DOUBLE in the immediately preceding migration.
        $this->connection->executeStatement(
            'ALTER TABLE track_e_attempt_qualify MODIFY COLUMN marks DOUBLE PRECISION NOT NULL'
        );

        $before = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM track_e_attempt_qualify');

        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO track_e_attempt_qualify (
    id,
    exe_id,
    question_id,
    marks,
    insert_date,
    author,
    teacher_comment,
    session_id,
    answer
)
SELECT
    recording.id,
    recording.exe_id,
    recording.question_id,
    recording.marks,
    recording.insert_date,
    recording.author,
    recording.teacher_comment,
    recording.session_id,
    recording.answer
FROM track_e_attempt_recording recording
INNER JOIN track_e_exercises exercise
    ON exercise.exe_id = recording.exe_id
LEFT JOIN track_e_attempt_qualify migrated
    ON migrated.id = recording.id
WHERE migrated.id IS NULL
SQL
        );

        $after = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM track_e_attempt_qualify');
        $this->getLogger()->info('Attempt recording migration completed.', [
            'before' => $before,
            'after' => $after,
            'inserted' => $after - $before,
        ]);
    }

    public function down(Schema $schema): void {}
}
