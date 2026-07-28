<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Service\LearningPath\ScormRuntimeManager;
use Doctrine\DBAL\Schema\Schema;

final class Version20260728150000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add scorm_preferences user extra field to store cmi.learner_preference values (SCORM 2004 runtime only, not user-editable).';
    }

    public function up(Schema $schema): void
    {
        if (!$this->existsExtraField()) {
            $this->addSql(
                "INSERT INTO extra_field (item_type, value_type, variable, display_text, created_at) VALUES (1, 1, '".ScormRuntimeManager::LEARNER_PREFERENCES_FIELD."', 'SCORM learner preferences', NOW())"
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->existsExtraField()) {
            $this->addSql("DELETE FROM extra_field WHERE variable = '".ScormRuntimeManager::LEARNER_PREFERENCES_FIELD."'");
        }
    }

    private function existsExtraField(): bool
    {
        $existingField = $this->connection
            ->executeQuery(
                'SELECT * FROM extra_field WHERE variable = :variable AND item_type = :item_type',
                [
                    'variable' => ScormRuntimeManager::LEARNER_PREFERENCES_FIELD,
                    'item_type' => ExtraField::USER_FIELD_TYPE,
                ]
            )
            ->fetchAssociative()
        ;

        return (bool) $existingField;
    }
}
