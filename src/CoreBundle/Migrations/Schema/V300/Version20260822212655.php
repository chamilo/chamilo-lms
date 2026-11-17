<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Version20260822212700 creates the demo courses through the ORM, whose Course mapping includes
 * course.language_id, which V310\Version20261005120000 creates for real. V200\Version20201212195010
 * adds it early on an upgrade from 1.11, but a platform that only runs the migrations from V300 on
 * never gets there, and Version20260822212650 then leaves course_language required because
 * language_id is missing.
 */
final class Version20260822212655 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add early the course language column that the V300 demo courses need on a platform that skipped V200';
    }

    public function up(Schema $schema): void
    {
        $courseTable = $schema->getTable('course');
        if ($courseTable->hasColumn('language_id') || !$courseTable->hasColumn('course_language')) {
            return;
        }

        // Nullable until V310\Version20261005120000 refills it, enforces it and drops course_language
        $this->addSql('ALTER TABLE course ADD language_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE course MODIFY course_language VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void {}
}
