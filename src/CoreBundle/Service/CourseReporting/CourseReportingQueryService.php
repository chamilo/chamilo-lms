<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseReporting;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CourseReportingQueryService
{
    private const int COURSE_TEACHER_STATUS = 1;
    private const int COURSE_STUDENT_STATUS = 5;
    private const int SESSION_STUDENT_STATUS = 0;
    private const int SESSION_COURSE_COACH_STATUS = 2;
    private const int HUMAN_RESOURCES_RELATION_TYPE = 1;

    /**
     * @var null|array<int, string>
     */
    private ?array $tableNames = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly ExtraFieldRepository $extraFieldRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getConfiguration(CourseReportingContext $context): array
    {
        $courseId = $context->courseId();
        $sessionId = $context->sessionId();

        $groups = [];
        if ($this->hasTables(['c_group_info', 'resource_link'])) {
            $sessionSql = $sessionId > 0
                ? 'AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
                : 'AND (rl.session_id IS NULL OR rl.session_id = 0)';
            $parameters = ['courseId' => $courseId];
            if ($sessionId > 0) {
                $parameters['sessionId'] = $sessionId;
            }

            $groups = $this->connection->fetchAllAssociative(
                "SELECT DISTINCT group_info.iid AS id, group_info.title
                   FROM c_group_info group_info
                   INNER JOIN resource_link rl
                       ON rl.resource_node_id = group_info.resource_node_id
                  WHERE rl.c_id = :courseId
                    AND rl.deleted_at IS NULL
                    $sessionSql
                  ORDER BY group_info.title ASC",
                $parameters
            );
        }

        $classes = [];
        if ($this->hasTables(['usergroup', 'usergroup_rel_course'])) {
            $classes = $this->connection->fetchAllAssociative(
                'SELECT DISTINCT usergroup.id, usergroup.title
                   FROM usergroup
                   INNER JOIN usergroup_rel_course relation
                       ON relation.usergroup_id = usergroup.id
                  WHERE relation.course_id = :courseId
                  ORDER BY usergroup.title ASC',
                ['courseId' => $courseId]
            );
        }

        $teachers = $this->getTeachers($context);
        $sessions = $context->hideSessionList ? [] : $this->getSessions($context);
        $configuredExercises = $this->getConfiguredExercises($context);
        $extraFields = [];

        foreach ($this->extraFieldRepository->getExtraFields(ExtraField::USER_FIELD_TYPE) as $field) {
            $extraFields[] = [
                'id' => (int) $field->getId(),
                'variable' => $field->getVariable(),
                'label' => (string) ($field->getDisplayText() ?: $field->getVariable()),
                'valueType' => $field->getValueType(),
                'filterable' => $field->isFilter(),
            ];
        }

        return [
            'courseId' => $courseId,
            'courseResourceNodeId' => $context->courseResourceNodeId(),
            'courseCode' => $context->course->getCode(),
            'courseTitle' => $context->course->getTitle(),
            'sessionId' => $sessionId,
            'sessionTitle' => $context->session?->getTitle() ?? '',
            'groupId' => $context->groupId,
            'currentUserId' => (int) $context->currentUser->getId(),
            'allowMessageTracking' => $context->allowMessageTracking,
            'showEmailAddresses' => $context->showEmailAddresses,
            'showCharts' => !$context->hideCharts,
            'groups' => array_map(
                static fn (array $row): array => [
                    'label' => (string) $row['title'],
                    'value' => 'group_'.(int) $row['id'],
                ],
                $groups
            ),
            'classes' => array_map(
                static fn (array $row): array => [
                    'label' => (string) $row['title'],
                    'value' => 'class_'.(int) $row['id'],
                ],
                $classes
            ),
            'teachers' => $teachers,
            'sessions' => $sessions,
            'extraFields' => $extraFields,
            'configuredExercises' => $configuredExercises,
            'hiddenColumnIndexes' => $context->hiddenColumnIndexes,
            'defaultExtraFieldVariables' => $context->defaultExtraFieldVariables,
            'inactiveDayOptions' => [2, 3, 4, 5, 6, 7, 15, 30],
            'tabs' => [
                ['key' => 'learners', 'label' => 'Report on learners', 'icon' => 'account', 'enabled' => true],
                ['key' => 'activity', 'label' => 'Course activity statistics', 'icon' => 'chart-box', 'enabled' => true],
                ['key' => 'groups', 'label' => 'Group reporting', 'icon' => 'account-group', 'enabled' => true],
                ['key' => 'resources', 'label' => 'Report on resources', 'icon' => 'chart-timeline-variant', 'enabled' => true],
                ['key' => 'course', 'label' => 'Course report', 'icon' => 'book-open-page-variant', 'enabled' => true],
                ['key' => 'exams', 'label' => 'Exam tracking', 'icon' => 'format-list-checks', 'enabled' => true],
                ['key' => 'audit', 'label' => 'Audit report', 'icon' => 'shield-check', 'enabled' => true],
                ['key' => 'learning-paths', 'label' => 'Learning paths generic stats', 'icon' => 'vector-polyline', 'enabled' => true],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverview(CourseReportingContext $context): array
    {
        $participants = $this->fetchParticipantRows($context, [
            'page' => 1,
            'itemsPerPage' => 100000,
            'keyword' => '',
            'groupFilter' => '',
            'showTeachers' => false,
            'showActiveUsers' => false,
            'sort' => 'lastname',
            'direction' => 'ASC',
        ]);
        $rows = $participants['items'];
        $userIds = array_values(array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        ));
        $metrics = $this->loadLearnerMetrics($context, $userIds);

        $completedLearningPaths = 0;
        $exerciseAverageTotal = 0.0;
        $certificateCount = 0;
        $scoreDistribution = array_fill(0, 10, 0);
        $topStudents = [];
        $timeStudents = [];

        foreach ($rows as $row) {
            $userId = (int) $row['id'];
            $userMetrics = $metrics[$userId] ?? $this->emptyMetrics();
            $learningPathProgress = (float) $userMetrics['learningPathProgress'];
            $exerciseAverage = (float) $userMetrics['exerciseAverage'];

            if ($learningPathProgress >= 100.0) {
                $completedLearningPaths++;
            }

            $exerciseAverageTotal += $exerciseAverage;
            if ($userMetrics['certificateAvailable']) {
                $certificateCount++;
            }

            $distributionIndex = $exerciseAverage >= 100.0
                ? 9
                : max(0, min(9, (int) floor($exerciseAverage / 10)));
            $scoreDistribution[$distributionIndex]++;

            $combinedScore = (int) floor(($learningPathProgress + $exerciseAverage) / 2);
            $topStudents[] = [
                'id' => $userId,
                'fullName' => $this->fullName($row),
                'pictureUri' => (string) ($row['picture_uri'] ?? ''),
                'score' => $combinedScore,
            ];
            $timeStudents[] = [
                'id' => $userId,
                'fullName' => $this->fullName($row),
                'minutes' => (int) round(((int) $userMetrics['timeSeconds']) / 60),
            ];
        }

        usort(
            $topStudents,
            static fn (array $left, array $right): int => $right['score'] <=> $left['score']
                ?: strcasecmp($left['fullName'], $right['fullName'])
        );

        return [
            'numberStudents' => \count($rows),
            'completedLearningPaths' => $completedLearningPaths,
            'exerciseAverage' => \count($rows) > 0
                ? round($exerciseAverageTotal / \count($rows), 2)
                : 0.0,
            'certificateCount' => $certificateCount,
            'scoreDistribution' => array_values($scoreDistribution),
            'topStudents' => \array_slice($topStudents, 0, 5),
            'timeStudents' => $timeStudents,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{
     *     total: int,
     *     page: int,
     *     itemsPerPage: int,
     *     items: array<int, array<string, mixed>>,
     *     groupSummary: array<string, mixed>
     * }
     */
    public function getLearners(CourseReportingContext $context, array $filters): array
    {
        $result = $this->fetchParticipantRows($context, $filters);
        $userIds = array_values(array_map(
            static fn (array $row): int => (int) $row['id'],
            $result['items']
        ));
        $metrics = $this->loadLearnerMetrics($context, $userIds);
        $classes = $this->getUserClasses($userIds);
        $extraFieldIds = $this->normalizeIdList($filters['extraFieldIds'] ?? []);
        $extraValues = $this->getExtraFieldValues($userIds, $extraFieldIds);
        $courseCounts = $this->getCourseWideCounts($context);
        $surveyProgress = 0 === $context->sessionId()
            ? $this->getSurveyProgress($context, $userIds)
            : [];
        $registrationDates = $context->sessionId() > 0
            ? $this->getSessionRegistrationDates($context->sessionId(), $userIds)
            : [];

        $items = [];
        foreach ($result['items'] as $row) {
            $userId = (int) $row['id'];
            $userMetrics = $metrics[$userId] ?? $this->emptyMetrics();

            $items[] = [
                'id' => $userId,
                'officialCode' => (string) ($row['official_code'] ?? ''),
                'firstname' => (string) ($row['firstname'] ?? ''),
                'lastname' => (string) ($row['lastname'] ?? ''),
                'fullName' => $this->fullName($row),
                'username' => (string) ($row['username'] ?? ''),
                'email' => $context->showEmailAddresses ? (string) ($row['email'] ?? '') : '',
                'pictureUri' => (string) ($row['picture_uri'] ?? ''),
                'role' => (string) ($row['report_role'] ?? 'student'),
                'timeSeconds' => (int) $userMetrics['timeSeconds'],
                'learningPathProgress' => round((float) $userMetrics['learningPathProgress'], 2),
                'exerciseProgress' => round((float) $userMetrics['exerciseProgress'], 2),
                'exerciseAverage' => round((float) $userMetrics['exerciseAverage'], 2),
                'score' => round((float) $userMetrics['score'], 2),
                'bestScore' => round((float) $userMetrics['bestScore'], 2),
                'assignments' => $courseCounts['assignments'],
                'messages' => $courseCounts['messages'],
                'classes' => $classes[$userId] ?? [],
                'survey' => $surveyProgress[$userId] ?? '0 / 0',
                'registeredAt' => $registrationDates[$userId] ?? null,
                'firstAccess' => $userMetrics['firstAccess'],
                'latestAccess' => $userMetrics['latestAccess'],
                'learningPathFinalizationDate' => $userMetrics['learningPathFinalizationDate'],
                'quizFinalizationDate' => $userMetrics['quizFinalizationDate'],
                'certificateAvailable' => (bool) $userMetrics['certificateAvailable'],
                'configuredExerciseResults' => $userMetrics['configuredExerciseResults'],
                'extraFields' => $extraValues[$userId] ?? [],
            ];
        }

        return [
            'total' => $result['total'],
            'page' => $result['page'],
            'itemsPerPage' => $result['itemsPerPage'],
            'items' => $items,
            'groupSummary' => $this->buildLearnerGroupSummary($context, $filters),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function buildLearnerGroupSummary(
        CourseReportingContext $context,
        array $filters,
    ): array {
        $summaryFilters = $filters;
        $summaryFilters['page'] = 1;
        $summaryFilters['itemsPerPage'] = 100000;
        $summaryFilters['keyword'] = '';
        $summaryFilters['groupFilter'] = '';
        $summaryFilters['showTeachers'] = false;

        $participants = $this->fetchParticipantRows($context, $summaryFilters)['items'];
        $participantIds = array_values(array_map(
            static fn (array $participant): int => (int) $participant['id'],
            $participants
        ));
        $metrics = $this->loadLearnerMetrics($context, $participantIds);
        $items = [];
        $hasGroups = false;

        if (
            [] !== $participantIds
            && $this->hasTables(['c_group_info', 'c_group_rel_user', 'resource_link'])
        ) {
            $sessionCondition = $context->sessionId() > 0
                ? 'AND (resource_link.session_id IS NULL OR resource_link.session_id = 0 OR resource_link.session_id = :sessionId)'
                : 'AND (resource_link.session_id IS NULL OR resource_link.session_id = 0)';
            $parameters = [
                'courseId' => $context->courseId(),
                'participantIds' => $participantIds,
            ];
            if ($context->sessionId() > 0) {
                $parameters['sessionId'] = $context->sessionId();
            }

            $groupRows = $this->connection->fetchAllAssociative(
                "SELECT group_info.iid AS group_id,
                        group_info.title,
                        group_user.user_id
                   FROM c_group_info group_info
                   INNER JOIN resource_link resource_link
                           ON resource_link.resource_node_id = group_info.resource_node_id
                   LEFT JOIN c_group_rel_user group_user
                          ON group_user.group_id = group_info.iid
                         AND group_user.c_id = :courseId
                         AND group_user.user_id IN (:participantIds)
                  WHERE resource_link.c_id = :courseId
                    AND resource_link.deleted_at IS NULL
                    $sessionCondition
               ORDER BY group_info.title ASC, group_user.user_id ASC",
                $parameters,
                ['participantIds' => ArrayParameterType::INTEGER]
            );

            $groups = [];
            foreach ($groupRows as $groupRow) {
                $groupId = (int) $groupRow['group_id'];
                $groups[$groupId] ??= [
                    'title' => (string) $groupRow['title'],
                    'userIds' => [],
                ];
                if (null !== $groupRow['user_id']) {
                    $groups[$groupId]['userIds'][] = (int) $groupRow['user_id'];
                }
            }

            $hasGroups = [] !== $groups;
            foreach ($groups as $groupId => $group) {
                $groupUserIds = array_values(array_unique($group['userIds']));
                $items[] = $this->buildLearnerSummaryRow(
                    (string) $group['title'],
                    $groupUserIds,
                    $metrics,
                    'group',
                    $groupId,
                );
            }
        }

        if ($this->normalizeBoolean($filters['showActiveUsers'] ?? false)) {
            foreach ($participants as $participant) {
                if ('active' !== (string) ($participant['report_role'] ?? '')) {
                    continue;
                }

                $userId = (int) $participant['id'];
                $items[] = $this->buildLearnerSummaryRow(
                    $this->fullName($participant).' (free)',
                    [$userId],
                    $metrics,
                    'active-user',
                    $userId,
                );
            }
        }

        if ($hasGroups) {
            $totalLearners = array_sum(array_column($items, 'learners'));
            $totalTime = array_sum(array_column($items, 'timeSeconds'));
            $weightedProgress = 0.0;
            $weightedExerciseAverage = 0.0;
            foreach ($items as $item) {
                $weightedProgress += (float) $item['learningPathProgress'] * (int) $item['learners'];
                $weightedExerciseAverage += (float) $item['exerciseAverage'] * (int) $item['learners'];
            }

            $items[] = [
                'id' => 'total-0',
                'rowType' => 'total',
                'sourceId' => 0,
                'title' => 'Total',
                'learners' => $totalLearners,
                'timeSeconds' => $totalTime,
                'averageTimeSeconds' => $totalLearners > 0 ? (int) round($totalTime / $totalLearners) : 0,
                'learningPathProgress' => $totalLearners > 0
                    ? round($weightedProgress / $totalLearners, 2)
                    : 0.0,
                'exerciseAverage' => $totalLearners > 0
                    ? round($weightedExerciseAverage / $totalLearners, 2)
                    : 0.0,
            ];
        } else {
            $items[] = $this->buildLearnerSummaryRow(
                'Total',
                $participantIds,
                $metrics,
                'total',
                0,
            );
        }

        return [
            'columns' => [
                ['key' => 'title', 'label' => 'Name', 'type' => 'text'],
                ['key' => 'timeSeconds', 'label' => 'Time', 'type' => 'duration'],
                ['key' => 'averageTimeSeconds', 'label' => 'Average time in the course', 'type' => 'duration'],
                ['key' => 'learningPathProgress', 'label' => 'Course progress', 'type' => 'percent'],
                ['key' => 'exerciseAverage', 'label' => 'Exercise average', 'type' => 'percent'],
            ],
            'items' => $items,
        ];
    }

    /**
     * @param int[]                            $userIds
     * @param array<int, array<string, mixed>> $metrics
     *
     * @return array<string, mixed>
     */
    private function buildLearnerSummaryRow(
        string $title,
        array $userIds,
        array $metrics,
        string $rowType,
        int $sourceId,
    ): array {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $totalTime = 0;
        $progressTotal = 0.0;
        $exerciseTotal = 0.0;

        foreach ($userIds as $userId) {
            $userMetrics = $metrics[$userId] ?? $this->emptyMetrics();
            $totalTime += (int) $userMetrics['timeSeconds'];
            $progressTotal += (float) $userMetrics['learningPathProgress'];
            $exerciseTotal += (float) $userMetrics['bestScore'];
        }

        $count = \count($userIds);

        return [
            'id' => $rowType.'-'.$sourceId,
            'rowType' => $rowType,
            'sourceId' => $sourceId,
            'title' => $title,
            'learners' => $count,
            'timeSeconds' => $totalTime,
            'averageTimeSeconds' => $count > 0 ? (int) round($totalTime / $count) : 0,
            'learningPathProgress' => $count > 0 ? round($progressTotal / $count, 2) : 0.0,
            'exerciseAverage' => $count > 0 ? round($exerciseTotal / $count, 2) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLearnerDetail(
        CourseReportingContext $context,
        int $userId,
        int $limit = 200
    ): array {
        $limit = max(1, min(500, $limit));
        if (!$this->isUserInReportingScope($context, $userId)) {
            throw new NotFoundHttpException('Learner not found in this course reporting scope.');
        }

        $user = $this->connection->fetchAssociative(
            'SELECT id, official_code, firstname, lastname, username, email, picture_uri
               FROM `user`
              WHERE id = :userId',
            ['userId' => $userId]
        );

        if (false === $user) {
            throw new NotFoundHttpException('User not found.');
        }

        $resourceLinkSessionCondition = $context->sessionId() > 0
            ? 'AND resource_link.session_id = :sessionId'
            : 'AND (resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $sessionCondition = $context->sessionId() > 0
            ? 'AND rl.session_id = :sessionId'
            : 'AND (rl.session_id IS NULL OR rl.session_id = 0)';
        $parameters = [
            'userId' => $userId,
            'courseId' => $context->courseId(),
            'sessionId' => $context->sessionId(),
            'limit' => $limit,
        ];

        $downloads = [];
        if ($this->hasTables(['track_e_downloads', 'resource_link', 'resource_node', 'c_document'])) {
            $downloads = $this->connection->fetchAllAssociative(
                "SELECT
                    download.down_id,
                    download.down_date,
                    download.down_doc_path,
                    resource_link.id AS resource_link_id,
                    resource_node.id AS resource_node_id,
                    resource_node.title AS resource_title,
                    resource_node.path AS resource_path,
                    document.iid AS document_id,
                    document.title AS document_title,
                    document.filetype AS document_filetype
                FROM track_e_downloads download
                INNER JOIN resource_link
                    ON resource_link.id = download.resource_link_id
                INNER JOIN resource_node
                    ON resource_node.id = resource_link.resource_node_id
                LEFT JOIN c_document document
                    ON document.resource_node_id = resource_node.id
                WHERE download.down_user_id = :userId
                    AND resource_link.c_id = :courseId
                    $resourceLinkSessionCondition
                ORDER BY download.down_date DESC
                LIMIT :limit",
                $parameters,
                ['limit' => ParameterType::INTEGER]
            );
        }

        $forumPosts = [];
        if ($this->hasTables(['c_forum_post', 'c_forum_thread', 'c_forum', 'resource_link'])) {
            $postSessionCondition = $context->sessionId() > 0
                ? 'AND post_link.session_id = :sessionId'
                : 'AND (post_link.session_id IS NULL OR post_link.session_id = 0)';
            $threadSessionCondition = $context->sessionId() > 0
                ? 'AND thread_link.session_id = :sessionId'
                : 'AND (thread_link.session_id IS NULL OR thread_link.session_id = 0)';

            $forumPosts = $this->connection->fetchAllAssociative(
                "SELECT
                    post.iid AS post_id,
                    post.title AS post_title,
                    post.post_date,
                    post.visible,
                    post.status,
                    thread.iid AS thread_id,
                    thread.title AS thread_title,
                    forum.iid AS forum_id,
                    forum.title AS forum_title
                FROM c_forum_post post
                LEFT JOIN c_forum_thread thread
                    ON thread.iid = post.thread_id
                LEFT JOIN c_forum forum
                    ON forum.iid = post.forum_id
                LEFT JOIN resource_link post_link
                    ON post_link.resource_node_id = post.resource_node_id
                    AND post_link.c_id = :courseId
                    $postSessionCondition
                LEFT JOIN resource_link thread_link
                    ON thread_link.resource_node_id = thread.resource_node_id
                    AND thread_link.c_id = :courseId
                    $threadSessionCondition
                WHERE post.poster_id = :userId
                    AND (post_link.id IS NOT NULL OR thread_link.id IS NOT NULL)
                ORDER BY post.post_date DESC
                LIMIT :limit",
                $parameters,
                ['limit' => ParameterType::INTEGER]
            );
        }

        $forumThreads = [];
        if ($this->hasTables(['c_forum_thread', 'c_forum', 'resource_link'])) {
            $forumThreads = $this->connection->fetchAllAssociative(
                "SELECT
                    thread.iid AS thread_id,
                    thread.title AS thread_title,
                    thread.thread_date,
                    thread.thread_replies,
                    thread.thread_views,
                    forum.iid AS forum_id,
                    forum.title AS forum_title
                FROM c_forum_thread thread
                LEFT JOIN c_forum forum
                    ON forum.iid = thread.forum_id
                INNER JOIN resource_link rl
                    ON rl.resource_node_id = thread.resource_node_id
                WHERE thread.thread_poster_id = :userId
                    AND rl.c_id = :courseId
                    $sessionCondition
                ORDER BY thread.thread_date DESC
                LIMIT :limit",
                $parameters,
                ['limit' => ParameterType::INTEGER]
            );
        }

        $courseAccess = $this->connection->fetchAllAssociative(
            'SELECT
                course_access_id,
                login_course_date,
                logout_course_date,
                counter,
                user_ip
            FROM track_e_course_access
            WHERE user_id = :userId
                AND c_id = :courseId
                AND session_id = :sessionId
            ORDER BY login_course_date DESC
            LIMIT :limit',
            $parameters,
            ['limit' => ParameterType::INTEGER]
        );

        $resourceAccess = $this->connection->fetchAllAssociative(
            'SELECT
                access_id,
                access_date,
                access_tool,
                user_ip
            FROM track_e_access
            WHERE access_user_id = :userId
                AND c_id = :courseId
                AND session_id = :sessionId
            ORDER BY access_date DESC
            LIMIT :limit',
            $parameters,
            ['limit' => ParameterType::INTEGER]
        );

        return [
            'user' => [
                'id' => (int) $user['id'],
                'officialCode' => (string) ($user['official_code'] ?? ''),
                'firstname' => (string) ($user['firstname'] ?? ''),
                'lastname' => (string) ($user['lastname'] ?? ''),
                'fullName' => $this->fullName($user),
                'username' => (string) ($user['username'] ?? ''),
                'email' => $context->showEmailAddresses ? (string) ($user['email'] ?? '') : '',
                'pictureUri' => (string) ($user['picture_uri'] ?? ''),
            ],
            'downloads' => array_values($downloads),
            'forumThreads' => array_values($forumThreads),
            'forumPosts' => array_values($forumPosts),
            'courseAccess' => array_values($courseAccess),
            'resourceAccess' => array_values($resourceAccess),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLearnersForExport(
        CourseReportingContext $context,
        array $filters
    ): array {
        $filters['page'] = 1;
        $filters['itemsPerPage'] = 100000;

        return $this->getLearners($context, $filters)['items'];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{total: int, page: int, itemsPerPage: int, items: array<int, array<string, mixed>>}
     */
    private function fetchParticipantRows(
        CourseReportingContext $context,
        array $filters
    ): array {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $itemsPerPage = max(1, min(100000, (int) ($filters['itemsPerPage'] ?? 20)));
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $groupFilter = trim((string) ($filters['groupFilter'] ?? ''));
        if ('' === $groupFilter && $context->groupId > 0) {
            $groupFilter = 'group_'.$context->groupId;
        }
        $showTeachers = $this->normalizeBoolean($filters['showTeachers'] ?? false);
        $showActiveUsers = $this->normalizeBoolean($filters['showActiveUsers'] ?? false);
        $sort = (string) ($filters['sort'] ?? 'lastname');
        $direction = 'DESC' === strtoupper((string) ($filters['direction'] ?? 'ASC')) ? 'DESC' : 'ASC';

        $sortMap = [
            'officialCode' => 'participant.official_code',
            'firstname' => 'participant.firstname',
            'lastname' => 'participant.lastname',
            'username' => 'participant.username',
            'email' => 'participant.email',
        ];
        $sortExpression = $sortMap[$sort] ?? 'participant.lastname';

        [$participantSql, $parameters] = $this->buildParticipantSource(
            $context,
            $showTeachers,
            $showActiveUsers,
            $groupFilter
        );

        $where = [];
        if ('' !== $keyword) {
            $where[] = '(participant.firstname LIKE :keyword
                OR participant.lastname LIKE :keyword
                OR participant.username LIKE :keyword
                OR participant.email LIKE :keyword
                OR participant.official_code LIKE :keyword)';
            $parameters['keyword'] = '%'.$keyword.'%';
        }

        $extraFieldFilters = $this->normalizeExtraFieldFilters($filters['extraFieldFilters'] ?? '');
        if ([] !== $extraFieldFilters && $this->hasTable('extra_field_values')) {
            foreach ($extraFieldFilters as $index => $extraFieldFilter) {
                $alias = 'extra_filter_'.$index;
                $fieldParameter = 'extraFieldFilterId'.$index;
                $valueParameter = 'extraFieldFilterValue'.$index;
                $where[] = "EXISTS (
                    SELECT 1
                      FROM extra_field_values $alias
                     WHERE $alias.item_id = participant.id
                       AND $alias.field_id = :$fieldParameter
                       AND $alias.value LIKE :$valueParameter
                )";
                $parameters[$fieldParameter] = $extraFieldFilter['fieldId'];
                $parameters[$valueParameter] = '%'.$extraFieldFilter['value'].'%';
            }
        }

        $whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';
        $total = (int) $this->connection->fetchOne(
            "SELECT COUNT(*)
               FROM ($participantSql) participant
               $whereSql",
            $parameters
        );

        $offset = ($page - 1) * $itemsPerPage;
        $items = $this->connection->fetchAllAssociative(
            "SELECT participant.*
               FROM ($participantSql) participant
               $whereSql
              ORDER BY $sortExpression $direction, participant.id ASC
              LIMIT :limit OFFSET :offset",
            [
                ...$parameters,
                'limit' => $itemsPerPage,
                'offset' => $offset,
            ],
            [
                'limit' => ParameterType::INTEGER,
                'offset' => ParameterType::INTEGER,
            ]
        );

        return [
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'items' => array_values($items),
        ];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildParticipantSource(
        CourseReportingContext $context,
        bool $showTeachers,
        bool $showActiveUsers,
        string $groupFilter
    ): array {
        $courseId = $context->courseId();
        $sessionId = $context->sessionId();
        $parameters = ['courseId' => $courseId];

        $roleCondition = $showTeachers
            ? ''
            : "AND enrolled.report_role = 'student'";

        if ($sessionId > 0) {
            $parameters['sessionId'] = $sessionId;
            $enrolledSql = 'SELECT DISTINCT
                    user.id,
                    user.official_code,
                    user.firstname,
                    user.lastname,
                    user.username,
                    user.email,
                    user.picture_uri,
                    CASE
                        WHEN subscription.status = '.self::SESSION_COURSE_COACH_STATUS." THEN 'teacher'
                        ELSE 'student'
                    END AS report_role
                FROM session_rel_course_rel_user subscription
                INNER JOIN `user` user
                    ON user.id = subscription.user_id
                WHERE subscription.c_id = :courseId
                    AND subscription.session_id = :sessionId
                    AND subscription.status IN (".
                        self::SESSION_STUDENT_STATUS.', '.self::SESSION_COURSE_COACH_STATUS.
                    ')
                    AND user.active = 1';
        } else {
            $enrolledSql = 'SELECT DISTINCT
                    user.id,
                    user.official_code,
                    user.firstname,
                    user.lastname,
                    user.username,
                    user.email,
                    user.picture_uri,
                    CASE
                        WHEN subscription.status = '.self::COURSE_TEACHER_STATUS." THEN 'teacher'
                        ELSE 'student'
                    END AS report_role
                FROM course_rel_user subscription
                INNER JOIN `user` user
                    ON user.id = subscription.user_id
                WHERE subscription.c_id = :courseId
                    AND subscription.status IN (".
                        self::COURSE_STUDENT_STATUS.', '.self::COURSE_TEACHER_STATUS.
                    ')
                    AND subscription.relation_type <> '.self::HUMAN_RESOURCES_RELATION_TYPE.'
                    AND user.active = 1';
        }

        $enrolledSql = "SELECT enrolled.*
            FROM ($enrolledSql) enrolled
            WHERE 1 = 1 $roleCondition";

        if (preg_match('/^(group|class)_(\d+)$/', $groupFilter, $match)) {
            $filterId = (int) $match[2];
            if ('group' === $match[1] && $this->hasTable('c_group_rel_user')) {
                $enrolledSql .= ' AND EXISTS (
                    SELECT 1
                      FROM c_group_rel_user group_user
                     WHERE group_user.user_id = enrolled.id
                       AND group_user.c_id = :courseId
                       AND group_user.group_id = :participantGroupId
                )';
                $parameters['participantGroupId'] = $filterId;
            }

            if ('class' === $match[1] && $this->hasTable('usergroup_rel_user')) {
                $enrolledSql .= ' AND EXISTS (
                    SELECT 1
                      FROM usergroup_rel_user class_user
                     WHERE class_user.user_id = enrolled.id
                       AND class_user.usergroup_id = :participantClassId
                )';
                $parameters['participantClassId'] = $filterId;
            }
        }

        if (!$showActiveUsers || '' !== $groupFilter) {
            return [$enrolledSql, $parameters];
        }

        $parameters['activeSessionId'] = $sessionId;

        $activeSql = "SELECT DISTINCT
                user.id,
                user.official_code,
                user.firstname,
                user.lastname,
                user.username,
                user.email,
                user.picture_uri,
                'active' AS report_role
            FROM track_e_course_access access_log
            INNER JOIN `user` user
                ON user.id = access_log.user_id
            WHERE access_log.c_id = :courseId
                AND access_log.session_id = :activeSessionId
                AND user.active = 1
                AND NOT EXISTS (
                    SELECT 1
                      FROM ($enrolledSql) enrolled_user
                     WHERE enrolled_user.id = user.id
                )";

        return ["$enrolledSql UNION $activeSql", $parameters];
    }

    /**
     * @param int[] $userIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadLearnerMetrics(
        CourseReportingContext $context,
        array $userIds
    ): array {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ([] === $userIds) {
            return [];
        }

        $metrics = [];
        foreach ($userIds as $userId) {
            $metrics[$userId] = $this->emptyMetrics();
        }

        $courseId = $context->courseId();
        $sessionId = $context->sessionId();

        $accessRows = $this->connection->fetchAllAssociative(
            'SELECT
                user_id,
                SUM(
                    CASE
                        WHEN logout_course_date IS NOT NULL
                            AND logout_course_date >= login_course_date
                        THEN TIMESTAMPDIFF(SECOND, login_course_date, logout_course_date)
                        ELSE 0
                    END
                ) AS time_seconds,
                MIN(login_course_date) AS first_access,
                MAX(login_course_date) AS latest_access
            FROM track_e_course_access
            WHERE c_id = :courseId
                AND session_id = :sessionId
                AND user_id IN (:userIds)
            GROUP BY user_id',
            [
                'courseId' => $courseId,
                'sessionId' => $sessionId,
                'userIds' => $userIds,
            ],
            ['userIds' => ArrayParameterType::INTEGER]
        );

        foreach ($accessRows as $row) {
            $userId = (int) $row['user_id'];
            $metrics[$userId]['timeSeconds'] = (int) ($row['time_seconds'] ?? 0);
            $metrics[$userId]['firstAccess'] = $row['first_access'] ?: null;
            $metrics[$userId]['latestAccess'] = $row['latest_access'] ?: null;
        }

        $quizIds = $this->getCourseResourceIds('c_quiz', $context);
        if ([] !== $quizIds) {
            $exerciseRows = $this->connection->fetchAllAssociative(
                "SELECT
                    exe_user_id AS user_id,
                    exe_exo_id AS quiz_id,
                    MAX(CASE WHEN max_score > 0 THEN score / max_score ELSE 0 END) AS best_ratio,
                    MAX(exe_date) AS latest_date
                FROM track_e_exercises
                WHERE c_id = :courseId
                    AND COALESCE(session_id, 0) = :sessionId
                    AND status = ''
                    AND exe_user_id IN (:userIds)
                    AND exe_exo_id IN (:quizIds)
                GROUP BY exe_user_id, exe_exo_id",
                [
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                    'userIds' => $userIds,
                    'quizIds' => $quizIds,
                ],
                [
                    'userIds' => ArrayParameterType::INTEGER,
                    'quizIds' => ArrayParameterType::INTEGER,
                ]
            );

            $attempted = [];
            $ratioSums = [];
            foreach ($exerciseRows as $row) {
                $userId = (int) $row['user_id'];
                $attempted[$userId] = ($attempted[$userId] ?? 0) + 1;
                $bestRatio = max(0.0, min(1.0, (float) $row['best_ratio']));
                $ratioSums[$userId] = ($ratioSums[$userId] ?? 0.0) + $bestRatio;
                $metrics[$userId]['configuredExerciseResults'][(string) (int) $row['quiz_id']] = round(
                    100 * $bestRatio,
                    2
                );

                if (
                    null === $metrics[$userId]['quizFinalizationDate']
                    || (string) $row['latest_date'] > (string) $metrics[$userId]['quizFinalizationDate']
                ) {
                    $metrics[$userId]['quizFinalizationDate'] = $row['latest_date'];
                }
            }

            $quizCount = \count($quizIds);
            foreach ($userIds as $userId) {
                $metrics[$userId]['exerciseProgress'] = $quizCount > 0
                    ? 100 * (($attempted[$userId] ?? 0) / $quizCount)
                    : 0.0;
                $metrics[$userId]['exerciseAverage'] = $quizCount > 0
                    ? 100 * (($ratioSums[$userId] ?? 0.0) / $quizCount)
                    : 0.0;
            }
        }

        $lpIds = $this->getCourseResourceIds('c_lp', $context);
        if ([] !== $lpIds) {
            $lpRows = $this->connection->fetchAllAssociative(
                'SELECT iid, user_id, lp_id, view_count, progress, completion_date
                   FROM c_lp_view
                  WHERE c_id = :courseId
                    AND session_id = :sessionId
                    AND user_id IN (:userIds)
                    AND lp_id IN (:lpIds)
                  ORDER BY user_id ASC, lp_id ASC, view_count DESC, iid DESC',
                [
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                    'userIds' => $userIds,
                    'lpIds' => $lpIds,
                ],
                [
                    'userIds' => ArrayParameterType::INTEGER,
                    'lpIds' => ArrayParameterType::INTEGER,
                ]
            );

            $latestProgress = [];
            foreach ($lpRows as $row) {
                $userId = (int) $row['user_id'];
                $lpId = (int) $row['lp_id'];
                if (isset($latestProgress[$userId][$lpId])) {
                    continue;
                }

                $latestProgress[$userId][$lpId] = max(0.0, min(100.0, (float) ($row['progress'] ?? 0)));
            }

            $lpCount = \count($lpIds);
            foreach ($userIds as $userId) {
                $progressValues = array_values($latestProgress[$userId] ?? []);
                $metrics[$userId]['learningPathProgress'] = $context->useMaximumLearningPathProgress
                    ? ($progressValues ? max($progressValues) : 0.0)
                    : ($lpCount > 0 ? array_sum($progressValues) / $lpCount : 0.0);
            }

            $this->loadLearningPathScores($metrics, $userIds, $courseId, $sessionId, $lpIds);

            $finalizationRows = $this->connection->fetchAllAssociative(
                "SELECT
                    lp_view.user_id,
                    MAX(FROM_UNIXTIME(item_view.start_time)) AS finalization_date
                FROM c_lp_item_view item_view
                INNER JOIN c_lp_view lp_view
                    ON lp_view.iid = item_view.lp_view_id
                INNER JOIN c_lp_item item
                    ON item.iid = item_view.lp_item_id
                WHERE lp_view.c_id = :courseId
                    AND lp_view.session_id = :sessionId
                    AND lp_view.user_id IN (:userIds)
                    AND lp_view.lp_id IN (:lpIds)
                    AND item.item_type = 'final_item'
                    AND item_view.status = 'completed'
                GROUP BY lp_view.user_id",
                [
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                    'userIds' => $userIds,
                    'lpIds' => $lpIds,
                ],
                [
                    'userIds' => ArrayParameterType::INTEGER,
                    'lpIds' => ArrayParameterType::INTEGER,
                ]
            );

            foreach ($finalizationRows as $row) {
                $metrics[(int) $row['user_id']]['learningPathFinalizationDate'] = $row['finalization_date'] ?: null;
            }
        }

        if ($this->hasTables(['gradebook_certificate', 'gradebook_category'])) {
            $certificateRows = $this->connection->fetchFirstColumn(
                'SELECT DISTINCT certificate.user_id
                   FROM gradebook_certificate certificate
                   INNER JOIN gradebook_category category
                       ON category.id = certificate.cat_id
                  WHERE category.c_id = :courseId
                    AND COALESCE(category.session_id, 0) = :sessionId
                    AND certificate.user_id IN (:userIds)',
                [
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                    'userIds' => $userIds,
                ],
                ['userIds' => ArrayParameterType::INTEGER]
            );

            foreach ($certificateRows as $userId) {
                $metrics[(int) $userId]['certificateAvailable'] = true;
            }
        }

        return $metrics;
    }

    /**
     * @param array<int, array<string, mixed>> $metrics
     * @param int[]                            $userIds
     * @param int[]                            $lpIds
     */
    private function loadLearningPathScores(
        array &$metrics,
        array $userIds,
        int $courseId,
        int $sessionId,
        array $lpIds
    ): void {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT
                lp_view.user_id,
                lp_view.lp_id,
                lp_view.iid AS lp_view_id,
                lp_view.view_count AS lp_view_count,
                item_view.lp_item_id,
                item_view.iid AS item_view_id,
                item_view.view_count AS item_view_count,
                item_view.score,
                item_view.max_score,
                item_view.status
            FROM c_lp_view lp_view
            INNER JOIN c_lp_item_view item_view
                ON item_view.lp_view_id = lp_view.iid
            WHERE lp_view.c_id = :courseId
                AND lp_view.session_id = :sessionId
                AND lp_view.user_id IN (:userIds)
                AND lp_view.lp_id IN (:lpIds)
            ORDER BY
                lp_view.user_id ASC,
                lp_view.lp_id ASC,
                lp_view.view_count DESC,
                lp_view.iid DESC,
                item_view.lp_item_id ASC,
                item_view.view_count DESC,
                item_view.iid DESC',
            [
                'courseId' => $courseId,
                'sessionId' => $sessionId,
                'userIds' => $userIds,
                'lpIds' => $lpIds,
            ],
            [
                'userIds' => ArrayParameterType::INTEGER,
                'lpIds' => ArrayParameterType::INTEGER,
            ]
        );

        $latestLpView = [];
        $latestItems = [];
        $bestItems = [];

        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $lpId = (int) $row['lp_id'];
            $lpViewId = (int) $row['lp_view_id'];
            $itemId = (int) $row['lp_item_id'];
            $maxScore = is_numeric($row['max_score']) ? (float) $row['max_score'] : 0.0;
            if ($maxScore <= 0.0 || 'not attempted' === $row['status']) {
                continue;
            }

            $ratio = max(0.0, min(1.0, ((float) $row['score']) / $maxScore));
            if (!isset($latestLpView[$userId][$lpId])) {
                $latestLpView[$userId][$lpId] = $lpViewId;
            }

            if (
                $latestLpView[$userId][$lpId] === $lpViewId
                && !isset($latestItems[$userId][$lpId][$itemId])
            ) {
                $latestItems[$userId][$lpId][$itemId] = $ratio;
            }

            $bestItems[$userId][$lpId][$itemId] = max(
                $bestItems[$userId][$lpId][$itemId] ?? 0.0,
                $ratio
            );
        }

        foreach ($userIds as $userId) {
            $normalRatios = [];
            foreach ($latestItems[$userId] ?? [] as $itemRatios) {
                array_push($normalRatios, ...array_values($itemRatios));
            }

            $bestRatios = [];
            foreach ($bestItems[$userId] ?? [] as $itemRatios) {
                array_push($bestRatios, ...array_values($itemRatios));
            }

            $metrics[$userId]['score'] = $normalRatios
                ? 100 * array_sum($normalRatios) / \count($normalRatios)
                : 0.0;
            $metrics[$userId]['bestScore'] = $bestRatios
                ? 100 * array_sum($bestRatios) / \count($bestRatios)
                : 0.0;
        }
    }

    /**
     * @return int[]
     */
    private function getCourseResourceIds(
        string $resourceTable,
        CourseReportingContext $context
    ): array {
        if (!$this->hasTables([$resourceTable, 'resource_link'])) {
            return [];
        }

        if (!\in_array($resourceTable, ['c_quiz', 'c_lp'], true)) {
            throw new BadRequestHttpException('Unsupported reporting resource table.');
        }

        $sessionId = $context->sessionId();
        $sessionSql = $sessionId > 0
            ? 'AND (resource_link.session_id IS NULL OR resource_link.session_id = 0 OR resource_link.session_id = :sessionId)'
            : 'AND (resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $groupSql = $context->groupId > 0
            ? 'AND (resource_link.group_id IS NULL OR resource_link.group_id = 0 OR resource_link.group_id = :groupId)'
            : 'AND (resource_link.group_id IS NULL OR resource_link.group_id = 0)';

        $parameters = ['courseId' => $context->courseId()];
        if ($sessionId > 0) {
            $parameters['sessionId'] = $sessionId;
        }
        if ($context->groupId > 0) {
            $parameters['groupId'] = $context->groupId;
        }

        $ids = $this->connection->fetchFirstColumn(
            "SELECT DISTINCT resource.iid
               FROM $resourceTable resource
               INNER JOIN resource_link
                   ON resource_link.resource_node_id = resource.resource_node_id
              WHERE resource_link.c_id = :courseId
                AND resource_link.deleted_at IS NULL
                $sessionSql
                $groupSql
              ORDER BY resource.iid ASC",
            $parameters
        );

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * @return array<int, array{id: int, title: string, columnKey: string}>
     */
    private function getConfiguredExercises(CourseReportingContext $context): array
    {
        if ([] === $context->configuredExerciseIds || !$this->hasTable('c_quiz')) {
            return [];
        }

        $courseQuizIds = array_flip($this->getCourseResourceIds('c_quiz', $context));
        $allowedIds = array_values(array_filter(
            $context->configuredExerciseIds,
            static fn (int $exerciseId): bool => isset($courseQuizIds[$exerciseId])
        ));
        if ([] === $allowedIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT iid, title
               FROM c_quiz
              WHERE iid IN (:exerciseIds)
              ORDER BY FIELD(iid, '.implode(', ', array_map('intval', $allowedIds)).')',
            ['exerciseIds' => $allowedIds],
            ['exerciseIds' => ArrayParameterType::INTEGER]
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['iid'],
                'title' => trim(strip_tags((string) $row['title'])),
                'columnKey' => 'exercise_'.(int) $row['iid'],
            ],
            $rows
        );
    }

    /**
     * @param int[] $userIds
     *
     * @return array<int, string[]>
     */
    private function getUserClasses(array $userIds): array
    {
        if ([] === $userIds || !$this->hasTables(['usergroup', 'usergroup_rel_user'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT relation.user_id, usergroup.title
               FROM usergroup_rel_user relation
               INNER JOIN usergroup
                   ON usergroup.id = relation.usergroup_id
              WHERE relation.user_id IN (:userIds)
              ORDER BY usergroup.title ASC',
            ['userIds' => $userIds],
            ['userIds' => ArrayParameterType::INTEGER]
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['user_id']][] = (string) $row['title'];
        }

        return $result;
    }

    /**
     * @param int[] $userIds
     * @param int[] $fieldIds
     *
     * @return array<int, array<string, string>>
     */
    private function getExtraFieldValues(array $userIds, array $fieldIds): array
    {
        if (
            [] === $userIds
            || [] === $fieldIds
            || !$this->hasTables(['extra_field', 'extra_field_values'])
        ) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT
                field_values.item_id AS user_id,
                field.variable,
                field_values.value
               FROM extra_field_values field_values
               INNER JOIN extra_field field
                   ON field.id = field_values.field_id
              WHERE field.item_type = :itemType
                AND field.id IN (:fieldIds)
                AND field_values.item_id IN (:userIds)
              ORDER BY field_values.id ASC',
            [
                'itemType' => ExtraField::USER_FIELD_TYPE,
                'fieldIds' => $fieldIds,
                'userIds' => $userIds,
            ],
            [
                'fieldIds' => ArrayParameterType::INTEGER,
                'userIds' => ArrayParameterType::INTEGER,
            ]
        );

        $result = [];
        foreach ($rows as $row) {
            $userId = (int) $row['user_id'];
            $variable = (string) $row['variable'];
            $value = trim((string) ($row['value'] ?? ''));

            if (isset($result[$userId][$variable]) && '' !== $value) {
                $result[$userId][$variable] .= ', '.$value;
            } else {
                $result[$userId][$variable] = $value;
            }
        }

        return $result;
    }

    /**
     * @return array{assignments: int, messages: int}
     */
    private function getCourseWideCounts(CourseReportingContext $context): array
    {
        return [
            'assignments' => $this->countLinkedResources('c_student_publication', $context),
            'messages' => $this->countLinkedResources('c_forum_post', $context),
        ];
    }

    private function countLinkedResources(
        string $resourceTable,
        CourseReportingContext $context
    ): int {
        if (!$this->hasTables([$resourceTable, 'resource_link'])) {
            return 0;
        }

        if (!\in_array($resourceTable, ['c_student_publication', 'c_forum_post'], true)) {
            return 0;
        }

        $sessionId = $context->sessionId();
        $sessionSql = $sessionId > 0
            ? 'AND resource_link.session_id = :sessionId'
            : 'AND (resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $groupSql = $context->groupId > 0
            ? 'AND resource_link.group_id = :groupId'
            : '';

        $parameters = ['courseId' => $context->courseId()];
        if ($sessionId > 0) {
            $parameters['sessionId'] = $sessionId;
        }
        if ($context->groupId > 0) {
            $parameters['groupId'] = $context->groupId;
        }

        return (int) $this->connection->fetchOne(
            "SELECT COUNT(DISTINCT resource.iid)
               FROM $resourceTable resource
               INNER JOIN resource_link
                   ON resource_link.resource_node_id = resource.resource_node_id
              WHERE resource_link.c_id = :courseId
                AND resource_link.deleted_at IS NULL
                $sessionSql
                $groupSql",
            $parameters
        );
    }

    /**
     * @param int[] $userIds
     *
     * @return array<int, string>
     */
    private function getSurveyProgress(
        CourseReportingContext $context,
        array $userIds
    ): array {
        if (
            [] === $userIds
            || !$this->hasTables(['c_survey', 'c_survey_answer', 'resource_link'])
        ) {
            return [];
        }

        $surveyIds = array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT DISTINCT survey.iid
                   FROM c_survey survey
                   INNER JOIN resource_link
                       ON resource_link.resource_node_id = survey.resource_node_id
                  WHERE resource_link.c_id = :courseId
                    AND resource_link.deleted_at IS NULL
                    AND (resource_link.session_id IS NULL OR resource_link.session_id = 0)',
                ['courseId' => $context->courseId()]
            )
        );

        $total = \count($surveyIds);
        $result = array_fill_keys($userIds, '0 / '.$total);
        if (0 === $total) {
            return $result;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT user AS user_id, COUNT(DISTINCT survey_id) AS completed
               FROM c_survey_answer
              WHERE user IN (:userIds)
                AND survey_id IN (:surveyIds)
              GROUP BY user',
            [
                'userIds' => $userIds,
                'surveyIds' => $surveyIds,
            ],
            [
                'userIds' => ArrayParameterType::INTEGER,
                'surveyIds' => ArrayParameterType::INTEGER,
            ]
        );

        foreach ($rows as $row) {
            $result[(int) $row['user_id']] = (int) $row['completed'].' / '.$total;
        }

        return $result;
    }

    /**
     * @param int[] $userIds
     *
     * @return array<int, null|string>
     */
    private function getSessionRegistrationDates(int $sessionId, array $userIds): array
    {
        if ([] === $userIds || !$this->hasTable('session_rel_user')) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT user_id, registered_at
               FROM session_rel_user
              WHERE session_id = :sessionId
                AND user_id IN (:userIds)',
            [
                'sessionId' => $sessionId,
                'userIds' => $userIds,
            ],
            ['userIds' => ArrayParameterType::INTEGER]
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['user_id']] = $row['registered_at'] ?: null;
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTeachers(CourseReportingContext $context): array
    {
        if ($context->sessionId() > 0) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT DISTINCT user.id, user.firstname, user.lastname, user.username, user.picture_uri
                   FROM session_rel_course_rel_user subscription
                   INNER JOIN `user` user
                       ON user.id = subscription.user_id
                  WHERE subscription.c_id = :courseId
                    AND subscription.session_id = :sessionId
                    AND subscription.status = :status
                  ORDER BY user.lastname ASC, user.firstname ASC',
                [
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                    'status' => self::SESSION_COURSE_COACH_STATUS,
                ]
            );
        } else {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT DISTINCT user.id, user.firstname, user.lastname, user.username, user.picture_uri
                   FROM course_rel_user subscription
                   INNER JOIN `user` user
                       ON user.id = subscription.user_id
                  WHERE subscription.c_id = :courseId
                    AND subscription.status = :status
                    AND subscription.relation_type <> :humanResourcesRelation
                  ORDER BY user.lastname ASC, user.firstname ASC',
                [
                    'courseId' => $context->courseId(),
                    'status' => self::COURSE_TEACHER_STATUS,
                    'humanResourcesRelation' => self::HUMAN_RESOURCES_RELATION_TYPE,
                ]
            );
        }

        return array_map(
            fn (array $row): array => [
                'id' => (int) $row['id'],
                'fullName' => $this->fullName($row),
                'username' => (string) $row['username'],
                'pictureUri' => (string) ($row['picture_uri'] ?? ''),
            ],
            $rows
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSessions(CourseReportingContext $context): array
    {
        if (!$this->hasTables(['session', 'session_rel_course'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT
                session.id,
                session.title,
                session.access_start_date,
                session.access_end_date
               FROM session
               INNER JOIN session_rel_course relation
                   ON relation.session_id = session.id
              WHERE relation.c_id = :courseId
              ORDER BY session.access_start_date DESC, session.title ASC',
            ['courseId' => $context->courseId()]
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'title' => (string) $row['title'],
                'accessStartDate' => $row['access_start_date'] ?: null,
                'accessEndDate' => $row['access_end_date'] ?: null,
            ],
            $rows
        );
    }

    private function isUserInReportingScope(
        CourseReportingContext $context,
        int $userId
    ): bool {
        if ($context->sessionId() > 0) {
            $isEnrolled = (bool) $this->connection->fetchOne(
                'SELECT 1
                   FROM session_rel_course_rel_user
                  WHERE user_id = :userId
                    AND c_id = :courseId
                    AND session_id = :sessionId
                    AND status IN (:statuses)
                  LIMIT 1',
                [
                    'userId' => $userId,
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                    'statuses' => [
                        self::SESSION_STUDENT_STATUS,
                        self::SESSION_COURSE_COACH_STATUS,
                    ],
                ],
                ['statuses' => ArrayParameterType::INTEGER]
            );

            if ($isEnrolled) {
                return true;
            }

            return (bool) $this->connection->fetchOne(
                'SELECT 1
                   FROM track_e_course_access
                  WHERE user_id = :userId
                    AND c_id = :courseId
                    AND session_id = :sessionId
                  LIMIT 1',
                [
                    'userId' => $userId,
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                ]
            );
        }

        $isEnrolled = (bool) $this->connection->fetchOne(
            'SELECT 1
               FROM course_rel_user
              WHERE user_id = :userId
                AND c_id = :courseId
                AND status IN (:statuses)
                AND relation_type <> :humanResourcesRelation
              LIMIT 1',
            [
                'userId' => $userId,
                'courseId' => $context->courseId(),
                'statuses' => [
                    self::COURSE_STUDENT_STATUS,
                    self::COURSE_TEACHER_STATUS,
                ],
                'humanResourcesRelation' => self::HUMAN_RESOURCES_RELATION_TYPE,
            ],
            ['statuses' => ArrayParameterType::INTEGER]
        );

        if ($isEnrolled) {
            return true;
        }

        return (bool) $this->connection->fetchOne(
            'SELECT 1
               FROM track_e_course_access
              WHERE user_id = :userId
                AND c_id = :courseId
                AND session_id = :sessionId
              LIMIT 1',
            [
                'userId' => $userId,
                'courseId' => $context->courseId(),
                'sessionId' => $context->sessionId(),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyMetrics(): array
    {
        return [
            'timeSeconds' => 0,
            'learningPathProgress' => 0.0,
            'exerciseProgress' => 0.0,
            'exerciseAverage' => 0.0,
            'score' => 0.0,
            'bestScore' => 0.0,
            'firstAccess' => null,
            'latestAccess' => null,
            'learningPathFinalizationDate' => null,
            'quizFinalizationDate' => null,
            'certificateAvailable' => false,
            'configuredExerciseResults' => [],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function fullName(array $row): string
    {
        $name = trim((string) ($row['firstname'] ?? '').' '.(string) ($row['lastname'] ?? ''));

        return '' !== $name ? $name : (string) ($row['username'] ?? '');
    }

    /**
     * @return array<int, array{fieldId: int, value: string}>
     */
    private function normalizeExtraFieldFilters(mixed $value): array
    {
        if (\is_string($value)) {
            $decoded = json_decode($value, true);
            $value = \is_array($decoded) ? $decoded : [];
        }

        if (!\is_array($value)) {
            return [];
        }

        $allowedFieldIds = [];
        foreach ($this->extraFieldRepository->getExtraFields(ExtraField::USER_FIELD_TYPE) as $field) {
            if ($field->isFilter() && null !== $field->getId()) {
                $allowedFieldIds[(int) $field->getId()] = true;
            }
        }

        $filters = [];
        foreach ($value as $fieldId => $filterValue) {
            $fieldId = (int) $fieldId;
            $filterValue = trim((string) $filterValue);
            if ($fieldId <= 0 || '' === $filterValue || !isset($allowedFieldIds[$fieldId])) {
                continue;
            }

            $filters[] = [
                'fieldId' => $fieldId,
                'value' => mb_substr($filterValue, 0, 255),
            ];
        }

        return $filters;
    }

    /**
     * @return int[]
     */
    private function normalizeIdList(mixed $value): array
    {
        if (\is_string($value)) {
            $value = explode(',', $value);
        }

        if (!\is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value))));
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        return \is_string($value)
            && \in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function hasTable(string $tableName): bool
    {
        if (null === $this->tableNames) {
            $this->tableNames = $this->connection->createSchemaManager()->listTableNames();
        }

        return \in_array($tableName, $this->tableNames, true);
    }

    /**
     * @param string[] $tableNames
     */
    private function hasTables(array $tableNames): bool
    {
        foreach ($tableNames as $tableName) {
            if (!$this->hasTable($tableName)) {
                return false;
            }
        }

        return true;
    }
}
