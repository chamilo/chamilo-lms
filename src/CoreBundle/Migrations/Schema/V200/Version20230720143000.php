<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use DirectoryIterator;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

final class Version20230720143000 extends AbstractMigrationChamilo
{
    private const FILE_FLUSH_BATCH_SIZE = 25;
    private const PROGRESS_INTERVAL = 500;

    private int $seenFiles = 0;
    private int $migratedFiles = 0;
    private int $alreadyExistingFiles = 0;
    private int $missingUsers = 0;
    private float $startedAt = 0.0;

    public function getDescription(): string
    {
        return 'Migrate user my_files with DBAL candidate checks and bounded filesystem batches';
    }

    public function up(Schema $schema): void
    {
        $kernel = $this->container->get('kernel');
        $projectDir = rtrim((string) $kernel->getProjectDir(), '/');
        $usersDirectory = $projectDir.'/app/upload/users';

        if (!is_dir($usersDirectory)) {
            $this->getLogger()->warning('Personal-file source directory does not exist.', [
                'directory' => $usersDirectory,
            ]);

            return;
        }

        $fallback = $this->resolveFallbackUserRow();
        $splitUsersUploadDirectory = $this->resolveSplitUsersUploadDirectory();
        $this->startedAt = microtime(true);

        $this->getLogger()->info('Starting optimized personal-file migration.', [
            'source' => $usersDirectory,
            'split_users_upload_directory' => $splitUsersUploadDirectory,
            'flush_batch_size' => self::FILE_FLUSH_BATCH_SIZE,
        ]);

        if ($splitUsersUploadDirectory) {
            foreach (new DirectoryIterator($usersDirectory) as $parentDirectory) {
                if ($parentDirectory->isDot() || !$parentDirectory->isDir()) {
                    continue;
                }

                foreach (new DirectoryIterator($parentDirectory->getPathname()) as $userDirectory) {
                    if ($userDirectory->isDot() || !$userDirectory->isDir()) {
                        continue;
                    }

                    $this->processUserDirectory(
                        (string) $userDirectory->getFilename(),
                        $userDirectory->getPathname(),
                        $fallback
                    );
                }
            }
        } else {
            foreach (new DirectoryIterator($usersDirectory) as $userDirectory) {
                if ($userDirectory->isDot() || !$userDirectory->isDir()) {
                    continue;
                }

                $this->processUserDirectory(
                    (string) $userDirectory->getFilename(),
                    $userDirectory->getPathname(),
                    $fallback
                );
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
        $this->logProgress(true);
    }

    /**
     * @param array{id: int, resource_node_id: int} $fallback
     */
    private function processUserDirectory(string $legacyUserId, string $userDirectory, array $fallback): void
    {
        if (!ctype_digit($legacyUserId)) {
            return;
        }

        $userRow = $this->connection->fetchAssociative(
            'SELECT id, resource_node_id
             FROM user
             WHERE id = :id',
            ['id' => (int) $legacyUserId]
        );

        if (!$userRow || empty($userRow['resource_node_id'])) {
            ++$this->missingUsers;
            $userId = $fallback['id'];
            $parentResourceNodeId = $fallback['resource_node_id'];
        } else {
            $userId = (int) $userRow['id'];
            $parentResourceNodeId = (int) $userRow['resource_node_id'];
        }

        $myFilesDirectory = rtrim($userDirectory, '/').'/my_files';
        if (!is_dir($myFilesDirectory)) {
            return;
        }

        $existingTitles = $this->loadExistingTitlesForCreator($userId);
        $pendingInEntityManager = 0;
        $userReference = $this->entityManager->getReference(User::class, $userId);

        foreach (new DirectoryIterator($myFilesDirectory) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile() || !$fileInfo->isReadable()) {
                continue;
            }

            ++$this->seenFiles;
            $title = (string) $fileInfo->getFilename();

            if (isset($existingTitles[$title])) {
                ++$this->alreadyExistingFiles;
                $this->logProgress();
                continue;
            }

            try {
                $path = $fileInfo->getPathname();
                $mimeType = (string) (mime_content_type($path) ?: 'application/octet-stream');
                $uploadedFile = new UploadedFile($path, $title, $mimeType, null, true);

                $personalFile = (new PersonalFile())
                    ->setTitle($title)
                    ->setCreator($userReference)
                    ->setParentResourceNode($parentResourceNodeId)
                    ->setResourceName($title)
                    ->setUploadFile($uploadedFile)
                ;
                $personalFile->addUserLink($userReference);

                $this->entityManager->persist($personalFile);
                $existingTitles[$title] = true;
                ++$this->migratedFiles;
                ++$pendingInEntityManager;

                if (0 === $pendingInEntityManager % self::FILE_FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $userReference = $this->entityManager->getReference(User::class, $userId);
                }
            } catch (Throwable $exception) {
                $this->getLogger()->warning('Personal file could not be migrated.', [
                    'legacy_user_id' => (int) $legacyUserId,
                    'assigned_user_id' => $userId,
                    'filename' => $title,
                    'error' => $exception->getMessage(),
                ]);
            }

            $this->logProgress();
        }

        if ($pendingInEntityManager > 0) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }
    }

