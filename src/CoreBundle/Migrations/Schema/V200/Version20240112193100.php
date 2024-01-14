<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20240112193100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add color field to sys_calendar table';
    }

    public function up(Schema $schema): void
    {
        error_log('Changes fields type bigint by integer');

        if ($schema->hasTable('sys_calendar')) {
            error_log('Perform the changes in the sys_calendar table');
            $this->addSql('ALTER TABLE sys_calendar ADD COLUMN color varchar(20) NULL;');
        }
    }
    public function down(Schema $schema): void
    {
        error_log('Changes fields type bigint by integer');

        if ($schema->hasTable('sys_calendar')) {
            error_log('Perform the changes in the sys_calendar table');
            $this->addSql('ALTER TABLE sys_calendar DROP COLUMN color;');
        }
    }
}
