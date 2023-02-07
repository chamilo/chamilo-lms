<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Class Version20160712150000
 * Add option to use SVG icons instead of their PNG version
 * @package Application\Migrations\Schema\V111
 */
class Version20230202100000 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('use_course_logo_in_course_page', NULL, 'radio', 'Course', 'false', 'UseCourseLogoInCoursePageTitle', 'UseCourseLogoInCoursePageComment','',NULL, 1)");

        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('use_course_logo_in_course_page', 'true', 'Yes') ");
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('use_course_logo_in_course_page', 'false', 'No') ");
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM settings_current WHERE variable = 'use_course_logo_in_course_page'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'use_course_logo_in_course_page'");
    }
}