<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * This migration used to install the bundled demo courses. It restores them through the ORM with
 * the current entity mapping, before the V310 schema changes those entities need: on a platform
 * that only runs the migrations from V300 on, every column V310 adds to a course table made it fail.
 * The chamilo:install-demo-courses-on-update command does the same once all migrations are done,
 * and the web update already runs it right after doctrine:migrations:migrate.
 */
final class Version20260822212700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Leave the installation of the bundled demo courses to chamilo:install-demo-courses-on-update, after all migrations.';
    }

    public function up(Schema $schema): void
    {
        $this->write('Bundled demo courses are installed by chamilo:install-demo-courses-on-update after the migrations.');
    }

    public function down(Schema $schema): void {}
}
