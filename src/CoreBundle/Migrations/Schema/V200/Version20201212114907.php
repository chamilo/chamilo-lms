<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Version20201212114912 loads users, and Version20230204150030 sessions, through the ORM, with their course
 * subscriptions, whose mapping includes subscription_date and, for course_rel_user, visibility:
 * V310\Version20261109180000 and V310\Version20261120120000 only add them later.
 * Same definitions and guards, so that these later migrations then leave the columns as they are.
 * See Version20201218132718.
 */
final class Version20201212114907 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add early the subscription dates and course visibility that the V200 ORM hydration of users and sessions needs';
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

        if ($schema->hasTable('course_rel_user') && !$schema->getTable('course_rel_user')->hasColumn('visibility')) {
            $this->addSql('ALTER TABLE course_rel_user ADD visibility INT NOT NULL DEFAULT 1');
        }
    }

    public function down(Schema $schema): void {}
}
