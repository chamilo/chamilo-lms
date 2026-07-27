<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
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
    name: 'chamilo:migration:repair-forum-posts-without-item-property',
    description: 'Repairs visible legacy forum posts that still have no resource node but have a valid migrated thread.',
)]
final class RepairForumPostsWithoutItemPropertyCommand extends Command
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
            'Shows the posts that would be repaired and rolls back every batch.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Repair legacy forum posts missing resource nodes');

        $dryRun = (bool) $input->getOption('dry-run');
        if ($dryRun) {
            $io->warning('Dry-run enabled. Every repair batch will be rolled back.');
        }

        try {
            $summary = $this->repair(
                $dryRun,
                static function (array $progress) use ($io): void {
                    $io->writeln(\sprintf(
                        'seen=%d repaired=%d last_iid=%d skipped_parent_context=%d rate=%s rows/s',
                        (int) $progress['seen'],
                        (int) $progress['repaired'],
                        (int) $progress['last_iid'],
                        (int) $progress['skipped_missing_parent_context'],
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
            [$dryRun ? 'Posts simulated' : 'Posts repaired' => $summary['repaired']],
            ['Skipped without parent context' => $summary['skipped_missing_parent_context']],
            ['Eligible posts still pending' => $summary['eligible_still_pending']],
            ['Posts under orphan threads preserved' => $summary['orphan_thread_posts_preserved']]
        );

        return Command::SUCCESS;
    }

    /**
     * Repairs visible legacy forum posts that still have no resource node,
     * but have a valid migrated thread, author and unambiguous course context.
     *
     * @param null|callable(array<string, int|float|string>): void $progress
     *
     * @return array<string, int>
     */
    private function repair(bool $dryRun = false, ?callable $progress = null): array
    {
        $resourceTypeId = $this->getResourceTypeId('forum_posts');
        $uuidIsBinary = $this->detectUuidIsBinary();
        $seen = 0;
        $repaired = 0;
        $skippedMissingParentContext = 0;
        $startedAt = microtime(true);

        $candidateIds = $this->findEligiblePendingPostIds();

        foreach (array_chunk($candidateIds, self::BATCH_SIZE) as $batchIds) {
            $rows = $this->fetchCandidateRows($batchIds);
            if ([] === $rows) {
                continue;
            }

            $seen += \count($rows);
            $lastIid = (int) $rows[array_key_last($rows)]['iid'];

            $parentNodeIds = array_values(array_unique(array_map(
                static fn (array $row): int => (int) $row['parent_node_id'],
                $rows
            )));
            $parentLinks = $this->fetchParentLinks($parentNodeIds);

            $this->connection->beginTransaction();

            try {
                foreach ($rows as $row) {
                    $postId = (int) $row['iid'];
                    $courseId = (int) $row['c_id'];
                    $parentNodeId = (int) $row['parent_node_id'];
                    $contexts = $this->getParentContextsForCourse(
                        $parentLinks[$parentNodeId] ?? [],
                        $courseId
                    );

                    if ([] === $contexts) {
                        ++$skippedMissingParentContext;

                        continue;
                    }

                    $title = $this->normalizeTitle((string) ($row['title'] ?? ''), $postId);
                    $slug = 'forum-post-'.$postId;

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
                        creatorId: (int) $row['poster_id'],
                        parentId: $parentNodeId
                    );

                    $newPath = $this->buildResourcePath(
                        (string) $row['parent_path'],
                        $title,
                        $postId,
                        $resourceNodeId
                    );
                    $this->connection->update('resource_node', ['path' => $newPath], ['id' => $resourceNodeId]);

                    foreach ($contexts as $context) {
                        $visibility = (int) $context['visibility'];
                        $this->connection->insert('resource_link', [
                            'visibility' => $visibility,
                            'start_visibility_at' => $context['start_visibility_at'],
                            'end_visibility_at' => $context['end_visibility_at'],
                            'display_order' => $postId,
                            'resource_type_group' => $resourceTypeId,
                            'deleted_at' => $context['deleted_at'],
                            'created_at' => $now,
                            'updated_at' => $now,
                            'resource_node_id' => $resourceNodeId,
                            'parent_id' => null,
                            'c_id' => $courseId,
                            'session_id' => $context['session_id'],
                            'usergroup_id' => $context['usergroup_id'],
                            'group_id' => $context['group_id'],
                            'user_id' => $context['user_id'],
                        ]);

                        if (ResourceLink::VISIBILITY_DRAFT === $visibility) {
                            $resourceLinkId = (int) $this->connection->lastInsertId();
                            $this->connection->insert('resource_right', [
                                'resource_link_id' => $resourceLinkId,
                                'role' => ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER,
                                'mask' => ResourceNodeVoter::getEditorMask(),
                            ]);
                        }
                    }

                    $this->connection->update(
                        'c_forum_post',
                        ['resource_node_id' => $resourceNodeId],
                        ['iid' => $postId]
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

                throw new RuntimeException('Forum post repair failed after legacy post '.$lastIid.': '.$e->getMessage(), 0, $e);
            }

            if (null !== $progress) {
                $elapsed = max(0.001, microtime(true) - $startedAt);
                $progress([
                    'seen' => $seen,
                    'repaired' => $repaired,
                    'last_iid' => $lastIid,
                    'skipped_missing_parent_context' => $skippedMissingParentContext,
                    'rows_per_second' => round($seen / $elapsed, 2),
                ]);
            }
        }

        return [
            'seen' => $seen,
            'repaired' => $repaired,
            'skipped_missing_parent_context' => $skippedMissingParentContext,
            'eligible_still_pending' => $this->countEligiblePending(),
            'orphan_thread_posts_preserved' => $this->countOrphanThreadPosts(),
        ];
    }

    /**
     * Returns only posts still missing a resource node. On Ricky this is a
     * very small remainder after the main forum migration, so selecting by
     * the existing resource_node_id index avoids rescanning 1.3M rows with
     * a correlated NOT EXISTS for every batch.
     *
     * @return array<int, int>
     */
    private function findEligiblePendingPostIds(): array
    {
        $pendingIds = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                <<<'SQL'
SELECT p.iid
FROM c_forum_post p
WHERE p.resource_node_id IS NULL
  AND p.visible = 1
ORDER BY p.iid
SQL
            )
        );

        if ([] === $pendingIds) {
            return [];
        }

        $eligibleIds = [];
        foreach (array_chunk($pendingIds, self::BATCH_SIZE) as $batchIds) {
            $rows = $this->connection->executeQuery(
                <<<'SQL'
SELECT
    p.iid
FROM c_forum_post p
INNER JOIN c_forum_thread t
    ON t.iid = p.thread_id
INNER JOIN resource_node parent
    ON parent.id = t.resource_node_id
INNER JOIN user author
    ON author.id = p.poster_id
INNER JOIN resource_link context_link
    ON context_link.resource_node_id = t.resource_node_id
   AND context_link.c_id IS NOT NULL
WHERE p.iid IN (:postIds)
GROUP BY p.iid
HAVING COUNT(DISTINCT context_link.c_id) = 1
ORDER BY p.iid
SQL,
                ['postIds' => $batchIds],
                ['postIds' => ArrayParameterType::INTEGER]
            )->fetchAllAssociative();

            foreach ($rows as $row) {
                $eligibleIds[] = (int) $row['iid'];
            }
        }

        return $eligibleIds;
    }

    /**
     * @param array<int, int> $postIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchCandidateRows(array $postIds): array
    {
        if ([] === $postIds) {
            return [];
        }

        return $this->connection->executeQuery(
            <<<'SQL'
SELECT
    p.iid,
    MIN(context_link.c_id) AS c_id,
    p.title,
    p.poster_id,
    p.visible,
    p.post_date,
    t.resource_node_id AS parent_node_id,
    parent.path AS parent_path,
    parent.level AS parent_level
FROM c_forum_post p
INNER JOIN c_forum_thread t
    ON t.iid = p.thread_id
INNER JOIN resource_node parent
    ON parent.id = t.resource_node_id
INNER JOIN user author
    ON author.id = p.poster_id
INNER JOIN resource_link context_link
    ON context_link.resource_node_id = t.resource_node_id
   AND context_link.c_id IS NOT NULL
WHERE p.iid IN (:postIds)
GROUP BY
    p.iid,
    p.title,
    p.poster_id,
    p.visible,
    p.post_date,
    t.resource_node_id,
    parent.path,
    parent.level
HAVING COUNT(DISTINCT context_link.c_id) = 1
ORDER BY p.iid
SQL,
            ['postIds' => $postIds],
            ['postIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();
    }

    private function countEligiblePending(): int
    {
        return \count($this->findEligiblePendingPostIds());
    }

    private function countOrphanThreadPosts(): int
    {
        return (int) $this->connection->fetchOne(
            <<<'SQL'
SELECT COUNT(*)
FROM c_forum_post p
INNER JOIN c_forum_thread t
    ON t.iid = p.thread_id
WHERE p.resource_node_id IS NULL
  AND t.resource_node_id IS NULL
SQL
        );
    }

    /**
     * @param array<int, int> $parentNodeIds
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function fetchParentLinks(array $parentNodeIds): array
    {
        if ([] === $parentNodeIds) {
            return [];
        }

        $rows = $this->connection->executeQuery(
            'SELECT
                id,
                resource_node_id,
                c_id,
                session_id,
                usergroup_id,
                group_id,
                user_id,
                visibility,
                start_visibility_at,
                end_visibility_at,
                deleted_at
             FROM resource_link
             WHERE resource_node_id IN (:nodeIds)
             ORDER BY resource_node_id, id',
            ['nodeIds' => $parentNodeIds],
            ['nodeIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['resource_node_id']][] = $row;
        }

        return $map;
    }

    /**
     * @param array<int, array<string, mixed>> $links
     *
     * @return array<int, array<string, mixed>>
     */
    private function getParentContextsForCourse(array $links, int $courseId): array
    {
        $contexts = [];
        $seen = [];

        foreach ($links as $link) {
            if ((int) $link['c_id'] !== $courseId) {
                continue;
            }

            $key = implode(':', [
                (string) ($link['session_id'] ?? 0),
                (string) ($link['usergroup_id'] ?? 0),
                (string) ($link['group_id'] ?? 0),
                (string) ($link['user_id'] ?? 0),
            ]);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $contexts[] = $link;
        }

        return $contexts;
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

    private function normalizeTitle(string $title, int $postId): string
    {
        $title = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!mb_check_encoding($title, 'UTF-8')) {
            $title = (string) iconv('UTF-8', 'UTF-8//IGNORE', $title);
        }

        $title = preg_replace('/\s+/u', ' ', trim($title));
        if (null === $title || '' === $title) {
            $title = 'Post #'.$postId;
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
        int $postId,
        int $resourceNodeId
    ): string {
        $parentPath = rtrim($parentPath, '/');
        if ('' === $parentPath) {
            throw new RuntimeException("Parent resource path is empty for legacy forum post {$postId}.");
        }

        $segment = preg_replace('/\s+/u', ' ', trim($title));
        if (null === $segment || '' === $segment) {
            $segment = 'forum-post-'.$postId;
        }
        $segment = str_replace(['/', '\\'], '-', $segment);

        return $parentPath.'/'.$segment.'-'.$postId.'-'.$resourceNodeId.'/';
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
