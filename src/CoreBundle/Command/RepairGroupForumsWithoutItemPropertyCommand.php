<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;

#[AsCommand(
    name: 'chamilo:migration:repair-group-forums-without-item-property',
    description: 'Repairs active legacy group forums that have no item property but still have a valid migrated group and parent.',
)]
final class RepairGroupForumsWithoutItemPropertyCommand extends Command
{
    private const int BATCH_SIZE = 100;
    private const int RESOURCE_NODE_TITLE_MAX_LENGTH = 255;

    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Shows the group forums that would be repaired and rolls back every batch.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Repair legacy group forums missing resource nodes');

        $dryRun = (bool) $input->getOption('dry-run');
        if ($dryRun) {
            $io->warning('Dry-run enabled. Every repair batch will be rolled back.');
        }

        try {
            $summary = $this->repair(
                $dryRun,
                static function (array $progress) use ($io): void {
                    $io->writeln(\sprintf(
                        'seen=%d repaired=%d last_iid=%d skipped_unsafe_context=%d rate=%s rows/s',
                        (int) $progress['seen'],
                        (int) $progress['repaired'],
                        (int) $progress['last_iid'],
                        (int) $progress['skipped_unsafe_context'],
                        (string) $progress['rows_per_second']
                    ));
                }
            );
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success($dryRun ? 'Dry-run completed.' : 'Repair completed.');
        $io->definitionList(
            ['Rows seen' => $summary['seen']],
            [$dryRun ? 'Forums simulated' : 'Forums repaired' => $summary['repaired']],
            ['Skipped unsafe group context' => $summary['skipped_unsafe_context']],
            ['Eligible group forums still pending' => $summary['eligible_still_pending']],
            ['Group forums still pending' => $summary['group_forums_still_pending']],
            ['All forums still pending' => $summary['all_forums_still_pending']]
        );

        return Command::SUCCESS;
    }

