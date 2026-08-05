<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260716130000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add room metadata and room assignments for session courses and attendances.';
    }

    public function up(Schema $schema): void
    {
        // Every block below may already exist: Version20201210100013 (V200)
        // creates the same room metadata/room_id columns early, because a
        // full migrate-from-legacy run needs them before that point in the
        // sequence (ORM hydration of CAttendance/Course via ->find()).
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
