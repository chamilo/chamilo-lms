<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * A deleted course keeps its row with deleted_at set, so that its id and code are never reused
 * (see DeletedCourseFilter and CourseRepository::deleteCourse()).
 */
final class Version20261115121500 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add course.deleted_at to keep the row of a deleted course';
    }

    public function up(Schema $schema): void
    {
        // V200\Version20201212195010 adds it first on a migration from 1.11.
        if (!$schema->hasTable('course') || $schema->getTable('course')->hasColumn('deleted_at')) {
            return;
        }

        $this->addSql("ALTER TABLE course ADD deleted_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)'");
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('course') || !$schema->getTable('course')->hasColumn('deleted_at')) {
            return;
        }

        // Deleted courses would become visible again: remove their rows first.
        $this->addSql('DELETE FROM course WHERE deleted_at IS NOT NULL');
        $this->addSql('ALTER TABLE course DROP deleted_at');
    }
}
