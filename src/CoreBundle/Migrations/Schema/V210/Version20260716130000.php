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
        $this->addSql('ALTER TABLE room ADD floor_number INT DEFAULT NULL, ADD capacity INT DEFAULT NULL');

        $this->addSql('ALTER TABLE session_rel_course ADD room_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE session_rel_course ADD CONSTRAINT FK_12D110D354177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_12D110D354177093 ON session_rel_course (room_id)');

        $this->addSql('ALTER TABLE c_attendance ADD room_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_attendance ADD CONSTRAINT FK_4136349254177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4136349254177093 ON c_attendance (room_id)');

        $this->addSql('ALTER TABLE c_attendance_calendar ADD room_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE c_attendance_calendar ADD CONSTRAINT FK_AA3A9AB854177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_AA3A9AB854177093 ON c_attendance_calendar (room_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_attendance_calendar DROP FOREIGN KEY FK_AA3A9AB854177093');
        $this->addSql('DROP INDEX IDX_AA3A9AB854177093 ON c_attendance_calendar');
        $this->addSql('ALTER TABLE c_attendance_calendar DROP room_id');

        $this->addSql('ALTER TABLE c_attendance DROP FOREIGN KEY FK_4136349254177093');
        $this->addSql('DROP INDEX IDX_4136349254177093 ON c_attendance');
        $this->addSql('ALTER TABLE c_attendance DROP room_id');

        $this->addSql('ALTER TABLE session_rel_course DROP FOREIGN KEY FK_12D110D354177093');
        $this->addSql('DROP INDEX IDX_12D110D354177093 ON session_rel_course');
        $this->addSql('ALTER TABLE session_rel_course DROP room_id');

        $this->addSql('ALTER TABLE room DROP floor_number, DROP capacity');
    }
}
