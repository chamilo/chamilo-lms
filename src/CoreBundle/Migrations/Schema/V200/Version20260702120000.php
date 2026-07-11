<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260702120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add c_lp_item_view.progress for SCORM 2004 cmi.progress_measure tracking.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_lp_item_view')) {
            return;
        }

        $table = $schema->getTable('c_lp_item_view');
        if (!$table->hasColumn('progress')) {
            $this->addSql('ALTER TABLE c_lp_item_view ADD progress DOUBLE PRECISION DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('c_lp_item_view')) {
            return;
        }

        $table = $schema->getTable('c_lp_item_view');
        if ($table->hasColumn('progress')) {
            $this->addSql('ALTER TABLE c_lp_item_view DROP progress');
        }
    }
}
