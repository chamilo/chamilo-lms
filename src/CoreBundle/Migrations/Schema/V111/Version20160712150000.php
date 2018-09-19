<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160712150000
 * Add option to use SVG icons instead of their PNG version.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V111
 */
class Version20160712150000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('icons_mode_svg', NULL, 'radio', 'Tuning', 'false', 'IconsModeSVGTitle','IconsModeSVGComment','',NULL, 1)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('icons_mode_svg','true','Yes') ");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('icons_mode_svg','false','No') ");
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM settings_current WHERE variable = 'icons_mode_svg'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'icons_mode_svg'");
    }
}
