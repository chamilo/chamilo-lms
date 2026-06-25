<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Entity\CStudentPublicationCorrection;
use Chamilo\CourseBundle\Repository\CStudentPublicationCommentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationCorrectionRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class Version20201217124011 extends AbstractMigrationChamilo
{
    private const RESOURCE_BATCH_SIZE = 500;
    private const COMMENT_BATCH_SIZE = 250;
    private const FILE_FLUSH_BATCH_SIZE = 25;
    private const RESOURCE_NODE_TITLE_MAX_LENGTH = 255;

    public function getDescription(): string
    {
        return 'Migrate student publications with resumable DBAL batches and batched files';
    }

    public function isTransactional(): bool
    {
        // Resource metadata is committed per batch and file phases are idempotent.
        return false;
    }

    public function up(Schema $schema): void
    {
        $resourceTypeIds = [
            'publication' => $this->getResourceTypeId('student_publications'),
            'assignment' => $this->getResourceTypeId('student_publications_assignments'),
            'comment' => $this->getResourceTypeId('student_publications_comments'),
            'correction' => $this->getResourceTypeId('student_publications_corrections'),
        ];

        $fallbackAdminId = $this->getFallbackAdminId();
        $uuidIsBinary = $this->detectUuidIsBinary();

        $this->getLogger()->info('Starting fast student publication migration.', [
            'resource_batch_size' => self::RESOURCE_BATCH_SIZE,
            'file_flush_batch_size' => self::FILE_FLUSH_BATCH_SIZE,
            'uuid_is_binary' => $uuidIsBinary,
        ]);

        $this->migrateAssignments(
            $resourceTypeIds['assignment'],
            $fallbackAdminId,
            $uuidIsBinary
        );
        $this->migrateSubmissions(
            $resourceTypeIds['publication'],
            $fallbackAdminId,
            $uuidIsBinary
        );
        $this->migrateComments(
            $resourceTypeIds['comment'],
            $fallbackAdminId,
            $uuidIsBinary
        );

        // Files and corrections need the configured resource filesystem and
        // therefore remain repository-based, but are separated from metadata,
        // batched and idempotent.
        $this->migratePublicationFiles();
        $this->migrateCorrections($resourceTypeIds['correction']);
        $this->migrateCommentFiles();

        $this->getLogger()->info('Completed fast student publication migration.', $this->buildFinalSummary());
    }

    private function migrateAssignments(int $resourceTypeId, int $fallbackAdminId, bool $uuidIsBinary): void
    {
        $sql = <<<'SQL'
SELECT
    e.iid,
    e.c_id,
    e.title,
    e.iid AS display_order,
    c.resource_node_id AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM c_student_publication e
INNER JOIN course c
    ON c.id = e.c_id
INNER JOIN resource_node parent
    ON parent.id = c.resource_node_id
WHERE e.resource_node_id IS NULL
  AND e.contains_file = 0
  AND e.iid > :lastIid
ORDER BY e.iid
LIMIT %d
SQL;

        $this->migrateResourceRows(
            entityName: 'student publication assignments',
            table: 'c_student_publication',
            tool: 'work',
            resourceTypeId: $resourceTypeId,
            selectSql: \sprintf($sql, self::RESOURCE_BATCH_SIZE),
            fallbackTitlePrefix: 'Assignment',
            slugPrefix: 'student-publication-assignment',
            batchSize: self::RESOURCE_BATCH_SIZE,
            fallbackAdminId: $fallbackAdminId,
            uuidIsBinary: $uuidIsBinary
        );
    }

    private function migrateSubmissions(int $resourceTypeId, int $fallbackAdminId, bool $uuidIsBinary): void
    {
        $sql = <<<'SQL'
SELECT
    e.iid,
    e.c_id,
    e.title,
    e.iid AS display_order,
    assignment.resource_node_id AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM c_student_publication e
INNER JOIN c_student_publication assignment
    ON assignment.iid = e.parent_id
INNER JOIN resource_node parent
    ON parent.id = assignment.resource_node_id
WHERE e.resource_node_id IS NULL
  AND e.contains_file = 1
  AND e.iid > :lastIid
ORDER BY e.iid
LIMIT %d
SQL;

        $this->migrateResourceRows(
            entityName: 'student publication submissions',
            table: 'c_student_publication',
            tool: 'work',
            resourceTypeId: $resourceTypeId,
            selectSql: \sprintf($sql, self::RESOURCE_BATCH_SIZE),
            fallbackTitlePrefix: 'Submission',
            slugPrefix: 'student-publication',
            batchSize: self::RESOURCE_BATCH_SIZE,
            fallbackAdminId: $fallbackAdminId,
            uuidIsBinary: $uuidIsBinary
        );
    }

    private function migrateComments(int $resourceTypeId, int $fallbackAdminId, bool $uuidIsBinary): void
    {
        $totalPending = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM c_student_publication_comment WHERE resource_node_id IS NULL'
        );

        if (0 === $totalPending) {
            $this->getLogger()->info('No pending student publication comments.');

            return;
        }

        $lastIid = 0;
        $seen = 0;
        $migrated = 0;
        $skippedMissingParent = 0;
        $startedAt = microtime(true);

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(<<<'SQL'
SELECT
    comment.iid,
    comment.c_id,
    comment.comment AS title,
    comment.user_id,
    publication.resource_node_id AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM c_student_publication_comment comment
INNER JOIN c_student_publication publication
    ON publication.iid = comment.work_id
INNER JOIN resource_node parent
    ON parent.id = publication.resource_node_id
WHERE comment.resource_node_id IS NULL
  AND comment.iid > :lastIid
ORDER BY comment.iid
LIMIT %d
SQL, self::COMMENT_BATCH_SIZE),
                ['lastIid' => $lastIid]
            );

            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[\array_key_last($rows)]['iid'];
            $seen += \count($rows);
            $preparedRows = [];

            foreach ($rows as $row) {
                $parentNodeId = (int) $row['parent_node_id'];
                $contexts = $this->fetchParentContexts($parentNodeId);
                if ([] === $contexts) {
                    ++$skippedMissingParent;
                    continue;
                }

                $iid = (int) $row['iid'];
                $creatorId = $this->resolveValidUserId((int) ($row['user_id'] ?? 0), $fallbackAdminId);
                $preparedRows[] = [
                    'iid' => $iid,
                    'course_id' => (int) $row['c_id'],
                    'title' => $this->normalizeResourceTitle((string) ($row['title'] ?? ''), 'Comment', $iid),
                    'slug' => 'student-publication-comment-'.$iid,
                    'display_order' => $iid,
                    'parent_node_id' => $parentNodeId,
                    'parent_path' => (string) $row['parent_path'],
                    'parent_level' => (int) $row['parent_level'],
                    'creator_id' => $creatorId,
                    'contexts' => $contexts,
                ];
            }

            if ([] !== $preparedRows) {
                $this->persistResourceBatch(
                    table: 'c_student_publication_comment',
                    resourceTypeId: $resourceTypeId,
                    rows: $preparedRows,
                    uuidIsBinary: $uuidIsBinary,
                    contextsKey: 'contexts'
                );
                $migrated += \count($preparedRows);
            }

            $this->getLogger()->info('Student publication comment migration progress.', [
                'seen' => $seen,
                'migrated' => $migrated,
                'initial_pending' => $totalPending,
                'last_iid' => $lastIid,
                'missing_parent_context' => $skippedMissingParent,
                'elapsed_seconds' => (int) (microtime(true) - $startedAt),
            ]);
        }
    }

    private function migrateResourceRows(
        string $entityName,
        string $table,
        string $tool,
        int $resourceTypeId,
        string $selectSql,
        string $fallbackTitlePrefix,
        string $slugPrefix,
        int $batchSize,
        int $fallbackAdminId,
        bool $uuidIsBinary
    ): void {
        $totalPending = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM {$table} WHERE resource_node_id IS NULL"
        );

        if (0 === $totalPending) {
            $this->getLogger()->info("No pending {$entityName}.");

            return;
        }

        $lastIid = 0;
        $seen = 0;
        $migrated = 0;
        $skippedMissingItemProperty = 0;
        $startedAt = microtime(true);

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                $selectSql,
                ['lastIid' => $lastIid]
            );

            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[\array_key_last($rows)]['iid'];
            $seen += \count($rows);
            $itemProperties = $this->fetchItemProperties($tool, $rows);
            $preparedRows = [];

            foreach ($rows as $row) {
                $iid = (int) $row['iid'];
                $courseId = (int) $row['c_id'];
                $key = $this->itemPropertyKey($courseId, $iid);
                $items = $itemProperties[$key] ?? [];

                if ([] === $items) {
                    ++$skippedMissingItemProperty;
                    continue;
                }

                $creatorId = !empty($items[0]['valid_user_id'])
                    ? (int) $items[0]['valid_user_id']
                    : $fallbackAdminId;

                $preparedRows[] = [
                    'iid' => $iid,
                    'course_id' => $courseId,
                    'title' => $this->normalizeResourceTitle(
                        (string) ($row['title'] ?? ''),
                        $fallbackTitlePrefix,
                        $iid
                    ),
                    'slug' => $slugPrefix.'-'.$iid,
                    'display_order' => (int) ($row['display_order'] ?? $iid),
                    'parent_node_id' => (int) $row['parent_node_id'],
                    'parent_path' => (string) $row['parent_path'],
                    'parent_level' => (int) $row['parent_level'],
                    'creator_id' => $creatorId,
                    'item_properties' => $items,
                ];
            }

            if ([] !== $preparedRows) {
                $this->persistResourceBatch(
                    table: $table,
                    resourceTypeId: $resourceTypeId,
                    rows: $preparedRows,
                    uuidIsBinary: $uuidIsBinary,
                    contextsKey: 'item_properties'
                );
                $migrated += \count($preparedRows);
            }

            $elapsedSeconds = max(1, (int) (microtime(true) - $startedAt));
            $rate = $seen / $elapsedSeconds;
            $remaining = max(0, $totalPending - $seen);

            $this->getLogger()->info("{$entityName} migration progress.", [
                'seen' => $seen,
                'migrated' => $migrated,
                'initial_pending' => $totalPending,
                'last_iid' => $lastIid,
                'missing_item_property' => $skippedMissingItemProperty,
                'rows_per_second' => round($rate, 2),
                'eta_seconds' => $rate > 0 ? (int) round($remaining / $rate) : null,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function fetchItemProperties(string $tool, array $rows): array
    {
        $refs = [];
        foreach ($rows as $row) {
            $refs[] = (int) $row['iid'];
        }

        $sql = <<<'SQL'
SELECT
    ip.iid,
    ip.c_id,
    ip.ref,
    ip.visibility,
    ip.insert_user_id,
    ip.session_id,
    ip.to_group_id,
    ip.lastedit_date,
    valid_user.id AS valid_user_id,
    valid_session.id AS valid_session_id,
    valid_group.iid AS valid_group_id
FROM c_item_property ip
LEFT JOIN user valid_user
    ON valid_user.id = ip.insert_user_id
LEFT JOIN session valid_session
    ON valid_session.id = ip.session_id
LEFT JOIN c_group_info valid_group
    ON valid_group.iid = ip.to_group_id
WHERE ip.tool = :tool
  AND ip.ref IN (:refs)
ORDER BY ip.ref, ip.c_id, ip.iid
SQL;

        $items = $this->connection->executeQuery(
            $sql,
            [
                'tool' => $tool,
                'refs' => \array_values(\array_unique($refs)),
            ],
            [
                'refs' => ArrayParameterType::INTEGER,
            ]
        )->fetchAllAssociative();

        $map = [];
        foreach ($items as $item) {
            $map[$this->itemPropertyKey((int) $item['c_id'], (int) $item['ref'])][] = $item;
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchParentContexts(int $parentNodeId): array
    {
        return $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT
    rl.c_id,
    rl.session_id AS valid_session_id,
    rl.group_id AS valid_group_id,
    rl.deleted_at,
    rl.visibility
FROM resource_link rl
WHERE rl.resource_node_id = :parentNodeId
  AND rl.c_id IS NOT NULL
ORDER BY rl.id
SQL,
            ['parentNodeId' => $parentNodeId]
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function persistResourceBatch(
        string $table,
        int $resourceTypeId,
        array $rows,
        bool $uuidIsBinary,
        string $contextsKey
    ): void {
        $this->connection->beginTransaction();

        try {
            $now = gmdate('Y-m-d H:i:s');
            $nodeRows = [];
            $uuidKeysBySourceIid = [];

            foreach ($rows as $row) {
                $uuid = Uuid::v4();
                $uuidValue = $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122();
                $uuidKey = $uuidIsBinary ? bin2hex($uuidValue) : $uuidValue;
                $uuidKeysBySourceIid[(int) $row['iid']] = $uuidKey;

                $nodeRows[] = [
                    'title' => $row['title'],
                    'slug' => $row['slug'],
                    'level' => ((int) $row['parent_level']) + 1,
                    'path' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'public' => 0,
                    'uuid' => $uuidValue,
                    'resource_type_id' => $resourceTypeId,
                    'resource_format_id' => null,
                    'language_id' => null,
                    'creator_id' => (int) $row['creator_id'],
                    'parent_id' => (int) $row['parent_node_id'],
                ];
            }

            $this->bulkInsert(
                'resource_node',
                [
                    'title', 'slug', 'level', 'path', 'created_at', 'updated_at',
                    'public', 'uuid', 'resource_type_id', 'resource_format_id',
                    'language_id', 'creator_id', 'parent_id',
                ],
                $nodeRows,
                $uuidIsBinary ? ['uuid'] : []
            );

            $nodeIdsByUuid = $this->fetchNodeIdsByUuid($nodeRows, $uuidIsBinary);
            if (\count($nodeIdsByUuid) !== \count($rows)) {
                throw new RuntimeException(
                    \sprintf(
                        'Expected %d inserted resource nodes for %s, found %d.',
                        \count($rows),
                        $table,
                        \count($nodeIdsByUuid)
                    )
                );
            }

            $sourceMappings = [];
            $pathMappings = [];
            $resourceLinkRows = [];
            $nodeIds = [];

            foreach ($rows as $row) {
                $iid = (int) $row['iid'];
                $uuidKey = $uuidKeysBySourceIid[$iid];
                $resourceNodeId = $nodeIdsByUuid[$uuidKey] ?? 0;
                if ($resourceNodeId <= 0) {
                    throw new RuntimeException("Resource node not resolved for source row {$iid}.");
                }

                $nodeIds[] = $resourceNodeId;
                $sourceMappings[$iid] = $resourceNodeId;
                $pathMappings[$resourceNodeId] = $this->buildResourcePath(
                    (string) $row['parent_path'],
                    (string) $row['title'],
                    $iid,
                    $resourceNodeId
                );

                $contexts = [];
                foreach ($row[$contextsKey] as $context) {
                    $sessionId = !empty($context['valid_session_id'])
                        ? (int) $context['valid_session_id']
                        : null;
                    $groupId = !empty($context['valid_group_id'])
                        ? (int) $context['valid_group_id']
                        : null;
                    $courseId = !empty($context['c_id'])
                        ? (int) $context['c_id']
                        : (int) $row['course_id'];
                    $contextKey = $courseId.':'.($sessionId ?? 0).':'.($groupId ?? 0);
                    if (isset($contexts[$contextKey])) {
                        continue;
                    }
                    $contexts[$contextKey] = true;

                    if ('item_properties' === $contextsKey) {
                        $legacyVisibility = (int) ($context['visibility'] ?? 0);
                        $visibility = 1 === $legacyVisibility
                            ? ResourceLink::VISIBILITY_PUBLISHED
                            : ResourceLink::VISIBILITY_DRAFT;
                        $deletedAt = null;
                        if (2 === $legacyVisibility) {
                            $lastEditDate = trim((string) ($context['lastedit_date'] ?? ''));
                            $deletedAt = '' !== $lastEditDate ? $lastEditDate : $now;
                        }
                    } else {
                        // Legacy comments were explicitly linked as published.
                        $visibility = ResourceLink::VISIBILITY_PUBLISHED;
                        $deletedAt = $context['deleted_at'] ?? null;
                    }

                    $resourceLinkRows[] = [
                        'resource_node_id' => $resourceNodeId,
                        'c_id' => $courseId,
                        'session_id' => $sessionId,
                        'usergroup_id' => null,
                        'group_id' => $groupId,
                        'user_id' => null,
                        'visibility' => $visibility,
                        'start_visibility_at' => null,
                        'end_visibility_at' => null,
                        'display_order' => (int) $row['display_order'],
                        'resource_type_group' => $resourceTypeId,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => $deletedAt,
                        'parent_id' => null,
                    ];
                }
            }

            $this->bulkUpdateById($table, 'iid', 'resource_node_id', $sourceMappings);
            $this->bulkUpdateById('resource_node', 'id', 'path', $pathMappings);
            $this->bulkInsert(
                'resource_link',
                [
                    'resource_node_id', 'c_id', 'session_id', 'usergroup_id',
                    'group_id', 'user_id', 'visibility', 'start_visibility_at',
                    'end_visibility_at', 'display_order', 'resource_type_group',
                    'created_at', 'updated_at', 'deleted_at', 'parent_id',
                ],
                $resourceLinkRows
            );

            $draftLinks = $this->connection->executeQuery(
                'SELECT id FROM resource_link WHERE resource_node_id IN (:nodeIds) AND visibility = :visibility',
                [
                    'nodeIds' => $nodeIds,
                    'visibility' => ResourceLink::VISIBILITY_DRAFT,
                ],
                [
                    'nodeIds' => ArrayParameterType::INTEGER,
                ]
            )->fetchFirstColumn();

            if ([] !== $draftLinks) {
                $rightRows = [];
                $editorMask = ResourceNodeVoter::getEditorMask();
                foreach ($draftLinks as $resourceLinkId) {
                    $rightRows[] = [
                        'resource_link_id' => (int) $resourceLinkId,
                        'role' => ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER,
                        'mask' => $editorMask,
                    ];
                }
                $this->bulkInsert('resource_right', ['resource_link_id', 'role', 'mask'], $rightRows);
            }

            $this->connection->commit();
        } catch (Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw new RuntimeException(
                "Fast student publication migration failed for {$table}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $nodeRows
     *
     * @return array<string, int>
     */
    private function fetchNodeIdsByUuid(array $nodeRows, bool $uuidIsBinary): array
    {
        $placeholders = [];
        $parameters = [];
        $types = [];

        foreach ($nodeRows as $index => $row) {
            $name = 'uuid_'.$index;
            $placeholders[] = ':'.$name;
            $parameters[$name] = $row['uuid'];
            if ($uuidIsBinary) {
                $types[$name] = ParameterType::BINARY;
            }
        }

        $rows = $this->connection->executeQuery(
            'SELECT id, uuid FROM resource_node WHERE uuid IN ('.\implode(', ', $placeholders).')',
            $parameters,
            $types
        )->fetchAllAssociative();

        $result = [];
        foreach ($rows as $row) {
            $key = $uuidIsBinary ? bin2hex((string) $row['uuid']) : (string) $row['uuid'];
            $result[$key] = (int) $row['id'];
        }

        return $result;
    }

    private function migratePublicationFiles(): void
    {
        /** @var CStudentPublicationRepository $repository */
        $repository = $this->container->get(CStudentPublicationRepository::class);
        $existingFiles = $this->loadExistingResourceFiles();
        $lastIid = 0;
        $processedSinceFlush = 0;
        $migrated = 0;
        $missing = 0;
        $seen = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(<<<'SQL'
SELECT
    publication.iid,
    publication.resource_node_id,
    publication.url,
    publication.title,
    publication.filesize,
    course.directory
FROM c_student_publication publication
INNER JOIN course
    ON course.id = publication.c_id
WHERE publication.contains_file = 1
  AND publication.resource_node_id IS NOT NULL
  AND publication.url IS NOT NULL
  AND publication.url <> ''
  AND publication.iid > :lastIid
ORDER BY publication.iid
LIMIT %d
SQL, self::RESOURCE_BATCH_SIZE),
                ['lastIid' => $lastIid]
            );

            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[\array_key_last($rows)]['iid'];

            foreach ($rows as $row) {
                ++$seen;
                $iid = (int) $row['iid'];
                $nodeId = (int) $row['resource_node_id'];
                $fileName = (string) $row['title'];
                $fileKey = $nodeId.':'.$fileName;
                if (isset($existingFiles[$fileKey])) {
                    continue;
                }

                $filePath = $this->getUpdateRootPath().'/app/courses/'.(string) $row['directory'].'/'.ltrim((string) $row['url'], '/');
                if (!$this->fileExists($filePath)) {
                    ++$missing;
                    $this->warnIf(true, "Student publication file {$iid} not found: {$filePath}");
                    continue;
                }

                $resource = $repository->find($iid);
                if (!$resource instanceof CStudentPublication || !$resource->hasResourceNode()) {
                    continue;
                }

                if ($this->addLegacyFileToResource($filePath, $repository, $resource, $iid, $fileName)) {
                    $this->entityManager->persist($resource);
                    $existingFiles[$fileKey] = true;
                    ++$migrated;
                    ++$processedSinceFlush;
                }

                if ($processedSinceFlush >= self::FILE_FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $processedSinceFlush = 0;
                }
            }

            $this->getLogger()->info('Student publication file migration progress.', [
                'seen' => $seen,
                'migrated' => $migrated,
                'missing_files' => $missing,
                'last_iid' => $lastIid,
            ]);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function migrateCorrections(int $correctionResourceTypeId): void
    {
        /** @var CStudentPublicationRepository $publicationRepository */
        $publicationRepository = $this->container->get(CStudentPublicationRepository::class);
        /** @var CStudentPublicationCorrectionRepository $correctionRepository */
        $correctionRepository = $this->container->get(CStudentPublicationCorrectionRepository::class);

        $admin = $this->getAdmin();
        $lastIid = 0;
        $processedSinceFlush = 0;
        $migrated = 0;
        $missingFiles = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(<<<'SQL'
SELECT
    publication.iid,
    publication.resource_node_id,
    publication.title_correction,
    publication.url_correction,
    course.directory
FROM c_student_publication publication
INNER JOIN course
    ON course.id = publication.c_id
WHERE publication.resource_node_id IS NOT NULL
  AND publication.title_correction IS NOT NULL
  AND TRIM(publication.title_correction) <> ''
  AND publication.iid > :lastIid
  AND NOT EXISTS (
      SELECT 1
      FROM resource_node child
      WHERE child.parent_id = publication.resource_node_id
        AND child.resource_type_id = :resourceTypeId
  )
ORDER BY publication.iid
LIMIT %d
SQL, self::RESOURCE_BATCH_SIZE),
                [
                    'lastIid' => $lastIid,
                    'resourceTypeId' => $correctionResourceTypeId,
                ]
            );

            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[\array_key_last($rows)]['iid'];

            foreach ($rows as $row) {
                $iid = (int) $row['iid'];
                $publication = $publicationRepository->find($iid);
                if (!$publication instanceof CStudentPublication || !$publication->hasResourceNode()) {
                    continue;
                }

                $correction = new CStudentPublicationCorrection();
                $correction->setTitle((string) $row['title_correction']);
                $correction->setParent($publication);
                $correctionRepository->addResourceNode($correction, $admin, $publication);
                $this->entityManager->persist($correction);

                $relativePath = trim((string) ($row['url_correction'] ?? ''));
                if ('' !== $relativePath) {
                    $filePath = $this->getUpdateRootPath().'/app/courses/'.(string) $row['directory'].'/'.ltrim($relativePath, '/');
                    if ($this->fileExists($filePath)) {
                        $this->addLegacyFileToResource(
                            $filePath,
                            $correctionRepository,
                            $correction,
                            null,
                            (string) $row['title_correction']
                        );
                    } else {
                        ++$missingFiles;
                        $this->warnIf(true, "Student publication correction file not found for publication {$iid}: {$filePath}");
                    }
                }

                ++$migrated;
                ++$processedSinceFlush;
                if ($processedSinceFlush >= self::FILE_FLUSH_BATCH_SIZE) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $processedSinceFlush = 0;
                    $admin = $this->getAdmin();
                }
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Student publication correction migration completed.', [
            'migrated' => $migrated,
            'missing_files' => $missingFiles,
        ]);
    }

    private function migrateCommentFiles(): void
    {
        /** @var CStudentPublicationCommentRepository $repository */
        $repository = $this->container->get(CStudentPublicationCommentRepository::class);
        $existingFiles = $this->loadExistingResourceFiles();
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT
    comment.iid,
    comment.resource_node_id,
    comment.file,
    course.directory
FROM c_student_publication_comment comment
INNER JOIN course
    ON course.id = comment.c_id
WHERE comment.resource_node_id IS NOT NULL
  AND comment.file IS NOT NULL
  AND comment.file <> ''
ORDER BY comment.iid
SQL
        );

        $processedSinceFlush = 0;
        $migrated = 0;
        $missing = 0;

        foreach ($rows as $row) {
            $iid = (int) $row['iid'];
            $nodeId = (int) $row['resource_node_id'];
            $relativePath = (string) $row['file'];
            $fileName = basename($relativePath);
            $fileKey = $nodeId.':'.$fileName;
            if (isset($existingFiles[$fileKey])) {
                continue;
            }

            $filePath = $this->getUpdateRootPath().'/app/courses/'.(string) $row['directory'].'/'.ltrim($relativePath, '/');
            if (!$this->fileExists($filePath)) {
                ++$missing;
                $this->warnIf(true, "Student publication comment file {$iid} not found: {$filePath}");
                continue;
            }

            $comment = $repository->find($iid);
            if (!$comment instanceof CStudentPublicationComment || !$comment->hasResourceNode()) {
                continue;
            }

            // Use the comment repository and actual file name. The legacy
            // migration incorrectly used the publication repository and a
            // stale/undefined title variable here.
            if ($this->addLegacyFileToResource($filePath, $repository, $comment, $iid, $fileName)) {
                $this->entityManager->persist($comment);
                $existingFiles[$fileKey] = true;
                ++$migrated;
                ++$processedSinceFlush;
            }

            if ($processedSinceFlush >= self::FILE_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $processedSinceFlush = 0;
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Student publication comment file migration completed.', [
            'candidates' => \count($rows),
            'migrated' => $migrated,
            'missing_files' => $missing,
        ]);
    }

    /**
     * @return array<string, true>
     */
    private function loadExistingResourceFiles(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT resource_node_id, original_name FROM resource_file WHERE resource_node_id IS NOT NULL'
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['resource_node_id'].':'.(string) $row['original_name']] = true;
        }

        return $result;
    }

    /**
     * @param array<int, string>               $columns
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string>               $binaryColumns
     */
    private function bulkInsert(
        string $table,
        array $columns,
        array $rows,
        array $binaryColumns = []
    ): void {
        if ([] === $rows) {
            return;
        }

        $valueGroups = [];
        $parameters = [];
        $types = [];

        foreach ($rows as $rowIndex => $row) {
            $placeholders = [];
            foreach ($columns as $column) {
                $parameterName = 'p_'.$rowIndex.'_'.\preg_replace('/[^a-zA-Z0-9_]/', '_', $column);
                $placeholders[] = ':'.$parameterName;
                $parameters[$parameterName] = $row[$column] ?? null;
                if (\in_array($column, $binaryColumns, true)) {
                    $types[$parameterName] = ParameterType::BINARY;
                }
            }
            $valueGroups[] = '('.\implode(', ', $placeholders).')';
        }

        $this->connection->executeStatement(
            \sprintf(
                'INSERT INTO %s (%s) VALUES %s',
                $table,
                \implode(', ', $columns),
                \implode(', ', $valueGroups)
            ),
            $parameters,
            $types
        );
    }

    /**
     * @param array<int, int|string|null> $mappings
     */
    private function bulkUpdateById(
        string $table,
        string $idColumn,
        string $valueColumn,
        array $mappings
    ): void {
        if ([] === $mappings) {
            return;
        }

        $cases = [];
        $where = [];
        $parameters = [];
        $index = 0;

        foreach ($mappings as $id => $value) {
            $idParameter = 'case_id_'.$index;
            $valueParameter = 'case_value_'.$index;
            $whereParameter = 'where_id_'.$index;
            $cases[] = "WHEN :{$idParameter} THEN :{$valueParameter}";
            $where[] = ':'.$whereParameter;
            $parameters[$idParameter] = (int) $id;
            $parameters[$valueParameter] = $value;
            $parameters[$whereParameter] = (int) $id;
            ++$index;
        }

        $this->connection->executeStatement(
            \sprintf(
                'UPDATE %s SET %s = CASE %s %s ELSE %s END WHERE %s IN (%s)',
                $table,
                $valueColumn,
                $idColumn,
                \implode(' ', $cases),
                $valueColumn,
                $idColumn,
                \implode(', ', $where)
            ),
            $parameters
        );
    }

    private function getResourceTypeId(string $title): int
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM resource_type WHERE title = :title',
            ['title' => $title]
        );

        if (false === $id || (int) $id <= 0) {
            throw new RuntimeException("Resource type '{$title}' was not found.");
        }

        return (int) $id;
    }

    private function getFallbackAdminId(): int
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM user WHERE roles LIKE :role ORDER BY id LIMIT 1',
            ['role' => '%ROLE_ADMIN%']
        );
        if (false === $id) {
            $id = $this->connection->fetchOne('SELECT id FROM user ORDER BY id LIMIT 1');
        }
        if (false === $id || (int) $id <= 0) {
            throw new RuntimeException('No fallback user could be resolved for student publication creators.');
        }

        return (int) $id;
    }

    private function resolveValidUserId(int $userId, int $fallbackAdminId): int
    {
        if ($userId <= 0) {
            return $fallbackAdminId;
        }

        return false !== $this->connection->fetchOne(
            'SELECT id FROM user WHERE id = :id',
            ['id' => $userId]
        ) ? $userId : $fallbackAdminId;
    }

    private function normalizeResourceTitle(string $title, string $fallbackPrefix, int $iid): string
    {
        $title = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!mb_check_encoding($title, 'UTF-8')) {
            $title = (string) iconv('UTF-8', 'UTF-8//IGNORE', $title);
        }
        $title = preg_replace('/\s+/u', ' ', trim($title));
        if (null === $title || '' === $title) {
            $title = $fallbackPrefix.' #'.$iid;
        }
        $title = str_replace(['/', '\\'], '-', $title);
        if (mb_strlen($title) > self::RESOURCE_NODE_TITLE_MAX_LENGTH) {
            $title = mb_substr($title, 0, self::RESOURCE_NODE_TITLE_MAX_LENGTH - 3).'...';
        }

        return $title;
    }

    private function buildResourcePath(
        string $parentPath,
        string $title,
        int $iid,
        int $resourceNodeId
    ): string {
        $parentPath = rtrim($parentPath, '/');
        if ('' === $parentPath) {
            throw new RuntimeException("Parent resource path is empty for student publication {$iid}.");
        }
        $segment = preg_replace('/\s+/u', ' ', trim($title));
        if (null === $segment || '' === $segment) {
            $segment = 'resource-'.$iid;
        }
        $segment = str_replace(['/', '\\'], '-', $segment);

        return $parentPath.'/'.$segment.'-'.$iid.'-'.$resourceNodeId.'/';
    }

    private function itemPropertyKey(int $courseId, int $ref): string
    {
        return $courseId.':'.$ref;
    }

    private function detectUuidIsBinary(): bool
    {
        try {
            $table = $this->connection->createSchemaManager()->introspectTable('resource_node');
            if (!$table->hasColumn('uuid')) {
                return false;
            }
            $column = $table->getColumn('uuid');
            $type = $column->getType()->getName();
            $length = $column->getLength();

            return \in_array($type, ['binary', 'varbinary'], true) || 16 === $length;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, int>
     */
    private function buildFinalSummary(): array
    {
        return [
            'pending_assignments' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_student_publication WHERE contains_file = 0 AND resource_node_id IS NULL'
            ),
            'pending_submissions' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_student_publication WHERE contains_file = 1 AND resource_node_id IS NULL'
            ),
            'pending_comments' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_student_publication_comment WHERE resource_node_id IS NULL'
            ),
        ];
    }
}
