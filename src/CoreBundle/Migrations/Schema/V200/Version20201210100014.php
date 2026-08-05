<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds the configurable grading columns early in the migrate-from-legacy
 * sequence. GradebookCategory::$calculationMode and
 * GradebookLink::$pointsOne/$pointsMany are already part of the current
 * mapping, but on the DB side they are only added much later by
 * V300/Version20260805180000 - so they must exist here for a full
 * migrate-from-legacy run to reach that point without crashing on
 * "Unknown column" whenever a migration hydrates either entity through the ORM.
 *
 * Duplicates the full body of V300/Version20260805180000, each block guarded so
 * it is a no-op once that later migration - itself guarded the same way - has
 * already run.
 */
final class Version20201210100014 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add gradebook_category.calculation_mode and gradebook_link.points_one/points_many for configurable grading.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('gradebook_category')->hasColumn('calculation_mode')) {
            $this->addSql(
                "ALTER TABLE gradebook_category ADD calculation_mode VARCHAR(32) DEFAULT 'weighted_average' NOT NULL"
            );
        }

        if (!$schema->getTable('gradebook_link')->hasColumn('points_one')) {
            $this->addSql('ALTER TABLE gradebook_link ADD points_one NUMERIC(7, 4) DEFAULT NULL');
        }

        if (!$schema->getTable('gradebook_link')->hasColumn('points_many')) {
            $this->addSql('ALTER TABLE gradebook_link ADD points_many NUMERIC(7, 4) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('gradebook_link')->hasColumn('points_many')) {
            $this->addSql('ALTER TABLE gradebook_link DROP points_many');
        }

        if ($schema->getTable('gradebook_link')->hasColumn('points_one')) {
            $this->addSql('ALTER TABLE gradebook_link DROP points_one');
        }

        if ($schema->getTable('gradebook_category')->hasColumn('calculation_mode')) {
            $this->addSql('ALTER TABLE gradebook_category DROP calculation_mode');
        }
    }
}
