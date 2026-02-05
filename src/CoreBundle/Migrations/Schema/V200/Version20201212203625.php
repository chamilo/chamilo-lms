<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class Version20201212203625 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate c_document';
    }

    public function up(Schema $schema): void
    {
        $this->ensureCDocumentFiletypeVarchar15();
        $documentRepo = $this->container->get(CDocumentRepository::class);
        $courseRepo = $this->container->get(CourseRepository::class);
        $attemptRepo = $this->entityManager->getRepository(TrackEAttempt::class);

        $batchSize = self::BATCH_SIZE;

        // Migrate teacher exercise audio.
        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = (int) $course->getId();
            $sql = "SELECT iid, path
                    FROM c_document
                    WHERE c_id = $courseId
                      AND path LIKE '/../exercises/teacher_audio%'";
            $documents = $this->connection->executeQuery($sql)->fetchAllAssociative();

            foreach ($documents as $documentData) {
                $path = (string) ($documentData['path'] ?? '');

                $path = str_replace('//', '/', $path);
                $path = str_replace('/../exercises/teacher_audio/', '', $path);

                $filePath = $this->getUpdateRootPath().'/app/courses/'.$course->getDirectory().'/exercises/teacher_audio/'.$path;
                error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                if (!$this->fileExists($filePath)) {
                    continue;
                }

                // attemptId is the first folder name in the path
                preg_match('#^/([^/]+)/#', '/'.$path, $matches);
                $attemptId = isset($matches[1]) && '' !== $matches[1] ? (int) $matches[1] : 0;
                if ($attemptId <= 0) {
                    error_log('[MIGRATION][teacher_audio] Could not parse attempt id from path, skipping.');

                    continue;
                }

                /** @var TrackEAttempt|null $attempt */
                $attempt = $attemptRepo->find($attemptId);
                if (null === $attempt) {
                    continue;
                }

                // Avoid duplicates even within the same EM (we insert via DBAL)
                if ($this->attemptHasFeedback($attemptId)) {
                    continue;
                }

                $userId = $this->getAttemptUserId($attempt);
                if (null === $userId || $userId <= 0) {
                    error_log('[MIGRATION][teacher_audio] Missing attempt user id, skipping.');

                    continue;
                }

                try {
                    $fileName = basename($filePath);
                    $mimeType = (string) (mime_content_type($filePath) ?: 'application/octet-stream');
                    $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);

                    $asset = (new Asset())
                        ->setCategory(Asset::EXERCISE_FEEDBACK)
                        ->setTitle($fileName)
                        ->setFile($file)
                    ;

                    $this->entityManager->persist($asset);
                    $this->entityManager->flush();

                    $this->insertAttemptFeedbackRow($attemptId, $userId, $asset);
                } catch (Throwable $e) {
                    error_log('[MIGRATION][teacher_audio] Failed processing audio: '.$e->getMessage());
                }
            }
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Migrate student exercise audio.
        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseId = (int) $course->getId();

            $sql = "SELECT iid, path
                    FROM c_document
                    WHERE c_id = $courseId
                      AND path NOT LIKE '/../exercises/teacher_audio%'
                      AND path LIKE '/../exercises/%'";
            $documents = $this->connection->executeQuery($sql)->fetchAllAssociative();

            foreach ($documents as $documentData) {
                $path = (string) ($documentData['path'] ?? '');

                $path = str_replace('//', '/', $path);
                $path = str_replace('/../exercises/', '', $path);

                $filePath = $this->getUpdateRootPath().'/app/courses/'.$course->getDirectory().'/exercises/'.$path;
                error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');
                if (!$this->fileExists($filePath)) {
                    continue;
                }

                $fileName = basename($filePath);

                preg_match('#/(.*)/(.*)/(.*)/(.*)/#', '/'.$path, $matches);
                $questionId = isset($matches[3]) ? (int) $matches[3] : 0;
                $userId = isset($matches[4]) ? (int) $matches[4] : 0;

                if ($questionId <= 0 || $userId <= 0) {
                    continue;
                }

                /** @var TrackEAttempt|null $attempt */
                $attempt = $attemptRepo->findOneBy([
                    'user' => $userId,
                    'questionId' => $questionId,
                    'filename' => $fileName,
                ]);

                if (null === $attempt) {
                    continue;
                }

                $attemptId = (int) $attempt->getId();
                if ($attemptId <= 0) {
                    continue;
                }

                if ($this->attemptHasFiles($attemptId)) {
                    continue;
                }

                try {
                    $mimeType = (string) (mime_content_type($filePath) ?: 'application/octet-stream');
                    $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);

                    $asset = (new Asset())
                        ->setCategory(Asset::EXERCISE_ATTEMPT)
                        ->setTitle($fileName)
                        ->setFile($file)
                    ;

                    $this->entityManager->persist($asset);
                    $this->entityManager->flush();

                    $this->insertAttemptFileRow($attemptId, $asset);
                } catch (Throwable $e) {
                    error_log('[MIGRATION][student_audio] Failed processing audio: '.$e->getMessage());
                }
            }
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Migrate normal documents.
        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $counter = 1;
            $courseId = (int) $course->getId();

            // We fetch session_id and filetype for debugging/consistency checks during migration.
            // The legacy "path" is the only reliable identifier for system folders (language-independent).
            $sql = "SELECT iid, path, session_id, filetype FROM c_document
                    WHERE c_id = {$courseId}
                      AND path NOT LIKE '/../exercises/%'
                      AND path NOT LIKE '/chat_files/%'
                    ORDER BY filetype DESC, path";
            $documents = $this->connection->executeQuery($sql)->fetchAllAssociative();

            foreach ($documents as $documentData) {
                $documentId = (int) ($documentData['iid'] ?? 0);
                $documentPath = (string) ($documentData['path'] ?? '');
                $legacySessionId = (int) ($documentData['session_id'] ?? 0);
                $legacyFiletype = (string) ($documentData['filetype'] ?? '');

                if ($documentId <= 0 || '' === $documentPath) {
                    continue;
                }

                $courseEntity = $courseRepo->find($courseId);
                if (!$courseEntity) {
                    continue;
                }

                /** @var CDocument|null $document */
                $document = $documentRepo->find($documentId);
                if (null === $document) {
                    continue;
                }

                if ($document->hasResourceNode()) {
                    continue;
                }

                // Detect and mark legacy system folders before linking/persisting.
                // This avoids relying on translated titles and keeps identification stable over time.
                $normalizedLegacyPath = $this->normalizeLegacyDocumentPath($documentPath);
                $systemFolderType = $this->detectSystemFolderType($normalizedLegacyPath);

                // Only overwrite filetype for folders to keep behavior safe and predictable.
                if (null !== $systemFolderType) {
                    $effectiveFiletype = $document->getFiletype() ?? $legacyFiletype;

                    if ('folder' === $effectiveFiletype) {
                        $document->setFiletype($systemFolderType);

                        error_log(\sprintf(
                            '[MIGRATION][documents] Marked system folder (iid=%d, legacyPath=%s, legacySid=%d, type=%s).',
                            $documentId,
                            $normalizedLegacyPath,
                            $legacySessionId,
                            $systemFolderType
                        ));
                    }
                }

                $parent = null;
                if ('.' !== \dirname($documentPath)) {
                    $currentPath = \dirname($documentPath);

                    $sqlParent = "SELECT iid FROM c_document
                                  WHERE c_id = {$courseId}
                                    AND path LIKE '$currentPath'";
                    $parentId = $this->connection->executeQuery($sqlParent)->fetchOne();

                    if (!empty($parentId)) {
                        $parent = $documentRepo->find((int) $parentId);
                    }
                }

                if (null === $parent) {
                    $parent = $courseEntity;
                }
                if (null === $parent->getResourceNode()) {
                    $this->logItemPropertyInconsistency('document', $documentId, $documentPath);

                    continue;
                }
                $admin = $this->getAdmin();
                $ok = $this->fixItemProperty('document', $documentRepo, $courseEntity, $admin, $document, $parent);
                if (false === $ok) {
                    continue;
                }
                $documentPath = ltrim($documentPath, '/');
                $filePath = $this->getUpdateRootPath().'/app/courses/'.$courseEntity->getDirectory().'/document/'.$documentPath;
                error_log('MIGRATIONS :: $filePath -- '.$filePath.' ...');

                $filePathToUpload = $this->rewriteHtmlFileLegacyLinksIfNeeded($filePath, (string) $courseEntity->getDirectory());
                $originalFilename = basename($filePath);

                $this->addLegacyFileToResource(
                    $filePathToUpload,
                    $documentRepo,
                    $document,
                    $documentId,
                    $originalFilename
                );

                $this->entityManager->persist($document);

                if (0 === ($counter % $batchSize)) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
                $counter++;
            }
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function down(Schema $schema): void {}

    private function attemptHasFeedback(int $attemptId): bool
    {
        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM attempt_feedback WHERE attempt_id = :aid AND asset_id IS NOT NULL',
            ['aid' => $attemptId]
        );

        return $count > 0;
    }

    private function attemptHasFiles(int $attemptId): bool
    {
        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM attempt_file WHERE attempt_id = :aid AND asset_id IS NOT NULL',
            ['aid' => $attemptId]
        );

        return $count > 0;
    }

    private function getAttemptUserId(TrackEAttempt $attempt): ?int
    {
        if (method_exists($attempt, 'getUser')) {
            $u = $attempt->getUser();

            if (\is_object($u) && method_exists($u, 'getId')) {
                return (int) $u->getId();
            }

            if (is_numeric($u)) {
                return (int) $u;
            }
        }

        if (method_exists($attempt, 'getUserId')) {
            $v = $attempt->getUserId();
            if (null !== $v && is_numeric($v)) {
                return (int) $v;
            }
        }

        return null;
    }

    private function insertAttemptFeedbackRow(int $attemptId, int $userId, Asset $asset): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        // asset_id column is intentionally not mapped in the entity anymore,
        // but it still exists for the later migration that converts it to ResourceNode/ResourceFile.
        $this->connection->insert('attempt_feedback', [
            'id' => Uuid::v4()->toBinary(),
            'attempt_id' => $attemptId,
            'user_id' => $userId,
            'asset_id' => $asset->getId()->toBinary(),
            'resource_node_id' => null,
            'comment' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        error_log(\sprintf('[MIGRATION][attempt_feedback] Linked asset to attempt (attemptId=%d).', $attemptId));
    }

    private function insertAttemptFileRow(int $attemptId, Asset $asset): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->connection->insert('attempt_file', [
            'id' => Uuid::v4()->toBinary(),
            'attempt_id' => $attemptId,
            'asset_id' => $asset->getId()->toBinary(),
            'resource_node_id' => null,
            'comment' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        error_log(\sprintf('[MIGRATION][attempt_file] Linked asset to attempt (attemptId=%d).', $attemptId));
    }

    /**
     * Ensure c_document.filetype is large enough BEFORE we update it during data migration.
     * Execute the ALTER immediately to avoid truncation during flush().
     */
    private function ensureCDocumentFiletypeVarchar15(): void
    {
        try {
            // We run this upfront because this migration updates c_document.filetype during flush().
            $this->connection->executeStatement('ALTER TABLE c_document MODIFY filetype VARCHAR(15);');
            error_log('[MIGRATION][documents] Ensured c_document.filetype is VARCHAR(15) before data migration.');
        } catch (Throwable $e) {
            error_log('[MIGRATION][documents] Failed to resize c_document.filetype: '.$e->getMessage());
        }
    }

    /**
     * Normalize legacy paths so system folders can be detected reliably.
     * Keep it strict and language-independent (do not use titles).
     */
    private function normalizeLegacyDocumentPath(string $path): string
    {
        $p = str_replace('//', '/', trim($path));
        if ('' === $p) {
            return '';
        }

        // Ensure leading slash for consistent prefix checks.
        if ('/' !== $p[0]) {
            $p = '/'.$p;
        }

        return $p;
    }

    /**
     * Detect legacy system folder type from the original C1 document path.
     */
    private function detectSystemFolderType(string $legacyPath): ?string
    {
        if ('' === $legacyPath) {
            return null;
        }

        // Session-scoped shared folders: keep a distinct type to hide them in base course views.
        if (preg_match('#^/shared_folder_session_\d+(/|$)#', $legacyPath)) {
            return 'user_folder_ses'; // <= 15 chars
        }

        // Base course shared folder.
        if (str_starts_with($legacyPath, '/shared_folder')) {
            return 'user_folder';
        }

        if (preg_match('#^/(images|audio|flash|video)(/|$)#', $legacyPath)) {
            return 'media_folder';
        }

        if (str_starts_with($legacyPath, '/certificates')) {
            return 'cert_folder'; // <= 15 chars
        }

        if (
            str_starts_with($legacyPath, '/chat_history')
            || str_starts_with($legacyPath, '/chat_files')
        ) {
            return 'chat_folder';
        }

        return null;
    }
}
