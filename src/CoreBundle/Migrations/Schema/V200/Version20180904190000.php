<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20180904190000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate sys_announcement';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('sys_announcement');

        if (!$table->hasForeignKey('FK_E4A3EAD473444FD5')) {
            //$this->addSql('');
        }
        if (!$table->hasIndex('IDX_E4A3EAD473444FD5')) {
            //$this->addSql('');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
