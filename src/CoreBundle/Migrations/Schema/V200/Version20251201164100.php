<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251201164100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add parent_id column to resource_link to store per-context document hierarchy.';
    }

    public function up(Schema $schema): void
    {
        // Add parent_id column on resource_link
        $this->addSql('ALTER TABLE resource_link ADD parent_id INT DEFAULT NULL');

        // Add foreign key to self (hierarchical links)
        $this->addSql(
            'ALTER TABLE resource_link
             ADD CONSTRAINT FK_398C394B727ACA70
             FOREIGN KEY (parent_id) REFERENCES resource_link (id)
             ON DELETE SET NULL'
        );

        // Add index for faster lookups by parent
        $this->addSql('CREATE INDEX idx_resource_link_parent ON resource_link (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key, index and column to rollback schema change
        $this->addSql('ALTER TABLE resource_link DROP FOREIGN KEY FK_398C394B727ACA70');
        $this->addSql('DROP INDEX idx_resource_link_parent ON resource_link');
        $this->addSql('ALTER TABLE resource_link DROP parent_id');
    }
}
