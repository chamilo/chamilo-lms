<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Repository\CForumPostRepository;
use Chamilo\CourseBundle\Repository\CForumRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class Version20201215160445 extends AbstractMigrationChamilo
{
    private const SMALL_BATCH_SIZE = 500;
    private const POST_BATCH_SIZE = 1500;
    private const FILE_FLUSH_BATCH_SIZE = 25;
    private const RESOURCE_NODE_TITLE_MAX_LENGTH = 255;
    private const ITEM_PROPERTY_INDEX = 'idx_ricky_migration_item_property_tool_ref_course';

    public function getDescription(): string
    {
        return 'Migrate c_forum tables with resumable DBAL batches';
    }

    public function isTransactional(): bool
    {
        // Each DBAL batch has its own transaction. This makes the migration
        // resumable when a long forum migration is interrupted.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->ensureItemPropertyMigrationIndex();

        $resourceTypeIds = [
            'forum_category' => $this->getResourceTypeId('forum_categories'),
            'forum' => $this->getResourceTypeId('forums'),
            'forum_thread' => $this->getResourceTypeId('forum_threads'),
            'forum_post' => $this->getResourceTypeId('forum_posts'),
        ];

        $fallbackAdminId = $this->getFallbackAdminId();
        $uuidIsBinary = $this->detectUuidIsBinary();

        $this->getLogger()->info('Starting fast forum migration.', [
            'small_batch_size' => self::SMALL_BATCH_SIZE,
            'post_batch_size' => self::POST_BATCH_SIZE,
            'uuid_is_binary' => $uuidIsBinary,
        ]);

        $this->migrateCategories(
            $resourceTypeIds['forum_category'],
            $fallbackAdminId,
            $uuidIsBinary
        );
        $this->migrateForums(
            $resourceTypeIds['forum'],
            $fallbackAdminId,
            $uuidIsBinary
        );
        $this->migrateThreads(
            $resourceTypeIds['forum_thread'],
            $fallbackAdminId,
            $uuidIsBinary
        );
        $this->migratePosts(
            $resourceTypeIds['forum_post'],
            $fallbackAdminId,
            $uuidIsBinary
        );

        // File migration remains repository-based because it must use the
        // configured resource filesystem. It is deliberately separated from
        // the 1.3M-row DBAL post migration and is idempotent by node/name/size.
        $this->migrateForumImages();
        $this->migratePostAttachments();

        $this->getLogger()->info('Completed fast forum migration.', $this->buildFinalSummary());
    }

    private function migrateCategories(int $resourceTypeId, int $fallbackAdminId, bool $uuidIsBinary): void
    {
        $sql = <<<'SQL'
SELECT
    e.iid,
    e.c_id,
    e.title,
    e.cat_order AS display_order,
    c.resource_node_id AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM c_forum_category e
INNER JOIN course c
    ON c.id = e.c_id
INNER JOIN resource_node parent
    ON parent.id = c.resource_node_id
WHERE e.resource_node_id IS NULL
  AND e.iid > :lastIid
ORDER BY e.iid
LIMIT %d
SQL;

        $this->migrateResourceRows(
            entityName: 'forum categories',
            table: 'c_forum_category',
            tool: 'forum_category',
            resourceTypeId: $resourceTypeId,
            selectSql: \sprintf($sql, self::SMALL_BATCH_SIZE),
            fallbackTitlePrefix: 'Forum category',
            slugPrefix: 'forum-category',
            batchSize: self::SMALL_BATCH_SIZE,
            fallbackAdminId: $fallbackAdminId,
            uuidIsBinary: $uuidIsBinary
        );
    }

    private function migrateForums(int $resourceTypeId, int $fallbackAdminId, bool $uuidIsBinary): void
    {
        $sql = <<<'SQL'
SELECT
    e.iid,
    e.c_id,
    e.title,
    COALESCE(e.forum_order, e.iid) AS display_order,
    CASE
        WHEN category.iid IS NULL THEN c.resource_node_id
        ELSE category.resource_node_id
    END AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM c_forum_forum e
INNER JOIN course c
    ON c.id = e.c_id
LEFT JOIN c_forum_category category
    ON category.iid = e.forum_category
INNER JOIN resource_node parent
    ON parent.id = CASE
        WHEN category.iid IS NULL THEN c.resource_node_id
        ELSE category.resource_node_id
    END
WHERE e.resource_node_id IS NULL
  AND e.iid > :lastIid
ORDER BY e.iid
LIMIT %d
SQL;

        $this->migrateResourceRows(
            entityName: 'forums',
            table: 'c_forum_forum',
            tool: 'forum',
            resourceTypeId: $resourceTypeId,
            selectSql: \sprintf($sql, self::SMALL_BATCH_SIZE),
            fallbackTitlePrefix: 'Forum',
            slugPrefix: 'forum',
            batchSize: self::SMALL_BATCH_SIZE,
            fallbackAdminId: $fallbackAdminId,
            uuidIsBinary: $uuidIsBinary
        );
    }

    private function migrateThreads(int $resourceTypeId, int $fallbackAdminId, bool $uuidIsBinary): void
    {
        $sql = <<<'SQL'
SELECT
    e.iid,
    e.c_id,
    e.title,
    e.iid AS display_order,
    forum.resource_node_id AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM c_forum_thread e
INNER JOIN c_forum_forum forum
    ON forum.iid = e.forum_id
INNER JOIN resource_node parent
    ON parent.id = forum.resource_node_id
WHERE e.resource_node_id IS NULL
  AND e.iid > :lastIid
ORDER BY e.iid
LIMIT %d
SQL;

        $this->migrateResourceRows(
            entityName: 'forum threads',
            table: 'c_forum_thread',
            tool: 'forum_thread',
            resourceTypeId: $resourceTypeId,
            selectSql: \sprintf($sql, self::SMALL_BATCH_SIZE),
            fallbackTitlePrefix: 'Forum thread',
            slugPrefix: 'forum-thread',
            batchSize: self::SMALL_BATCH_SIZE,
            fallbackAdminId: $fallbackAdminId,
            uuidIsBinary: $uuidIsBinary
        );
    }

    private function migratePosts(int $resourceTypeId, int $fallbackAdminId, bool $uuidIsBinary): void
    {
        $sql = <<<'SQL'
SELECT
    e.iid,
    e.c_id,
    e.title,
    e.iid AS display_order,
    thread.resource_node_id AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM c_forum_post e
INNER JOIN c_forum_thread thread
    ON thread.iid = e.thread_id
INNER JOIN c_forum_forum forum
    ON forum.iid = thread.forum_id
INNER JOIN resource_node parent
    ON parent.id = thread.resource_node_id
WHERE e.resource_node_id IS NULL
  AND e.iid > :lastIid
ORDER BY e.iid
LIMIT %d
SQL;

        $this->migrateResourceRows(
            entityName: 'forum posts',
            table: 'c_forum_post',
            tool: 'forum_post',
            resourceTypeId: $resourceTypeId,
            selectSql: \sprintf($sql, self::POST_BATCH_SIZE),
            fallbackTitlePrefix: 'Post',
            slugPrefix: 'forum-post',
            batchSize: self::POST_BATCH_SIZE,
            fallbackAdminId: $fallbackAdminId,
            uuidIsBinary: $uuidIsBinary
        );
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

        $this->getLogger()->info("Starting {$entityName} DBAL migration.", [
            'pending' => $totalPending,
            'batch_size' => $batchSize,
        ]);

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

                $firstItem = $items[0];
                $creatorId = !empty($firstItem['valid_user_id'])
                    ? (int) $firstItem['valid_user_id']
                    : $fallbackAdminId;
                $title = $this->normalizeResourceTitle(
                    (string) ($row['title'] ?? ''),
                    $fallbackTitlePrefix,
                    $iid
                );

                $preparedRows[] = [
                    'iid' => $iid,
                    'course_id' => $courseId,
                    'title' => $title,
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
                    uuidIsBinary: $uuidIsBinary
                );
                $migrated += \count($preparedRows);
            }

            $elapsedSeconds = max(1, (int) (microtime(true) - $startedAt));
            $rate = $seen / $elapsedSeconds;
            $remaining = max(0, $totalPending - $seen);
            $etaSeconds = $rate > 0 ? (int) round($remaining / $rate) : null;

            $this->getLogger()->info("{$entityName} migration progress.", [
                'seen' => $seen,
                'migrated' => $migrated,
                'initial_pending' => $totalPending,
                'percent' => round(($seen / max(1, $totalPending)) * 100, 2),
                'last_iid' => $lastIid,
                'missing_item_property' => $skippedMissingItemProperty,
                'rows_per_second' => round($rate, 2),
                'eta_seconds' => $etaSeconds,
            ]);
        }

        $this->getLogger()->info("Completed {$entityName} DBAL migration.", [
            'seen' => $seen,
            'migrated' => $migrated,
            'missing_item_property' => $skippedMissingItemProperty,
            'remaining' => (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM {$table} WHERE resource_node_id IS NULL"
            ),
            'elapsed_seconds' => (int) (microtime(true) - $startedAt),
        ]);
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

        $hasSessionTable = $this->tableExists('session');
        $hasGroupTable = $this->tableExists('c_group_info');

        $sessionSelect = $hasSessionTable ? 'valid_session.id' : 'NULL';
        $sessionJoin = $hasSessionTable
            ? 'LEFT JOIN session valid_session ON valid_session.id = ip.session_id'
            : '';
        $groupSelect = $hasGroupTable ? 'valid_group.iid' : 'NULL';
        $groupJoin = $hasGroupTable
            ? 'LEFT JOIN c_group_info valid_group ON valid_group.iid = ip.to_group_id'
            : '';

        $sql = "SELECT
                    ip.iid,
                    ip.c_id,
                    ip.ref,
                    ip.visibility,
                    ip.insert_user_id,
                    ip.session_id,
                    ip.to_group_id,
                    ip.lastedit_date,
                    valid_user.id AS valid_user_id,
                    {$sessionSelect} AS valid_session_id,
                    {$groupSelect} AS valid_group_id
                FROM c_item_property ip
                LEFT JOIN user valid_user ON valid_user.id = ip.insert_user_id
                {$sessionJoin}
                {$groupJoin}
                WHERE ip.tool = :tool
                  AND ip.ref IN (:refs)
                ORDER BY ip.ref, ip.c_id, ip.iid";

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
            $key = $this->itemPropertyKey((int) $item['c_id'], (int) $item['ref']);
            $map[$key][] = $item;
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function persistResourceBatch(
        string $table,
        int $resourceTypeId,
        array $rows,
        bool $uuidIsBinary
    ): void {
        $slugs = \array_column($rows, 'slug');
        $existingSlugCount = (int) $this->connection->executeQuery(
            'SELECT COUNT(*)
             FROM resource_node
             WHERE resource_type_id = :resourceTypeId
               AND slug IN (:slugs)',
            [
                'resourceTypeId' => $resourceTypeId,
                'slugs' => $slugs,
            ],
            [
                'slugs' => ArrayParameterType::STRING,
            ]
        )->fetchOne();

        if ($existingSlugCount > 0) {
            throw new RuntimeException(
                "Detected {$existingSlugCount} pre-existing deterministic resource slugs for {$table}. "
                .'Refusing to create duplicate nodes; audit partial data first.'
            );
        }

        $this->connection->beginTransaction();

        try {
            $now = gmdate('Y-m-d H:i:s');
            $nodeRows = [];

            foreach ($rows as $row) {
                $uuid = Uuid::v4();
                $nodeRows[] = [
                    'title' => $row['title'],
                    'slug' => $row['slug'],
                    'level' => ((int) $row['parent_level']) + 1,
                    'path' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'public' => 0,
                    'uuid' => $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122(),
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

            $nodeIdRows = $this->connection->executeQuery(
                'SELECT id, slug
                 FROM resource_node
                 WHERE resource_type_id = :resourceTypeId
                   AND slug IN (:slugs)',
                [
                    'resourceTypeId' => $resourceTypeId,
                    'slugs' => $slugs,
                ],
                [
                    'slugs' => ArrayParameterType::STRING,
                ]
            )->fetchAllAssociative();

            if (\count($nodeIdRows) !== \count($rows)) {
                throw new RuntimeException(
                    \sprintf(
                        'Expected %d inserted resource nodes for %s, found %d.',
                        \count($rows),
                        $table,
                        \count($nodeIdRows)
                    )
                );
            }

            $nodeIdsBySlug = [];
            foreach ($nodeIdRows as $nodeIdRow) {
                $nodeIdsBySlug[(string) $nodeIdRow['slug']] = (int) $nodeIdRow['id'];
            }

            $sourceMappings = [];
            $pathMappings = [];
            $resourceLinkRows = [];
            $nodeIds = [];

            foreach ($rows as $row) {
                $slug = (string) $row['slug'];
                $resourceNodeId = $nodeIdsBySlug[$slug] ?? 0;
                if ($resourceNodeId <= 0) {
                    throw new RuntimeException("Resource node not resolved for slug {$slug}.");
                }

                $nodeIds[] = $resourceNodeId;
                $sourceMappings[(int) $row['iid']] = $resourceNodeId;
                $pathMappings[$resourceNodeId] = $this->buildResourcePath(
                    (string) $row['parent_path'],
                    (string) $row['title'],
                    (int) $row['iid'],
                    $resourceNodeId
                );

                $contexts = [];
                foreach ($row['item_properties'] as $item) {
                    $sessionId = !empty($item['valid_session_id'])
                        ? (int) $item['valid_session_id']
                        : null;
                    $groupId = !empty($item['valid_group_id'])
                        ? (int) $item['valid_group_id']
                        : null;
                    $contextKey = ($sessionId ?? 0).':'.($groupId ?? 0);

                    // AbstractResource::addCourseLink keeps the first link for a
                    // repeated course/session/group context.
                    if (isset($contexts[$contextKey])) {
                        continue;
                    }
                    $contexts[$contextKey] = true;

                    $legacyVisibility = (int) ($item['visibility'] ?? 0);
                    $visibility = 1 === $legacyVisibility
                        ? ResourceLink::VISIBILITY_PUBLISHED
                        : ResourceLink::VISIBILITY_DRAFT;
                    $deletedAt = null;
                    if (2 === $legacyVisibility) {
                        $lastEditDate = trim((string) ($item['lastedit_date'] ?? ''));
                        $deletedAt = '' !== $lastEditDate ? $lastEditDate : $now;
                    }

                    $resourceLinkRows[] = [
                        'resource_node_id' => $resourceNodeId,
                        'c_id' => (int) $row['course_id'],
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
                'SELECT id
                 FROM resource_link
                 WHERE resource_node_id IN (:nodeIds)
                   AND visibility = :visibility',
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

                $this->bulkInsert(
                    'resource_right',
                    ['resource_link_id', 'role', 'mask'],
                    $rightRows
                );
            }

            $this->connection->commit();
        } catch (Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw new RuntimeException(
                "Fast forum migration failed for table {$table}: {$e->getMessage()}",
                0,
                $e
            );
        }
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

        $sql = \sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $table,
            \implode(', ', $columns),
            \implode(', ', $valueGroups)
        );

        $this->connection->executeStatement($sql, $parameters, $types);
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

        $sql = \sprintf(
            'UPDATE %s SET %s = CASE %s %s ELSE %s END WHERE %s IN (%s)',
            $table,
            $valueColumn,
            $idColumn,
            \implode(' ', $cases),
            $valueColumn,
            $idColumn,
            \implode(', ', $where)
        );

        $this->connection->executeStatement($sql, $parameters);
    }

    private function migrateForumImages(): void
    {
        /** @var CForumRepository $forumRepository */
        $forumRepository = $this->container->get(CForumRepository::class);
        $rows = $this->connection->fetchAllAssociative(
            "SELECT f.iid, f.resource_node_id, f.forum_image, c.directory
             FROM c_forum_forum f
             INNER JOIN course c ON c.id = f.c_id
             WHERE f.resource_node_id IS NOT NULL
               AND f.forum_image IS NOT NULL
               AND f.forum_image <> ''
             ORDER BY f.iid"
        );

        $processed = 0;
        $migrated = 0;
        $missing = 0;

        foreach ($rows as $row) {
            $forumId = (int) $row['iid'];
            $resourceNodeId = (int) $row['resource_node_id'];
            $fileName = (string) $row['forum_image'];

            if ($this->resourceFileAlreadyExists($resourceNodeId, $fileName, null)) {
                continue;
            }

            $filePath = $this->getUpdateRootPath().'/app/courses/'.(string) $row['directory'].'/upload/forum/images/'.$fileName;
            if (!$this->fileExists($filePath)) {
                ++$missing;
                $this->warnIf(true, "Forum image not found for forum {$forumId}: {$filePath}");
                continue;
            }

            $forum = $forumRepository->find($forumId);
            if (!$forum instanceof CForum || !$forum->hasResourceNode()) {
                $this->warnIf(true, "Forum {$forumId} could not be reloaded for image migration.");
                continue;
            }

            if ($this->addLegacyFileToResource($filePath, $forumRepository, $forum, $forumId, $fileName)) {
                $this->entityManager->persist($forum);
                ++$migrated;
            }

            ++$processed;
            if (0 === $processed % self::FILE_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Forum image migration completed.', [
            'candidates' => \count($rows),
            'migrated' => $migrated,
            'missing_files' => $missing,
        ]);
    }

    private function migratePostAttachments(): void
    {
        /** @var CForumPostRepository $postRepository */
        $postRepository = $this->container->get(CForumPostRepository::class);
        $rows = $this->connection->fetchAllAssociative(
            "SELECT
                a.iid,
                a.post_id,
                a.path,
                a.filename,
                a.comment,
                a.size,
                p.resource_node_id,
                c.directory
             FROM c_forum_attachment a
             INNER JOIN c_forum_post p ON p.iid = a.post_id
             INNER JOIN course c ON c.id = a.c_id
             WHERE p.resource_node_id IS NOT NULL
               AND a.filename IS NOT NULL
               AND a.filename <> ''
               AND a.path IS NOT NULL
               AND a.path <> ''
             ORDER BY a.iid"
        );

        $processed = 0;
        $migrated = 0;
        $missing = 0;
        $migratedAttachmentIds = $this->loadMigratedAttachmentIds();

        foreach ($rows as $row) {
            $attachmentId = (int) $row['iid'];
            $postId = (int) $row['post_id'];
            $resourceNodeId = (int) $row['resource_node_id'];
            $fileName = (string) $row['filename'];
            $expectedSize = isset($row['size']) ? (int) $row['size'] : null;

            if (isset($migratedAttachmentIds[$attachmentId])
                || $this->resourceFileAlreadyExists($resourceNodeId, $fileName, $expectedSize)
            ) {
                continue;
            }

            $filePath = $this->getUpdateRootPath().'/app/courses/'.(string) $row['directory'].'/upload/forum/'.(string) $row['path'];
            if (!$this->fileExists($filePath)) {
                ++$missing;
                $this->warnIf(true, "Forum attachment {$attachmentId} not found: {$filePath}");
                continue;
            }

            $post = $postRepository->find($postId);
            if (!$post instanceof CForumPost || !$post->hasResourceNode()) {
                $this->warnIf(true, "Forum post {$postId} could not be reloaded for attachment {$attachmentId}.");
                continue;
            }

            $description = (string) ($row['comment'] ?? '');
            if ($this->addLegacyFileToResource(
                $filePath,
                $postRepository,
                $post,
                $attachmentId,
                $fileName,
                $description
            )) {
                $resourceFile = $post->getResourceNode()->getResourceFiles()->last();
                if (false !== $resourceFile) {
                    $metadata = $resourceFile->getMetadata();
                    $metadata['legacy_forum_attachment_iid'] = $attachmentId;
                    $resourceFile->setMetadata($metadata);
                    $this->entityManager->persist($resourceFile);
                }

                $migratedAttachmentIds[$attachmentId] = true;
                $this->entityManager->persist($post);
                ++$migrated;
            }

            ++$processed;
            if (0 === $processed % self::FILE_FLUSH_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Forum post attachment migration completed.', [
            'candidates' => \count($rows),
            'migrated' => $migrated,
            'missing_files' => $missing,
        ]);
    }


    /**
     * @return array<int, true>
     */
    private function loadMigratedAttachmentIds(): array
    {
        $rows = $this->connection->fetchFirstColumn(
            "SELECT metadata
             FROM resource_file
             WHERE metadata IS NOT NULL
               AND metadata LIKE :marker",
            ['marker' => '%legacy_forum_attachment_iid%']
        );

        $ids = [];
        foreach ($rows as $serializedMetadata) {
            if (!\is_string($serializedMetadata) || '' === $serializedMetadata) {
                continue;
            }

            $metadata = @unserialize($serializedMetadata, ['allowed_classes' => false]);
            if (!\is_array($metadata) || empty($metadata['legacy_forum_attachment_iid'])) {
                continue;
            }

            $ids[(int) $metadata['legacy_forum_attachment_iid']] = true;
        }

        return $ids;
    }

    private function resourceFileAlreadyExists(int $resourceNodeId, string $originalName, ?int $size): bool
    {
        $sql = 'SELECT 1
                FROM resource_file
                WHERE resource_node_id = :resourceNodeId
                  AND original_name = :originalName';
        $parameters = [
            'resourceNodeId' => $resourceNodeId,
            'originalName' => $originalName,
        ];

        if (null !== $size && $size >= 0) {
            $sql .= ' AND size = :size';
            $parameters['size'] = $size;
        }

        $sql .= ' LIMIT 1';

        return false !== $this->connection->fetchOne($sql, $parameters);
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
            throw new RuntimeException('No fallback user could be resolved for forum resource creators.');
        }

        return (int) $id;
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
            throw new RuntimeException("Parent resource path is empty for legacy forum item {$iid}.");
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

    private function tableExists(string $tableName): bool
    {
        try {
            return \in_array(
                $tableName,
                $this->connection->createSchemaManager()->listTableNames(),
                true
            );
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
            'pending_categories' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_forum_category WHERE resource_node_id IS NULL'
            ),
            'pending_forums' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_forum_forum WHERE resource_node_id IS NULL'
            ),
            'pending_threads' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_forum_thread WHERE resource_node_id IS NULL'
            ),
            'pending_posts' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_forum_post WHERE resource_node_id IS NULL'
            ),
        ];
    }
}
