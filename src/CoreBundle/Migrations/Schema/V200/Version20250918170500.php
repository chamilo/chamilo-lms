<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250918170500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Dropbox: link c_dropbox_file to resource_node with FK + unique index, and set integer defaults.';
    }

    public function up(Schema $schema): void
    {
        // Add resource_node_id and normalize defaults (idempotent at SQL level depends on platform)
        $this->addSql("
            ALTER TABLE c_dropbox_file
                ADD resource_node_id INT DEFAULT NULL,
                CHANGE c_id c_id INT DEFAULT 0 NOT NULL,
                CHANGE filesize filesize INT DEFAULT 0 NOT NULL,
                CHANGE cat_id cat_id INT DEFAULT 0 NOT NULL,
                CHANGE session_id session_id INT DEFAULT 0 NOT NULL
        ");

        // Add FK to resource_node(id)
        $this->addSql("
            ALTER TABLE c_dropbox_file
                ADD CONSTRAINT FK_4D71B46C1BAD783F
                FOREIGN KEY (resource_node_id) REFERENCES resource_node (id) ON DELETE CASCADE
        ");

        // Make the 1:1 explicit (unique on resource_node_id).
        $this->addSql("
            CREATE UNIQUE INDEX UNIQ_4D71B46C1BAD783F ON c_dropbox_file (resource_node_id)
        ");
    }

    public function down(Schema $schema): void
    {
        // Drop unique index + FK + column (best-effort rollback)
        $this->addSql("DROP INDEX UNIQ_4D71B46C1BAD783F ON c_dropbox_file");
        $this->addSql("ALTER TABLE c_dropbox_file DROP FOREIGN KEY FK_4D71B46C1BAD783F");
        $this->addSql("ALTER TABLE c_dropbox_file DROP COLUMN resource_node_id");
    }
}
