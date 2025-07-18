<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250718182400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove the rssfeeds field from the extra_field table and related entries in extra_field_values';
    }

    public function up(Schema $schema): void
    {
        $extraFieldType = ExtraField::USER_FIELD_TYPE;

        $this->addSql("DELETE FROM extra_field_values WHERE field_id IN (
            SELECT id FROM extra_field
            WHERE item_type = $extraFieldType AND variable = 'rssfeeds'
        )");

        $this->addSql("DELETE FROM extra_field WHERE variable = 'rssfeeds' AND item_type = $extraFieldType");
    }

    public function down(Schema $schema): void {}
}
