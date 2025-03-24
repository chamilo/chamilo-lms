<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250305123000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Sets visible_to_self and changeable to 0 for extra fields migrated from configuration.php.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            UPDATE extra_field
            SET visible_to_self = 0, changeable = 0
            WHERE variable IN (
                'session_courses_read_only_mode',
                'is_mandatory',
                'show_in_catalogue',
                'multiple_language',
                'send_notification_at_a_specific_date',
                'date_to_send_notification',
                'send_to_users_in_session',
                'tags',
                'acquisition',
                'invisible',
                'start_date',
                'end_date'
            );
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            UPDATE extra_field
            SET visible_to_self = 1, changeable = 1
            WHERE variable IN (
                'session_courses_read_only_mode',
                'is_mandatory',
                'show_in_catalogue',
                'multiple_language',
                'send_notification_at_a_specific_date',
                'date_to_send_notification',
                'send_to_users_in_session',
                'tags',
                'acquisition',
                'invisible',
                'start_date',
                'end_date'
            );
        ");
    }
}
