<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261007124500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add scheduling and join URL fields to the generic conference meeting model.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE conference_meeting
                ADD start_at DATETIME DEFAULT NULL,
                ADD end_at DATETIME DEFAULT NULL,
                ADD join_url TEXT DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE conference_meeting
                DROP start_at,
                DROP end_at,
                DROP join_url'
        );
    }
}