    /**
     * @return array<string, true>
     */
    private function loadExistingTitlesForCreator(int $creatorId): array
    {
        $titles = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT node.title
             FROM personal_file file
             INNER JOIN resource_node node ON node.id = file.resource_node_id
             WHERE node.creator_id = :creatorId',
            ['creatorId' => $creatorId]
        );

        $map = [];
        foreach ($titles as $title) {
            $map[(string) $title] = true;
        }

        return $map;
    }

    /**
     * @return array{id: int, resource_node_id: int}
     */
    private function resolveFallbackUserRow(): array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, resource_node_id
             FROM user
             WHERE status = :status
               AND resource_node_id IS NOT NULL
             ORDER BY id
             LIMIT 1',
            ['status' => User::ROLE_FALLBACK]
        );

        if (!$row) {
            throw new RuntimeException('Fallback user with a resource node was not found.');
        }

        return [
            'id' => (int) $row['id'],
            'resource_node_id' => (int) $row['resource_node_id'],
        ];
    }

    private function resolveSplitUsersUploadDirectory(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        $settingsTable = null;

        if ($schemaManager->tablesExist(['settings'])) {
            $settingsTable = 'settings';
        } elseif ($schemaManager->tablesExist(['settings_current'])) {
            $settingsTable = 'settings_current';
        }

        if (null === $settingsTable) {
            $this->getLogger()->warning('Split users upload setting could not be read because no settings table exists. Using the non-split directory layout.');

            return false;
        }

        $value = $this->connection->fetchOne(
            sprintf(
                'SELECT selected_value
                 FROM %s
                 WHERE variable = :variable
                 ORDER BY id
                 LIMIT 1',
                $settingsTable
            ),
            ['variable' => 'split_users_upload_directory']
        );

        $enabled = 'true' === strtolower(trim((string) $value));

        $this->getLogger()->info('Resolved split users upload directory setting.', [
            'settings_table' => $settingsTable,
            'enabled' => $enabled,
        ]);

        return $enabled;
    }

    private function logProgress(bool $force = false): void
    {
        if (!$force && (0 === $this->seenFiles || 0 !== $this->seenFiles % self::PROGRESS_INTERVAL)) {
            return;
        }

        $elapsed = max(1, (int) (microtime(true) - $this->startedAt));
        $this->getLogger()->info('Personal-file migration progress.', [
            'seen' => $this->seenFiles,
            'migrated' => $this->migratedFiles,
            'already_existing' => $this->alreadyExistingFiles,
            'missing_users_using_fallback' => $this->missingUsers,
            'files_per_second' => round($this->seenFiles / $elapsed, 2),
            'elapsed_seconds' => $elapsed,
        ]);
    }
}
