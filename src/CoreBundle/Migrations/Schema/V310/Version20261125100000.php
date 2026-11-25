<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * A connection did not record the access URL it happened on, so the reports of one URL also
 * showed the connections a shared user made on another one. On a platform with a single URL
 * every existing connection belongs to it; with several URLs the old ones stay unattributed.
 */
final class Version20261125100000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add the access URL to track_e_login';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('track_e_login') || $schema->getTable('track_e_login')->hasColumn('access_url_id')) {
            return;
        }

        $this->addSql('ALTER TABLE track_e_login ADD access_url_id INT DEFAULT NULL');

        $urlIds = $this->connection->fetchFirstColumn('SELECT id FROM access_url');
        if (1 === \count($urlIds)) {
            $this->addSql(
                'UPDATE track_e_login SET access_url_id = :url WHERE access_url_id IS NULL',
                ['url' => (int) $urlIds[0]]
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('track_e_login') && $schema->getTable('track_e_login')->hasColumn('access_url_id')) {
            $this->addSql('ALTER TABLE track_e_login DROP access_url_id');
        }
    }
}
