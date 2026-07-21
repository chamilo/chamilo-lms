<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20240507160300 extends AbstractMigrationChamilo
{
    private const FLUSH_BATCH_SIZE = 50;
    private const PROGRESS_INTERVAL = 100;

    public function getDescription(): string
    {
        return 'Migrate missing user profile illustrations using DBAL candidates and bounded repository flushes.';
    }

    public function up(Schema $schema): void
    {
        /** @var IllustrationRepository $illustrationRepo */
        $illustrationRepo = $this->container->get(IllustrationRepository::class);

        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);

        $splitUsersDirectory = $this->isSplitUsersUploadDirectoryEnabled();
        $illustrationTypeId = (int) $illustrationRepo->getResourceType()->getId();
        $rows = $this->connection->fetchAllAssociative(
            'SELECT u.id, u.picture_uri
             FROM user u
             WHERE u.resource_node_id IS NOT NULL
               AND u.picture_uri IS NOT NULL
               AND TRIM(u.picture_uri) <> :empty
               AND NOT EXISTS (
                   SELECT 1
                   FROM resource_node child
                   INNER JOIN resource_file rf ON rf.resource_node_id = child.id
                   WHERE child.parent_id = u.resource_node_id
                     AND child.resource_type_id = :illustrationTypeId
               )
             ORDER BY u.id',
            [
                'empty' => '',
                'illustrationTypeId' => $illustrationTypeId,
            ]
        );

        $totalCandidates = \count($rows);
        if (0 === $totalCandidates) {
            $this->getLogger()->info('No missing user illustrations were found.');

            return;
        }

        $seen = 0;
        $migrated = 0;
        $missing = 0;
        $invalidUsers = 0;
        $pendingFlush = 0;
        $startedAt = microtime(true);

        $this->getLogger()->info('User illustration migration started.', [
            'candidates' => $totalCandidates,
            'flush_batch_size' => self::FLUSH_BATCH_SIZE,
        ]);

        foreach ($rows as $row) {
            ++$seen;
            $userId = (int) $row['id'];
            $picture = trim((string) $row['picture_uri']);
            $path = $this->determinePath($userId, $splitUsersDirectory);
            $picturePath = $this->getUpdateRootPath().'/app/upload/'.$path.$picture;

            if (!$this->fileExists($picturePath)) {
                ++$missing;
                $this->logProgressIfNeeded($seen, $totalCandidates, $migrated, $missing, $invalidUsers, $startedAt);

                continue;
            }

            $user = $userRepo->find($userId);
            if (!$user instanceof User || null === $user->getResourceNode()) {
                ++$invalidUsers;
                $this->logProgressIfNeeded($seen, $totalCandidates, $migrated, $missing, $invalidUsers, $startedAt);

                continue;
            }

            $mimeType = mime_content_type($picturePath) ?: 'application/octet-stream';
            $file = new UploadedFile($picturePath, $picture, $mimeType, null, true);
            $resourceFile = $illustrationRepo->addIllustration($user, $user, $file, '', false);

            if (null !== $resourceFile) {
                ++$migrated;
                ++$pendingFlush;
            }

            if ($pendingFlush >= self::FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $pendingFlush = 0;
            }

            $this->logProgressIfNeeded($seen, $totalCandidates, $migrated, $missing, $invalidUsers, $startedAt);
        }

        if ($pendingFlush > 0) {
            $this->entityManager->flush();
        }
        $this->entityManager->clear();

        $this->getLogger()->info('User illustration migration completed.', [
            'candidates' => $totalCandidates,
            'seen' => $seen,
            'migrated' => $migrated,
            'missing_files' => $missing,
            'invalid_users' => $invalidUsers,
            'elapsed_seconds' => (int) (microtime(true) - $startedAt),
        ]);
    }

    private function logProgressIfNeeded(
        int $seen,
        int $total,
        int $migrated,
        int $missing,
        int $invalidUsers,
        float $startedAt
    ): void {
        if (0 !== $seen % self::PROGRESS_INTERVAL && $seen !== $total) {
            return;
        }

        $elapsed = max(1, (int) (microtime(true) - $startedAt));
        $rate = $seen / $elapsed;
        $remaining = max(0, $total - $seen);

        $this->getLogger()->info('User illustration migration progress.', [
            'seen' => $seen,
            'total' => $total,
            'percentage' => round(100 * $seen / $total, 2),
            'migrated' => $migrated,
            'missing_files' => $missing,
            'invalid_users' => $invalidUsers,
            'rows_per_second' => round($rate, 2),
            'eta_seconds' => $rate > 0 ? (int) round($remaining / $rate) : null,
        ]);
    }

    private function determinePath(int $userId, bool $splitUsersDirectory): string
    {
        if ($splitUsersDirectory) {
            return 'users/'.substr((string) $userId, 0, 1).'/'.$userId.'/';
        }

        return "users/{$userId}/";
    }

    private function isSplitUsersUploadDirectoryEnabled(): bool
    {
        $value = $this->connection->fetchOne(
            "SELECT selected_value FROM settings WHERE variable = 'split_users_upload_directory' AND access_url = 1 LIMIT 1"
        );

        return 'true' === (string) $value || '1' === (string) $value;
    }
}
