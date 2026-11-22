<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Keep historical survey-answer timestamps unknown instead of claiming that
 * existing responses were submitted when this migration was installed.
 */
final class Version20261120000000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Record creation and update times for individual survey answers.';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('c_survey_answer')) {
            throw new RuntimeException('The c_survey_answer table must exist before adding timestamps.');
        }

        $table = $schema->getTable('c_survey_answer');

        // Add nullable columns first: existing answers must keep NULL timestamps.
        if (!$table->hasColumn('created_at')) {
            $this->addSql('ALTER TABLE c_survey_answer ADD created_at DATETIME NULL DEFAULT NULL');
        }

        if (!$table->hasColumn('updated_at')) {
            $this->addSql('ALTER TABLE c_survey_answer ADD updated_at DATETIME NULL DEFAULT NULL');
        }

        // Only subsequent INSERT/UPDATE statements receive automatic timestamps.
        // The same defaults are declared on the entity for fresh installations.
        $this->addSql('ALTER TABLE c_survey_answer MODIFY created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE c_survey_answer MODIFY updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // Keep collected timestamps on rollback; older application versions ignore these columns.
    }
}
