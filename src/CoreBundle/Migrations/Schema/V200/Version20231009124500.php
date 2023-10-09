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

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track_e_attempt_recording CHANGE marks marks DOUBLE PRECISION NOT NULL');
    }
}
