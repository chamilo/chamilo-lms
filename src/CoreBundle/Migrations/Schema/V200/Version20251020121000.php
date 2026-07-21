<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251020121000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Keep certificate file migration outside schema migrations; use the resumable Ricky certificate command';
    }

    public function up(Schema $schema): void
    {
        $pendingMetadata = (int) $this->connection->fetchOne(
            <<<'SQL'
SELECT COUNT(*)
FROM gradebook_certificate
WHERE resource_node_id IS NULL
  AND path_certificate IS NOT NULL
  AND TRIM(path_certificate) <> ''
SQL
        );

        $pendingFiles = (int) $this->connection->fetchOne(
            <<<'SQL'
SELECT COUNT(*)
FROM gradebook_certificate gc
INNER JOIN resource_node rn ON rn.id = gc.resource_node_id
LEFT JOIN resource_file rf ON rf.resource_node_id = rn.id
WHERE gc.resource_node_id IS NOT NULL
  AND gc.path_certificate IS NOT NULL
  AND TRIM(gc.path_certificate) <> ''
  AND rf.id IS NULL
SQL
        );

        $this->write(\sprintf(
            'Certificate schema phase completed. pending_metadata=%d pending_physical_files=%d. Physical files must be processed with chamilo:migration:migrate-ricky-certificate-files.',
            $pendingMetadata,
            $pendingFiles
        ));
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
