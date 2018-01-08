<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V111;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20171002154600
 *
 * Added a new option in registration settings called "confirmation"
 * This option prevents the new user to login in the platform if your account is not
 * confirmed via email.
 * @package Application\Migrations\Schema\V111
 */
class Version20171002154600 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_registration', 'confirmation', 'MailConfirmation')");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE settings_options WHERE variable='allow_registration' AND value='confirmation' AND display_text='MailConfirmation'");
    }
}
