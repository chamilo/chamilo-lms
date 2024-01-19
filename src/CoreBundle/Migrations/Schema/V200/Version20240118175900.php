<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240118175900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add text_when_finished_failure field to c_quiz';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_quiz ADD COLUMN text_when_finished_failure longtext DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE c_quiz DROP COLUMN text_when_finished_failure');
    }
}
