<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Runs before Version20201216110722, which fully hydrates CAttendance via the
 * ORM (->find()). The current CAttendance mapping includes `room` (room_id),
 * but that column is only added on the DB side much later by
 * V210/Version20260716130000 - so it must be created here for a full
 * migrate-from-legacy run to reach that point without crashing on
 * "Unknown column 'room_id'". Guarded so it's a no-op if that later
 * migration (or this one) has already run.
 */
final class Version20201210100013 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add c_attendance.room_id early (needed by a later migration\'s ORM hydration).';
    }

    public function up(Schema $schema): void
    {
        if ($schema->getTable('c_attendance')->hasColumn('room_id')) {
            return;
        }

        $this->addSql('ALTER TABLE c_attendance ADD room_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_attendance ADD CONSTRAINT FK_4136349254177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4136349254177093 ON c_attendance (room_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_attendance DROP FOREIGN KEY FK_4136349254177093');
        $this->addSql('DROP INDEX IDX_4136349254177093 ON c_attendance');
        $this->addSql('ALTER TABLE c_attendance DROP room_id');
    }
}
