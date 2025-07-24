<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250724115010 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove show_conditions_to_user setting and gdpr + my_terms extra_fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM settings WHERE variable = 'show_conditions_to_user'");

        $userType = ExtraField::USER_FIELD_TYPE;
        $this->addSql("
            DELETE FROM extra_field_values
            WHERE field_id IN (
                SELECT id FROM extra_field
                WHERE item_type = $userType
                  AND variable IN ('gdpr','my_terms')
            )
        ");

        $this->addSql("
            DELETE FROM extra_field
            WHERE item_type = $userType
              AND variable IN ('gdpr','my_terms')
        ");
    }

    public function down(Schema $schema): void {}
}
