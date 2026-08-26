<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\AdminStatistics;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldOptions;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\SettingsManagerHelper;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const PHP_INT_MAX;
use const PHP_OS_FAMILY;

final readonly class AdminStatisticsUserSystemQueryService
{
    private const SUPPORTED_REPORTS = [
        'users',
        'recentlogins',
        'logins',
        'pictures',
        'logins_by_date',
        'no_login_users',
        'users_active',
        'users_online',
        'new_user_registrations',
        'subscription_by_day',
        'user_session',
        'quarterly_report',
    ];

    private const USER_STATUS_STUDENT_BOSS = 17;
    private const SESSION_DURATION_OPTIONS = [0, 5, 15, 30, 60];
    private const ONLINE_INTERVALS = [3, 5, 30, 120];
    private const QUARTERLY_SECTIONS = [
        'users',
        'courses',
        'hours_of_training',
        'certificates',
        'sessions_by_duration',
        'courses_and_sessions',
        'disk_usage',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private SettingsManagerHelper $settingsManagerHelper,
        private LanguageRepository $languageRepository,
        private Security $security,
        private TranslatorInterface $translator,
    ) {}

    public function supports(string $report): bool
    {
        return \in_array($report, self::SUPPORTED_REPORTS, true);
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    public function getReport(string $report, array $parameters = []): array
    {
        if (!\in_array($report, self::SUPPORTED_REPORTS, true)) {
            throw new NotFoundHttpException('Unknown statistics report.');
        }

        return match ($report) {
            'users' => $this->getUsersReport(),
            'recentlogins' => $this->getRecentLoginsReport($parameters),
            'logins' => $this->getLoginsReport($parameters),
            'pictures' => $this->getPicturesReport(),
            'logins_by_date' => $this->getLoginsByDateReport($parameters),
            'no_login_users' => $this->getNoLoginUsersReport(),
            'users_active' => $this->getUsersActiveReport($parameters),
            'users_online' => $this->getUsersOnlineReport($parameters),
            'new_user_registrations' => $this->getNewUserRegistrationsReport($parameters),
            'subscription_by_day' => $this->getSubscriptionByDayReport($parameters),
            'user_session' => $this->getUserSessionReport($parameters),
            'quarterly_report' => $this->getQuarterlyReport($parameters),
            default => throw new NotFoundHttpException('Unknown statistics report.'),
        };
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{columns: array<int, array<string, string>>, items: array<int, array<string, mixed>>}
     */
    public function getExportData(string $report, array $parameters): array
    {
        $data = match ($report) {
            'logins_by_date' => $this->getLoginsByDateReport($parameters),
            'users_active' => $this->getUsersActiveReport([...$parameters, 'export' => true]),
            'user_session' => $this->getUserSessionReport($parameters),
            default => throw new BadRequestHttpException('This report is not exportable.'),
        };

        $table = \is_array($data['table'] ?? null) ? $data['table'] : [];

        return [
            'columns' => \is_array($table['columns'] ?? null) ? array_values($table['columns']) : [],
            'items' => \is_array($table['items'] ?? null) ? array_values($table['items']) : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getUsersReport(): array
    {
        $teacherTotal = $this->countUsers(CourseRelUser::TEACHER);
        $studentTotal = $this->countUsers(CourseRelUser::STUDENT);

        $teacherByCategory = [];
        $studentByCategory = [];
        $categories = $this->entityManager->getRepository(CourseCategory::class)->findAll();
        foreach ($categories as $category) {
            $name = str_replace($this->trans('Department'), '', (string) $category->getTitle());
            $teacherByCategory[$name] = $this->countUsers(CourseRelUser::TEACHER, (string) $category->getCode());
            $studentByCategory[$name] = $this->countUsers(CourseRelUser::STUDENT, (string) $category->getCode());
        }

        return [
            'title' => $this->trans('Number of users'),
            'description' => '',
            'charts' => [
                $this->buildChart('pie', [
                    $this->trans('Trainers') => $teacherTotal,
                    $this->trans('Learners') => $studentTotal,
                ], $this->trans('Number of users')),
                $this->buildChart(
                    'pie',
                    $teacherByCategory,
                    $this->trans('Teachers'),
                    $this->trans('Trainers')
                ),
                $this->buildChart(
                    'pie',
                    $studentByCategory,
                    $this->trans('Students'),
                    $this->trans('Learners')
                ),
            ],
            'statsGroups' => [
                ['title' => $this->trans('Number of users'), 'items' => $this->buildStats([
                    $this->trans('Trainers') => $teacherTotal,
                    $this->trans('Learners') => $studentTotal,
                ])],
                ['title' => $this->trans('Trainers'), 'items' => $this->buildStats($teacherByCategory)],
                ['title' => $this->trans('Learners'), 'items' => $this->buildStats($studentByCategory)],
            ],
            'meta' => [
                'legacyStatsGroups' => true,
                'legacyFlatCharts' => true,
                'legacyChartsColumns' => 3,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getRecentLoginsReport(array $parameters): array
    {
        $sessionDuration = $this->normalizeAllowedInt($parameters['sessionDuration'] ?? 0, self::SESSION_DURATION_OPTIONS, 0);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $chartStart = $now->sub(new DateInterval('P31D'));

        $allChart = $this->getDailyLoginCounts($chartStart, $now, false, $sessionDuration);
        $distinctChart = $this->getDailyLoginCounts($chartStart, $now, true, $sessionDuration);

        return [
            'title' => \sprintf($this->trans('Last %s days'), '15'),
            'description' => '',
            'chart' => [
                'type' => 'line',
                'title' => '',
                'data' => [
                    'labels' => array_values(array_keys($allChart)),
                    'datasets' => [
                        [
                            'label' => $this->trans('Logins'),
                            'data' => array_values($allChart),
                        ],
                        [
                            'label' => $this->trans('Distinct users logins'),
                            'data' => array_values($distinctChart),
                        ],
                    ],
                ],
            ],
            'filters' => [
                'sessionDuration' => $sessionDuration,
                'sessionDurationOptions' => self::SESSION_DURATION_OPTIONS,
            ],
            'statsGroups' => [
                [
                    'title' => $this->trans('Logins'),
                    'items' => $this->getRecentLoginPeriodStats(false, $sessionDuration),
                ],
                [
                    'title' => $this->trans('Distinct users logins'),
                    'items' => $this->getRecentLoginPeriodStats(true, $sessionDuration),
                ],
            ],
            'meta' => [
                'legacyStatsGroups' => true,
                'legacyFlatChart' => true,
                'contentTitle' => \sprintf($this->trans('Last %s days'), '15'),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getLoginsReport(array $parameters): array
    {
        $type = \is_scalar($parameters['type'] ?? null) ? (string) $parameters['type'] : 'month';
        if (!\in_array($type, ['month', 'day', 'hour'], true)) {
            $type = 'month';
        }

        $connection = $this->entityManager->getConnection();
        $urlId = $this->getCurrentAccessUrlId();
        $join = '';
        $where = [];
        $params = [];
        $types = [];
        if ($this->accessUrlHelper->isMultiple() && null !== $urlId) {
            $join = ' INNER JOIN access_url_rel_user aur ON aur.user_id = l.login_user_id ';
            $where[] = 'aur.access_url_id = :urlId';
            $params['urlId'] = $urlId;
            $types['urlId'] = Types::INTEGER;
        }

        $whereSql = [] === $where ? '' : ' WHERE '.implode(' AND ', $where);
        $statsGroups = [];

        if ('month' === $type) {
            $sql = "SELECT DATE_FORMAT(l.login_date, '%Y-%m') stat_date, COUNT(l.login_id) total FROM track_e_login l{$join}{$whereSql} GROUP BY stat_date ORDER BY l.login_date DESC";
            $rows = $connection->executeQuery($sql, $params, $types)->fetchAllAssociative();
            $values = [];
            foreach ($rows as $row) {
                $date = DateTimeImmutable::createFromFormat('!Y-m', (string) $row['stat_date']);
                $label = $date instanceof DateTimeImmutable
                    ? $date->format('Y').' '.$this->trans($date->format('F'))
                    : (string) $row['stat_date'];
                $values[$label] = (int) $row['total'];
            }
            $statsGroups[] = [
                'title' => $this->trans('All logins').' ('.$this->trans('Month').')',
                'items' => $this->buildStats($values),
            ];
        }

        if ('day' === $type) {
            $statsGroups[] = [
                'title' => $this->trans('Last logins').' ('.$this->trans('Day').')',
                'items' => $this->buildStats($this->queryLoginDayOrHourCounts('day', true, $urlId)),
            ];
            $statsGroups[] = [
                'title' => $this->trans('All logins').' ('.$this->trans('Day').')',
                'items' => $this->buildStats($this->queryLoginDayOrHourCounts('day', false, $urlId)),
            ];
        }

        if ('hour' === $type) {
            $statsGroups[] = [
                'title' => $this->trans('Last logins').' ('.$this->trans('Hour').')',
                'items' => $this->buildStats($this->queryLoginDayOrHourCounts('hour', true, $urlId)),
            ];
            $statsGroups[] = [
                'title' => $this->trans('All logins').' ('.$this->trans('Hour').')',
                'items' => $this->buildStats($this->queryLoginDayOrHourCounts('hour', false, $urlId)),
            ];
        }

        return [
            'title' => $this->trans('Logins'),
            'description' => '',
            'statsGroups' => $statsGroups,
            'filters' => ['type' => $type],
            'meta' => ['legacyStatsGroups' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPicturesReport(): array
    {
        $connection = $this->entityManager->getConnection();

        try {
            $columns = $connection->createSchemaManager()->listTableColumns('user');
        } catch (Throwable) {
            $columns = [];
        }

        $join = '';
        $params = ['softDeleted' => User::SOFT_DELETED];
        $types = ['softDeleted' => Types::INTEGER];
        $urlId = $this->getCurrentAccessUrlId();
        if ($this->accessUrlHelper->isMultiple() && null !== $urlId) {
            $join = ' INNER JOIN access_url_rel_user url ON url.user_id = u.id AND url.access_url_id = :urlId ';
            $params['urlId'] = $urlId;
            $types['urlId'] = Types::INTEGER;
        }

        $where = ' WHERE u.active <> :softDeleted';
        $totalUsers = (int) $connection
            ->executeQuery('SELECT COUNT(*) AS n FROM user u'.$join.$where, $params, $types)
            ->fetchOne()
        ;

        $pictureWhereParts = [];
        if (isset($columns['picture_uri'])) {
            $pictureWhereParts[] = "(u.picture_uri IS NOT NULL AND u.picture_uri <> '')";
        }
        if (isset($columns['picture'])) {
            $pictureWhereParts[] = "(u.picture IS NOT NULL AND u.picture <> '' AND u.picture <> '0' AND u.picture <> 'unknown.jpg' AND u.picture <> 'unknown.png')";
        }
        if (isset($columns['picture_resource_node_id'])) {
            $pictureWhereParts[] = '(u.picture_resource_node_id IS NOT NULL AND u.picture_resource_node_id <> 0)';
        }

        $withPicture = 0;
        if ([] !== $pictureWhereParts) {
            $withPicture = (int) $connection
                ->executeQuery(
                    'SELECT COUNT(*) AS n FROM user u'.$join.$where.' AND ('.implode(' OR ', $pictureWhereParts).')',
                    $params,
                    $types
                )
                ->fetchOne()
            ;
        }

        $values = [
            $this->trans('No') => max(0, $totalUsers - $withPicture),
            $this->trans('Yes') => max(0, $withPicture),
        ];

        return [
            'title' => $this->trans('Number of users').' ('.$this->trans('Picture').')',
            'description' => '',
            'stats' => $this->buildStats($values),
            'meta' => [
                'legacyStatsTable' => true,
                'statsTitle' => $this->trans('Number of users').' ('.$this->trans('Picture').')',
                'showStatsPercentage' => false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getLoginsByDateReport(array $parameters): array
    {
        [$start, $end] = $this->readDateRange($parameters, true);
        if (null === $start || null === $end) {
            return [
                'title' => $this->trans('Logins by date'),
                'description' => '',
                'meta' => ['requiresDateRange' => true],
                'table' => $this->emptyLoginsByDateTable(),
            ];
        }

        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT u.id, u.firstname, u.lastname, u.username, '
            .'SUM(TIMESTAMPDIFF(SECOND, l.login_date, l.logout_date)) AS time_count '
            .'FROM user u INNER JOIN track_e_login l ON u.id = l.login_user_id ';
        $params = [
            'softDeleted' => User::SOFT_DELETED,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ];
        $types = ['softDeleted' => Types::INTEGER, 'start' => Types::STRING, 'end' => Types::STRING];
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'INNER JOIN access_url_rel_user aur ON u.id = aur.user_id ';
        }
        $sql .= 'WHERE u.active <> :softDeleted AND l.login_date BETWEEN :start AND :end ';
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'AND aur.access_url_id = :urlId ';
            $params['urlId'] = $this->getCurrentAccessUrlId();
            $types['urlId'] = Types::INTEGER;
        }
        $sql .= 'GROUP BY u.id, u.firstname, u.lastname, u.username';

        $items = [];
        foreach ($connection->executeQuery($sql, $params, $types)->fetchAllAssociative() as $row) {
            $seconds = (int) ($row['time_count'] ?? 0);
            $items[] = [
                'username' => (string) $row['username'],
                'firstname' => (string) ($row['firstname'] ?? ''),
                'lastname' => (string) ($row['lastname'] ?? ''),
                'totalTime' => $this->formatSeconds($seconds),
            ];
        }

        return [
            'title' => $this->trans('Logins by date'),
            'description' => '',
            'filters' => ['rangeStart' => $parameters['rangeStart'] ?? '', 'rangeEnd' => $parameters['rangeEnd'] ?? ''],
            'table' => [
                'columns' => $this->emptyLoginsByDateTable()['columns'],
                'items' => $items,
                'totalItems' => \count($items),
            ],
            'meta' => [
                'requiresDateRange' => true,
                'canExportXls' => [] !== $items,
                'exportReport' => 'logins_by_date',
                'legacyFlatTable' => true,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getNoLoginUsersReport(): array
    {
        $total = $this->countUsers();
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $periods = [
            $this->trans('This day') => $now->sub(new DateInterval('P1D')),
            $this->trans('In the last 7 days') => $now->sub(new DateInterval('P7D')),
            $this->trans('In the last 31 days') => $now->sub(new DateInterval('P31D')),
            \sprintf($this->trans('Last %d months'), 6) => $now->sub(new DateInterval('P6M')),
        ];

        $values = [];
        foreach ($periods as $label => $cutoff) {
            $connected = $this->countDistinctLoginUsers($cutoff, null);
            $values[$label] = max(0, $total - $connected);
        }
        $values[$this->trans('Never connected')] = max(0, $total - $this->countDistinctLoginUsers(null, null));

        return [
            'title' => $this->trans('Not logged in for some time'),
            'description' => '',
            'stats' => $this->buildStats($values),
            'meta' => [
                'legacyStatsTable' => true,
                'statsTitle' => $this->trans('Not logged in for some time'),
                'showStatsPercentage' => false,
                'totalUsers' => $total,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getUsersActiveReport(array $parameters): array
    {
        [$start, $end] = $this->readDateRange($parameters, true);
        $page = $this->normalizePositiveInt($parameters['page'] ?? 1, 1, 1, PHP_INT_MAX);
        $itemsPerPage = $this->normalizePositiveInt($parameters['itemsPerPage'] ?? 10, 10, 5, 100);
        $export = true === ($parameters['export'] ?? false) || '1' === (string) ($parameters['export'] ?? '');

        if (null === $start || null === $end) {
            return [
                'title' => $this->trans('Users statistics'),
                'description' => '',
                'meta' => ['requiresDateRange' => true],
                'table' => $this->emptyUsersActiveTable(),
            ];
        }

        $baseQuery = $this->createUsersInRangeQuery($start, $end);
        $countQuery = clone $baseQuery;
        $totalItems = (int) $countQuery->select('COUNT(DISTINCT user.id)')->getQuery()->getSingleScalarResult();

        $rowsQuery = clone $baseQuery;
        $rowsQuery
            ->select(
                'user.id AS userId',
                'user.firstname AS firstname',
                'user.lastname AS lastname',
                'user.createdAt AS createdAt',
                'user.locale AS locale',
                'user.status AS status',
                'user.active AS active',
                'user.roles AS roles'
            )
        ;
        if (!$export) {
            $rowsQuery
                ->setFirstResult(($page - 1) * $itemsPerPage)
                ->setMaxResults($itemsPerPage)
            ;
        }

        $rows = $rowsQuery->getQuery()->getArrayResult();
        $userIds = array_values(array_map(static fn (array $row): int => (int) $row['userId'], $rows));
        $extraValues = $this->getExtraFieldValuesForUsers($userIds);
        $certificateUsers = $this->getCertificateUserIds($userIds);
        $languageNames = $this->languageRepository->getAllAvailableToArray(true, true);

        $items = [];
        foreach ($rows as $row) {
            $userId = (int) $row['userId'];
            $extras = $extraValues[$userId] ?? [];
            $hasCertificate = isset($certificateUsers[$userId]);
            $legalValue = (string) ($extras['legal_accept'] ?? '');
            $contract = '' !== $legalValue && '0' !== strtok($legalValue, ':');
            $targetLanguage = (string) ($extras['langue_cible'] ?? '');
            if ('' !== $targetLanguage) {
                $targetLanguage = $this->trans(ucfirst(str_replace('2', '', strtolower($targetLanguage))));
            }
            $locale = (string) ($row['locale'] ?? '');
            $roles = \is_array($row['roles'] ?? null) ? $row['roles'] : [];

            $items[] = [
                'firstname' => (string) ($row['firstname'] ?? ''),
                'lastname' => (string) ($row['lastname'] ?? ''),
                'registrationDate' => $row['createdAt'] instanceof DateTimeInterface
                    ? DateTimeImmutable::createFromInterface($row['createdAt'])->setTimezone($this->getUserTimezone())->format('Y-m-d H:i:s')
                    : '',
                'nativeLanguage' => (string) ($languageNames[$locale] ?? $locale),
                'targetLanguage' => $targetLanguage,
                'contract' => $contract ? $this->trans('Yes') : $this->trans('No'),
                'residence' => (string) ($extras['terms_paysresidence'] ?? ''),
                'career' => (string) ($extras['filiere_user'] ?? ''),
                'status' => $this->getUserStatusLabel((int) $row['status'], $hasCertificate, $roles),
                'active' => User::ACTIVE === (int) $row['active'] ? $this->trans('Yes') : $this->trans('No'),
                'certificate' => $hasCertificate ? $this->trans('Yes') : $this->trans('No'),
                'birthday' => (string) ($extras['terms_datedenaissance'] ?? ''),
            ];
        }

        $allUserIds = $this->getUserIdsInRange($start, $end);
        $charts = $this->buildUsersActiveCharts($allUserIds, $start, $end);
        $studentCount = $this->countUsersForUserList(CourseRelUser::STUDENT);

        return [
            'title' => $this->trans('Users statistics'),
            'description' => '',
            'charts' => $charts,
            'filters' => [
                'rangeStart' => $parameters['rangeStart'] ?? '',
                'rangeEnd' => $parameters['rangeEnd'] ?? '',
            ],
            'table' => [
                'columns' => $this->emptyUsersActiveTable()['columns'],
                'items' => $items,
                'totalItems' => $totalItems,
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'lazy' => !$export,
            ],
            'meta' => [
                'requiresDateRange' => true,
                'canExportXls' => $totalItems > 0,
                'exportReport' => 'users_active',
                'legacyFlatTable' => true,
                'studentCount' => $studentCount,
                'legacyUsersActive' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getUsersOnlineReport(array $parameters): array
    {
        $page = $this->normalizePositiveInt($parameters['page'] ?? 1, 1, 1, PHP_INT_MAX);
        $itemsPerPage = $this->normalizePositiveInt($parameters['itemsPerPage'] ?? 10, 10, 5, 100);
        $timeLimit = max(0, (int) $this->settingsManager->getSetting('display.time_limit_whosonline'));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $cutoff = $now->modify('-'.$timeLimit.' minutes');

        $online = [];
        $tests = [];
        foreach (self::ONLINE_INTERVALS as $minutes) {
            $online[] = [
                'label' => $this->trans('Users online')." ({$minutes}')",
                'value' => $this->countOnlineUsers($now->modify('-'.$minutes.' minutes')),
                'minutes' => $minutes,
            ];
            $tests[] = [
                'label' => $this->trans('Users active in a test')." ({$minutes}')",
                'value' => $this->countUsersActiveInTest($minutes),
                'minutes' => $minutes,
            ];
        }

        $onlineUsers = $this->getOnlineUsersPage($cutoff, $page, $itemsPerPage);

        return [
            'title' => $this->trans('Users online'),
            'description' => '',
            'table' => [
                'columns' => [
                    ['key' => 'fullName', 'label' => $this->trans('Name')],
                    ['key' => 'username', 'label' => $this->trans('Username')],
                    ['key' => 'lastActivity', 'label' => $this->trans('Last activity')],
                ],
                'items' => $onlineUsers['items'],
                'totalItems' => $onlineUsers['totalItems'],
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'lazy' => true,
            ],
            'meta' => [
                'generatedAt' => (new DateTimeImmutable('now', $this->getUserTimezone()))->format('Y-m-d H:i:s'),
                'onlineCards' => $online,
                'testCards' => $tests,
                'configuredOnlineMinutes' => $timeLimit,
                'refreshIntervalSeconds' => 15,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getNewUserRegistrationsReport(array $parameters): array
    {
        [$start, $end] = $this->readPlainDateRange($parameters);
        if (null === $start || null === $end) {
            return [
                'title' => $this->trans('New users registrations'),
                'description' => '',
                'meta' => ['requiresDateRange' => true],
            ];
        }

        $month = \is_scalar($parameters['month'] ?? null) ? (string) $parameters['month'] : '';
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $monthStart = DateTimeImmutable::createFromFormat('!Y-m-d', $month.'-01');
            if ($monthStart instanceof DateTimeImmutable) {
                $monthEnd = $monthStart->modify('last day of this month');
                $registrations = $this->queryNewUserRegistrations($monthStart, $monthEnd);

                return [
                    'title' => $this->trans('New users registrations'),
                    'description' => '',
                    'chart' => $this->buildChart('bar', $registrations, $this->trans('User registrations by day')),
                    'filters' => [
                        'rangeStart' => $start->format('Y-m-d'),
                        'rangeEnd' => $end->format('Y-m-d'),
                    ],
                    'meta' => [
                        'requiresDateRange' => true,
                        'registrationDrilldown' => true,
                        'drilldownMonth' => $month,
                        'legacyRegistrationCharts' => true,
                        'legacyFlatChart' => true,
                    ],
                ];
            }
        }

        $registrations = $this->queryNewUserRegistrations($start, $end);
        if ([] === $registrations) {
            return [
                'title' => $this->trans('New users registrations'),
                'description' => '',
                'filters' => ['rangeStart' => $start->format('Y-m-d'), 'rangeEnd' => $end->format('Y-m-d')],
                'meta' => ['requiresDateRange' => true, 'noData' => true],
            ];
        }

        $diff = $start->diff($end);
        $moreThanMonth = $diff->y >= 1 || $diff->m > 1 || (1 === $diff->m && $diff->d > 0);
        $mainValues = $moreThanMonth
            ? $this->groupDailyValuesByMonth($registrations)
            : $this->fillRegistrationDateRange($start, $end, $registrations);
        $mainTitle = $moreThanMonth ? $this->trans('User registrations by month') : $this->trans('User registrations by day');
        $creators = $this->queryRegistrationsByCreator($start, $end);

        $charts = [$this->buildChart('bar', $mainValues, $mainTitle)];
        if ([] !== $creators) {
            $charts[] = $this->buildChart('pie', $creators, $this->trans('User registrations by creator'));
        }

        return [
            'title' => $this->trans('New users registrations'),
            'description' => '',
            'charts' => $charts,
            'filters' => ['rangeStart' => $start->format('Y-m-d'), 'rangeEnd' => $end->format('Y-m-d')],
            'meta' => [
                'requiresDateRange' => true,
                'registrationMonthly' => $moreThanMonth,
                'registrationDrilldown' => false,
                'registrationMonths' => $moreThanMonth ? array_values(array_keys($mainValues)) : [],
                'legacyRegistrationCharts' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getSubscriptionByDayReport(array $parameters): array
    {
        [$start, $end] = $this->readPlainDateRange($parameters);
        if (null === $start || null === $end) {
            return [
                'title' => $this->trans('Course/Session subscriptions by day'),
                'description' => '',
                'meta' => ['requiresDateRange' => true],
            ];
        }

        $dates = [];
        $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
        foreach ($period as $date) {
            $dates[$date->format('Y-m-d')] = ['subscriptions' => 0, 'unsubscriptions' => 0];
        }

        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT DATE(default_date) AS event_date, default_event_type, COUNT(default_id) AS total '
            .'FROM track_e_default WHERE default_date BETWEEN :start AND :end '
            .'AND default_event_type IN (:events) GROUP BY DATE(default_date), default_event_type ORDER BY DATE(default_date)';
        $rows = $connection->executeQuery(
            $sql,
            [
                'start' => $start->format('Y-m-d').' 00:00:00',
                'end' => $end->format('Y-m-d').' 23:59:59',
                'events' => ['user_subscribed', 'user_unsubscribed', 'session_user_deleted'],
            ],
            ['start' => Types::STRING, 'end' => Types::STRING, 'events' => ArrayParameterType::STRING]
        )->fetchAllAssociative();
        foreach ($rows as $row) {
            $date = (string) $row['event_date'];
            if (!isset($dates[$date])) {
                continue;
            }
            if ('user_subscribed' === $row['default_event_type']) {
                $dates[$date]['subscriptions'] += (int) $row['total'];
            } else {
                $dates[$date]['unsubscriptions'] += (int) $row['total'];
            }
        }

        $items = [];
        $subscriptions = [];
        $unsubscriptions = [];
        foreach ($dates as $date => $values) {
            $items[] = [
                'date' => $date,
                'subscriptions' => $values['subscriptions'],
                'unsubscriptions' => $values['unsubscriptions'],
            ];
            $subscriptions[$date] = $values['subscriptions'];
            $unsubscriptions[$date] = $values['unsubscriptions'];
        }

        return [
            'title' => $this->trans('Course/Session subscriptions by day'),
            'description' => '',
            'chart' => [
                'type' => 'bar',
                'title' => $this->trans('Subscriptions vs unsubscriptions, by day'),
                'data' => [
                    'labels' => array_keys($dates),
                    'datasets' => [
                        ['label' => $this->trans('Subscriptions'), 'data' => array_values($subscriptions)],
                        ['label' => $this->trans('Unsubscriptions'), 'data' => array_values($unsubscriptions)],
                    ],
                ],
            ],
            'table' => [
                'columns' => [
                    ['key' => 'date', 'label' => $this->trans('Date')],
                    ['key' => 'subscriptions', 'label' => $this->trans('Subscriptions')],
                    ['key' => 'unsubscriptions', 'label' => $this->trans('Unsubscriptions')],
                ],
                'items' => $items,
                'totalItems' => \count($items),
            ],
            'filters' => ['rangeStart' => $start->format('Y-m-d'), 'rangeEnd' => $end->format('Y-m-d')],
            'meta' => [
                'requiresDateRange' => true,
                'legacyFlatChart' => true,
                'legacyFlatTable' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getUserSessionReport(array $parameters): array
    {
        [$start, $end] = $this->readDateRange($parameters, true, true);
        if (null === $start || null === $end) {
            throw new BadRequestHttpException('Invalid date range.');
        }

        $sortOrder = strtolower(trim((string) ($parameters['sortOrder'] ?? 'asc')));
        $sortOrder = 'desc' === $sortOrder ? 'DESC' : 'ASC';

        $connection = $this->entityManager->getConnection();
        $rows = $connection->executeQuery(
            'SELECT au.id AS url_id, au.url, s.id AS session_id, s.title AS session_title, '
            .'COUNT(DISTINCT CASE WHEN sru.relation_type = :studentType AND sru.registered_at >= :start '
            .'AND sru.registered_at <= :end AND auru.access_url_id = au.id THEN sru.user_id END) AS users_count '
            .'FROM access_url au '
            .'INNER JOIN access_url_rel_session aurs ON aurs.access_url_id = au.id '
            .'INNER JOIN session s ON s.id = aurs.session_id '
            .'LEFT JOIN session_rel_user sru ON sru.session_id = s.id '
            .'LEFT JOIN access_url_rel_user auru ON auru.user_id = sru.user_id '
            .'GROUP BY au.id, au.url, s.id, s.title '
            .'ORDER BY au.url '.$sortOrder.', s.id ASC',
            [
                'studentType' => Session::STUDENT,
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ],
            ['studentType' => Types::INTEGER, 'start' => Types::STRING, 'end' => Types::STRING]
        )->fetchAllAssociative();

        $sessionIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['session_id'],
            $rows
        )));
        $courseTitlesBySession = [];
        if ([] !== $sessionIds) {
            $courseRows = $connection->executeQuery(
                'SELECT src.session_id, c.title '
                .'FROM session_rel_course src '
                .'INNER JOIN course c ON c.id = src.c_id '
                .'WHERE src.session_id IN (:sessionIds) '
                .'ORDER BY src.session_id ASC, src.position ASC, src.c_id ASC',
                ['sessionIds' => $sessionIds],
                ['sessionIds' => ArrayParameterType::INTEGER]
            )->fetchAllAssociative();
            foreach ($courseRows as $courseRow) {
                $sessionId = (int) $courseRow['session_id'];
                $courseTitlesBySession[$sessionId] ??= [];
                $courseTitlesBySession[$sessionId][] = (string) $courseRow['title'];
            }
        }

        $items = [];
        foreach ($rows as $row) {
            $sessionId = (int) $row['session_id'];
            $items[] = [
                'url' => (string) $row['url'],
                'session' => (string) $row['session_title'],
                'sessionId' => $sessionId,
                'sessionUrl' => '/main/session/resume_session.php?id_session='.$sessionId,
                'course' => implode(', ', $courseTitlesBySession[$sessionId] ?? []),
                'count' => (int) $row['users_count'],
            ];
        }

        return [
            'title' => $this->trans('Portal user session stats'),
            'description' => '',
            'filters' => [
                'rangeStart' => $this->toLocalDate($start),
                'rangeEnd' => $this->toLocalDate($end),
                'sortOrder' => strtolower($sortOrder),
            ],
            'table' => [
                'columns' => [
                    ['key' => 'url', 'label' => 'URL', 'sortable' => true],
                    ['key' => 'session', 'label' => $this->trans('Session')],
                    ['key' => 'course', 'label' => $this->trans('Course')],
                    ['key' => 'count', 'label' => $this->trans('Number of users')],
                ],
                'items' => $items,
                'totalItems' => \count($items),
            ],
            'meta' => [
                'legacyUserSession' => true,
                'canExportCsv' => false,
                'canExportXls' => true,
                'exportReport' => 'user_session',
                'legacyFlatTable' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getQuarterlyReport(array $parameters): array
    {
        $section = \is_scalar($parameters['section'] ?? null) ? (string) $parameters['section'] : '';
        if ('' === $section) {
            $cards = [
                ['id' => 'users', 'title' => $this->trans('Number of users registered and connected')],
                ['id' => 'courses', 'title' => $this->trans('Number of existing and available courses')],
                ['id' => 'hours_of_training', 'title' => $this->trans('Hours of training')],
                ['id' => 'certificates', 'title' => $this->trans('Number of certificates generated')],
                ['id' => 'sessions_by_duration', 'title' => $this->trans('Number of sessions per duration')],
                ['id' => 'courses_and_sessions', 'title' => $this->trans('Number of courses, sessions and subscribed users')],
            ];
            if (1 === $this->getCurrentAccessUrlId()) {
                $cards[] = ['id' => 'disk_usage', 'title' => $this->trans('Total disk usage')];
            }

            return [
                'title' => $this->trans('Quarterly report'),
                'description' => '',
                'meta' => ['quarterlyCards' => $cards],
            ];
        }

        if (!\in_array($section, self::QUARTERLY_SECTIONS, true)) {
            throw new NotFoundHttpException('Unknown quarterly report section.');
        }

        return [
            'title' => $this->trans('Quarterly report'),
            'description' => '',
            'meta' => [
                'quarterlySection' => $section,
                'quarterlySectionData' => $this->getQuarterlySection($section),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getQuarterlySection(string $section): array
    {
        return match ($section) {
            'users' => $this->getQuarterlyUsersSection(),
            'courses' => $this->getQuarterlyCoursesSection(),
            'hours_of_training' => $this->getQuarterlyHoursSection(),
            'certificates' => $this->getQuarterlyCertificatesSection(),
            'sessions_by_duration' => $this->getQuarterlySessionsDurationSection(),
            'courses_and_sessions' => $this->getQuarterlyCoursesAndSessionsSection(),
            'disk_usage' => $this->getQuarterlyDiskUsageSection(),
            default => throw new NotFoundHttpException('Unknown quarterly report section.'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function getQuarterlyUsersSection(): array
    {
        $quarters = $this->getSixQuarterPeriods();
        $headers = $this->getQuarterlyHeaders($quarters);
        $counts = [];
        $connected = [];
        foreach ($quarters as $key => $quarter) {
            $counts[$key] = $this->countUsersUntil($quarter['end'], 'current' === $key);
            $connected[$key] = $this->countConnectedUsers($quarter['start'], $quarter['end']);
        }

        $rows = [
            [
                $this->trans('Number of users registered (total)'),
                $counts['pre5'], $counts['pre4'], $counts['pre3'], $counts['pre2'], $counts['pre1'],
                $this->incrementPercent($counts['pre1'], $counts['pre5']),
                $counts['current'],
            ],
            [
                $this->trans('Number of users registered (new vs previous quarter)'),
                '-',
                '+'.($counts['pre4'] - $counts['pre5']),
                '+'.($counts['pre3'] - $counts['pre4']),
                '+'.($counts['pre2'] - $counts['pre3']),
                '+'.($counts['pre1'] - $counts['pre2']),
                '-',
                '+'.($counts['current'] - $counts['pre1']),
            ],
            [
                $this->trans('Number of users who connected'),
                $connected['pre5'], $connected['pre4'], $connected['pre3'], $connected['pre2'], $connected['pre1'],
                $this->incrementPercent($connected['pre1'], $connected['pre5']),
                $connected['current'],
            ],
        ];

        return $this->buildQuarterlyTable($headers, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function getQuarterlyCoursesSection(): array
    {
        $quarters = $this->getSixQuarterPeriods();
        $headers = $this->getQuarterlyHeaders($quarters);
        $existing = [];
        $available = [];
        foreach ($quarters as $key => $quarter) {
            $existing[$key] = $this->countCoursesUntil($quarter['end'], 'current' === $key, null);
            $available[$key] = $this->countCoursesUntil($quarter['end'], 'current' === $key, [1, 2, 3]);
        }

        return $this->buildQuarterlyTable($headers, [
            [
                $this->trans('Number of existing courses (total)'),
                $existing['pre5'], $existing['pre4'], $existing['pre3'], $existing['pre2'], $existing['pre1'],
                $this->incrementPercent($existing['pre1'], $existing['pre5']),
                $existing['current'],
            ],
            [
                $this->trans('Number of available courses (not closed or hidden, total)'),
                $available['pre5'], $available['pre4'], $available['pre3'], $available['pre2'], $available['pre1'],
                $this->incrementPercent($available['pre1'], $available['pre5']),
                $available['current'],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getQuarterlyHoursSection(): array
    {
        $quarters = $this->getSixQuarterPeriods();
        $headers = $this->getQuarterlyHeaders($quarters);
        $values = [];
        foreach ($quarters as $key => $quarter) {
            $values[$key] = $this->getTrainingHours($quarter['start'], $quarter['end']);
        }

        return $this->buildQuarterlyTable($headers, [[
            $this->trans('Number of hours of training followed (total)'),
            $values['pre5'], $values['pre4'], $values['pre3'], $values['pre2'], $values['pre1'],
            $this->incrementPercent($values['pre1'], $values['pre5']),
            $values['current'],
        ]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getQuarterlyCertificatesSection(): array
    {
        $quarters = $this->getSixQuarterPeriods();
        $headers = $this->getQuarterlyHeaders($quarters);
        $values = [];
        foreach ($quarters as $key => $quarter) {
            $values[$key] = $this->countCertificatesUntil($quarter['end']);
        }

        return $this->buildQuarterlyTable($headers, [[
            $this->trans('Number of certificates generated'),
            $values['pre5'], $values['pre4'], $values['pre3'], $values['pre2'], $values['pre1'],
            $this->incrementPercent($values['pre1'], $values['pre5']),
            $values['current'],
        ]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getQuarterlySessionsDurationSection(): array
    {
        $quarters = $this->getSixQuarterPeriods();
        $headers = $this->getQuarterlyHeaders($quarters);
        $headers[0] = $this->trans('Sessions per duration (by quarter)');
        $values = [];
        foreach ($quarters as $key => $quarter) {
            $values[$key] = $this->getSessionsByDuration($quarter['start'], $quarter['end']);
        }

        $buckets = [
            '0' => '0-5′',
            '5' => '6-10′',
            '10' => '11-15′',
            '15' => '16-30′',
            '30' => '31-60′',
            '60' => '60-∞′',
        ];
        $rows = [];
        foreach ($buckets as $bucket => $label) {
            $rows[] = [
                $label,
                $values['pre5'][$bucket], $values['pre4'][$bucket], $values['pre3'][$bucket],
                $values['pre2'][$bucket], $values['pre1'][$bucket],
                $this->incrementPercent($values['pre1'][$bucket], $values['pre5'][$bucket]),
                $values['current'][$bucket],
            ];
        }

        return $this->buildQuarterlyTable($headers, $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function getQuarterlyCoursesAndSessionsSection(): array
    {
        return [
            'tables' => [
                [
                    'title' => '',
                    'columns' => [
                        ['key' => 'course', 'label' => $this->trans('List of course codes')],
                        ['key' => 'subscribed', 'label' => $this->trans('Number of subscribed users').'*'],
                        ['key' => 'finished', 'label' => $this->trans('Number of users who finished the course (as defined in gradebook)')],
                    ],
                    'items' => $this->getCourseCompletionCounts(false),
                ],
                [
                    'title' => '',
                    'columns' => [
                        ['key' => 'course', 'label' => $this->trans('List of course codes and sessions')],
                        ['key' => 'subscribed', 'label' => $this->trans('Number of subscribed users').'*'],
                        ['key' => 'finished', 'label' => $this->trans('Number of users who finished the course (as defined in gradebook)')],
                    ],
                    'items' => $this->getCourseCompletionCounts(true),
                ],
            ],
            'warning' => $this->trans('*: All users, including inactive, are included'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getQuarterlyDiskUsageSection(): array
    {
        if (1 !== $this->getCurrentAccessUrlId()) {
            throw new NotFoundHttpException('Disk usage is available only on the main access URL.');
        }
        if ('Windows' === PHP_OS_FAMILY) {
            return ['message' => $this->trans('The space used on disk cannot be measured properly on Windows-based systems.')];
        }

        $path = \dirname(__DIR__, 4).'/var/';
        $output = [];
        $status = 0;
        exec('du -s '.escapeshellarg($path), $output, $status);
        $sizeGb = 0.0;
        if (0 === $status && isset($output[0])) {
            $parts = preg_split('/\s+/', trim((string) $output[0]));
            if (\is_array($parts) && isset($parts[0])) {
                $sizeGb = round(((int) $parts[0]) / (1024 * 1024), 1);
            }
        }

        $currentUrl = $this->accessUrlHelper->getCurrent();
        $url = null !== $currentUrl ? $currentUrl->getUrl() : '';
        $hostingLimit = $this->settingsManagerHelper->getOverride('hosting_limit', $currentUrl);
        $diskLimitKb = \is_array($hostingLimit) ? (float) ($hostingLimit['disk_space'] ?? 0) : 0.0;

        $message = \sprintf($this->trans('Total space used by %s is %sGB'), $url, $sizeGb);
        $limitGb = null;
        if ($diskLimitKb > 0) {
            $limitGb = round($diskLimitKb / 1024, 1);
            $message = \sprintf(
                $this->trans('Total space used by portal %s is %sGB (limit is set to %sGB)'),
                $url,
                $sizeGb,
                $limitGb
            );
        }

        return [
            'message' => $message,
            'sizeGb' => $sizeGb,
            'limitGb' => $limitGb,
        ];
    }

    private function countUsers(?int $status = null, ?string $categoryCode = null): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT user.id)')
            ->from(User::class, 'user')
            ->andWhere('user.active <> :softDeleted')
            ->setParameter('softDeleted', User::SOFT_DELETED, Types::INTEGER)
        ;
        if (null !== $status) {
            $queryBuilder
                ->andWhere('user.status = :status')
                ->setParameter('status', $status, Types::INTEGER)
            ;
        }
        if (null !== $categoryCode) {
            $queryBuilder
                ->innerJoin('user.courses', 'courseRelUser')
                ->innerJoin('courseRelUser.course', 'course')
                ->innerJoin('course.categories', 'category')
                ->andWhere('category.code = :categoryCode')
                ->setParameter('categoryCode', $categoryCode, Types::STRING)
            ;
        }
        $this->applyUserAccessUrlScope($queryBuilder, 'user', 'userCountPortal');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function countUsersForUserList(?int $status = null): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT user.id)')
            ->from(User::class, 'user')
            ->andWhere('user.status <> :anonymous')
            ->setParameter('anonymous', 6, Types::INTEGER)
        ;
        if (null !== $status) {
            $queryBuilder
                ->andWhere('user.status = :status')
                ->setParameter('status', $status, Types::INTEGER)
            ;
        }
        $this->applyUserAccessUrlScope($queryBuilder, 'user', 'userListPortal');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<string, int>
     */
    private function getDailyLoginCounts(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        bool $distinct,
        int $sessionDuration
    ): array {
        $values = [];
        $cursor = $start->setTime(0, 0);
        while ($cursor < $end->setTime(0, 0)) {
            $values[$cursor->format('m-d')] = 0;
            $cursor = $cursor->modify('+1 day');
        }

        $connection = $this->entityManager->getConnection();
        $field = $distinct ? 'COUNT(DISTINCT l.login_user_id)' : 'COUNT(l.login_id)';
        $sql = "SELECT {$field} AS total, DATE(l.login_date) AS login_day FROM track_e_login l ";
        $params = ['start' => $start->format('Y-m-d h:i:s'), 'duration' => $sessionDuration * 60];
        $types = ['start' => Types::STRING, 'duration' => Types::INTEGER];
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'INNER JOIN access_url_rel_user aur ON aur.user_id = l.login_user_id ';
        }
        $sql .= 'WHERE l.login_date >= :start ';
        if (0 === $sessionDuration) {
            $sql .= 'AND l.logout_date <> l.login_date ';
        } else {
            $sql .= 'AND TIMESTAMPDIFF(SECOND, l.login_date, l.logout_date) > :duration ';
        }
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'AND aur.access_url_id = :urlId ';
            $params['urlId'] = $this->getCurrentAccessUrlId();
            $types['urlId'] = Types::INTEGER;
        }
        $sql .= 'GROUP BY DATE(l.login_date)';
        foreach ($connection->executeQuery($sql, $params, $types)->fetchAllAssociative() as $row) {
            $key = substr((string) $row['login_day'], 5, 5);
            $values[$key] = (int) $row['total'];
        }

        return $values;
    }

    /**
     * @return array<int, array{label:string,value:int,detail?:string}>
     */
    private function getRecentLoginPeriodStats(bool $distinct, int $sessionDuration): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $end = $now->setTime(23, 59, 59);
        $items = [];

        foreach ([1, 7, 31] as $days) {
            $start = 1 === $days
                ? $now->setTime(0, 0, 0)
                : $now->sub(new DateInterval('P'.$days.'D'))->setTime(0, 0, 0);
            $label = 1 === $days ? $this->trans('Today') : \sprintf($this->trans('Last %s days'), $days);
            $localStart = $start->setTimezone($this->getUserTimezone())->format('Y-m-d');
            $localEnd = $end->setTimezone($this->getUserTimezone())->format('Y-m-d');
            $items[] = [
                'label' => $label,
                'value' => $this->countLoginRows($start, $end, $distinct, $sessionDuration),
                'detail' => '['.$localStart.' - '.$localEnd.']',
            ];
        }

        $items[] = [
            'label' => $this->trans('Total'),
            'value' => $this->countLoginRows(null, null, $distinct, $sessionDuration),
        ];

        return $items;
    }

    private function countLoginRows(
        ?DateTimeImmutable $start,
        ?DateTimeImmutable $end,
        bool $distinct,
        int $sessionDuration
    ): int {
        $connection = $this->entityManager->getConnection();
        $field = $distinct ? 'COUNT(DISTINCT l.login_user_id)' : 'COUNT(l.login_id)';
        $sql = "SELECT {$field} total FROM track_e_login l ";
        $params = ['duration' => $sessionDuration * 60];
        $types = ['duration' => Types::INTEGER];
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'INNER JOIN access_url_rel_user aur ON aur.user_id = l.login_user_id ';
        }
        $where = [];
        $where[] = 0 === $sessionDuration
            ? 'l.logout_date <> l.login_date'
            : 'TIMESTAMPDIFF(SECOND, l.login_date, l.logout_date) > :duration';
        if (null !== $start && null !== $end) {
            $where[] = 'l.login_date BETWEEN :start AND :end';
            $params['start'] = $start->format('Y-m-d H:i:s');
            $params['end'] = $end->format('Y-m-d H:i:s');
            $types['start'] = Types::STRING;
            $types['end'] = Types::STRING;
        }
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $where[] = 'aur.access_url_id = :urlId';
            $params['urlId'] = $this->getCurrentAccessUrlId();
            $types['urlId'] = Types::INTEGER;
        }
        $sql .= 'WHERE '.implode(' AND ', $where);

        return (int) $connection->executeQuery($sql, $params, $types)->fetchOne();
    }

    /**
     * @return array<string, int>
     */
    private function queryLoginDayOrHourCounts(string $type, bool $recent, ?int $urlId): array
    {
        $connection = $this->entityManager->getConnection();
        $format = 'day' === $type ? '%w' : '%H';
        $sql = "SELECT DATE_FORMAT(l.login_date, '{$format}') stat_date, COUNT(l.login_id) total FROM track_e_login l ";
        $params = [];
        $types = [];
        if ($this->accessUrlHelper->isMultiple() && null !== $urlId) {
            $sql .= 'INNER JOIN access_url_rel_user aur ON aur.user_id = l.login_user_id ';
        }
        $where = [];
        if ($recent) {
            $unit = 'day' === $type ? 'WEEK' : 'DAY';
            $where[] = "l.login_date > DATE_SUB(:now, INTERVAL 1 {$unit})";
            $params['now'] = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
            $types['now'] = Types::STRING;
        }
        if ($this->accessUrlHelper->isMultiple() && null !== $urlId) {
            $where[] = 'aur.access_url_id = :urlId';
            $params['urlId'] = $urlId;
            $types['urlId'] = Types::INTEGER;
        }
        if ([] !== $where) {
            $sql .= 'WHERE '.implode(' AND ', $where).' ';
        }
        $sql .= 'GROUP BY stat_date ORDER BY stat_date';

        $weekdays = [
            $this->trans('Sunday'), $this->trans('Monday'), $this->trans('Tuesday'), $this->trans('Wednesday'),
            $this->trans('Thursday'), $this->trans('Friday'), $this->trans('Saturday'),
        ];
        $values = [];
        foreach ($connection->executeQuery($sql, $params, $types)->fetchAllAssociative() as $row) {
            $key = (string) $row['stat_date'];
            $label = 'day' === $type ? ($weekdays[(int) $key] ?? $key) : $key;
            $values[$label] = (int) $row['total'];
        }

        return $values;
    }

    private function countDistinctLoginUsers(?DateTimeImmutable $start, ?DateTimeImmutable $end): int
    {
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT COUNT(DISTINCT l.login_user_id) total FROM track_e_login l ';
        $params = [];
        $types = [];
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'INNER JOIN access_url_rel_user aur ON aur.user_id = l.login_user_id ';
        }
        $where = [];
        if (null !== $start) {
            $where[] = 'l.login_date >= :start';
            $params['start'] = $start->format('Y-m-d H:i:s');
            $types['start'] = Types::STRING;
        }
        if (null !== $end) {
            $where[] = 'l.login_date <= :end';
            $params['end'] = $end->format('Y-m-d H:i:s');
            $types['end'] = Types::STRING;
        }
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $where[] = 'aur.access_url_id = :urlId';
            $params['urlId'] = $this->getCurrentAccessUrlId();
            $types['urlId'] = Types::INTEGER;
        }
        if ([] !== $where) {
            $sql .= 'WHERE '.implode(' AND ', $where);
        }

        return (int) $connection->executeQuery($sql, $params, $types)->fetchOne();
    }

    private function createUsersInRangeQuery(DateTimeImmutable $start, DateTimeImmutable $end): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(User::class, 'user')
            ->andWhere('user.createdAt BETWEEN :start AND :end')
            ->andWhere('user.status <> :anonymous')
            ->setParameter('start', $start, Types::DATETIME_IMMUTABLE)
            ->setParameter('end', $end, Types::DATETIME_IMMUTABLE)
            ->setParameter('anonymous', 6, Types::INTEGER)
        ;
        $this->applyUserAccessUrlScope($queryBuilder, 'user', 'activeUserPortal');

        return $queryBuilder;
    }

    /**
     * @return array<int, int>
     */
    private function getUserIdsInRange(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $queryBuilder = $this->createUsersInRangeQuery($start, $end);
        $rows = $queryBuilder->select('DISTINCT user.id AS id')->getQuery()->getArrayResult();

        return array_values(array_map(static fn (array $row): int => (int) $row['id'], $rows));
    }

    /**
     * @param array<int, int> $userIds
     *
     * @return array<int, array<string, string>>
     */
    private function getExtraFieldValuesForUsers(array $userIds): array
    {
        if ([] === $userIds) {
            return [];
        }
        $variables = ['langue_cible', 'legal_accept', 'terms_paysresidence', 'filiere_user', 'terms_datedenaissance', 'statusocial', 'termactivated'];
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('value.itemId AS itemId', 'field.variable AS variable', 'value.fieldValue AS fieldValue')
            ->from(ExtraFieldValues::class, 'value')
            ->innerJoin('value.field', 'field')
            ->andWhere('value.itemId IN (:userIds)')
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
            ->andWhere('field.itemType = :itemType')
            ->setParameter('itemType', ExtraField::USER_FIELD_TYPE, Types::INTEGER)
            ->andWhere('field.variable IN (:variables)')
            ->setParameter('variables', $variables, ArrayParameterType::STRING)
        ;

        $values = [];
        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $values[(int) $row['itemId']][(string) $row['variable']] = (string) ($row['fieldValue'] ?? '');
        }

        return $values;
    }

    /**
     * @param array<int, int> $userIds
     *
     * @return array<int, true>
     */
    private function getCertificateUserIds(array $userIds): array
    {
        if ([] === $userIds) {
            return [];
        }
        $rows = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT IDENTITY(certificate.user) AS userId')
            ->from(GradebookCertificate::class, 'certificate')
            ->andWhere('IDENTITY(certificate.user) IN (:userIds)')
            ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getArrayResult()
        ;
        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['userId']] = true;
        }

        return $result;
    }

    /**
     * @param array<int, string> $roles
     */
    private function getUserStatusLabel(int $status, bool $hasCertificate, array $roles): string
    {
        if (CourseRelUser::STUDENT === $status) {
            return $hasCertificate ? $this->trans('Graduated') : $this->trans('Learner');
        }
        if (CourseRelUser::TEACHER === $status) {
            return \in_array('ROLE_ADMIN', $roles, true) || \in_array('ROLE_GLOBAL_ADMIN', $roles, true)
                ? $this->trans('Admin')
                : $this->trans('Teacher');
        }
        if (self::USER_STATUS_STUDENT_BOSS === $status) {
            return $this->trans('Student boss');
        }

        return (string) $status;
    }

    /**
     * @param array<int, int> $userIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildUsersActiveCharts(array $userIds, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $charts = [];
        $activeCounts = [
            $this->trans('Active') => 0,
            $this->trans('inactive') => 0,
        ];
        $languageCounts = [];
        if ([] !== $userIds) {
            $rows = $this->entityManager->createQueryBuilder()
                ->select('user.active AS active', 'user.locale AS locale', 'COUNT(user.id) AS total')
                ->from(User::class, 'user')
                ->andWhere('user.id IN (:userIds)')
                ->setParameter('userIds', $userIds, ArrayParameterType::INTEGER)
                ->groupBy('user.active, user.locale')
                ->getQuery()->getArrayResult()
            ;
            foreach ($rows as $row) {
                $count = (int) $row['total'];
                if (User::ACTIVE === (int) $row['active']) {
                    $activeCounts[$this->trans('Active')] += $count;
                } elseif (User::INACTIVE === (int) $row['active']) {
                    $activeCounts[$this->trans('inactive')] += $count;
                }
                $locale = (string) ($row['locale'] ?? '');
                $languageCounts[$locale] = ($languageCounts[$locale] ?? 0) + $count;
            }
        }
        $charts[] = $this->buildChart('pie', $activeCounts, $this->trans('Users created in the selected period'));

        $extraValues = $this->getExtraFieldValuesForUsers($userIds);
        $charts[] = $this->buildExtraFieldOptionChart(
            $userIds,
            $extraValues,
            'statusocial',
            $this->trans('Users by status'),
            false,
            $this->trans('Number of users')
        );

        $languages = $this->languageRepository->getAllAvailableToArray(true, true);
        $languageDisplay = [];
        foreach ($languageCounts as $locale => $count) {
            $languageDisplay[(string) ($languages[$locale] ?? $locale)] = $count;
        }
        $charts[] = $this->buildChart(
            'pie',
            $languageDisplay,
            $this->trans('Number of users'),
            $this->trans('Users per language')
        );
        $charts[] = $this->buildExtraFieldOptionChart(
            $userIds,
            $extraValues,
            'langue_cible',
            $this->trans('Users by target language'),
            true,
            $this->trans('Number of users')
        );
        $charts[] = $this->buildAgeChart($userIds, $extraValues, $this->trans('Number of users'));
        $charts[] = $this->buildExtraFieldOptionChart(
            $userIds,
            $extraValues,
            'filiere_user',
            $this->trans('Users by career'),
            false,
            $this->trans('Number of users')
        );
        $charts[] = $this->buildBooleanExtraFieldChart(
            $userIds,
            $extraValues,
            'termactivated',
            $this->trans('Users by contract'),
            $this->trans('Number of users')
        );
        if ($this->hasUserExtraField('langue_cible')) {
            $certificateUsers = $this->getCertificateUserIds($userIds);
            $charts[] = $this->buildChart('pie', [
                $this->trans('Yes') => \count($certificateUsers),
                $this->trans('No') => max(0, \count($userIds) - \count($certificateUsers)),
            ], $this->trans('Number of users'), $this->trans('Users by certificate'));
        }

        return array_values(array_filter($charts, static fn (array $chart): bool => [] !== ($chart['data']['labels'] ?? [])));
    }

    private function hasUserExtraField(string $variable): bool
    {
        return $this->entityManager->getRepository(ExtraField::class)->findOneBy([
            'itemType' => ExtraField::USER_FIELD_TYPE,
            'variable' => $variable,
        ]) instanceof ExtraField;
    }

    private function buildExtraFieldOptionChart(
        array $userIds,
        array $extraValues,
        string $variable,
        string $title,
        bool $translateOptions = false,
        ?string $datasetLabel = null
    ): array {
        $field = $this->entityManager->getRepository(ExtraField::class)->findOneBy([
            'itemType' => ExtraField::USER_FIELD_TYPE,
            'variable' => $variable,
        ]);
        if (!$field instanceof ExtraField || null === $field->getId()) {
            return ['data' => ['labels' => [], 'datasets' => []], 'title' => $title, 'type' => 'pie'];
        }
        $options = $this->entityManager->getRepository(ExtraFieldOptions::class)->findBy(
            ['field' => $field],
            ['optionOrder' => 'ASC']
        );
        $counts = [];
        $found = 0;
        foreach ($options as $option) {
            $value = (string) ($option->getValue() ?? '');
            $label = (string) ($option->getDisplayText() ?? $value);
            if ($translateOptions) {
                $label = $this->trans(ucfirst(str_replace('2', '', strtolower($label))));
            }
            $count = 0;
            foreach ($userIds as $userId) {
                if (($extraValues[$userId][$variable] ?? null) === $value) {
                    ++$count;
                }
            }
            $counts[$label] = $count;
            $found += $count;
        }
        $counts[$this->trans('Not available')] = max(0, \count($userIds) - $found);

        return $this->buildChart('pie', $counts, $datasetLabel ?? $title, $title);
    }

    /**
     * @param array<int, int>                   $userIds
     * @param array<int, array<string, string>> $extraValues
     *
     * @return array<string, mixed>
     */
    private function buildBooleanExtraFieldChart(
        array $userIds,
        array $extraValues,
        string $variable,
        string $title,
        ?string $datasetLabel = null
    ): array {
        $field = $this->entityManager->getRepository(ExtraField::class)->findOneBy([
            'itemType' => ExtraField::USER_FIELD_TYPE,
            'variable' => $variable,
        ]);
        if (!$field instanceof ExtraField) {
            return ['data' => ['labels' => [], 'datasets' => []], 'title' => $title, 'type' => 'pie'];
        }
        $yes = 0;
        foreach ($userIds as $userId) {
            if ('1' === (string) ($extraValues[$userId][$variable] ?? '')) {
                ++$yes;
            }
        }

        return $this->buildChart('pie', [
            $this->trans('Yes') => $yes,
            $this->trans('No') => max(0, \count($userIds) - $yes),
        ], $datasetLabel ?? $title, $title);
    }

    /**
     * @param array<int, int>                   $userIds
     * @param array<int, array<string, string>> $extraValues
     *
     * @return array<string, mixed>
     */
    private function buildAgeChart(array $userIds, array $extraValues, ?string $datasetLabel = null): array
    {
        $field = $this->entityManager->getRepository(ExtraField::class)->findOneBy([
            'itemType' => ExtraField::USER_FIELD_TYPE,
            'variable' => 'terms_datedenaissance',
        ]);
        if (!$field instanceof ExtraField) {
            return ['data' => ['labels' => [], 'datasets' => []], 'title' => $this->trans('Users by age'), 'type' => 'pie'];
        }
        $counts = ['16-17' => 0, '18-25' => 0, '26-30' => 0];
        $now = new DateTimeImmutable();
        foreach ($userIds as $userId) {
            $value = (string) ($extraValues[$userId]['terms_datedenaissance'] ?? '');
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $value) {
                continue;
            }
            $years = $now->diff($date)->y;
            if ($years >= 16 && $years <= 17) {
                ++$counts['16-17'];
            }
            if ($years >= 18 && $years <= 25) {
                ++$counts['18-25'];
            }
            if ($years >= 26 && $years <= 30) {
                ++$counts['26-30'];
            }
        }

        $title = $this->trans('Users by age');

        return $this->buildChart('pie', $counts, $datasetLabel ?? $title, $title);
    }

    private function countOnlineUsers(DateTimeImmutable $cutoff): int
    {
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT COUNT(DISTINCT o.login_user_id) total FROM track_e_online o INNER JOIN user u ON u.id = o.login_user_id '
            .'WHERE u.active <> :softDeleted AND u.status <> :anonymous AND o.login_date >= :cutoff';
        $params = [
            'softDeleted' => User::SOFT_DELETED,
            'anonymous' => 6,
            'cutoff' => $cutoff->format('Y-m-d H:i:s'),
        ];
        $types = ['softDeleted' => Types::INTEGER, 'anonymous' => Types::INTEGER, 'cutoff' => Types::STRING];
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= ' AND o.access_url_id = :urlId';
            $params['urlId'] = $this->getCurrentAccessUrlId();
            $types['urlId'] = Types::INTEGER;
        }

        return (int) $connection->executeQuery($sql, $params, $types)->fetchOne();
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, totalItems: int}
     */
    private function getOnlineUsersPage(DateTimeImmutable $cutoff, int $page, int $itemsPerPage): array
    {
        $connection = $this->entityManager->getConnection();
        $where = [
            'u.active <> :softDeleted',
            'u.status <> :anonymous',
            'o.login_date >= :cutoff',
        ];
        $params = [
            'softDeleted' => User::SOFT_DELETED,
            'anonymous' => 6,
            'cutoff' => $cutoff->format('Y-m-d H:i:s'),
        ];
        $types = [
            'softDeleted' => Types::INTEGER,
            'anonymous' => Types::INTEGER,
            'cutoff' => Types::STRING,
        ];

        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $where[] = 'o.access_url_id = :urlId';
            $params['urlId'] = $this->getCurrentAccessUrlId();
            $types['urlId'] = Types::INTEGER;
        }

        $whereSql = implode(' AND ', $where);
        $totalItems = (int) $connection->executeQuery(
            'SELECT COUNT(DISTINCT o.login_user_id) FROM track_e_online o '
            .'INNER JOIN user u ON u.id = o.login_user_id WHERE '.$whereSql,
            $params,
            $types
        )->fetchOne();

        $rows = $connection->executeQuery(
            'SELECT u.id, u.firstname, u.lastname, u.username, MAX(o.login_date) AS last_activity '
            .'FROM track_e_online o INNER JOIN user u ON u.id = o.login_user_id '
            .'WHERE '.$whereSql.' '
            .'GROUP BY u.id, u.firstname, u.lastname, u.username '
            .'ORDER BY last_activity DESC, u.id ASC '
            .'LIMIT '.max(0, ($page - 1) * $itemsPerPage).', '.$itemsPerPage,
            $params,
            $types
        )->fetchAllAssociative();

        $items = [];
        foreach ($rows as $row) {
            $userId = (int) $row['id'];
            $lastActivity = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                (string) ($row['last_activity'] ?? ''),
                new DateTimeZone('UTC')
            );
            $items[] = [
                'id' => $userId,
                'fullName' => trim((string) ($row['firstname'] ?? '').' '.(string) ($row['lastname'] ?? '')),
                'username' => (string) ($row['username'] ?? ''),
                'lastActivity' => $lastActivity instanceof DateTimeImmutable ? $lastActivity->format(DateTimeInterface::ATOM) : '',
                'detailsUrl' => '/main/admin/user_information.php?user_id='.$userId,
            ];
        }

        return [
            'items' => $items,
            'totalItems' => $totalItems,
        ];
    }

    private function countUsersActiveInTest(int $minutes): int
    {
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT COUNT(DISTINCT user_id) FROM track_e_attempt '
            .'WHERE DATE_ADD(tms, INTERVAL '.$minutes.' MINUTE) > UTC_TIMESTAMP()';

        return (int) $connection->executeQuery($sql)->fetchOne();
    }

    /**
     * @return array<string, int>
     */
    private function queryNewUserRegistrations(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $connection = $this->entityManager->getConnection();
        $rows = $connection->executeQuery(
            "SELECT DATE_FORMAT(created_at, '%Y-%m-%d') reg_date, COUNT(*) total FROM user "
            .'WHERE created_at BETWEEN :start AND :end GROUP BY reg_date',
            [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
            ['start' => Types::STRING, 'end' => Types::STRING]
        )->fetchAllAssociative();

        $values = [];
        foreach ($rows as $row) {
            $date = (string) ($row['reg_date'] ?? '');
            if ('' !== $date) {
                $values[$date] = (int) ($row['total'] ?? 0);
            }
        }

        return $values;
    }

    /**
     * Legacy main registration chart fills missing days only for ranges of one month or less.
     *
     * @param array<string, int> $registrations
     *
     * @return array<string, int>
     */
    private function fillRegistrationDateRange(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        array $registrations
    ): array {
        $values = [];
        $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $values[$key] = (int) ($registrations[$key] ?? 0);
        }

        return $values;
    }

    /**
     * @return array<string, int>
     */
    private function queryRegistrationsByCreator(DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $connection = $this->entityManager->getConnection();
        $rows = $connection->executeQuery(
            'SELECT u.creator_id, COUNT(u.id) total, c.firstname, c.lastname FROM user u '
            .'LEFT JOIN user c ON u.creator_id = c.id WHERE u.created_at BETWEEN :start AND :end '
            .'AND u.creator_id IS NOT NULL GROUP BY u.creator_id, c.firstname, c.lastname',
            ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')],
            ['start' => Types::STRING, 'end' => Types::STRING]
        )->fetchAllAssociative();
        $values = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row['firstname'] ?? '').' '.(string) ($row['lastname'] ?? ''));
            if ('' !== $name) {
                $values[$name] = (int) $row['total'];
            }
        }

        return $values;
    }

    /**
     * @param array<string, int> $daily
     *
     * @return array<string, int>
     */
    private function groupDailyValuesByMonth(array $daily): array
    {
        $result = [];
        foreach ($daily as $date => $count) {
            $month = substr($date, 0, 7);
            $result[$month] = ($result[$month] ?? 0) + $count;
        }

        return $result;
    }

    /**
     * @return array<string, array{start:string,end:string,title:string}>
     */
    private function getSixQuarterPeriods(): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $month = (int) $now->format('n');
        $quarter = (int) ceil($month / 3);
        $startMonth = (($quarter - 1) * 3) + 1;
        $currentStart = new DateTimeImmutable($now->format('Y').'-'.\sprintf('%02d', $startMonth).'-01', new DateTimeZone('UTC'));

        $result = [];
        foreach (['current' => 0, 'pre1' => 3, 'pre2' => 6, 'pre3' => 9, 'pre4' => 12, 'pre5' => 15] as $key => $monthsBack) {
            $start = 0 === $monthsBack ? $currentStart : $currentStart->modify('-'.$monthsBack.' months');
            $end = $start->modify('+3 months')->modify('-1 day');
            $q = (int) ceil(((int) $start->format('n')) / 3);
            $result[$key] = [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'title' => \sprintf($this->trans('Q%s %s'), $q, $start->format('Y')),
            ];
        }

        return $result;
    }

    /**
     * @param array<string, array{start:string,end:string,title:string}> $quarters
     *
     * @return array<int, string>
     */
    private function getQuarterlyHeaders(array $quarters): array
    {
        return [
            '',
            $quarters['pre5']['title'],
            $quarters['pre4']['title'],
            $quarters['pre3']['title'],
            $quarters['pre2']['title'],
            $quarters['pre1']['title'],
            $this->trans('YoY'),
            $quarters['current']['title'].'*',
        ];
    }

    /**
     * @param array<int, string>                       $headers
     * @param array<int, array<int, int|string|float>> $rows
     *
     * @return array<string, mixed>
     */
    private function buildQuarterlyTable(array $headers, array $rows): array
    {
        $columns = [];
        foreach ($headers as $index => $header) {
            $columns[] = ['key' => 'c'.$index, 'label' => $header];
        }
        $items = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($row as $index => $value) {
                $item['c'.$index] = $value;
            }
            $items[] = $item;
        }

        return [
            'columns' => $columns,
            'items' => $items,
            'warning' => $this->trans('*: Current quarter, incomplete data'),
        ];
    }

    private function countUsersUntil(string $dateUntil, bool $current): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(user.id)')
            ->from(User::class, 'user')
        ;
        if (!$current) {
            $until = $this->localDateToUtc($dateUntil, false);
            $queryBuilder
                ->andWhere('user.createdAt <= :until')
                ->setParameter('until', $until, Types::DATETIME_IMMUTABLE)
            ;
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function countConnectedUsers(string $start, string $end): int
    {
        [$from, $until] = $this->dateStringsToUtc($start, $end);
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT COUNT(DISTINCT u.id) FROM user u INNER JOIN track_e_login l ON u.id = l.login_user_id '
            .'WHERE u.active <> :softDeleted AND l.login_date BETWEEN :start AND :end';
        $params = ['softDeleted' => User::SOFT_DELETED, 'start' => $from->format('Y-m-d H:i:s'), 'end' => $until->format('Y-m-d H:i:s')];
        $types = ['softDeleted' => Types::INTEGER, 'start' => Types::STRING, 'end' => Types::STRING];
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql = 'SELECT COUNT(DISTINCT u.id) FROM user u INNER JOIN track_e_login l ON u.id = l.login_user_id '
                .'INNER JOIN access_url_rel_user aur ON u.id = aur.user_id '
                .'WHERE u.active <> :softDeleted AND l.login_date BETWEEN :start AND :end AND aur.access_url_id = :urlId';
            $params['urlId'] = $this->getCurrentAccessUrlId();
            $types['urlId'] = Types::INTEGER;
        }

        return (int) $connection->executeQuery($sql, $params, $types)->fetchOne();
    }

    /**
     * @param array<int, int>|null $visibilities
     */
    private function countCoursesUntil(string $dateUntil, bool $current, ?array $visibilities): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT course.id)')
            ->from(Course::class, 'course')
        ;
        if (!$current) {
            $until = $this->localDateToUtc($dateUntil, false);
            $queryBuilder
                ->andWhere('course.creationDate <= :until')
                ->setParameter('until', $until, Types::DATETIME_IMMUTABLE)
            ;
        }
        if (null !== $visibilities) {
            $queryBuilder
                ->andWhere('course.visibility IN (:visibilities)')
                ->setParameter('visibilities', $visibilities, ArrayParameterType::INTEGER)
            ;
        }
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $queryBuilder
                ->innerJoin('course.urls', 'coursePortal')
                ->andWhere('IDENTITY(coursePortal.url) = :urlId')
                ->setParameter('urlId', $this->getCurrentAccessUrlId(), Types::INTEGER)
            ;
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function getTrainingHours(string $start, string $end): int
    {
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT TIMESTAMPDIFF(HOUR, a.login_course_date, a.logout_course_date) diff FROM track_e_course_access a ';
        $params = ['start' => $start, 'end' => $end];
        $types = ['start' => Types::STRING, 'end' => Types::STRING];
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'INNER JOIN access_url_rel_user aur ON a.user_id = aur.user_id ';
        }
        $sql .= 'WHERE a.login_course_date >= :start AND a.logout_course_date <= :end ';
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'AND aur.access_url_id = :urlId';
            $params['urlId'] = $this->getCurrentAccessUrlId();
            $types['urlId'] = Types::INTEGER;
        }
        $total = 0;
        foreach ($connection->executeQuery($sql, $params, $types)->fetchFirstColumn() as $hours) {
            $total += min(6, max(0, (int) $hours));
        }

        return $total;
    }

    private function countCertificatesUntil(string $dateUntil): int
    {
        $until = $this->localDateToUtc($dateUntil, false);

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(certificate.id)')
            ->from(GradebookCertificate::class, 'certificate')
            ->andWhere('certificate.createdAt <= :until')
            ->setParameter('until', $until, Types::DATETIME_IMMUTABLE)
            ->getQuery()->getSingleScalarResult()
        ;
    }

    /**
     * @return array<int, int>
     */
    private function getSessionsByDuration(string $start, string $end): array
    {
        [$from, $until] = $this->dateStringsToUtc($start, $end);
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT TIMESTAMPDIFF(SECOND, l.login_date, l.logout_date) duration FROM track_e_login l ';
        $params = ['start' => $from->format('Y-m-d H:i:s'), 'end' => $until->format('Y-m-d H:i:s')];
        $types = ['start' => Types::STRING, 'end' => Types::STRING];
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'INNER JOIN access_url_rel_user aur ON aur.user_id = l.login_user_id ';
        }
        $sql .= 'WHERE l.login_date >= :start AND l.logout_date <= :end ';
        if ($this->accessUrlHelper->isMultiple() && null !== $this->getCurrentAccessUrlId()) {
            $sql .= 'AND aur.access_url_id = :urlId';
            $params['urlId'] = $this->getCurrentAccessUrlId();
            $types['urlId'] = Types::INTEGER;
        }
        $result = ['0' => 0, '5' => 0, '10' => 0, '15' => 0, '30' => 0, '60' => 0];
        foreach ($connection->executeQuery($sql, $params, $types)->fetchFirstColumn() as $durationValue) {
            $duration = (int) $durationValue;
            if ($duration > 3600) {
                ++$result['60'];
            } elseif ($duration > 1800) {
                ++$result['30'];
            } elseif ($duration > 900) {
                ++$result['15'];
            } elseif ($duration > 600) {
                ++$result['10'];
            } elseif ($duration > 300) {
                ++$result['5'];
            } else {
                ++$result['0'];
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    private function getCourseCompletionCounts(bool $withSessions): array
    {
        $connection = $this->entityManager->getConnection();
        $urlId = $this->getCurrentAccessUrlId() ?? 1;
        if (!$withSessions) {
            $sql = 'SELECT c.id course_id, c.code, cru.user_id FROM course_rel_user cru '
                .'INNER JOIN course c ON cru.c_id = c.id '
                .'INNER JOIN access_url_rel_user auru ON cru.user_id = auru.user_id '
                .'INNER JOIN access_url_rel_course aurc ON c.id = aurc.c_id '
                .'WHERE aurc.access_url_id = :urlId ORDER BY c.code';
            $rows = $connection->executeQuery($sql, ['urlId' => $urlId], ['urlId' => Types::INTEGER])->fetchAllAssociative();
            $groups = [];
            foreach ($rows as $row) {
                $key = (string) $row['code'];
                $groups[$key] ??= ['courseId' => (int) $row['course_id'], 'sessionId' => null, 'users' => []];
                $groups[$key]['users'][] = (int) $row['user_id'];
            }
        } else {
            $sql = 'SELECT c.id course_id, c.code, srcru.session_id, srcru.user_id, s.title FROM session_rel_course_rel_user srcru '
                .'INNER JOIN course c ON srcru.c_id = c.id '
                .'INNER JOIN access_url_rel_session aurs ON srcru.session_id = aurs.session_id '
                .'INNER JOIN session s ON srcru.session_id = s.id '
                .'WHERE aurs.access_url_id = :urlId ORDER BY c.code, s.title';
            $rows = $connection->executeQuery($sql, ['urlId' => $urlId], ['urlId' => Types::INTEGER])->fetchAllAssociative();
            $groups = [];
            foreach ($rows as $row) {
                $key = (string) $row['code'].' ('.(string) $row['title'].')';
                $groups[$key] ??= [
                    'courseId' => (int) $row['course_id'],
                    'sessionId' => (int) $row['session_id'],
                    'users' => [],
                ];
                $groups[$key]['users'][] = (int) $row['user_id'];
            }
        }

        $items = [];
        foreach ($groups as $label => $group) {
            $gradebookCriteria = ['course' => $group['courseId']];
            if ($withSessions) {
                $gradebookCriteria['session'] = $group['sessionId'];
            }
            $gradebook = $this->entityManager->getRepository(GradebookCategory::class)->findOneBy($gradebookCriteria);
            $finished = 0;
            if ($gradebook instanceof GradebookCategory && null !== $gradebook->getId()) {
                $certificateUserIds = $this->entityManager->createQueryBuilder()
                    ->select('IDENTITY(certificate.user) AS userId')
                    ->from(GradebookCertificate::class, 'certificate')
                    ->andWhere('IDENTITY(certificate.category) = :categoryId')
                    ->setParameter('categoryId', (int) $gradebook->getId(), Types::INTEGER)
                    ->andWhere('IDENTITY(certificate.user) IN (:users)')
                    ->setParameter('users', array_values(array_unique($group['users'])), ArrayParameterType::INTEGER)
                    ->getQuery()->getSingleColumnResult()
                ;
                $certificateUsers = array_fill_keys(array_map(static fn ($userId): int => (int) $userId, $certificateUserIds), true);
                foreach ($group['users'] as $userId) {
                    if (isset($certificateUsers[$userId])) {
                        ++$finished;
                    }
                }
            }
            $items[] = [
                'course' => $label,
                'courseUrl' => $withSessions ? '' : '/main/course_home/course_home.php?cidReq='.rawurlencode($label),
                'subscribed' => \count($group['users']),
                'finished' => $finished,
            ];
        }

        return $items;
    }

    private function incrementPercent(int $current, int $old): string
    {
        if ($old <= 0) {
            return ' - ';
        }

        return ' '.round(100 * (($current / $old) - 1), 2).' %';
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{0:?DateTimeImmutable,1:?DateTimeImmutable}
     */
    private function readDateRange(array $parameters, bool $convertToUtc, bool $defaultToday = false): array
    {
        $startValue = \is_scalar($parameters['rangeStart'] ?? null) ? (string) $parameters['rangeStart'] : '';
        $endValue = \is_scalar($parameters['rangeEnd'] ?? null) ? (string) $parameters['rangeEnd'] : '';
        if ($defaultToday && ('' === $startValue || '' === $endValue)) {
            $today = new DateTimeImmutable('today', $this->getUserTimezone());
            $startValue = $today->format('Y-m-d');
            $endValue = $today->format('Y-m-d');
        }
        $start = $this->parsePlainDate($startValue);
        $end = $this->parsePlainDate($endValue);
        if (null === $start || null === $end || $start > $end) {
            return [null, null];
        }
        if (!$convertToUtc) {
            return [$start, $end];
        }

        return [
            $this->localDateToUtc($startValue, true),
            $this->localDateToUtc($endValue, false),
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{0:?DateTimeImmutable,1:?DateTimeImmutable}
     */
    private function readPlainDateRange(array $parameters): array
    {
        return $this->readDateRange($parameters, false);
    }

    private function parsePlainDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->getUserTimezone());
        if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date;
    }

    private function localDateToUtc(string $value, bool $startOfDay): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->getUserTimezone());
        if (!$date instanceof DateTimeImmutable) {
            throw new BadRequestHttpException('Invalid date.');
        }
        $date = $startOfDay ? $date->setTime(0, 0, 0) : $date->setTime(23, 59, 59);

        return $date->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * @return array{0:DateTimeImmutable,1:DateTimeImmutable}
     */
    private function dateStringsToUtc(string $start, string $end): array
    {
        return [$this->localDateToUtc($start, true), $this->localDateToUtc($end, false)];
    }

    private function toLocalDate(DateTimeImmutable $date): string
    {
        return $date->setTimezone($this->getUserTimezone())->format('Y-m-d');
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

    private function getCurrentAccessUrlId(): ?int
    {
        $url = $this->accessUrlHelper->getCurrent();

        return null !== $url && null !== $url->getId() ? (int) $url->getId() : null;
    }

    private function applyUserAccessUrlScope(QueryBuilder $queryBuilder, string $userAlias, string $portalAlias): void
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return;
        }
        $urlId = $this->getCurrentAccessUrlId();
        if (null === $urlId) {
            return;
        }
        $queryBuilder
            ->innerJoin($userAlias.'.portals', $portalAlias)
            ->andWhere('IDENTITY('.$portalAlias.'.url) = :'.$portalAlias.'UrlId')
            ->setParameter($portalAlias.'UrlId', $urlId, Types::INTEGER)
        ;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyLoginsByDateTable(): array
    {
        return [
            'columns' => [
                ['key' => 'username', 'label' => $this->trans('Username')],
                ['key' => 'firstname', 'label' => $this->trans('First name')],
                ['key' => 'lastname', 'label' => $this->trans('Last name')],
                ['key' => 'totalTime', 'label' => $this->trans('Total time')],
            ],
            'items' => [],
            'totalItems' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyUsersActiveTable(): array
    {
        return [
            'columns' => [
                ['key' => 'firstname', 'label' => $this->trans('First name')],
                ['key' => 'lastname', 'label' => $this->trans('Last name')],
                ['key' => 'registrationDate', 'label' => $this->trans('Registration date')],
                ['key' => 'nativeLanguage', 'label' => $this->trans('Native language')],
                ['key' => 'targetLanguage', 'label' => $this->trans('Users by target language')],
                ['key' => 'contract', 'label' => $this->trans('Apprenticeship contract')],
                ['key' => 'residence', 'label' => $this->trans('Country of residence')],
                ['key' => 'career', 'label' => $this->trans('Career')],
                ['key' => 'status', 'label' => $this->trans('Status')],
                ['key' => 'active', 'label' => $this->trans('Active')],
                ['key' => 'certificate', 'label' => $this->trans('Certificate')],
                ['key' => 'birthday', 'label' => $this->trans('Birthday')],
            ],
            'items' => [],
            'totalItems' => 0,
        ];
    }

    private function formatSeconds(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;

        return \sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining);
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
                'datasets' => [['label' => $label, 'data' => array_values($values)]],
            ],
        ];
    }

    /**
     * @param array<string, int|float> $values
     *
     * @return array<int, array{label:string,value:int|float}>
     */
    private function buildStats(array $values): array
    {
        $stats = [];
        foreach ($values as $label => $value) {
            $stats[] = ['label' => (string) $label, 'value' => $value];
        }

        return $stats;
    }

    private function normalizePositiveInt(mixed $value, int $default, int $minimum, int $maximum): int
    {
        if (!\is_scalar($value)) {
            return $default;
        }
        $number = (int) $value;

        return $number < $minimum || $number > $maximum ? $default : $number;
    }

    /**
     * @param array<int, int> $allowed
     */
    private function normalizeAllowedInt(mixed $value, array $allowed, int $default): int
    {
        if (!\is_scalar($value)) {
            return $default;
        }
        $number = (int) $value;

        return \in_array($number, $allowed, true) ? $number : $default;
    }

    private function trans(string $message): string
    {
        return $this->translator->trans($message);
    }
}
