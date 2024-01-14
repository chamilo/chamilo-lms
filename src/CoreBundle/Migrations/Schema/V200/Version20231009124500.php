<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20231009124500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Changes for track_e_attempt_recording';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track_e_attempt_recording CHANGE marks marks DOUBLE PRECISION NOT NULL');

        $this->addSql('DELETE FROM track_e_attempt_recording WHERE exe_id NOT IN (SELECT exe_id FROM track_e_exercises)');
        $this->addSql('ALTER TABLE track_e_attempt_recording ADD CONSTRAINT FK_369B2007B5A18F57 FOREIGN KEY (exe_id) REFERENCES track_e_exercises (exe_id)');
    }
}
