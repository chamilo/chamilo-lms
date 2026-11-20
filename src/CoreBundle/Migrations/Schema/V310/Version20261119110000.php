<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V310;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;

/**
 * Courses saved from the administration edit page had their empty department URL stored as the
 * bare scheme "http://", which then made the course settings form fail with "Invalid department URL".
 * Treat a department URL that only holds a scheme as empty.
 */
final class Version20261119110000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Empty the course department URL when it only holds "http://" or "https://".';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('course') || !$schema->getTable('course')->hasColumn('department_url')) {
            throw new RuntimeException('The course.department_url column does not exist.');
        }

        $this->addSql("UPDATE course SET department_url = '' WHERE LOWER(TRIM(department_url)) IN ('http://', 'https://')");
    }

    public function down(Schema $schema): void
    {
        // The emptied values carried no information.
    }
}
