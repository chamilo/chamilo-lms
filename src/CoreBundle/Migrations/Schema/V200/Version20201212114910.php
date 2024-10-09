<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20201212114910 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Make soft-deleteable message_rel_user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE message_rel_user ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)'");
    }
}
