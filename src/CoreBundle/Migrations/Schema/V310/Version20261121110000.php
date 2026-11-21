<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Update the description of the existing course thumbnail setting.
 */
final class Version20261121110000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename the course thumbnail setting metadata (GH-8769).';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $this->addSql(
            'UPDATE settings SET title = ?, comment = ? WHERE variable = ? AND category = ?',
            [
                'Custom thumbnails for courses',
                'Show course-specific thumbnails in course lists. When disabled, use the default course image.',
                'course_images_in_courses_list',
                'course',
            ],
        );
    }

    public function down(Schema $schema): void
    {
        // Preserve current administrator-facing setting metadata on rollback.
    }
}
