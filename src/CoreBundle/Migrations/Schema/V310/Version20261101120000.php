<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261101120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Store recoverable snapshots of deleted exercise attempts with their answers and related records.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('exercise_attempt_recycle_bin')) {
            return;
        }

        $this->addSql('CREATE TABLE exercise_attempt_recycle_bin (
            id INT AUTO_INCREMENT NOT NULL,
            attempt_id INT NOT NULL,
            exercise_id INT NOT NULL,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            session_id INT DEFAULT NULL,
            deleted_by INT NOT NULL,
            deleted_at DATETIME NOT NULL,
            snapshot_sha256 CHAR(64) NOT NULL,
            snapshot LONGTEXT NOT NULL,
            restored_by INT DEFAULT NULL,
            restored_at DATETIME DEFAULT NULL,
            INDEX idx_exercise_recycle_context (course_id, session_id, exercise_id, restored_at),
            INDEX idx_exercise_recycle_deleted (deleted_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Never drop recoverable student work during a migration rollback.
    }
}
