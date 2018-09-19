<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160628220000
 * Integrate the Skype plugin and create new settings current to enable it.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V111
 */
class Version20160628220000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this
            ->connection
            ->executeQuery("UPDATE c_lp_item SET item_type = 'dir' WHERE item_type = 'dokeos_chapter'");
        $this
            ->connection
            ->executeQuery("UPDATE c_lp_item SET item_type = 'dir' WHERE item_type = 'dokeos_module'");
        $this
            ->connection
            ->executeQuery("UPDATE c_lp_item SET item_type = 'dir' WHERE item_type = 'chapter'");
        $this
            ->connection
            ->executeQuery("UPDATE c_lp_item SET item_type = 'dir' WHERE item_type = 'module'");
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
    }
}
