<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Version20230204150030 loads sessions through the ORM, with their course subscriptions, whose mapping
 * includes subscription_date: V310\Version20261109180000 only adds it later. Same definition and guard,
 * so that this later migration then leaves the column as is. See Version20201218132718.
 */
final class Version20230204150029 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add early the subscription dates that the V200 ORM hydration of sessions needs';
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

    public function down(Schema $schema): void {}
}
