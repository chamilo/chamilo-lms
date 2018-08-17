<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160713180000
 * Add option to use SVG icons instead of their PNG version
 * @package Application\Migrations\Schema\V111
 */
class Version20160713180000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql("DELETE FROM settings_current WHERE variable = 'allow_browser_sniffer'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'allow_browser_sniffer'");
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('allow_browser_sniffer', NULL, 'radio', 'Tuning', 'false', 'AllowBrowserSnifferTitle','AllowBrowserSnifferComment',NULL,NULL, 0)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_browser_sniffer','true','Yes') ");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_browser_sniffer','false','No') ");
    }
}