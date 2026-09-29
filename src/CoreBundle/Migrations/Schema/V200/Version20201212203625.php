<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;
use Throwable;

use const PATHINFO_EXTENSION;

final class Version20201212203625 extends AbstractMigrationChamilo
{
    private const int DOCUMENT_BATCH_SIZE = 100;
    private const int AUDIO_ASSET_FLUSH_BATCH_SIZE = 50;
    private const string ITEM_PROPERTY_INDEX = 'idx_legacy_migration_item_property_tool_ref_course';
    private const string CDOCUMENT_CID_INDEX = 'idx_legacy_migration_cdocument_c_id';
    private const string TRACK_E_ATTEMPT_LOOKUP_INDEX = 'idx_legacy_migration_track_e_attempt_uid_qid_fn';

    private const array FOLDER_LIKE_FILETYPES = [
        'folder',
        'user_folder',
        'user_folder_ses',
        'media_folder',
        'cert_folder',
        'chat_folder',
    ];

    public function getDescription(): string
    {
        return 'Migrate c_document';
    }

    public function up(Schema $schema): void
    {
        $this->ensureCDocumentFiletypeVarchar15();
        $this->ensureItemPropertyMigrationIndex();
        $this->ensureCDocumentCidIndex();

        // The composite track_e_attempt index is only needed by the student
        // exercise-audio lookup. Avoid building it on large tracking tables
        // when the legacy database has no such documents.
        $hasStudentExerciseAudioDocuments = $this->hasStudentExerciseAudioDocuments();
        if ($hasStudentExerciseAudioDocuments) {
            $this->ensureTrackEAttemptLookupIndex();
        }

        /** @var CDocumentRepository $documentRepo */
        $documentRepo = $this->container->get(CDocumentRepository::class);

        /** @var CourseRepository $courseRepo */
        $courseRepo = $this->container->get(CourseRepository::class);

        $batchSize = self::DOCUMENT_BATCH_SIZE;
        $updateRootPath = $this->getUpdateRootPath();

        $courses = $this->connection->fetchAllAssociative(
            'SELECT id, directory FROM course ORDER BY id'
        );

        // Prepared statements.
        $stmtTeacherAudioDocs = $this->connection->prepare(
            'SELECT iid, path
             FROM c_document
             WHERE c_id = :cid
               AND path LIKE :pattern'
        );

        $stmtAttemptUserId = $this->connection->prepare(
            'SELECT user_id
             FROM track_e_attempt
             WHERE id = :id'
        );

        $stmtStudentAudioDocs = $this->connection->prepare(
            'SELECT iid, path
             FROM c_document
             WHERE c_id = :cid
               AND path NOT LIKE :notPattern
               AND path LIKE :pattern'
        );

        $stmtFindAttemptId = $this->connection->prepare(
            'SELECT id
             FROM track_e_attempt
             WHERE user_id = :uid
               AND question_id = :qid
               AND filename = :fn'
        );

        $stmtCourseDocuments = $this->connection->prepare(
            'SELECT iid, path, filetype
             FROM c_document
             WHERE c_id = :cid
               AND path NOT LIKE :exPattern
               AND path NOT LIKE :chatFilesPattern
               AND path NOT LIKE :chatHistoryPattern
             ORDER BY filetype DESC, path'
        );

        // --------------------------
        // 1) Teacher exercise audio
        // --------------------------
        $pendingFeedbackRows = [];

        foreach ($courses as $courseData) {
            $courseId = (int) ($courseData['id'] ?? 0);
            $courseDirectory = (string) ($courseData['directory'] ?? '');

            if ($courseId <= 0 || '' === $courseDirectory) {
                continue;
            }

            $documents = $stmtTeacherAudioDocs->executeQuery([
                'cid' => $courseId,
                'pattern' => '/../exercises/teacher_audio%',
            ])->fetchAllAssociative();

            $baseTeacherAudioPath = $updateRootPath.'/app/courses/'.$courseDirectory.'/exercises/teacher_audio/';

            foreach ($documents as $documentData) {
                $path = (string) ($documentData['path'] ?? '');
                if ('' === $path) {
                    continue;
                }

                $path = str_replace('//', '/', $path);
                $path = str_replace('/../exercises/teacher_audio/', '', $path);

                $filePath = $baseTeacherAudioPath.$path;
                if (!$this->fileExists($filePath)) {
                    continue;
                }

                // attemptId is the first folder name in the relative path.
                $attemptIdStr = strtok(ltrim($path, '/'), '/');
                $attemptId = (\is_string($attemptIdStr) && ctype_digit($attemptIdStr)) ? (int) $attemptIdStr : 0;
                if ($attemptId <= 0) {
                    continue;
                }

                if ($this->attemptHasFeedback($attemptId)) {
                    continue;
                }

                $userId = (int) $stmtAttemptUserId->executeQuery(['id' => $attemptId])->fetchOne();
                if ($userId <= 0) {
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
                    $pendingFeedbackRows[] = [$attemptId, $userId, $asset];

                    if (\count($pendingFeedbackRows) >= self::AUDIO_ASSET_FLUSH_BATCH_SIZE) {
                        $this->flushPendingFeedbackRows($pendingFeedbackRows);
                        $pendingFeedbackRows = [];
                    }
                } catch (Throwable) {
                    // Ignore single-file failures to keep the migration running.
                }
            }
        }

        if ([] !== $pendingFeedbackRows) {
            $this->flushPendingFeedbackRows($pendingFeedbackRows);
        }

        // --------------------------
        // 2) Student exercise audio
        // --------------------------
        $pendingFileRows = [];

        foreach ($hasStudentExerciseAudioDocuments ? $courses : [] as $courseData) {
            $courseId = (int) ($courseData['id'] ?? 0);
            $courseDirectory = (string) ($courseData['directory'] ?? '');

            if ($courseId <= 0 || '' === $courseDirectory) {
                continue;
            }

            $documents = $stmtStudentAudioDocs->executeQuery([
                'cid' => $courseId,
                'notPattern' => '/../exercises/teacher_audio%',
                'pattern' => '/../exercises/%',
            ])->fetchAllAssociative();

            $baseStudentAudioPath = $updateRootPath.'/app/courses/'.$courseDirectory.'/exercises/';

            foreach ($documents as $documentData) {
                $path = (string) ($documentData['path'] ?? '');
                if ('' === $path) {
                    continue;
                }

                $path = str_replace('//', '/', $path);
                $path = str_replace('/../exercises/', '', $path);

                $filePath = $baseStudentAudioPath.$path;
                if (!$this->fileExists($filePath)) {
                    continue;
                }

                $fileName = basename($filePath);

                // Expected: .../<something>/<something>/<questionId>/<userId>/<file>
                $parts = explode('/', trim($path, '/'));
                if (\count($parts) < 5) {
                    continue;
                }

                $questionId = (int) ($parts[2] ?? 0);
                $userId = (int) ($parts[3] ?? 0);

                if ($questionId <= 0 || $userId <= 0) {
                    continue;
                }

                $attemptId = (int) $stmtFindAttemptId->executeQuery([
                    'uid' => $userId,
                    'qid' => $questionId,
                    'fn' => $fileName,
                ])->fetchOne();

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
                    $pendingFileRows[] = [$attemptId, $asset];

                    if (\count($pendingFileRows) >= self::AUDIO_ASSET_FLUSH_BATCH_SIZE) {
                        $this->flushPendingFileRows($pendingFileRows);
                        $pendingFileRows = [];
                    }
                } catch (Throwable) {
                    // Ignore single-file failures to keep the migration running.
                }
            }
        }

