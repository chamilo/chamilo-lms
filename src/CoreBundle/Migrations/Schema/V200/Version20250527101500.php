<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250527101500 extends AbstractMigrationChamilo
{

    public function getDescription(): string
    {
        return 'Add index on the resource_type.title column';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_title ON resource_type (title);');
    }
}
