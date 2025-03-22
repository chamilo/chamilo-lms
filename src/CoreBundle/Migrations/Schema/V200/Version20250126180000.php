<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250126180000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update the presence column in c_attendance_sheet from TINYINT(1) to INT to allow multiple attendance statuses.';
    }

    public function up(Schema $schema): void
    {
        // Alter the column type to integer
        $this->addSql('ALTER TABLE c_attendance_sheet MODIFY presence INT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert the column type back to TINYINT(1)
        $this->addSql('ALTER TABLE c_attendance_sheet MODIFY presence TINYINT(1) NOT NULL');
    }
}
