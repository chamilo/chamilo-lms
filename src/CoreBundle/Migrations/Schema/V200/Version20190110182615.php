<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20190110182615.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V200
 */
class Version20190110182615 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            'ALTER TABLE resource_link DROP FOREIGN KEY FK_398C394BFE54D947;');
        $this->addSql(
            'ALTER TABLE resource_link ADD CONSTRAINT FK_398C394BFE54D947
            FOREIGN KEY (group_id) REFERENCES c_group_info (iid) ON DELETE CASCADE;'
        );
        $this->addSql(
            'ALTER TABLE resource_right DROP
            FOREIGN KEY FK_9F710F26F004E599;'
        );
        $this->addSql(
            'ALTER TABLE resource_right ADD CONSTRAINT FK_9F710F26F004E599
            FOREIGN KEY (resource_link_id) REFERENCES resource_link (id) ON DELETE CASCADE;'
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
