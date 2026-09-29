<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Configurable grading columns for the 3.0 series.
 *
 * GradebookCategory::$calculationMode and GradebookLink::$pointsOne/$pointsMany
 * are already part of the current mapping, so any migration that hydrates those
 * entities through the ORM crashes on "Unknown column" while the columns are
 * missing. Duplicated in V200/Version20201210100014, which creates them early
 * enough for a full migrate-from-legacy run, each block guarded so it is a
 * no-op once that earlier migration - itself guarded the same way - has already
 * run.
 */
final class Version20260805180000 extends AbstractMigrationChamilo
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
