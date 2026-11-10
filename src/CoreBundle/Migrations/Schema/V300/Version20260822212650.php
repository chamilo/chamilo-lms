<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Version20260822212700 creates the demo courses through the ORM, which only writes course.language_id:
 * on a migration from 1.11, course_language still exists until V310\Version20261005120000 drops it, and
 * rejects these courses as long as it is required.
 */
final class Version20260822212650 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Make course.course_language optional for the courses created before its removal';
    }

    public function up(Schema $schema): void
    {
        $courseTable = $schema->getTable('course');
        if (!$courseTable->hasColumn('course_language') || !$courseTable->hasColumn('language_id')) {
            return;
        }

        $this->addSql('ALTER TABLE course MODIFY course_language VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void {}
}
