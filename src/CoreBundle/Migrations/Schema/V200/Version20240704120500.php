<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240704120500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate extra fields to duration field in multiple tables';
    }

    public function up(Schema $schema): void
    {
        $this->migrateStudentPublicationDuration();
        $this->migrateAttendanceCalendarDuration();
    }

    public function down(Schema $schema): void
    {
        // Revert changes if necessary
        $this->addSql('UPDATE c_student_publication SET duration = NULL WHERE duration IS NOT NULL');
        $this->addSql('UPDATE c_attendance_calendar SET duration = NULL WHERE duration IS NOT NULL');
    }

    private function migrateStudentPublicationDuration(): void
    {
        $sql = 'SELECT selected_value FROM settings_current WHERE variable = "considered_working_time" AND selected_value IS NOT NULL AND selected_value != "" AND selected_value != "false"';
        $selectedValue = $this->connection->fetchOne($sql);

        if ($selectedValue) {
            $sql = 'SELECT s.*, efv.field_value
                    FROM c_student_publication s
                    INNER JOIN extra_field_values efv ON s.iid = efv.item_id
                    INNER JOIN extra_field ef ON efv.field_id = ef.id
                    WHERE ef.variable = ? AND ef.item_type = ?';

            $params = [$selectedValue, ExtraField::WORK_FIELD_TYPE];
            $data = $this->connection->fetchAllAssociative($sql, $params);

            foreach ($data as $item) {
                $id = $item['iid'];
                $workTime = (int) $item['field_value'];

                $durationInSeconds = $workTime * 60;

                $this->addSql("UPDATE c_student_publication SET duration = ? WHERE iid = ?", [$durationInSeconds, $id]);
            }
        }
    }

    private function migrateAttendanceCalendarDuration(): void
    {
        $sql = 'SELECT s.*, efv.field_value
                FROM c_attendance_calendar s
                INNER JOIN extra_field_values efv ON s.iid = efv.item_id
                INNER JOIN extra_field ef ON efv.field_id = ef.id
                WHERE ef.variable = "duration" AND ef.item_type = ?';

        $params = [ExtraField::ATTENDANCE_CALENDAR_TYPE];
        $data = $this->connection->fetchAllAssociative($sql, $params);

        foreach ($data as $item) {
            $id = $item['iid'];
            $duration = $item['field_value'];

            $matches = [];
            $newDuration = null;

            if (preg_match('/(\d+)([h:](\d+)?)?/', $duration, $matches)) {
                $hours = (int)$matches[1];
                $minutes = 0;
                if (!empty($matches[3])) {
                    $minutes = (int)$matches[3];
                }
                $newDuration = ($hours * 3600) + ($minutes * 60);
            }

            if ($newDuration !== null) {
                $this->addSql('UPDATE c_attendance_calendar SET duration = ? WHERE iid = ?', [$newDuration, $id]);
            }
        }
    }
}
