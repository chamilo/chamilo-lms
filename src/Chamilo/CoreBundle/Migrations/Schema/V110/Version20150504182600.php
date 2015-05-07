<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V110;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150504182600
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\v1
 */
class Version20150504182600 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        // Set parent language to Spanish for all close-by languages. Same for Italian,
        // French, Portuguese and Chinese
        $this->addSql("
            UPDATE language SET parent_id = 49 WHERE english_name = 'quechua_cusco'
        ");
        $this->addSql("
            UPDATE language SET parent_id = 49 WHERE english_name = 'galician'
        ");
        $this->addSql("
            UPDATE language SET parent_id = 49 WHERE english_name = 'esperanto'
        ");
        $this->addSql("
            UPDATE language SET parent_id = 49 WHERE english_name = 'catalan'
        ");
        $this->addSql("
            UPDATE language SET parent_id = 49 WHERE english_name = 'asturian'
        ");
        $this->addSql("
            UPDATE language SET parent_id = 28 WHERE english_name = 'friulian'
        ");
        $this->addSql("
            UPDATE language SET parent_id = 18 WHERE english_name = 'occitan'
        ");
        $this->addSql("
            UPDATE language SET parent_id = 40 WHERE english_name = 'brazilian'
        ");
        $this->addSql("
            UPDATE language SET parent_id = 45 WHERE english_name = 'trad_chinese'
        ");
        $this->addSql("
            UPDATE settings_current SET selected_value = '1.10.0.37' WHERE variable = 'chamilo_database_version'
        ");
    }

    /**
     * We don't allow downgrades yet
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("
            UPDATE language SET parent_id = 0 WHERE english_name IN ('trad_chinese', 'brazilian', 'occitan', 'friulian', 'asturian', 'catalan', 'esperanto', 'galician', 'quechua_cusco')
        ");

        $this->addSql("
            UPDATE settings_current SET selected_value = '1.10.0.36' WHERE variable = 'chamilo_database_version'
        ");
    }
}
