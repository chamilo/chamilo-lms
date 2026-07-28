<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260728140000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add c_lp_iv_objective.progress_measure and c_lp_iv_interaction.description for SCORM 2004 runtime support.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_lp_iv_objective ADD COLUMN IF NOT EXISTS progress_measure DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE c_lp_iv_interaction ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_lp_iv_objective DROP COLUMN IF EXISTS progress_measure');
        $this->addSql('ALTER TABLE c_lp_iv_interaction DROP COLUMN IF EXISTS description');
    }
}
