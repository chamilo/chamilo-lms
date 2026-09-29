<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\DataFixtures\DemoCoursesFixtures;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\ResourceFileRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Throwable;

use const DIRECTORY_SEPARATOR;
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

        $lastResourceFileId = $this->getLastResourceFileId();

        try {
            // Keep update parity with chamilo:install-demo-courses-on-update:
            // missing demo courses are installed as private courses, while existing
            // courses are left untouched by DemoCoursesFixtures.
            $demoCoursesFixtures->installDemoCourses($this->getAdmin(), Course::REGISTERED);

            // Local Flysystem stores private files with mode 0600. During an update,
            // the migration is commonly executed by a CLI user while PHP serves the
            // portal as another user in the same deployment group. Make only the
            // resource files created by this migration group-readable/writable.
            //
            // Do not modify parent directories here: Chamilo's resource storage
            // already provides shared traversal through its deployment permissions
            // and ACLs, and some existing directories can be owned by the web user.
            $this->normalizeNewLocalResourceFilePermissions($lastResourceFileId);
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

    private function getLastResourceFileId(): int
    {
        $repository = $this->entityManager?->getRepository(ResourceFile::class);
        if (!$repository instanceof ResourceFileRepository) {
            throw new RuntimeException('Could not resolve ResourceFileRepository.');
        }

        return (int) $repository->createQueryBuilder('rf')
            ->select('COALESCE(MAX(rf.id), 0)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function normalizeNewLocalResourceFilePermissions(int $lastResourceFileId): void
    {
        if (null === $this->container || null === $this->entityManager) {
            return;
        }

        // chmod semantics are only relevant to Unix-like local storage.
        if ('\\' === DIRECTORY_SEPARATOR) {
            return;
        }

        $projectDir = (string) $this->container->getParameter('kernel.project_dir');
        $resourceRoot = rtrim($projectDir, DIRECTORY_SEPARATOR).'/var/upload/resource';

        if (!is_dir($resourceRoot)) {
            return;
        }

        $fileRepository = $this->entityManager->getRepository(ResourceFile::class);
        $nodeRepository = $this->entityManager->getRepository(ResourceNode::class);

        if (!$fileRepository instanceof ResourceFileRepository || !$nodeRepository instanceof ResourceNodeRepository) {
            throw new RuntimeException('Could not resolve resource repositories for demo-course permission normalization.');
        }

        $resourceFiles = $fileRepository->createQueryBuilder('rf')
            ->where('rf.id > :lastId')
            ->setParameter('lastId', $lastResourceFileId)
            ->orderBy('rf.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $normalized = 0;

        foreach ($resourceFiles as $resourceFile) {
            if (!$resourceFile instanceof ResourceFile) {
                continue;
            }

            $relativePath = $nodeRepository->getFilename($resourceFile);
            if (null === $relativePath || '' === $relativePath) {
                continue;
            }

            $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
            if (str_contains('/'.$relativePath.'/', '/../')) {
                throw new RuntimeException('Unsafe resource path returned while installing demo courses.');
            }

            $absolutePath = rtrim($resourceRoot, DIRECTORY_SEPARATOR)
                .DIRECTORY_SEPARATOR
                .str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            // Remote adapters have no corresponding local payload here.
            if (!is_file($absolutePath)) {
                continue;
            }

            // The file was just created by this migration, so the CLI process owns
            // it and can safely grant access to the shared deployment group.
            if (!@chmod($absolutePath, 0660)) {
                throw new RuntimeException(\sprintf('Could not set shared permissions on demo resource file "%s".', $absolutePath));
            }

            ++$normalized;
        }

        if ($normalized > 0) {
            $this->write(\sprintf(
                'Normalized shared permissions for %d bundled demo resource files.',
                $normalized
            ));
        }
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
