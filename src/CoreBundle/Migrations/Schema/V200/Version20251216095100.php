<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251216095100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add FK + index for search_engine_ref.resource_node_id (resource_node.id).';
    }

    public function up(Schema $schema): void
    {
        // Ensure the FK can be created (remove orphan references).
        $this->addSql('
            UPDATE search_engine_ref ser
            SET resource_node_id = NULL
            WHERE ser.resource_node_id IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1
                  FROM resource_node rn
                  WHERE rn.id = ser.resource_node_id
              )
        ');

        // Add index for faster joins and stable naming.
        $this->addSql('CREATE INDEX IDX_473F03781BAD783F ON search_engine_ref (resource_node_id)');

        // Add FK constraint.
        $this->addSql('
            ALTER TABLE search_engine_ref
            ADD CONSTRAINT FK_473F03781BAD783F
            FOREIGN KEY (resource_node_id)
            REFERENCES resource_node (id)
            ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE search_engine_ref DROP FOREIGN KEY FK_473F03781BAD783F');
        $this->addSql('DROP INDEX IDX_473F03781BAD783F ON search_engine_ref');
    }
}
