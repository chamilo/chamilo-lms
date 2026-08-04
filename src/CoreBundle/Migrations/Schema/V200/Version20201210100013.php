<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Runs before Version20201216110722, which fully hydrates CAttendance/Course
 * via the ORM (->find()). Both entities' current mapping includes a `room`
 * (room_id) column, but on the DB side these are only added much later by
 * V210/Version20260716130000 - so they must exist here for a full
 * migrate-from-legacy run to reach that point without crashing on
 * "Unknown column 'room_id'".
 *
 * Duplicates the full body of V210/Version20260716130000 (room metadata +
 * every room_id column/FK/index it adds), each block guarded so it's a no-op
 * once that later migration - itself guarded the same way - has already run.
 */
final class Version20201210100013 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add room metadata and room_id columns early (needed by a later migration\'s ORM hydration).';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('room')->hasColumn('floor_number')) {
            $this->addSql('ALTER TABLE room ADD floor_number INT DEFAULT NULL, ADD capacity INT DEFAULT NULL');
        }

        if (!$schema->getTable('session_rel_course')->hasColumn('room_id')) {
            $this->addSql('ALTER TABLE session_rel_course ADD room_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE session_rel_course ADD CONSTRAINT FK_12D110D354177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_12D110D354177093 ON session_rel_course (room_id)');
        }

        if (!$schema->getTable('c_attendance')->hasColumn('room_id')) {
            $this->addSql('ALTER TABLE c_attendance ADD room_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_attendance ADD CONSTRAINT FK_4136349254177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_4136349254177093 ON c_attendance (room_id)');
        }

        if (!$schema->getTable('c_attendance_calendar')->hasColumn('room_id')) {
            $this->addSql('ALTER TABLE c_attendance_calendar ADD room_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE c_attendance_calendar ADD CONSTRAINT FK_AA3A9AB854177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_AA3A9AB854177093 ON c_attendance_calendar (room_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('c_attendance_calendar')->hasColumn('room_id')) {
            $this->addSql('ALTER TABLE c_attendance_calendar DROP FOREIGN KEY FK_AA3A9AB854177093');
            $this->addSql('DROP INDEX IDX_AA3A9AB854177093 ON c_attendance_calendar');
            $this->addSql('ALTER TABLE c_attendance_calendar DROP room_id');
        }

        if ($schema->getTable('c_attendance')->hasColumn('room_id')) {
            $this->addSql('ALTER TABLE c_attendance DROP FOREIGN KEY FK_4136349254177093');
            $this->addSql('DROP INDEX IDX_4136349254177093 ON c_attendance');
            $this->addSql('ALTER TABLE c_attendance DROP room_id');
        }

        if ($schema->getTable('session_rel_course')->hasColumn('room_id')) {
            $this->addSql('ALTER TABLE session_rel_course DROP FOREIGN KEY FK_12D110D354177093');
            $this->addSql('DROP INDEX IDX_12D110D354177093 ON session_rel_course');
            $this->addSql('ALTER TABLE session_rel_course DROP room_id');
        }

        if ($schema->getTable('room')->hasColumn('floor_number')) {
            $this->addSql('ALTER TABLE room DROP floor_number, DROP capacity');
        }
    }
}
