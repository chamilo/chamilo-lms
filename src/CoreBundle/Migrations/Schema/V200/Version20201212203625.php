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
                        ->setFile($file);

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
                        ->setFile($file);

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

            $sql = "SELECT iid, path FROM c_document
                    WHERE c_id = {$courseId}
                      AND path NOT LIKE '/../exercises/%'
                      AND path NOT LIKE '/chat_files/%'
                    ORDER BY filetype DESC, path";
            $documents = $this->connection->executeQuery($sql)->fetchAllAssociative();

            foreach ($documents as $documentData) {
                $documentId = (int) $documentData['iid'];
                $documentPath = (string) $documentData['path'];

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

    public function down(Schema $schema): void
    {
    }

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

            if (is_object($u) && method_exists($u, 'getId')) {
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
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

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

        error_log(sprintf('[MIGRATION][attempt_feedback] Linked asset to attempt (attemptId=%d).', $attemptId));
    }

    private function insertAttemptFileRow(int $attemptId, Asset $asset): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->connection->insert('attempt_file', [
            'id' => Uuid::v4()->toBinary(),
            'attempt_id' => $attemptId,
            'asset_id' => $asset->getId()->toBinary(),
            'resource_node_id' => null,
            'comment' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        error_log(sprintf('[MIGRATION][attempt_file] Linked asset to attempt (attemptId=%d).', $attemptId));
    }
}
