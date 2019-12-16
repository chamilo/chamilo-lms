<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160705190000
 * Add accumulate scorm time to c_lp table.
 */
class Version20160705190000 extends AbstractMigrationChamilo
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_lp ADD COLUMN accumulate_scorm_time INT NOT NULL DEFAULT 1');
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE c_lp DROP COLUMN accumulate_scorm_time');
    }
}
