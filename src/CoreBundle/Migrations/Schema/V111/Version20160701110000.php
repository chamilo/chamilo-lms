<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160701110000
 * Add option to remove splash screen on course creation.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V111
 */
class Version20160701110000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('course_creation_splash_screen',NULL,'radio','Course','true','CourseCreationSplashScreenTitle','CourseCreationSplashScreenComment','',NULL, 1)");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('course_creation_splash_screen','true','Yes') ");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('course_creation_splash_screen','false','No') ");
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM settings_current WHERE variable = 'course_creation_splash_screen'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'course_creation_splash_screen'");
    }
}
