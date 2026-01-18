<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Helpers\ScimHelper;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20251231115400 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add extra field for SCIM integration';
    }

    public function up(Schema $schema): void
    {
        if (!$this->existsExtraField()) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, created_at) VALUES (1, 1, '".ScimHelper::SCIM_FIELD."', 'SCIM external ID', NOW())"
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->existsExtraField()) {
            $this->addSql("DELETE FROM extra_field WHERE variable = '".ScimHelper::SCIM_FIELD."'");
        }
    }

    private function existsExtraField(): bool
    {
        $existingField = $this->connection
            ->executeQuery(
                'SELECT * FROM extra_field WHERE variable = :variable AND item_type = :item_type',
                [
                    'variable' => ScimHelper::SCIM_FIELD,
                    'item_type' => ExtraField::USER_FIELD_TYPE,
                ]
            )
            ->fetchAssociative()
        ;

        return (bool) $existingField;
    }
}