        if ([] !== $pendingFileRows) {
            $this->flushPendingFileRows($pendingFileRows);
        }

        // --------------------------
        // 3) Normal documents
        // --------------------------
        $docMeta = $this->entityManager->getClassMetadata(CDocument::class);
        $docIdField = (string) $docMeta->getSingleIdentifierFieldName();
        $documentResourceTypeId = (int) $documentRepo->getResourceType()->getId();
        $adminId = (int) $this->connection->fetchOne(
            'SELECT user_id FROM admin WHERE user_id IN (SELECT id FROM user) ORDER BY id LIMIT 1'
        );

        foreach ($courses as $courseData) {
            $courseId = (int) ($courseData['id'] ?? 0);
            $courseDirectory = (string) ($courseData['directory'] ?? '');

            if ($courseId <= 0 || '' === $courseDirectory) {
                continue;
            }

            $rows = $stmtCourseDocuments->executeQuery([
                'cid' => $courseId,
                'exPattern' => '/../exercises/%',
                'chatFilesPattern' => '%/chat_files/%',
                'chatHistoryPattern' => '%/chat_history/%',
            ])->fetchAllAssociative();

            if (empty($rows)) {
                continue;
            }

            // Local map per course: path => iid (removes parent SQL per document).
            $pathToIid = [];
            foreach ($rows as $r) {
                $iid = (int) ($r['iid'] ?? 0);
                $p = (string) ($r['path'] ?? '');
                if ($iid > 0 && '' !== $p) {
                    $pathToIid[$p] = $iid;
                }
            }

            $baseDocumentsPath = $updateRootPath.'/app/courses/'.$courseDirectory.'/document/';

            $documentRefs = [];
            foreach ($rows as $row) {
                $documentId = (int) ($row['iid'] ?? 0);
                if ($documentId > 0) {
                    $documentRefs[] = $documentId;
                }
            }

            // Fetch once per course instead of once per small document batch.
            $itemPropsMap = $this->fetchItemPropertiesMap('document', $courseId, $documentRefs);

            $total = \count($rows);
            for ($offset = 0; $offset < $total; $offset += $batchSize) {
                /** @var Course|null $courseEntity */
                $courseEntity = $courseRepo->find($courseId);
                if (null === $courseEntity) {
                    break;
                }

                // These references must belong to the current EntityManager after clear().
                $admin = $adminId > 0
                    ? $this->entityManager->getReference(User::class, $adminId)
                    : $this->getAdmin();
                $documentResourceType = $this->entityManager->getReference(
                    ResourceType::class,
                    $documentResourceTypeId
                );

                $chunk = \array_slice($rows, $offset, $batchSize);

                // Batch-load documents (+ parents) to avoid per-row find().
                $idsToLoad = [];
                foreach ($chunk as $r) {
                    $iid = (int) ($r['iid'] ?? 0);
                    $p = (string) ($r['path'] ?? '');
                    if ($iid > 0) {
                        $idsToLoad[] = $iid;
                    }

                    if ('' !== $p) {
                        $parentPath = \dirname($p);
                        if ('.' !== $parentPath && '/' !== $parentPath && '\\' !== $parentPath) {
                            $pid = (int) ($pathToIid[$parentPath] ?? 0);
                            if ($pid > 0) {
                                $idsToLoad[] = $pid;
                            }
                        }
                    }
                }

                $idsToLoad = array_values(array_unique(array_filter($idsToLoad, static function ($v): bool {
                    return \is_int($v) && $v > 0;
                })));

                /** @var CDocument[] $docs */
                $docs = !empty($idsToLoad) ? $documentRepo->findBy([$docIdField => $idsToLoad]) : [];

                $docMap = [];
                foreach ($docs as $d) {
                    $idValues = $docMeta->getIdentifierValues($d);
                    $iid = (int) ($idValues[$docIdField] ?? 0);
                    if ($iid > 0) {
                        $docMap[$iid] = $d;
                    }
                }

                foreach ($chunk as $documentData) {
                    $documentId = (int) ($documentData['iid'] ?? 0);
                    $documentPath = (string) ($documentData['path'] ?? '');
                    $legacyFiletype = (string) ($documentData['filetype'] ?? '');

                    if ($documentId <= 0 || '' === $documentPath) {
                        continue;
                    }

                    /** @var CDocument|null $document */
                    $document = $docMap[$documentId] ?? null;
                    if (null === $document) {
                        continue;
                    }

                    if ($document->hasResourceNode()) {
                        continue;
                    }

                    // Mark system folders safely (folder only).
                    $normalizedLegacyPath = $this->normalizeLegacyDocumentPath($documentPath);
                    $systemFolderType = $this->detectSystemFolderType($normalizedLegacyPath);

                    if (null !== $systemFolderType) {
                        $effectiveFiletype = (string) ($document->getFiletype() ?? $legacyFiletype);
                        if ('folder' === $effectiveFiletype) {
                            $document->setFiletype($systemFolderType);
                        }
                    }

                    // Resolve parent using the local map (no SQL).
                    $parent = null;
                    $currentPath = \dirname($documentPath);

                    if ('.' !== $currentPath && '/' !== $currentPath && '\\' !== $currentPath) {
                        $parentId = (int) ($pathToIid[$currentPath] ?? 0);
                        if ($parentId > 0) {
                            $parent = $docMap[$parentId] ?? null;
                        }
                    }

                    if (null === $parent) {
                        $parent = $courseEntity;
                    }

                    if (null === $parent->getResourceNode()) {
                        $this->logItemPropertyInconsistency('document', $documentId, $documentPath);

                        continue;
                    }

                    $items = $itemPropsMap[$documentId] ?? [];

                    // The legacy certificate folder can exist without c_item_property while
                    // its certificate templates still have valid item-property rows. Create
                    // only the technical parent node so the child templates can be migrated
                    // with their original metadata. Do not synthesize a course/session/group
                    // link for the folder itself.
                    $effectiveFiletype = (string) ($document->getFiletype() ?? $legacyFiletype);
                    if (empty($items) && 'cert_folder' === $effectiveFiletype) {
                        $document->setParent($parent);
                        $resourceNode = $documentRepo->addResourceNode(
                            $document,
                            $admin,
                            $parent,
                            $documentResourceType,
                            false
                        );
                        $this->entityManager->persist($resourceNode);
                        $this->entityManager->persist($document);

                        continue;
                    }

                    $ok = $this->fixItemProperty(
                        'document',
                        $documentRepo,
                        $courseEntity,
                        $admin,
                        $document,
                        $parent,
                        $items,
                        $documentResourceType,
                        false
                    );
                    if (false === $ok) {
                        continue;
                    }

                    // Folders/system folders: no file upload step.
                    $effectiveFiletype = (string) ($document->getFiletype() ?? $legacyFiletype);
                    if (\in_array($effectiveFiletype, self::FOLDER_LIKE_FILETYPES, true)) {
                        continue;
                    }

                    $documentPathRel = ltrim($documentPath, '/');
                    $filePath = $baseDocumentsPath.$documentPathRel;

                    if (!$this->fileExists($filePath)) {
                        continue;
                    }

                    $filePathToUpload = $filePath;

                    $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
                    if ('html' === $ext || 'htm' === $ext) {
                        $filePathToUpload = $this->rewriteHtmlFileLegacyLinksIfNeeded($filePath, $courseDirectory);
                        if (!$this->fileExists($filePathToUpload)) {
                            continue;
                        }
                    }

                    $originalFilename = basename($filePath);

                    $this->addLegacyFileToResource(
                        $filePathToUpload,
                        $documentRepo,
                        $document,
                        $documentId,
                        $originalFilename
                    );
                }

                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function down(Schema $schema): void {}

    private function attemptHasFeedback(int $attemptId): bool
    {
        $v = $this->connection->fetchOne(
            'SELECT 1 FROM attempt_feedback WHERE attempt_id = :aid AND asset_id IS NOT NULL LIMIT 1',
            ['aid' => $attemptId]
        );

        return false !== $v;
    }

    private function attemptHasFiles(int $attemptId): bool
    {
        $v = $this->connection->fetchOne(
            'SELECT 1 FROM attempt_file WHERE attempt_id = :aid AND asset_id IS NOT NULL LIMIT 1',
            ['aid' => $attemptId]
        );

        return false !== $v;
    }

    /**
     * @param array<int, array{0: int, 1: int, 2: Asset}> $pendingRows
     */
    private function flushPendingFeedbackRows(array $pendingRows): void
    {
        try {
            $this->entityManager->flush();
            foreach ($pendingRows as [$attemptId, $userId, $asset]) {
                $this->insertAttemptFeedbackRow($attemptId, $userId, $asset);
            }
            $this->entityManager->clear();

            return;
        } catch (Throwable) {
            $this->entityManager->clear();
        }

        // A single bad file must not drop the whole batch: retry one by one,
        // exactly like the original per-row flush this batching replaced.
        foreach ($pendingRows as [$attemptId, $userId, $asset]) {
            try {
                $this->entityManager->persist($asset);
                $this->entityManager->flush();
                $this->insertAttemptFeedbackRow($attemptId, $userId, $asset);
            } catch (Throwable) {
                // Ignore single-file failures to keep the migration running.
            }
        }

        $this->entityManager->clear();
    }

    /**
     * @param array<int, array{0: int, 1: Asset}> $pendingRows
     */
    private function flushPendingFileRows(array $pendingRows): void
    {
        try {
            $this->entityManager->flush();
            foreach ($pendingRows as [$attemptId, $asset]) {
                $this->insertAttemptFileRow($attemptId, $asset);
            }
            $this->entityManager->clear();

            return;
        } catch (Throwable) {
            $this->entityManager->clear();
        }

        // A single bad file must not drop the whole batch: retry one by one,
        // exactly like the original per-row flush this batching replaced.
        foreach ($pendingRows as [$attemptId, $asset]) {
            try {
                $this->entityManager->persist($asset);
                $this->entityManager->flush();
                $this->insertAttemptFileRow($attemptId, $asset);
            } catch (Throwable) {
                // Ignore single-file failures to keep the migration running.
            }
        }

        $this->entityManager->clear();
    }

    private function insertAttemptFeedbackRow(int $attemptId, int $userId, Asset $asset): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

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
    }

    private function ensureItemPropertyMigrationIndex(): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array('c_item_property', $schemaManager->listTableNames(), true)) {
                return;
            }

