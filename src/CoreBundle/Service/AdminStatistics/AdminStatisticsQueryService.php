<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\AdminStatistics;

use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\TrackEAccess;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const PHP_INT_MAX;

final readonly class AdminStatisticsQueryService
{
    private const SUPPORTED_REPORTS = [
        'courses',
        'tools',
        'tool_usage',
        'courselastvisit',
        'coursebylanguage',
        'courses_usage',
        'messagesent',
        'messagereceived',
        'friends',
        'session_by_date',
    ];

    private const TRACKED_TOOLS = [
        'announcement',
        'assignment',
        'calendar_event',
        'chat',
        'course_description',
        'document',
        'dropbox',
        'group',
        'learnpath',
        'link',
        'quiz',
        'student_publication',
        'user',
        'forum',
        'glossary',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResourceLinkRepository $resourceLinkRepository,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private Security $security,
        private TranslatorInterface $translator,
        private AdminStatisticsUserSystemQueryService $userSystemQueryService,
        private AdminStatisticsMaintenanceQueryService $maintenanceQueryService,
    ) {}

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    public function getReport(string $report, array $parameters = []): array
    {
        if ($this->maintenanceQueryService->supports($report)) {
            return $this->maintenanceQueryService->getReport($report, $parameters);
        }

        if ($this->userSystemQueryService->supports($report)) {
            return $this->userSystemQueryService->getReport($report, $parameters);
        }

        if (!\in_array($report, self::SUPPORTED_REPORTS, true)) {
            throw new NotFoundHttpException('Unknown statistics report.');
        }

        return match ($report) {
            'courses' => $this->getCoursesReport(),
            'tools' => $this->getToolsReport(),
            'tool_usage' => $this->getToolUsageReport($parameters),
            'courselastvisit' => $this->getCourseLastVisitReport($parameters),
            'coursebylanguage' => $this->getCoursesByLanguageReport(),
            'courses_usage' => $this->getCoursesUsageReport($parameters),
            'messagesent' => $this->getMessagesReport(MessageRelUser::TYPE_SENDER),
            'messagereceived' => $this->getMessagesReport(MessageRelUser::TYPE_TO),
            'friends' => $this->getFriendsReport(),
            'session_by_date' => $this->getSessionByDateReport($parameters),
            default => throw new NotFoundHttpException('Unknown statistics report.'),
        };
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{columns: array<int, array<string, string>>, items: array<int, array<string, mixed>>}
     */
    public function getSessionByDateExportData(array $parameters): array
    {
        $report = $this->getSessionByDateReport($parameters);
        $table = \is_array($report['table'] ?? null) ? $report['table'] : [];

        return [
            'columns' => \is_array($table['columns'] ?? null) ? array_values($table['columns']) : [],
            'items' => \is_array($table['items'] ?? null) ? array_values($table['items']) : [],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{columns: array<int, array<string, string>>, items: array<int, array<string, mixed>>}
     */
    public function getExportData(string $report, array $parameters): array
    {
        if ('session_by_date' === $report) {
            return $this->getSessionByDateExportData($parameters);
        }
        if ($this->maintenanceQueryService->supports($report)) {
            return $this->maintenanceQueryService->getExportData($report, $parameters);
        }

        return $this->userSystemQueryService->getExportData($report, $parameters);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCoursesReport(): array
    {
        /** @var array<int, CourseCategory> $categories */
        $categories = $this->entityManager->getRepository(CourseCategory::class)->findAll();

        $values = [];
        foreach ($categories as $category) {
            $values[$category->getTitle()] = $category->getCourses()->count();
        }

        return [
            'title' => $this->trans('Courses'),
            'description' => '',
            'chart' => $this->buildChart(
                'pie',
                $values,
                $this->trans('Total number of courses'),
                $this->trans('Courses count')
            ),
            'stats' => $this->buildStats($values),
            'meta' => [
                'legacyStatsTable' => true,
                'statsTitle' => $this->trans('Courses'),
                'showStatsPercentage' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getToolsReport(): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('access.accessTool AS tool', 'COUNT(access.accessId) AS total')
            ->from(TrackEAccess::class, 'access')
            ->andWhere('access.accessTool IN (:tools)')
            ->setParameter('tools', self::TRACKED_TOOLS, ArrayParameterType::STRING)
            ->groupBy('access.accessTool')
            ->orderBy('COUNT(access.accessId)', 'DESC')
        ;

        $this->applyCourseAccessUrlScope($queryBuilder, 'access.cId', 'toolUrlRel');

        $values = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $tool = (string) ($row['tool'] ?? '');
            if ('' === $tool) {
                continue;
            }

            $values[$this->trans(ucfirst($tool))] = (int) $row['total'];
        }

        return [
            'title' => $this->trans('Tools access'),
            'description' => '',
            'chart' => $this->buildChart(
                'pie',
                $values,
                $this->trans('Tools'),
                $this->trans('Tools access')
            ),
            'stats' => $this->buildStats($values),
            'meta' => [
                'legacyStatsTable' => true,
                'statsTitle' => $this->trans('Tools access'),
                'showStatsPercentage' => false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getToolUsageReport(array $parameters): array
    {
        $availableTools = $this->resourceLinkRepository->getAvailableTools();
        $options = [];
        foreach ($availableTools as $id => $label) {
            $options[] = [
                'id' => (int) $id,
                'name' => (string) $label,
            ];
        }

        $selectedToolIds = $this->normalizeIntList($parameters['toolIds'] ?? []);
        $availableIds = array_map(static fn (array $option): int => (int) $option['id'], $options);
        $selectedToolIds = array_values(array_intersect($selectedToolIds, $availableIds));

        $items = [];
        if ([] !== $selectedToolIds) {
            $rows = $this->resourceLinkRepository->getToolUsageReportByTools($selectedToolIds);
            usort($rows, static function (array $first, array $second): int {
                $resourceCompare = (int) ($second['resource_count'] ?? 0) <=> (int) ($first['resource_count'] ?? 0);
                if (0 !== $resourceCompare) {
                    return $resourceCompare;
                }

                return strcmp((string) ($second['last_updated'] ?? ''), (string) ($first['last_updated'] ?? ''));
            });

            foreach ($rows as $row) {
                $lastUpdated = $row['last_updated'] ?? null;
                if ($lastUpdated instanceof DateTimeInterface) {
                    $lastUpdated = $lastUpdated->format('Y-m-d H:i:s');
                }

                $items[] = [
                    'toolName' => (string) ($row['tool_name'] ?? ''),
                    'sessionName' => (string) ($row['session_name'] ?? '-'),
                    'courseName' => (string) ($row['course_name'] ?? ''),
                    'resourceCount' => (int) ($row['resource_count'] ?? 0),
                    'lastUpdated' => \is_string($lastUpdated) ? $lastUpdated : '-',
                    'link' => (string) ($row['link'] ?? '-'),
                ];
            }
        }

        return [
            'title' => $this->trans('Tool-based resource count'),
            'description' => '',
            'filters' => [
                'tools' => $options,
                'selectedToolIds' => $selectedToolIds,
            ],
            'table' => [
                'columns' => [
                    ['key' => 'toolName', 'label' => $this->trans('Tool')],
                    ['key' => 'sessionName', 'label' => $this->trans('Session')],
                    ['key' => 'courseName', 'label' => $this->trans('Course')],
                    ['key' => 'resourceCount', 'label' => $this->trans('Resource count')],
                    ['key' => 'lastUpdated', 'label' => $this->trans('Last updated')],
                ],
                'items' => $items,
                'totalItems' => \count($items),
            ],
            'meta' => [
                'requiresSelection' => true,
                'noToolsAvailable' => [] === $options,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getCourseLastVisitReport(array $parameters): array
    {
        $dateDiff = $this->normalizePositiveInt($parameters['dateDiff'] ?? 60, 60, 1, PHP_INT_MAX);
        $page = $this->normalizePositiveInt($parameters['page'] ?? 1, 1, 1, PHP_INT_MAX);
        $itemsPerPage = $this->normalizePositiveInt($parameters['itemsPerPage'] ?? 50, 50, 1, PHP_INT_MAX);
        $column = $this->normalizeNonNegativeInt($parameters['column'] ?? 0, 0);
        if (!\in_array($column, [0, 1, 2], true)) {
            $column = 0;
        }
        $directionValue = \is_scalar($parameters['direction'] ?? null) ? (string) $parameters['direction'] : '4';
        $direction = \in_array(strtoupper($directionValue), ['3', 'DESC'], true) ? 'DESC' : 'ASC';

        $connection = $this->entityManager->getConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
            ->select('last_access.*')
            ->from('track_e_lastaccess', 'last_access')
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $currentUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $currentUrl && null !== $currentUrl->getId()) {
                $queryBuilder
                    ->innerJoin(
                        'last_access',
                        'access_url_rel_course',
                        'access_course',
                        'last_access.c_id = access_course.c_id'
                    )
                    ->andWhere('access_course.access_url_id = :accessUrlId')
                    ->setParameter('accessUrlId', (int) $currentUrl->getId(), Types::INTEGER)
                ;
            }
        }

        $queryBuilder
            ->groupBy('last_access.c_id')
            ->having("last_access.c_id <> ''")
            ->andHaving('DATEDIFF(:utcNow, last_access.access_date) <= :dateDiff')
            ->setParameter('utcNow', (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'), Types::STRING)
            ->setParameter('dateDiff', $dateDiff, Types::INTEGER)
            ->orderBy(['last_access.c_id', 'last_access.c_id', 'last_access.access_date'][$column], $direction)
        ;

        $countQueryBuilder = clone $queryBuilder;
        $allRows = $countQueryBuilder->executeQuery()->fetchAllAssociative();
        $totalItems = \count($allRows);

        $rows = $queryBuilder
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $courseIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['c_id'] ?? 0),
            $rows
        ))));
        $courseTitles = [];
        if ([] !== $courseIds) {
            /** @var array<int, Course> $courses */
            $courses = $this->entityManager->getRepository(Course::class)->findBy(['id' => $courseIds]);
            foreach ($courses as $course) {
                $courseTitles[(int) $course->getId()] = $course->getTitle();
            }
        }

        $items = [];
        foreach ($rows as $row) {
            $courseId = (int) ($row['c_id'] ?? 0);
            $items[] = [
                'courseId' => $courseId,
                'courseTitle' => $courseTitles[$courseId] ?? '',
                'lastAccess' => (string) ($row['access_date'] ?? ''),
            ];
        }

        return [
            'title' => $this->trans('Latest access'),
            'description' => '',
            'filters' => [
                'dateDiff' => $dateDiff,
                'column' => $column,
                'direction' => 'DESC' === $direction ? 3 : 4,
            ],
            'table' => [
                'columns' => [
                    ['key' => 'courseId', 'label' => $this->trans('Id'), 'sortable' => true],
                    ['key' => 'courseTitle', 'label' => $this->trans('Course title'), 'sortable' => true],
                    ['key' => 'lastAccess', 'label' => $this->trans('Latest access'), 'sortable' => true],
                ],
                'items' => $items,
                'totalItems' => $totalItems,
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'lazy' => true,
            ],
            'meta' => [
                'legacySummary' => $this->trans('Latest access').' >= '.$dateDiff.' '.$this->trans('days'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getCoursesByLanguageReport(): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('course.courseLanguage AS language', 'COUNT(course.id) AS total')
            ->from(Course::class, 'course')
            ->groupBy('course.courseLanguage')
            ->orderBy('COUNT(course.id)', 'DESC')
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $currentUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $currentUrl && null !== $currentUrl->getId()) {
                $queryBuilder
                    ->innerJoin('course.urls', 'courseUrlRel')
                    ->andWhere('IDENTITY(courseUrlRel.url) = :courseLanguageUrlId')
                    ->setParameter('courseLanguageUrlId', (int) $currentUrl->getId(), Types::INTEGER)
                ;
            }
        }

        $values = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $values[(string) ($row['language'] ?? '')] = (int) $row['total'];
        }

        return [
            'title' => $this->trans('Number of courses by language'),
            'description' => '',
            'chart' => $this->buildChart(
                'pie',
                $values,
                $this->trans('Count course by language'),
                $this->trans('Courses count by language')
            ),
            'stats' => $this->buildStats($values),
            'meta' => [
                'legacyStatsTable' => true,
                'statsTitle' => $this->trans('Number of courses by language'),
                'showStatsPercentage' => false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getCoursesUsageReport(array $parameters): array
    {
        $page = $this->normalizePositiveInt($parameters['page'] ?? 1, 1, 1, PHP_INT_MAX);
        $itemsPerPage = $this->normalizePositiveInt($parameters['itemsPerPage'] ?? 20, 20, 1, PHP_INT_MAX);
        $connection = $this->entityManager->getConnection();

        $totalItems = (int) $connection->executeQuery('SELECT COUNT(*) FROM course')->fetchOne();
        $courses = $connection->createQueryBuilder()
            ->select('course.id', 'course.title')
            ->from('course', 'course')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->executeQuery()
            ->fetchAllAssociative()
        ;

        $courseIds = array_map(static fn (array $course): int => (int) $course['id'], $courses);
        $today = new DateTimeImmutable('now', $this->getUserTimezone());
        $endDate = $today->format('Y-m-d');
        $ranges = [
            'day' => $today->modify('-1 day')->format('Y-m-d'),
            'week' => $today->modify('-1 week')->format('Y-m-d'),
            'month' => $today->modify('-1 month')->format('Y-m-d'),
            'sixMonths' => $today->modify('-6 months')->format('Y-m-d'),
            'year' => $today->modify('-1 year')->format('Y-m-d'),
            'twoYears' => $today->modify('-2 years')->format('Y-m-d'),
            'total' => null,
        ];

        $counts = [];
        foreach ($ranges as $key => $startDate) {
            $counts[$key] = $this->getCourseUsageCounts($courseIds, $startDate, $endDate);
        }

        $items = [];
        foreach ($courses as $course) {
            $courseId = (int) $course['id'];
            $totalCounts = $counts['total'][$courseId] ?? ['base' => 0, 'session' => 0];
            $items[] = [
                'course' => (string) $course['title'],
                'today' => $this->sumCourseUsage($counts['day'][$courseId] ?? []),
                'week' => $this->sumCourseUsage($counts['week'][$courseId] ?? []),
                'month' => $this->sumCourseUsage($counts['month'][$courseId] ?? []),
                'sixMonths' => $this->sumCourseUsage($counts['sixMonths'][$courseId] ?? []),
                'year' => $this->sumCourseUsage($counts['year'][$courseId] ?? []),
                'twoYears' => $this->sumCourseUsage($counts['twoYears'][$courseId] ?? []),
                'allTimeOutsideSessions' => (int) ($totalCounts['base'] ?? 0),
                'allTimeInSessions' => (int) ($totalCounts['session'] ?? 0),
                'allTimeTotal' => $this->sumCourseUsage($totalCounts),
            ];
        }

        return [
            'title' => $this->trans('Courses usage'),
            'description' => '',
            'table' => [
                'columns' => [
                    ['key' => 'course', 'label' => $this->trans('Course')],
                    ['key' => 'today', 'label' => $this->trans('Today')],
                    ['key' => 'week', 'label' => $this->trans('This week')],
                    ['key' => 'month', 'label' => $this->trans('This month')],
                    ['key' => 'sixMonths', 'label' => '6 '.$this->trans('months')],
                    ['key' => 'year', 'label' => '1 '.$this->trans('Year')],
                    ['key' => 'twoYears', 'label' => '2 '.$this->trans('Years')],
                    ['key' => 'allTimeOutsideSessions', 'label' => $this->trans('All time visits outside sessions')],
                    ['key' => 'allTimeInSessions', 'label' => $this->trans('All time visits in sessions')],
                    ['key' => 'allTimeTotal', 'label' => $this->trans('All time visits total')],
                ],
                'items' => $items,
                'totalItems' => $totalItems,
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'lazy' => true,
            ],
            'meta' => [
                'contentTitle' => $this->trans('Student visits per period, per course'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getMessagesReport(int $receiverType): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();

        if (MessageRelUser::TYPE_SENDER === $receiverType) {
            $queryBuilder
                ->select(
                    'user.id AS userId',
                    'user.firstname AS firstname',
                    'user.lastname AS lastname',
                    'user.username AS username',
                    'COUNT(DISTINCT message.id) AS total'
                )
                ->from(Message::class, 'message')
                ->innerJoin('message.receivers', 'messageRelUser', Join::WITH, 'messageRelUser.receiverType = :receiverType')
                ->innerJoin('message.sender', 'user')
            ;
        } else {
            $queryBuilder
                ->select(
                    'user.id AS userId',
                    'user.firstname AS firstname',
                    'user.lastname AS lastname',
                    'user.username AS username',
                    'COUNT(DISTINCT message.id) AS total'
                )
                ->from(MessageRelUser::class, 'messageRelUser')
                ->innerJoin('messageRelUser.message', 'message')
                ->innerJoin('messageRelUser.receiver', 'user')
                ->andWhere('messageRelUser.receiverType = :receiverType')
            ;
        }

        $queryBuilder
            ->setParameter('receiverType', $receiverType, Types::INTEGER)
            ->andWhere('user.active <> :softDeleted')
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ->groupBy('user.id, user.firstname, user.lastname, user.username')
            ->orderBy('COUNT(DISTINCT message.id)', 'DESC')
        ;

        $this->applyUserAccessUrlScope($queryBuilder, 'user', 'messagePortal');

        $values = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $values[$this->formatLegacySocialUserLabel($row, true)] = (int) $row['total'];
        }

        $sent = MessageRelUser::TYPE_SENDER === $receiverType;

        $title = $this->trans($sent ? 'Number of messages sent' : 'Number of messages received');

        return [
            'title' => $title,
            'description' => '',
            'stats' => $this->buildStats($values),
            'meta' => [
                'legacyStatsTable' => true,
                'statsTitle' => $title,
                'showStatsPercentage' => false,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getFriendsReport(): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select(
                'IDENTITY(relation.user) AS userId',
                'user.firstname AS firstname',
                'user.lastname AS lastname',
                'user.username AS username',
                'COUNT(relation.id) AS total'
            )
            ->from(UserRelUser::class, 'relation')
            ->leftJoin('relation.user', 'user', Join::WITH, 'user.active <> :softDeleted')
            ->andWhere('relation.relationType <> :hrRelation')
            ->setParameter('hrRelation', UserRelUser::USER_RELATION_TYPE_RRHH, Types::INTEGER)
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
            ->groupBy('relation.user, user.firstname, user.lastname, user.username')
            ->orderBy('COUNT(relation.id)', 'DESC')
        ;

        if ($this->accessUrlHelper->isMultiple()) {
            $currentUrl = $this->accessUrlHelper->getCurrent();
            if (null !== $currentUrl && null !== $currentUrl->getId()) {
                $queryBuilder
                    ->innerJoin('relation.user', 'friendScopeUser')
                    ->innerJoin('friendScopeUser.portals', 'friendPortal')
                    ->andWhere('IDENTITY(friendPortal.url) = :friendPortalUrlId')
                    ->setParameter('friendPortalUrlId', (int) $currentUrl->getId(), Types::INTEGER)
                ;
            }
        }

        $values = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $values[$this->formatLegacySocialUserLabel($row, false)] = (int) $row['total'];
        }

        return [
            'title' => $this->trans('Contacts count'),
            'description' => '',
            'stats' => $this->buildStats($values),
            'meta' => [
                'legacyStatsTable' => true,
                'statsTitle' => $this->trans('Contacts count'),
                'showStatsPercentage' => false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getSessionByDateReport(array $parameters): array
    {
        $rangeStart = trim((string) ($parameters['rangeStart'] ?? ''));
        $rangeEnd = trim((string) ($parameters['rangeEnd'] ?? ''));
        $statusId = $this->normalizeNonNegativeInt($parameters['statusId'] ?? 0, 0);

        $statusOptions = [
            ['value' => 0, 'label' => $this->trans('All')],
            ['value' => Session::STATUS_PLANNED, 'label' => $this->getSessionStatusLabel(Session::STATUS_PLANNED)],
            ['value' => Session::STATUS_PROGRESS, 'label' => $this->getSessionStatusLabel(Session::STATUS_PROGRESS)],
            ['value' => Session::STATUS_FINISHED, 'label' => $this->getSessionStatusLabel(Session::STATUS_FINISHED)],
            ['value' => Session::STATUS_CANCELLED, 'label' => $this->getSessionStatusLabel(Session::STATUS_CANCELLED)],
        ];

        $base = [
            'title' => $this->trans('Sessions by date'),
            'description' => $this->trans('Sessions created over time.'),
            'filters' => [
                'rangeStart' => $rangeStart,
                'rangeEnd' => $rangeEnd,
                'statusId' => $statusId,
                'statusOptions' => $statusOptions,
            ],
            'table' => [
                'columns' => [
                    ['key' => 'name', 'label' => $this->trans('Name')],
                    ['key' => 'startDate', 'label' => $this->trans('Start date')],
                    ['key' => 'endDate', 'label' => $this->trans('End date')],
                    ['key' => 'language', 'label' => $this->trans('Language')],
                    ['key' => 'status', 'label' => $this->trans('Status')],
                    ['key' => 'students', 'label' => $this->trans('Total number of students')],
                ],
                'items' => [],
                'totalItems' => 0,
            ],
            'meta' => [
                'requiresDateRange' => true,
                'legacySessionByDate' => true,
                'statsTitle' => $this->trans('Global statistics'),
                'canExportXls' => false,
            ],
        ];

        if ('' === $rangeStart && '' === $rangeEnd) {
            return $base;
        }

        $startDate = $this->parseDate($rangeStart);
        $endDate = $this->parseDate($rangeEnd);
        if (null === $startDate || null === $endDate || $startDate > $endDate) {
            throw new BadRequestHttpException('A valid start and end date are required.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder
            ->select('session', 'category', 'sessionCourse', 'course')
            ->from(Session::class, 'session')
            ->leftJoin('session.category', 'category')
            ->leftJoin('session.courses', 'sessionCourse')
            ->leftJoin('sessionCourse.course', 'course')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->between('session.displayStartDate', ':rangeStart', ':rangeEnd'),
                    $queryBuilder->expr()->between('session.displayEndDate', ':rangeStart', ':rangeEnd')
                )
            )
            ->setParameter('rangeStart', $startDate, Types::DATETIME_MUTABLE)
            ->setParameter('rangeEnd', $endDate, Types::DATETIME_MUTABLE)
        ;

        if ($statusId > 0) {
            $queryBuilder
                ->andWhere('session.status = :statusId')
                ->setParameter('statusId', $statusId, Types::INTEGER)
            ;
        }

        /** @var array<int, Session> $sessions */
        $sessions = $queryBuilder->getQuery()->getResult();
        $sessionIds = [];
        foreach ($sessions as $session) {
            $sessionId = $session->getId();
            if (null !== $sessionId) {
                $sessionIds[] = (int) $sessionId;
            }
        }

        $studentCounts = $this->getSessionStudentCounts($sessionIds);
        $uniqueCoaches = $this->getUniqueGeneralCoachCount($sessionIds);
        $sessionCount = \count($sessions);
        $numberUsers = 0;
        $courseSessions = [];
        $categoryCountsById = [];
        $languageCounts = [];
        $statusCounts = [];
        $items = [];

        foreach ($sessions as $session) {
            $sessionId = (int) $session->getId();
            $numberUsers += $session->getNbrUsers();

            $category = $session->getCategory();
            $categoryId = null !== $category && null !== $category->getId() ? (int) $category->getId() : 0;
            $categoryCountsById[$categoryId] ??= [
                'label' => null !== $category ? $category->getTitle() : $this->trans('Without category'),
                'count' => 0,
            ];
            ++$categoryCountsById[$categoryId]['count'];

            $statusLabel = $this->getSessionStatusLabel($session->getStatus());
            $statusCounts[$statusLabel] = ($statusCounts[$statusLabel] ?? 0) + 1;

            $chartLanguage = $this->trans('Nothing');
            $tableLanguage = '';
            $firstCourse = null;
            foreach ($session->getCourses() as $sessionCourse) {
                $course = $sessionCourse->getCourse();
                $courseId = (int) $course->getId();
                $courseSessions[$courseId] ??= [
                    'course' => $course->getTitle(),
                    'sessionsCount' => 0,
                ];
                ++$courseSessions[$courseId]['sessionsCount'];
                $firstCourse ??= $course;
            }

            if ($firstCourse instanceof Course) {
                $languageKey = ucfirst(str_replace('2', '', $firstCourse->getCourseLanguage()));
                $chartLanguage = $this->trans($languageKey);
                $tableLanguage = $chartLanguage;
            }
            $languageCounts[$chartLanguage] = ($languageCounts[$chartLanguage] ?? 0) + 1;

            $items[] = [
                'name' => $session->getTitle(),
                'startDate' => $this->formatLegacyLocalDateTime($session->getDisplayStartDate()),
                'endDate' => $this->formatLegacyLocalDateTime($session->getDisplayEndDate()),
                'language' => $tableLanguage,
                'status' => $statusLabel,
                'students' => (int) ($studentCounts[$sessionId] ?? 0),
            ];
        }

        $categoryCounts = [];
        foreach ($categoryCountsById as $categoryData) {
            $categoryCounts[(string) $categoryData['label']] = (int) $categoryData['count'];
        }

        uasort($courseSessions, static fn (array $first, array $second): int => $second['sessionsCount'] <=> $first['sessionsCount']);

        $days = (int) $startDate->diff($endDate)->days;
        $numberOfWeeks = (int) floor($days / 7);
        $sessionAverage = 0.0;
        if ($numberOfWeeks > 0) {
            $sessionAverage = round($sessionCount / $numberOfWeeks, 2);
        }
        $averageUser = 0.0;
        if ($sessionCount > 0) {
            $averageUser = round($numberUsers / $sessionCount, 2);
        }
        $averageCoach = 0.0;
        if ($uniqueCoaches > 0) {
            $averageCoach = round($sessionCount / $uniqueCoaches, 2);
        }

        $query = http_build_query([
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'statusId' => $statusId,
        ]);

        return array_replace($base, [
            'stats' => [
                ['label' => $this->trans('Weeks'), 'value' => $numberOfWeeks],
                ['label' => $this->trans('Sessions count'), 'value' => $sessionCount],
                ['label' => $this->trans('Sessions per week'), 'value' => $sessionAverage],
                ['label' => $this->trans('Average number of users per session'), 'value' => $averageUser],
                ['label' => $this->trans('Average number of sessions per general session tutor'), 'value' => $averageCoach],
            ],
            'charts' => [
                $this->buildChart(
                    'pie',
                    $categoryCounts,
                    $this->trans('Number of users'),
                    $this->trans('Sessions per category')
                ),
                $this->buildChart(
                    'pie',
                    $languageCounts,
                    $this->trans('Number of users'),
                    $this->trans('Sessions per language')
                ),
                $this->buildChart(
                    'pie',
                    $statusCounts,
                    $this->trans('Number of users'),
                    $this->trans('Sessions per status')
                ),
            ],
            'table' => [
                'columns' => [
                    ['key' => 'name', 'label' => $this->trans('Name')],
                    ['key' => 'startDate', 'label' => $this->trans('Start date')],
                    ['key' => 'endDate', 'label' => $this->trans('End date')],
                    ['key' => 'language', 'label' => $this->trans('Language')],
                    ['key' => 'status', 'label' => $this->trans('Status')],
                    ['key' => 'students', 'label' => $this->trans('Total number of students')],
                ],
                'items' => $items,
                'totalItems' => $sessionCount,
            ],
            'meta' => [
                'requiresDateRange' => true,
                'legacySessionByDate' => true,
                'statsTitle' => $this->trans('Global statistics'),
                'canExportXls' => $sessionCount > 0,
                'exportUrl' => $sessionCount > 0 ? '/api/admin/statistics/session-by-date.xls?'.$query : '',
                'courseSessions' => array_values($courseSessions),
            ],
        ]);
    }

    /**
     * @param array<int, int> $courseIds
     *
     * @return array<int, array{base:int, session:int}>
     */
    private function getCourseUsageCounts(array $courseIds, ?string $startDate, string $endDate): array
    {
        if ([] === $courseIds) {
            return [];
        }

        $counts = [];
        foreach ([0 => 'base', 1 => 'session'] as $withSession => $bucket) {
            $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
            $queryBuilder
                ->distinct()
                ->select(
                    'CAST(course_access.login_course_date AS DATE) AS login_course_date',
                    'course_access.user_id',
                    'course_access.c_id'
                )
                ->from('track_e_course_access', 'course_access')
                ->where('course_access.c_id IN (:courseIds)')
                ->setParameter('courseIds', $courseIds, ArrayParameterType::INTEGER)
                ->andWhere('course_access.login_course_date <= :endDate')
                ->setParameter('endDate', $this->legacyLocalDateToUtcDate($endDate), Types::STRING)
                ->groupBy(
                    'course_access.c_id',
                    'course_access.session_id',
                    'CAST(course_access.login_course_date AS DATE)',
                    'course_access.user_id'
                )
                ->orderBy('course_access.c_id')
            ;

            if (0 === $withSession) {
                $queryBuilder->andWhere('course_access.session_id = 0');
            } else {
                $queryBuilder->andWhere('course_access.session_id != 0');
            }

            if (null !== $startDate) {
                $queryBuilder
                    ->andWhere('course_access.login_course_date >= :startDate')
                    ->setParameter('startDate', $this->legacyLocalDateToUtcDate($startDate), Types::STRING)
                ;
            }

            foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
                $courseId = (int) $row['c_id'];
                $counts[$courseId] ??= ['base' => 0, 'session' => 0];
                ++$counts[$courseId][$bucket];
            }
        }

        return $counts;
    }

    private function legacyLocalDateToUtcDate(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->getUserTimezone());
        if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $value) {
            throw new BadRequestHttpException('Invalid date.');
        }

        return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d');
    }

    private function getUserTimezone(): DateTimeZone
    {
        $timezone = date_default_timezone_get();
        $platformTimezone = trim((string) $this->settingsManager->getSetting('platform.timezone'));
        if ('' !== $platformTimezone) {
            $timezone = $platformTimezone;
        }

        $allowUserTimezones = (string) $this->settingsManager->getSetting('profile.use_users_timezone');
        $user = $this->security->getUser();
        if ('true' === $allowUserTimezones && $user instanceof User) {
            $userTimezone = trim((string) $user->getTimezone());
            if ('' !== $userTimezone) {
                $timezone = $userTimezone;
            }
        }

        try {
            return new DateTimeZone($timezone);
        } catch (Throwable) {
            return new DateTimeZone('UTC');
        }
    }

    /**
     * @param array<string, int> $counts
     */
    private function sumCourseUsage(array $counts): int
    {
        return (int) ($counts['base'] ?? 0) + (int) ($counts['session'] ?? 0);
    }

    /**
     * @param array<int, int> $sessionIds
     *
     * @return array<int, int>
     */
    private function getSessionStudentCounts(array $sessionIds): array
    {
        if ([] === $sessionIds) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder();
        $rows = $queryBuilder
            ->select('IDENTITY(relation.session) AS sessionId', 'COUNT(relation.id) AS total')
            ->from(SessionRelUser::class, 'relation')
            ->andWhere('IDENTITY(relation.session) IN (:sessionIds)')
            ->setParameter('sessionIds', $sessionIds, ArrayParameterType::INTEGER)
            ->andWhere('relation.relationType = :studentType')
            ->setParameter('studentType', Session::STUDENT, Types::INTEGER)
            ->groupBy('relation.session')
            ->getQuery()
            ->getArrayResult()
        ;

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['sessionId']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @param array<int, int> $sessionIds
     */
    private function getUniqueGeneralCoachCount(array $sessionIds): int
    {
        if ([] === $sessionIds) {
            return 0;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder();

        return (int) $queryBuilder
            ->select('COUNT(DISTINCT IDENTITY(relation.user))')
            ->from(SessionRelUser::class, 'relation')
            ->andWhere('IDENTITY(relation.session) IN (:sessionIds)')
            ->setParameter('sessionIds', $sessionIds, ArrayParameterType::INTEGER)
            ->andWhere('relation.relationType = :coachType')
            ->setParameter('coachType', Session::GENERAL_COACH, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function applyCourseAccessUrlScope(QueryBuilder $queryBuilder, string $courseIdExpression, string $alias): void
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return;
        }

        $currentUrl = $this->accessUrlHelper->getCurrent();
        if (null === $currentUrl || null === $currentUrl->getId()) {
            return;
        }

        $queryBuilder
            ->innerJoin(
                AccessUrlRelCourse::class,
                $alias,
                Join::WITH,
                'IDENTITY('.$alias.'.course) = '.$courseIdExpression
            )
            ->andWhere('IDENTITY('.$alias.'.url) = :'.$alias.'UrlId')
            ->setParameter($alias.'UrlId', (int) $currentUrl->getId(), Types::INTEGER)
        ;
    }

    private function applyUserAccessUrlScope(QueryBuilder $queryBuilder, string $userAlias, string $portalAlias): void
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return;
        }

        $currentUrl = $this->accessUrlHelper->getCurrent();
        if (null === $currentUrl || null === $currentUrl->getId()) {
            return;
        }

        $queryBuilder
            ->innerJoin($userAlias.'.portals', $portalAlias)
            ->andWhere('IDENTITY('.$portalAlias.'.url) = :'.$portalAlias.'UrlId')
            ->setParameter($portalAlias.'UrlId', (int) $currentUrl->getId(), Types::INTEGER)
        ;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function formatLegacySocialUserLabel(array $row, bool $unknownUsername): string
    {
        $firstname = trim((string) ($row['firstname'] ?? ''));
        $lastname = trim((string) ($row['lastname'] ?? ''));
        $username = trim((string) ($row['username'] ?? ''));
        $fullName = trim($firstname.' '.$lastname);
        if ($unknownUsername && '' === $username) {
            $username = $this->trans('Unknown');
        }

        return $fullName."\n(".$username.')';
    }

    /**
     * @param array<string, int|float> $values
     *
     * @return array<string, mixed>
     */
    private function buildChart(string $type, array $values, string $label, ?string $title = null): array
    {
        return [
            'type' => $type,
            'title' => $title ?? $label,
            'data' => [
                'labels' => array_values(array_map('strval', array_keys($values))),
                'datasets' => [
                    [
                        'label' => $label,
                        'data' => array_values($values),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, int|float> $values
     *
     * @return array<int, array{label:string, value:int|float}>
     */
    private function buildStats(array $values): array
    {
        $stats = [];
        foreach ($values as $label => $value) {
            $stats[] = [
                'label' => (string) $label,
                'value' => $value,
            ];
        }

        return $stats;
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIntList(mixed $value): array
    {
        if (\is_string($value)) {
            $value = preg_split('/[,;]+/', $value) ?: [];
        }
        if (!\is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            if (!\is_scalar($item)) {
                continue;
            }
            $id = (int) $item;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    private function normalizePositiveInt(mixed $value, int $default, int $minimum, int $maximum): int
    {
        if (!\is_scalar($value)) {
            return $default;
        }

        $number = (int) $value;
        if ($number < $minimum || $number > $maximum) {
            return $default;
        }

        return $number;
    }

    private function normalizeNonNegativeInt(mixed $value, int $default): int
    {
        if (!\is_scalar($value)) {
            return $default;
        }

        return max(0, (int) $value);
    }

    private function parseDate(string $value): ?DateTime
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        if (!$date instanceof DateTime || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date;
    }

    private function formatLegacyLocalDateTime(?DateTimeInterface $date): string
    {
        if (!$date instanceof DateTimeInterface) {
            return '';
        }

        $utcDate = new DateTimeImmutable($date->format('Y-m-d H:i:s'), new DateTimeZone('UTC'));

        return $utcDate->setTimezone($this->getUserTimezone())->format('Y-m-d H:i:s');
    }

    private function getSessionStatusLabel(int $status): string
    {
        return match ($status) {
            Session::STATUS_PLANNED => $this->trans('Planned'),
            Session::STATUS_PROGRESS => $this->trans('In progress'),
            Session::STATUS_FINISHED => $this->trans('Finished'),
            Session::STATUS_CANCELLED => $this->trans('Cancelled'),
            default => $this->trans('No status'),
        };
    }

    private function trans(string $message): string
    {
        return $this->translator->trans($message);
    }
}
