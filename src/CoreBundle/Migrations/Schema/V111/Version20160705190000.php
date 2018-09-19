<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160705190000
 * Add accumulate scorm time to c_lp table.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V111
 */
class Version20160705190000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE c_lp ADD COLUMN accumulate_scorm_time INT NOT NULL DEFAULT 1");
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        $this->addSql("ALTER TABLE c_lp DROP COLUMN accumulate_scorm_time");
    }
}
