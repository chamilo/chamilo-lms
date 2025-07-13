<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20201210100010 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add auto_forward_video column to c_lp table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_lp ADD auto_forward_video TINYINT(1) DEFAULT 0 NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_lp DROP COLUMN auto_forward_video;');
    }
}
