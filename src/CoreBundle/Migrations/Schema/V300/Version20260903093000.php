<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260903093000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Preserve geographic coordinate precision for branches';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('branch_sync')) {
            return;
        }

        $this->addSql(
            'ALTER TABLE branch_sync '
            .'CHANGE latitude latitude DECIMAL(10, 8) DEFAULT NULL, '
            .'CHANGE longitude longitude DECIMAL(11, 8) DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('branch_sync')) {
            return;
        }

        $this->addSql(
            'ALTER TABLE branch_sync '
            .'CHANGE latitude latitude DECIMAL(10, 0) DEFAULT NULL, '
            .'CHANGE longitude longitude DECIMAL(10, 0) DEFAULT NULL'
        );
    }
}
