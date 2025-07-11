<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20201210100009 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add "publish" field to gradebook_certificate table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gradebook_certificate ADD publish TINYINT(1) DEFAULT 0 NOT NULL;');
    }
}
