<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\DataFixtures\DemoCoursesFixtures;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

use const PHP_SAPI;

final class Version20260822212700 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Install missing bundled demo courses on existing portals.';
    }

    public function up(Schema $schema): void
    {
        // Doctrine still invokes up() while generating SQL or running a dry-run.
        // The demo-course import performs ORM and filesystem writes directly, so it
        // must not run in those simulation modes.
        if ($this->isSimulationMode()) {
            $this->write('Skipping bundled demo course import during migration simulation.');

            return;
        }

        if (null === $this->container || null === $this->entityManager) {
            throw new RuntimeException('The Symfony container and EntityManager are required to install demo courses.');
        }

        if (!$this->container->has(DemoCoursesFixtures::class)) {
            throw new RuntimeException('DemoCoursesFixtures service is not available.');
        }

        $demoCoursesFixtures = $this->container->get(DemoCoursesFixtures::class);
        if (!$demoCoursesFixtures instanceof DemoCoursesFixtures) {
            throw new RuntimeException('Could not resolve DemoCoursesFixtures service.');
        }

        try {
            // Keep update parity with chamilo:install-demo-courses-on-update:
            // missing demo courses are installed as private courses, while existing
            // courses are left untouched by DemoCoursesFixtures.
            $demoCoursesFixtures->installDemoCourses($this->getAdmin(), Course::REGISTERED);
        } catch (Throwable $e) {
            throw new RuntimeException('Could not install bundled demo courses during migration: '.$e->getMessage(), 0, $e);
        }

        $this->write('Bundled demo courses are available. Existing demo courses were left unchanged.');
    }

    public function down(Schema $schema): void
    {
        // Never delete courses on rollback: administrators may already have edited,
        // published or used a demo course after the migration ran.
        $this->write('Demo course installation is intentionally not reverted.');
    }

    private function isSimulationMode(): bool
    {
        if ('cli' !== PHP_SAPI) {
            return false;
        }

        foreach ($_SERVER['argv'] ?? [] as $argument) {
            $argument = (string) $argument;

            if ('--dry-run' === $argument || str_starts_with($argument, '--write-sql')) {
                return true;
            }
        }

        return false;
    }
}
