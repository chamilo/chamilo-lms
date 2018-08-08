<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160929120000
 * Change tables engine to InnoDB
 * @package Application\Migrations\Schema\V111
 */
class Version20160929120000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        error_log('Version20160929120000');
        $this->addSql("ALTER TABLE c_tool ADD INDEX idx_ctool_name (name(20))");
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        foreach ($this->names as $name) {
            if (!$schema->hasTable($name)) {
                continue;
            }

            $this->addSql("ALTER TABLE c_tool DROP INDEX idx_ctool_name");
        }
    }
}
