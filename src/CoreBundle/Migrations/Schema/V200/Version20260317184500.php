<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260317184500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Allow multiple c_shortcut rows to reference the same shortcut_node_id.';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement(
            'ALTER TABLE c_shortcut
                DROP INDEX UNIQ_3F6BB957937100BE,
                ADD INDEX IDX_3F6BB957937100BE (shortcut_node_id)'
        );

        $this->write('Changed c_shortcut.shortcut_node_id from UNIQUE index to normal index.');
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement(
            'ALTER TABLE c_shortcut
                DROP INDEX IDX_3F6BB957937100BE,
                ADD UNIQUE INDEX UNIQ_3F6BB957937100BE (shortcut_node_id)'
        );

        $this->write('Restored UNIQUE index on c_shortcut.shortcut_node_id.');
    }
}
