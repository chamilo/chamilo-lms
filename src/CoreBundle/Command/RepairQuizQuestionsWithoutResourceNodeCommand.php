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
    name: 'chamilo:migration:repair-quiz-questions-without-resource-node',
    description: 'Creates the missing resource node and course resource links of quiz questions, using the courses of the quizzes they belong to.',
)]
final class RepairQuizQuestionsWithoutResourceNodeCommand extends Command
{
    private const int BATCH_SIZE = 500;
    private const int RESOURCE_NODE_TITLE_MAX_LENGTH = 255;

    /**
     * @var array<int, int> next display_order per course
     */
    private array $displayOrders = [];

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
            'Shows how many questions would be repaired and rolls back every batch.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Repair quiz questions missing resource nodes');

        $dryRun = (bool) $input->getOption('dry-run');
        if ($dryRun) {
            $io->warning('Dry-run enabled. Every repair batch will be rolled back.');
        }

        try {
            $summary = $this->repair($dryRun, $io);
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success($dryRun ? 'Dry-run completed.' : 'Repair completed.');
        $io->definitionList(
            ['Questions without resource node' => $summary['seen']],
            [$dryRun ? 'Questions simulated' : 'Questions repaired' => $summary['repaired']],
            ['Resource links created' => $summary['links']],
            ['Linked to more than one course' => $summary['multi_course']],
            ['Skipped: not used in any quiz' => $summary['skipped_no_quiz']],
            ['Skipped: quizzes have no course link' => $summary['skipped_no_course']]
        );

        if ($io->isVerbose() && [] !== $summary['skipped_ids']) {
            $io->writeln('Skipped question iids: '.implode(', ', $summary['skipped_ids']));
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{seen: int, repaired: int, links: int, multi_course: int, skipped_no_quiz: int, skipped_no_course: int, skipped_ids: array<int, int>}
     */
    private function repair(bool $dryRun, SymfonyStyle $io): array
    {
        $resourceTypeId = $this->getResourceTypeId('questions');
        $uuidIsBinary = $this->detectUuidIsBinary();

        $questionIds = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT iid FROM c_quiz_question WHERE resource_node_id IS NULL ORDER BY iid'
            )
        );

        $summary = [
            'seen' => \count($questionIds),
            'repaired' => 0,
            'links' => 0,
            'multi_course' => 0,
            'skipped_no_quiz' => 0,
            'skipped_no_course' => 0,
            'skipped_ids' => [],
        ];

        foreach (array_chunk($questionIds, self::BATCH_SIZE) as $batchIds) {
            $questions = $this->fetchQuestions($batchIds);
            $contexts = $this->fetchQuizContexts($batchIds);
            $courseNodes = $this->fetchCourseNodes($contexts);

            $this->connection->beginTransaction();

            try {
                foreach ($batchIds as $questionId) {
                    $context = $contexts[$questionId] ?? null;

                    if (null === $context) {
                        ++$summary['skipped_no_quiz'];
                        $summary['skipped_ids'][] = $questionId;

                        continue;
                    }

                    // Keep only courses whose resource node exists.
                    $courseIds = array_values(array_filter(
                        $context['course_ids'],
                        static fn (int $courseId): bool => isset($courseNodes[$courseId])
                    ));

                    if ([] === $courseIds) {
                        ++$summary['skipped_no_course'];
                        $summary['skipped_ids'][] = $questionId;

                        continue;
                    }

                    // The node hangs from the first course, as Question::save() does with the current course.
                    $parentCourse = $courseNodes[$courseIds[0]];
                    $title = $this->normalizeTitle($questions[$questionId] ?? '', $questionId);
                    $now = gmdate('Y-m-d H:i:s');
                    $uuid = Uuid::v4();

                    $resourceNodeId = $this->insertResourceNode(
                        title: $title,
                        slug: 'question-'.$questionId,
                        level: $parentCourse['level'] + 1,
                        createdAt: $now,
                        uuid: $uuidIsBinary ? $uuid->toBinary() : $uuid->toRfc4122(),
                        uuidIsBinary: $uuidIsBinary,
                        resourceTypeId: $resourceTypeId,
                        creatorId: $context['creator_id'],
                        parentId: $parentCourse['node_id']
                    );

                    $this->connection->update(
                        'resource_node',
                        ['path' => $this->buildResourcePath($parentCourse['path'], $title, $questionId, $resourceNodeId)],
                        ['id' => $resourceNodeId]
                    );

                    foreach ($courseIds as $courseId) {
                        $this->connection->insert('resource_link', [
                            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                            'start_visibility_at' => null,
                            'end_visibility_at' => null,
                            'display_order' => $this->nextDisplayOrder($courseId, $resourceTypeId),
                            'resource_type_group' => $resourceTypeId,
                            'deleted_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                            'resource_node_id' => $resourceNodeId,
                            'parent_id' => null,
                            'c_id' => $courseId,
                            'session_id' => null,
                            'usergroup_id' => null,
                            'group_id' => null,
                            'user_id' => null,
                        ]);
                        ++$summary['links'];
                    }

                    $this->connection->update(
                        'c_quiz_question',
                        ['resource_node_id' => $resourceNodeId],
                        ['iid' => $questionId]
                    );

                    ++$summary['repaired'];
                    if (\count($courseIds) > 1) {
                        ++$summary['multi_course'];
                    }
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

                throw new RuntimeException('Quiz question repair failed in batch starting at question '.$batchIds[0].': '.$e->getMessage(), 0, $e);
            }

            if ($dryRun) {
                // Rolled-back display orders must not be reused as if they had been persisted.
                $this->displayOrders = [];
            }

            $io->writeln(\sprintf(
                'processed up to iid=%d repaired=%d skipped=%d',
                (int) end($batchIds),
                $summary['repaired'],
                $summary['skipped_no_quiz'] + $summary['skipped_no_course']
            ));
        }

        return $summary;
    }

    /**
     * @param array<int, int> $questionIds
     *
     * @return array<int, string> question text by iid
     */
    private function fetchQuestions(array $questionIds): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT iid, question FROM c_quiz_question WHERE iid IN (:ids)',
            ['ids' => $questionIds],
            ['ids' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['iid']] = (string) ($row['question'] ?? '');
        }

        return $map;
    }

    /**
     * Resolves, through c_quiz_rel_question, the courses where each question is used
     * (the c_id of the resource links of its quizzes) and the creator of its first quiz.
     *
     * @param array<int, int> $questionIds
     *
     * @return array<int, array{course_ids: array<int, int>, creator_id: int}>
     */
    private function fetchQuizContexts(array $questionIds): array
    {
        $rows = $this->connection->executeQuery(
            'SELECT rq.question_id, rq.quiz_id, rl.c_id, quiz_node.creator_id
             FROM c_quiz_rel_question rq
             INNER JOIN c_quiz quiz ON quiz.iid = rq.quiz_id
             INNER JOIN resource_node quiz_node ON quiz_node.id = quiz.resource_node_id
             INNER JOIN resource_link rl ON rl.resource_node_id = quiz_node.id AND rl.c_id IS NOT NULL
             WHERE rq.question_id IN (:ids)
             ORDER BY rq.question_id, rq.quiz_id, rl.c_id',
            ['ids' => $questionIds],
            ['ids' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $questionId = (int) $row['question_id'];
            $courseId = (int) $row['c_id'];

            if (!isset($map[$questionId])) {
                $map[$questionId] = [
                    'course_ids' => [],
                    'creator_id' => (int) $row['creator_id'],
                ];
            }

            if (!\in_array($courseId, $map[$questionId]['course_ids'], true)) {
                $map[$questionId]['course_ids'][] = $courseId;
            }
        }

        return $map;
    }

    /**
     * @param array<int, array{course_ids: array<int, int>, creator_id: int}> $contexts
     *
     * @return array<int, array{node_id: int, path: string, level: int}>
     */
    private function fetchCourseNodes(array $contexts): array
    {
        $courseIds = array_values(array_unique(array_merge(
            [],
            ...array_map(static fn (array $context): array => $context['course_ids'], array_values($contexts))
        )));

        if ([] === $courseIds) {
            return [];
        }

        $rows = $this->connection->executeQuery(
            'SELECT c.id, n.id AS node_id, n.path, n.level
             FROM course c
             INNER JOIN resource_node n ON n.id = c.resource_node_id
             WHERE c.id IN (:ids)',
            ['ids' => $courseIds],
            ['ids' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = [
                'node_id' => (int) $row['node_id'],
                'path' => (string) ($row['path'] ?? ''),
                'level' => (int) $row['level'],
            ];
        }

        return $map;
    }

    private function nextDisplayOrder(int $courseId, int $resourceTypeId): int
    {
        if (!isset($this->displayOrders[$courseId])) {
            $this->displayOrders[$courseId] = (int) $this->connection->fetchOne(
                'SELECT COALESCE(MAX(display_order), -1) + 1
                 FROM resource_link
                 WHERE c_id = :courseId
                   AND resource_type_group = :resourceTypeId
                   AND session_id IS NULL
                   AND usergroup_id IS NULL
                   AND group_id IS NULL
                   AND user_id IS NULL',
                [
                    'courseId' => $courseId,
                    'resourceTypeId' => $resourceTypeId,
                ]
            );
        }

        return $this->displayOrders[$courseId]++;
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

    private function normalizeTitle(string $title, int $questionId): string
    {
        $title = html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!mb_check_encoding($title, 'UTF-8')) {
            $title = (string) iconv('UTF-8', 'UTF-8//IGNORE', $title);
        }

        $title = preg_replace('/\s+/u', ' ', trim($title));
        if (null === $title || '' === $title) {
            $title = 'Question #'.$questionId;
        }

        $title = str_replace(['/', '\\'], '-', $title);
        if (mb_strlen($title) > self::RESOURCE_NODE_TITLE_MAX_LENGTH) {
            $title = mb_substr($title, 0, self::RESOURCE_NODE_TITLE_MAX_LENGTH - 3).'...';
        }

        return $title;
    }

    private function buildResourcePath(string $parentPath, string $title, int $questionId, int $resourceNodeId): string
    {
        $parentPath = rtrim($parentPath, '/');
        if ('' === $parentPath) {
            throw new RuntimeException("Parent course resource path is empty for question {$questionId}.");
        }

        return $parentPath.'/'.$title.'-'.$questionId.'-'.$resourceNodeId.'/';
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

            return \in_array($type, ['binary', 'varbinary'], true) || 16 === $column->getLength();
        } catch (Throwable) {
            return false;
        }
    }

    private function insertResourceNode(
        string $title,
        string $slug,
        int $level,
        string $createdAt,
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
            'updated_at' => $createdAt,
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
