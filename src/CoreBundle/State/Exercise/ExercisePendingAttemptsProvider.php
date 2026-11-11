<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExercisePendingAttempts;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Service\Reporting\ReportIpVisibility;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * Read-only provider for the migrated pending exercise attempts page.
 *
 * This intentionally mirrors public/main/exercise/pending.php while returning
 * clean JSON for the Vue screen.
 *
 * @implements ProviderInterface<ExercisePendingAttempts>
 */
final readonly class ExercisePendingAttemptsProvider implements ProviderInterface
{
    private const int STATUS_ALL = 1;
    private const int STATUS_VALIDATED = 2;
    private const int STATUS_NOT_VALIDATED = 3;
    private const int STATUS_UNCLOSED = 4;
    private const int STATUS_ONGOING = 5;
    private const int STATUS_PENDING_CORRECTION = 6;
    private const int QUESTION_TYPE_WITHOUT_AUTOMATIC_CORRECTION = 1;
    private const int DEFAULT_LIMIT = 1000;

    public function __construct(
        private RequestStack $requestStack,
        private Connection $connection,
        private Security $security,
        private SettingsManager $settingsManager,
        private ReportIpVisibility $reportIpVisibility,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExercisePendingAttempts
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        return $this->buildData($request);
    }

    public function buildData(Request $request): ExercisePendingAttempts
    {
        $this->assertAccessAllowed();

        $filters = $this->getFilters($request);
        $includeSessions = $this->isSettingEnabled('exercise.show_exercise_attempts_in_all_user_sessions');
        $allowedCourseIds = $this->getAllowedCourseIds($includeSessions);
        $items = $this->getPendingAttempts($filters, $includeSessions, $allowedCourseIds);

        $response = new ExercisePendingAttempts();
        $response->items = $items;
        $response->filters = $filters;
        $response->courseOptions = $this->getCourseOptions($includeSessions);
        $response->exerciseOptions = $this->getExerciseOptions((int) $filters['courseId'], $allowedCourseIds);
        $response->settings = [
            'showOfficialCode' => $this->isSettingEnabled('exercise.show_official_code_exercise_result_list'),
            'showUsername' => $this->isSettingEnabled('exercise.exercise_attempts_report_show_username'),
            'showIp' => !$this->reportIpVisibility->isExerciseHidden(),
            'includeSessions' => $includeSessions,
        ];
        $response->actionUrls = [
            'exportCsv' => '/api/exercise/pending-attempts/export.csv?'.$this->buildQueryString($filters),
        ];
        $response->totalItems = \count($items);
        $response->canManage = true;

        return $response;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, string>
     */
    public function getCsvHeaders(array $data): array
    {
        $settings = \is_array($data['settings'] ?? null) ? $data['settings'] : [];
        $headers = [
            'Course',
            'Exercise',
        ];

        if (true === ($settings['showOfficialCode'] ?? false)) {
            $headers[] = 'Official code';
        }

        $headers[] = 'First name';
        $headers[] = 'Last name';

        if (true === ($settings['showUsername'] ?? false)) {
            $headers[] = 'Username';
        }

        return [
            ...$headers,
            'Duration (min)',
            'Start date',
            'End date',
            'Score',
            ...(true === ($settings['showIp'] ?? false) ? ['IP'] : []),
            'Status',
            'Corrector',
            'Correction date',
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, array<int, string|float|int>>
     */
    public function getCsvRows(array $data): array
    {
        $settings = \is_array($data['settings'] ?? null) ? $data['settings'] : [];
        $items = \is_array($data['items'] ?? null) ? $data['items'] : [];
        $rows = [];

        foreach ($items as $item) {
            if (!\is_array($item)) {
                continue;
            }

            $row = [
                (string) ($item['courseTitle'] ?? ''),
                (string) ($item['exerciseTitle'] ?? ''),
            ];

            if (true === ($settings['showOfficialCode'] ?? false)) {
                $row[] = (string) ($item['officialCode'] ?? '');
            }

            $row[] = (string) ($item['firstName'] ?? '');
            $row[] = (string) ($item['lastName'] ?? '');

            if (true === ($settings['showUsername'] ?? false)) {
                $row[] = (string) ($item['username'] ?? '');
            }

            $row[] = (float) ($item['durationMinutes'] ?? 0);
            $row[] = (string) ($item['startDate'] ?? '');
            $row[] = (string) ($item['endDate'] ?? '');
            $row[] = (string) ($item['scoreLabel'] ?? '');
            if (true === ($settings['showIp'] ?? false)) {
                $row[] = (string) ($item['userIp'] ?? '');
            }
            $row[] = (string) ($item['statusLabel'] ?? '');
            $row[] = (string) ($item['qualificatorFullName'] ?? '');
            $row[] = (string) ($item['qualificationDate'] ?? '');

            $rows[] = $row;
        }

        return $rows;
    }

    private function assertAccessAllowed(): void
    {
        if (!$this->isGlobalExerciseManager()) {
            throw new AccessDeniedHttpException('You are not allowed to view pending exercise attempts.');
        }

        if (!$this->isSettingEnabled('exercise.my_courses_show_pending_exercise_attempts')) {
            throw new AccessDeniedHttpException('Pending exercise attempts are disabled.');
        }
    }

    private function isGlobalExerciseManager(): bool
    {
        return $this->security->isGranted('ROLE_TEACHER') || $this->security->isGranted('ROLE_SESSION_MANAGER');
    }

    /**
     * @return array<string, int|string>
     */
    private function getFilters(Request $request): array
    {
        $startDate = trim((string) $request->query->get('startDate', $request->query->get('start_date', '')));
        $endDate = trim((string) $request->query->get('endDate', $request->query->get('end_date', '')));

        return [
            'courseId' => max(0, $request->query->getInt('courseId', $request->query->getInt('course_id'))),
            'exerciseId' => max(0, $request->query->getInt('exerciseId', $request->query->getInt('exercise_id'))),
            'filterByUser' => max(0, $request->query->getInt('filterByUser', $request->query->getInt('filter_by_user'))),
            'status' => $this->normalizeStatus($request->query->getInt('status', self::STATUS_NOT_VALIDATED)),
            'questionTypeId' => max(0, $request->query->getInt('questionTypeId')),
            'startDate' => $this->isValidDate($startDate) ? $startDate : '',
            'endDate' => $this->isValidDate($endDate) ? $endDate : '',
        ];
    }

    private function normalizeStatus(int $status): int
    {
        return \in_array($status, [
            self::STATUS_ALL,
            self::STATUS_VALIDATED,
            self::STATUS_NOT_VALIDATED,
            self::STATUS_UNCLOSED,
            self::STATUS_ONGOING,
            self::STATUS_PENDING_CORRECTION,
        ], true) ? $status : self::STATUS_NOT_VALIDATED;
    }

    private function isValidDate(string $date): bool
    {
        return 1 === preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    /**
     * @param array<string, int|string> $filters
     * @param array<int, int>           $allowedCourseIds
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function getPendingAttempts(array $filters, bool $includeSessions, array $allowedCourseIds): array
    {
        $params = [];
        $types = [];
        $where = [];

        if (!$this->isPlatformAdmin()) {
            if ([] === $allowedCourseIds) {
                $where[] = '1 = 0';
            } else {
                $where[] = 'te.c_id IN (:allowedCourseIds)';
                $params['allowedCourseIds'] = $allowedCourseIds;
                $types['allowedCourseIds'] = ArrayParameterType::INTEGER;
            }
        }

        if (!$includeSessions) {
            $currentSessionId = $this->getCurrentSessionId();
            if ($currentSessionId > 0) {
                $where[] = 'te.session_id = :currentSessionId';
                $params['currentSessionId'] = $currentSessionId;
                $types['currentSessionId'] = ParameterType::INTEGER;
            } else {
                $where[] = '(te.session_id IS NULL OR te.session_id = 0)';
            }
        }

        if ((int) $filters['courseId'] > 0) {
            $where[] = 'te.c_id = :courseId';
            $params['courseId'] = (int) $filters['courseId'];
            $types['courseId'] = ParameterType::INTEGER;
        }

        if ((int) $filters['exerciseId'] > 0) {
            $where[] = 'te.exe_exo_id = :exerciseId';
            $params['exerciseId'] = (int) $filters['exerciseId'];
            $types['exerciseId'] = ParameterType::INTEGER;
        }

        if ((int) $filters['filterByUser'] > 0) {
            $where[] = 'te.exe_user_id = :filterByUser';
            $params['filterByUser'] = (int) $filters['filterByUser'];
            $types['filterByUser'] = ParameterType::INTEGER;
        }

        if ('' !== (string) $filters['startDate']) {
            $where[] = 'te.start_date >= :startDate';
            $params['startDate'] = $filters['startDate'].' 00:00:00';
            $types['startDate'] = ParameterType::STRING;
        }

        if ('' !== (string) $filters['endDate']) {
            $where[] = 'te.exe_date <= :endDate';
            $params['endDate'] = $filters['endDate'].' 23:59:59';
            $types['endDate'] = ParameterType::STRING;
        }

        $status = (int) $filters['status'];
        if (self::STATUS_VALIDATED === $status) {
            $where[] = $this->existsQualifiedAttemptSql();
        } elseif (self::STATUS_NOT_VALIDATED === $status) {
            $where[] = "te.status <> 'incomplete'";
            $where[] = 'NOT '.$this->existsQualifiedAttemptSql();
        } elseif (self::STATUS_UNCLOSED === $status) {
            $where[] = "te.status = 'incomplete'";
        } elseif (self::STATUS_ONGOING === $status) {
            $where[] = "(te.exe_date IS NULL OR te.exe_date = '0000-00-00 00:00:00')";
        } elseif (self::STATUS_PENDING_CORRECTION === $status) {
            $where[] = $this->pendingCorrectionPredicateSql();
        }

        if (self::QUESTION_TYPE_WITHOUT_AUTOMATIC_CORRECTION === (int) $filters['questionTypeId']) {
            $where[] = $this->pendingCorrectionPredicateSql();
        }

        $whereSql = [] === $where ? '1 = 1' : implode("\n AND ", $where);
        $officialCodeSelect = $this->tableHasColumn('user', 'official_code') ? 'u.official_code' : "''";

        $sql = <<<SQL
SELECT DISTINCT
    c.id AS course_id,
    c.code AS course_code,
    c.title AS course_title,
    q.iid AS exercise_id,
    q.resource_node_id AS exercise_resource_node_id,
    q.title AS exercise_title,
    te.exe_id AS attempt_id,
    te.exe_user_id AS user_id,
    te.session_id AS session_id,
    te.score AS score,
    te.max_score AS max_score,
    te.start_date AS start_date,
    te.exe_date AS end_date,
    te.status AS attempt_status,
    te.questions_to_check AS pending_questions_to_check,
    te.exe_duration AS duration,
    te.user_ip AS user_ip,
    {$this->revisedSelectSql()} AS revised,
    {$this->qualificationDateSelectSql()} AS date_of_qualification,
    {$this->qualifierSelectSql()} AS qualificator_fullname,
    {$officialCodeSelect} AS official_code,
    u.firstname,
    u.lastname,
    u.username
FROM track_e_exercises te
INNER JOIN c_quiz q
    ON q.iid = te.exe_exo_id
INNER JOIN resource_node rn
    ON rn.id = q.resource_node_id
INNER JOIN resource_link rl
    ON rl.resource_node_id = rn.id
INNER JOIN course c
    ON c.id = te.c_id
INNER JOIN user u
    ON u.id = te.exe_user_id
WHERE {$whereSql}
    AND rl.c_id = te.c_id
    AND rl.deleted_at IS NULL
ORDER BY c.title ASC, q.title ASC, te.exe_date DESC
LIMIT :limit
SQL;

        $params['limit'] = self::DEFAULT_LIMIT;
        $types['limit'] = ParameterType::INTEGER;

        $rows = $this->connection->executeQuery($sql, $params, $types)->fetchAllAssociative();
        $items = [];

        foreach ($rows as $row) {
            $items[] = $this->normalizeRow($row);
        }

        return $items;
    }

    private function pendingCorrectionPredicateSql(): string
    {
        return "te.status <> 'incomplete' AND TRIM(te.questions_to_check) <> ''";
    }

    private function existsQualifiedAttemptSql(): string
    {
        return <<<'SQL'
EXISTS (
    SELECT 1
    FROM track_e_attempt_qualify taq
    WHERE taq.exe_id = te.exe_id
      AND taq.author > 0
      AND taq.final = 1
    LIMIT 1
)
SQL;
    }

    private function revisedSelectSql(): string
    {
        return <<<'SQL'
CASE
    WHEN te.status = 'incomplete' THEN -1
    WHEN EXISTS (
        SELECT 1
        FROM track_e_attempt_qualify taq
        WHERE taq.exe_id = te.exe_id
          AND taq.author > 0
      AND taq.final = 1
        LIMIT 1
    ) THEN 1
    ELSE 0
END
SQL;
    }

    private function qualifierSelectSql(): string
    {
        return <<<'SQL'
COALESCE((
    SELECT CONCAT(author.firstname, ' ', author.lastname)
    FROM track_e_attempt_qualify taq
    INNER JOIN user author
        ON author.id = taq.author
    WHERE taq.exe_id = te.exe_id
      AND taq.author > 0
      AND taq.final = 1
    ORDER BY taq.insert_date DESC
    LIMIT 1
), '')
SQL;
    }

    private function qualificationDateSelectSql(): string
    {
        return <<<'SQL'
COALESCE((
    SELECT taq.insert_date
    FROM track_e_attempt_qualify taq
    WHERE taq.exe_id = te.exe_id
      AND taq.author > 0
      AND taq.final = 1
    ORDER BY taq.insert_date DESC
    LIMIT 1
), '')
SQL;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $revised = (int) ($row['revised'] ?? 0);
        $pendingCorrection = 'incomplete' !== (string) ($row['attempt_status'] ?? '')
            && '' !== trim((string) ($row['pending_questions_to_check'] ?? ''));
        $statusLabel = $pendingCorrection ? 'Pending correction' : match ($revised) {
            1 => 'Validated',
            -1 => 'Unclosed',
            default => 'Not validated',
        };
        $durationSeconds = (int) ($row['duration'] ?? 0);
        $durationMinutes = round($durationSeconds / 60, 2);
        $score = (float) ($row['score'] ?? 0);
        $maxScore = (float) ($row['max_score'] ?? 0);
        $courseId = (int) ($row['course_id'] ?? 0);
        $sessionId = (int) ($row['session_id'] ?? 0);
        $exerciseId = (int) ($row['exercise_id'] ?? 0);
        $exerciseResourceNodeId = (int) ($row['exercise_resource_node_id'] ?? 0);
        $attemptId = (int) ($row['attempt_id'] ?? 0);

        return [
            'id' => $attemptId,
            'attemptId' => $attemptId,
            'courseId' => $courseId,
            'courseCode' => (string) ($row['course_code'] ?? ''),
            'courseTitle' => $this->decode((string) ($row['course_title'] ?? '')),
            'exerciseId' => $exerciseId,
            'exerciseResourceNodeId' => $exerciseResourceNodeId,
            'resourceNodeId' => $exerciseResourceNodeId,
            'exerciseTitle' => $this->decode((string) ($row['exercise_title'] ?? '')),
            'userId' => (int) ($row['user_id'] ?? 0),
            'sessionId' => $sessionId,
            'firstName' => (string) ($row['firstname'] ?? ''),
            'lastName' => (string) ($row['lastname'] ?? ''),
            'username' => (string) ($row['username'] ?? ''),
            'officialCode' => (string) ($row['official_code'] ?? ''),
            'duration' => $durationSeconds,
            'durationMinutes' => $durationMinutes,
            'startDate' => $this->formatDateValue($row['start_date'] ?? null),
            'endDate' => $this->formatDateValue($row['end_date'] ?? null),
            'score' => $score,
            'maxScore' => $maxScore,
            'scoreLabel' => $this->formatScore($score).' / '.$this->formatScore($maxScore),
            'userIp' => $this->reportIpVisibility->isExerciseHidden() ? '' : (string) ($row['user_ip'] ?? ''),
            'status' => $revised,
            'statusLabel' => $statusLabel,
            'pendingCorrection' => $pendingCorrection,
            'qualificatorFullName' => (string) ($row['qualificator_fullname'] ?? ''),
            'qualificationDate' => $this->formatDateValue($row['date_of_qualification'] ?? null),
            'resultQuery' => [
                'cid' => $courseId,
                'sid' => $sessionId,
                'gid' => 0,
                'review' => 1,
            ],
        ];
    }

    private function formatDateValue(mixed $value): string
    {
        $date = trim((string) $value);

        if ('' === $date || '0000-00-00 00:00:00' === $date) {
            return '';
        }

        return $date;
    }

    private function formatScore(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    private function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param array<int, int> $allowedCourseIds
     *
     * @return array<int, array<string, int|string>>
     *
     * @throws Exception
     */
    private function getExerciseOptions(int $courseId, array $allowedCourseIds): array
    {
        if ($courseId <= 0) {
            return [];
        }

        if (!$this->isPlatformAdmin() && !\in_array($courseId, $allowedCourseIds, true)) {
            return [];
        }

        $sql = <<<'SQL'
SELECT DISTINCT q.iid, q.title
FROM c_quiz q
INNER JOIN resource_node rn
    ON rn.id = q.resource_node_id
INNER JOIN resource_link rl
    ON rl.resource_node_id = rn.id
WHERE rl.c_id = :courseId
    AND rl.deleted_at IS NULL
ORDER BY q.title ASC
SQL;

        $rows = $this->connection
            ->executeQuery($sql, ['courseId' => $courseId], ['courseId' => ParameterType::INTEGER])
            ->fetchAllAssociative()
        ;
        $items = [];

        foreach ($rows as $row) {
            $exerciseId = (int) ($row['iid'] ?? 0);
            if ($exerciseId <= 0) {
                continue;
            }

            $items[] = [
                'label' => $this->decode((string) ($row['title'] ?? '')),
                'value' => $exerciseId,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function getCourseOptions(bool $includeSessions): array
    {
        $courses = [];

        if ($this->isPlatformAdmin()) {
            if (class_exists('CourseManager')) {
                $courses = CourseManager::get_courses_list();
            }
        } else {
            $courses = $this->getManagedCourses($includeSessions);
        }

        $options = [];
        foreach ($courses as $course) {
            if (!\is_array($course)) {
                continue;
            }

            $courseId = (int) ($course['real_id'] ?? $course['id'] ?? 0);
            if ($courseId <= 0) {
                continue;
            }

            $options[$courseId] = [
                'label' => (string) ($course['title'] ?? ''),
                'value' => $courseId,
            ];
        }

        uasort($options, static fn (array $a, array $b): int => strcasecmp((string) $a['label'], (string) $b['label']));

        return array_values($options);
    }

    /**
     * @return array<int, int>
     */
    private function getAllowedCourseIds(bool $includeSessions): array
    {
        if ($this->isPlatformAdmin()) {
            return [];
        }

        return array_map(
            static fn (array $course): int => (int) $course['real_id'],
            $this->getManagedCourses($includeSessions)
        );
    }

    /**
     * Courses the current user teaches, plus, when sessions are included, the courses they coach.
     * Being subscribed as a student never counts, whatever the platform status of the user.
     *
     * @return array<int, array{real_id: int, title: string}>
     */
    private function getManagedCourses(bool $includeSessions): array
    {
        $userId = $this->getCurrentUserId();
        if ($userId <= 0) {
            return [];
        }

        $sources = [
            'SELECT cru.c_id FROM course_rel_user cru WHERE cru.user_id = :userId AND cru.status = :teacherStatus',
        ];
        $params = [
            'userId' => $userId,
            'teacherStatus' => CourseRelUser::TEACHER,
            'urlId' => \function_exists('api_get_current_access_url_id') ? (int) api_get_current_access_url_id() : 1,
        ];

        if ($includeSessions) {
            $sources[] = 'SELECT srcru.c_id FROM session_rel_course_rel_user srcru
                WHERE srcru.user_id = :userId AND srcru.status = :courseCoach';
            $sources[] = 'SELECT src.c_id FROM session_rel_course src
                INNER JOIN session_rel_user sru ON sru.session_id = src.session_id
                WHERE sru.user_id = :userId AND sru.relation_type = :generalCoach';
            $params['courseCoach'] = Session::COURSE_COACH;
            $params['generalCoach'] = Session::GENERAL_COACH;
        }

        $sql = 'SELECT DISTINCT c.id AS real_id, c.title
            FROM course c
            INNER JOIN access_url_rel_course url ON url.c_id = c.id AND url.access_url_id = :urlId
            WHERE c.id IN ('.implode(' UNION ', $sources).')
            ORDER BY c.title';

        $courses = [];
        foreach ($this->connection->fetchAllAssociative($sql, $params) as $row) {
            $courses[] = ['real_id' => (int) $row['real_id'], 'title' => (string) $row['title']];
        }

        return $courses;
    }

    private function getCurrentUserId(): int
    {
        if (\function_exists('api_get_user_id')) {
            return (int) api_get_user_id();
        }

        $user = $this->security->getUser();
        if (\is_object($user) && method_exists($user, 'getId')) {
            return (int) $user->getId();
        }

        return 0;
    }

    private function getCurrentSessionId(): int
    {
        if (\function_exists('api_get_session_id')) {
            return (int) api_get_session_id();
        }

        return 0;
    }

    private function isPlatformAdmin(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            || (\function_exists('api_is_platform_admin') && api_is_platform_admin());
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 1 === $value || '1' === (string) $value || 'true' === (string) $value;
    }

    /**
     * @throws Exception
     */
    private function tableHasColumn(string $table, string $column): bool
    {
        $rows = $this->connection
            ->executeQuery(\sprintf('SHOW COLUMNS FROM %s LIKE :column', $table), ['column' => $column])
            ->fetchAllAssociative()
        ;

        return [] !== $rows;
    }

    /**
     * @param array<string, int|string> $filters
     */
    private function buildQueryString(array $filters): string
    {
        $params = array_filter($filters, function ($value) {
            return '' !== (string) $value && '0' !== (string) $value;
        });

        return http_build_query($params);
    }
}