    /**
     * Repairs only group forums that cannot be handled by the main forum migration because
     * their legacy forum item property is missing. The group and forum parent must already
     * have valid migrated resource contexts. No c_item_property row is created.
     *
     * @param null|callable(array<string, int|float|string>): void $progress
     *
     * @return array<string, int>
     */
    private function repair(bool $dryRun = false, ?callable $progress = null): array
    {
        $resourceTypeId = $this->getResourceTypeId('forums');
        $fallbackAdminId = $this->getFallbackAdminId();
        $uuidIsBinary = $this->detectUuidIsBinary();
        $seen = 0;
        $repaired = 0;
        $skippedUnsafeContext = 0;
        $startedAt = microtime(true);

        $candidateIds = $this->findPendingGroupForumIds();

        foreach (array_chunk($candidateIds, self::BATCH_SIZE) as $batchIds) {
            $rows = $this->fetchCandidateRows($batchIds);
            $seen += \count($batchIds);
            $skippedUnsafeContext += \count($batchIds) - \count($rows);
            $lastIid = (int) $batchIds[array_key_last($batchIds)];

            if ([] !== $rows) {
                $this->connection->beginTransaction();

                try {
                    foreach ($rows as $row) {
                        $forumId = (int) $row['iid'];
                        $title = $this->normalizeTitle((string) ($row['title'] ?? ''), $forumId);
                        $slug = 'forum-'.$forumId;

                        $this->assertDeterministicSlugIsFree($resourceTypeId, $slug, $forumId);

                        $now = gmdate('Y-m-d H:i:s');
                        $uuid = Uuid::v4();
                        $resourceNodeId = $this->insertResourceNode(
                            title: $title,
                            slug: $slug,
                            level: ((int) $row['parent_level']) + 1,
                            createdAt: $now,
                            updatedAt: $now,
                            uuid: $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122(),
                            uuidIsBinary: $uuidIsBinary,
                            resourceTypeId: $resourceTypeId,
                            creatorId: $fallbackAdminId,
                            parentId: (int) $row['parent_node_id']
                        );

                        $newPath = $this->buildResourcePath(
                            (string) $row['parent_path'],
                            $title,
                            $forumId,
                            $resourceNodeId
                        );
                        $this->connection->update('resource_node', ['path' => $newPath], ['id' => $resourceNodeId]);

                        $this->connection->insert('resource_link', [
                            'resource_node_id' => $resourceNodeId,
                            'c_id' => (int) $row['c_id'],
                            'session_id' => null !== $row['context_session_id'] ? (int) $row['context_session_id'] : null,
                            'usergroup_id' => null,
                            'group_id' => (int) $row['group_iid'],
                            'user_id' => null,
                            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                            'start_visibility_at' => null,
                            'end_visibility_at' => null,
                            'display_order' => (int) $row['display_order'],
                            'resource_type_group' => $resourceTypeId,
                            'created_at' => $now,
                            'updated_at' => $now,
                            'deleted_at' => null,
                            'parent_id' => null,
                        ]);

                        $this->connection->update(
                            'c_forum_forum',
                            ['resource_node_id' => $resourceNodeId],
                            ['iid' => $forumId]
                        );

                        ++$repaired;
                    }

                    if ($dryRun) {
                        $this->connection->rollBack();
                    } else {
                        $this->connection->commit();
                    }
                } catch (Throwable $e) {
                    if ($this->connection->isTransactionActive()) {
                        $this->connection->rollBack();
                    }

                    throw new RuntimeException('Group forum repair failed after legacy forum '.$lastIid.': '.$e->getMessage(), 0, $e);
                }
            }

            if (null !== $progress) {
                $elapsed = max(0.001, microtime(true) - $startedAt);
                $progress([
                    'seen' => $seen,
                    'repaired' => $repaired,
                    'last_iid' => $lastIid,
                    'skipped_unsafe_context' => $skippedUnsafeContext,
                    'rows_per_second' => round($seen / $elapsed, 2),
                ]);
            }
        }

        return [
            'seen' => $seen,
            'repaired' => $repaired,
            'skipped_unsafe_context' => $skippedUnsafeContext,
            'eligible_still_pending' => \count($this->findEligiblePendingGroupForumIds()),
            'group_forums_still_pending' => \count($this->findPendingGroupForumIds()),
            'all_forums_still_pending' => (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM c_forum_forum WHERE resource_node_id IS NULL'
            ),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function findPendingGroupForumIds(): array
    {
        $ids = $this->connection->fetchFirstColumn(
            <<<'SQL'
SELECT f.iid
FROM c_forum_forum f
WHERE f.resource_node_id IS NULL
  AND f.forum_of_group IS NOT NULL
  AND f.forum_of_group <> ''
  AND f.forum_of_group <> '0'
  AND NOT EXISTS (
      SELECT 1
      FROM c_item_property ip
      WHERE ip.tool = 'forum'
        AND ip.c_id = f.c_id
        AND ip.ref = f.iid
  )
ORDER BY f.iid
SQL
        );

        return array_map('intval', $ids);
    }

    /**
     * @return array<int, int>
     */
    private function findEligiblePendingGroupForumIds(): array
    {
        $eligible = [];
        foreach (array_chunk($this->findPendingGroupForumIds(), self::BATCH_SIZE) as $batchIds) {
            foreach ($this->fetchCandidateRows($batchIds) as $row) {
                $eligible[] = (int) $row['iid'];
            }
        }

        return $eligible;
    }

    /**
     * @param array<int, int> $forumIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchCandidateRows(array $forumIds): array
    {
        if ([] === $forumIds) {
            return [];
        }

        $baseRows = $this->connection->executeQuery(
            <<<'SQL'
SELECT
    f.iid,
    f.c_id,
    f.title,
    COALESCE(f.forum_order, f.iid) AS display_order,
    f.forum_of_group,
    CASE
        WHEN category.iid IS NULL THEN course.resource_node_id
        ELSE category.resource_node_id
    END AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM c_forum_forum f
INNER JOIN course
    ON course.id = f.c_id
LEFT JOIN c_forum_category category
    ON category.iid = f.forum_category
INNER JOIN resource_node parent
    ON parent.id = CASE
        WHEN category.iid IS NULL THEN course.resource_node_id
        ELSE category.resource_node_id
    END
WHERE f.iid IN (:forumIds)
  AND f.resource_node_id IS NULL
  AND NOT EXISTS (
      SELECT 1
      FROM c_item_property ip
      WHERE ip.tool = 'forum'
        AND ip.c_id = f.c_id
        AND ip.ref = f.iid
  )
ORDER BY f.iid
SQL,
            ['forumIds' => $forumIds],
            ['forumIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        if ([] === $baseRows) {
            return [];
        }

        $groupLegacyIds = [];
        foreach ($baseRows as $row) {
            $rawGroupId = trim((string) ($row['forum_of_group'] ?? ''));
            if ('' !== $rawGroupId && ctype_digit($rawGroupId) && (int) $rawGroupId > 0) {
                $groupLegacyIds[] = (int) $rawGroupId;
            }
        }
        $groupLegacyIds = array_values(array_unique($groupLegacyIds));

        if ([] === $groupLegacyIds) {
            return [];
        }

        $groupRows = $this->connection->executeQuery(
            <<<'SQL'
SELECT
    g.iid,
    g.id,
    g.c_id,
    g.status,
    g.forum_state,
    g.resource_node_id
FROM c_group_info g
INNER JOIN resource_node group_node
    ON group_node.id = g.resource_node_id
WHERE g.id IN (:groupIds)
  AND g.status = 1
  AND g.forum_state = 1
ORDER BY g.c_id, g.id, g.iid
SQL,
            ['groupIds' => $groupLegacyIds],
            ['groupIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $groupsByKey = [];
        $ambiguousGroupKeys = [];
        foreach ($groupRows as $groupRow) {
            $key = $this->groupKey((int) $groupRow['c_id'], (int) $groupRow['id']);
            if (isset($groupsByKey[$key])) {
                $ambiguousGroupKeys[$key] = true;

                continue;
            }
            $groupsByKey[$key] = $groupRow;
        }

        $groupNodeIds = [];
        foreach ($groupsByKey as $key => $groupRow) {
            if (isset($ambiguousGroupKeys[$key])) {
                continue;
            }
            $groupNodeIds[] = (int) $groupRow['resource_node_id'];
        }
        $groupNodeIds = array_values(array_unique($groupNodeIds));

        $groupContexts = $this->fetchGroupContexts($groupNodeIds);
        $eligible = [];

        foreach ($baseRows as $row) {
            $rawGroupId = trim((string) ($row['forum_of_group'] ?? ''));
            if ('' === $rawGroupId || !ctype_digit($rawGroupId) || (int) $rawGroupId <= 0) {
                continue;
            }

            $key = $this->groupKey((int) $row['c_id'], (int) $rawGroupId);
            if (isset($ambiguousGroupKeys[$key])) {
                continue;
            }

            $group = $groupsByKey[$key] ?? null;
            if (null === $group) {
                continue;
            }

            $context = $this->resolveUnambiguousGroupContext(
                $groupContexts[(int) $group['resource_node_id']] ?? [],
                (int) $row['c_id']
            );
            if (null === $context) {
                continue;
            }

            $row['group_iid'] = (int) $group['iid'];
            $row['context_session_id'] = $context['session_id'];
            $eligible[] = $row;
        }

        return $eligible;
    }

    /**
     * @param array<int, int> $groupNodeIds
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function fetchGroupContexts(array $groupNodeIds): array
    {
        if ([] === $groupNodeIds) {
            return [];
        }

        $rows = $this->connection->executeQuery(
            <<<'SQL'
SELECT
    resource_node_id,
    c_id,
    session_id,
    usergroup_id,
    group_id,
    user_id,
    deleted_at
FROM resource_link
WHERE resource_node_id IN (:nodeIds)
ORDER BY resource_node_id, id
SQL,
            ['nodeIds' => $groupNodeIds],
            ['nodeIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['resource_node_id']][] = $row;
        }

        return $map;
    }

    /**
     * Uses the migrated group resource as the source of truth for course/session scope.
     * The group forum adds its own group_id, so the group resource link itself must be
     * a non-deleted container link without a nested group/user/usergroup scope.
     *
     * @param array<int, array<string, mixed>> $links
     *
     * @return null|array{session_id: null|int}
     */
    private function resolveUnambiguousGroupContext(array $links, int $courseId): ?array
    {
        $contexts = [];

        foreach ($links as $link) {
            if ((int) ($link['c_id'] ?? 0) !== $courseId) {
                continue;
            }
            if (null !== $link['deleted_at']) {
                continue;
            }
            if (null !== $link['usergroup_id'] || null !== $link['group_id'] || null !== $link['user_id']) {
                continue;
            }

            $sessionId = null !== $link['session_id'] ? (int) $link['session_id'] : null;
            $key = (string) ($sessionId ?? 0);
            $contexts[$key] = ['session_id' => $sessionId];
        }

        if (1 !== \count($contexts)) {
            return null;
        }

        return array_values($contexts)[0];
    }

    private function groupKey(int $courseId, int $legacyGroupId): string
    {
        return $courseId.':'.$legacyGroupId;
    }

    private function assertDeterministicSlugIsFree(int $resourceTypeId, string $slug, int $forumId): void
    {
        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM resource_node WHERE resource_type_id = :resourceTypeId AND slug = :slug',
            [
                'resourceTypeId' => $resourceTypeId,
                'slug' => $slug,
            ]
        );

        if ($count > 0) {
            throw new RuntimeException("Detected a pre-existing deterministic resource slug '{$slug}' for legacy forum {$forumId}. ".'Refusing to create a duplicate resource node; audit partial data first.');
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

    private function normalizeTitle(string $title, int $forumId): string
    {
        $title = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!mb_check_encoding($title, 'UTF-8')) {
            $title = (string) iconv('UTF-8', 'UTF-8//IGNORE', $title);
        }

        $title = preg_replace('/\s+/u', ' ', trim($title));
        if (null === $title || '' === $title) {
            $title = 'Forum #'.$forumId;
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
        int $forumId,
        int $resourceNodeId
    ): string {
        $parentPath = rtrim($parentPath, '/');
        if ('' === $parentPath) {
            throw new RuntimeException("Parent resource path is empty for legacy group forum {$forumId}.");
        }

        $segment = preg_replace('/\s+/u', ' ', trim($title));
        if (null === $segment || '' === $segment) {
            $segment = 'forum-'.$forumId;
        }
        $segment = str_replace(['/', '\\'], '-', $segment);

        return $parentPath.'/'.$segment.'-'.$forumId.'-'.$resourceNodeId.'/';
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

    private function insertResourceNode(
        string $title,
        string $slug,
        int $level,
        string $createdAt,
        string $updatedAt,
        string $uuid,
        bool $uuidIsBinary,
        int $resourceTypeId,
        int $creatorId,
        int $parentId
    ): int {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'level' => $level,
            'path' => null,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
            'public' => 0,
            'uuid' => $uuid,
            'resource_type_id' => $resourceTypeId,
            'resource_format_id' => null,
            'language_id' => null,
            'creator_id' => $creatorId,
            'parent_id' => $parentId,
        ];

        $types = [];
        if ($uuidIsBinary) {
            $types['uuid'] = ParameterType::BINARY;
        }

        if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            return (int) $this->connection->fetchOne(
                'INSERT INTO resource_node (
                    title, slug, level, path, created_at, updated_at, public,
                    uuid, resource_type_id, resource_format_id, language_id,
                    creator_id, parent_id
                 ) VALUES (
                    :title, :slug, :level, :path, :created_at, :updated_at, :public,
                    :uuid, :resource_type_id, :resource_format_id, :language_id,
                    :creator_id, :parent_id
                 ) RETURNING id',
                $data,
                $types
            );
        }

        $this->connection->insert('resource_node', $data, $types);

        return (int) $this->connection->lastInsertId();
    }
}
