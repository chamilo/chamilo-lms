<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260611183000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add learning path completion date support.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_lp_view')) {
            return;
        }

        $table = $schema->getTable('c_lp_view');

        if (!$table->hasColumn('compdate')) {
            $this->addSql('ALTER TABLE c_lp_view ADD compdate DATE DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Intentionally left empty.
        // The completion date can be part of migrated tracking/legal evidence.
    }
}
