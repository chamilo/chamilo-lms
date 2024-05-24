<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20240506164100 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $selectedMailValue = $this->getMailConfigurationValueFromFile('SMTP_UNIQUE_SENDER') ? 'true' : 'false';

        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('smtp_unique_sender', null, null, 'mail', '$selectedMailValue', 'smtp_unique_sender', null, '', null, 1, 1, 1)");

        $selectedMailValue = $this->getMailConfigurationValueFromFile('SMTP_FROM_EMAIL');

        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('smtp_from_email', null, null, 'mail', '$selectedMailValue', 'smtp_from_email', null, '', null, 1, 1, 1)");

        $selectedMailValue = $this->getMailConfigurationValueFromFile('SMTP_FROM_NAME');

        $this->addSql("INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('smtp_from_name', null, null, 'mail', '$selectedMailValue', 'smtp_from_name', null, '', null, 1, 1, 1)");
    }
}
