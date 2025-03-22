<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\HookEvent;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250317170000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Drop tables related to hooks management.';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql("DROP TABLE hook_call");
        $this->addSql("DROP TABLE hook_event");
        $this->addSql("DROP TABLE hook_observer");
    }
}
