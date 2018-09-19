<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160519201900
 * Update the subkeytext of some settings, previously preventing translations.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V111
 */
class Version20160519201900 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE settings_current SET subkeytext = 'Name' WHERE variable = 'profile' AND subkey = 'name'");
        $this->addSql("UPDATE settings_current SET subkeytext = 'OfficialCode' WHERE variable = 'profile' AND subkey = 'officialcode'");
        $this->addSql("UPDATE settings_current SET subkeytext = 'Phone' WHERE variable = 'profile' AND subkey = 'phone'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("UPDATE settings_current SET subkeytext = 'name' WHERE variable = 'profile' AND subkey = 'name'");
        $this->addSql("UPDATE settings_current SET subkeytext = 'officialcode' WHERE variable = 'profile' AND subkey = 'officialcode'");
        $this->addSql("UPDATE settings_current SET subkeytext = 'phone' WHERE variable = 'profile' AND subkey = 'phone'");
    }
}
