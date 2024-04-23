<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20201212114900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add collapsed field to session_rel_user';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable('session_rel_user')->hasColumn('collapsed')) {
            $this->addSql('ALTER TABLE session_rel_user ADD collapsed TINYINT(1) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->getTable('session_rel_user')->hasColumn('collapsed')) {
            $this->addSql('ALTER TABLE session_rel_user DROP COLUMN collapsed');
        }
    }
}
