<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260419193500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove obsolete legacy settings for attendance, certificate and display.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM settings
            WHERE
                (category = 'attendance' AND variable IN (
                    'attendance_calendar_set_duration'
                ))
                OR
                (category = 'certificate' AND variable IN (
                    'add_gradebook_certificates_cron_task_enabled'
                ))
                OR
                (category = 'display' AND variable IN (
                    'hide_home_top_when_connected',
                    'show_administrator_data',
                    'show_teacher_data',
                    'show_tutor_data'
                ))
        ");
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty.
        // Recreating deleted settings safely would require restoring
        // their registration metadata and original defaults together.
    }
}
