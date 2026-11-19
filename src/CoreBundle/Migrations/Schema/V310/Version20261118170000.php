<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\IntegerType;
use RuntimeException;

/**
 * Version20170525122901 changes this column first for migrations from 1.11.
 * This migration upgrades existing platforms where the old V200 phase was
 * executed before BIGINT support was introduced.
 */
final class Version20261118170000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Expand resource_file.size to BIGINT for files larger than 2 GB.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('resource_file') || !$schema->getTable('resource_file')->hasColumn('size')) {
            throw new RuntimeException('The resource_file.size column must exist before importing legacy files.');
        }

        $type = $schema->getTable('resource_file')->getColumn('size')->getType();
        if ($type instanceof BigIntType) {
            return;
        }

        if (!$type instanceof IntegerType) {
            throw new RuntimeException('Unexpected resource_file.size column type.');
        }

        $this->addSql('ALTER TABLE resource_file MODIFY size BIGINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Shrinking the column would lose file sizes above 2 GB.
    }
}
