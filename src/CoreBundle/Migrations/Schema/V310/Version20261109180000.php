<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20261109180000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Record course and session-course subscription dates for GH-1651';
    }

    public function up(Schema $schema): void
    {
        foreach (['course_rel_user', 'session_rel_course_rel_user'] as $tableName) {
            if (!$schema->hasTable($tableName) || $schema->getTable($tableName)->hasColumn('subscription_date')) {
                continue;
            }

            // Old records must remain unknown; the default only applies to new legacy SQL inserts.
            $this->addSql('ALTER TABLE '.$tableName.' ADD COLUMN subscription_date DATETIME DEFAULT NULL');
            $this->addSql('ALTER TABLE '.$tableName.' ALTER COLUMN subscription_date SET DEFAULT CURRENT_TIMESTAMP');
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['course_rel_user', 'session_rel_course_rel_user'] as $tableName) {
            if ($schema->hasTable($tableName) && $schema->getTable($tableName)->hasColumn('subscription_date')) {
                $this->addSql('ALTER TABLE '.$tableName.' DROP COLUMN subscription_date');
            }
        }
    }
}
