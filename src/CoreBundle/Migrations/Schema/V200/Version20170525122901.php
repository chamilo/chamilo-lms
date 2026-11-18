<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\IntegerType;
use RuntimeException;

/**
 * Resource files are created by Version20170525122900 and may be populated by
 * later V200 legacy migrations, before the V310 schema migration runs.
 * Version20261118170000 applies the same column change on existing platforms.
 */
final class Version20170525122901 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add early BIGINT size for resource files before legacy file migrations.';
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
