<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260419110300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix course announcement extra field item_type and value_type.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE extra_field
            SET item_type = 21, value_type = 13
            WHERE variable = 'send_notification_at_a_specific_date'
        ");

        $this->addSql("
            UPDATE extra_field
            SET item_type = 21, value_type = 6
            WHERE variable = 'date_to_send_notification'
        ");

        $this->addSql("
            UPDATE extra_field
            SET item_type = 21, value_type = 13
            WHERE variable = 'send_to_users_in_session'
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            UPDATE extra_field
            SET item_type = 21, value_type = 6
            WHERE variable = 'send_notification_at_a_specific_date'
        ");

        $this->addSql("
            UPDATE extra_field
            SET item_type = 21, value_type = 6
            WHERE variable = 'date_to_send_notification'
        ");

        $this->addSql("
            UPDATE extra_field
            SET item_type = 3, value_type = 13
            WHERE variable = 'send_to_users_in_session'
        ");
    }
}
