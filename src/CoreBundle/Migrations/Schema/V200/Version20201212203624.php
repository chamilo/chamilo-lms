<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20201212203624 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add certificate_validity_period column to gradebook_category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gradebook_category ADD certificate_validity_period INT DEFAULT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gradebook_category DROP COLUMN certificate_validity_period;');
    }
}
