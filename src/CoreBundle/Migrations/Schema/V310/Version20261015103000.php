<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261015103000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add an Etherpad identifier to Wiki page versions.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('c_wiki') && !$schema->getTable('c_wiki')->hasColumn('etherpad_id')) {
            $this->addSql('ALTER TABLE c_wiki ADD etherpad_id VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('c_wiki') && $schema->getTable('c_wiki')->hasColumn('etherpad_id')) {
            $this->addSql('ALTER TABLE c_wiki DROP etherpad_id');
        }
    }
}
