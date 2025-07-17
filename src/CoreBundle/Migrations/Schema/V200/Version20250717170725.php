<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20250717170725 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Prepare access_url table for login-only URLs';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE access_url ADD is_login_only TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
