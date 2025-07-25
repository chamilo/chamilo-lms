<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250725104800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove noreply_email_address setting';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings WHERE variable = 'noreply_email_address'");
    }
}
