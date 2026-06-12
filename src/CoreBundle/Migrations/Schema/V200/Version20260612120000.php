<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260612120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add gradebook_category.calculation_mode and gradebook_link.points_one/points_many for configurable grading.';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        $categoryColumns = $sm->listTableColumns('gradebook_category');
        if (!isset($categoryColumns['calculation_mode'])) {
            $this->addSql(
                "ALTER TABLE gradebook_category ADD calculation_mode VARCHAR(32) DEFAULT 'weighted_average' NOT NULL"
            );
        }

        $linkColumns = $sm->listTableColumns('gradebook_link');
        if (!isset($linkColumns['points_one'])) {
            $this->addSql('ALTER TABLE gradebook_link ADD points_one NUMERIC(7, 4) DEFAULT NULL');
        }
        if (!isset($linkColumns['points_many'])) {
            $this->addSql('ALTER TABLE gradebook_link ADD points_many NUMERIC(7, 4) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gradebook_category DROP calculation_mode');
        $this->addSql('ALTER TABLE gradebook_link DROP points_one');
        $this->addSql('ALTER TABLE gradebook_link DROP points_many');
    }
}
