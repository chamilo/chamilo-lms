<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\GlobalReporting;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Security\Authorization\LoginAsAuthorizationChecker;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const PATHINFO_FILENAME;
use const PHP_QUERY_RFC3986;
use const SORT_NUMERIC;

final readonly class GlobalReportingSectionQueryService
{
    private const int USER_STATUS_TEACHER = 1;
    private const int USER_STATUS_HUMAN_RESOURCES = 4;
    private const int USER_STATUS_STUDENT = 5;
    private const int USER_STATUS_STUDENT_BOSS = 17;

    private const int SESSION_RELATION_TYPE_HUMAN_RESOURCES = 1;
    private const int SESSION_STATUS_COURSE_COACH = 2;
    private const int SESSION_RELATION_TYPE_GENERAL_COACH = 3;
    private const int SESSION_RELATION_TYPE_ADMINISTRATOR = 4;

    /**
     * @var string[]
     */
    private const array ADMIN_SECTIONS = [
        'admin-index',
        'admin-coaches',
        'admin-users',
        'admin-sessions',
        'admin-courses',
        'learning-results',
        'session-results',
        'access-overview',
        'exercise-categories',
        'surveys',
        'student-bosses',
        'tutor-planning',
        'question-stats',
        'question-stats-detail',
        'organization',
        'learning-path-authors',
        'learning-path-item-authors',
    ];

    /**
     * @var string[]
     */
    private const array ALLOWED_SECTIONS = [
        'my-progress',
        'learners',
        'learner-detail',
        'teachers',
        'users',
        'courses',
        'sessions',
        'admin-index',
        'admin-users',
        'admin-courses',
        'admin-sessions',
        'admin-coaches',
        'access-overview',
        'exams',
        'current-courses',
        'certificates',
        'company',
        'company-summary',
        'learning-results',
        'session-results',
        'exercise-categories',
        'surveys',
        'student-bosses',
        'tutor-planning',
        'question-stats',
        'question-stats-detail',
        'organization',
        'learning-path-authors',
        'learning-path-item-authors',
        'works-in-session',
    ];

    public function __construct(
        private Connection $connection,
        private GlobalReportingQueryService $dashboardQueryService,
        private CourseRepository $courseRepository,
        private IllustrationRepository $illustrationRepository,
        private UserRepository $userRepository,
        private LoginAsAuthorizationChecker $loginAsAuthorizationChecker,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function getSection(
        GlobalReportingContext $context,
        string $section,
        array $filters,
        bool $forExport = false,
    ): array {
        if (!\in_array($section, self::ALLOWED_SECTIONS, true)) {
            throw new BadRequestHttpException('Unsupported global reporting section.');
        }

        $filters = $this->normalizeFilters($filters, $forExport);
        $this->assertSectionAccess($context, $section);

        $result = match ($section) {
            'my-progress' => $this->getMyProgress($context, $context->currentUserId(), $filters),
            'learner-detail' => $this->getLearnerDetail(
                $context,
                max(0, (int) ($filters['userId'] ?? 0)),
                $filters,
                false,
            ),
            'learners' => $this->getUsers($context, $filters, self::USER_STATUS_STUDENT, 'Learners', true),
            'teachers' => $this->getUsers($context, $filters, self::USER_STATUS_TEACHER, 'Teachers'),
            'student-bosses' => $this->getStudentBossesReport($context, $filters),
            'users' => $this->getUsers(
                $context,
                $filters,
                $filters['status'] > 0 ? (int) $filters['status'] : null,
                $this->followedUsersTitle($filters['status'] > 0 ? (int) $filters['status'] : null),
                true,
                true,
            ),
            'courses' => $this->getTrackedCourses($context, $filters),
            'sessions' => $this->getSessions($context, $filters, false),
            'admin-index' => $this->getAdminIndex($context, $filters),
            'admin-users' => $this->getAdminUsers($context, $filters),
            'admin-courses' => $this->getAdminCourseOverview($context, $filters),
            'admin-sessions' => $this->getSessions($context, $filters, true),
            'admin-coaches' => $this->getCoaches($context, $filters),
            'access-overview' => $this->getAccessOverview($context, $filters),
            'exams' => $this->getExams($context, $filters),
            'current-courses' => $this->getCurrentCourses($context, $filters),
            'certificates' => $this->getCertificates($context, $filters),
            'company' => $this->getCompanyReport($context, $filters),
            'company-summary' => $this->getCompanySummary($context, $filters),
            'learning-results' => $this->getLearningResults($context, $filters, false),
            'session-results' => $this->getLearningResults($context, $filters, true),
            'exercise-categories' => $this->getExerciseCategories($context, $filters),
            'surveys' => $this->getSurveyReport($context, $filters),
            'tutor-planning' => $this->getTutorPlanning($context, $filters),
            'question-stats' => $this->getQuestionStats($context, $filters, false),
            'question-stats-detail' => $this->getQuestionStats($context, $filters, true),
            'organization' => $this->getOrganizationReport($context, $filters),
            'learning-path-authors' => $this->getLearningPathAuthors($context, $filters, false),
            'learning-path-item-authors' => $this->getLearningPathAuthors($context, $filters, true),
            'works-in-session' => $this->getWorksInSession($context, $filters),
        };

        if (\in_array($section, self::ADMIN_SECTIONS, true)) {
            $meta = \is_array($result['meta'] ?? null) ? $result['meta'] : [];
            $meta['adminReports'] = $this->getAvailableAdminReports();
            $meta['activeAdminSection'] = $section;
            $result['meta'] = $meta;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getAdminIndex(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);

        return $this->result(
            $filters,
            'Admin view',
            0,
            [],
            [],
            [],
            [],
            [
                'supportsKeyword' => false,
                'supportsReset' => false,
                'canExportCsv' => false,
                'canExportXlsx' => false,
            ],
        );
    }

    /**
     * @return array<int, array{section: string, label: string, icon: string, badge?: string}>
     */
    private function getAvailableAdminReports(): array
    {
        $reports = [
            ['section' => 'admin-coaches', 'label' => 'Trainers Overview', 'icon' => 'human-male-board'],
            ['section' => 'admin-users', 'label' => 'User overview', 'icon' => 'account'],
            ['section' => 'admin-sessions', 'label' => 'Sessions overview', 'icon' => 'sessions'],
            ['section' => 'admin-courses', 'label' => 'Courses overview', 'icon' => 'courses'],
            [
                'section' => 'learning-results',
                'label' => 'Learning paths exercises results list',
                'icon' => 'learning-paths',
            ],
            [
                'section' => 'session-results',
                'label' => 'Results of learning paths exercises by session',
                'icon' => 'graph',
            ],
            [
                'section' => 'access-overview',
                'label' => 'Accesses by user overview',
                'icon' => 'tracking',
                'badge' => 'Beta',
            ],
            [
                'section' => 'exercise-categories',
                'label' => 'Exercise report by category for all sessions',
                'icon' => 'order-bool-ascending-variant',
            ],
            ['section' => 'surveys', 'label' => 'Surveys report', 'icon' => 'list'],
            ['section' => 'student-bosses', 'label' => "Student's superior follow up", 'icon' => 'account'],
            ['section' => 'tutor-planning', 'label' => 'General tutor planning', 'icon' => 'agenda-plan'],
            ['section' => 'question-stats', 'label' => 'Question stats', 'icon' => 'help'],
            [
                'section' => 'question-stats-detail',
                'label' => 'Detailed questions stats',
                'icon' => 'format-list-bulleted',
            ],
        ];

        if ($this->extraFieldExists('company')) {
            $reports[] = ['section' => 'organization', 'label' => 'User by organization', 'icon' => 'courses'];
        }
        if ($this->extraFieldExists('authors')) {
            $reports[] = [
                'section' => 'learning-path-authors',
                'label' => 'Learning path by author',
                'icon' => 'learning-paths',
            ];
        }
        if ($this->extraFieldExists('authorlpitem')) {
            $reports[] = [
                'section' => 'learning-path-item-authors',
                'label' => 'LP item by author',
                'icon' => 'file-text',
            ];
        }

        return $reports;
    }

    private function extraFieldExists(string $variable): bool
    {
        if (!$this->hasTables(['extra_field']) || !$this->hasColumns('extra_field', ['variable'])) {
            return false;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM extra_field WHERE variable = :variable',
            ['variable' => $variable],
            ['variable' => Types::STRING],
        ) > 0;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getAdminCourseOverview(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $courseIds = $this->allAccessUrlCourseIds($context);
        $meta = [
            'renderMode' => 'course-cards',
            'supportsKeyword' => false,
            'supportsReset' => false,
            'canExportCsv' => true,
            'canExportXlsx' => false,
        ];

        if ([] === $courseIds) {
            return $this->result($filters, 'Courses overview', 0, [], [], [], [], $meta);
        }

        $where = ['course.id IN (:courseIds)'];
        $parameters = ['courseIds' => $courseIds];
        $types = ['courseIds' => ArrayParameterType::INTEGER];
        $keyword = trim((string) $filters['keyword']);
        if ('' !== $keyword) {
            $where[] = '(course.title LIKE :keyword OR course.code LIKE :keyword OR course.visual_code LIKE :keyword)';
            $parameters['keyword'] = '%'.$keyword.'%';
            $types['keyword'] = Types::STRING;
        }

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM course WHERE '.implode(' AND ', $where),
            $parameters,
            $types,
        );
        $rows = $this->connection->fetchAllAssociative(
            'SELECT course.id, course.code, course.visual_code AS visualCode, course.title
               FROM course
              WHERE '.implode(' AND ', $where).'
           ORDER BY course.title ASC, course.id ASC
              LIMIT :limit OFFSET :offset',
            [
                ...$parameters,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                ...$types,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );
        $pageCourseIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $learnerMetrics = $this->getTrackedCourseLearnerMetrics($pageCourseIds, 0);
        $timeMetrics = $this->getAdminCourseTimeMetrics($pageCourseIds);
        $progressMetrics = $this->getAdminCourseProgressMetrics($pageCourseIds);
        $scoreMetrics = $this->getTrackedCourseLearningPathScore($pageCourseIds, 0, $learnerMetrics);
        $testMetrics = $this->getAdminCourseTestMetrics($pageCourseIds);
        $messageMetrics = $this->getTrackedCourseResourceCounts('c_forum_post', $pageCourseIds, 0);
        $assignmentMetrics = $this->getTrackedCourseResourceCounts(
            'c_student_publication',
            $pageCourseIds,
            0,
        );

        foreach ($rows as &$row) {
            $courseId = (int) $row['id'];
            $course = $this->courseRepository->find($courseId);
            $tests = $testMetrics[$courseId] ?? [
                'scoreObtained' => 0.0,
                'scorePossible' => 0.0,
                'questionsAnswered' => 0,
            ];
            $scorePossible = (float) $tests['scorePossible'];
            $scoreObtained = (float) $tests['scoreObtained'];

            $row['id'] = $courseId;
            $row['illustrationUrl'] = null === $course
                ? ''
                : (string) $this->illustrationRepository->getIllustrationUrl(
                    $course,
                    'course_picture_medium',
                );
            $row['learners'] = (int) ($learnerMetrics[$courseId]['learners'] ?? 0);
            $row['timeSeconds'] = (int) ($timeMetrics[$courseId]['timeSeconds'] ?? 0);
            $row['lastAccess'] = $timeMetrics[$courseId]['lastAccess'] ?? null;
            $row['progress'] = round((float) ($progressMetrics[$courseId] ?? 0), 2);
            $row['averageLearningPathScore'] = isset($scoreMetrics[$courseId])
                ? round((float) $scoreMetrics[$courseId], 2)
                : null;
            $row['messages'] = (int) ($messageMetrics[$courseId] ?? 0);
            $row['assignments'] = (int) ($assignmentMetrics[$courseId] ?? 0);
            $row['scoreObtained'] = round($scoreObtained, 2);
            $row['scorePossible'] = round($scorePossible, 2);
            $row['scorePercentage'] = $scorePossible > 0.0
                ? round(100 * $scoreObtained / $scorePossible, 2)
                : null;
            $row['questionsAnswered'] = (int) $tests['questionsAnswered'];
        }
        unset($row);

        return $this->result(
            $filters,
            'Courses overview',
            $total,
            [],
            [],
            $rows,
            [],
            $meta,
        );
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, array{timeSeconds: int, lastAccess: mixed}>
     */
    private function getAdminCourseTimeMetrics(array $courseIds): array
    {
        if ([] === $courseIds || !$this->hasTables(['track_e_course_access'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT access_log.c_id,
                    SUM(CASE
                        WHEN access_log.logout_course_date IS NOT NULL
                         AND access_log.logout_course_date >= access_log.login_course_date
                        THEN TIMESTAMPDIFF(
                            SECOND,
                            access_log.login_course_date,
                            access_log.logout_course_date
                        )
                        ELSE 0
                    END) AS timeSeconds,
                    MAX(access_log.login_course_date) AS lastAccess
               FROM track_e_course_access access_log
               INNER JOIN course_rel_user relation
                   ON relation.c_id = access_log.c_id
                  AND relation.user_id = access_log.user_id
                  AND relation.status = :studentStatus
              WHERE access_log.c_id IN (:courseIds)
                AND COALESCE(access_log.session_id, 0) = 0
           GROUP BY access_log.c_id',
            ['courseIds' => $courseIds, 'studentStatus' => self::USER_STATUS_STUDENT],
            ['courseIds' => ArrayParameterType::INTEGER, 'studentStatus' => Types::INTEGER],
        );

        $metrics = [];
        foreach ($rows as $row) {
            $metrics[(int) $row['c_id']] = [
                'timeSeconds' => (int) $row['timeSeconds'],
                'lastAccess' => $row['lastAccess'],
            ];
        }

        return $metrics;
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, float>
     */
    private function getAdminCourseProgressMetrics(array $courseIds): array
    {
        if ([] === $courseIds || !$this->hasTables(['c_lp_view', 'course_rel_user'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT ranked.c_id, AVG(ranked.progress) AS progress
               FROM (
                    SELECT lp_view.c_id,
                           lp_view.user_id,
                           lp_view.lp_id,
                           COALESCE(lp_view.progress, 0) AS progress,
                           ROW_NUMBER() OVER (
                               PARTITION BY lp_view.c_id, lp_view.user_id, lp_view.lp_id
                               ORDER BY lp_view.view_count DESC, lp_view.iid DESC
                           ) AS rowNumber
                      FROM c_lp_view lp_view
                      INNER JOIN course_rel_user relation
                          ON relation.c_id = lp_view.c_id
                         AND relation.user_id = lp_view.user_id
                         AND relation.status = :studentStatus
                     WHERE lp_view.c_id IN (:courseIds)
                       AND COALESCE(lp_view.session_id, 0) = 0
               ) ranked
              WHERE ranked.rowNumber = 1
           GROUP BY ranked.c_id',
            ['courseIds' => $courseIds, 'studentStatus' => self::USER_STATUS_STUDENT],
            ['courseIds' => ArrayParameterType::INTEGER, 'studentStatus' => Types::INTEGER],
        );

        $metrics = [];
        foreach ($rows as $row) {
            $metrics[(int) $row['c_id']] = round((float) $row['progress'], 2);
        }

        return $metrics;
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, array{scoreObtained: float, scorePossible: float, questionsAnswered: int}>
     */
    private function getAdminCourseTestMetrics(array $courseIds): array
    {
        if ([] === $courseIds || !$this->hasTables(['track_e_exercises', 'course_rel_user'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT exercise.c_id,
                    SUM(COALESCE(exercise.score, 0)) AS scoreObtained,
                    SUM(COALESCE(exercise.max_score, 0)) AS scorePossible,
                    COUNT(exercise.exe_id) AS questionsAnswered
               FROM track_e_exercises exercise
               INNER JOIN course_rel_user relation
                   ON relation.c_id = exercise.c_id
                  AND relation.user_id = exercise.exe_user_id
                  AND relation.status = :studentStatus
              WHERE exercise.c_id IN (:courseIds)
                AND COALESCE(exercise.session_id, 0) = 0
           GROUP BY exercise.c_id',
            ['courseIds' => $courseIds, 'studentStatus' => self::USER_STATUS_STUDENT],
            ['courseIds' => ArrayParameterType::INTEGER, 'studentStatus' => Types::INTEGER],
        );

        $metrics = [];
        foreach ($rows as $row) {
            $metrics[(int) $row['c_id']] = [
                'scoreObtained' => (float) $row['scoreObtained'],
                'scorePossible' => (float) $row['scorePossible'],
                'questionsAnswered' => (int) $row['questionsAnswered'],
            ];
        }

        return $metrics;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getStudentBossesReport(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $where = [
            'boss.status = :bossStatus',
            'boss.active = 1',
            'access_user.access_url_id = :accessUrlId',
        ];
        $parameters = [
            'bossStatus' => self::USER_STATUS_STUDENT_BOSS,
            'accessUrlId' => $context->accessUrlId,
        ];
        $types = [
            'bossStatus' => Types::INTEGER,
            'accessUrlId' => Types::INTEGER,
        ];
        if ('' !== $filters['language']) {
            $where[] = 'boss.locale = :language';
            $parameters['language'] = $filters['language'];
            $types['language'] = Types::STRING;
        }

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT boss.id)
               FROM user boss
               INNER JOIN access_url_rel_user access_user ON access_user.user_id = boss.id
              WHERE '.implode(' AND ', $where),
            $parameters,
            $types,
        );
        $bosses = $this->connection->fetchAllAssociative(
            'SELECT boss.id, boss.firstname, boss.lastname, boss.username, boss.locale
               FROM user boss
               INNER JOIN access_url_rel_user access_user ON access_user.user_id = boss.id
              WHERE '.implode(' AND ', $where).'
           ORDER BY boss.lastname ASC, boss.firstname ASC, boss.id ASC
              LIMIT :limit OFFSET :offset',
            [
                ...$parameters,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                ...$types,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );
        $bossIds = array_map(static fn (array $boss): int => (int) $boss['id'], $bosses);
        $learnersByBoss = [];
        if ([] !== $bossIds) {
            $learnerRows = $this->connection->fetchAllAssociative(
                'SELECT relation.friend_user_id AS bossId,
                        learner.id,
                        learner.firstname,
                        learner.lastname,
                        learner.username,
                        learner.active
                   FROM user_rel_user relation
                   INNER JOIN user learner ON learner.id = relation.user_id
                  WHERE relation.friend_user_id IN (:bossIds)
                    AND relation.relation_type = :relationType
               ORDER BY learner.lastname ASC, learner.firstname ASC, learner.id ASC',
                [
                    'bossIds' => $bossIds,
                    'relationType' => UserRelUser::USER_RELATION_TYPE_BOSS,
                ],
                [
                    'bossIds' => ArrayParameterType::INTEGER,
                    'relationType' => Types::INTEGER,
                ],
            );
            foreach ($learnerRows as $learner) {
                $bossId = (int) $learner['bossId'];
                $learnersByBoss[$bossId][] = [
                    'id' => (int) $learner['id'],
                    'fullName' => trim((string) $learner['firstname'].' '.(string) $learner['lastname']),
                    'username' => (string) $learner['username'],
                    'active' => 1 === (int) $learner['active'],
                ];
            }
        }

        foreach ($bosses as &$boss) {
            $bossId = (int) $boss['id'];
            $boss['id'] = $bossId;
            $boss['fullName'] = trim((string) $boss['firstname'].' '.(string) $boss['lastname']);
            $boss['learners'] = $learnersByBoss[$bossId] ?? [];
            $boss['addLearnerUrl'] = '/main/my_space/tc_report.php?a=add_user&boss_id='.$bossId;
        }
        unset($boss);

        $languageRows = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT boss.locale
               FROM user boss
               INNER JOIN access_url_rel_user access_user ON access_user.user_id = boss.id
              WHERE boss.status = :bossStatus
                AND boss.active = 1
                AND access_user.access_url_id = :accessUrlId
                AND boss.locale IS NOT NULL
                AND boss.locale <> :emptyLocale
           ORDER BY boss.locale ASC',
            [
                'bossStatus' => self::USER_STATUS_STUDENT_BOSS,
                'accessUrlId' => $context->accessUrlId,
                'emptyLocale' => '',
            ],
            [
                'bossStatus' => Types::INTEGER,
                'accessUrlId' => Types::INTEGER,
                'emptyLocale' => Types::STRING,
            ],
        );

        return $this->result(
            $filters,
            "Student's superior follow up",
            $total,
            [],
            [],
            $bosses,
            [],
            [
                'renderMode' => 'student-boss-cards',
                'supportsKeyword' => false,
                'supportsLanguage' => true,
                'languageOptions' => array_values(array_map('strval', $languageRows)),
                'canExportCsv' => false,
                'canExportXlsx' => false,
            ],
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getUsers(
        GlobalReportingContext $context,
        array $filters,
        ?int $status,
        string $title,
        bool $showDetails = false,
        bool $supportsUserStatus = false,
    ): array {
        $userIds = $this->dashboardQueryService->getScopedUserIds($context, $status);
        $meta = [
            'supportsKeyword' => true,
            'supportsUserStatus' => $supportsUserStatus && self::USER_STATUS_STUDENT_BOSS !== $status,
            'supportsActive' => true,
            'supportsInactiveDays' => true,
            'canExportCsv' => true,
            'canExportXlsx' => true,
            'userStatus' => $status,
        ];

        if ([] === $userIds) {
            return $this->emptyResult(
                $filters,
                $title,
                $this->userColumns($showDetails, $context->showEmailAddresses),
                $meta,
            );
        }

        $where = ['usr.id IN (:userIds)'];
        $parameters = ['userIds' => $userIds];
        $types = ['userIds' => ArrayParameterType::INTEGER];
        $keyword = trim((string) $filters['keyword']);
        if ('' !== $keyword) {
            $where[] = '(usr.firstname LIKE :keyword OR usr.lastname LIKE :keyword OR usr.username LIKE :keyword OR usr.official_code LIKE :keyword)';
            $parameters['keyword'] = '%'.$keyword.'%';
        }
        if (null !== $filters['active']) {
            $where[] = 'usr.active = :active';
            $parameters['active'] = (int) $filters['active'];
            $types['active'] = Types::INTEGER;
        }
        if ($filters['sleepingDays'] > 0) {
            $where[] = '(usr.last_login IS NULL OR usr.last_login <= :inactiveLimit)';
            $parameters['inactiveLimit'] = new DateTimeImmutable('-'.$filters['sleepingDays'].' days', new DateTimeZone('UTC'));
            $types['inactiveLimit'] = Types::DATETIME_IMMUTABLE;
        }

        $sortMap = [
            'officialCode' => 'usr.official_code',
            'lastname' => 'usr.lastname',
            'firstname' => 'usr.firstname',
            'username' => 'usr.username',
            'lastLogin' => 'usr.last_login',
            'courseCount' => 'courseCount',
            'sessionCount' => 'sessionCount',
        ];
        $sort = $sortMap[(string) $filters['sort']] ?? 'usr.lastname';
        $direction = $this->direction((string) $filters['direction']);

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT usr.id) FROM user usr WHERE '.implode(' AND ', $where),
            $parameters,
            $types,
        );

        $rows = $this->connection->fetchAllAssociative(
            'SELECT usr.id,
                    usr.official_code AS officialCode,
                    usr.firstname,
                    usr.lastname,
                    usr.username,
                    usr.email,
                    usr.status,
                    usr.active,
                    usr.last_login AS lastLogin,
                    (
                        SELECT COUNT(DISTINCT subscription.c_id)
                          FROM course_rel_user subscription
                         WHERE subscription.user_id = usr.id
                    ) AS courseCount,
                    (
                        SELECT COUNT(DISTINCT subscription.session_id)
                          FROM session_rel_course_rel_user subscription
                         WHERE subscription.user_id = usr.id
                    ) AS sessionCount,
                    COALESCE((
                        SELECT SUM(TIMESTAMPDIFF(SECOND, login.login_date, COALESCE(login.logout_date, login.login_date)))
                          FROM track_e_login login
                         WHERE login.login_user_id = usr.id
                    ), 0) AS timeSeconds
               FROM user usr
              WHERE '.implode(' AND ', $where).'
           ORDER BY '.$sort.' '.$direction.', usr.id ASC
              LIMIT :limit OFFSET :offset',
            [
                ...$parameters,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                ...$types,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['status'] = $this->statusLabel((int) $row['status']);
            $row['active'] = 1 === (int) $row['active'] ? 'Active' : 'Inactive';
            $row['courseCount'] = (int) $row['courseCount'];
            $row['sessionCount'] = (int) $row['sessionCount'];
            $row['timeSeconds'] = (int) $row['timeSeconds'];
        }
        unset($row);
        if (!$context->showEmailAddresses) {
            $this->removeEmailFromRows($rows);
        }

        return $this->result(
            $filters,
            $title,
            $total,
            [
                ['key' => 'users', 'label' => $title, 'value' => $total],
                ['key' => 'active', 'label' => 'Active', 'value' => $this->countActiveRows($rows)],
            ],
            $this->userColumns($showDetails, $context->showEmailAddresses),
            $rows,
            [],
            $meta,
        );
    }

    /**
     * @return array<int, array<string, string|bool>>
     */
    private function userColumns(bool $showDetails, bool $showEmailAddresses = false): array
    {
        $columns = [
            ['key' => 'officialCode', 'label' => 'Code', 'type' => 'text', 'sortable' => true],
            ['key' => 'lastname', 'label' => 'Last name', 'type' => 'text', 'sortable' => true],
            ['key' => 'firstname', 'label' => 'First name', 'type' => 'text', 'sortable' => true],
            ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
            ['key' => 'active', 'label' => 'Active', 'type' => 'status'],
            ['key' => 'courseCount', 'label' => 'Courses', 'type' => 'number', 'sortable' => true],
            ['key' => 'sessionCount', 'label' => 'Sessions', 'type' => 'number', 'sortable' => true],
            ['key' => 'timeSeconds', 'label' => 'Time', 'type' => 'duration'],
            ['key' => 'lastLogin', 'label' => 'Latest access', 'type' => 'datetime', 'sortable' => true],
        ];

        if ($showEmailAddresses) {
            array_splice(
                $columns,
                4,
                0,
                [['key' => 'email', 'label' => 'E-mail', 'type' => 'text']],
            );
        }

        if ($showDetails) {
            $columns[] = ['key' => 'actions', 'label' => 'Details', 'type' => 'learner-detail'];
        }

        return $columns;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getMyProgress(
        GlobalReportingContext $context,
        int $userId,
        array $filters,
    ): array {
        if ($userId <= 0) {
            throw new AccessDeniedHttpException('The requested learner is outside your reporting scope.');
        }

        $user = $this->connection->fetchAssociative(
            'SELECT id, official_code AS officialCode, firstname, lastname, username, email, status, active, last_login AS lastLogin
               FROM user
              WHERE id = :userId',
            ['userId' => $userId],
            ['userId' => Types::INTEGER],
        );
        if (false === $user) {
            throw new BadRequestHttpException('The requested learner does not exist.');
        }

        $sessionId = max(0, (int) $filters['sessionId']);
        $where = [$sessionId > 0 ? 'session_subscription.id IS NOT NULL' : 'base_subscription.id IS NOT NULL'];
        $parameters = [
            'userId' => $userId,
            'sessionId' => $sessionId,
            'accessUrlId' => $context->accessUrlId,
        ];
        $types = [
            'userId' => Types::INTEGER,
            'sessionId' => Types::INTEGER,
            'accessUrlId' => Types::INTEGER,
        ];
        $keyword = trim((string) $filters['keyword']);
        if ('' !== $keyword) {
            $where[] = '(course.title LIKE :keyword OR course.code LIKE :keyword)';
            $parameters['keyword'] = '%'.$keyword.'%';
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT course.id,
                    course.resource_node_id AS resourceNodeId,
                    course.code,
                    course.title,
                    COALESCE(access_metrics.timeSeconds, 0) AS timeSeconds,
                    access_metrics.firstAccess,
                    access_metrics.lastAccess
               FROM course
               INNER JOIN access_url_rel_course access_course
                   ON access_course.c_id = course.id
                  AND access_course.access_url_id = :accessUrlId
               LEFT JOIN course_rel_user base_subscription
                   ON base_subscription.c_id = course.id
                  AND base_subscription.user_id = :userId
                  AND base_subscription.relation_type <> 1
               LEFT JOIN session_rel_course_rel_user session_subscription
                   ON session_subscription.c_id = course.id
                  AND session_subscription.user_id = :userId
                  AND session_subscription.session_id = :sessionId
               LEFT JOIN (
                    SELECT c_id,
                           SUM(
                               CASE
                                   WHEN logout_course_date IS NOT NULL
                                    AND logout_course_date >= login_course_date
                                   THEN TIMESTAMPDIFF(SECOND, login_course_date, logout_course_date)
                                   ELSE 0
                               END
                           ) AS timeSeconds,
                           MIN(login_course_date) AS firstAccess,
                           MAX(logout_course_date) AS lastAccess
                      FROM track_e_course_access
                     WHERE user_id = :userId
                       AND COALESCE(session_id, 0) = :sessionId
                  GROUP BY c_id
               ) access_metrics ON access_metrics.c_id = course.id
              WHERE '.implode(' AND ', $where).'
           GROUP BY course.id, course.resource_node_id, course.code, course.title,
                    access_metrics.timeSeconds, access_metrics.firstAccess, access_metrics.lastAccess
           ORDER BY course.title ASC',
            $parameters,
            $types,
        );

        $courseIds = array_values(array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows,
        ));
        $progressByCourse = $this->learningPathProgressByCourse($userId, $courseIds, $sessionId);
        $learningPathScoreByCourse = $this->learningPathScoreByCourse($userId, $courseIds, $sessionId);
        $outsideLearningPathScoreByCourse = $this->outsideLearningPathScoreByCourse($userId, $courseIds, $sessionId);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        foreach ($rows as &$row) {
            $courseId = (int) $row['id'];
            $resourceNodeId = (int) ($row['resourceNodeId'] ?? 0);
            $lastAccess = null === $row['lastAccess'] ? null : (string) $row['lastAccess'];
            $lastAccessIsStale = false;
            if (null !== $lastAccess && '' !== $lastAccess) {
                $lastAccessDate = DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i:s',
                    $lastAccess,
                    new DateTimeZone('UTC'),
                );
                $lastAccessIsStale = false !== $lastAccessDate && $lastAccessDate < $now->modify('-7 days');
            }

            $row['id'] = $courseId;
            $row['resourceNodeId'] = $resourceNodeId;
            $row['sessionId'] = $sessionId;
            $row['timeSeconds'] = (int) $row['timeSeconds'];
            $row['learningPathProgress'] = round((float) ($progressByCourse[$courseId] ?? 0), 1);
            $row['bestScoreInLearningPaths'] = $learningPathScoreByCourse[$courseId] ?? null;
            $row['bestScoreOutsideLearningPaths'] = $outsideLearningPathScoreByCourse[$courseId] ?? null;
            $row['lastAccessIsStale'] = $lastAccessIsStale;
            $row['inactiveReminderUrl'] = null;

            if ($lastAccessIsStale && $context->canViewGlobalReports && $resourceNodeId > 0) {
                $row['inactiveReminderUrl'] = '/resources/announcement/'.$resourceNodeId.'/add?'.http_build_query([
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'remind_inactive' => $userId,
                ]);
            }
        }
        unset($row);

        $total = \count($rows);
        $selectedCourseId = max(0, (int) $filters['courseId']);
        $selectedCourse = null;
        foreach ($rows as $row) {
            if ($selectedCourseId === (int) $row['id']) {
                $selectedCourse = [
                    'id' => (int) $row['id'],
                    'title' => (string) $row['title'],
                    'code' => (string) $row['code'],
                    'sessionId' => $sessionId,
                ];

                break;
            }
        }
        if (null === $selectedCourse) {
            $selectedCourseId = 0;
        }

        $summary = [];
        $columns = [
            ['key' => 'title', 'label' => 'Course', 'type' => 'text'],
            ['key' => 'timeSeconds', 'label' => 'Time spent in the course', 'type' => 'duration'],
            ['key' => 'learningPathProgress', 'label' => 'Progress', 'type' => 'percent'],
            [
                'key' => 'bestScoreInLearningPaths',
                'label' => 'Best score in learning path',
                'type' => 'nullable-percent',
                'help' => 'Average of all learners in all courses',
            ],
            [
                'key' => 'bestScoreOutsideLearningPaths',
                'label' => 'Best score not in learning path',
                'type' => 'nullable-percent',
            ],
            ['key' => 'lastAccess', 'label' => 'Latest login', 'type' => 'last-access-status'],
            ['key' => 'details', 'label' => 'Details', 'type' => 'course-detail'],
        ];

        return $this->result(
            $filters,
            'My courses',
            $total,
            $summary,
            $columns,
            \array_slice($rows, $filters['offset'], $filters['itemsPerPage']),
            $selectedCourseId > 0
                ? $this->courseDetailSections($userId, $selectedCourseId, $sessionId)
                : [],
            [
                'user' => [
                    'id' => (int) $user['id'],
                    'officialCode' => (string) $user['officialCode'],
                    'firstname' => (string) $user['firstname'],
                    'lastname' => (string) $user['lastname'],
                    'username' => (string) $user['username'],
                    ...($context->showEmailAddresses ? ['email' => (string) $user['email']] : []),
                    'lastLogin' => $user['lastLogin'],
                ],
                'isSelfProgress' => true,
                'selectedCourse' => $selectedCourse,
                'supportsKeyword' => true,
                'canExportCsv' => true,
                'canExportXlsx' => true,
            ],
        );
    }

    private function getLearnerDetail(
        GlobalReportingContext $context,
        int $userId,
        array $filters,
        bool $selfProgress,
    ): array {
        if ($userId <= 0 || (!$selfProgress && !$this->dashboardQueryService->isUserInScope($context, $userId))) {
            throw new AccessDeniedHttpException('The requested learner is outside your reporting scope.');
        }

        $user = $this->connection->fetchAssociative(
            'SELECT id,
                    official_code AS officialCode,
                    firstname,
                    lastname,
                    username,
                    email,
                    phone,
                    timezone,
                    status,
                    active,
                    last_login AS lastLogin
               FROM user
              WHERE id = :userId',
            ['userId' => $userId],
            ['userId' => Types::INTEGER],
        );
        if (false === $user) {
            throw new BadRequestHttpException('The requested learner does not exist.');
        }

        $detailWhere = ['(base_subscription.id IS NOT NULL OR session_subscription.id IS NOT NULL)'];
        $detailParameters = ['userId' => $userId];
        $detailTypes = ['userId' => Types::INTEGER];
        $sessionMetricFilter = '';
        $resourceSessionFilter = '';

        if ($filters['courseId'] > 0) {
            $detailWhere[] = 'course.id = :courseId';
            $detailParameters['courseId'] = $filters['courseId'];
            $detailTypes['courseId'] = Types::INTEGER;
        }
        if ($filters['sessionId'] > 0) {
            $detailWhere[] = 'session_subscription.session_id = :sessionId';
            $detailParameters['sessionId'] = $filters['sessionId'];
            $detailTypes['sessionId'] = Types::INTEGER;
            $sessionMetricFilter = ' AND session_id = :sessionId';
            $resourceSessionFilter = ' AND resource_link.session_id = :sessionId';
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT course.id,
                    course.resource_node_id AS resourceNodeId,
                    course.code,
                    course.title,
                    COALESCE(session_subscription.session_id, 0) AS sessionId,
                    COALESCE(base_subscription.progress, session_subscription.progress, 0) AS subscriptionProgress,
                    COALESCE(access_metrics.timeSeconds, 0) AS timeSeconds,
                    access_metrics.firstAccess,
                    access_metrics.lastAccess,
                    COALESCE(lp_metrics.learningPathProgress, 0) AS learningPathProgress,
                    COALESCE(exercise_metrics.exerciseAverage, 0) AS score,
                    COALESCE(exercise_metrics.attempts, 0) AS attempts,
                    COALESCE(work_metrics.assignments, 0) AS assignments,
                    COALESCE(forum_metrics.forumPosts, 0) AS forumPosts
               FROM course
               LEFT JOIN course_rel_user base_subscription
                   ON base_subscription.c_id = course.id
                  AND base_subscription.user_id = :userId
               LEFT JOIN session_rel_course_rel_user session_subscription
                   ON session_subscription.c_id = course.id
                  AND session_subscription.user_id = :userId
               LEFT JOIN (
                    SELECT c_id,
                           SUM(
                               CASE
                                   WHEN logout_course_date IS NOT NULL
                                    AND logout_course_date >= login_course_date
                                   THEN TIMESTAMPDIFF(SECOND, login_course_date, logout_course_date)
                                   ELSE 0
                               END
                           ) AS timeSeconds,
                           MIN(login_course_date) AS firstAccess,
                           MAX(COALESCE(logout_course_date, login_course_date)) AS lastAccess
                      FROM track_e_course_access
                     WHERE user_id = :userId'.$sessionMetricFilter.'
                  GROUP BY c_id
               ) access_metrics ON access_metrics.c_id = course.id
               LEFT JOIN (
                    SELECT c_id, AVG(progress) AS learningPathProgress
                      FROM c_lp_view
                     WHERE user_id = :userId'.$sessionMetricFilter.'
                  GROUP BY c_id
               ) lp_metrics ON lp_metrics.c_id = course.id
               LEFT JOIN (
                    SELECT c_id,
                           COUNT(*) AS attempts,
                           AVG(CASE WHEN max_score > 0 THEN score * 100 / max_score ELSE 0 END) AS exerciseAverage
                      FROM track_e_exercises
                     WHERE exe_user_id = :userId'.$sessionMetricFilter.'
                  GROUP BY c_id
               ) exercise_metrics ON exercise_metrics.c_id = course.id
               LEFT JOIN (
                    SELECT resource_link.c_id, COUNT(publication.iid) AS assignments
                      FROM c_student_publication publication
                      INNER JOIN resource_link ON resource_link.resource_node_id = publication.resource_node_id
                     WHERE publication.user_id = :userId
                       AND publication.parent_id IS NOT NULL
                       AND resource_link.deleted_at IS NULL'.$resourceSessionFilter.'
                  GROUP BY resource_link.c_id
               ) work_metrics ON work_metrics.c_id = course.id
               LEFT JOIN (
                    SELECT resource_link.c_id, COUNT(post.iid) AS forumPosts
                      FROM c_forum_post post
                      INNER JOIN resource_link ON resource_link.resource_node_id = post.resource_node_id
                     WHERE post.poster_id = :userId
                       AND post.visible = 1
                       AND resource_link.deleted_at IS NULL'.$resourceSessionFilter.'
                  GROUP BY resource_link.c_id
               ) forum_metrics ON forum_metrics.c_id = course.id
              WHERE '.implode(' AND ', $detailWhere).'
           GROUP BY course.id,
                    course.resource_node_id,
                    course.code,
                    course.title,
                    session_subscription.session_id,
                    base_subscription.progress,
                    session_subscription.progress,
                    access_metrics.timeSeconds,
                    access_metrics.firstAccess,
                    access_metrics.lastAccess,
                    lp_metrics.learningPathProgress,
                    exercise_metrics.exerciseAverage,
                    exercise_metrics.attempts,
                    work_metrics.assignments,
                    forum_metrics.forumPosts
           ORDER BY course.title ASC',
            $detailParameters,
            $detailTypes,
        );

        $totalTime = 0;
        $progressSum = 0.0;
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['resourceNodeId'] = (int) ($row['resourceNodeId'] ?? 0);
            $row['sessionId'] = (int) $row['sessionId'];
            $row['subscriptionProgress'] = round((float) $row['subscriptionProgress'], 2);
            $row['timeSeconds'] = (int) $row['timeSeconds'];
            $row['learningPathProgress'] = round((float) $row['learningPathProgress'], 2);
            $row['score'] = round((float) $row['score'], 2);
            $row['attempts'] = (int) $row['attempts'];
            $row['assignments'] = (int) $row['assignments'];
            $row['forumPosts'] = (int) $row['forumPosts'];
            $row['absences'] = null;
            $row['gradebook'] = null;
            $totalTime += $row['timeSeconds'];
            $progressSum += $row['learningPathProgress'];
        }
        unset($row);

        $platformConnections = $this->getLearnerPlatformConnections($userId, $context->accessUrlId);
        $fullName = trim((string) $user['firstname'].' '.(string) $user['lastname']);
        $pictureUrl = (string) $this->userRepository->getUserPicture(
            $userId,
            UserRepository::USER_IMAGE_SIZE_BIG,
            false,
        );
        $showTimezone = $this->getBooleanSetting($context, 'use_users_timezone');
        $isCurrentUserBoss = $this->isCurrentUserBossOf($context->currentUserId(), $userId);
        $canManageLearner = $context->isAdministrator
            || $context->isHumanResourcesManager
            || $isCurrentUserBoss;
        $legal = $this->getLearnerLegalData(
            $context,
            $userId,
            $context->isAdministrator || $isCurrentUserBoss,
        );
        $targetUser = $this->userRepository->find($userId);
        $loginAsUrl = null;
        if (
            $targetUser instanceof User
            && $this->loginAsAuthorizationChecker->canLoginAs($context->currentUser, $targetUser)
        ) {
            $loginAsUrl = '/admin/user-list-login-as?user_id='.$userId.'&sec_token='.
                rawurlencode($this->csrfTokenManager->getToken('login_as')->getValue());
        }
        $courseCount = \count($rows);
        $courseDetail = null;
        $courseDetailSections = [];
        if ((int) $filters['courseId'] > 0) {
            if ([] === $rows) {
                throw new AccessDeniedHttpException('The requested course is outside the learner reporting scope.');
            }

            $courseRow = $rows[0];
            $courseDetail = $this->getLearnerCourseDetailMeta(
                $context,
                $userId,
                $courseRow,
                $canManageLearner,
            );
            $courseDetailSections = $this->getLearnerCourseDetailSections(
                $userId,
                (int) $courseRow['id'],
                (int) $courseRow['sessionId'],
                $canManageLearner,
            );
        }

        return $this->result(
            $filters,
            $selfProgress ? 'View my progress' : 'Learner details',
            $courseCount,
            [],
            [
                ['key' => 'title', 'label' => 'Course', 'type' => 'text'],
                ['key' => 'timeSeconds', 'label' => 'Time', 'type' => 'duration'],
                ['key' => 'learningPathProgress', 'label' => 'Progress', 'type' => 'percent'],
                ['key' => 'score', 'label' => 'Score', 'type' => 'percent'],
                ['key' => 'absences', 'label' => 'Absences', 'type' => 'text'],
                ['key' => 'gradebook', 'label' => 'Gradebooks', 'type' => 'text'],
                ['key' => 'details', 'label' => 'Details', 'type' => 'course-reporting-detail'],
            ],
            $rows,
            $courseDetailSections,
            [
                'user' => [
                    'id' => (int) $user['id'],
                    'officialCode' => (string) $user['officialCode'],
                    'firstname' => (string) $user['firstname'],
                    'lastname' => (string) $user['lastname'],
                    'fullName' => $fullName,
                    'username' => (string) $user['username'],
                    'phone' => (string) ($user['phone'] ?? ''),
                    'timezone' => $showTimezone ? (string) ($user['timezone'] ?? '') : '',
                    'status' => $this->statusLabel((int) $user['status']),
                    'active' => 1 === (int) $user['active'],
                    'online' => $platformConnections['online'],
                    'pictureUrl' => $pictureUrl,
                    'firstLogin' => $platformConnections['firstLogin'],
                    'lastLogin' => $platformConnections['lastLogin'] ?? $user['lastLogin'],
                    ...($context->showEmailAddresses ? ['email' => (string) $user['email']] : []),
                ],
                'course' => $courseDetail,
                'bosses' => $this->getLearnerBosses($userId),
                'skills' => $this->getLearnerSkills($context, $userId),
                'legal' => $legal,
                'certificate' => [
                    'canGenerate' => $canManageLearner,
                    'actionUrl' => null === $courseDetail
                        ? '/main/my_space/myStudents.php?action=generate_certificate&student='.$userId
                        : '/main/my_space/myStudents.php?'.http_build_query([
                            'action' => 'generate_certificate',
                            'student' => $userId,
                            'cid' => (int) $courseDetail['id'],
                            'course' => (string) $courseDetail['code'],
                        ]),
                ],
                'actions' => [
                    'accessDetailsUrl' => '/main/my_space/access_details_session.php?user_id='.$userId,
                    'emailUrl' => $context->showEmailAddresses && '' !== (string) $user['email']
                        ? 'mailto:'.(string) $user['email']
                        : null,
                    'loginAsUrl' => $loginAsUrl,
                    'assignSkillUrl' => $canManageLearner ? '/main/skills/assign.php?user='.$userId : null,
                    'attendanceUrl' => $canManageLearner
                        ? '/main/my_space/myStudents.php?action=all_attendance&student='.$userId
                        : null,
                    'blogUrl' => $context->studentFollowUpEnabled && $canManageLearner
                        ? '/plugin/StudentFollowUp/posts.php?student_id='.$userId
                        : null,
                ],
                'totals' => [
                    'courses' => $courseCount,
                    'timeSeconds' => $totalTime,
                    'progress' => $courseCount > 0 ? round($progressSum / $courseCount, 2) : 0,
                ],
                'supportsKeyword' => false,
                'canExportCsv' => true,
                'canExportXlsx' => true,
            ],
        );
    }

    /**
     * @return array{firstLogin: ?string, lastLogin: ?string, online: bool}
     */
    private function getLearnerPlatformConnections(int $userId, int $accessUrlId): array
    {
        $connections = $this->connection->fetchAssociative(
            'SELECT MIN(login_date) AS firstLogin,
                    MAX(COALESCE(logout_date, login_date)) AS lastLogin
               FROM track_e_login
              WHERE login_user_id = :userId',
            ['userId' => $userId],
            ['userId' => Types::INTEGER],
        );
        $online = false;
        if ($this->hasTables(['track_e_online'])) {
            $online = (int) $this->connection->fetchOne(
                'SELECT COUNT(*)
                   FROM track_e_online
                  WHERE login_user_id = :userId
                    AND access_url_id = :accessUrlId
                    AND login_date >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 5 MINUTE)',
                ['userId' => $userId, 'accessUrlId' => $accessUrlId],
                ['userId' => Types::INTEGER, 'accessUrlId' => Types::INTEGER],
            ) > 0;
        }

        $firstLogin = false === $connections || null === ($connections['firstLogin'] ?? null)
            ? null
            : (string) $connections['firstLogin'];
        $lastLogin = false === $connections || null === ($connections['lastLogin'] ?? null)
            ? null
            : (string) $connections['lastLogin'];

        return [
            'firstLogin' => $firstLogin,
            'lastLogin' => $lastLogin,
            'online' => $online,
        ];
    }

    /**
     * @return array<int, array{id: int, fullName: string, username: string}>
     */
    private function getLearnerBosses(int $userId): array
    {
        if (!$this->hasTables(['user_rel_user'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT boss.id, boss.firstname, boss.lastname, boss.username
               FROM user_rel_user relation_table
               INNER JOIN user boss ON boss.id = relation_table.friend_user_id
              WHERE relation_table.user_id = :userId
                AND relation_table.relation_type = :relationType
           ORDER BY boss.lastname, boss.firstname, boss.id',
            [
                'userId' => $userId,
                'relationType' => UserRelUser::USER_RELATION_TYPE_BOSS,
            ],
            [
                'userId' => Types::INTEGER,
                'relationType' => Types::INTEGER,
            ],
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'fullName' => trim((string) $row['firstname'].' '.(string) $row['lastname']),
                'username' => (string) $row['username'],
            ],
            $rows,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLearnerSkills(GlobalReportingContext $context, int $userId): array
    {
        $isOwnProfile = $context->currentUserId() === $userId;
        $canViewSkills = $isOwnProfile
            || $context->isAdministrator
            || $context->isHumanResourcesManager
            || $context->isStudentBoss
            || $context->allowTeacherAccessStudentSkills;
        if (!$canViewSkills) {
            return [];
        }

        if (
            !$this->hasTables(['skill', 'skill_rel_user'])
            || !$this->hasColumns('skill_rel_user', ['user_id', 'skill_id'])
        ) {
            return [];
        }

        return $this->connection->fetchAllAssociative(
            'SELECT skill.id,
                    skill.title,
                    skill.short_code AS shortCode,
                    skill.icon
               FROM skill_rel_user relation_table
               INNER JOIN skill ON skill.id = relation_table.skill_id
              WHERE relation_table.user_id = :userId
                AND skill.access_url_id = :accessUrlId
                AND skill.status = 1
           ORDER BY skill.title, skill.id',
            ['userId' => $userId, 'accessUrlId' => $context->accessUrlId],
            ['userId' => Types::INTEGER, 'accessUrlId' => Types::INTEGER],
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getLearnerLegalData(
        GlobalReportingContext $context,
        int $userId,
        bool $canManageLearner,
    ): ?array {
        if (!$this->getBooleanSetting($context, 'allow_terms_conditions')) {
            return null;
        }

        $value = '';
        if (
            $this->hasTables(['extra_field', 'extra_field_values'])
            && $this->hasColumns('extra_field_values', ['field_id', 'item_id'])
        ) {
            $valueColumn = '';
            if ($this->hasColumns('extra_field_values', ['field_value'])) {
                $valueColumn = 'field_value';
            } elseif ($this->hasColumns('extra_field_values', ['value'])) {
                $valueColumn = 'value';
            }

            if ('' !== $valueColumn) {
                $value = (string) ($this->connection->fetchOne(
                    'SELECT values_table.'.$valueColumn.'
                       FROM extra_field_values values_table
                       INNER JOIN extra_field field ON field.id = values_table.field_id
                      WHERE values_table.item_id = :userId
                        AND field.item_type = 1
                        AND field.variable = :variable
                   ORDER BY values_table.id DESC
                      LIMIT 1',
                    ['userId' => $userId, 'variable' => 'legal_accept'],
                    ['userId' => Types::INTEGER],
                ) ?: '');
            }
        }

        $acceptedAt = null;
        if ('' !== $value) {
            $parts = explode(':', $value, 3);
            $acceptedAt = $parts[2] ?? null;
        }
        $accepted = '' !== $value;

        return [
            'accepted' => $accepted,
            'acceptedAt' => $acceptedAt,
            'canManage' => $canManageLearner,
            'actionLabel' => $accepted ? 'Delete legal agreement' : 'Send legal agreement',
            'actionUrl' => $canManageLearner
                ? '/main/my_space/myStudents.php?action='.($accepted ? 'delete_legal' : 'send_legal').'&student='.$userId
                : null,
        ];
    }

    private function isCurrentUserBossOf(int $bossId, int $studentId): bool
    {
        if (!$this->hasTables(['user_rel_user'])) {
            return false;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
               FROM user_rel_user
              WHERE user_id = :studentId
                AND friend_user_id = :bossId
                AND relation_type = :relationType',
            [
                'studentId' => $studentId,
                'bossId' => $bossId,
                'relationType' => UserRelUser::USER_RELATION_TYPE_BOSS,
            ],
            [
                'studentId' => Types::INTEGER,
                'bossId' => Types::INTEGER,
                'relationType' => Types::INTEGER,
            ],
        ) > 0;
    }

    private function getBooleanSetting(GlobalReportingContext $context, string $variable): bool
    {
        if (!$this->hasTables(['settings'])) {
            return false;
        }

        $value = $this->connection->fetchOne(
            'SELECT selected_value
               FROM settings
              WHERE variable = :variable
                AND (access_url = :accessUrlId OR access_url IS NULL)
           ORDER BY CASE WHEN access_url = :accessUrlId THEN 0 ELSE 1 END, id DESC
              LIMIT 1',
            ['variable' => $variable, 'accessUrlId' => $context->accessUrlId],
            ['accessUrlId' => Types::INTEGER],
        );
        if (false === $value || null === $value) {
            return false;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, float>
     */
    private function learningPathProgressByCourse(int $userId, array $courseIds, int $sessionId): array
    {
        if ([] === $courseIds || !$this->hasTables(['c_lp', 'resource_link', 'c_lp_view'])) {
            return [];
        }

        $sessionFilter = $sessionId > 0
            ? '(resource_link.session_id IS NULL OR resource_link.session_id = 0 OR resource_link.session_id = :sessionId)'
            : '(resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $rows = $this->connection->fetchAllAssociative(
            'SELECT course_lps.courseId,
                    ROUND(
                        SUM(COALESCE(user_view.progress, 0)) / NULLIF(COUNT(course_lps.learningPathId), 0),
                        1
                    ) AS progress
               FROM (
                    SELECT DISTINCT resource_link.c_id AS courseId, learning_path.iid AS learningPathId
                      FROM c_lp learning_path
                      INNER JOIN resource_link ON resource_link.resource_node_id = learning_path.resource_node_id
                     WHERE resource_link.c_id IN (:courseIds)
                       AND resource_link.deleted_at IS NULL
                       AND '.$sessionFilter.'
               ) course_lps
               LEFT JOIN (
                    SELECT view.c_id AS courseId, view.lp_id AS learningPathId, MAX(view.progress) AS progress
                      FROM c_lp_view view
                     WHERE view.user_id = :userId
                       AND COALESCE(view.session_id, 0) = :sessionId
                       AND view.c_id IN (:courseIds)
                  GROUP BY view.c_id, view.lp_id
               ) user_view
                 ON user_view.courseId = course_lps.courseId
                AND user_view.learningPathId = course_lps.learningPathId
           GROUP BY course_lps.courseId',
            [
                'courseIds' => $courseIds,
                'userId' => $userId,
                'sessionId' => $sessionId,
            ],
            [
                'courseIds' => ArrayParameterType::INTEGER,
                'userId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
            ],
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['courseId']] = round((float) ($row['progress'] ?? 0), 1);
        }

        return $result;
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, float|null>
     */
    private function learningPathScoreByCourse(int $userId, array $courseIds, int $sessionId): array
    {
        if ([] === $courseIds || !$this->hasTables(['c_lp_view', 'c_lp_item_view'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT selected_view.c_id AS courseId,
                    CASE
                        WHEN SUM(CAST(item_view.max_score AS DECIMAL(18, 4))) > 0
                        THEN 100 * SUM(item_view.score) / SUM(CAST(item_view.max_score AS DECIMAL(18, 4)))
                        ELSE NULL
                    END AS score
               FROM c_lp_view selected_view
               INNER JOIN (
                    SELECT c_id, lp_id, MAX(view_count) AS latestViewCount
                      FROM c_lp_view
                     WHERE user_id = :userId
                       AND COALESCE(session_id, 0) = :sessionId
                       AND c_id IN (:courseIds)
                  GROUP BY c_id, lp_id
               ) latest_view
                 ON latest_view.c_id = selected_view.c_id
                AND latest_view.lp_id = selected_view.lp_id
                AND latest_view.latestViewCount = selected_view.view_count
               INNER JOIN c_lp_item_view item_view ON item_view.lp_view_id = selected_view.iid
              WHERE selected_view.user_id = :userId
                AND COALESCE(selected_view.session_id, 0) = :sessionId
                AND selected_view.c_id IN (:courseIds)
           GROUP BY selected_view.c_id',
            [
                'courseIds' => $courseIds,
                'userId' => $userId,
                'sessionId' => $sessionId,
            ],
            [
                'courseIds' => ArrayParameterType::INTEGER,
                'userId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
            ],
        );

        $result = [];
        foreach ($rows as $row) {
            $courseId = (int) $row['courseId'];
            $result[$courseId] = null === $row['score'] ? null : round((float) $row['score'], 2);
        }

        return $result;
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, float|null>
     */
    private function outsideLearningPathScoreByCourse(int $userId, array $courseIds, int $sessionId): array
    {
        if (
            [] === $courseIds
            || !$this->hasTables(['c_quiz', 'resource_link', 'track_e_exercises'])
            || !$this->hasColumns('track_e_exercises', ['orig_lp_id'])
        ) {
            return [];
        }

        $sessionFilter = $sessionId > 0
            ? '(resource_link.session_id IS NULL OR resource_link.session_id = 0 OR resource_link.session_id = :sessionId)'
            : '(resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $rows = $this->connection->fetchAllAssociative(
            'SELECT course_quizzes.courseId,
                    ROUND(AVG(COALESCE(best_attempt.bestScore, 0)), 2) AS score
               FROM (
                    SELECT DISTINCT resource_link.c_id AS courseId, quiz.iid AS quizId
                      FROM c_quiz quiz
                      INNER JOIN resource_link ON resource_link.resource_node_id = quiz.resource_node_id
                     WHERE resource_link.c_id IN (:courseIds)
                       AND resource_link.deleted_at IS NULL
                       AND '.$sessionFilter.'
               ) course_quizzes
               LEFT JOIN (
                    SELECT attempt.c_id AS courseId,
                           attempt.exe_exo_id AS quizId,
                           MAX(
                               CASE
                                   WHEN attempt.max_score > 0
                                   THEN attempt.score * 100 / attempt.max_score
                                   ELSE 0
                               END
                           ) AS bestScore
                      FROM track_e_exercises attempt
                     WHERE attempt.exe_user_id = :userId
                       AND COALESCE(attempt.session_id, 0) = :sessionId
                       AND COALESCE(attempt.orig_lp_id, 0) = 0
                       AND attempt.c_id IN (:courseIds)
                  GROUP BY attempt.c_id, attempt.exe_exo_id
               ) best_attempt
                 ON best_attempt.courseId = course_quizzes.courseId
                AND best_attempt.quizId = course_quizzes.quizId
           GROUP BY course_quizzes.courseId',
            [
                'courseIds' => $courseIds,
                'userId' => $userId,
                'sessionId' => $sessionId,
            ],
            [
                'courseIds' => ArrayParameterType::INTEGER,
                'userId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
            ],
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['courseId']] = null === $row['score'] ? null : round((float) $row['score'], 2);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $courseRow
     *
     * @return array<string, mixed>
     */
    private function getLearnerCourseDetailMeta(
        GlobalReportingContext $context,
        int $userId,
        array $courseRow,
        bool $canManageLearner,
    ): array {
        $courseId = (int) $courseRow['id'];
        $sessionId = (int) $courseRow['sessionId'];
        $links = 0;
        if ($this->hasTables(['track_e_links'])) {
            $links = (int) $this->connection->fetchOne(
                'SELECT COUNT(*)
                   FROM track_e_links
                  WHERE links_user_id = :userId
                    AND c_id = :courseId
                    AND COALESCE(session_id, 0) = :sessionId',
                [
                    'userId' => $userId,
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                ],
                [
                    'userId' => Types::INTEGER,
                    'courseId' => Types::INTEGER,
                    'sessionId' => Types::INTEGER,
                ],
            );
        }

        $documents = 0;
        if (
            $this->hasTables(['track_e_downloads', 'resource_link'])
            && $this->hasColumns('track_e_downloads', ['down_user_id', 'resource_link_id'])
        ) {
            $sessionCondition = $sessionId > 0 ? ' AND resource_link.session_id = :sessionId' : '';
            $parameters = [
                'userId' => $userId,
                'courseId' => $courseId,
            ];
            $types = [
                'userId' => Types::INTEGER,
                'courseId' => Types::INTEGER,
            ];
            if ($sessionId > 0) {
                $parameters['sessionId'] = $sessionId;
                $types['sessionId'] = Types::INTEGER;
            }
            $documents = (int) $this->connection->fetchOne(
                'SELECT COUNT(*)
                   FROM track_e_downloads download
                   INNER JOIN resource_link ON resource_link.id = download.resource_link_id
                  WHERE download.down_user_id = :userId
                    AND resource_link.c_id = :courseId
                    AND resource_link.deleted_at IS NULL'.$sessionCondition,
                $parameters,
                $types,
            );
        }

        $uploadedDocuments = 0;
        if (
            $this->hasTables(['c_document', 'resource_link'])
            && $this->hasColumns('resource_link', ['user_id'])
            && $this->hasColumns('c_document', ['filetype'])
        ) {
            $sessionCondition = $sessionId > 0
                ? ' AND resource_link.session_id = :sessionId'
                : ' AND (resource_link.session_id IS NULL OR resource_link.session_id = 0)';
            $parameters = [
                'userId' => $userId,
                'courseId' => $courseId,
                'fileType' => 'file',
            ];
            $types = [
                'userId' => Types::INTEGER,
                'courseId' => Types::INTEGER,
            ];
            if ($sessionId > 0) {
                $parameters['sessionId'] = $sessionId;
                $types['sessionId'] = Types::INTEGER;
            }
            $uploadedDocuments = (int) $this->connection->fetchOne(
                'SELECT COUNT(DISTINCT document.iid)
                   FROM c_document document
                   INNER JOIN resource_link ON resource_link.resource_node_id = document.resource_node_id
                  WHERE resource_link.user_id = :userId
                    AND resource_link.c_id = :courseId
                    AND resource_link.deleted_at IS NULL
                    AND document.filetype = :fileType'.$sessionCondition,
                $parameters,
                $types,
            );
        }

        $chatLastConnection = null;
        if (
            $this->hasTables(['track_e_lastaccess'])
            && $this->hasColumns(
                'track_e_lastaccess',
                ['access_tool', 'access_user_id', 'c_id', 'session_id', 'access_date'],
            )
        ) {
            $chatLastConnection = $this->connection->fetchOne(
                'SELECT access_date
                   FROM track_e_lastaccess
                  WHERE access_tool = :tool
                    AND access_user_id = :userId
                    AND c_id = :courseId
                    AND COALESCE(session_id, 0) = :sessionId
               ORDER BY access_date DESC
                  LIMIT 1',
                [
                    'tool' => 'chat',
                    'userId' => $userId,
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                ],
                [
                    'userId' => Types::INTEGER,
                    'courseId' => Types::INTEGER,
                    'sessionId' => Types::INTEGER,
                ],
            );
            $chatLastConnection = false === $chatLastConnection ? null : (string) $chatLastConnection;
        }

        $accessDates = 0;
        if ($this->hasTables(['track_e_course_access'])) {
            $accessDates = (int) $this->connection->fetchOne(
                'SELECT COUNT(*)
                   FROM track_e_course_access
                  WHERE user_id = :userId
                    AND c_id = :courseId
                    AND COALESCE(session_id, 0) = :sessionId',
                [
                    'userId' => $userId,
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                ],
                [
                    'userId' => Types::INTEGER,
                    'courseId' => Types::INTEGER,
                    'sessionId' => Types::INTEGER,
                ],
            );
        }

        return [
            'id' => $courseId,
            'resourceNodeId' => (int) ($courseRow['resourceNodeId'] ?? 0),
            'code' => (string) $courseRow['code'],
            'title' => (string) $courseRow['title'],
            'sessionId' => $sessionId,
            'timeSeconds' => (int) $courseRow['timeSeconds'],
            'progress' => round((float) $courseRow['learningPathProgress'], 2),
            'score' => round((float) $courseRow['score'], 2),
            'firstAccess' => $courseRow['firstAccess'],
            'lastAccess' => $courseRow['lastAccess'],
            'tools' => [
                'links' => $links,
                'documents' => $documents,
                'assignments' => (int) $courseRow['assignments'],
                'forumPosts' => (int) $courseRow['forumPosts'],
                'uploadedDocuments' => $uploadedDocuments,
                'chatLastConnection' => $chatLastConnection,
                'accessDates' => $accessDates,
            ],
            'actions' => [
                'courseReportingUrl' => '/resources/course-reporting/?'.http_build_query([
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'gid' => 0,
                ]),
                'courseLearningPathsUrl' => '/resources/course-reporting/learning-paths?'.http_build_query([
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'gid' => 0,
                ]),
                'courseExamsUrl' => '/resources/course-reporting/exams?'.http_build_query([
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'gid' => 0,
                ]),
                'accessDetailsUrl' => '/main/my_space/access_details.php?'.http_build_query([
                    'student' => $userId,
                    'course' => (string) $courseRow['code'],
                    'origin' => 'tracking',
                    'cid' => $courseId,
                    'id_session' => $sessionId,
                ]),
                'attendanceUrl' => $canManageLearner
                    ? '/main/my_space/myStudents.php?'.http_build_query([
                        'action' => 'all_attendance',
                        'student' => $userId,
                    ])
                    : null,
            ],
            'showEmail' => $context->showEmailAddresses,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLearnerCourseDetailSections(
        int $userId,
        int $courseId,
        int $sessionId,
        bool $canManageLearner,
    ): array {
        $learningPaths = $this->learningPathDetailRows($userId, $courseId, $sessionId);
        foreach ($learningPaths as &$learningPath) {
            $learningPath['detailsUrl'] = (int) ($learningPath['resourceNodeId'] ?? 0) > 0
                ? '/resources/lp/'.(int) $learningPath['resourceNodeId'].'/'.(int) $learningPath['id'].
                    '/reporting?'.http_build_query([
                        'cid' => $courseId,
                        'sid' => $sessionId,
                        'studentId' => $userId,
                        'isStudentView' => 'false',
                        'returnTo' => 'global-reporting-learner-course-detail',
                        'returnUserId' => $userId,
                        'returnCourseId' => $courseId,
                        'returnSessionId' => $sessionId,
                    ])
                : null;
            $learningPath['resetUrl'] = $canManageLearner ? $learningPath['detailsUrl'] : null;
            $learningPath['latestAttemptAverageScore'] = $learningPath['score'];
        }
        unset($learningPath);

        $tests = $this->exerciseDetailRows($userId, $courseId, $sessionId);
        $assignments = $this->assignmentDetailRows($userId, $courseId, $sessionId);

        return [
            [
                'key' => 'course-skills',
                'title' => 'Acquired skills',
                'columns' => [
                    ['key' => 'title', 'label' => 'Skill', 'type' => 'text'],
                    ['key' => 'acquiredAt', 'label' => 'Acquired at', 'type' => 'datetime'],
                ],
                'items' => $this->skillDetailRows($userId, $courseId, $sessionId),
                'emptyText' => 'No acquired skill',
            ],
            [
                'key' => 'course-learning-paths',
                'title' => 'Learning paths',
                'columns' => [
                    ['key' => 'title', 'label' => 'Learning paths', 'type' => 'text'],
                    ['key' => 'timeSeconds', 'label' => 'Time', 'type' => 'duration'],
                    ['key' => 'bestScore', 'label' => 'Best score', 'type' => 'nullable-percent'],
                    [
                        'key' => 'latestAttemptAverageScore',
                        'label' => 'Latest attempt average score',
                        'type' => 'nullable-percent',
                    ],
                    ['key' => 'progress', 'label' => 'Progress', 'type' => 'percent'],
                    ['key' => 'lastAccess', 'label' => 'Latest login', 'type' => 'datetime'],
                    ['key' => 'details', 'label' => 'Details', 'type' => 'action'],
                    ['key' => 'reset', 'label' => 'Reset Learning path', 'type' => 'action'],
                ],
                'items' => $learningPaths,
                'emptyText' => 'No learning path',
            ],
            [
                'key' => 'course-tests',
                'title' => 'Tests',
                'columns' => [
                    ['key' => 'title', 'label' => 'Tests', 'type' => 'text'],
                    ['key' => 'learningPath', 'label' => 'Learning paths', 'type' => 'text'],
                    [
                        'key' => 'bestAttempt',
                        'label' => 'Average score in learning paths',
                        'type' => 'nullable-percent',
                    ],
                    ['key' => 'attempts', 'label' => 'Attempts', 'type' => 'number'],
                    ['key' => 'latestAttempt', 'label' => 'Latest attempt', 'type' => 'action'],
                    ['key' => 'allAttempts', 'label' => 'All attempts', 'type' => 'action'],
                ],
                'items' => $tests,
                'emptyText' => 'There is no test for the moment',
            ],
            [
                'key' => 'course-assignments',
                'title' => 'Tasks',
                'columns' => [
                    ['key' => 'title', 'label' => 'Tasks', 'type' => 'text'],
                    ['key' => 'documentId', 'label' => 'Document ID', 'type' => 'number'],
                    ['key' => 'qualification', 'label' => 'Score', 'type' => 'text'],
                    ['key' => 'sentDate', 'label' => 'Handed out', 'type' => 'datetime'],
                    ['key' => 'deadline', 'label' => 'Deadline', 'type' => 'datetime'],
                    ['key' => 'workTime', 'label' => 'Assignment work time', 'type' => 'duration'],
                    ['key' => 'details', 'label' => 'Details', 'type' => 'action'],
                ],
                'items' => $assignments,
                'emptyText' => 'No assignments',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assignmentDetailRows(int $userId, int $courseId, int $sessionId): array
    {
        if (!$this->hasTables(['c_student_publication', 'resource_link'])) {
            return [];
        }

        $hasAssignmentTable = $this->hasTables(['c_student_publication_assignment']);
        $assignmentJoin = $hasAssignmentTable
            ? ' LEFT JOIN c_student_publication_assignment assignment ON assignment.publication_id = parent.iid'
            : '';
        $deadlineSelect = $hasAssignmentTable ? 'assignment.expires_on AS deadline' : 'NULL AS deadline';
        $sessionCondition = $sessionId > 0
            ? ' AND resource_link.session_id = :sessionId'
            : ' AND (resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $parameters = [
            'userId' => $userId,
            'courseId' => $courseId,
        ];
        $types = [
            'userId' => Types::INTEGER,
            'courseId' => Types::INTEGER,
        ];
        if ($sessionId > 0) {
            $parameters['sessionId'] = $sessionId;
            $types['sessionId'] = Types::INTEGER;
        }
        $rows = $this->connection->fetchAllAssociative(
            'SELECT result.iid AS id,
                    parent.title,
                    result.document_id AS documentId,
                    result.qualification,
                    result.sent_date AS sentDate,
                    '.$deadlineSelect.',
                    COALESCE(result.duration, 0) AS workTime
               FROM c_student_publication result
               INNER JOIN c_student_publication parent ON parent.iid = result.parent_id
               INNER JOIN resource_link ON resource_link.resource_node_id = result.resource_node_id'.$assignmentJoin.'
              WHERE result.user_id = :userId
                AND resource_link.c_id = :courseId
                AND resource_link.deleted_at IS NULL'.$sessionCondition.'
           ORDER BY result.sent_date DESC, result.iid DESC',
            $parameters,
            $types,
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['documentId'] = (int) $row['documentId'];
            $row['workTime'] = (int) $row['workTime'];
            $row['detailsUrl'] = '/main/work/view.php?'.http_build_query([
                'cid' => $courseId,
                'sid' => $sessionId,
                'id' => $row['id'],
            ]);
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function courseDetailSections(int $userId, int $courseId, int $sessionId): array
    {
        return [
            [
                'key' => 'course-exercises',
                'title' => 'Tests',
                'columns' => [
                    ['key' => 'title', 'label' => 'Tests', 'type' => 'link', 'urlKey' => 'testUrl'],
                    ['key' => 'attempts', 'label' => 'Attempts', 'type' => 'number'],
                    ['key' => 'bestAttempt', 'label' => 'Best attempt', 'type' => 'nullable-percent'],
                    ['key' => 'ranking', 'label' => 'Ranking', 'type' => 'text'],
                    ['key' => 'bestResultInCourse', 'label' => 'Best result in course', 'type' => 'nullable-percent'],
                    ['key' => 'statistics', 'label' => 'Statistics', 'type' => 'nullable-percent'],
                ],
                'items' => $this->exerciseDetailRows($userId, $courseId, $sessionId),
                'emptyText' => 'There is no test for the moment',
            ],
            [
                'key' => 'course-learning-paths',
                'title' => 'Learning paths',
                'columns' => [
                    ['key' => 'title', 'label' => 'Learning paths', 'type' => 'text'],
                    ['key' => 'timeSeconds', 'label' => 'Time spent', 'type' => 'duration'],
                    ['key' => 'progress', 'label' => 'Progress', 'type' => 'percent'],
                    ['key' => 'score', 'label' => 'Score', 'type' => 'nullable-percent'],
                    ['key' => 'bestScore', 'label' => 'Best score', 'type' => 'nullable-percent'],
                    ['key' => 'lastAccess', 'label' => 'Latest login', 'type' => 'datetime'],
                ],
                'items' => $this->learningPathDetailRows($userId, $courseId, $sessionId),
                'emptyText' => 'No learning path',
            ],
            [
                'key' => 'course-skills',
                'title' => 'Skills acquired',
                'columns' => [
                    ['key' => 'title', 'label' => 'Skill', 'type' => 'text'],
                    ['key' => 'acquiredAt', 'label' => 'Acquired at', 'type' => 'datetime'],
                ],
                'items' => $this->skillDetailRows($userId, $courseId, $sessionId),
                'emptyText' => 'No skills acquired',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function exerciseDetailRows(int $userId, int $courseId, int $sessionId): array
    {
        if (!$this->hasTables(['c_quiz', 'resource_link', 'track_e_exercises'])) {
            return [];
        }

        $sessionFilter = $sessionId > 0
            ? '(resource_link.session_id IS NULL OR resource_link.session_id = 0 OR resource_link.session_id = :sessionId)'
            : '(resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $quizParameters = ['courseId' => $courseId];
        $quizTypes = ['courseId' => Types::INTEGER];
        if ($sessionId > 0) {
            $quizParameters['sessionId'] = $sessionId;
            $quizTypes['sessionId'] = Types::INTEGER;
        }
        $quizzes = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT quiz.iid AS id, quiz.resource_node_id AS resourceNodeId, quiz.title
               FROM c_quiz quiz
               INNER JOIN resource_link ON resource_link.resource_node_id = quiz.resource_node_id
              WHERE resource_link.c_id = :courseId
                AND resource_link.deleted_at IS NULL
                AND '.$sessionFilter.'
           ORDER BY quiz.title ASC',
            $quizParameters,
            $quizTypes,
        );
        if ([] === $quizzes) {
            return [];
        }

        $quizIds = array_values(array_map(
            static fn (array $quiz): int => (int) $quiz['id'],
            $quizzes,
        ));
        $learningPathTitles = $this->exerciseLearningPathTitles($quizIds);
        $statusFilter = $this->hasColumns('track_e_exercises', ['status'])
            ? " AND (attempt.status IS NULL OR attempt.status <> 'incomplete')"
            : '';
        $attemptRows = $this->connection->fetchAllAssociative(
            'SELECT attempt.exe_id AS attemptId,
                    attempt.exe_exo_id AS quizId,
                    attempt.exe_user_id AS userId,
                    attempt.score,
                    attempt.max_score AS maxScore
               FROM track_e_exercises attempt
              WHERE attempt.c_id = :courseId
                AND COALESCE(attempt.session_id, 0) = :sessionId
                AND attempt.exe_exo_id IN (:quizIds)'.$statusFilter,
            [
                'courseId' => $courseId,
                'sessionId' => $sessionId,
                'quizIds' => $quizIds,
            ],
            [
                'courseId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
                'quizIds' => ArrayParameterType::INTEGER,
            ],
        );

        $stats = [];
        foreach ($attemptRows as $attemptRow) {
            $quizId = (int) $attemptRow['quizId'];
            $attemptUserId = (int) $attemptRow['userId'];
            $maxScore = (float) ($attemptRow['maxScore'] ?? 0);
            $percentage = $maxScore > 0 ? 100 * (float) $attemptRow['score'] / $maxScore : 0.0;
            $stats[$quizId][$attemptUserId] ??= [
                'attempts' => 0,
                'bestScore' => null,
                'bestAttemptId' => 0,
            ];
            $stats[$quizId][$attemptUserId]['attempts']++;
            if (
                null === $stats[$quizId][$attemptUserId]['bestScore']
                || $percentage > $stats[$quizId][$attemptUserId]['bestScore']
            ) {
                $stats[$quizId][$attemptUserId]['bestScore'] = $percentage;
                $stats[$quizId][$attemptUserId]['bestAttemptId'] = (int) $attemptRow['attemptId'];
            }
        }

        $rows = [];
        foreach ($quizzes as $quiz) {
            $quizId = (int) $quiz['id'];
            $quizStats = $stats[$quizId] ?? [];
            $scores = array_values(array_filter(
                array_map(
                    static fn (array $item): ?float => null === $item['bestScore']
                        ? null
                        : (float) $item['bestScore'],
                    $quizStats,
                ),
                static fn (?float $score): bool => null !== $score,
            ));
            rsort($scores, SORT_NUMERIC);
            $uniqueScores = array_values(array_unique(array_map(
                static fn (float $score): string => number_format($score, 6, '.', ''),
                $scores,
            )));
            $userStats = $quizStats[$userId] ?? [
                'attempts' => 0,
                'bestScore' => null,
                'bestAttemptId' => 0,
            ];
            $ranking = null;
            if (null !== $userStats['bestScore']) {
                $scoreKey = number_format((float) $userStats['bestScore'], 6, '.', '');
                $rankIndex = array_search($scoreKey, $uniqueScores, true);
                if (false !== $rankIndex) {
                    $ranking = ($rankIndex + 1).' / '.\count($scores);
                }
            }

            $resourceNodeId = (int) ($quiz['resourceNodeId'] ?? 0);
            $latestAttemptUrl = null;
            $allAttemptsUrl = null;
            if ($resourceNodeId > 0 && (int) $userStats['bestAttemptId'] > 0) {
                $latestAttemptUrl = '/resources/exercise/'.$resourceNodeId.'/'.$quizId.'/result/'.
                    (int) $userStats['bestAttemptId'].'?'.http_build_query([
                        'cid' => $courseId,
                        'sid' => $sessionId,
                        'gid' => 0,
                        'student' => $userId,
                        'origin' => 'tracking',
                    ]);
            }
            if ($resourceNodeId > 0 && (int) $userStats['attempts'] > 0) {
                $allAttemptsUrl = '/resources/exercise/'.$resourceNodeId.'/'.$quizId.'/report?'.http_build_query([
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'gid' => 0,
                    'filter_by_user' => $userId,
                ]);
            }

            $rows[] = [
                'id' => $quizId,
                'resourceNodeId' => $resourceNodeId,
                'title' => (string) $quiz['title'],
                'learningPath' => $learningPathTitles[$quizId] ?? '-',
                'testUrl' => '/main/exercise/overview.php?'.http_build_query([
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'exerciseId' => $quizId,
                ]),
                'attempts' => (int) $userStats['attempts'],
                'bestAttempt' => null === $userStats['bestScore']
                    ? null
                    : round((float) $userStats['bestScore'], 2),
                'bestAttemptUrl' => $latestAttemptUrl,
                'latestAttemptUrl' => $latestAttemptUrl,
                'allAttemptsUrl' => $allAttemptsUrl,
                'ranking' => $ranking,
                'bestResultInCourse' => [] === $scores ? null : round(max($scores), 2),
                'statistics' => [] === $scores ? null : round(array_sum($scores) / \count($scores), 2),
            ];
        }

        return $rows;
    }

    /**
     * @param int[] $quizIds
     *
     * @return array<int, string>
     */
    private function exerciseLearningPathTitles(array $quizIds): array
    {
        if ([] === $quizIds || !$this->hasTables(['c_lp_item', 'c_lp'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT CAST(item.ref AS UNSIGNED) AS quizId,
                    GROUP_CONCAT(DISTINCT learning_path.title ORDER BY learning_path.title SEPARATOR ', ') AS titles
               FROM c_lp_item item
               INNER JOIN c_lp learning_path ON learning_path.iid = item.lp_id
              WHERE item.item_type IN (:itemTypes)
                AND CAST(item.ref AS UNSIGNED) IN (:quizIds)
           GROUP BY CAST(item.ref AS UNSIGNED)",
            [
                'itemTypes' => ['quiz', 'exercise'],
                'quizIds' => $quizIds,
            ],
            [
                'itemTypes' => ArrayParameterType::STRING,
                'quizIds' => ArrayParameterType::INTEGER,
            ],
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['quizId']] = (string) $row['titles'];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function learningPathDetailRows(int $userId, int $courseId, int $sessionId): array
    {
        if (!$this->hasTables(['c_lp', 'resource_link', 'c_lp_view', 'c_lp_item_view'])) {
            return [];
        }

        $sessionFilter = $sessionId > 0
            ? '(resource_link.session_id IS NULL OR resource_link.session_id = 0 OR resource_link.session_id = :sessionId)'
            : '(resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $rows = $this->connection->fetchAllAssociative(
            'SELECT learning_path.iid AS id,
                    learning_path.resource_node_id AS resourceNodeId,
                    learning_path.title,
                    COALESCE(selected_view.progress, 0) AS progress,
                    COALESCE(SUM(item_view.total_time), 0) AS timeSeconds,
                    CASE
                        WHEN SUM(CAST(item_view.max_score AS DECIMAL(18, 4))) > 0
                        THEN 100 * SUM(item_view.score) / SUM(CAST(item_view.max_score AS DECIMAL(18, 4)))
                        ELSE NULL
                    END AS score,
                    MAX(
                        CASE
                            WHEN item_view.start_time > 0
                            THEN FROM_UNIXTIME(item_view.start_time)
                            ELSE NULL
                        END
                    ) AS lastAccess
               FROM c_lp learning_path
               INNER JOIN resource_link ON resource_link.resource_node_id = learning_path.resource_node_id
               LEFT JOIN c_lp_view selected_view
                 ON selected_view.iid = (
                    SELECT latest_view.iid
                      FROM c_lp_view latest_view
                     WHERE latest_view.user_id = :userId
                       AND latest_view.c_id = :courseId
                       AND latest_view.lp_id = learning_path.iid
                       AND COALESCE(latest_view.session_id, 0) = :sessionId
                  ORDER BY latest_view.view_count DESC, latest_view.iid DESC
                     LIMIT 1
                 )
               LEFT JOIN c_lp_item_view item_view ON item_view.lp_view_id = selected_view.iid
              WHERE resource_link.c_id = :courseId
                AND resource_link.deleted_at IS NULL
                AND '.$sessionFilter.'
           GROUP BY learning_path.iid, learning_path.resource_node_id, learning_path.title, selected_view.progress
           ORDER BY learning_path.title ASC',
            [
                'userId' => $userId,
                'courseId' => $courseId,
                'sessionId' => $sessionId,
            ],
            [
                'userId' => Types::INTEGER,
                'courseId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
            ],
        );

        foreach ($rows as &$row) {
            $score = null === $row['score'] ? null : round((float) $row['score'], 2);
            $row['id'] = (int) $row['id'];
            $row['resourceNodeId'] = (int) ($row['resourceNodeId'] ?? 0);
            $row['progress'] = round((float) $row['progress'], 1);
            $row['timeSeconds'] = (int) $row['timeSeconds'];
            $row['score'] = $score;
            $row['bestScore'] = $score;
        }
        unset($row);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function skillDetailRows(int $userId, int $courseId, int $sessionId): array
    {
        if (
            !$this->hasTables(['skill', 'skill_rel_user'])
            || !$this->hasColumns('skill_rel_user', ['skill_id', 'user_id', 'course_id', 'session_id'])
        ) {
            return [];
        }

        $acquiredColumn = null;
        foreach (['acquired_skill_at', 'acquired_at'] as $candidate) {
            if ($this->hasColumns('skill_rel_user', [$candidate])) {
                $acquiredColumn = $candidate;

                break;
            }
        }
        $acquiredSelect = null === $acquiredColumn
            ? 'NULL AS acquiredAt'
            : 'relation.'.$acquiredColumn.' AS acquiredAt';
        $rows = $this->connection->fetchAllAssociative(
            'SELECT skill.id, skill.title, '.$acquiredSelect.'
               FROM skill_rel_user relation
               INNER JOIN skill ON skill.id = relation.skill_id
              WHERE relation.user_id = :userId
                AND relation.course_id = :courseId
                AND COALESCE(relation.session_id, 0) = :sessionId
           ORDER BY skill.title ASC',
            [
                'userId' => $userId,
                'courseId' => $courseId,
                'sessionId' => $sessionId,
            ],
            [
                'userId' => Types::INTEGER,
                'courseId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
            ],
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getTrackedCourses(GlobalReportingContext $context, array $filters): array
    {
        $sessionId = max(0, (int) $filters['sessionId']);
        $courseIds = $this->getTrackedCourseIds($context, $filters, $sessionId);
        $title = $sessionId > 0 ? 'Courses in this session' : 'Your courses';
        $columns = $this->trackedCourseColumns();

        if ([] === $courseIds) {
            return $this->result(
                $filters,
                $title,
                0,
                [],
                $columns,
                [],
                [],
                [
                    'supportsKeyword' => true,
                    'supportsReset' => false,
                    'canExportCsv' => false,
                    'canExportXlsx' => false,
                    'sessionId' => $sessionId,
                ],
            );
        }

        $where = ['course.id IN (:courseIds)'];
        $parameters = ['courseIds' => $courseIds];
        $types = ['courseIds' => ArrayParameterType::INTEGER];
        $keyword = trim((string) $filters['keyword']);
        if ('' !== $keyword) {
            $where[] = '(course.title LIKE :keyword OR course.code LIKE :keyword OR course.visual_code LIKE :keyword)';
            $parameters['keyword'] = '%'.$keyword.'%';
        }

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM course WHERE '.implode(' AND ', $where),
            $parameters,
            $types,
        );
        $rows = $this->connection->fetchAllAssociative(
            'SELECT course.id,
                    course.resource_node_id AS resourceNodeId,
                    course.code,
                    course.visual_code AS visualCode,
                    course.title
               FROM course
              WHERE '.implode(' AND ', $where).'
           ORDER BY course.title ASC
              LIMIT :limit OFFSET :offset',
            [
                ...$parameters,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                ...$types,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );

        $pageCourseIds = array_values(array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows,
        ));
        $learnerMetrics = $this->getTrackedCourseLearnerMetrics($pageCourseIds, $sessionId);
        $progressByCourse = $this->getTrackedCourseLearningPathProgress(
            $pageCourseIds,
            $sessionId,
            $learnerMetrics,
        );
        $scoreByCourse = $this->getTrackedCourseLearningPathScore(
            $pageCourseIds,
            $sessionId,
            $learnerMetrics,
        );
        $thematicProgressByCourse = $this->getTrackedCourseThematicProgress($pageCourseIds, $sessionId);
        $forumPostsByCourse = $this->getTrackedCourseResourceCounts('c_forum_post', $pageCourseIds, $sessionId);
        $assignmentsByCourse = $this->getTrackedCourseResourceCounts(
            'c_student_publication',
            $pageCourseIds,
            $sessionId,
        );
        $attendanceCourseIds = $this->getTrackedCourseAttendanceIds($pageCourseIds, $sessionId);

        foreach ($rows as &$row) {
            $courseId = (int) $row['id'];
            $resourceNodeId = (int) ($row['resourceNodeId'] ?? 0);
            $metrics = $learnerMetrics[$courseId] ?? ['learners' => 0, 'averageTimeSeconds' => 0];
            $thematicProgress = $thematicProgressByCourse[$courseId] ?? null;

            $row['id'] = $courseId;
            $row['resourceNodeId'] = $resourceNodeId;
            $row['sessionId'] = $sessionId;
            $row['learners'] = (int) $metrics['learners'];
            $row['averageTimeSeconds'] = (int) $metrics['averageTimeSeconds'];
            $row['thematicProgress'] = $thematicProgress;
            $row['learningPathProgress'] = $progressByCourse[$courseId] ?? null;
            $row['averageLearningPathScore'] = $scoreByCourse[$courseId] ?? null;
            $row['forumPosts'] = $forumPostsByCourse[$courseId] ?? null;
            $row['assignments'] = $assignmentsByCourse[$courseId] ?? null;
            $row['thematicUrl'] = null;
            $row['attendanceUrl'] = null;

            if (null !== $thematicProgress && $thematicProgress > 0 && $resourceNodeId > 0) {
                $row['thematicUrl'] = '/resources/course-progress/'.$resourceNodeId.'/?'.http_build_query([
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'gid' => 0,
                ]);
            }

            if ($sessionId > 0 && isset($attendanceCourseIds[$courseId])) {
                $row['attendanceUrl'] = '/main/attendance/index.php?'.http_build_query([
                    'cid' => $courseId,
                    'sid' => $sessionId,
                    'action' => 'calendar_logins',
                ]);
            }
        }
        unset($row);

        return $this->result(
            $filters,
            $title,
            $total,
            [],
            $columns,
            $rows,
            [],
            [
                'supportsKeyword' => true,
                'supportsReset' => false,
                'canExportCsv' => false,
                'canExportXlsx' => false,
                'sessionId' => $sessionId,
            ],
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return int[]
     */
    private function getTrackedCourseIds(
        GlobalReportingContext $context,
        array $filters,
        int $sessionId,
    ): array {
        $requestedUserId = max(0, (int) ($filters['userId'] ?? 0));
        if ($requestedUserId > 0 && $context->isAdministrator) {
            $courseIds = array_map(
                static fn (mixed $courseId): int => (int) $courseId,
                $this->connection->fetchFirstColumn(
                    'SELECT DISTINCT subscription.c_id
                       FROM course_rel_user subscription
                       INNER JOIN access_url_rel_course access_course
                           ON access_course.c_id = subscription.c_id
                      WHERE subscription.user_id = :userId
                        AND subscription.status = :teacherStatus
                        AND access_course.access_url_id = :accessUrlId
                   ORDER BY subscription.c_id',
                    [
                        'userId' => $requestedUserId,
                        'teacherStatus' => self::USER_STATUS_TEACHER,
                        'accessUrlId' => $context->accessUrlId,
                    ],
                    [
                        'userId' => Types::INTEGER,
                        'teacherStatus' => Types::INTEGER,
                        'accessUrlId' => Types::INTEGER,
                    ],
                ),
            );
        } else {
            $courseIds = match ($filters['mode']) {
                'followed' => $this->dashboardQueryService->getFollowedCourseIds($context),
                'assigned' => $this->dashboardQueryService->getAssignedCourseIds($context),
                default => $context->isHumanResourcesManager && !$context->isAdministrator
                    ? $this->dashboardQueryService->getScopedCourseIds($context)
                    : $this->dashboardQueryService->getAssignedCourseIds($context),
            };
        }

        if ($sessionId <= 0) {
            return array_values(array_unique(array_map(
                static fn (mixed $courseId): int => (int) $courseId,
                $courseIds,
            )));
        }

        $sessionCourseIds = array_map(
            static fn (mixed $courseId): int => (int) $courseId,
            $this->connection->fetchFirstColumn(
                'SELECT DISTINCT session_course.c_id
                   FROM session_rel_course session_course
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = session_course.session_id
                  WHERE session_course.session_id = :sessionId
                    AND access_session.access_url_id = :accessUrlId
               ORDER BY session_course.c_id',
                [
                    'sessionId' => $sessionId,
                    'accessUrlId' => $context->accessUrlId,
                ],
                [
                    'sessionId' => Types::INTEGER,
                    'accessUrlId' => Types::INTEGER,
                ],
            ),
        );

        if ($context->isAdministrator) {
            return $sessionCourseIds;
        }

        return array_values(array_intersect($sessionCourseIds, $courseIds));
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, array{learners: int, averageTimeSeconds: int}>
     */
    private function getTrackedCourseLearnerMetrics(array $courseIds, int $sessionId): array
    {
        if ([] === $courseIds) {
            return [];
        }

        if ($sessionId > 0) {
            $relationTable = 'session_rel_course_rel_user';
            $relationConditions = 'relation.session_id = :sessionId AND relation.status = :studentStatus';
            $parameters = [
                'courseIds' => $courseIds,
                'sessionId' => $sessionId,
                'studentStatus' => 0,
            ];
            $types = [
                'courseIds' => ArrayParameterType::INTEGER,
                'sessionId' => Types::INTEGER,
                'studentStatus' => Types::INTEGER,
            ];
        } else {
            $relationTable = 'course_rel_user';
            $relationConditions = 'relation.status = :studentStatus AND relation.relation_type <> :managerRelation';
            $parameters = [
                'courseIds' => $courseIds,
                'sessionId' => 0,
                'studentStatus' => self::USER_STATUS_STUDENT,
                'managerRelation' => 1,
            ];
            $types = [
                'courseIds' => ArrayParameterType::INTEGER,
                'sessionId' => Types::INTEGER,
                'studentStatus' => Types::INTEGER,
                'managerRelation' => Types::INTEGER,
            ];
        }

        $timeExpression = $this->hasTables(['track_e_course_access'])
            ? 'COALESCE(SUM(CASE
                    WHEN access_log.logout_course_date IS NOT NULL
                     AND access_log.logout_course_date >= access_log.login_course_date
                    THEN TIMESTAMPDIFF(SECOND, access_log.login_course_date, access_log.logout_course_date)
                    ELSE 0
                END) / NULLIF(COUNT(DISTINCT relation.user_id), 0), 0)'
            : '0';
        $accessJoin = $this->hasTables(['track_e_course_access'])
            ? 'LEFT JOIN track_e_course_access access_log
                    ON access_log.c_id = relation.c_id
                   AND access_log.user_id = relation.user_id
                   AND COALESCE(access_log.session_id, 0) = :sessionId'
            : '';

        $rows = $this->connection->fetchAllAssociative(
            "SELECT relation.c_id,
                    COUNT(DISTINCT relation.user_id) AS learners,
                    $timeExpression AS averageTimeSeconds
               FROM $relationTable relation
               $accessJoin
              WHERE relation.c_id IN (:courseIds)
                AND $relationConditions
           GROUP BY relation.c_id",
            $parameters,
            $types,
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['c_id']] = [
                'learners' => (int) $row['learners'],
                'averageTimeSeconds' => (int) floor((float) $row['averageTimeSeconds']),
            ];
        }

        return $result;
    }

    /**
     * @param int[]                                                     $courseIds
     * @param array<int, array{learners: int, averageTimeSeconds: int}> $learnerMetrics
     *
     * @return array<int, float>
     */
    private function getTrackedCourseLearningPathProgress(
        array $courseIds,
        int $sessionId,
        array $learnerMetrics,
    ): array {
        if ([] === $courseIds || !$this->hasTables(['c_lp_view'])) {
            return [];
        }

        if ($sessionId > 0) {
            $relationJoin = 'INNER JOIN session_rel_course_rel_user relation
                    ON relation.c_id = lp_view.c_id
                   AND relation.user_id = lp_view.user_id
                   AND relation.session_id = :sessionId
                   AND relation.status = :studentStatus';
            $studentStatus = 0;
        } else {
            $relationJoin = 'INNER JOIN course_rel_user relation
                    ON relation.c_id = lp_view.c_id
                   AND relation.user_id = lp_view.user_id
                   AND relation.status = :studentStatus
                   AND relation.relation_type <> :managerRelation';
            $studentStatus = self::USER_STATUS_STUDENT;
        }

        $parameters = [
            'courseIds' => $courseIds,
            'sessionId' => $sessionId,
            'studentStatus' => $studentStatus,
        ];
        $types = [
            'courseIds' => ArrayParameterType::INTEGER,
            'sessionId' => Types::INTEGER,
            'studentStatus' => Types::INTEGER,
        ];
        if ($sessionId <= 0) {
            $parameters['managerRelation'] = 1;
            $types['managerRelation'] = Types::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT ranked.c_id, AVG(ranked.progress) AS progress
               FROM (
                    SELECT lp_view.c_id,
                           lp_view.lp_id,
                           COALESCE(lp_view.progress, 0) AS progress,
                           ROW_NUMBER() OVER (
                               PARTITION BY lp_view.c_id, lp_view.lp_id
                               ORDER BY lp_view.view_count DESC, lp_view.iid DESC
                           ) AS rowNumber
                      FROM c_lp_view lp_view
                      $relationJoin
                     WHERE lp_view.c_id IN (:courseIds)
                       AND COALESCE(lp_view.session_id, 0) = :sessionId
               ) ranked
              WHERE ranked.rowNumber = 1
           GROUP BY ranked.c_id",
            $parameters,
            $types,
        );

        $result = [];
        foreach ($rows as $row) {
            $courseId = (int) $row['c_id'];
            $learners = (int) ($learnerMetrics[$courseId]['learners'] ?? 0);
            if ($learners <= 0) {
                continue;
            }

            $result[$courseId] = round((float) $row['progress'] / $learners, 2);
        }

        return $result;
    }

    /**
     * @param int[]                                                     $courseIds
     * @param array<int, array{learners: int, averageTimeSeconds: int}> $learnerMetrics
     *
     * @return array<int, float>
     */
    private function getTrackedCourseLearningPathScore(
        array $courseIds,
        int $sessionId,
        array $learnerMetrics,
    ): array {
        if ([] === $courseIds || !$this->hasTables(['track_e_exercises'])) {
            return [];
        }

        if ($sessionId > 0) {
            $relationJoin = 'INNER JOIN session_rel_course_rel_user relation
                    ON relation.c_id = exercise.c_id
                   AND relation.user_id = exercise.exe_user_id
                   AND relation.session_id = :sessionId
                   AND relation.status = :studentStatus';
            $studentStatus = 0;
        } else {
            $relationJoin = 'INNER JOIN course_rel_user relation
                    ON relation.c_id = exercise.c_id
                   AND relation.user_id = exercise.exe_user_id
                   AND relation.status = :studentStatus
                   AND relation.relation_type <> :managerRelation';
            $studentStatus = self::USER_STATUS_STUDENT;
        }

        $parameters = [
            'courseIds' => $courseIds,
            'sessionId' => $sessionId,
            'studentStatus' => $studentStatus,
            'completedStatus' => '',
        ];
        $types = [
            'courseIds' => ArrayParameterType::INTEGER,
            'sessionId' => Types::INTEGER,
            'studentStatus' => Types::INTEGER,
            'completedStatus' => Types::STRING,
        ];
        if ($sessionId <= 0) {
            $parameters['managerRelation'] = 1;
            $types['managerRelation'] = Types::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT lp_scores.c_id, AVG(lp_scores.lpScore) AS score
               FROM (
                    SELECT ranked.c_id,
                           ranked.orig_lp_id,
                           AVG(ranked.attemptScore) AS lpScore
                      FROM (
                           SELECT exercise.c_id,
                                  exercise.orig_lp_id,
                                  exercise.orig_lp_item_id,
                                  exercise.exe_user_id,
                                  CASE
                                      WHEN exercise.max_score > 0
                                      THEN exercise.score * 100 / exercise.max_score
                                      ELSE NULL
                                  END AS attemptScore,
                                  ROW_NUMBER() OVER (
                                      PARTITION BY exercise.c_id, exercise.orig_lp_id,
                                                   exercise.orig_lp_item_id, exercise.exe_user_id
                                      ORDER BY exercise.exe_date DESC, exercise.exe_id DESC
                                  ) AS rowNumber
                             FROM track_e_exercises exercise
                             $relationJoin
                            WHERE exercise.c_id IN (:courseIds)
                              AND exercise.orig_lp_id > 0
                              AND exercise.max_score > 0
                              AND exercise.status = :completedStatus
                              AND COALESCE(exercise.session_id, 0) = :sessionId
                      ) ranked
                     WHERE ranked.rowNumber = 1
                  GROUP BY ranked.c_id, ranked.orig_lp_id
               ) lp_scores
           GROUP BY lp_scores.c_id",
            $parameters,
            $types,
        );

        $result = [];
        foreach ($rows as $row) {
            $courseId = (int) $row['c_id'];
            $learners = (int) ($learnerMetrics[$courseId]['learners'] ?? 0);
            if ($learners <= 0 || null === $row['score']) {
                continue;
            }

            $result[$courseId] = round((float) $row['score'] / $learners, 2);
        }

        return $result;
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, float>
     */
    private function getTrackedCourseThematicProgress(array $courseIds, int $sessionId): array
    {
        if ([] === $courseIds || !$this->hasTables(['c_thematic', 'c_thematic_advance', 'resource_link'])) {
            return [];
        }

        $sessionCondition = $sessionId > 0
            ? 'resource_link.session_id = :sessionId'
            : '(resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $parameters = ['courseIds' => $courseIds];
        $types = ['courseIds' => ArrayParameterType::INTEGER];
        if ($sessionId > 0) {
            $parameters['sessionId'] = $sessionId;
            $types['sessionId'] = Types::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT thematic_metrics.c_id,
                    ROUND(SUM(thematic_metrics.thematicAverage) / COUNT(*)) AS progress
               FROM (
                    SELECT resource_link.c_id,
                           thematic.iid,
                           CASE
                               WHEN COUNT(advance.iid) = 0 THEN 0
                               ELSE ROUND(
                                   SUM(CASE WHEN advance.done_advance = 1 THEN 1 ELSE 0 END) * 100
                                   / COUNT(advance.iid)
                               )
                           END AS thematicAverage
                      FROM c_thematic thematic
                      INNER JOIN resource_link
                          ON resource_link.resource_node_id = thematic.resource_node_id
                      LEFT JOIN c_thematic_advance advance
                          ON advance.thematic_id = thematic.iid
                     WHERE resource_link.c_id IN (:courseIds)
                       AND resource_link.deleted_at IS NULL
                       AND $sessionCondition
                       AND thematic.active = 1
                  GROUP BY resource_link.c_id, thematic.iid
               ) thematic_metrics
           GROUP BY thematic_metrics.c_id",
            $parameters,
            $types,
        );

        $result = [];
        foreach ($rows as $row) {
            $progress = (float) $row['progress'];
            if ($progress > 0) {
                $result[(int) $row['c_id']] = $progress;
            }
        }

        return $result;
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, int>
     */
    private function getTrackedCourseResourceCounts(string $resourceTable, array $courseIds, int $sessionId): array
    {
        if (
            [] === $courseIds
            || !\in_array($resourceTable, ['c_forum_post', 'c_student_publication'], true)
            || !$this->hasTables([$resourceTable, 'resource_link'])
        ) {
            return [];
        }

        $sessionCondition = $sessionId > 0
            ? 'resource_link.session_id = :sessionId'
            : '(resource_link.session_id IS NULL OR resource_link.session_id = 0)';
        $parameters = ['courseIds' => $courseIds];
        $types = ['courseIds' => ArrayParameterType::INTEGER];
        if ($sessionId > 0) {
            $parameters['sessionId'] = $sessionId;
            $types['sessionId'] = Types::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative(
            "SELECT resource_link.c_id, COUNT(DISTINCT resource.iid) AS resourceCount
               FROM $resourceTable resource
               INNER JOIN resource_link
                   ON resource_link.resource_node_id = resource.resource_node_id
              WHERE resource_link.c_id IN (:courseIds)
                AND resource_link.deleted_at IS NULL
                AND $sessionCondition
           GROUP BY resource_link.c_id",
            $parameters,
            $types,
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['c_id']] = (int) $row['resourceCount'];
        }

        return $result;
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, true>
     */
    private function getTrackedCourseAttendanceIds(array $courseIds, int $sessionId): array
    {
        if (
            $sessionId <= 0
            || [] === $courseIds
            || !$this->hasTables(['c_attendance', 'resource_link'])
        ) {
            return [];
        }

        $rows = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT resource_link.c_id
               FROM c_attendance attendance
               INNER JOIN resource_link
                   ON resource_link.resource_node_id = attendance.resource_node_id
              WHERE resource_link.c_id IN (:courseIds)
                AND resource_link.session_id = :sessionId
                AND resource_link.deleted_at IS NULL',
            [
                'courseIds' => $courseIds,
                'sessionId' => $sessionId,
            ],
            [
                'courseIds' => ArrayParameterType::INTEGER,
                'sessionId' => Types::INTEGER,
            ],
        );

        $result = [];
        foreach ($rows as $courseId) {
            $result[(int) $courseId] = true;
        }

        return $result;
    }

    /**
     * @return array<int, array<string, string|bool>>
     */
    private function trackedCourseColumns(): array
    {
        return [
            ['key' => 'title', 'label' => 'Course title', 'type' => 'course-home'],
            ['key' => 'learners', 'label' => 'Learners', 'type' => 'number'],
            [
                'key' => 'averageTimeSeconds',
                'label' => 'Time spent in the course',
                'type' => 'duration',
                'help' => 'Time in course',
            ],
            ['key' => 'thematicProgress', 'label' => 'Thematic advance', 'type' => 'thematic-progress'],
            [
                'key' => 'learningPathProgress',
                'label' => 'Progress',
                'type' => 'nullable-percent',
                'help' => 'Average of all learners in all courses',
            ],
            [
                'key' => 'averageLearningPathScore',
                'label' => 'Average score in learning paths',
                'type' => 'nullable-percent',
                'help' => 'Average of all learners in all courses',
            ],
            ['key' => 'forumPosts', 'label' => 'Messages per learner', 'type' => 'nullable-number'],
            ['key' => 'assignments', 'label' => 'Assignments', 'type' => 'nullable-number'],
            ['key' => 'attendanceUrl', 'label' => 'Attendances', 'type' => 'attendance-link'],
            ['key' => 'actions', 'label' => 'Details', 'type' => 'course-reporting'],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getCourses(GlobalReportingContext $context, array $filters, bool $adminMode): array
    {
        $this->assertAdminWhenRequired($context, $adminMode);
        $courseIds = $adminMode
            ? $this->allAccessUrlCourseIds($context)
            : match ($filters['mode']) {
                'assigned' => $this->dashboardQueryService->getAssignedCourseIds($context),
                'followed' => $this->dashboardQueryService->getFollowedCourseIds($context),
                default => $this->dashboardQueryService->getScopedCourseIds($context),
            };
        if ([] === $courseIds) {
            return $this->emptyResult($filters, $adminMode ? 'Courses overview' : 'Courses', $this->courseColumns());
        }

        $where = ['course.id IN (:courseIds)'];
        $parameters = ['courseIds' => $courseIds];
        $types = ['courseIds' => ArrayParameterType::INTEGER];
        $keyword = trim((string) $filters['keyword']);
        if ('' !== $keyword) {
            $where[] = '(course.title LIKE :keyword OR course.code LIKE :keyword OR course.visual_code LIKE :keyword)';
            $parameters['keyword'] = '%'.$keyword.'%';
        }

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM course WHERE '.implode(' AND ', $where),
            $parameters,
            $types,
        );
        $rows = $this->connection->fetchAllAssociative(
            'SELECT course.id,
                    course.code,
                    course.visual_code AS visualCode,
                    course.title,
                    course.visibility,
                    course.creation_date AS createdAt,
                    course.expiration_date AS expiresAt,
                    (SELECT COUNT(DISTINCT subscription.user_id)
                       FROM course_rel_user subscription
                       INNER JOIN user learner ON learner.id = subscription.user_id
                      WHERE subscription.c_id = course.id AND learner.status = 5) AS learners,
                    (SELECT COUNT(DISTINCT relation.session_id)
                       FROM session_rel_course relation
                      WHERE relation.c_id = course.id) AS sessions,
                    (SELECT COUNT(DISTINCT access_log.user_id)
                       FROM track_e_course_access access_log
                      WHERE access_log.c_id = course.id) AS activeLearners,
                    (SELECT MAX(access_log.login_course_date)
                       FROM track_e_course_access access_log
                      WHERE access_log.c_id = course.id) AS lastAccess,
                    (SELECT AVG(lp_view.progress)
                       FROM c_lp_view lp_view
                      WHERE lp_view.c_id = course.id) AS progress
               FROM course
              WHERE '.implode(' AND ', $where).'
           ORDER BY course.title ASC
              LIMIT :limit OFFSET :offset',
            [
                ...$parameters,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                ...$types,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['learners'] = (int) $row['learners'];
            $row['sessions'] = (int) $row['sessions'];
            $row['activeLearners'] = (int) $row['activeLearners'];
            $row['progress'] = round((float) ($row['progress'] ?? 0), 2);
            $row['visibility'] = (int) $row['visibility'];
        }
        unset($row);

        return $this->result(
            $filters,
            $adminMode ? 'Courses overview' : 'Courses',
            $total,
            [
                ['key' => 'courses', 'label' => 'Courses', 'value' => $total],
                ['key' => 'learners', 'label' => 'Learners', 'value' => array_sum(array_column($rows, 'learners'))],
            ],
            $this->courseColumns(),
            $rows,
            [],
            [
                'supportsKeyword' => true,
                'canExportCsv' => true,
                'canExportXlsx' => true,
            ],
        );
    }

    /**
     * @return array<int, array<string, string|bool>>
     */
    private function courseColumns(): array
    {
        return [
            ['key' => 'code', 'label' => 'Code', 'type' => 'text'],
            ['key' => 'visualCode', 'label' => 'Course code', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Course', 'type' => 'text'],
            ['key' => 'learners', 'label' => 'Number of learners', 'type' => 'number'],
            ['key' => 'activeLearners', 'label' => 'Number of learners accessing the course', 'type' => 'number'],
            ['key' => 'sessions', 'label' => 'Sessions', 'type' => 'number'],
            ['key' => 'progress', 'label' => 'Progress', 'type' => 'percent'],
            ['key' => 'lastAccess', 'label' => 'Latest access', 'type' => 'datetime'],
            ['key' => 'createdAt', 'label' => 'Creation date', 'type' => 'datetime'],
            ['key' => 'expiresAt', 'label' => 'End date', 'type' => 'datetime'],
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getSessions(GlobalReportingContext $context, array $filters, bool $adminMode): array
    {
        $this->assertAdminWhenRequired($context, $adminMode);
        $sessionIds = $adminMode
            ? $this->allAccessUrlSessionIds($context)
            : $this->getTrackedSessionIds($context);
        $title = $adminMode ? 'Sessions overview' : 'Your sessions';
        $columns = $this->sessionColumns(!$adminMode);
        $meta = $adminMode
            ? [
                'supportsKeyword' => true,
                'supportsDateRange' => true,
                'canExportCsv' => true,
                'canExportXlsx' => true,
            ]
            : [
                'supportsKeyword' => true,
                'supportsReset' => false,
                'supportsDateRange' => false,
                'canExportCsv' => true,
                'canExportXlsx' => false,
                'sessionTracking' => true,
            ];

        if ([] === $sessionIds) {
            return $this->result($filters, $title, 0, [], $columns, [], [], $meta);
        }

        $where = ['session.id IN (:sessionIds)'];
        $parameters = ['sessionIds' => $sessionIds];
        $types = ['sessionIds' => ArrayParameterType::INTEGER];
        $keyword = trim((string) $filters['keyword']);
        if ('' !== $keyword) {
            $where[] = 'session.title LIKE :keyword';
            $parameters['keyword'] = '%'.$keyword.'%';
        }

        if ($adminMode && null !== $filters['startDate']) {
            $where[] = '(session.access_end_date IS NULL OR session.access_end_date >= :startDate)';
            $parameters['startDate'] = $filters['startDate'];
            $types['startDate'] = Types::DATE_IMMUTABLE;
        }
        if ($adminMode && null !== $filters['endDate']) {
            $where[] = '(session.access_start_date IS NULL OR session.access_start_date <= :endDate)';
            $parameters['endDate'] = $filters['endDate'];
            $types['endDate'] = Types::DATE_IMMUTABLE;
        }

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM session WHERE '.implode(' AND ', $where),
            $parameters,
            $types,
        );

        $rowParameters = $parameters;
        $rowTypes = $types;
        $canAccessAllSessionCourses = $context->isAdministrator
            || $context->isSessionAdministratorOnly
            || ($context->isHumanResourcesManager && $context->humanResourcesCanAccessAllSessionContent);

        $courseCountExpression = '(SELECT COUNT(DISTINCT relation.c_id)
                                      FROM session_rel_course relation
                                     WHERE relation.session_id = session.id)';

        if (!$canAccessAllSessionCourses) {
            $courseCountExpression = '(SELECT COUNT(DISTINCT session_course.c_id)
                                          FROM session_rel_course session_course
                                         WHERE session_course.session_id = session.id
                                           AND (
                                               EXISTS (
                                                   SELECT 1
                                                     FROM session_rel_user general_coach
                                                    WHERE general_coach.session_id = session.id
                                                      AND general_coach.user_id = :currentUserId
                                                      AND general_coach.relation_type = :generalCoachRelationType
                                               )
                                               OR EXISTS (
                                                   SELECT 1
                                                     FROM session_rel_course_rel_user course_coach
                                                    WHERE course_coach.session_id = session.id
                                                      AND course_coach.c_id = session_course.c_id
                                                      AND course_coach.user_id = :currentUserId
                                                      AND course_coach.status = :courseCoachStatus
                                               )
                                           ))';
            $rowParameters['currentUserId'] = $context->currentUserId();
            $rowParameters['generalCoachRelationType'] = self::SESSION_RELATION_TYPE_GENERAL_COACH;
            $rowParameters['courseCoachStatus'] = self::SESSION_STATUS_COURSE_COACH;
            $rowTypes['currentUserId'] = Types::INTEGER;
            $rowTypes['generalCoachRelationType'] = Types::INTEGER;
            $rowTypes['courseCoachStatus'] = Types::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT session.id,
                    session.title,
                    session.status,
                    session.access_start_date AS startDate,
                    session.access_end_date AS endDate,
                    session.display_start_date AS displayStartDate,
                    session.display_end_date AS displayEndDate,
                    '.$courseCountExpression.' AS courses,
                    (SELECT COUNT(DISTINCT relation.user_id)
                       FROM session_rel_course_rel_user relation
                      WHERE relation.session_id = session.id AND relation.status = 0) AS learners,
                    (SELECT COUNT(DISTINCT relation.user_id)
                       FROM session_rel_user relation
                      WHERE relation.session_id = session.id AND relation.relation_type = 3) AS coaches,
                    (SELECT MAX(access_log.login_course_date)
                       FROM track_e_course_access access_log
                      WHERE access_log.session_id = session.id) AS lastAccess,
                    (SELECT AVG(lp_view.progress)
                       FROM c_lp_view lp_view
                      WHERE lp_view.session_id = session.id) AS progress
               FROM session
              WHERE '.implode(' AND ', $where).'
           ORDER BY session.title ASC
              LIMIT :limit OFFSET :offset',
            [
                ...$rowParameters,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                ...$rowTypes,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['courses'] = (int) $row['courses'];
            $row['learners'] = (int) $row['learners'];
            $row['coaches'] = (int) $row['coaches'];
            $row['progress'] = round((float) ($row['progress'] ?? 0), 2);
            $row['status'] = (int) $row['status'];
            $row['date'] = trim(implode(' - ', array_filter([
                (string) ($row['startDate'] ?? ''),
                (string) ($row['endDate'] ?? ''),
            ])));
            $row['achievementPdfUrl'] = '/main/my_space/session.php?'.http_build_query([
                'action' => 'export_to_pdf',
                'type' => 'achievement',
                'session_to_export' => $row['id'],
                'all_students' => 1,
            ]);
        }
        unset($row);

        return $this->result(
            $filters,
            $title,
            $total,
            $adminMode
                ? [
                    ['key' => 'sessions', 'label' => 'Sessions', 'value' => $total],
                    ['key' => 'learners', 'label' => 'Learners', 'value' => array_sum(array_column($rows, 'learners'))],
                    ['key' => 'courses', 'label' => 'Courses', 'value' => array_sum(array_column($rows, 'courses'))],
                ]
                : [],
            $columns,
            $rows,
            [],
            $meta,
        );
    }

    /**
     * @return array<int, array<string, string|bool>>
     */
    private function sessionColumns(bool $trackingList = false): array
    {
        if ($trackingList) {
            return [
                ['key' => 'title', 'label' => 'Title', 'type' => 'session-title'],
                ['key' => 'date', 'label' => 'Date', 'type' => 'session-date-range'],
                ['key' => 'courses', 'label' => 'Number of courses per session', 'type' => 'number'],
                ['key' => 'learners', 'label' => 'Number of learners by session', 'type' => 'number'],
                ['key' => 'actions', 'label' => 'Details', 'type' => 'session-actions'],
            ];
        }

        return [
            ['key' => 'title', 'label' => 'Session', 'type' => 'text'],
            ['key' => 'startDate', 'label' => 'Start date', 'type' => 'datetime'],
            ['key' => 'endDate', 'label' => 'End date', 'type' => 'datetime'],
            ['key' => 'courses', 'label' => 'Courses', 'type' => 'number'],
            ['key' => 'learners', 'label' => 'Learners', 'type' => 'number'],
            ['key' => 'coaches', 'label' => 'Tutors', 'type' => 'number'],
            ['key' => 'progress', 'label' => 'Progress', 'type' => 'percent'],
            ['key' => 'lastAccess', 'label' => 'Latest access', 'type' => 'datetime'],
        ];
    }

    /**
     * @return int[]
     */
    private function getTrackedSessionIds(GlobalReportingContext $context): array
    {
        if ($context->isHumanResourcesManager) {
            return $this->fetchIntegerColumn(
                'SELECT DISTINCT relation.session_id
                   FROM session_rel_user relation
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = relation.session_id
                  WHERE relation.user_id = :currentUserId
                    AND relation.relation_type = :relationType
                    AND access_session.access_url_id = :accessUrlId
               ORDER BY relation.session_id',
                [
                    'currentUserId' => $context->currentUserId(),
                    'relationType' => self::SESSION_RELATION_TYPE_HUMAN_RESOURCES,
                    'accessUrlId' => $context->accessUrlId,
                ],
                [
                    'currentUserId' => Types::INTEGER,
                    'relationType' => Types::INTEGER,
                    'accessUrlId' => Types::INTEGER,
                ],
            );
        }

        if ($context->isSessionAdministratorOnly) {
            return $this->fetchIntegerColumn(
                'SELECT DISTINCT relation.session_id
                   FROM session_rel_user relation
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = relation.session_id
                  WHERE relation.user_id = :currentUserId
                    AND relation.relation_type = :relationType
                    AND access_session.access_url_id = :accessUrlId
               ORDER BY relation.session_id',
                [
                    'currentUserId' => $context->currentUserId(),
                    'relationType' => self::SESSION_RELATION_TYPE_ADMINISTRATOR,
                    'accessUrlId' => $context->accessUrlId,
                ],
                [
                    'currentUserId' => Types::INTEGER,
                    'relationType' => Types::INTEGER,
                    'accessUrlId' => Types::INTEGER,
                ],
            );
        }

        return $this->fetchIntegerColumn(
            'SELECT DISTINCT scoped_session.session_id
               FROM (
                    SELECT general_coach.session_id
                      FROM session_rel_user general_coach
                     WHERE general_coach.user_id = :currentUserId
                       AND general_coach.relation_type = :generalCoachRelationType
                    UNION
                    SELECT course_coach.session_id
                      FROM session_rel_course_rel_user course_coach
                     WHERE course_coach.user_id = :currentUserId
                       AND course_coach.status = :courseCoachStatus
               ) scoped_session
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = scoped_session.session_id
              WHERE access_session.access_url_id = :accessUrlId
           ORDER BY scoped_session.session_id',
            [
                'currentUserId' => $context->currentUserId(),
                'generalCoachRelationType' => self::SESSION_RELATION_TYPE_GENERAL_COACH,
                'courseCoachStatus' => self::SESSION_STATUS_COURSE_COACH,
                'accessUrlId' => $context->accessUrlId,
            ],
            [
                'currentUserId' => Types::INTEGER,
                'generalCoachRelationType' => Types::INTEGER,
                'courseCoachStatus' => Types::INTEGER,
                'accessUrlId' => Types::INTEGER,
            ],
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getAdminUsers(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $userIds = $this->allAccessUrlUserIds($context);
        $meta = [
            'renderMode' => 'user-cards',
            'supportsKeyword' => true,
            'supportsReset' => true,
            'canExportCsv' => true,
            'canExportXlsx' => false,
        ];

        if ([] === $userIds) {
            return $this->result($filters, 'User overview', 0, [], [], [], [], $meta);
        }

        $where = ['usr.id IN (:userIds)'];
        $parameters = ['userIds' => $userIds];
        $types = ['userIds' => ArrayParameterType::INTEGER];
        $keyword = trim((string) $filters['keyword']);
        if ('' !== $keyword) {
            $where[] = '(usr.firstname LIKE :keyword OR usr.lastname LIKE :keyword OR usr.username LIKE :keyword OR usr.official_code LIKE :keyword)';
            $parameters['keyword'] = '%'.$keyword.'%';
            $types['keyword'] = Types::STRING;
        }

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM user usr WHERE '.implode(' AND ', $where),
            $parameters,
            $types,
        );
        $rows = $this->connection->fetchAllAssociative(
            'SELECT usr.id, usr.official_code AS officialCode, usr.firstname, usr.lastname,
                    usr.username, usr.email, usr.status, usr.active, usr.last_login AS lastLogin
               FROM user usr
              WHERE '.implode(' AND ', $where).'
           ORDER BY usr.lastname ASC, usr.firstname ASC, usr.id ASC
              LIMIT :limit OFFSET :offset',
            [
                ...$parameters,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                ...$types,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );

        $pageUserIds = array_map(static fn (array $row): int => (int) $row['id'], $rows);
        $coursesByUser = $this->getAdminUserCourseOverview($pageUserIds);
        foreach ($rows as &$row) {
            $userId = (int) $row['id'];
            $user = $this->userRepository->find($userId);
            $row['id'] = $userId;
            $row['fullName'] = trim((string) $row['firstname'].' '.(string) $row['lastname']);
            $row['status'] = $this->statusLabel((int) $row['status']);
            $row['active'] = 1 === (int) $row['active'];
            $row['avatarUrl'] = null === $user
                ? ''
                : (string) $this->illustrationRepository->getIllustrationUrl($user);
            $row['courses'] = $coursesByUser[$userId] ?? [];
        }
        unset($row);

        if (!$context->showEmailAddresses) {
            $this->removeEmailFromRows($rows);
        }

        return $this->result(
            $filters,
            'User overview',
            $total,
            [],
            [],
            $rows,
            [],
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @param int[]                $userIds
     *
     * @return array<string, mixed>
     */
    private function getUsersFromExplicitIds(
        array $filters,
        array $userIds,
        string $title,
        bool $showEmailAddresses,
    ): array {
        $where = ['usr.id IN (:userIds)'];
        $parameters = ['userIds' => $userIds];
        $types = ['userIds' => ArrayParameterType::INTEGER];
        $keyword = trim((string) $filters['keyword']);
        if ('' !== $keyword) {
            $where[] = '(usr.firstname LIKE :keyword OR usr.lastname LIKE :keyword OR usr.username LIKE :keyword OR usr.official_code LIKE :keyword)';
            $parameters['keyword'] = '%'.$keyword.'%';
        }

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM user usr WHERE '.implode(' AND ', $where),
            $parameters,
            $types,
        );
        $rows = $this->connection->fetchAllAssociative(
            'SELECT usr.id, usr.official_code AS officialCode, usr.firstname, usr.lastname, usr.username,
                    usr.email, usr.status, usr.active, usr.last_login AS lastLogin,
                    (SELECT COUNT(*) FROM course_rel_user relation WHERE relation.user_id = usr.id) AS courseCount,
                    (SELECT COUNT(DISTINCT session_id) FROM session_rel_course_rel_user relation WHERE relation.user_id = usr.id) AS sessionCount,
                    COALESCE((SELECT SUM(TIMESTAMPDIFF(SECOND, login_date, COALESCE(logout_date, login_date))) FROM track_e_login login WHERE login.login_user_id = usr.id), 0) AS timeSeconds
               FROM user usr
              WHERE '.implode(' AND ', $where).'
           ORDER BY usr.lastname ASC, usr.firstname ASC
              LIMIT :limit OFFSET :offset',
            [...$parameters, 'limit' => $filters['itemsPerPage'], 'offset' => $filters['offset']],
            [...$types, 'limit' => Types::INTEGER, 'offset' => Types::INTEGER],
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['status'] = $this->statusLabel((int) $row['status']);
            $row['active'] = 1 === (int) $row['active'] ? 'Active' : 'Inactive';
            $row['courseCount'] = (int) $row['courseCount'];
            $row['sessionCount'] = (int) $row['sessionCount'];
            $row['timeSeconds'] = (int) $row['timeSeconds'];
        }
        unset($row);
        if (!$showEmailAddresses) {
            $this->removeEmailFromRows($rows);
        }

        return $this->result(
            $filters,
            $title,
            $total,
            [['key' => 'users', 'label' => 'Users', 'value' => $total]],
            $this->userColumns(true, $showEmailAddresses),
            $rows,
            [],
            ['supportsKeyword' => true, 'canExportCsv' => true, 'canExportXlsx' => true],
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getCoaches(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $rows = $this->connection->fetchAllAssociative(
            'SELECT usr.id,
                    usr.official_code AS officialCode,
                    usr.firstname,
                    usr.lastname,
                    usr.username,
                    usr.email,
                    COUNT(DISTINCT session_relation.session_id) AS sessions,
                    COUNT(DISTINCT course_relation.c_id) AS courses,
                    MAX(login.login_date) AS lastLogin
               FROM user usr
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = usr.id AND access_user.access_url_id = :accessUrlId
               LEFT JOIN session_rel_user session_relation
                   ON session_relation.user_id = usr.id AND session_relation.relation_type = 3
               LEFT JOIN session_rel_course_rel_user course_relation
                   ON course_relation.user_id = usr.id AND course_relation.status = 2
               LEFT JOIN track_e_login login ON login.login_user_id = usr.id
              WHERE usr.status = :teacherStatus
           GROUP BY usr.id, usr.official_code, usr.firstname, usr.lastname, usr.username, usr.email
           ORDER BY usr.lastname ASC, usr.firstname ASC
              LIMIT :limit OFFSET :offset',
            [
                'accessUrlId' => $context->accessUrlId,
                'teacherStatus' => self::USER_STATUS_TEACHER,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                'accessUrlId' => Types::INTEGER,
                'teacherStatus' => Types::INTEGER,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['sessions'] = (int) $row['sessions'];
            $row['courses'] = (int) $row['courses'];
        }
        unset($row);
        if (!$context->showEmailAddresses) {
            $this->removeEmailFromRows($rows);
        }

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT usr.id)
               FROM user usr
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = usr.id AND access_user.access_url_id = :accessUrlId
              WHERE usr.status = :teacherStatus',
            ['accessUrlId' => $context->accessUrlId, 'teacherStatus' => self::USER_STATUS_TEACHER],
            ['accessUrlId' => Types::INTEGER, 'teacherStatus' => Types::INTEGER],
        );

        return $this->result(
            $filters,
            'Trainers Overview',
            $total,
            [['key' => 'trainers', 'label' => 'Trainers', 'value' => $total]],
            [
                ['key' => 'officialCode', 'label' => 'Code', 'type' => 'text'],
                ['key' => 'lastname', 'label' => 'Last name', 'type' => 'text'],
                ['key' => 'firstname', 'label' => 'First name', 'type' => 'text'],
                ['key' => 'username', 'label' => 'Username', 'type' => 'text'],
                ['key' => 'courses', 'label' => 'Courses', 'type' => 'number'],
                ['key' => 'sessions', 'label' => 'Sessions', 'type' => 'number'],
                ['key' => 'lastLogin', 'label' => 'Latest access', 'type' => 'datetime'],
            ],
            $rows,
            [],
            ['canExportCsv' => true, 'canExportXlsx' => true],
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getAccessOverview(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);

        $meta = [
            'renderMode' => 'access-log',
            'supportsCourse' => true,
            'supportsSession' => true,
            'supportsProfile' => true,
            'supportsUser' => true,
            'supportsDateRange' => true,
            'requiresCourse' => true,
            'requiresProfile' => true,
            'submitLabel' => 'Generate',
            'courseOptions' => $this->getAdminCourseOptions($context),
            'sessionOptions' => $this->getAdminSessionOptions($context, (int) $filters['courseId']),
            'profileOptions' => $this->getAdminProfileOptions(),
            'userOptions' => $this->getAdminUserOptions(
                $context,
                (int) $filters['status'] > 0 ? (int) $filters['status'] : null,
                (int) $filters['courseId'],
                (int) $filters['sessionId'],
            ),
            'canExportCsv' => true,
            'canExportXlsx' => true,
        ];

        $columns = [
            ['key' => 'loginDate', 'label' => 'Login date', 'type' => 'datetime'],
            ['key' => 'username', 'label' => 'Username', 'type' => 'text'],
            ['key' => 'firstname', 'label' => 'First name', 'type' => 'text'],
            ['key' => 'lastname', 'label' => 'Last name', 'type' => 'text'],
            ['key' => 'ip', 'label' => 'IP', 'type' => 'text'],
            ['key' => 'durationSeconds', 'label' => 'Time connected (hh:mm)', 'type' => 'duration'],
        ];

        if ((int) $filters['courseId'] <= 0 || (int) $filters['status'] <= 0) {
            return $this->result($filters, 'Accesses by user overview', 0, [], $columns, [], [], $meta);
        }

        $allowedCourseIds = $this->allAccessUrlCourseIds($context);
        if (!\in_array((int) $filters['courseId'], $allowedCourseIds, true)) {
            throw new AccessDeniedHttpException('The requested course is outside the current access URL.');
        }

        $where = [
            'access_log.c_id = :courseId',
            'usr.status = :profile',
            'usr.active <> :softDeleted',
        ];
        $parameters = [
            'courseId' => (int) $filters['courseId'],
            'profile' => (int) $filters['status'],
            'softDeleted' => User::SOFT_DELETED,
        ];
        $types = [
            'courseId' => Types::INTEGER,
            'profile' => Types::INTEGER,
            'softDeleted' => Types::INTEGER,
        ];

        if ((int) $filters['sessionId'] > 0) {
            $where[] = 'COALESCE(access_log.session_id, 0) = :sessionId';
            $parameters['sessionId'] = (int) $filters['sessionId'];
            $types['sessionId'] = Types::INTEGER;
        }
        if ((int) $filters['userId'] > 0) {
            $where[] = 'usr.id = :userId';
            $parameters['userId'] = (int) $filters['userId'];
            $types['userId'] = Types::INTEGER;
        }
        if (null !== $filters['startDate']) {
            $where[] = 'access_log.login_course_date >= :startDate';
            $parameters['startDate'] = $filters['startDate'];
            $types['startDate'] = Types::DATE_IMMUTABLE;
        }
        if (null !== $filters['endDate']) {
            $where[] = 'access_log.logout_course_date < DATE_ADD(:endDate, INTERVAL 1 DAY)';
            $parameters['endDate'] = $filters['endDate'];
            $types['endDate'] = Types::DATE_IMMUTABLE;
        }

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(access_log.course_access_id)
               FROM track_e_course_access access_log
               INNER JOIN user usr ON usr.id = access_log.user_id
              WHERE '.implode(' AND ', $where),
            $parameters,
            $types,
        );
        $rows = $this->connection->fetchAllAssociative(
            'SELECT access_log.course_access_id AS id,
                    access_log.login_course_date AS loginDate,
                    usr.username,
                    usr.firstname,
                    usr.lastname,
                    access_log.user_ip AS ip,
                    CASE
                        WHEN access_log.logout_course_date IS NOT NULL
                         AND access_log.logout_course_date >= access_log.login_course_date
                        THEN TIMESTAMPDIFF(
                            SECOND,
                            access_log.login_course_date,
                            access_log.logout_course_date
                        )
                        ELSE 0
                    END AS durationSeconds
               FROM track_e_course_access access_log
               INNER JOIN user usr ON usr.id = access_log.user_id
              WHERE '.implode(' AND ', $where).'
           ORDER BY access_log.login_course_date DESC, access_log.course_access_id DESC
              LIMIT :limit OFFSET :offset',
            [
                ...$parameters,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                ...$types,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['durationSeconds'] = (int) $row['durationSeconds'];
        }
        unset($row);

        return $this->result(
            $filters,
            'Accesses by user overview',
            $total,
            [],
            $columns,
            $rows,
            [],
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getExams(GlobalReportingContext $context, array $filters): array
    {
        $courseIds = $this->dashboardQueryService->getScopedCourseIds($context);
        $scoreThreshold = (int) $filters['score'];
        $meta = [
            'supportsKeyword' => false,
            'supportsReset' => false,
            'canExportCsv' => false,
            'canExportXlsx' => true,
            'scoreThreshold' => $scoreThreshold,
            'courseOptions' => [],
            'sessionOptions' => [],
        ];

        if ([] === $courseIds || !$this->hasTables([
            'course',
            'c_quiz',
            'resource_link',
            'course_rel_user',
            'session_rel_course',
            'session_rel_course_rel_user',
            'track_e_exercises',
        ])) {
            return $this->emptyResult($filters, 'Exam tracking', [], $meta);
        }

        $courses = $this->connection->fetchAllAssociative(
            'SELECT course.id, course.title
               FROM course
              WHERE course.id IN (:courseIds)
           ORDER BY course.title ASC, course.id ASC',
            ['courseIds' => $courseIds],
            ['courseIds' => ArrayParameterType::INTEGER],
        );
        $courseTitles = [];
        foreach ($courses as $course) {
            $courseTitles[(int) $course['id']] = (string) $course['title'];
        }
        $meta['courseOptions'] = array_map(
            static fn (array $course): array => [
                'id' => (int) $course['id'],
                'label' => (string) $course['title'],
            ],
            $courses,
        );

        $sessions = $this->connection->fetchAllAssociative(
            'SELECT session_course.c_id AS courseId,
                    session.id,
                    session.title
               FROM session_rel_course session_course
               INNER JOIN session ON session.id = session_course.session_id
              WHERE session_course.c_id IN (:courseIds)
           ORDER BY session.title ASC, session.id ASC',
            ['courseIds' => $courseIds],
            ['courseIds' => ArrayParameterType::INTEGER],
        );
        $sessionsByCourse = [];
        $sessionOptions = [];
        foreach ($sessions as $session) {
            $courseId = (int) $session['courseId'];
            $sessionId = (int) $session['id'];
            $sessionsByCourse[$courseId][$sessionId] = (string) $session['title'];
            if (!isset($sessionOptions[$sessionId])) {
                $sessionOptions[$sessionId] = [
                    'id' => $sessionId,
                    'label' => (string) $session['title'],
                    'courseIds' => [],
                ];
            }
            $sessionOptions[$sessionId]['courseIds'][$courseId] = $courseId;
        }
        $meta['sessionOptions'] = array_values(array_map(
            static fn (array $session): array => [
                'id' => (int) $session['id'],
                'label' => (string) $session['label'],
                'courseIds' => array_values($session['courseIds']),
            ],
            $sessionOptions,
        ));

        $courseLearners = [];
        foreach ($this->connection->fetchAllAssociative(
            'SELECT subscription.c_id AS courseId,
                    COUNT(DISTINCT subscription.user_id) AS totalLearners
               FROM course_rel_user subscription
              WHERE subscription.c_id IN (:courseIds)
                AND subscription.status = :studentStatus
           GROUP BY subscription.c_id',
            [
                'courseIds' => $courseIds,
                'studentStatus' => self::USER_STATUS_STUDENT,
            ],
            [
                'courseIds' => ArrayParameterType::INTEGER,
                'studentStatus' => Types::INTEGER,
            ],
        ) as $row) {
            $courseLearners[(int) $row['courseId']] = (int) $row['totalLearners'];
        }

        $sessionLearners = [];
        foreach ($this->connection->fetchAllAssociative(
            'SELECT subscription.c_id AS courseId,
                    subscription.session_id AS sessionId,
                    COUNT(DISTINCT subscription.user_id) AS totalLearners
               FROM session_rel_course_rel_user subscription
              WHERE subscription.c_id IN (:courseIds)
                AND subscription.status = 0
           GROUP BY subscription.c_id, subscription.session_id',
            ['courseIds' => $courseIds],
            ['courseIds' => ArrayParameterType::INTEGER],
        ) as $row) {
            $sessionLearners[(int) $row['courseId']][(int) $row['sessionId']] = (int) $row['totalLearners'];
        }

        $attemptStats = [];
        foreach ($this->connection->fetchAllAssociative(
            'SELECT attempts.c_id AS courseId,
                    attempts.exe_exo_id AS quizId,
                    attempts.session_id AS sessionId,
                    COUNT(*) AS taken,
                    SUM(CASE WHEN attempts.bestScore >= :scoreThreshold THEN 1 ELSE 0 END) AS passed
               FROM (
                    SELECT exercise.c_id,
                           exercise.exe_exo_id,
                           exercise.session_id,
                           exercise.exe_user_id,
                           MAX(
                               CASE
                                   WHEN exercise.max_score > 0
                                   THEN exercise.score * 100 / exercise.max_score
                                   ELSE 0
                               END
                           ) AS bestScore
                      FROM track_e_exercises exercise
                     WHERE exercise.c_id IN (:courseIds)
                  GROUP BY exercise.c_id,
                           exercise.exe_exo_id,
                           exercise.session_id,
                           exercise.exe_user_id
               ) attempts
           GROUP BY attempts.c_id, attempts.exe_exo_id, attempts.session_id',
            [
                'courseIds' => $courseIds,
                'scoreThreshold' => $scoreThreshold,
            ],
            [
                'courseIds' => ArrayParameterType::INTEGER,
                'scoreThreshold' => Types::INTEGER,
            ],
        ) as $row) {
            $key = (int) $row['courseId'].'-'.(int) $row['quizId'].'-'.(int) $row['sessionId'];
            $attemptStats[$key] = [
                'taken' => (int) $row['taken'],
                'passed' => (int) $row['passed'],
            ];
        }

        $quizRows = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT quiz.iid AS quizId,
                    quiz.title,
                    resource_link.c_id AS courseId,
                    COALESCE(resource_link.session_id, 0) AS sessionId
               FROM c_quiz quiz
               INNER JOIN resource_link
                   ON resource_link.resource_node_id = quiz.resource_node_id
              WHERE resource_link.c_id IN (:courseIds)
                AND resource_link.deleted_at IS NULL
                AND resource_link.user_id IS NULL
                AND resource_link.group_id IS NULL
                AND resource_link.usergroup_id IS NULL
           ORDER BY resource_link.c_id ASC, quiz.title ASC, quiz.iid ASC',
            ['courseIds' => $courseIds],
            ['courseIds' => ArrayParameterType::INTEGER],
        );

        $contexts = [];
        $coursesWithTests = [];
        foreach ($quizRows as $quizRow) {
            $courseId = (int) $quizRow['courseId'];
            $quizId = (int) $quizRow['quizId'];
            $resourceSessionId = (int) $quizRow['sessionId'];
            $coursesWithTests[$courseId] = true;
            if ($resourceSessionId > 0) {
                $sessionContexts = [
                    $resourceSessionId => $sessionsByCourse[$courseId][$resourceSessionId] ?? '',
                ];
            } else {
                $sessionContexts = [0 => ''];
                foreach ($sessionsByCourse[$courseId] ?? [] as $courseSessionId => $courseSessionTitle) {
                    $sessionContexts[$courseSessionId] = $courseSessionTitle;
                }
            }

            foreach ($sessionContexts as $sessionId => $sessionTitle) {
                $contextKey = $courseId.'-'.$quizId.'-'.$sessionId;
                $contexts[$contextKey] = [
                    'id' => $contextKey,
                    'courseId' => $courseId,
                    'course' => $courseTitles[$courseId] ?? '',
                    'quizId' => $quizId,
                    'testTitle' => (string) $quizRow['title'],
                    'test' => (string) $quizRow['title']
                        .($sessionId > 0 && '' !== $sessionTitle ? ' ('.$sessionTitle.')' : ''),
                    'sessionId' => (int) $sessionId,
                    'sessionTitle' => (string) $sessionTitle,
                ];
            }
        }

        $rows = [];
        foreach ($contexts as $examContext) {
            $courseId = (int) $examContext['courseId'];
            $quizId = (int) $examContext['quizId'];
            $sessionId = (int) $examContext['sessionId'];
            $key = $courseId.'-'.$quizId.'-'.$sessionId;
            $statistics = $attemptStats[$key] ?? ['taken' => 0, 'passed' => 0];
            $totalLearners = $sessionId > 0
                ? (int) ($sessionLearners[$courseId][$sessionId] ?? 0)
                : (int) ($courseLearners[$courseId] ?? 0);
            $taken = min($totalLearners, (int) $statistics['taken']);
            $passed = min($taken, (int) $statistics['passed']);

            $rows[] = [
                ...$examContext,
                'taken' => $taken,
                'notTaken' => max(0, $totalLearners - $taken),
                'passed' => $passed,
                'failed' => max(0, $taken - $passed),
                'totalLearners' => $totalLearners,
                'empty' => false,
            ];
        }

        foreach ($courseTitles as $courseId => $courseTitle) {
            if (isset($coursesWithTests[$courseId])) {
                continue;
            }

            $rows[] = [
                'id' => $courseId.'-empty',
                'courseId' => $courseId,
                'course' => $courseTitle,
                'quizId' => 0,
                'testTitle' => 'There is no test for the moment',
                'test' => 'There is no test for the moment',
                'sessionId' => 0,
                'sessionTitle' => '',
                'taken' => null,
                'notTaken' => null,
                'passed' => null,
                'failed' => null,
                'totalLearners' => null,
                'empty' => true,
            ];
        }

        usort(
            $rows,
            static fn (array $left, array $right): int => strcasecmp((string) $left['course'], (string) $right['course'])
                ?: strcasecmp((string) $left['test'], (string) $right['test'])
                ?: ((int) $left['sessionId'] <=> (int) $right['sessionId']),
        );
        $total = \count($rows);
        $pageRows = \array_slice($rows, $filters['offset'], $filters['itemsPerPage']);

        return $this->result(
            $filters,
            'Exam tracking',
            $total,
            [],
            [
                ['key' => 'course', 'label' => 'Courses', 'type' => 'text'],
                ['key' => 'test', 'label' => 'Tests', 'type' => 'exam-title'],
                ['key' => 'taken', 'label' => 'Taken', 'type' => 'nullable-number'],
                ['key' => 'notTaken', 'label' => 'Not taken', 'type' => 'nullable-number'],
                ['key' => 'passed', 'label' => 'Pass minimum %s', 'type' => 'pass-threshold'],
                ['key' => 'failed', 'label' => 'Fail', 'type' => 'nullable-number'],
                ['key' => 'totalLearners', 'label' => 'Total learners', 'type' => 'nullable-number'],
            ],
            $pageRows,
            [],
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getCurrentCourses(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $courseIds = $this->allAccessUrlCourseIds($context);
        if ([] === $courseIds) {
            return $this->emptyResult($filters, 'Current courses report', $this->courseColumns());
        }

        return $this->getCourses($context, $filters, true);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getCertificates(GlobalReportingContext $context, array $filters): array
    {
        $this->assertCertificateReportAccess($context);

        $title = 'See list of learner certificates';
        $columns = $this->certificateColumns();
        $studentIds = $context->isAdministrator
            ? $this->allAccessUrlUserIds($context)
            : $this->dashboardQueryService->getScopedUserIds($context, self::USER_STATUS_STUDENT);
        $courseIds = $context->isAdministrator
            ? $this->allAccessUrlCourseIds($context)
            : $this->getCertificateStudentCourseIds($context, $studentIds);
        $sessionIds = $context->isAdministrator
            ? $this->allAccessUrlSessionIds($context)
            : $this->getCertificateStudentSessionIds($context, $studentIds);

        $sessionOptions = $this->getCertificateSessionOptions($sessionIds);
        $courseOptions = $this->getCertificateCourseOptions($courseIds);
        $learnerOptions = $context->isStudentBoss
            ? $this->getCertificateLearnerOptions($studentIds)
            : [];

        $monthOptions = [];
        for ($month = 1; $month <= 12; ++$month) {
            $monthOptions[] = [
                'label' => \sprintf('%02d', $month),
                'value' => $month,
            ];
        }

        $sessionId = (int) $filters['sessionId'];
        $courseId = (int) $filters['courseId'];
        $userId = (int) $filters['userId'];
        $month = (int) $filters['month'];
        $year = (string) $filters['year'];

        if ('' !== $year && 1 !== preg_match('/^\d{4}$/', $year)) {
            throw new BadRequestHttpException('The certificate year must contain four digits.');
        }

        if ($sessionId > 0 && !\in_array($sessionId, $sessionIds, true)) {
            throw new AccessDeniedHttpException('The selected session is outside your reporting scope.');
        }

        if ($courseId > 0 && !\in_array($courseId, $courseIds, true)) {
            throw new AccessDeniedHttpException('The selected course is outside your reporting scope.');
        }

        if ($sessionId > 0 && $courseId > 0) {
            $courseBelongsToSession = (bool) $this->connection->fetchOne(
                'SELECT 1
                   FROM session_rel_course session_course
                  WHERE session_course.session_id = :sessionId
                    AND session_course.c_id = :courseId
                  LIMIT 1',
                [
                    'sessionId' => $sessionId,
                    'courseId' => $courseId,
                ],
                [
                    'sessionId' => Types::INTEGER,
                    'courseId' => Types::INTEGER,
                ],
            );
            if (!$courseBelongsToSession) {
                throw new BadRequestHttpException('The selected course does not belong to the selected session.');
            }
        }

        if ($userId > 0 && (!$context->isStudentBoss || !\in_array($userId, $studentIds, true))) {
            throw new AccessDeniedHttpException('The selected learner is outside your reporting scope.');
        }

        $hasCertificateTables = $this->hasTables(['gradebook_certificate', 'gradebook_category']);
        $yearOptions = [];

        if ($hasCertificateTables && [] !== $studentIds && [] !== $courseIds) {
            $yearWhere = [
                'certificate.user_id IN (:yearUserIds)',
                'category.c_id IN (:yearCourseIds)',
            ];
            $yearParameters = [
                'yearUserIds' => $studentIds,
                'yearCourseIds' => $courseIds,
            ];
            $yearTypes = [
                'yearUserIds' => ArrayParameterType::INTEGER,
                'yearCourseIds' => ArrayParameterType::INTEGER,
            ];

            if ([] === $sessionIds) {
                $yearWhere[] = 'COALESCE(category.session_id, 0) = 0';
            } else {
                $yearWhere[] = '(COALESCE(category.session_id, 0) = 0 OR category.session_id IN (:yearSessionIds))';
                $yearParameters['yearSessionIds'] = $sessionIds;
                $yearTypes['yearSessionIds'] = ArrayParameterType::INTEGER;
            }

            $availableYears = $this->connection->fetchFirstColumn(
                'SELECT DISTINCT YEAR(certificate.created_at) AS certificateYear
                   FROM gradebook_certificate certificate
                   INNER JOIN gradebook_category category ON category.id = certificate.cat_id
                  WHERE '.implode(' AND ', $yearWhere).
                ' ORDER BY certificateYear DESC',
                $yearParameters,
                $yearTypes,
            );

            foreach ($availableYears as $availableYear) {
                $yearValue = (int) $availableYear;
                if ($yearValue <= 0) {
                    continue;
                }

                $yearOptions[] = [
                    'label' => (string) $yearValue,
                    'value' => (string) $yearValue,
                ];
            }
        }

        $meta = [
            'supportsKeyword' => false,
            'supportsReset' => true,
            'canExportCsv' => false,
            'canExportXlsx' => false,
            'supportsLearnerFilter' => $context->isStudentBoss,
            'sessionOptions' => $sessionOptions,
            'courseOptions' => $courseOptions,
            'monthOptions' => $monthOptions,
            'yearOptions' => $yearOptions,
            'learnerOptions' => $learnerOptions,
            'selectedSessionId' => $sessionId,
            'selectedCourseId' => $courseId,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'selectedUserId' => $userId,
            'searchPerformed' => true,
            'exportAllUrl' => null,
        ];

        if (!$hasCertificateTables || [] === $studentIds || [] === $courseIds) {
            return $this->result($filters, $title, 0, [], $columns, [], [], $meta);
        }

        $where = [
            'certificate.user_id IN (:scopedUserIds)',
            'category.c_id IN (:scopedCourseIds)',
        ];
        $parameters = [
            'scopedUserIds' => $studentIds,
            'scopedCourseIds' => $courseIds,
        ];
        $types = [
            'scopedUserIds' => ArrayParameterType::INTEGER,
            'scopedCourseIds' => ArrayParameterType::INTEGER,
        ];

        if ([] === $sessionIds) {
            $where[] = 'COALESCE(category.session_id, 0) = 0';
        } else {
            $where[] = '(COALESCE(category.session_id, 0) = 0 OR category.session_id IN (:scopedSessionIds))';
            $parameters['scopedSessionIds'] = $sessionIds;
            $types['scopedSessionIds'] = ArrayParameterType::INTEGER;
        }

        if ($sessionId > 0) {
            $where[] = 'category.session_id = :selectedSessionId';
            $parameters['selectedSessionId'] = $sessionId;
            $types['selectedSessionId'] = Types::INTEGER;
        }

        if ($courseId > 0) {
            $where[] = 'category.c_id = :selectedCourseId';
            $parameters['selectedCourseId'] = $courseId;
            $types['selectedCourseId'] = Types::INTEGER;

            if ($sessionId <= 0) {
                $where[] = 'COALESCE(category.session_id, 0) = 0';
            }
        }

        if ($userId > 0) {
            $where[] = 'certificate.user_id = :selectedUserId';
            $parameters['selectedUserId'] = $userId;
            $types['selectedUserId'] = Types::INTEGER;
        }

        if ($month > 0) {
            $where[] = 'MONTH(certificate.created_at) = :selectedMonth';
            $parameters['selectedMonth'] = $month;
            $types['selectedMonth'] = Types::INTEGER;
        }

        if ('' !== $year) {
            $where[] = 'YEAR(certificate.created_at) = :selectedYear';
            $parameters['selectedYear'] = (int) $year;
            $types['selectedYear'] = Types::INTEGER;
        }

        if ($courseId > 0) {
            $category = $this->connection->fetchAssociative(
                'SELECT category.id, course.code
                   FROM gradebook_category category
                   INNER JOIN course ON course.id = category.c_id
                   INNER JOIN access_url_rel_course access_course
                       ON access_course.c_id = course.id
                  WHERE category.c_id = :courseId
                    AND COALESCE(category.session_id, 0) = :sessionId
                    AND category.parent_id IS NULL
                    AND access_course.access_url_id = :accessUrlId
               ORDER BY category.id ASC
                  LIMIT 1',
                [
                    'courseId' => $courseId,
                    'sessionId' => $sessionId,
                    'accessUrlId' => $context->accessUrlId,
                ],
                [
                    'courseId' => Types::INTEGER,
                    'sessionId' => Types::INTEGER,
                    'accessUrlId' => Types::INTEGER,
                ],
            );

            if (false !== $category) {
                // Controlled compatibility fallback: this global HR/DRH export has a broader authorization
                // scope than the course-context Gradebook export endpoint and must remain legacy until a
                // dedicated authorization-safe global certificate export is available.
                $meta['exportAllUrl'] = '/main/gradebook/gradebook_display_certificate.php?'.http_build_query(
                    [
                        'action' => 'export_all_certificates',
                        'cidReq' => (string) $category['code'],
                        'id_session' => 0,
                        'gidReq' => 0,
                        'cat_id' => (int) $category['id'],
                    ],
                    '',
                    '&',
                    PHP_QUERY_RFC3986,
                );
            }
        }

        $from = ' FROM gradebook_certificate certificate
                  INNER JOIN user usr ON usr.id = certificate.user_id
                  INNER JOIN gradebook_category category ON category.id = certificate.cat_id
                  INNER JOIN course ON course.id = category.c_id
                  LEFT JOIN session ON session.id = category.session_id
                  INNER JOIN access_url_rel_user access_user
                      ON access_user.user_id = usr.id
                  INNER JOIN access_url_rel_course access_course
                      ON access_course.c_id = course.id
                 WHERE access_user.access_url_id = :accessUrlId
                   AND access_course.access_url_id = :accessUrlId
                   AND '.implode(' AND ', $where);

        $parameters['accessUrlId'] = $context->accessUrlId;
        $types['accessUrlId'] = Types::INTEGER;

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(certificate.id)'.$from,
            $parameters,
            $types,
        );

        $rows = $this->connection->fetchAllAssociative(
            'SELECT certificate.id,
                    usr.id AS userId,
                    usr.firstname,
                    usr.lastname,
                    usr.username,
                    COALESCE(session.title, \'\') AS session,
                    course.title AS course,
                    certificate.created_at AS createdAt,
                    certificate.path_certificate AS certificatePath'
            .$from.
            ' ORDER BY certificate.created_at DESC, certificate.id DESC
               LIMIT :limit OFFSET :offset',
            [
                ...$parameters,
                'limit' => $filters['itemsPerPage'],
                'offset' => $filters['offset'],
            ],
            [
                ...$types,
                'limit' => Types::INTEGER,
                'offset' => Types::INTEGER,
            ],
        );

        foreach ($rows as &$row) {
            $path = trim((string) ($row['certificatePath'] ?? ''));
            $hash = '' === $path ? '' : pathinfo(basename($path), PATHINFO_FILENAME);
            $row['id'] = (int) $row['id'];
            $row['userId'] = (int) $row['userId'];
            $row['fullName'] = trim((string) $row['firstname'].' '.(string) $row['lastname']);
            $row['certificateUrl'] = '' === $hash
                ? ''
                : '/certificates/'.rawurlencode($hash).'.html';
        }
        unset($row);

        return $this->result(
            $filters,
            $title,
            $total,
            [],
            $columns,
            $rows,
            [],
            $meta,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function certificateColumns(): array
    {
        return [
            ['key' => 'fullName', 'label' => 'Learner', 'type' => 'text'],
            ['key' => 'session', 'label' => 'Session', 'type' => 'text'],
            ['key' => 'course', 'label' => 'Course', 'type' => 'text'],
            ['key' => 'createdAt', 'label' => 'Date', 'type' => 'datetime'],
            ['key' => 'certificateUrl', 'label' => 'Certificate', 'type' => 'certificate-link'],
        ];
    }

    /**
     * @param int[] $sessionIds
     *
     * @return array<int, array{id: int, label: string, courseIds: int[]}>
     */
    private function getCertificateSessionOptions(array $sessionIds): array
    {
        if ([] === $sessionIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT session.id,
                    session.title,
                    GROUP_CONCAT(DISTINCT session_course.c_id ORDER BY session_course.c_id) AS courseIds
               FROM session
               LEFT JOIN session_rel_course session_course
                   ON session_course.session_id = session.id
              WHERE session.id IN (:sessionIds)
           GROUP BY session.id, session.title
           ORDER BY session.title ASC, session.id ASC',
            ['sessionIds' => $sessionIds],
            ['sessionIds' => ArrayParameterType::INTEGER],
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'label' => (string) $row['title'],
                'courseIds' => '' === (string) ($row['courseIds'] ?? '')
                    ? []
                    : array_map(
                        static fn (string $courseId): int => (int) $courseId,
                        explode(',', (string) $row['courseIds']),
                    ),
            ],
            $rows,
        );
    }

    /**
     * @param int[] $courseIds
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function getCertificateCourseOptions(array $courseIds): array
    {
        if ([] === $courseIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT course.id, course.title
               FROM course
              WHERE course.id IN (:courseIds)
           ORDER BY course.title ASC, course.id ASC',
            ['courseIds' => $courseIds],
            ['courseIds' => ArrayParameterType::INTEGER],
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'label' => (string) $row['title'],
            ],
            $rows,
        );
    }

    /**
     * @param int[] $userIds
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function getCertificateLearnerOptions(array $userIds): array
    {
        if ([] === $userIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT usr.id, usr.firstname, usr.lastname, usr.username
               FROM user usr
              WHERE usr.id IN (:userIds)
           ORDER BY usr.lastname ASC, usr.firstname ASC, usr.username ASC',
            ['userIds' => $userIds],
            ['userIds' => ArrayParameterType::INTEGER],
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'label' => trim(
                    (string) $row['firstname'].' '.(string) $row['lastname'].' ('.(string) $row['username'].')',
                ),
            ],
            $rows,
        );
    }

    /**
     * @param int[] $studentIds
     *
     * @return int[]
     */
    private function getCertificateStudentCourseIds(
        GlobalReportingContext $context,
        array $studentIds,
    ): array {
        if ([] === $studentIds) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (mixed $courseId): int => (int) $courseId,
            $this->connection->fetchFirstColumn(
                'SELECT direct_course.c_id
                   FROM course_rel_user direct_course
                   INNER JOIN access_url_rel_course access_course
                       ON access_course.c_id = direct_course.c_id
                  WHERE direct_course.user_id IN (:studentIds)
                    AND access_course.access_url_id = :accessUrlId
                  UNION
                 SELECT session_course.c_id
                   FROM session_rel_course_rel_user session_course
                   INNER JOIN access_url_rel_course access_course
                       ON access_course.c_id = session_course.c_id
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = session_course.session_id
                  WHERE session_course.user_id IN (:studentIds)
                    AND access_course.access_url_id = :accessUrlId
                    AND access_session.access_url_id = :accessUrlId
               ORDER BY c_id',
                [
                    'studentIds' => $studentIds,
                    'accessUrlId' => $context->accessUrlId,
                ],
                [
                    'studentIds' => ArrayParameterType::INTEGER,
                    'accessUrlId' => Types::INTEGER,
                ],
            ),
        )));
    }

    /**
     * @param int[] $studentIds
     *
     * @return int[]
     */
    private function getCertificateStudentSessionIds(
        GlobalReportingContext $context,
        array $studentIds,
    ): array {
        if ([] === $studentIds) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (mixed $sessionId): int => (int) $sessionId,
            $this->connection->fetchFirstColumn(
                'SELECT session_user.session_id
                   FROM session_rel_user session_user
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = session_user.session_id
                  WHERE session_user.user_id IN (:studentIds)
                    AND access_session.access_url_id = :accessUrlId
                  UNION
                 SELECT session_course.session_id
                   FROM session_rel_course_rel_user session_course
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = session_course.session_id
                  WHERE session_course.user_id IN (:studentIds)
                    AND access_session.access_url_id = :accessUrlId
               ORDER BY session_id',
                [
                    'studentIds' => $studentIds,
                    'accessUrlId' => $context->accessUrlId,
                ],
                [
                    'studentIds' => ArrayParameterType::INTEGER,
                    'accessUrlId' => Types::INTEGER,
                ],
            ),
        )));
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getCompanyReport(GlobalReportingContext $context, array $filters): array
    {
        $this->assertCompanyReportAccess($context);
        $userIds = $context->isAdministrator
            ? $this->allAccessUrlUserIds($context)
            : $this->dashboardQueryService->getScopedUserIds($context, self::USER_STATUS_STUDENT);
        if ([] === $userIds) {
            return $this->emptyResult($filters, 'Corporate report', []);
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT CONCAT(course.id, \'-\', usr.id) AS id,
                    course.title AS course,
                    usr.id AS userId,
                    CONCAT(usr.firstname, \' \', usr.lastname) AS user,
                    usr.email,
                    COALESCE(access_metrics.timeSeconds, 0) AS timeSeconds,
                    COALESCE(lp_metrics.progress, 0) AS progress,
                    CASE WHEN certificate.id IS NULL THEN \'No\' ELSE \'Yes\' END AS certificate,
                    COALESCE(lp_metrics.completed, 0) AS completedLearningPaths
               FROM user usr
               INNER JOIN course_rel_user subscription ON subscription.user_id = usr.id
               INNER JOIN course ON course.id = subscription.c_id
               INNER JOIN access_url_rel_course access_course
                   ON access_course.c_id = course.id AND access_course.access_url_id = :accessUrlId
               LEFT JOIN (
                    SELECT user_id, c_id,
                           SUM(TIMESTAMPDIFF(SECOND, login_course_date, COALESCE(logout_course_date, login_course_date))) AS timeSeconds
                      FROM track_e_course_access
                  GROUP BY user_id, c_id
               ) access_metrics ON access_metrics.user_id = usr.id AND access_metrics.c_id = course.id
               LEFT JOIN (
                    SELECT user_id, c_id,
                           AVG(progress) AS progress,
                           SUM(CASE WHEN progress >= 100 THEN 1 ELSE 0 END) AS completed
                      FROM c_lp_view
                  GROUP BY user_id, c_id
               ) lp_metrics ON lp_metrics.user_id = usr.id AND lp_metrics.c_id = course.id
               LEFT JOIN gradebook_category category ON category.c_id = course.id
               LEFT JOIN gradebook_certificate certificate
                   ON certificate.cat_id = category.id AND certificate.user_id = usr.id
              WHERE usr.id IN (:userIds)
           GROUP BY course.id, course.title, usr.id, usr.firstname, usr.lastname, usr.email,
                    access_metrics.timeSeconds, lp_metrics.progress, lp_metrics.completed, certificate.id
           ORDER BY course.title ASC, usr.lastname ASC, usr.firstname ASC',
            ['accessUrlId' => $context->accessUrlId, 'userIds' => $userIds],
            ['accessUrlId' => Types::INTEGER, 'userIds' => ArrayParameterType::INTEGER],
        );
        foreach ($rows as &$row) {
            $row['userId'] = (int) $row['userId'];
            $row['timeSeconds'] = (int) $row['timeSeconds'];
            $row['progress'] = round((float) $row['progress'], 2);
            $row['completedLearningPaths'] = (int) $row['completedLearningPaths'];
        }
        unset($row);
        if (!$context->showEmailAddresses) {
            $this->removeEmailFromRows($rows);
        }
        $total = \count($rows);
        $pageRows = \array_slice($rows, $filters['offset'], $filters['itemsPerPage']);

        return $this->result(
            $filters,
            'Corporate report',
            $total,
            [
                ['key' => 'subscriptions', 'label' => 'Subscriptions count', 'value' => $total],
                ['key' => 'users', 'label' => 'Users count', 'value' => \count(array_unique(array_column($rows, 'userId')))],
            ],
            [
                ['key' => 'course', 'label' => 'Course', 'type' => 'text'],
                ['key' => 'user', 'label' => 'User', 'type' => 'text'],
                ...($context->showEmailAddresses
                    ? [['key' => 'email', 'label' => 'E-mail', 'type' => 'text']]
                    : []),
                ['key' => 'timeSeconds', 'label' => 'Man hours', 'type' => 'duration'],
                ['key' => 'certificate', 'label' => 'Generated certificate', 'type' => 'status'],
                ['key' => 'completedLearningPaths', 'label' => 'Completed learning paths', 'type' => 'number'],
                ['key' => 'progress', 'label' => 'Course progress', 'type' => 'percent'],
            ],
            $pageRows,
            [],
            ['canExportCsv' => true, 'canExportXlsx' => true],
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getCompanySummary(GlobalReportingContext $context, array $filters): array
    {
        $this->assertCompanyReportAccess($context);
        $detail = $this->getCompanyReport($context, [...$filters, 'itemsPerPage' => 100000, 'offset' => 0]);
        $items = (array) $detail['items'];
        $userIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['userId'],
            $items,
        )));
        $organizationByUser = $this->getOrganizationValues($userIds);
        $summary = [];
        foreach ($items as $item) {
            $organization = $organizationByUser[(int) $item['userId']] ?? 'Without organization';
            if (!isset($summary[$organization])) {
                $summary[$organization] = [
                    'id' => md5($organization),
                    'company' => $organization,
                    'timeSeconds' => 0,
                    'subscriptions' => 0,
                    'users' => [],
                    'certificates' => 0,
                ];
            }
            $summary[$organization]['timeSeconds'] += (int) $item['timeSeconds'];
            ++$summary[$organization]['subscriptions'];
            $summary[$organization]['users'][(int) $item['userId']] = true;
            if ('Yes' === $item['certificate']) {
                ++$summary[$organization]['certificates'];
            }
        }

        $rows = [];
        foreach ($summary as $row) {
            $userCount = \count($row['users']);
            $rows[] = [
                'id' => $row['id'],
                'company' => $row['company'],
                'timeSeconds' => $row['timeSeconds'],
                'subscriptions' => $row['subscriptions'],
                'users' => $userCount,
                'averageTimeSeconds' => $userCount > 0 ? (int) round($row['timeSeconds'] / $userCount) : 0,
                'certificates' => $row['certificates'],
            ];
        }
        usort($rows, static fn (array $left, array $right): int => strcasecmp($left['company'], $right['company']));
        $total = \count($rows);

        return $this->result(
            $filters,
            'Corporate report, short version',
            $total,
            [['key' => 'companies', 'label' => 'Company', 'value' => $total]],
            [
                ['key' => 'company', 'label' => 'Company', 'type' => 'text'],
                ['key' => 'timeSeconds', 'label' => 'Hours of accumulated training', 'type' => 'duration'],
                ['key' => 'subscriptions', 'label' => 'Subscriptions count', 'type' => 'number'],
                ['key' => 'users', 'label' => 'Users count', 'type' => 'number'],
                ['key' => 'averageTimeSeconds', 'label' => 'Avg hours/student', 'type' => 'duration'],
                ['key' => 'certificates', 'label' => 'Certificates count', 'type' => 'number'],
            ],
            \array_slice($rows, $filters['offset'], $filters['itemsPerPage']),
            [],
            ['canExportCsv' => true, 'canExportXlsx' => true],
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getLearningResults(GlobalReportingContext $context, array $filters, bool $bySession): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $courseIds = $this->allAccessUrlCourseIds($context);
        $title = $bySession
            ? 'Results of learning paths exercises by session'
            : 'Learning paths exercises results list';
        $meta = [
            'supportsCourse' => !$bySession,
            'supportsSession' => true,
            'requiresCourse' => !$bySession,
            'requiresSession' => $bySession,
            'courseOptions' => $this->getAdminCourseOptions($context),
            'sessionOptions' => $this->getAdminSessionOptions($context, (int) $filters['courseId']),
            'submitLabel' => 'Filter',
            'canExportCsv' => true,
            'canExportXlsx' => true,
        ];

        if ([] === $courseIds) {
            return $this->emptyResult($filters, $title, [], $meta);
        }
        if ((!$bySession && (int) $filters['courseId'] <= 0)
            || ($bySession && (int) $filters['sessionId'] <= 0)
        ) {
            return $this->result($filters, $title, 0, [], [], [], [], $meta);
        }

        $where = ['lp_view.c_id IN (:courseIds)'];
        $parameters = ['courseIds' => $courseIds];
        $types = ['courseIds' => ArrayParameterType::INTEGER];
        if ((int) $filters['courseId'] > 0) {
            $where[] = 'lp_view.c_id = :courseId';
            $parameters['courseId'] = (int) $filters['courseId'];
            $types['courseId'] = Types::INTEGER;
        }
        if ((int) $filters['sessionId'] > 0) {
            $where[] = 'COALESCE(lp_view.session_id, 0) = :sessionId';
            $parameters['sessionId'] = (int) $filters['sessionId'];
            $types['sessionId'] = Types::INTEGER;
        }

        if ($bySession) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT usr.id,
                        CONCAT(usr.firstname, \' \', usr.lastname) AS user,
                        course.title AS course,
                        AVG(lp_view.progress) AS averageScore,
                        MAX(usr.last_login) AS lastAccess
                   FROM c_lp_view lp_view
                   INNER JOIN user usr ON usr.id = lp_view.user_id
                   INNER JOIN course ON course.id = lp_view.c_id
                  WHERE '.implode(' AND ', $where).'
               GROUP BY usr.id, usr.firstname, usr.lastname, course.id, course.title
               ORDER BY usr.lastname ASC, usr.firstname ASC, course.title ASC',
                $parameters,
                $types,
            );
            foreach ($rows as &$row) {
                $row['id'] = (int) $row['id'].'-'.md5((string) $row['course']);
                $row['averageScore'] = round((float) ($row['averageScore'] ?? 0), 2);
            }
            unset($row);

            return $this->result(
                $filters,
                $title,
                \count($rows),
                [],
                [
                    ['key' => 'user', 'label' => 'User', 'type' => 'text'],
                    ['key' => 'course', 'label' => 'Course', 'type' => 'text'],
                    ['key' => 'averageScore', 'label' => 'Average score %', 'type' => 'percent'],
                    ['key' => 'lastAccess', 'label' => 'Latest connection date', 'type' => 'date-status'],
                ],
                \array_slice($rows, $filters['offset'], $filters['itemsPerPage']),
                [],
                $meta,
            );
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT lp_view.c_id AS id,
                    course.title,
                    COUNT(DISTINCT lp_view.user_id) AS learners,
                    COUNT(DISTINCT lp_view.lp_id) AS learningPaths,
                    AVG(lp_view.progress) AS progress,
                    SUM(CASE WHEN lp_view.progress >= 100 THEN 1 ELSE 0 END) AS completed
               FROM c_lp_view lp_view
               INNER JOIN course ON course.id = lp_view.c_id
              WHERE '.implode(' AND ', $where).'
           GROUP BY lp_view.c_id, course.title
           ORDER BY course.title ASC',
            $parameters,
            $types,
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['learners'] = (int) $row['learners'];
            $row['learningPaths'] = (int) $row['learningPaths'];
            $row['progress'] = round((float) $row['progress'], 2);
            $row['completed'] = (int) $row['completed'];
        }
        unset($row);

        return $this->result(
            $filters,
            $title,
            \count($rows),
            [],
            [
                ['key' => 'title', 'label' => 'Course', 'type' => 'text'],
                ['key' => 'learners', 'label' => 'Learners', 'type' => 'number'],
                ['key' => 'learningPaths', 'label' => 'Learning paths', 'type' => 'number'],
                ['key' => 'completed', 'label' => 'Completed', 'type' => 'number'],
                ['key' => 'progress', 'label' => 'Progress', 'type' => 'percent'],
            ],
            \array_slice($rows, $filters['offset'], $filters['itemsPerPage']),
            [],
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getExerciseCategories(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $columns = [
            ['key' => 'course', 'label' => 'Course', 'type' => 'text'],
            ['key' => 'test', 'label' => 'Tests', 'type' => 'text'],
            ['key' => 'learners', 'label' => 'Learners', 'type' => 'number'],
            ['key' => 'attempts', 'label' => 'Attempts', 'type' => 'number'],
            ['key' => 'score', 'label' => 'Average score', 'type' => 'percent'],
            ['key' => 'lastAttempt', 'label' => 'Latest attempt', 'type' => 'date-status'],
        ];
        $meta = [
            'supportsStartDate' => true,
            'supportsCourse' => true,
            'supportsExercise' => true,
            'courseOptions' => $this->getAdminCourseOptions($context),
            'exerciseOptions' => $this->getAdminExerciseOptions((int) $filters['courseId']),
            'submitLabel' => 'Search',
            'canExportCsv' => true,
            'canExportXlsx' => true,
        ];

        if (!$this->hasTables(['c_quiz', 'track_e_exercises', 'resource_link'])) {
            return $this->emptyResult($filters, 'Exercise report by category for all sessions', $columns, $meta);
        }

        if ((int) $filters['courseId'] <= 0 || (int) $filters['exerciseId'] <= 0) {
            return $this->result(
                $filters,
                'Exercise report by category for all sessions',
                0,
                [],
                $columns,
                [],
                [],
                $meta,
            );
        }

        $where = [
            'resource_link.c_id = :courseId',
            'quiz.iid = :exerciseId',
            'resource_link.deleted_at IS NULL',
        ];
        $parameters = [
            'courseId' => (int) $filters['courseId'],
            'exerciseId' => (int) $filters['exerciseId'],
        ];
        $types = [
            'courseId' => Types::INTEGER,
            'exerciseId' => Types::INTEGER,
        ];
        if (null !== $filters['startDate']) {
            $where[] = 'exercise.exe_date >= :startDate';
            $parameters['startDate'] = $filters['startDate'];
            $types['startDate'] = Types::DATE_IMMUTABLE;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT quiz.iid AS id,
                    course.title AS course,
                    quiz.title AS test,
                    COUNT(exercise.exe_id) AS attempts,
                    COUNT(DISTINCT exercise.exe_user_id) AS learners,
                    AVG(CASE WHEN exercise.max_score > 0 THEN exercise.score * 100 / exercise.max_score ELSE 0 END) AS score,
                    MAX(exercise.exe_date) AS lastAttempt
               FROM c_quiz quiz
               INNER JOIN resource_link ON resource_link.resource_node_id = quiz.resource_node_id
               INNER JOIN course ON course.id = resource_link.c_id
               LEFT JOIN track_e_exercises exercise
                   ON exercise.exe_exo_id = quiz.iid
                  AND exercise.c_id = resource_link.c_id
              WHERE '.implode(' AND ', $where).'
           GROUP BY quiz.iid, course.title, quiz.title',
            $parameters,
            $types,
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['attempts'] = (int) $row['attempts'];
            $row['learners'] = (int) $row['learners'];
            $row['score'] = round((float) ($row['score'] ?? 0), 2);
        }
        unset($row);

        return $this->result(
            $filters,
            'Exercise report by category for all sessions',
            \count($rows),
            [],
            $columns,
            $rows,
            [],
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getSurveyReport(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $columns = [
            ['key' => 'course', 'label' => 'Course', 'type' => 'text'],
            ['key' => 'title', 'label' => 'Survey', 'type' => 'html'],
            ['key' => 'answers', 'label' => 'Answers', 'type' => 'number'],
        ];
        $meta = [
            'renderMode' => 'survey-user',
            'supportsUser' => true,
            'requiresUser' => true,
            'userOptions' => $this->getAdminUserOptions($context, null, 0, 0),
            'submitLabel' => 'Search',
            'canExportCsv' => true,
            'canExportXlsx' => true,
        ];

        if (!$this->hasTables(['c_survey', 'c_survey_answer', 'resource_link'])) {
            return $this->emptyResult($filters, 'Surveys report', $columns, $meta);
        }

        if ((int) $filters['userId'] <= 0) {
            return $this->result($filters, 'Surveys report', 0, [], $columns, [], [], $meta);
        }

        $allowedUserIds = $this->allAccessUrlUserIds($context);
        if (!\in_array((int) $filters['userId'], $allowedUserIds, true)) {
            throw new AccessDeniedHttpException('The requested user is outside the current access URL.');
        }

        $courseIds = $this->allAccessUrlCourseIds($context);
        if ([] === $courseIds) {
            return $this->result($filters, 'Surveys report', 0, [], $columns, [], [], $meta);
        }

        $selectedUser = $this->userRepository->find((int) $filters['userId']);
        $rows = $this->connection->fetchAllAssociative(
            'SELECT survey.iid AS id,
                    course.title AS course,
                    survey.title,
                    COUNT(answer.iid) AS answers
               FROM c_survey survey
               INNER JOIN resource_link ON resource_link.resource_node_id = survey.resource_node_id
               INNER JOIN course ON course.id = resource_link.c_id
               INNER JOIN c_survey_answer answer
                   ON answer.survey_id = survey.iid
                  AND answer.user = :userId
              WHERE resource_link.c_id IN (:courseIds)
                AND resource_link.deleted_at IS NULL
           GROUP BY survey.iid, course.title, survey.title
           ORDER BY course.title ASC, survey.title ASC',
            [
                'userId' => (int) $filters['userId'],
                'courseIds' => $courseIds,
            ],
            [
                'userId' => Types::INTEGER,
                'courseIds' => ArrayParameterType::INTEGER,
            ],
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['answers'] = (int) $row['answers'];
        }
        unset($row);
        $meta['selectedUser'] = null === $selectedUser
            ? null
            : [
                'id' => (int) $selectedUser->getId(),
                'fullName' => trim($selectedUser->getFirstname().' '.$selectedUser->getLastname()),
                'username' => $selectedUser->getUsername(),
            ];

        return $this->result(
            $filters,
            'Surveys report',
            \count($rows),
            [],
            $columns,
            $rows,
            [],
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getTutorPlanning(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $meta = [
            'renderMode' => 'tutor-planning',
            'supportsDateRange' => true,
            'submitLabel' => 'Search',
            'canExportCsv' => false,
            'canExportXlsx' => false,
        ];
        $where = [
            'relation.relation_type = :relationType',
            'access_session.access_url_id = :accessUrlId',
        ];
        $parameters = [
            'relationType' => self::SESSION_RELATION_TYPE_GENERAL_COACH,
            'accessUrlId' => $context->accessUrlId,
        ];
        $types = [
            'relationType' => Types::INTEGER,
            'accessUrlId' => Types::INTEGER,
        ];
        if (null !== $filters['startDate']) {
            $where[] = 'session.display_start_date >= :startDate';
            $parameters['startDate'] = $filters['startDate'];
            $types['startDate'] = Types::DATE_IMMUTABLE;
        }
        if (null !== $filters['endDate']) {
            $where[] = 'session.display_start_date < DATE_ADD(:endDate, INTERVAL 1 DAY)';
            $parameters['endDate'] = $filters['endDate'];
            $types['endDate'] = Types::DATE_IMMUTABLE;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT usr.id,
                    CONCAT(usr.firstname, \' \', usr.lastname) AS tutor,
                    usr.username,
                    session.id AS sessionId,
                    session.title AS session,
                    session.display_start_date AS startDate,
                    session.display_end_date AS endDate
               FROM session_rel_user relation
               INNER JOIN user usr ON usr.id = relation.user_id
               INNER JOIN session ON session.id = relation.session_id
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = session.id
              WHERE '.implode(' AND ', $where).'
           ORDER BY usr.lastname ASC, usr.firstname ASC, session.display_start_date ASC',
            $parameters,
            $types,
        );

        $tutors = [];
        foreach ($rows as $row) {
            $userId = (int) $row['id'];
            if (!isset($tutors[$userId])) {
                $tutors[$userId] = [
                    'id' => $userId,
                    'tutor' => (string) $row['tutor'],
                    'username' => (string) $row['username'],
                    'sessions' => [],
                ];
            }
            $tutors[$userId]['sessions'][] = [
                'id' => (int) $row['sessionId'],
                'title' => (string) $row['session'],
                'startDate' => $row['startDate'],
                'endDate' => $row['endDate'],
                'url' => '/main/session/resume_session.php?id_session='.(int) $row['sessionId'],
            ];
        }

        return $this->result(
            $filters,
            'General tutor planning',
            \count($tutors),
            [],
            [],
            array_values($tutors),
            [],
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getQuestionStats(GlobalReportingContext $context, array $filters, bool $detailed): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $title = $detailed ? 'Detailed questions stats' : 'Question stats';
        $columns = [
            ['key' => 'course', 'label' => 'Course', 'type' => 'text'],
            ['key' => 'test', 'label' => 'Test', 'type' => 'text'],
            ['key' => 'question', 'label' => 'Question', 'type' => 'html'],
        ];
        if ($detailed) {
            $columns[] = ['key' => 'user', 'label' => 'User', 'type' => 'text'];
        }
        array_push(
            $columns,
            ['key' => 'attempts', 'label' => 'Attempts', 'type' => 'number'],
            ['key' => 'averageMarks', 'label' => 'Average score', 'type' => 'number'],
            ['key' => 'weighting', 'label' => 'Weight', 'type' => 'number'],
            ['key' => 'success', 'label' => 'Percentage', 'type' => 'percent'],
        );

        $meta = [
            'supportsCourse' => true,
            'supportsExercise' => true,
            'supportsUser' => !$detailed,
            'courseOptions' => $this->getAdminCourseOptions($context),
            'exerciseOptions' => $this->getAdminExerciseOptions((int) $filters['courseId']),
            'userOptions' => $this->getAdminUserOptions($context, null, (int) $filters['courseId'], 0),
            'submitLabel' => 'Search',
            'canExportCsv' => true,
            'canExportXlsx' => true,
        ];

        if (!$this->hasTables(['track_e_attempt', 'track_e_exercises', 'c_quiz_question', 'c_quiz'])) {
            return $this->emptyResult($filters, $title, $columns, $meta);
        }

        if ((int) $filters['courseId'] <= 0) {
            return $this->result($filters, $title, 0, [], $columns, [], [], $meta);
        }

        $where = ['exercise.c_id = :courseId'];
        $parameters = ['courseId' => (int) $filters['courseId']];
        $types = ['courseId' => Types::INTEGER];
        if ((int) $filters['exerciseId'] > 0) {
            $where[] = 'exercise.exe_exo_id = :exerciseId';
            $parameters['exerciseId'] = (int) $filters['exerciseId'];
            $types['exerciseId'] = Types::INTEGER;
        }
        if ((int) $filters['userId'] > 0) {
            $where[] = 'exercise.exe_user_id = :userId';
            $parameters['userId'] = (int) $filters['userId'];
            $types['userId'] = Types::INTEGER;
        }

        $selectUser = $detailed
            ? ", exercise.exe_user_id AS userId, CONCAT(usr.firstname, ' ', usr.lastname) AS user"
            : '';
        $joinUser = $detailed ? 'LEFT JOIN user usr ON usr.id = exercise.exe_user_id' : '';
        $groupUser = $detailed ? ', exercise.exe_user_id, usr.firstname, usr.lastname' : '';
        $idExpression = $detailed
            ? "CONCAT(attempt.question_id, '-', exercise.exe_user_id)"
            : 'attempt.question_id';

        $rows = $this->connection->fetchAllAssociative(
            'SELECT '.$idExpression.' AS id,
                    course.title AS course,
                    quiz.title AS test,
                    question.question,
                    COUNT(*) AS attempts,
                    ROUND(AVG(attempt.marks), 2) AS averageMarks,
                    question.ponderation AS weighting'.$selectUser.'
               FROM track_e_attempt attempt
               INNER JOIN track_e_exercises exercise ON exercise.exe_id = attempt.exe_id
               INNER JOIN c_quiz_question question ON question.iid = attempt.question_id
               LEFT JOIN c_quiz quiz ON quiz.iid = exercise.exe_exo_id
               INNER JOIN course ON course.id = exercise.c_id
               '.$joinUser.'
              WHERE '.implode(' AND ', $where).'
           GROUP BY attempt.question_id, question.question, question.ponderation,
                    quiz.iid, quiz.title, course.id, course.title'.$groupUser.'
           ORDER BY quiz.title ASC, attempt.question_id ASC',
            $parameters,
            $types,
        );
        foreach ($rows as &$row) {
            $row['attempts'] = (int) $row['attempts'];
            $row['averageMarks'] = round((float) $row['averageMarks'], 2);
            $row['weighting'] = round((float) $row['weighting'], 2);
            $row['success'] = $row['weighting'] > 0.0
                ? round(100 * $row['averageMarks'] / $row['weighting'], 2)
                : 0.0;
        }
        unset($row);

        return $this->result(
            $filters,
            $title,
            \count($rows),
            [],
            $columns,
            \array_slice($rows, $filters['offset'], $filters['itemsPerPage']),
            [],
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getOrganizationReport(GlobalReportingContext $context, array $filters): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $userIds = $this->allAccessUrlUserIds($context);
        $organizations = $this->getOrganizationValues($userIds);
        $counts = [];
        foreach ($organizations as $organization) {
            $counts[$organization] = ($counts[$organization] ?? 0) + 1;
        }
        $rows = [];
        foreach ($counts as $organization => $users) {
            $rows[] = ['id' => md5($organization), 'organization' => $organization, 'users' => $users];
        }
        usort($rows, static fn (array $left, array $right): int => strcasecmp($left['organization'], $right['organization']));

        return $this->simpleRowsResult(
            $filters,
            'User by organization',
            $rows,
            [
                ['key' => 'organization', 'label' => 'Company', 'type' => 'text'],
                ['key' => 'users', 'label' => 'Users count', 'type' => 'number'],
            ],
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getLearningPathAuthors(GlobalReportingContext $context, array $filters, bool $items): array
    {
        $this->assertAdministratorOrHumanResources($context);
        $courseIds = $this->allAccessUrlCourseIds($context);
        if ([] === $courseIds || !$this->hasTables(['resource_link', 'c_lp'])) {
            return $this->emptyResult($filters, $items ? 'LP item by author' : 'Learning path by author', []);
        }

        if ($items) {
            if (!$this->hasTables(['c_lp_item', 'extra_field', 'extra_field_values'])) {
                return $this->emptyResult($filters, 'LP item by author', []);
            }
            $rows = $this->connection->fetchAllAssociative(
                "SELECT MIN(item.iid) AS id,
                        COALESCE(NULLIF(TRIM(field_values.field_value), ''), 'Unknown') AS author,
                        COUNT(*) AS items,
                        GROUP_CONCAT(DISTINCT course.title ORDER BY course.title SEPARATOR ', ') AS courses
                   FROM c_lp_item item
                   INNER JOIN c_lp lp ON lp.iid = item.lp_id
                   INNER JOIN resource_link ON resource_link.resource_node_id = lp.resource_node_id
                   INNER JOIN course ON course.id = resource_link.c_id
                   LEFT JOIN extra_field field ON field.variable = 'authorlpitem'
                   LEFT JOIN extra_field_values field_values
                       ON field_values.field_id = field.id AND field_values.item_id = item.iid
                  WHERE resource_link.c_id IN (:courseIds)
                    AND resource_link.deleted_at IS NULL
               GROUP BY COALESCE(NULLIF(TRIM(field_values.field_value), ''), 'Unknown')
               ORDER BY author ASC",
                ['courseIds' => $courseIds],
                ['courseIds' => ArrayParameterType::INTEGER],
            );
        } else {
            $rows = $this->connection->fetchAllAssociative(
                "SELECT MIN(lp.iid) AS id,
                        COALESCE(NULLIF(TRIM(lp.author), ''), 'Unknown') AS author,
                        COUNT(*) AS items,
                        GROUP_CONCAT(DISTINCT course.title ORDER BY course.title SEPARATOR ', ') AS courses
                   FROM c_lp lp
                   INNER JOIN resource_link ON resource_link.resource_node_id = lp.resource_node_id
                   INNER JOIN course ON course.id = resource_link.c_id
                  WHERE resource_link.c_id IN (:courseIds)
                    AND resource_link.deleted_at IS NULL
               GROUP BY COALESCE(NULLIF(TRIM(lp.author), ''), 'Unknown')
               ORDER BY author ASC",
                ['courseIds' => $courseIds],
                ['courseIds' => ArrayParameterType::INTEGER],
            );
        }

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['items'] = (int) $row['items'];
        }
        unset($row);

        return $this->simpleRowsResult(
            $filters,
            $items ? 'LP item by author' : 'Learning path by author',
            $rows,
            [
                ['key' => 'author', 'label' => 'Author', 'type' => 'text'],
                ['key' => 'items', 'label' => $items ? 'Items' : 'Learning paths', 'type' => 'number'],
                ['key' => 'courses', 'label' => 'Courses', 'type' => 'text'],
            ],
        );
    }

    /**
     * @param int[] $userIds
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function getAdminUserCourseOverview(array $userIds): array
    {
        if ([] === $userIds) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT membership.user_id AS userId,
                    course.id,
                    course.code,
                    course.title,
                    COALESCE((
                        SELECT SUM(
                            CASE
                                WHEN access_log.logout_course_date IS NOT NULL
                                 AND access_log.logout_course_date >= access_log.login_course_date
                                THEN TIMESTAMPDIFF(
                                    SECOND,
                                    access_log.login_course_date,
                                    access_log.logout_course_date
                                )
                                ELSE 0
                            END
                        )
                          FROM track_e_course_access access_log
                         WHERE access_log.user_id = membership.user_id
                           AND access_log.c_id = course.id
                    ), 0) AS timeSeconds,
                    COALESCE((
                        SELECT AVG(lp_view.progress)
                          FROM c_lp_view lp_view
                         WHERE lp_view.user_id = membership.user_id
                           AND lp_view.c_id = course.id
                    ), 0) AS progress,
                    COALESCE((
                        SELECT AVG(
                            CASE
                                WHEN exercise.max_score > 0
                                THEN exercise.score * 100 / exercise.max_score
                                ELSE 0
                            END
                        )
                          FROM track_e_exercises exercise
                         WHERE exercise.exe_user_id = membership.user_id
                           AND exercise.c_id = course.id
                    ), 0) AS testScore,
                    (
                        SELECT AVG(
                            CASE
                                WHEN exercise.max_score > 0
                                THEN exercise.score * 100 / exercise.max_score
                                ELSE 0
                            END
                        )
                          FROM track_e_exercises exercise
                         WHERE exercise.exe_user_id = membership.user_id
                           AND exercise.c_id = course.id
                           AND COALESCE(exercise.orig_lp_id, 0) > 0
                    ) AS averageLearningPathScore,
                    COALESCE((
                        SELECT COUNT(post.iid)
                          FROM c_forum_post post
                          INNER JOIN resource_link post_link
                              ON post_link.resource_node_id = post.resource_node_id
                         WHERE post.poster_id = membership.user_id
                           AND post_link.c_id = course.id
                           AND post_link.deleted_at IS NULL
                    ), 0) AS messages,
                    COALESCE((
                        SELECT COUNT(publication.iid)
                          FROM c_student_publication publication
                          INNER JOIN resource_link publication_link
                              ON publication_link.resource_node_id = publication.resource_node_id
                         WHERE publication.user_id = membership.user_id
                           AND publication_link.c_id = course.id
                           AND publication_link.deleted_at IS NULL
                           AND publication.parent_id IS NOT NULL
                    ), 0) AS assignments,
                    COALESCE((
                        SELECT COUNT(exercise.exe_id)
                          FROM track_e_exercises exercise
                         WHERE exercise.exe_user_id = membership.user_id
                           AND exercise.c_id = course.id
                    ), 0) AS testsAnswered,
                    (
                        SELECT MAX(access_log.login_course_date)
                          FROM track_e_course_access access_log
                         WHERE access_log.user_id = membership.user_id
                           AND access_log.c_id = course.id
                    ) AS lastAccess
               FROM (
                    SELECT relation.user_id, relation.c_id
                      FROM course_rel_user relation
                     WHERE relation.user_id IN (:baseUserIds)
                       AND relation.status = :studentStatus
                    UNION
                    SELECT session_relation.user_id, session_relation.c_id
                      FROM session_rel_course_rel_user session_relation
                     WHERE session_relation.user_id IN (:sessionUserIds)
               ) membership
               INNER JOIN course ON course.id = membership.c_id
           ORDER BY membership.user_id ASC, course.title ASC, course.id ASC',
            [
                'baseUserIds' => $userIds,
                'sessionUserIds' => $userIds,
                'studentStatus' => self::USER_STATUS_STUDENT,
            ],
            [
                'baseUserIds' => ArrayParameterType::INTEGER,
                'sessionUserIds' => ArrayParameterType::INTEGER,
                'studentStatus' => Types::INTEGER,
            ],
        );

        $coursesByUser = [];
        foreach ($rows as $row) {
            $userId = (int) $row['userId'];
            $coursesByUser[$userId][] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'title' => (string) $row['title'],
                'timeSeconds' => (int) $row['timeSeconds'],
                'progress' => round((float) $row['progress'], 2),
                'testScore' => round((float) $row['testScore'], 2),
                'averageLearningPathScore' => null === $row['averageLearningPathScore']
                    ? null
                    : round((float) $row['averageLearningPathScore'], 2),
                'messages' => (int) $row['messages'],
                'assignments' => (int) $row['assignments'],
                'testsAnswered' => (int) $row['testsAnswered'],
                'lastAccess' => $row['lastAccess'],
            ];
        }

        return $coursesByUser;
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function getAdminCourseOptions(GlobalReportingContext $context): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT course.id, course.title
               FROM course
               INNER JOIN access_url_rel_course access_course
                   ON access_course.c_id = course.id
              WHERE access_course.access_url_id = :accessUrlId
           ORDER BY course.title ASC, course.id ASC',
            ['accessUrlId' => $context->accessUrlId],
            ['accessUrlId' => Types::INTEGER],
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'label' => (string) $row['title'],
            ],
            $rows,
        );
    }

    /**
     * @return array<int, array{id: int, label: string, courseIds: int[]}>
     */
    private function getAdminSessionOptions(GlobalReportingContext $context, int $courseId = 0): array
    {
        $where = ['access_session.access_url_id = :accessUrlId'];
        $parameters = ['accessUrlId' => $context->accessUrlId];
        $types = ['accessUrlId' => Types::INTEGER];
        if ($courseId > 0) {
            $where[] = 'session_course.c_id = :courseId';
            $parameters['courseId'] = $courseId;
            $types['courseId'] = Types::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT session.id, session.title,
                    GROUP_CONCAT(DISTINCT session_course.c_id ORDER BY session_course.c_id) AS courseIds
               FROM session
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = session.id
               LEFT JOIN session_rel_course session_course
                   ON session_course.session_id = session.id
              WHERE '.implode(' AND ', $where).'
           GROUP BY session.id, session.title
           ORDER BY session.title ASC, session.id ASC',
            $parameters,
            $types,
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'label' => (string) $row['title'],
                'courseIds' => '' === (string) ($row['courseIds'] ?? '')
                    ? []
                    : array_map(static fn (string $id): int => (int) $id, explode(',', (string) $row['courseIds'])),
            ],
            $rows,
        );
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function getAdminProfileOptions(): array
    {
        return [
            ['id' => self::USER_STATUS_STUDENT, 'label' => 'Learner'],
            ['id' => self::USER_STATUS_TEACHER, 'label' => 'Teacher'],
            ['id' => self::USER_STATUS_HUMAN_RESOURCES, 'label' => 'Human Resources Manager'],
        ];
    }

    /**
     * @return array<int, array{id: int, label: string, sublabel: string}>
     */
    private function getAdminUserOptions(
        GlobalReportingContext $context,
        ?int $status = null,
        int $courseId = 0,
        int $sessionId = 0,
    ): array {
        $where = [
            'access_user.access_url_id = :accessUrlId',
            'usr.active <> :softDeleted',
        ];
        $parameters = [
            'accessUrlId' => $context->accessUrlId,
            'softDeleted' => User::SOFT_DELETED,
        ];
        $types = [
            'accessUrlId' => Types::INTEGER,
            'softDeleted' => Types::INTEGER,
        ];
        if (null !== $status && $status > 0) {
            $where[] = 'usr.status = :status';
            $parameters['status'] = $status;
            $types['status'] = Types::INTEGER;
        }
        if ($courseId > 0) {
            $where[] = '(
                EXISTS (
                    SELECT 1
                      FROM course_rel_user course_user
                     WHERE course_user.user_id = usr.id
                       AND course_user.c_id = :courseId
                )
                OR EXISTS (
                    SELECT 1
                      FROM session_rel_course_rel_user session_user
                     WHERE session_user.user_id = usr.id
                       AND session_user.c_id = :courseId
                )
            )';
            $parameters['courseId'] = $courseId;
            $types['courseId'] = Types::INTEGER;
        }
        if ($sessionId > 0) {
            $where[] = 'EXISTS (
                SELECT 1
                  FROM session_rel_course_rel_user session_user
                 WHERE session_user.user_id = usr.id
                   AND session_user.session_id = :sessionId
            )';
            $parameters['sessionId'] = $sessionId;
            $types['sessionId'] = Types::INTEGER;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT DISTINCT usr.id, usr.firstname, usr.lastname, usr.username
               FROM user usr
               INNER JOIN access_url_rel_user access_user ON access_user.user_id = usr.id
              WHERE '.implode(' AND ', $where).'
           ORDER BY usr.lastname ASC, usr.firstname ASC, usr.username ASC',
            $parameters,
            $types,
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'label' => trim((string) $row['firstname'].' '.(string) $row['lastname']),
                'sublabel' => (string) $row['username'],
            ],
            $rows,
        );
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function getAdminExerciseOptions(int $courseId): array
    {
        if ($courseId <= 0 || !$this->hasTables(['c_quiz', 'resource_link'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT quiz.iid AS id, quiz.title AS label
               FROM c_quiz quiz
               INNER JOIN resource_link
                   ON resource_link.resource_node_id = quiz.resource_node_id
              WHERE resource_link.c_id = :courseId
                AND resource_link.deleted_at IS NULL
           ORDER BY quiz.title ASC, quiz.iid ASC',
            ['courseId' => $courseId],
            ['courseId' => Types::INTEGER],
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'label' => (string) $row['label'],
            ],
            $rows,
        );
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getWorksInSession(GlobalReportingContext $context, array $filters): array
    {
        $sessionIds = $context->isAdministrator
            ? $this->allAccessUrlSessionIds($context)
            : $this->getTrackedSessionIds($context);
        $requestedSessionId = (int) $filters['sessionId'];
        if ($requestedSessionId > 0) {
            if (!\in_array($requestedSessionId, $sessionIds, true)) {
                throw new AccessDeniedHttpException('The requested session is outside your reporting scope.');
            }
            $sessionIds = [$requestedSessionId];
        }

        if ([] === $sessionIds || !$this->hasTables(['c_student_publication', 'resource_link'])) {
            return $this->emptyResult($filters, 'Works in session report', []);
        }
        $rows = $this->connection->fetchAllAssociative(
            'SELECT publication.iid AS id,
                    session.title AS session,
                    course.title AS course,
                    publication.title,
                    publication.author,
                    publication.sent_date AS sentAt,
                    publication.qualification,
                    publication.weight,
                    publication.accepted
               FROM c_student_publication publication
               INNER JOIN resource_link ON resource_link.resource_node_id = publication.resource_node_id
               INNER JOIN course ON course.id = resource_link.c_id
               INNER JOIN session ON session.id = resource_link.session_id
              WHERE resource_link.session_id IN (:sessionIds)
                AND resource_link.deleted_at IS NULL
                AND publication.parent_id IS NOT NULL
           ORDER BY session.title, course.title, publication.sent_date DESC',
            ['sessionIds' => $sessionIds],
            ['sessionIds' => ArrayParameterType::INTEGER],
        );
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['qualification'] = null === $row['qualification'] ? null : (float) $row['qualification'];
            $row['weight'] = null === $row['weight'] ? null : (float) $row['weight'];
            $row['accepted'] = 1 === (int) $row['accepted'] ? 'Yes' : 'No';
        }
        unset($row);

        return $this->simpleRowsResult(
            $filters,
            'Works in session report',
            $rows,
            [
                ['key' => 'session', 'label' => 'Session', 'type' => 'text'],
                ['key' => 'course', 'label' => 'Course', 'type' => 'text'],
                ['key' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['key' => 'author', 'label' => 'Learner name', 'type' => 'text'],
                ['key' => 'sentAt', 'label' => 'Sent date', 'type' => 'datetime'],
                ['key' => 'qualification', 'label' => 'Score', 'type' => 'number'],
                ['key' => 'weight', 'label' => 'Weight', 'type' => 'number'],
                ['key' => 'accepted', 'label' => 'Accepted', 'type' => 'status'],
            ],
        );
    }

    /**
     * @param array<string, mixed>             $filters
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, array<string, mixed>> $columns
     *
     * @return array<string, mixed>
     */
    private function simpleRowsResult(array $filters, string $title, array $rows, array $columns): array
    {
        $total = \count($rows);

        return $this->result(
            $filters,
            $title,
            $total,
            [['key' => 'rows', 'label' => 'Results', 'value' => $total]],
            $columns,
            \array_slice($rows, $filters['offset'], $filters['itemsPerPage']),
            [],
            ['canExportCsv' => true, 'canExportXlsx' => true],
        );
    }

    /**
     * @param array<string, mixed>             $filters
     * @param array<int, array<string, mixed>> $columns
     *
     * @return array<string, mixed>
     */
    private function emptyResult(
        array $filters,
        string $title,
        array $columns,
        array $meta = ['canExportCsv' => true, 'canExportXlsx' => true],
    ): array {
        return $this->result(
            $filters,
            $title,
            0,
            [],
            $columns,
            [],
            [],
            $meta,
        );
    }

    /**
     * @param array<string, mixed>             $filters
     * @param array<int, array<string, mixed>> $summary
     * @param array<int, array<string, mixed>> $columns
     * @param array<int, array<string, mixed>> $items
     * @param array<int, array<string, mixed>> $sections
     * @param array<string, mixed>             $meta
     *
     * @return array<string, mixed>
     */
    private function result(
        array $filters,
        string $title,
        int $total,
        array $summary,
        array $columns,
        array $items,
        array $sections,
        array $meta,
    ): array {
        return [
            'title' => $title,
            'total' => $total,
            'page' => $filters['page'],
            'itemsPerPage' => $filters['itemsPerPage'],
            'summary' => $summary,
            'columns' => $columns,
            'items' => $items,
            'sections' => $sections,
            'meta' => $meta,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters, bool $forExport): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $itemsPerPage = $forExport ? 100000 : min(100, max(10, (int) ($filters['itemsPerPage'] ?? 20)));
        $active = $filters['active'] ?? null;
        if (null !== $active && '' !== (string) $active) {
            $active = (int) $active;
        } else {
            $active = null;
        }

        return [
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'offset' => ($page - 1) * $itemsPerPage,
            'keyword' => (string) ($filters['keyword'] ?? ''),
            'sort' => (string) ($filters['sort'] ?? ''),
            'direction' => (string) ($filters['direction'] ?? 'ASC'),
            'status' => max(0, (int) ($filters['status'] ?? 0)),
            'active' => $active,
            'sleepingDays' => max(0, (int) ($filters['sleepingDays'] ?? 0)),
            'userId' => max(0, (int) ($filters['userId'] ?? 0)),
            'courseId' => max(0, (int) ($filters['courseId'] ?? 0)),
            'sessionId' => max(0, (int) ($filters['sessionId'] ?? 0)),
            'month' => min(12, max(0, (int) ($filters['month'] ?? 0))),
            'year' => trim((string) ($filters['year'] ?? '')),
            'exerciseId' => max(0, (int) ($filters['exerciseId'] ?? 0)),
            'score' => min(100, max(0, (int) ($filters['score'] ?? 70))),
            'startDate' => $this->dateFilter($filters['startDate'] ?? null),
            'endDate' => $this->dateFilter($filters['endDate'] ?? null),
            'mode' => (string) ($filters['mode'] ?? ''),
            'language' => trim((string) ($filters['language'] ?? '')),
        ];
    }

    private function dateFilter(mixed $value): ?DateTimeImmutable
    {
        if (!\is_string($value) || '' === trim($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return false === $date ? null : $date;
    }

    private function direction(string $direction): string
    {
        return 'DESC' === strtoupper($direction) ? 'DESC' : 'ASC';
    }

    private function followedUsersTitle(?int $status): string
    {
        return match ($status) {
            self::USER_STATUS_STUDENT => 'Followed students',
            self::USER_STATUS_STUDENT_BOSS => 'Followed student bosses',
            self::USER_STATUS_TEACHER => 'Followed teachers',
            self::USER_STATUS_HUMAN_RESOURCES => 'Followed HR directors',
            default => 'Followed users',
        };
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::USER_STATUS_TEACHER => 'Teacher',
            self::USER_STATUS_HUMAN_RESOURCES => 'Human resources manager',
            self::USER_STATUS_STUDENT => 'Learner',
            self::USER_STATUS_STUDENT_BOSS => 'Student boss',
            default => 'User',
        };
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function removeEmailFromRows(array &$rows): void
    {
        foreach ($rows as &$row) {
            unset($row['email']);
        }
        unset($row);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function countActiveRows(array $rows): int
    {
        return \count(array_filter($rows, static fn (array $row): bool => 'Active' === ($row['active'] ?? null)));
    }

    private function assertSectionAccess(GlobalReportingContext $context, string $section): void
    {
        if ('my-progress' === $section) {
            if ($context->blockMyProgressPage) {
                throw new AccessDeniedHttpException('The My progress page is disabled by platform configuration.');
            }

            return;
        }

        if (\in_array($section, self::ADMIN_SECTIONS, true)) {
            if (!$context->isAdministrator) {
                throw new AccessDeniedHttpException('This report is restricted to platform administrators.');
            }

            return;
        }

        if (\in_array($section, ['learners', 'learner-detail'], true)
            && ($context->canViewGlobalReports || $context->isStudentBoss)) {
            return;
        }

        if ('certificates' === $section && ($context->isAdministrator || $context->isStudentBoss)) {
            return;
        }

        if (\in_array($section, ['company', 'company-summary'], true)
            && ($context->isAdministrator || $context->isHumanResourcesManager || $context->isStudentBoss)) {
            return;
        }

        if (!$context->canViewGlobalReports) {
            throw new AccessDeniedHttpException('You are not allowed to view this global reporting section.');
        }
    }

    private function assertAdminWhenRequired(GlobalReportingContext $context, bool $required): void
    {
        if ($required) {
            $this->assertAdministratorOrHumanResources($context);
        }
    }

    private function assertAdministratorOrHumanResources(GlobalReportingContext $context): void
    {
        if (!$context->isAdministrator && !$context->isHumanResourcesManager) {
            throw new AccessDeniedHttpException('This report is restricted to administrators and human resources managers.');
        }
    }

    private function assertCertificateReportAccess(GlobalReportingContext $context): void
    {
        if (!$context->isAdministrator && !$context->isStudentBoss) {
            throw new AccessDeniedHttpException('This report is restricted to platform administrators and student bosses.');
        }
    }

    private function assertCompanyReportAccess(GlobalReportingContext $context): void
    {
        if (
            !$context->isAdministrator
            && !$context->isHumanResourcesManager
            && !$context->isStudentBoss
        ) {
            throw new AccessDeniedHttpException('This report is restricted to administrators, human resources managers, and student bosses.');
        }
    }

    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $types
     *
     * @return int[]
     */
    private function fetchIntegerColumn(string $sql, array $parameters, array $types = []): array
    {
        return array_values(array_map(
            'intval',
            $this->connection->fetchFirstColumn($sql, $parameters, $types),
        ));
    }

    /**
     * @return int[]
     */
    private function allAccessUrlUserIds(GlobalReportingContext $context): array
    {
        return array_values(array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT DISTINCT user_id FROM access_url_rel_user WHERE access_url_id = :accessUrlId ORDER BY user_id',
                ['accessUrlId' => $context->accessUrlId],
                ['accessUrlId' => Types::INTEGER],
            ),
        ));
    }

    /**
     * @return int[]
     */
    private function allAccessUrlCourseIds(GlobalReportingContext $context): array
    {
        return array_values(array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT DISTINCT c_id FROM access_url_rel_course WHERE access_url_id = :accessUrlId ORDER BY c_id',
                ['accessUrlId' => $context->accessUrlId],
                ['accessUrlId' => Types::INTEGER],
            ),
        ));
    }

    /**
     * @return int[]
     */
    private function allAccessUrlSessionIds(GlobalReportingContext $context): array
    {
        return array_values(array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT DISTINCT session_id FROM access_url_rel_session WHERE access_url_id = :accessUrlId ORDER BY session_id',
                ['accessUrlId' => $context->accessUrlId],
                ['accessUrlId' => Types::INTEGER],
            ),
        ));
    }

    /**
     * @param int[] $userIds
     *
     * @return array<int, string>
     */
    private function getOrganizationValues(array $userIds): array
    {
        if ([] === $userIds || !$this->hasTables(['extra_field', 'extra_field_values'])) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT values_table.item_id AS userId, values_table.field_value AS organization
               FROM extra_field_values values_table
               INNER JOIN extra_field field ON field.id = values_table.field_id
              WHERE values_table.item_id IN (:userIds)
                AND field.item_type = 1
                AND field.variable IN (\'company\', \'ruc\')
           ORDER BY CASE WHEN field.variable = \'company\' THEN 0 ELSE 1 END',
            ['userIds' => $userIds],
            ['userIds' => ArrayParameterType::INTEGER],
        );
        $values = [];
        foreach ($rows as $row) {
            $userId = (int) $row['userId'];
            if (!isset($values[$userId]) && '' !== trim((string) $row['organization'])) {
                $values[$userId] = trim((string) $row['organization']);
            }
        }

        return $values;
    }

    /**
     * @param string[] $columns
     */
    private function hasColumns(string $table, array $columns): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist([$table])) {
            return false;
        }

        $availableColumns = array_map(
            static fn (Column $column): string => strtolower($column->getName()),
            $schemaManager->listTableColumns($table),
        );

        foreach ($columns as $column) {
            if (!\in_array(strtolower($column), $availableColumns, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string[] $tables
     */
    private function hasTables(array $tables): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        foreach ($tables as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                return false;
            }
        }

        return true;
    }
}