            foreach ($schemaManager->listTableIndexes('c_item_property') as $index) {
                if (self::ITEM_PROPERTY_INDEX === strtolower($index->getName())) {
                    return;
                }

                $columns = array_map('strtolower', $index->getColumns());
                if (\count($columns) >= 2
                    && 'tool' === $columns[0]
                    && 'ref' === $columns[1]
                ) {
                    return;
                }
            }

            $this->getLogger()->notice('Creating temporary migration index on c_item_property.', [
                'index' => self::ITEM_PROPERTY_INDEX,
            ]);
            $this->connection->executeStatement(
                'CREATE INDEX '.self::ITEM_PROPERTY_INDEX.' ON c_item_property (tool, ref, c_id)'
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create c_item_property migration index; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function ensureCDocumentCidIndex(): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array('c_document', $schemaManager->listTableNames(), true)) {
                return;
            }

            foreach ($schemaManager->listTableIndexes('c_document') as $index) {
                if (self::CDOCUMENT_CID_INDEX === strtolower($index->getName())) {
                    return;
                }

                $columns = array_map('strtolower', $index->getColumns());
                if ([] !== $columns && 'c_id' === $columns[0]) {
                    return;
                }
            }

            $this->getLogger()->notice('Creating temporary migration index on c_document.', [
                'index' => self::CDOCUMENT_CID_INDEX,
            ]);
            $this->connection->executeStatement(
                'CREATE INDEX '.self::CDOCUMENT_CID_INDEX.' ON c_document (c_id)'
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create c_document migration index; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function hasStudentExerciseAudioDocuments(): bool
    {
        $value = $this->connection->fetchOne(
            'SELECT 1
             FROM c_document
             WHERE path LIKE :pattern
               AND path NOT LIKE :teacherPattern
             LIMIT 1',
            [
                'pattern' => '/../exercises/%',
                'teacherPattern' => '/../exercises/teacher_audio%',
            ]
        );

        return false !== $value;
    }

    private function ensureTrackEAttemptLookupIndex(): void
    {
        try {
            $schemaManager = $this->connection->createSchemaManager();
            if (!\in_array('track_e_attempt', $schemaManager->listTableNames(), true)) {
                return;
            }

            foreach ($schemaManager->listTableIndexes('track_e_attempt') as $index) {
                if (self::TRACK_E_ATTEMPT_LOOKUP_INDEX === strtolower($index->getName())) {
                    return;
                }

                $columns = array_map('strtolower', $index->getColumns());
                if (\count($columns) >= 3
                    && 'user_id' === $columns[0]
                    && 'question_id' === $columns[1]
                    && 'filename' === $columns[2]
                ) {
                    return;
                }
            }

            $this->getLogger()->notice('Creating temporary migration index on track_e_attempt.', [
                'index' => self::TRACK_E_ATTEMPT_LOOKUP_INDEX,
            ]);
            $this->connection->executeStatement(
                'CREATE INDEX '.self::TRACK_E_ATTEMPT_LOOKUP_INDEX.' ON track_e_attempt (user_id, question_id, filename)'
            );
        } catch (Throwable $exception) {
            $this->getLogger()->warning('Could not create track_e_attempt migration index; continuing safely.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function ensureCDocumentFiletypeVarchar15(): void
    {
        try {
            $this->connection->executeStatement('ALTER TABLE c_document MODIFY filetype VARCHAR(15);');
        } catch (Throwable) {
            // Ignore schema errors to keep migrations resilient across DB variants.
        }
    }

    private function normalizeLegacyDocumentPath(string $path): string
    {
        $p = str_replace('//', '/', trim($path));
        if ('' === $p) {
            return '';
        }

        if ('/' !== $p[0]) {
            $p = '/'.$p;
        }

        return $p;
    }

    private function detectSystemFolderType(string $legacyPath): ?string
    {
        if ('' === $legacyPath) {
            return null;
        }

        if (preg_match('#^/shared_folder_session_\d+(/|$)#', $legacyPath)) {
            return 'user_folder_ses';
        }

        if (str_starts_with($legacyPath, '/shared_folder')) {
            return 'user_folder';
        }

        if (preg_match('#^/(images|audio|flash|video)(/|$)#', $legacyPath)) {
            return 'media_folder';
        }

        if (str_starts_with($legacyPath, '/certificates')) {
            return 'cert_folder';
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
