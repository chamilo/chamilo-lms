<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260828120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add track_e_attempt_qualify.final to distinguish a draft correction save from a validated one.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track_e_attempt_qualify ADD COLUMN IF NOT EXISTS final TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE track_e_attempt_qualify DROP COLUMN IF EXISTS final');
    }
}
