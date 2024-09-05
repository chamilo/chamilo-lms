<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20240715183456 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Remove the special_course field from the extra_field table and related entries in extra_field_values';
    }

    public function up(Schema $schema): void
    {
        $extraFieldType = ExtraField::COURSE_FIELD_TYPE;

        // Delete entries in extra_field_values
        $this->addSql("DELETE FROM extra_field_values WHERE field_id IN (
            SELECT id FROM extra_field
            WHERE item_type = $extraFieldType AND variable = 'special_course'
        )");

        // Delete the extra_field
        $this->addSql("DELETE FROM extra_field WHERE variable = 'special_course' AND item_type = $extraFieldType");
    }

    public function down(Schema $schema): void {}
}
