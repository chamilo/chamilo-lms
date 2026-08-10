<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseReporting;

use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CourseReportingSectionQueryService
{
    private const int STUDENT_STATUS = 5;
    private const int MAX_ITEMS_PER_PAGE = 200;

    /**
     * @var array<string, string>
     */
    private const array RESOURCE_TYPE_LABELS = [
        'files' => 'Documents',
        'lps' => 'Learning paths',
        'exercises' => 'Tests',
        'glossaries' => 'Glossary',
        'links' => 'Links',
        'course_descriptions' => 'Course description',
        'announcements' => 'Announcements',
        'thematics' => 'Thematics',
        'thematic_advance' => 'Thematic advance',
        'thematic_plan' => 'Thematic plan',
    ];

    /**
     * @var array<string, bool>
     */
    private array $tableCache = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private array $columnCache = [];

    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function getSection(
        CourseReportingContext $context,
        string $section,
        array $filters = []
    ): array {
        return match ($section) {
            'activity' => $this->getActivity($context, $filters),
            'groups' => $this->getGroups($context, $filters),
            'resources' => $this->getResources($context, $filters),
            'tools' => $this->getTools($context, $filters),
            'exams' => $this->getExams($context, $filters),
            'audit' => $this->getAudit($context, $filters),
            'learning-paths' => $this->getLearningPaths($context, $filters),
            'total-time' => $this->getTotalTime($context, $filters),
            'session' => $this->getSessionReport($context, $filters),
            'messages' => $this->getMessages($context, $filters),
            default => throw new BadRequestHttpException('Unknown reporting section.'),
        };
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getActivity(CourseReportingContext $context, array $filters): array
    {
        $startDate = $this->resolveStartDate($filters, '-30 days');
        $learners = $this->getLearners($context);
        $learnerIds = array_map(static fn (array $row): int => (int) $row['id'], $learners);

        $periodDefinitions = [
            ['key' => 'last24Hours', 'label' => 'Last 24 hours', 'date' => new DateTimeImmutable('-24 hours')],
            ['key' => 'last7Days', 'label' => 'Last week', 'date' => new DateTimeImmutable('-7 days')],
            ['key' => 'last30Days', 'label' => 'Last month', 'date' => new DateTimeImmutable('-30 days')],
            ['key' => 'sinceStartDate', 'label' => 'Since reset date', 'date' => $startDate],
        ];

        $summary = [];
        $periodRows = [];
        foreach ($periodDefinitions as $period) {
            $rows = $this->getConnectedLearners($context, $learnerIds, $period['date']);
            $periodRows[$period['key']] = $rows;
            $count = \count($rows);
            $summary[] = [
                'key' => $period['key'],
                'label' => $period['label'],
                'value' => $count,
                'secondary' => \count($learnerIds) > 0
                    ? round($count * 100 / \count($learnerIds), 1).'%'
                    : '0%',
            ];
        }

        $connectedSinceStart = $periodRows['sinceStartDate'];
        $connectedLookup = array_fill_keys(
            array_map(static fn (array $row): int => (int) $row['id'], $connectedSinceStart),
            true
        );
        $inactive = array_values(array_filter(
            $learners,
            static fn (array $row): bool => !isset($connectedLookup[(int) $row['id']])
        ));
        foreach ($inactive as &$inactiveLearner) {
            $inactiveLearner['accesses'] = $this->getLearnerAccessCount(
                $context,
                (int) $inactiveLearner['id']
            );
        }
        unset($inactiveLearner);

        $resourceUsage = [];
        if ($this->tableExists('track_e_access')) {
            $resourceUsage = $this->connection->fetchAllAssociative(
                'SELECT COALESCE(NULLIF(access_tool, \'\'), \'unknown\') AS tool,
                        COUNT(*) AS accesses,
                        COUNT(DISTINCT access_user_id) AS users,
                        MAX(access_date) AS lastAccess
                   FROM track_e_access
                  WHERE c_id = :courseId
                    AND COALESCE(session_id, 0) = :sessionId
                    AND access_date >= :startDate
               GROUP BY COALESCE(NULLIF(access_tool, \'\'), \'unknown\')
               ORDER BY accesses DESC, tool ASC
                  LIMIT 100',
                [
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                    'startDate' => $startDate->format('Y-m-d H:i:s'),
                ]
            );
            foreach ($resourceUsage as &$resourceRow) {
                $resourceRow['id'] = (string) $resourceRow['tool'];
                $resourceRow['accesses'] = (int) $resourceRow['accesses'];
                $resourceRow['users'] = (int) $resourceRow['users'];
            }
            unset($resourceRow);
        }

        $connectedColumns = [
            ['key' => 'fullName', 'label' => 'Learner', 'type' => 'text'],
            ['key' => 'username', 'label' => 'Username', 'type' => 'text'],
            ['key' => 'accesses', 'label' => 'Connections', 'type' => 'number'],
            ['key' => 'firstAccess', 'label' => 'First connection', 'type' => 'datetime'],
            ['key' => 'lastAccess', 'label' => 'Last connection', 'type' => 'datetime'],
            ['key' => 'totalTime', 'label' => 'Total time', 'type' => 'duration'],
        ];

        return $this->result(
            'activity',
            'Course activity statistics',
            \count($periodRows['last24Hours']),
            1,
            max(1, \count($periodRows['last24Hours'])),
            $summary,
            $connectedColumns,
            $periodRows['last24Hours'],
            [
                [
                    'key' => 'last-week',
                    'title' => 'Connected users in the last week',
                    'columns' => $connectedColumns,
                    'items' => $periodRows['last7Days'],
                ],
                [
                    'key' => 'last-month',
                    'title' => 'Connected users in the last month',
                    'columns' => $connectedColumns,
                    'items' => $periodRows['last30Days'],
                ],
                [
                    'key' => 'since-reset-date',
                    'title' => 'Connected users since reset date',
                    'columns' => $connectedColumns,
                    'items' => $connectedSinceStart,
                ],
                [
                    'key' => 'inactive',
                    'title' => 'Not recently connected users',
                    'columns' => [
                        ['key' => 'fullName', 'label' => 'Learner', 'type' => 'text'],
                        ['key' => 'username', 'label' => 'Username', 'type' => 'text'],
                        ['key' => 'email', 'label' => 'Email', 'type' => 'text'],
                        ['key' => 'lastAccess', 'label' => 'Last connection', 'type' => 'datetime'],
                        ['key' => 'accesses', 'label' => 'Connections', 'type' => 'number'],
                    ],
                    'items' => $inactive,
                ],
                [
                    'key' => 'resources',
                    'title' => 'Resources used since reset date',
                    'columns' => [
                        ['key' => 'tool', 'label' => 'Tool', 'type' => 'text'],
                        ['key' => 'accesses', 'label' => 'Number of accesses', 'type' => 'number'],
                        ['key' => 'users', 'label' => 'Learners', 'type' => 'number'],
                        ['key' => 'lastAccess', 'label' => 'Last access', 'type' => 'datetime'],
                    ],
                    'items' => $resourceUsage,
                ],
            ],
            [
                'startDate' => $startDate->format('Y-m-d'),
                'totalLearners' => \count($learners),
            ]
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getGroups(CourseReportingContext $context, array $filters): array
    {
        $pagination = $this->pagination($filters);
        if (!$this->tableExists('c_group_info') || !$this->tableExists('resource_link')) {
            return $this->emptyResult('groups', 'Group report', $pagination, 'Group tables are unavailable.');
        }

        $titleColumn = $this->columnExists('c_group_info', 'title') ? 'group_info.title' : 'group_info.name';
        $where = [
            'rl.c_id = :courseId',
            'rl.deleted_at IS NULL',
        ];
        $params = ['courseId' => $context->courseId()];

        if ($context->sessionId() > 0) {
            $where[] = '(rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)';
            $params['sessionId'] = $context->sessionId();
        } else {
            $where[] = '(rl.session_id IS NULL OR rl.session_id = 0)';
        }

        if ($context->groupId > 0) {
            $where[] = 'group_info.iid = :groupId';
            $params['groupId'] = $context->groupId;
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ('' !== $keyword) {
            $where[] = $titleColumn.' LIKE :keyword';
            $params['keyword'] = '%'.$keyword.'%';
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT group_info.iid)
               FROM c_group_info group_info
               INNER JOIN resource_link rl ON rl.resource_node_id = group_info.resource_node_id
              WHERE '.$whereSql,
            $params
        );

        $totalMembers = 0;
        if ($this->tableExists('c_group_rel_user')) {
            $totalMembers = (int) $this->connection->fetchOne(
                'SELECT COUNT(DISTINCT group_user.user_id)
                   FROM c_group_info group_info
                   INNER JOIN resource_link rl ON rl.resource_node_id = group_info.resource_node_id
                   INNER JOIN c_group_rel_user group_user
                           ON group_user.group_id = group_info.iid
                          AND group_user.c_id = :courseId
                  WHERE '.$whereSql,
                $params
            );
        }

        $memberExpression = $this->tableExists('c_group_rel_user')
            ? '(SELECT COUNT(DISTINCT group_user.user_id)
                  FROM c_group_rel_user group_user
                 WHERE group_user.group_id = group_info.iid
                   AND group_user.c_id = :courseId)'
            : '0';

        $rows = $this->connection->fetchAllAssociative(
            'SELECT group_info.iid AS id,
                    '.$titleColumn.' AS title,
                    '.$memberExpression.' AS members
               FROM c_group_info group_info
               INNER JOIN resource_link rl ON rl.resource_node_id = group_info.resource_node_id
              WHERE '.$whereSql.'
           GROUP BY group_info.iid, '.$titleColumn.'
           ORDER BY '.$titleColumn.' ASC
              LIMIT :limit OFFSET :offset',
            $params + [
                'limit' => $pagination['itemsPerPage'],
                'offset' => $pagination['offset'],
            ],
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );

        foreach ($rows as &$row) {
            $groupId = (int) $row['id'];
            $row['id'] = $groupId;
            $row['members'] = (int) $row['members'];
            $row['time'] = $this->getGroupTime($context, $groupId);
            $row['progress'] = round($this->getGroupLearningPathProgress($context, $groupId), 2);
            $row['score'] = round($this->getGroupLearningPathScore($context, $groupId), 2);
            $row['works'] = $this->getGroupPublicationCount($context, $groupId);
            $row['messages'] = $this->getGroupForumPostCount($context, $groupId);
            $row['details'] = $groupId;
        }
        unset($row);

        return $this->result(
            'groups',
            'Group report',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [
                ['key' => 'groups', 'label' => 'Groups', 'value' => $total],
                ['key' => 'members', 'label' => 'Learners', 'value' => $totalMembers],
            ],
            [
                ['key' => 'title', 'label' => 'Group', 'type' => 'text'],
                ['key' => 'time', 'label' => 'Time', 'type' => 'duration'],
                ['key' => 'progress', 'label' => 'Progress', 'type' => 'percent'],
                ['key' => 'score', 'label' => 'Score', 'type' => 'percent'],
                ['key' => 'works', 'label' => 'Assignments', 'type' => 'number'],
                ['key' => 'messages', 'label' => 'Messages', 'type' => 'number'],
                ['key' => 'details', 'label' => 'Details', 'type' => 'group-detail'],
            ],
            $rows
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getResources(CourseReportingContext $context, array $filters): array
    {
        $pagination = $this->pagination($filters);
        if (
            !$this->tableExists('resource_link')
            || !$this->tableExists('resource_node')
            || !$this->tableExists('resource_type')
        ) {
            return $this->emptyResult('resources', 'Resource report', $pagination, 'Resource tables are unavailable.');
        }

        $where = [
            'rl.c_id = :courseId',
            'rl.deleted_at IS NULL',
            'resource_type.title IN (:resourceTypes)',
        ];
        $params = [
            'courseId' => $context->courseId(),
            'resourceTypes' => array_keys(self::RESOURCE_TYPE_LABELS),
        ];
        $types = [
            'resourceTypes' => ArrayParameterType::STRING,
        ];

        if ($context->sessionId() > 0) {
            $where[] = 'rl.session_id = :sessionId';
            $params['sessionId'] = $context->sessionId();
        } else {
            $where[] = 'rl.session_id IS NULL';
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ('' !== $keyword) {
            $where[] = '(creator.username LIKE :keyword
                OR creator.firstname LIKE :keyword
                OR creator.lastname LIKE :keyword
                OR resource_node.title LIKE :keyword
                OR resource_type.title LIKE :keyword)';
            $params['keyword'] = '%'.$keyword.'%';
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT rl.id)
               FROM resource_link rl
               INNER JOIN resource_node resource_node ON resource_node.id = rl.resource_node_id
               INNER JOIN resource_type resource_type ON resource_type.id = resource_node.resource_type_id
               LEFT JOIN user creator ON creator.id = resource_node.creator_id
              WHERE '.$whereSql,
            $params,
            $types
        );

        $sortMap = [
            'tool' => 'resource_type.title',
            'eventType' => 'rl.created_at',
            'session' => 'session.title',
            'username' => 'creator.username',
            'document' => 'resource_node.title',
            'date' => 'rl.created_at',
        ];
        $sort = (string) ($filters['sort'] ?? 'date');
        $orderBy = $sortMap[$sort] ?? $sortMap['date'];
        $direction = 'asc' === strtolower((string) ($filters['direction'] ?? 'desc')) ? 'ASC' : 'DESC';

        $ipExpression = $this->tableExists('track_e_login')
            ? '(SELECT login.user_ip
                  FROM track_e_login login
                 WHERE login.login_user_id = resource_node.creator_id
                   AND login.login_date < rl.created_at
              ORDER BY login.login_date DESC
                 LIMIT 1)'
            : 'NULL';

        $rows = $this->connection->fetchAllAssociative(
            'SELECT rl.id,
                    resource_type.title AS resourceType,
                    resource_node.title AS document,
                    resource_node.parent_id AS parentResourceNodeId,
                    creator.id AS userId,
                    creator.username,
                    CONCAT_WS(\' \', creator.firstname, creator.lastname) AS fullName,
                    session.id AS sessionId,
                    session.title AS session,
                    '.$ipExpression.' AS userIp,
                    rl.created_at AS date
               FROM resource_link rl
               INNER JOIN resource_node resource_node ON resource_node.id = rl.resource_node_id
               INNER JOIN resource_type resource_type ON resource_type.id = resource_node.resource_type_id
               LEFT JOIN user creator ON creator.id = resource_node.creator_id
               LEFT JOIN session session ON session.id = rl.session_id
              WHERE '.$whereSql.'
           ORDER BY '.$orderBy.' '.$direction.'
              LIMIT :limit OFFSET :offset',
            $params + [
                'limit' => $pagination['itemsPerPage'],
                'offset' => $pagination['offset'],
            ],
            $types + [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['userId'] = null === $row['userId'] ? null : (int) $row['userId'];
            $row['sessionId'] = null === $row['sessionId'] ? 0 : (int) $row['sessionId'];
            $row['parentResourceNodeId'] = null === $row['parentResourceNodeId']
                ? 0
                : (int) $row['parentResourceNodeId'];
            $row['tool'] = self::RESOURCE_TYPE_LABELS[(string) $row['resourceType']] ?? (string) $row['resourceType'];
            $row['eventType'] = 'Created';
            $row['session'] = (string) ($row['session'] ?? '');
            $row['userIp'] = (string) ($row['userIp'] ?? 'Unknown');
        }
        unset($row);

        return $this->result(
            'resources',
            'Resource report',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [['key' => 'resources', 'label' => 'Resources', 'value' => $total]],
            [
                ['key' => 'tool', 'label' => 'Tool', 'type' => 'text', 'sortable' => true],
                ['key' => 'eventType', 'label' => 'Event type', 'type' => 'text', 'sortable' => true],
                ['key' => 'session', 'label' => 'Session', 'type' => 'text', 'sortable' => true],
                ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'sortable' => true],
                ['key' => 'userIp', 'label' => 'IP address', 'type' => 'text'],
                ['key' => 'document', 'label' => 'Document', 'type' => 'text', 'sortable' => true],
                ['key' => 'date', 'label' => 'Date', 'type' => 'datetime', 'sortable' => true],
            ],
            $rows
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getTools(CourseReportingContext $context, array $filters): array
    {
        $keyword = mb_strtolower(trim((string) ($filters['keyword'] ?? '')));
        $metrics = [
            $this->toolMetric('Learning paths', 'c_lp', $context),
            $this->toolMetric('Tests', 'c_quiz', $context),
            $this->toolMetric('Forums', 'c_forum_forum', $context),
            $this->toolMetric('Forum threads', 'c_forum_thread', $context),
            $this->toolMetric('Forum posts', 'c_forum_post', $context),
            $this->toolMetric('Documents', 'c_document', $context),
            $this->toolMetric('Links', 'c_link', $context),
            $this->toolMetric('Assignments', 'c_student_publication', $context),
        ];
        $metrics = array_values(array_filter(
            $metrics,
            static fn (array $item): bool => (bool) $item['available']
        ));

        $items = [];
        foreach ($metrics as $metric) {
            if (
                '' !== $keyword
                && !str_contains(mb_strtolower((string) $metric['title']), $keyword)
            ) {
                continue;
            }

            $items[] = [
                'id' => (string) $metric['id'],
                'title' => (string) $metric['title'],
                'value' => (int) $metric['total'],
            ];
        }

        $sections = [];

        $learningPathRows = [];
        if ($this->tableExists('c_lp') && $this->tableExists('resource_link')) {
            $learningPathRows = $this->connection->fetchAllAssociative(
                'SELECT DISTINCT lp.iid AS id, lp.title
                   FROM c_lp lp
                   INNER JOIN resource_link rl ON rl.resource_node_id = lp.resource_node_id
                  WHERE rl.c_id = :courseId
                    AND rl.deleted_at IS NULL
                    AND '.($context->sessionId() > 0
                        ? '(rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
                        : '(rl.session_id IS NULL OR rl.session_id = 0)').'
               ORDER BY lp.title ASC',
                $context->sessionId() > 0
                    ? ['courseId' => $context->courseId(), 'sessionId' => $context->sessionId()]
                    : ['courseId' => $context->courseId()]
            );
            foreach ($learningPathRows as &$row) {
                $row['id'] = (int) $row['id'];
                $row = array_merge($row, $this->learningPathStats($context, (int) $row['id']));
            }
            unset($row);
        }
        $sections[] = [
            'key' => 'learning-paths',
            'title' => 'Learning path progress',
            'columns' => [
                ['key' => 'title', 'label' => 'Learning path', 'type' => 'text'],
                ['key' => 'learners', 'label' => 'Learners', 'type' => 'number'],
                ['key' => 'averageProgress', 'label' => 'Progress', 'type' => 'percent'],
                ['key' => 'totalTime', 'label' => 'Total time', 'type' => 'duration'],
            ],
            'items' => $learningPathRows,
        ];

        $exerciseRows = [];
        if ($this->tableExists('c_quiz') && $this->tableExists('resource_link')) {
            $exerciseRows = $this->connection->fetchAllAssociative(
                'SELECT DISTINCT quiz.iid AS id, quiz.title
                   FROM c_quiz quiz
                   INNER JOIN resource_link rl ON rl.resource_node_id = quiz.resource_node_id
                  WHERE rl.c_id = :courseId
                    AND rl.deleted_at IS NULL
                    AND '.($context->sessionId() > 0
                        ? '(rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
                        : '(rl.session_id IS NULL OR rl.session_id = 0)').'
               ORDER BY quiz.title ASC',
                $context->sessionId() > 0
                    ? ['courseId' => $context->courseId(), 'sessionId' => $context->sessionId()]
                    : ['courseId' => $context->courseId()]
            );
            foreach ($exerciseRows as &$row) {
                $row['id'] = (int) $row['id'];
                $row = array_merge($row, $this->exerciseStats($context, (int) $row['id']));
            }
            unset($row);
        }
        $sections[] = [
            'key' => 'tests',
            'title' => 'Test results',
            'columns' => [
                ['key' => 'title', 'label' => 'Test', 'type' => 'text'],
                ['key' => 'learners', 'label' => 'Learners', 'type' => 'number'],
                ['key' => 'attempts', 'label' => 'Attempts', 'type' => 'number'],
                ['key' => 'averageScore', 'label' => 'Average score', 'type' => 'percent'],
                ['key' => 'bestScore', 'label' => 'Best score', 'type' => 'percent'],
            ],
            'items' => $exerciseRows,
        ];

        $accessRows = [];
        if ($this->tableExists('track_e_access')) {
            $accessRows = $this->connection->fetchAllAssociative(
                'SELECT COALESCE(NULLIF(access_tool, \'\'), \'unknown\') AS title,
                        COUNT(*) AS accesses,
                        COUNT(DISTINCT access_user_id) AS users,
                        MAX(access_date) AS lastAccess
                   FROM track_e_access
                  WHERE c_id = :courseId
                    AND COALESCE(session_id, 0) = :sessionId
               GROUP BY COALESCE(NULLIF(access_tool, \'\'), \'unknown\')
               ORDER BY accesses DESC, title ASC
                  LIMIT 100',
                [
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                ]
            );
            foreach ($accessRows as &$accessRow) {
                $accessRow['id'] = (string) $accessRow['title'];
                $accessRow['accesses'] = (int) $accessRow['accesses'];
                $accessRow['users'] = (int) $accessRow['users'];
            }
            unset($accessRow);
        }
        $sections[] = [
            'key' => 'usage',
            'title' => 'Most used tools',
            'columns' => [
                ['key' => 'title', 'label' => 'Tool', 'type' => 'text'],
                ['key' => 'accesses', 'label' => 'Number of accesses', 'type' => 'number'],
                ['key' => 'users', 'label' => 'Learners', 'type' => 'number'],
                ['key' => 'lastAccess', 'label' => 'Last access', 'type' => 'datetime'],
            ],
            'items' => $accessRows,
        ];

        $downloadRows = [];
        if (
            $this->tableExists('track_e_downloads')
            && $this->tableExists('resource_link')
            && $this->tableExists('resource_node')
            && $this->columnExists('track_e_downloads', 'resource_link_id')
        ) {
            $downloadRows = $this->connection->fetchAllAssociative(
                'SELECT COALESCE(NULLIF(resource_node.title, \'\'), download.down_doc_path) AS title,
                        COUNT(*) AS downloads,
                        COUNT(DISTINCT download.down_user_id) AS users,
                        MAX(download.down_date) AS lastDownload
                   FROM track_e_downloads download
                   INNER JOIN resource_link rl ON rl.id = download.resource_link_id
                   LEFT JOIN resource_node resource_node ON resource_node.id = rl.resource_node_id
                  WHERE rl.c_id = :courseId
                    AND rl.deleted_at IS NULL
                    AND '.($context->sessionId() > 0
                        ? 'rl.session_id = :sessionId'
                        : 'rl.session_id IS NULL').'
               GROUP BY COALESCE(NULLIF(resource_node.title, \'\'), download.down_doc_path)
               ORDER BY downloads DESC, title ASC
                  LIMIT 10',
                $context->sessionId() > 0
                    ? ['courseId' => $context->courseId(), 'sessionId' => $context->sessionId()]
                    : ['courseId' => $context->courseId()]
            );
            foreach ($downloadRows as $index => &$downloadRow) {
                $downloadRow['id'] = $index + 1;
                $downloadRow['downloads'] = (int) $downloadRow['downloads'];
                $downloadRow['users'] = (int) $downloadRow['users'];
            }
            unset($downloadRow);
        }
        $sections[] = [
            'key' => 'documents',
            'title' => 'Most downloaded documents',
            'columns' => [
                ['key' => 'title', 'label' => 'Document', 'type' => 'text'],
                ['key' => 'downloads', 'label' => 'Downloads', 'type' => 'number'],
                ['key' => 'users', 'label' => 'Learners', 'type' => 'number'],
                ['key' => 'lastDownload', 'label' => 'Last download', 'type' => 'datetime'],
            ],
            'items' => $downloadRows,
        ];

        $linkRows = [];
        if ($this->tableExists('track_e_links') && $this->tableExists('c_link')) {
            $linkRows = $this->connection->fetchAllAssociative(
                'SELECT link.iid AS id,
                        link.title,
                        link.url,
                        COUNT(*) AS visits,
                        COUNT(DISTINCT tracking.links_user_id) AS users
                   FROM track_e_links tracking
                   INNER JOIN c_link link ON link.iid = tracking.links_link_id
                  WHERE tracking.c_id = :courseId
                    AND COALESCE(tracking.session_id, 0) = :sessionId
               GROUP BY link.iid, link.title, link.url
               ORDER BY visits DESC, link.title ASC
                  LIMIT 10',
                [
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                ]
            );
            foreach ($linkRows as &$linkRow) {
                $linkRow['id'] = (int) $linkRow['id'];
                $linkRow['visits'] = (int) $linkRow['visits'];
                $linkRow['users'] = (int) $linkRow['users'];
            }
            unset($linkRow);
        }
        $sections[] = [
            'key' => 'links',
            'title' => 'Most visited links',
            'columns' => [
                ['key' => 'title', 'label' => 'Link', 'type' => 'text'],
                ['key' => 'url', 'label' => 'URL', 'type' => 'text'],
                ['key' => 'visits', 'label' => 'Visits', 'type' => 'number'],
                ['key' => 'users', 'label' => 'Learners', 'type' => 'number'],
            ],
            'items' => $linkRows,
        ];

        return $this->result(
            'tools',
            'Course report',
            \count($items),
            1,
            max(1, \count($items)),
            [['key' => 'tools', 'label' => 'Available tools', 'value' => \count($items)]],
            [
                ['key' => 'title', 'label' => 'Tool', 'type' => 'text'],
                ['key' => 'value', 'label' => 'Total', 'type' => 'number'],
            ],
            $items,
            $sections
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getExams(CourseReportingContext $context, array $filters): array
    {
        $pagination = $this->pagination($filters);
        if (
            !$this->tableExists('c_quiz')
            || !$this->tableExists('resource_link')
            || !$this->tableExists('track_e_exercises')
        ) {
            return $this->emptyResult('exams', 'Test report', $pagination, 'Test tables are unavailable.');
        }

        $quizWhere = [
            'rl.c_id = :courseId',
            'rl.deleted_at IS NULL',
        ];
        $quizParams = ['courseId' => $context->courseId()];
        if ($context->sessionId() > 0) {
            $quizWhere[] = '(rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)';
            $quizParams['sessionId'] = $context->sessionId();
        } else {
            $quizWhere[] = '(rl.session_id IS NULL OR rl.session_id = 0)';
        }

        $exerciseId = max(0, (int) ($filters['exerciseId'] ?? 0));
        if ($exerciseId > 0) {
            $quizWhere[] = 'quiz.iid = :exerciseId';
            $quizParams['exerciseId'] = $exerciseId;
        }

        $quizzes = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT quiz.iid AS id, quiz.title
               FROM c_quiz quiz
               INNER JOIN resource_link rl ON rl.resource_node_id = quiz.resource_node_id
              WHERE '.implode(' AND ', $quizWhere).'
           ORDER BY quiz.title ASC',
            $quizParams
        );

        $allQuizOptions = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT quiz.iid AS value, quiz.title AS label
               FROM c_quiz quiz
               INNER JOIN resource_link rl ON rl.resource_node_id = quiz.resource_node_id
              WHERE rl.c_id = :courseId
                AND rl.deleted_at IS NULL
                AND '.($context->sessionId() > 0
                    ? '(rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
                    : '(rl.session_id IS NULL OR rl.session_id = 0)').'
           ORDER BY quiz.title ASC',
            $context->sessionId() > 0
                ? ['courseId' => $context->courseId(), 'sessionId' => $context->sessionId()]
                : ['courseId' => $context->courseId()]
        );
        foreach ($allQuizOptions as &$quizOption) {
            $quizOption['value'] = (int) $quizOption['value'];
        }
        unset($quizOption);

        $learners = $this->getLearners($context);
        $quizIds = array_map(static fn (array $quiz): int => (int) $quiz['id'], $quizzes);
        $learnerIds = array_map(static fn (array $learner): int => (int) $learner['id'], $learners);

        $attemptsByPair = [];
        if ([] !== $quizIds && [] !== $learnerIds) {
            $quizPlaceholders = implode(',', array_fill(0, \count($quizIds), '?'));
            $learnerPlaceholders = implode(',', array_fill(0, \count($learnerIds), '?'));
            $attemptRows = $this->connection->fetchAllAssociative(
                'SELECT exercise.exe_exo_id AS quizId,
                        exercise.exe_user_id AS userId,
                        COUNT(*) AS attempts,
                        MAX(
                            CASE
                                WHEN exercise.max_score > 0
                                THEN exercise.score * 100 / exercise.max_score
                                ELSE 0
                            END
                        ) AS percentage
                   FROM track_e_exercises exercise
                  WHERE exercise.c_id = ?
                    AND COALESCE(exercise.session_id, 0) = ?
                    AND exercise.exe_exo_id IN ('.$quizPlaceholders.')
                    AND exercise.exe_user_id IN ('.$learnerPlaceholders.')
               GROUP BY exercise.exe_exo_id, exercise.exe_user_id',
                [
                    $context->courseId(),
                    $context->sessionId(),
                    ...$quizIds,
                    ...$learnerIds,
                ]
            );

            foreach ($attemptRows as $attemptRow) {
                $key = (int) $attemptRow['quizId'].'-'.(int) $attemptRow['userId'];
                $attemptsByPair[$key] = [
                    'attempts' => (int) $attemptRow['attempts'],
                    'percentage' => round((float) $attemptRow['percentage'], 2),
                ];
            }
        }

        $threshold = min(100, max(0, (int) ($filters['score'] ?? 70)));
        $keyword = mb_strtolower(trim((string) ($filters['keyword'] ?? '')));
        $items = [];

        foreach ($quizzes as $quiz) {
            $quizId = (int) $quiz['id'];
            $quizTitle = (string) $quiz['title'];

            foreach ($learners as $learner) {
                $userId = (int) $learner['id'];
                $pairKey = $quizId.'-'.$userId;
                $stats = $attemptsByPair[$pairKey] ?? ['attempts' => 0, 'percentage' => 0.0];

                $searchable = mb_strtolower(
                    $quizTitle.' '
                    .(string) $learner['fullName'].' '
                    .(string) $learner['username']
                );
                if ('' !== $keyword && !str_contains($searchable, $keyword)) {
                    continue;
                }

                $status = 'No attempts';
                if ($stats['attempts'] > 0) {
                    $status = $stats['percentage'] >= $threshold ? 'Pass' : 'Fail';
                }

                $items[] = [
                    'id' => $pairKey,
                    'test' => $quizTitle,
                    'userId' => $userId,
                    'fullName' => (string) $learner['fullName'],
                    'username' => (string) $learner['username'],
                    'percentage' => $stats['percentage'],
                    'status' => $status,
                    'attempts' => $stats['attempts'],
                ];
            }
        }

        $total = \count($items);
        $pageItems = \array_slice($items, $pagination['offset'], $pagination['itemsPerPage']);

        return $this->result(
            'exams',
            'Test report',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [
                ['key' => 'tests', 'label' => 'Tests', 'value' => \count($quizzes)],
                ['key' => 'learners', 'label' => 'Learners', 'value' => \count($learners)],
                ['key' => 'attempts', 'label' => 'Attempts', 'value' => array_sum(array_column($items, 'attempts'))],
            ],
            [
                ['key' => 'test', 'label' => 'Tests', 'type' => 'text'],
                ['key' => 'fullName', 'label' => 'User', 'type' => 'text'],
                ['key' => 'username', 'label' => 'Username', 'type' => 'text'],
                ['key' => 'percentage', 'label' => 'Percentage', 'type' => 'percent'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                ['key' => 'attempts', 'label' => 'Attempts', 'type' => 'number'],
            ],
            $pageItems,
            [],
            [
                'score' => $threshold,
                'exerciseId' => $exerciseId,
                'exercises' => $allQuizOptions,
            ]
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getAudit(CourseReportingContext $context, array $filters): array
    {
        $pagination = $this->pagination($filters);
        if (!$this->tableExists('track_e_default')) {
            return $this->emptyResult('audit', 'Audit report', $pagination, 'Audit table is unavailable.');
        }

        $requiredColumns = [
            'default_id',
            'default_user_id',
            'default_event_type',
            'default_value_type',
            'default_value',
            'default_date',
            'c_id',
            'session_id',
        ];
        foreach ($requiredColumns as $column) {
            if (!$this->columnExists('track_e_default', $column)) {
                return $this->emptyResult('audit', 'Audit report', $pagination, 'Audit table structure is incomplete.');
            }
        }

        $where = [
            'audit.c_id = :courseId',
            'audit.session_id = :sessionId',
            'user.active <> -2',
        ];
        $params = [
            'courseId' => $context->courseId(),
            'sessionId' => $context->sessionId(),
        ];

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ('' !== $keyword) {
            $where[] = '(user.username LIKE :keyword
                OR user.firstname LIKE :keyword
                OR user.lastname LIKE :keyword
                OR audit.default_event_type LIKE :keyword
                OR audit.default_value_type LIKE :keyword
                OR audit.default_value LIKE :keyword)';
            $params['keyword'] = '%'.$keyword.'%';
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
               FROM track_e_default audit
               LEFT JOIN user user ON user.id = audit.default_user_id
              WHERE '.$whereSql,
            $params
        );

        $sortMap = [
            'eventType' => 'audit.default_event_type',
            'dataType' => 'audit.default_value_type',
            'value' => 'audit.default_value',
            'username' => 'user.username',
            'date' => 'audit.default_date',
        ];
        $sort = (string) ($filters['sort'] ?? 'date');
        $orderBy = $sortMap[$sort] ?? $sortMap['date'];
        $direction = 'asc' === strtolower((string) ($filters['direction'] ?? 'desc')) ? 'ASC' : 'DESC';

        $ipExpression = $this->tableExists('track_e_login')
            ? '(SELECT login.user_ip
                  FROM track_e_login login
                 WHERE login.login_user_id = audit.default_user_id
                   AND login.login_date < audit.default_date
              ORDER BY login.login_date DESC
                 LIMIT 1)'
            : 'NULL';

        $sessionExpression = $this->tableExists('session')
            ? '(SELECT session.title FROM session session WHERE session.id = audit.session_id)'
            : 'NULL';

        $rows = $this->connection->fetchAllAssociative(
            'SELECT audit.default_id AS id,
                    audit.default_user_id AS userId,
                    audit.default_event_type AS eventType,
                    audit.default_value_type AS dataType,
                    audit.default_value AS value,
                    audit.default_date AS date,
                    audit.c_id AS courseId,
                    audit.session_id AS sessionId,
                    user.username,
                    CONCAT_WS(\' \', user.firstname, user.lastname) AS fullName,
                    '.$ipExpression.' AS userIp,
                    '.$sessionExpression.' AS session
               FROM track_e_default audit
               LEFT JOIN user user ON user.id = audit.default_user_id
              WHERE '.$whereSql.'
           ORDER BY '.$orderBy.' '.$direction.'
              LIMIT :limit OFFSET :offset',
            $params + [
                'limit' => $pagination['itemsPerPage'],
                'offset' => $pagination['offset'],
            ],
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['userId'] = (int) $row['userId'];
            $row['courseId'] = (int) $row['courseId'];
            $row['sessionId'] = (int) $row['sessionId'];
            $row['course'] = $context->course->getTitle();
            $row['session'] = (string) ($row['session'] ?? '');
            $row['userIp'] = (string) ($row['userIp'] ?? 'Unknown');
            $row['value'] = $this->normalizeAuditValue((string) ($row['value'] ?? ''));
        }
        unset($row);

        return $this->result(
            'audit',
            'Audit report',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [['key' => 'events', 'label' => 'Events', 'value' => $total]],
            [
                ['key' => 'eventType', 'label' => 'Event type', 'type' => 'text', 'sortable' => true],
                ['key' => 'dataType', 'label' => 'Data type', 'type' => 'text', 'sortable' => true],
                ['key' => 'value', 'label' => 'Value', 'type' => 'text', 'sortable' => true],
                ['key' => 'course', 'label' => 'Course', 'type' => 'text'],
                ['key' => 'session', 'label' => 'Session', 'type' => 'text'],
                ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'sortable' => true],
                ['key' => 'userIp', 'label' => 'IP address', 'type' => 'text'],
                ['key' => 'date', 'label' => 'Date', 'type' => 'datetime', 'sortable' => true],
            ],
            $rows
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getLearningPaths(CourseReportingContext $context, array $filters): array
    {
        $pagination = $this->pagination($filters);
        $mode = (string) ($filters['mode'] ?? 'paths');
        if (!\in_array($mode, ['paths', 'users', 'questions'], true)) {
            $mode = 'paths';
        }

        if ('questions' === $mode) {
            return $this->getQuestionReport($context, $filters, $pagination);
        }

        if (!$this->tableExists('c_lp') || !$this->tableExists('resource_link')) {
            return $this->emptyResult('learning-paths', 'Learning path report', $pagination, 'Learning path tables are unavailable.');
        }

        if ('users' === $mode) {
            return $this->getLearningPathUsers($context, $filters, $pagination);
        }

        $where = ['rl.c_id = :courseId', 'rl.deleted_at IS NULL'];
        $params = ['courseId' => $context->courseId()];
        $where[] = $context->sessionId() > 0
            ? '(rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
            : '(rl.session_id IS NULL OR rl.session_id = 0)';
        if ($context->sessionId() > 0) {
            $params['sessionId'] = $context->sessionId();
        }
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ('' !== $keyword) {
            $where[] = 'lp.title LIKE :keyword';
            $params['keyword'] = '%'.$keyword.'%';
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT lp.iid)
               FROM c_lp lp
               INNER JOIN resource_link rl ON rl.resource_node_id = lp.resource_node_id
              WHERE '.$whereSql,
            $params
        );
        $rows = $this->connection->fetchAllAssociative(
            'SELECT lp.iid AS id, lp.title
               FROM c_lp lp
               INNER JOIN resource_link rl ON rl.resource_node_id = lp.resource_node_id
              WHERE '.$whereSql.'
           GROUP BY lp.iid, lp.title
           ORDER BY lp.title ASC
              LIMIT :limit OFFSET :offset',
            $params + [
                'limit' => $pagination['itemsPerPage'],
                'offset' => $pagination['offset'],
            ],
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );
        foreach ($rows as &$row) {
            $stats = $this->learningPathStats($context, (int) $row['id']);
            $row = array_merge($row, $stats);
        }
        unset($row);

        return $this->result(
            'learning-paths',
            'Learning path report',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [
                ['key' => 'paths', 'label' => 'Learning paths', 'value' => $total],
                ['key' => 'views', 'label' => 'Learners', 'value' => array_sum(array_column($rows, 'learners'))],
            ],
            [
                ['key' => 'title', 'label' => 'Learning path', 'type' => 'text'],
                ['key' => 'learners', 'label' => 'Learners', 'type' => 'number'],
                ['key' => 'averageProgress', 'label' => 'Progress', 'type' => 'percent'],
                ['key' => 'averageScore', 'label' => 'Score', 'type' => 'percent'],
                ['key' => 'totalTime', 'label' => 'Total time', 'type' => 'duration'],
                ['key' => 'firstAccess', 'label' => 'First access', 'type' => 'datetime'],
                ['key' => 'lastAccess', 'label' => 'Last access', 'type' => 'datetime'],
            ],
            $rows,
            [],
            ['mode' => $mode, 'modes' => ['paths', 'users', 'questions']]
        );
    }

    /**
     * @param array<string, mixed>                             $filters
     * @param array{page: int, itemsPerPage: int, offset: int} $pagination
     *
     * @return array<string, mixed>
     */
    private function getLearningPathUsers(
        CourseReportingContext $context,
        array $filters,
        array $pagination
    ): array {
        if (!$this->tableExists('c_lp_view')) {
            return $this->emptyResult(
                'learning-paths',
                'Learning path results by learner',
                $pagination,
                'The learning path view table is unavailable.',
                ['mode' => 'users', 'modes' => ['paths', 'users', 'questions']]
            );
        }

        $latestWhere = [
            'candidate.c_id = :courseId',
            'COALESCE(candidate.session_id, 0) = :sessionId',
        ];
        $params = [
            'courseId' => $context->courseId(),
            'sessionId' => $context->sessionId(),
        ];

        if ($context->groupId > 0 && $this->tableExists('c_group_rel_user')) {
            $latestWhere[] = 'EXISTS (
                SELECT 1
                  FROM c_group_rel_user group_user
                 WHERE group_user.user_id = candidate.user_id
                   AND group_user.group_id = :groupId
                   AND group_user.c_id = :courseId
            )';
            $params['groupId'] = $context->groupId;
        }

        $latestViewSql = 'SELECT candidate.user_id,
                                 candidate.lp_id,
                                 MAX(candidate.iid) AS latestId
                            FROM c_lp_view candidate
                           WHERE '.implode(' AND ', $latestWhere).'
                        GROUP BY candidate.user_id, candidate.lp_id';

        $where = [];
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ('' !== $keyword) {
            $where[] = '(user.username LIKE :keyword
                OR user.firstname LIKE :keyword
                OR user.lastname LIKE :keyword
                OR lp.title LIKE :keyword)';
            $params['keyword'] = '%'.$keyword.'%';
        }
        $whereSql = [] === $where ? '' : 'WHERE '.implode(' AND ', $where);

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
               FROM ('.$latestViewSql.') latest
               INNER JOIN c_lp_view view ON view.iid = latest.latestId
               LEFT JOIN user user ON user.id = view.user_id
               LEFT JOIN c_lp lp ON lp.iid = view.lp_id
               '.$whereSql,
            $params
        );

        $hasItemViews = $this->tableExists('c_lp_item_view');
        $totalTimeExpression = $hasItemViews && $this->columnExists('c_lp_item_view', 'total_time')
            ? '(SELECT COALESCE(SUM(item_view.total_time), 0)
                  FROM c_lp_item_view item_view
                 WHERE item_view.lp_view_id = view.iid)'
            : '0';
        $scoreExpression = $hasItemViews
            && $this->columnExists('c_lp_item_view', 'score')
            && $this->columnExists('c_lp_item_view', 'max_score')
            ? '(SELECT COALESCE(
                    100 * SUM(item_view.score) / NULLIF(SUM(item_view.max_score), 0),
                    0
                )
                  FROM c_lp_item_view item_view
                 WHERE item_view.lp_view_id = view.iid)'
            : '0';
        $firstAccessExpression = $hasItemViews && $this->columnExists('c_lp_item_view', 'start_time')
            ? '(SELECT MIN(FROM_UNIXTIME(item_view.start_time))
                  FROM c_lp_item_view item_view
                 WHERE item_view.lp_view_id = view.iid
                   AND item_view.start_time > 0)'
            : 'NULL';
        $completionExpression = $this->columnExists('c_lp_view', 'completion_date')
            ? 'view.completion_date'
            : 'NULL';

        $rows = $this->connection->fetchAllAssociative(
            'SELECT view.iid AS id,
                    view.user_id AS userId,
                    view.lp_id AS learningPathId,
                    CONCAT_WS(\' \', user.firstname, user.lastname) AS fullName,
                    user.username,
                    lp.title,
                    view.progress,
                    '.$scoreExpression.' AS score,
                    '.$totalTimeExpression.' AS totalTime,
                    '.$firstAccessExpression.' AS firstAccess,
                    '.$completionExpression.' AS completionDate
               FROM ('.$latestViewSql.') latest
               INNER JOIN c_lp_view view ON view.iid = latest.latestId
               LEFT JOIN user user ON user.id = view.user_id
               LEFT JOIN c_lp lp ON lp.iid = view.lp_id
               '.$whereSql.'
           ORDER BY user.lastname ASC, user.firstname ASC, lp.title ASC
              LIMIT :limit OFFSET :offset',
            $params + [
                'limit' => $pagination['itemsPerPage'],
                'offset' => $pagination['offset'],
            ],
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['userId'] = (int) $row['userId'];
            $row['learningPathId'] = (int) $row['learningPathId'];
            $row['progress'] = (float) ($row['progress'] ?? 0);
            $row['score'] = round((float) ($row['score'] ?? 0), 2);
            $row['totalTime'] = (int) ($row['totalTime'] ?? 0);
        }
        unset($row);

        return $this->result(
            'learning-paths',
            'Learning path results by learner',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [['key' => 'results', 'label' => 'Results', 'value' => $total]],
            [
                ['key' => 'fullName', 'label' => 'Learner', 'type' => 'text'],
                ['key' => 'username', 'label' => 'Username', 'type' => 'text'],
                ['key' => 'title', 'label' => 'Learning path', 'type' => 'text'],
                ['key' => 'progress', 'label' => 'Progress', 'type' => 'percent'],
                ['key' => 'score', 'label' => 'Score', 'type' => 'percent'],
                ['key' => 'totalTime', 'label' => 'Total time', 'type' => 'duration'],
                ['key' => 'firstAccess', 'label' => 'First access', 'type' => 'datetime'],
                ['key' => 'completionDate', 'label' => 'Completion date', 'type' => 'datetime'],
            ],
            $rows,
            [],
            ['mode' => 'users', 'modes' => ['paths', 'users', 'questions']]
        );
    }

    /**
     * @param array<string, mixed>                             $filters
     * @param array{page: int, itemsPerPage: int, offset: int} $pagination
     *
     * @return array<string, mixed>
     */
    private function getQuestionReport(
        CourseReportingContext $context,
        array $filters,
        array $pagination
    ): array {
        if (
            !$this->tableExists('track_e_attempt')
            || !$this->tableExists('track_e_exercises')
            || !$this->tableExists('c_quiz_question')
        ) {
            return $this->emptyResult(
                'learning-paths',
                'Question report',
                $pagination,
                'Question attempt tables are unavailable.',
                ['mode' => 'questions', 'modes' => ['paths', 'users', 'questions']]
            );
        }

        $where = [
            'exercise.c_id = :courseId',
            'COALESCE(exercise.session_id, 0) = :sessionId',
            'exercise.orig_lp_id > 0',
        ];
        $params = [
            'courseId' => $context->courseId(),
            'sessionId' => $context->sessionId(),
        ];

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ('' !== $keyword) {
            $where[] = 'question.question LIKE :keyword';
            $params['keyword'] = '%'.$keyword.'%';
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT attempt.question_id)
               FROM track_e_attempt attempt
               INNER JOIN track_e_exercises exercise ON exercise.exe_id = attempt.exe_id
               INNER JOIN c_quiz_question question ON question.iid = attempt.question_id
              WHERE '.$whereSql,
            $params
        );

        $rows = $this->connection->fetchAllAssociative(
            'SELECT attempt.question_id AS id,
                    question.question AS title,
                    question.ponderation AS weighting,
                    ROUND(AVG(attempt.marks), 2) AS averageMarks,
                    COUNT(*) AS quantity
               FROM track_e_attempt attempt
               INNER JOIN track_e_exercises exercise ON exercise.exe_id = attempt.exe_id
               INNER JOIN c_quiz_question question ON question.iid = attempt.question_id
              WHERE '.$whereSql.'
           GROUP BY attempt.question_id, question.question, question.ponderation
           ORDER BY quantity DESC, title ASC
              LIMIT :limit OFFSET :offset',
            $params + [
                'limit' => $pagination['itemsPerPage'],
                'offset' => $pagination['offset'],
            ],
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['averageMarks'] = round((float) $row['averageMarks'], 2);
            $row['weighting'] = round((float) $row['weighting'], 2);
            $row['quantity'] = (int) $row['quantity'];
            $row['score'] = $row['averageMarks'].' / '.$row['weighting'];
        }
        unset($row);

        return $this->result(
            'learning-paths',
            'Question report',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [['key' => 'questions', 'label' => 'Questions', 'value' => $total]],
            [
                ['key' => 'title', 'label' => 'Question', 'type' => 'text'],
                ['key' => 'score', 'label' => 'Score', 'type' => 'text'],
                ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number'],
            ],
            $rows,
            [],
            ['mode' => 'questions', 'modes' => ['paths', 'users', 'questions']]
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getTotalTime(CourseReportingContext $context, array $filters): array
    {
        $pagination = $this->pagination($filters);
        $learners = $this->filterLearners($this->getLearners($context), $filters);
        $total = \count($learners);

        $learnerIds = array_map(static fn (array $learner): int => (int) $learner['id'], $learners);
        $totalCourseTime = $this->getCourseTimeForLearners($context, $learnerIds);

        $pageRows = \array_slice($learners, $pagination['offset'], $pagination['itemsPerPage']);
        foreach ($pageRows as &$row) {
            $userId = (int) $row['id'];
            $row['courseTime'] = $this->getLearnerCourseTime($context, $userId);
            $row['learningPathTime'] = $this->getLearnerLearningPathTime($context, $userId);
            $row['details'] = $userId;
        }
        unset($row);

        return $this->result(
            'total-time',
            'Total time',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [
                ['key' => 'learners', 'label' => 'Learners', 'value' => $total],
                ['key' => 'time', 'label' => 'Total time', 'value' => $totalCourseTime, 'type' => 'duration'],
            ],
            [
                ['key' => 'officialCode', 'label' => 'Official code', 'type' => 'text'],
                ['key' => 'lastName', 'label' => 'Last name', 'type' => 'text'],
                ['key' => 'firstName', 'label' => 'First name', 'type' => 'text'],
                ['key' => 'username', 'label' => 'Username', 'type' => 'text'],
                ['key' => 'courseTime', 'label' => 'Course time', 'type' => 'duration'],
                ['key' => 'learningPathTime', 'label' => 'Learning path time', 'type' => 'duration'],
                ['key' => 'firstAccess', 'label' => 'First access', 'type' => 'datetime'],
                ['key' => 'lastAccess', 'label' => 'Last access', 'type' => 'datetime'],
                ['key' => 'details', 'label' => 'Details', 'type' => 'learner-detail'],
            ],
            $pageRows
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getSessionReport(CourseReportingContext $context, array $filters): array
    {
        $pagination = $this->pagination($filters);
        if (0 === $context->sessionId()) {
            return $this->emptyResult(
                'session',
                'Session report',
                $pagination,
                'This report is available only inside a session.'
            );
        }

        $learners = $this->filterLearners($this->getLearners($context), $filters);
        $total = \count($learners);
        $pageRows = \array_slice($learners, $pagination['offset'], $pagination['itemsPerPage']);
        foreach ($pageRows as &$row) {
            $userId = (int) $row['id'];
            $row['courseAccesses'] = $this->getLearnerAccessCount($context, $userId);
            $row['totalTime'] = $this->getLearnerCourseTime($context, $userId);
            $row['learningPathProgress'] = $this->getLearnerLearningPathProgress($context, $userId);
            $row['exerciseAverage'] = $this->getLearnerExerciseAverage($context, $userId);
        }
        unset($row);

        return $this->result(
            'session',
            'Session report',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [['key' => 'learners', 'label' => 'Learners', 'value' => $total]],
            [
                ['key' => 'fullName', 'label' => 'Learner', 'type' => 'text'],
                ['key' => 'username', 'label' => 'Username', 'type' => 'text'],
                ['key' => 'courseAccesses', 'label' => 'Number of accesses', 'type' => 'number'],
                ['key' => 'totalTime', 'label' => 'Total time', 'type' => 'duration'],
                ['key' => 'learningPathProgress', 'label' => 'Learning path progress', 'type' => 'percent'],
                ['key' => 'exerciseAverage', 'label' => 'Average score', 'type' => 'percent'],
            ],
            $pageRows
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getMessages(CourseReportingContext $context, array $filters): array
    {
        $pagination = $this->pagination($filters);
        if (!$context->allowMessageTracking) {
            throw new AccessDeniedHttpException('Message tracking is disabled.');
        }
        if (!$context->isAdministrator && !$context->isTeacher && !$context->isHumanResourcesManager) {
            throw new AccessDeniedHttpException('You are not allowed to track messages.');
        }
        if (!$this->tableExists('message')) {
            return $this->emptyResult('messages', 'Message report', $pagination, 'Message table is unavailable.');
        }

        $messageUsers = $this->getLearners($context);
        $userOptions = array_map(
            static fn (array $row): array => [
                'label' => (string) $row['fullName'].' ('.(string) $row['username'].')',
                'value' => (int) $row['id'],
            ],
            $messageUsers
        );
        $userId = max(0, (int) ($filters['userId'] ?? 0));
        $peerUserId = max(0, (int) ($filters['peerUserId'] ?? 0));
        if (0 === $userId || 0 === $peerUserId) {
            return $this->result(
                'messages',
                'Message report',
                0,
                1,
                $pagination['itemsPerPage'],
                [],
                [],
                [],
                [],
                [
                    'requiresUsers' => true,
                    'users' => $userOptions,
                    'limitations' => ['Select two learners from this course context to load their conversation.'],
                ]
            );
        }

        $allowedIds = array_fill_keys(array_map(
            static fn (array $row): int => (int) $row['id'],
            $messageUsers
        ), true);
        if (!isset($allowedIds[$userId]) || !isset($allowedIds[$peerUserId])) {
            throw new AccessDeniedHttpException('The selected users are outside this course context.');
        }

        $senderColumn = $this->columnExists('message', 'user_sender_id') ? 'user_sender_id' : 'sender_id';
        $receiverColumn = $this->columnExists('message', 'user_receiver_id') ? 'user_receiver_id' : 'receiver_id';
        $dateColumn = $this->columnExists('message', 'send_date') ? 'send_date' : 'created_at';
        $titleColumn = $this->columnExists('message', 'title') ? 'title' : "''";
        $contentColumn = $this->columnExists('message', 'content') ? 'content' : "''";
        $where = '(('.$senderColumn.' = :userId AND '.$receiverColumn.' = :peerUserId)
                OR ('.$senderColumn.' = :peerUserId AND '.$receiverColumn.' = :userId))';
        $params = ['userId' => $userId, 'peerUserId' => $peerUserId];
        $total = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM message WHERE '.$where, $params);
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id,
                    '.$senderColumn.' AS senderId,
                    '.$receiverColumn.' AS receiverId,
                    '.$titleColumn.' AS title,
                    '.$contentColumn.' AS content,
                    '.$dateColumn.' AS sendDate
               FROM message
              WHERE '.$where.'
           ORDER BY '.$dateColumn.' DESC
              LIMIT :limit OFFSET :offset',
            $params + [
                'limit' => $pagination['itemsPerPage'],
                'offset' => $pagination['offset'],
            ],
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );
        foreach ($rows as &$row) {
            $row['direction'] = (int) $row['senderId'] === $userId ? 'sent' : 'received';
            $row['content'] = trim(strip_tags((string) $row['content']));
        }
        unset($row);

        return $this->result(
            'messages',
            'Message report',
            $total,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [['key' => 'messages', 'label' => 'Messages', 'value' => $total]],
            [
                ['key' => 'sendDate', 'label' => 'Date', 'type' => 'datetime'],
                ['key' => 'direction', 'label' => 'Direction', 'type' => 'text'],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'content', 'label' => 'Message', 'type' => 'text'],
            ],
            $rows,
            [],
            ['userId' => $userId, 'peerUserId' => $peerUserId, 'users' => $userOptions]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLearners(CourseReportingContext $context): array
    {
        $params = [
            'courseId' => $context->courseId(),
            'studentStatus' => self::STUDENT_STATUS,
            'accessSessionId' => $context->sessionId(),
        ];

        $firstAccessExpression = $this->tableExists('track_e_course_access')
            ? '(SELECT MIN(access_log.login_course_date)
                  FROM track_e_course_access access_log
                 WHERE access_log.c_id = :courseId
                   AND COALESCE(access_log.session_id, 0) = :accessSessionId
                   AND access_log.user_id = user.id)'
            : 'NULL';
        $lastAccessExpression = $this->tableExists('track_e_course_access')
            ? '(SELECT MAX(access_log.login_course_date)
                  FROM track_e_course_access access_log
                 WHERE access_log.c_id = :courseId
                   AND COALESCE(access_log.session_id, 0) = :accessSessionId
                   AND access_log.user_id = user.id)'
            : 'NULL';

        $select = 'SELECT DISTINCT user.id,
                           user.official_code AS officialCode,
                           user.firstname AS firstName,
                           user.lastname AS lastName,
                           user.username,
                           user.email,
                           CONCAT_WS(\' \', user.firstname, user.lastname) AS fullName,
                           '.$firstAccessExpression.' AS firstAccess,
                           '.$lastAccessExpression.' AS lastAccess';

        if ($context->sessionId() > 0) {
            $sql = $select.'
                      FROM session_rel_course_rel_user relation
                      INNER JOIN user ON user.id = relation.user_id
                     WHERE relation.c_id = :courseId
                       AND relation.session_id = :sessionId
                       AND relation.status = :studentStatus';
            $params['sessionId'] = $context->sessionId();
        } else {
            $sql = $select.'
                      FROM course_rel_user relation
                      INNER JOIN user ON user.id = relation.user_id
                     WHERE relation.c_id = :courseId
                       AND relation.status = :studentStatus';
        }

        if ($context->groupId > 0 && $this->tableExists('c_group_rel_user')) {
            $sql .= ' AND EXISTS (
                SELECT 1
                  FROM c_group_rel_user group_user
                 WHERE group_user.user_id = user.id
                   AND group_user.group_id = :groupId
                   AND group_user.c_id = :courseId
            )';
            $params['groupId'] = $context->groupId;
        }

        $sql .= ' ORDER BY user.lastname ASC, user.firstname ASC, user.username ASC';

        $rows = $this->connection->fetchAllAssociative($sql, $params);
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['officialCode'] = (string) ($row['officialCode'] ?? '');
            $row['firstName'] = (string) ($row['firstName'] ?? '');
            $row['lastName'] = (string) ($row['lastName'] ?? '');
            $row['email'] = (string) ($row['email'] ?? '');
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $learners
     * @param array<string, mixed>             $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterLearners(array $learners, array $filters): array
    {
        $keyword = mb_strtolower(trim((string) ($filters['keyword'] ?? '')));
        if ('' === $keyword) {
            return $learners;
        }

        return array_values(array_filter(
            $learners,
            static fn (array $row): bool => str_contains(
                mb_strtolower(
                    (string) $row['fullName'].' '
                    .(string) $row['username'].' '
                    .(string) ($row['officialCode'] ?? '').' '
                    .(string) ($row['email'] ?? '')
                ),
                $keyword
            )
        ));
    }

    /**
     * @param int[] $learnerIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function getConnectedLearners(
        CourseReportingContext $context,
        array $learnerIds,
        DateTimeImmutable $startDate
    ): array {
        if ([] === $learnerIds || !$this->tableExists('track_e_course_access')) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($learnerIds), '?'));
        $rows = $this->connection->fetchAllAssociative(
            'SELECT user.id,
                    user.username,
                    CONCAT_WS(\' \', user.firstname, user.lastname) AS fullName,
                    MIN(access_log.login_course_date) AS firstAccess,
                    MAX(access_log.login_course_date) AS lastAccess,
                    COUNT(*) AS accesses,
                    COALESCE(SUM(
                        CASE
                            WHEN access_log.logout_course_date IS NOT NULL
                            THEN GREATEST(
                                TIMESTAMPDIFF(
                                    SECOND,
                                    access_log.login_course_date,
                                    access_log.logout_course_date
                                ),
                                0
                            )
                            ELSE 0
                        END
                    ), 0) AS totalTime
               FROM track_e_course_access access_log
               INNER JOIN user ON user.id = access_log.user_id
              WHERE access_log.c_id = ?
                AND COALESCE(access_log.session_id, 0) = ?
                AND access_log.login_course_date >= ?
                AND access_log.user_id IN ('.$placeholders.')
           GROUP BY user.id, user.username, user.firstname, user.lastname
           ORDER BY lastAccess DESC',
            [$context->courseId(), $context->sessionId(), $startDate->format('Y-m-d H:i:s'), ...$learnerIds]
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['accesses'] = (int) $row['accesses'];
            $row['totalTime'] = (int) $row['totalTime'];
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function toolMetric(string $title, string $table, CourseReportingContext $context): array
    {
        if (!$this->tableExists($table)) {
            return ['id' => $table, 'title' => $title, 'total' => 0, 'available' => false];
        }

        $total = 0;
        if ($this->columnExists($table, 'resource_node_id') && $this->tableExists('resource_link')) {
            $sessionSql = $context->sessionId() > 0
                ? 'AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
                : 'AND (rl.session_id IS NULL OR rl.session_id = 0)';
            $params = ['courseId' => $context->courseId()];
            if ($context->sessionId() > 0) {
                $params['sessionId'] = $context->sessionId();
            }
            $total = (int) $this->connection->fetchOne(
                'SELECT COUNT(DISTINCT item.resource_node_id)
                   FROM '.$table.' item
                   INNER JOIN resource_link rl ON rl.resource_node_id = item.resource_node_id
                  WHERE rl.c_id = :courseId
                    AND rl.deleted_at IS NULL '.$sessionSql,
                $params
            );
        } elseif ($this->columnExists($table, 'c_id')) {
            $total = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM '.$table.' WHERE c_id = :courseId',
                ['courseId' => $context->courseId()]
            );
        }

        return ['id' => $table, 'title' => $title, 'total' => $total, 'available' => true];
    }

    /**
     * @return array<string, int|float>
     */
    private function exerciseStats(CourseReportingContext $context, int $exerciseId): array
    {
        if (!$this->tableExists('track_e_exercises')) {
            return ['learners' => 0, 'attempts' => 0, 'averageScore' => 0.0, 'bestScore' => 0.0];
        }

        $row = $this->connection->fetchAssociative(
            'SELECT COUNT(*) AS attempts,
                    COUNT(DISTINCT exe_user_id) AS learners,
                    ROUND(AVG(CASE WHEN max_score > 0 THEN score * 100 / max_score ELSE 0 END), 2) AS averageScore,
                    ROUND(MAX(CASE WHEN max_score > 0 THEN score * 100 / max_score ELSE 0 END), 2) AS bestScore
               FROM track_e_exercises
              WHERE c_id = :courseId
                AND COALESCE(session_id, 0) = :sessionId
                AND exe_exo_id = :exerciseId',
            [
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
                'exerciseId' => $exerciseId,
            ]
        ) ?: [];

        return [
            'learners' => (int) ($row['learners'] ?? 0),
            'attempts' => (int) ($row['attempts'] ?? 0),
            'averageScore' => (float) ($row['averageScore'] ?? 0),
            'bestScore' => (float) ($row['bestScore'] ?? 0),
        ];
    }

    /**
     * @return array<string, int|float|string|null>
     */
    private function learningPathStats(CourseReportingContext $context, int $learningPathId): array
    {
        if (!$this->tableExists('c_lp_view')) {
            return [
                'learners' => 0,
                'averageProgress' => 0.0,
                'averageScore' => 0.0,
                'totalTime' => 0,
                'firstAccess' => null,
                'lastAccess' => null,
            ];
        }

        $completionExpression = $this->columnExists('c_lp_view', 'completion_date')
            ? 'MAX(completion_date)'
            : 'NULL';
        $row = $this->connection->fetchAssociative(
            'SELECT COUNT(DISTINCT user_id) AS learners,
                    ROUND(AVG(progress), 2) AS averageProgress,
                    '.$completionExpression.' AS lastAccess
               FROM c_lp_view
              WHERE c_id = :courseId
                AND COALESCE(session_id, 0) = :sessionId
                AND lp_id = :learningPathId',
            [
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
                'learningPathId' => $learningPathId,
            ]
        ) ?: [];

        $averageScore = 0.0;
        $totalTime = 0;
        $firstAccess = null;
        if ($this->tableExists('c_lp_item_view')) {
            $select = [];
            if (
                $this->columnExists('c_lp_item_view', 'score')
                && $this->columnExists('c_lp_item_view', 'max_score')
            ) {
                $select[] = 'COALESCE(100 * SUM(item_view.score) / NULLIF(SUM(item_view.max_score), 0), 0) AS averageScore';
            } else {
                $select[] = '0 AS averageScore';
            }
            $select[] = $this->columnExists('c_lp_item_view', 'total_time')
                ? 'COALESCE(SUM(item_view.total_time), 0) AS totalTime'
                : '0 AS totalTime';
            $select[] = $this->columnExists('c_lp_item_view', 'start_time')
                ? 'MIN(CASE WHEN item_view.start_time > 0 THEN FROM_UNIXTIME(item_view.start_time) END) AS firstAccess'
                : 'NULL AS firstAccess';

            $itemRow = $this->connection->fetchAssociative(
                'SELECT '.implode(', ', $select).'
                   FROM c_lp_item_view item_view
                   INNER JOIN c_lp_view view ON view.iid = item_view.lp_view_id
                  WHERE view.c_id = :courseId
                    AND COALESCE(view.session_id, 0) = :sessionId
                    AND view.lp_id = :learningPathId',
                [
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                    'learningPathId' => $learningPathId,
                ]
            ) ?: [];
            $averageScore = (float) ($itemRow['averageScore'] ?? 0);
            $totalTime = (int) ($itemRow['totalTime'] ?? 0);
            $firstAccess = $itemRow['firstAccess'] ?? null;
        }

        return [
            'learners' => (int) ($row['learners'] ?? 0),
            'averageProgress' => (float) ($row['averageProgress'] ?? 0),
            'averageScore' => round($averageScore, 2),
            'totalTime' => $totalTime,
            'firstAccess' => $firstAccess,
            'lastAccess' => $row['lastAccess'] ?? null,
        ];
    }

    private function getGroupLearningPathProgress(CourseReportingContext $context, int $groupId): float
    {
        $userIds = $this->getGroupUserIds($context, $groupId);
        $learningPathIds = $this->getCourseLearningPathIds($context);
        if ([] === $userIds || [] === $learningPathIds || !$this->tableExists('c_lp_view')) {
            return 0.0;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT view.user_id, view.lp_id, view.progress, view.view_count, view.iid
               FROM c_lp_view view
              WHERE view.c_id = :courseId
                AND COALESCE(view.session_id, 0) = :sessionId
                AND view.user_id IN (:userIds)
                AND view.lp_id IN (:learningPathIds)
           ORDER BY view.user_id ASC,
                    view.lp_id ASC,
                    view.view_count DESC,
                    view.iid DESC',
            [
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
                'userIds' => $userIds,
                'learningPathIds' => $learningPathIds,
            ],
            [
                'userIds' => ArrayParameterType::INTEGER,
                'learningPathIds' => ArrayParameterType::INTEGER,
            ]
        );

        $latestProgress = [];
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $learningPathId = (int) $row['lp_id'];
            if (isset($latestProgress[$userId][$learningPathId])) {
                continue;
            }

            $latestProgress[$userId][$learningPathId] = max(0.0, min(100.0, (float) $row['progress']));
        }

        $userProgressValues = [];
        $learningPathCount = \count($learningPathIds);
        foreach ($userIds as $userId) {
            $progressValues = array_values($latestProgress[$userId] ?? []);
            $userProgressValues[] = $context->useMaximumLearningPathProgress
                ? ($progressValues ? max($progressValues) : 0.0)
                : ($learningPathCount > 0 ? array_sum($progressValues) / $learningPathCount : 0.0);
        }

        return $userProgressValues
            ? array_sum($userProgressValues) / \count($userProgressValues)
            : 0.0;
    }

    private function getGroupLearningPathScore(CourseReportingContext $context, int $groupId): float
    {
        $userIds = $this->getGroupUserIds($context, $groupId);
        $learningPathIds = $this->getCourseLearningPathIds($context);
        if (
            [] === $userIds
            || [] === $learningPathIds
            || !$this->tableExists('c_lp_view')
            || !$this->tableExists('c_lp_item_view')
        ) {
            return 0.0;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT view.user_id,
                    view.lp_id,
                    view.iid AS lpViewId,
                    view.view_count AS lpViewCount,
                    item_view.lp_item_id AS itemId,
                    item_view.iid AS itemViewId,
                    item_view.view_count AS itemViewCount,
                    item_view.score,
                    item_view.max_score AS maxScore,
                    item_view.status
               FROM c_lp_view view
               INNER JOIN c_lp_item_view item_view ON item_view.lp_view_id = view.iid
              WHERE view.c_id = :courseId
                AND COALESCE(view.session_id, 0) = :sessionId
                AND view.user_id IN (:userIds)
                AND view.lp_id IN (:learningPathIds)
           ORDER BY view.user_id ASC,
                    view.lp_id ASC,
                    view.view_count DESC,
                    view.iid DESC,
                    item_view.lp_item_id ASC,
                    item_view.view_count DESC,
                    item_view.iid DESC',
            [
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
                'userIds' => $userIds,
                'learningPathIds' => $learningPathIds,
            ],
            [
                'userIds' => ArrayParameterType::INTEGER,
                'learningPathIds' => ArrayParameterType::INTEGER,
            ]
        );

        $latestLearningPathView = [];
        $latestItems = [];
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $learningPathId = (int) $row['lp_id'];
            $learningPathViewId = (int) $row['lpViewId'];
            $itemId = (int) $row['itemId'];
            $maxScore = is_numeric($row['maxScore']) ? (float) $row['maxScore'] : 0.0;
            if ($maxScore <= 0.0 || 'not attempted' === (string) $row['status']) {
                continue;
            }

            if (!isset($latestLearningPathView[$userId][$learningPathId])) {
                $latestLearningPathView[$userId][$learningPathId] = $learningPathViewId;
            }
            if (
                $latestLearningPathView[$userId][$learningPathId] !== $learningPathViewId
                || isset($latestItems[$userId][$learningPathId][$itemId])
            ) {
                continue;
            }

            $latestItems[$userId][$learningPathId][$itemId] = max(
                0.0,
                min(1.0, (float) $row['score'] / $maxScore)
            );
        }

        $userScores = [];
        foreach ($userIds as $userId) {
            $ratios = [];
            foreach ($latestItems[$userId] ?? [] as $itemRatios) {
                array_push($ratios, ...array_values($itemRatios));
            }
            $userScores[] = $ratios ? 100 * array_sum($ratios) / \count($ratios) : 0.0;
        }

        return $userScores ? array_sum($userScores) / \count($userScores) : 0.0;
    }

    /**
     * @return int[]
     */
    private function getGroupUserIds(CourseReportingContext $context, int $groupId): array
    {
        if (!$this->tableExists('c_group_rel_user')) {
            return [];
        }

        $ids = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT user_id
               FROM c_group_rel_user
              WHERE group_id = :groupId
                AND c_id = :courseId
              ORDER BY user_id ASC',
            [
                'groupId' => $groupId,
                'courseId' => $context->courseId(),
            ]
        );

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * @return int[]
     */
    private function getCourseLearningPathIds(CourseReportingContext $context): array
    {
        if (!$this->tableExists('c_lp') || !$this->tableExists('resource_link')) {
            return [];
        }

        $sessionSql = $context->sessionId() > 0
            ? '(rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
            : '(rl.session_id IS NULL OR rl.session_id = 0)';
        $params = ['courseId' => $context->courseId()];
        if ($context->sessionId() > 0) {
            $params['sessionId'] = $context->sessionId();
        }

        $ids = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT lp.iid
               FROM c_lp lp
               INNER JOIN resource_link rl ON rl.resource_node_id = lp.resource_node_id
              WHERE rl.c_id = :courseId
                AND rl.deleted_at IS NULL
                AND '.$sessionSql.'
           ORDER BY lp.iid ASC',
            $params
        );

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function getGroupTime(CourseReportingContext $context, int $groupId): int
    {
        if (!$this->tableExists('c_group_rel_user') || !$this->tableExists('track_e_course_access')) {
            return 0;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COALESCE(SUM(
                    CASE
                        WHEN access_log.logout_course_date IS NOT NULL
                        THEN GREATEST(TIMESTAMPDIFF(SECOND, access_log.login_course_date, access_log.logout_course_date), 0)
                        ELSE 0
                    END
                ), 0)
               FROM track_e_course_access access_log
               INNER JOIN c_group_rel_user group_user ON group_user.user_id = access_log.user_id
              WHERE group_user.group_id = :groupId
                AND access_log.c_id = :courseId
                AND COALESCE(access_log.session_id, 0) = :sessionId',
            [
                'groupId' => $groupId,
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
            ]
        );
    }

    private function getGroupPublicationCount(CourseReportingContext $context, int $groupId): int
    {
        if (
            !$this->tableExists('c_group_rel_user')
            || !$this->tableExists('c_student_publication')
            || !$this->tableExists('resource_link')
        ) {
            return 0;
        }

        $sessionCondition = $context->sessionId() > 0
            ? '(rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
            : '(rl.session_id IS NULL OR rl.session_id = 0)';
        $params = [
            'groupId' => $groupId,
            'courseId' => $context->courseId(),
        ];
        if ($context->sessionId() > 0) {
            $params['sessionId'] = $context->sessionId();
        }

        $parentCondition = $this->columnExists('c_student_publication', 'parent_id')
            ? 'AND publication.parent_id IS NOT NULL AND publication.parent_id > 0'
            : '';

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT publication.iid)
               FROM c_student_publication publication
               INNER JOIN c_group_rel_user group_user
                       ON group_user.user_id = publication.user_id
                      AND group_user.group_id = :groupId
                      AND group_user.c_id = :courseId
               INNER JOIN resource_link rl ON rl.resource_node_id = publication.resource_node_id
              WHERE rl.c_id = :courseId
                AND rl.deleted_at IS NULL
                AND '.$sessionCondition.'
                '.$parentCondition,
            $params
        );
    }

    private function getGroupForumPostCount(CourseReportingContext $context, int $groupId): int
    {
        if (
            !$this->tableExists('c_group_rel_user')
            || !$this->tableExists('c_forum_post')
            || !$this->tableExists('resource_link')
        ) {
            return 0;
        }

        $sessionCondition = $context->sessionId() > 0
            ? '(rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
            : '(rl.session_id IS NULL OR rl.session_id = 0)';
        $params = [
            'groupId' => $groupId,
            'courseId' => $context->courseId(),
        ];
        if ($context->sessionId() > 0) {
            $params['sessionId'] = $context->sessionId();
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT post.iid)
               FROM c_forum_post post
               INNER JOIN c_group_rel_user group_user
                       ON group_user.user_id = post.poster_id
                      AND group_user.group_id = :groupId
                      AND group_user.c_id = :courseId
               INNER JOIN resource_link rl ON rl.resource_node_id = post.resource_node_id
              WHERE rl.c_id = :courseId
                AND rl.deleted_at IS NULL
                AND '.$sessionCondition,
            $params
        );
    }

    private function normalizeAuditValue(string $value): string
    {
        $normalized = trim(strip_tags($value));
        if (mb_strlen($normalized) > 2000) {
            return mb_substr($normalized, 0, 1997).'...';
        }

        return $normalized;
    }

    /**
     * @param int[] $userIds
     */
    private function getCourseTimeForLearners(CourseReportingContext $context, array $userIds): int
    {
        if ([] === $userIds || !$this->tableExists('track_e_course_access')) {
            return 0;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COALESCE(SUM(
                    CASE
                        WHEN logout_course_date IS NOT NULL
                        THEN GREATEST(
                            TIMESTAMPDIFF(SECOND, login_course_date, logout_course_date),
                            0
                        )
                        ELSE 0
                    END
                ), 0)
               FROM track_e_course_access
              WHERE c_id = :courseId
                AND COALESCE(session_id, 0) = :sessionId
                AND user_id IN (:userIds)',
            [
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
                'userIds' => $userIds,
            ],
            ['userIds' => ArrayParameterType::INTEGER]
        );
    }

    private function getLearnerCourseTime(CourseReportingContext $context, int $userId): int
    {
        if (!$this->tableExists('track_e_course_access')) {
            return 0;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COALESCE(SUM(
                    CASE
                        WHEN logout_course_date IS NOT NULL
                        THEN GREATEST(TIMESTAMPDIFF(SECOND, login_course_date, logout_course_date), 0)
                        ELSE 0
                    END
                ), 0)
               FROM track_e_course_access
              WHERE c_id = :courseId
                AND COALESCE(session_id, 0) = :sessionId
                AND user_id = :userId',
            [
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
                'userId' => $userId,
            ]
        );
    }

    private function getLearnerLearningPathTime(CourseReportingContext $context, int $userId): int
    {
        if ($this->tableExists('c_lp_item_view') && $this->columnExists('c_lp_item_view', 'total_time')) {
            return (int) $this->connection->fetchOne(
                'SELECT COALESCE(SUM(item_view.total_time), 0)
                   FROM c_lp_item_view item_view
                   INNER JOIN c_lp_view view ON view.iid = item_view.lp_view_id
                  WHERE view.c_id = :courseId
                    AND COALESCE(view.session_id, 0) = :sessionId
                    AND view.user_id = :userId',
                [
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                    'userId' => $userId,
                ]
            );
        }
        if ($this->tableExists('c_lp_view') && $this->columnExists('c_lp_view', 'total_time')) {
            return (int) $this->connection->fetchOne(
                'SELECT COALESCE(SUM(total_time), 0)
                   FROM c_lp_view
                  WHERE c_id = :courseId
                    AND COALESCE(session_id, 0) = :sessionId
                    AND user_id = :userId',
                [
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                    'userId' => $userId,
                ]
            );
        }

        return 0;
    }

    private function getLearnerAccessCount(CourseReportingContext $context, int $userId): int
    {
        if (!$this->tableExists('track_e_course_access')) {
            return 0;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
               FROM track_e_course_access
              WHERE c_id = :courseId
                AND COALESCE(session_id, 0) = :sessionId
                AND user_id = :userId',
            [
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
                'userId' => $userId,
            ]
        );
    }

    private function getLearnerLearningPathProgress(CourseReportingContext $context, int $userId): float
    {
        $learningPathIds = $this->getCourseLearningPathIds($context);
        if ([] === $learningPathIds || !$this->tableExists('c_lp_view')) {
            return 0.0;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT lp_id, progress
               FROM c_lp_view
              WHERE c_id = :courseId
                AND COALESCE(session_id, 0) = :sessionId
                AND user_id = :userId
                AND lp_id IN (:learningPathIds)
           ORDER BY lp_id ASC, view_count DESC, iid DESC',
            [
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
                'userId' => $userId,
                'learningPathIds' => $learningPathIds,
            ],
            ['learningPathIds' => ArrayParameterType::INTEGER]
        );

        $latestProgress = [];
        foreach ($rows as $row) {
            $learningPathId = (int) $row['lp_id'];
            if (!isset($latestProgress[$learningPathId])) {
                $latestProgress[$learningPathId] = max(0.0, min(100.0, (float) $row['progress']));
            }
        }

        $values = array_values($latestProgress);
        if ($context->useMaximumLearningPathProgress) {
            return $values ? max($values) : 0.0;
        }

        return array_sum($values) / \count($learningPathIds);
    }

    private function getLearnerExerciseAverage(CourseReportingContext $context, int $userId): float
    {
        if (!$this->tableExists('track_e_exercises')) {
            return 0.0;
        }

        return (float) $this->connection->fetchOne(
            'SELECT COALESCE(AVG(CASE WHEN max_score > 0 THEN score * 100 / max_score ELSE 0 END), 0)
               FROM track_e_exercises
              WHERE c_id = :courseId
                AND COALESCE(session_id, 0) = :sessionId
                AND exe_user_id = :userId',
            [
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
                'userId' => $userId,
            ]
        );
    }

    /**
     * @return array{page: int, itemsPerPage: int, offset: int}
     */
    private function pagination(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $itemsPerPage = min(self::MAX_ITEMS_PER_PAGE, max(1, (int) ($filters['itemsPerPage'] ?? 20)));

        return [
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'offset' => ($page - 1) * $itemsPerPage,
        ];
    }

    private function resolveStartDate(array $filters, string $fallback): DateTimeImmutable
    {
        $value = trim((string) ($filters['startDate'] ?? ''));
        if ('' !== $value) {
            $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value.' 00:00:00');
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        return new DateTimeImmutable($fallback);
    }

    private function tableExists(string $table): bool
    {
        if (!isset($this->tableCache[$table])) {
            $this->tableCache[$table] = $this->connection->createSchemaManager()->tablesExist([$table]);
        }

        return $this->tableCache[$table];
    }

    private function columnExists(string $table, string $column): bool
    {
        if (!isset($this->columnCache[$table])) {
            $this->columnCache[$table] = [];
            if ($this->tableExists($table)) {
                foreach (array_keys($this->connection->createSchemaManager()->listTableColumns($table)) as $name) {
                    $this->columnCache[$table][strtolower((string) $name)] = true;
                }
            }
        }

        return isset($this->columnCache[$table][strtolower($column)]);
    }

    /**
     * @param array{page: int, itemsPerPage: int, offset: int} $pagination
     * @param array<string, mixed>                             $meta
     *
     * @return array<string, mixed>
     */
    private function emptyResult(
        string $section,
        string $title,
        array $pagination,
        string $limitation,
        array $meta = []
    ): array {
        $meta['limitations'] = [$limitation];

        return $this->result(
            $section,
            $title,
            0,
            $pagination['page'],
            $pagination['itemsPerPage'],
            [],
            [],
            [],
            [],
            $meta
        );
    }

    /**
     * @param array<int, array<string, mixed>> $summary
     * @param array<int, array<string, mixed>> $columns
     * @param array<int, array<string, mixed>> $items
     * @param array<int, array<string, mixed>> $sections
     * @param array<string, mixed>             $meta
     *
     * @return array<string, mixed>
     */
    private function result(
        string $section,
        string $title,
        int $total,
        int $page,
        int $itemsPerPage,
        array $summary,
        array $columns,
        array $items,
        array $sections = [],
        array $meta = []
    ): array {
        return [
            'section' => $section,
            'title' => $title,
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'summary' => $summary,
            'columns' => $columns,
            'items' => $items,
            'sections' => $sections,
            'meta' => $meta,
        ];
    }
}
