<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250616220500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate timezone from extra_field_values to user.timezone and remove the extra_field.';
    }

    public function up(Schema $schema): void
    {
        // 1. Copy values only when user.timezone is empty
        $this->addSql("
            UPDATE user u
            JOIN extra_field f
              ON f.variable = 'timezone'
             AND f.item_type = 1
            JOIN extra_field_values v
              ON v.field_id = f.id
             AND v.item_id = u.id
            SET u.timezone = v.field_value
            WHERE v.field_value IS NOT NULL
              AND v.field_value <> ''
              AND (u.timezone IS NULL OR u.timezone = '')
        ");

        // 2. Remove values from the extra_field
        $this->addSql("
            DELETE v FROM extra_field_values v
            JOIN extra_field f
              ON f.id = v.field_id
            WHERE f.variable = 'timezone'
              AND f.item_type = 1
        ");

        // 3. Remove the extra_field definition itself
        $this->addSql("
            DELETE FROM extra_field
            WHERE variable = 'timezone'
              AND item_type = 1
        ");
    }

    public function down(Schema $schema): void
    {
        // No rollback: the legacy extra_field will not be recreated
    }
}
