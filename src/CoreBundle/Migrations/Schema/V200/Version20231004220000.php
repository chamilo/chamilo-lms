<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Exception;

class Version20231004220000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Drop fields not used in this version in table sys_announcement';
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('sys_announcement');
        if ($table->hasColumn('visible_teacher')) {
            $this->addSql('ALTER TABLE sys_announcement DROP visible_teacher');
        }
        if ($table->hasColumn('visible_student')) {
            $this->addSql('ALTER TABLE sys_announcement DROP visible_student');
        }
        if ($table->hasColumn('visible_guest')) {
            $this->addSql('ALTER TABLE sys_announcement DROP visible_guest');
        }
    }
}
