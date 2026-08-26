<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\AdminStatistics;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

use const PHP_INT_MAX;

final readonly class AdminStatisticsMaintenanceQueryService
{
    private const SUPPORTED_REPORTS = [
        'zombies',
        'duplicated_users',
    ];

    private const ZOMBIE_SORT_FIELDS = [
        'id' => 'u.id',
        'officialCode' => 'u.official_code',
        'firstname' => 'u.firstname',
        'lastname' => 'u.lastname',
        'username' => 'u.username',
        'email' => 'u.email',
        'status' => 'u.status',
        'registeredDate' => 'u.created_at',
        'active' => 'u.active',
        'lastAccess' => 'access.login_date',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
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
        return match ($report) {
            'zombies' => $this->getZombiesReport($parameters),
            'duplicated_users' => $this->getDuplicateUsersReport($parameters),
            default => throw new NotFoundHttpException('This statistics report has not been migrated.'),
        };
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{columns: array<int, array<string, string>>, items: array<int, array<string, mixed>>}
     */
    public function getExportData(string $report, array $parameters): array
    {
        if ('duplicated_users' !== $report) {
            throw new BadRequestHttpException('This report is not exportable.');
        }

        $data = $this->getDuplicateUsersReport($parameters);
        $table = \is_array($data['table'] ?? null) ? $data['table'] : [];
        $columns = \is_array($table['columns'] ?? null) ? array_values($table['columns']) : [];
        $columns = array_values(array_filter(
            $columns,
            static fn (array $column): bool => 'actions' !== (string) ($column['key'] ?? '')
        ));

        return [
            'columns' => $columns,
            'items' => \is_array($table['items'] ?? null) ? array_values($table['items']) : [],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getZombiesReport(array $parameters): array
    {
        $ceiling = $this->parseDate((string) ($parameters['ceiling'] ?? '')) ?? new DateTimeImmutable('today');
        $activeOnly = $this->toBoolean($parameters['activeOnly'] ?? $parameters['active_only'] ?? false);
        $page = $this->normalizePositiveInt($parameters['page'] ?? 1, 1, 1, PHP_INT_MAX);
        $itemsPerPage = $this->normalizePositiveInt($parameters['itemsPerPage'] ?? 50, 50, 5, 200);
        $sortField = (string) ($parameters['sortField'] ?? 'firstname');
        $sortField = isset(self::ZOMBIE_SORT_FIELDS[$sortField]) ? $sortField : 'firstname';
        $sortOrder = 'ASC' === strtoupper((string) ($parameters['sortOrder'] ?? 'DESC')) ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $itemsPerPage;

        $connection = $this->entityManager->getConnection();
        [$joinUrl, $urlCondition, $urlParameters, $urlTypes] = $this->getUserAccessUrlSql('u', 'url');
        $where = 'access.login_date = (SELECT MAX(a.login_date) FROM track_e_login a WHERE a.login_user_id = u.id) '
            .'AND access.login_date <= :ceiling AND u.id = access.login_user_id '
            .$urlCondition.' AND u.active <> :softDeleted';
        if ($activeOnly) {
            $where .= ' AND u.active = :active';
        }

        $sqlParameters = [
            ...$urlParameters,
            'ceiling' => $ceiling->format('Y-m-d').' 00:00:00',
            'softDeleted' => User::SOFT_DELETED,
        ];
        $sqlTypes = [
            ...$urlTypes,
            'ceiling' => Types::STRING,
            'softDeleted' => Types::INTEGER,
        ];
        if ($activeOnly) {
            $sqlParameters['active'] = User::ACTIVE;
            $sqlTypes['active'] = Types::INTEGER;
        }

        $countSql = 'SELECT COUNT(*) FROM user u INNER JOIN track_e_login access ON access.login_user_id = u.id '
            .$joinUrl.' WHERE '.$where;
        $totalItems = (int) $connection->fetchOne($countSql, $sqlParameters, $sqlTypes);

        $dataSql = 'SELECT u.id, u.official_code, u.firstname, u.lastname, u.username, u.email, u.status, '
            .'u.created_at, u.active, access.login_date '
            .'FROM user u INNER JOIN track_e_login access ON access.login_user_id = u.id '
            .$joinUrl.' WHERE '.$where.' ORDER BY '.self::ZOMBIE_SORT_FIELDS[$sortField].' '.$sortOrder
            .' LIMIT '.$offset.', '.$itemsPerPage;
        $rows = $connection->executeQuery($dataSql, $sqlParameters, $sqlTypes)->fetchAllAssociative();

        $usersById = [];
        $ids = array_values(array_filter(array_map(static fn (array $row): int => (int) $row['id'], $rows)));
        if ([] !== $ids) {
            $users = $this->entityManager->createQueryBuilder()
                ->select('u')
                ->from(User::class, 'u')
                ->andWhere('u.id IN (:ids)')
                ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
                ->getQuery()
                ->getResult()
            ;
            foreach ($users as $user) {
                if ($user instanceof User && null !== $user->getId()) {
                    $usersById[(int) $user->getId()] = $user;
                }
            }
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        $items = [];
        foreach ($rows as $row) {
            $userId = (int) $row['id'];
            $authSources = [];
            $user = $usersById[$userId] ?? null;
            if ($user instanceof User) {
                $authSources = $user->getAuthSourcesAuthentications($accessUrl);
            }

            $items[] = [
                'id' => $userId,
                'officialCode' => (string) ($row['official_code'] ?? ''),
                'firstname' => (string) ($row['firstname'] ?? ''),
                'lastname' => (string) ($row['lastname'] ?? ''),
                'username' => (string) ($row['username'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'profile' => $this->getUserStatusLabel((int) $row['status']),
                'authenticationSource' => implode(', ', $authSources),
                'registeredDate' => (string) ($row['created_at'] ?? ''),
                'lastAccess' => (string) ($row['login_date'] ?? ''),
                'active' => (int) $row['active'],
                'activeLabel' => User::ACTIVE === (int) $row['active'] ? $this->trans('Yes') : $this->trans('No'),
            ];
        }

        return [
            'title' => $this->trans('Zombies'),
            'description' => $this->trans('Users whose latest access is older than the selected date.'),
            'filters' => [
                'ceiling' => $ceiling->format('Y-m-d'),
                'activeOnly' => $activeOnly,
                'sortField' => $sortField,
                'sortOrder' => $sortOrder,
            ],
            'table' => [
                'columns' => [
                    ['key' => 'officialCode', 'label' => $this->trans('Code')],
                    ['key' => 'firstname', 'label' => $this->trans('First name')],
                    ['key' => 'lastname', 'label' => $this->trans('Last name')],
                    ['key' => 'username', 'label' => $this->trans('Login')],
                    ['key' => 'email', 'label' => $this->trans('E-mail')],
                    ['key' => 'profile', 'label' => $this->trans('Profile')],
                    ['key' => 'authenticationSource', 'label' => $this->trans('Authentication source')],
                    ['key' => 'registeredDate', 'label' => $this->trans('Registered date')],
                    ['key' => 'lastAccess', 'label' => $this->trans('Latest access')],
                    ['key' => 'activeLabel', 'label' => $this->trans('active')],
                ],
                'items' => $items,
                'totalItems' => $totalItems,
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'lazy' => true,
            ],
            'meta' => [
                'maintenanceActions' => ['activate', 'deactivate', 'delete'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function getDuplicateUsersReport(array $parameters): array
    {
        $dupMode = (string) ($parameters['dupMode'] ?? $parameters['dup_mode'] ?? 'name');
        if (!\in_array($dupMode, ['name', 'email', 'extra'], true)) {
            $dupMode = 'name';
        }

        $extraFieldId = max(0, (int) ($parameters['extraFieldId'] ?? $parameters['extra_field_id'] ?? 0));
        $additionalFieldIds = $this->normalizeIntList(
            $parameters['additionalProfileFields'] ?? $parameters['additional_profile_field'] ?? []
        );
        $extraFields = $this->getUserExtraFields();
        $extraFieldsById = [];
        foreach ($extraFields as $field) {
            $extraFieldsById[(int) $field['id']] = $field;
        }

        if ('extra' === $dupMode && (0 === $extraFieldId || !isset($extraFieldsById[$extraFieldId]))) {
            $extraFieldId = 0;
        }
        $additionalFieldIds = array_values(array_filter(
            $additionalFieldIds,
            static fn (int $id): bool => isset($extraFieldsById[$id])
        ));

        $extraFieldIdsToLoad = [];
        if ('extra' === $dupMode && $extraFieldId > 0) {
            $extraFieldIdsToLoad[] = $extraFieldId;
        }
        foreach ($additionalFieldIds as $fieldId) {
            if (!\in_array($fieldId, $extraFieldIdsToLoad, true)) {
                $extraFieldIdsToLoad[] = $fieldId;
            }
        }

        $duplicateUsers = $this->queryDuplicateUsers($dupMode, $extraFieldId);
        $userIds = array_values(array_unique(array_map(static fn (array $row): int => (int) $row['id'], $duplicateUsers)));
        $courseCounts = $this->getDuplicateRelationCounts('course_rel_user', $userIds);
        $sessionCounts = $this->getDuplicateRelationCounts('session_rel_user', $userIds);
        $extraValues = $this->getExtraFieldValues($userIds, $extraFieldIdsToLoad);

        $columns = [
            ['key' => 'id', 'label' => $this->trans('Id')],
            ['key' => 'firstname', 'label' => $this->trans('Firstname')],
            ['key' => 'lastname', 'label' => $this->trans('Lastname')],
            ['key' => 'email', 'label' => $this->trans('Email')],
            ['key' => 'registrationDate', 'label' => $this->trans('Registration date')],
            ['key' => 'firstLogin', 'label' => $this->trans('First login in platform')],
            ['key' => 'lastLogin', 'label' => $this->trans('Last login in platform')],
            ['key' => 'role', 'label' => $this->trans('Role')],
            ['key' => 'courses', 'label' => $this->trans('Courses')],
            ['key' => 'sessions', 'label' => $this->trans('Sessions')],
        ];
        foreach ($extraFieldIdsToLoad as $fieldId) {
            $field = $extraFieldsById[$fieldId];
            $columns[] = [
                'key' => 'extra_'.$fieldId,
                'label' => (string) ($field['label'] ?? $field['variable'] ?? 'Extra field #'.$fieldId),
            ];
        }
        $columns[] = ['key' => 'activeLabel', 'label' => $this->trans('Active')];
        $columns[] = ['key' => 'actions', 'label' => $this->trans('Actions')];

        $groups = [];
        foreach ($duplicateUsers as $row) {
            $groupLabel = trim((string) ($row['duplicate_label'] ?? ''));
            if ('' === $groupLabel) {
                $groupLabel = $this->trans('Not available');
            }
            $groupKey = $dupMode.':'.$groupLabel;
            $groups[$groupKey] ??= ['key' => $groupKey, 'label' => $groupLabel, 'items' => []];

            $userId = (int) $row['id'];
            $item = [
                'id' => $userId,
                'firstname' => (string) ($row['firstname'] ?? ''),
                'lastname' => (string) ($row['lastname'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'registrationDate' => (string) ($row['registration_date'] ?? ''),
                'firstLogin' => (string) ($row['first_login'] ?? ''),
                'lastLogin' => (string) ($row['last_login'] ?? ''),
                'role' => $this->getDuplicateUserRoleLabel((int) ($row['status'] ?? 0)),
                'courses' => $courseCounts[$userId] ?? 0,
                'sessions' => $sessionCounts[$userId] ?? 0,
                'active' => (int) ($row['active'] ?? 0),
                'activeLabel' => $this->getDuplicateActiveLabel((int) ($row['active'] ?? 0)),
                'detailsUrl' => '/main/admin/user_information.php?user_id='.$userId,
                'actions' => '',
            ];
            foreach ($extraFieldIdsToLoad as $fieldId) {
                $item['extra_'.$fieldId] = $extraValues[$userId][$fieldId] ?? '';
            }
            $groups[$groupKey]['items'][] = $item;
        }

        $items = [];
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $items[] = $item;
            }
        }

        $options = array_map(
            static fn (array $field): array => [
                'value' => (int) $field['id'],
                'label' => (string) $field['label'],
            ],
            $extraFields
        );

        return [
            'title' => $this->trans('Duplicate users'),
            'description' => $this->trans('Duplicate user detection and maintenance report.'),
            'filters' => [
                'dupMode' => $dupMode,
                'extraFieldId' => $extraFieldId,
                'extraFieldOptions' => $options,
                'additionalProfileFields' => $additionalFieldIds,
            ],
            'table' => [
                'columns' => $columns,
                'items' => $items,
                'totalItems' => \count($items),
                'lazy' => false,
            ],
            'meta' => [
                'duplicateGroups' => array_values($groups),
                'duplicateMode' => $dupMode,
                'canExportCsv' => true,
                'canExportXls' => true,
                'exportReport' => 'duplicated_users',
            ],
        ];
    }

    /**
     * @return array<int, array{id: int, variable: string, label: string}>
     */
    private function getUserExtraFields(): array
    {
        $rows = $this->entityManager->getConnection()->executeQuery(
            'SELECT id, variable, display_text FROM extra_field WHERE item_type = :itemType ORDER BY display_text, variable, id',
            ['itemType' => ExtraField::USER_FIELD_TYPE],
            ['itemType' => Types::INTEGER]
        )->fetchAllAssociative();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'variable' => (string) $row['variable'],
                'label' => '' !== trim((string) ($row['display_text'] ?? ''))
                    ? (string) $row['display_text']
                    : (string) $row['variable'],
            ],
            $rows
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function queryDuplicateUsers(string $dupMode, int $extraFieldId): array
    {
        $connection = $this->entityManager->getConnection();
        $params = ['softDeleted' => User::SOFT_DELETED];
        $types = ['softDeleted' => Types::INTEGER];

        if ('name' === $dupMode) {
            $sql = <<<'SQL'
SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.status, u.active,
       u.created_at AS registration_date, NULL AS first_login, u.last_login,
       LOWER(TRIM(COALESCE(u.firstname, ''))) AS dup_firstname_norm,
       LOWER(TRIM(COALESCE(u.lastname, ''))) AS dup_lastname_norm,
       CONCAT(TRIM(COALESCE(u.firstname, '')), ' ', TRIM(COALESCE(u.lastname, ''))) AS duplicate_label
FROM user u
INNER JOIN (
    SELECT LOWER(TRIM(COALESCE(firstname, ''))) AS d_firstname,
           LOWER(TRIM(COALESCE(lastname, ''))) AS d_lastname
    FROM user
    WHERE active <> :softDeleted
      AND (TRIM(COALESCE(firstname, '')) <> '' OR TRIM(COALESCE(lastname, '')) <> '')
    GROUP BY LOWER(TRIM(COALESCE(firstname, ''))), LOWER(TRIM(COALESCE(lastname, '')))
    HAVING COUNT(*) > 1
) d ON d.d_firstname = LOWER(TRIM(COALESCE(u.firstname, '')))
   AND d.d_lastname = LOWER(TRIM(COALESCE(u.lastname, '')))
WHERE u.active <> :softDeleted
ORDER BY duplicate_label ASC, u.created_at ASC, u.id ASC
SQL;
        } elseif ('email' === $dupMode) {
            $sql = <<<'SQL'
SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.status, u.active,
       u.created_at AS registration_date, NULL AS first_login, u.last_login,
       LOWER(TRIM(COALESCE(u.email, ''))) AS dup_email_norm,
       TRIM(COALESCE(u.email, '')) AS duplicate_label
FROM user u
INNER JOIN (
    SELECT LOWER(TRIM(COALESCE(email, ''))) AS d_email
    FROM user
    WHERE active <> :softDeleted AND TRIM(COALESCE(email, '')) <> ''
    GROUP BY LOWER(TRIM(COALESCE(email, '')))
    HAVING COUNT(*) > 1
) d ON d.d_email = LOWER(TRIM(COALESCE(u.email, '')))
WHERE u.active <> :softDeleted
ORDER BY duplicate_label ASC, u.created_at ASC, u.id ASC
SQL;
        } else {
            if ($extraFieldId <= 0) {
                return [];
            }
            $sql = <<<'SQL'
SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.status, u.active,
       u.created_at AS registration_date, NULL AS first_login, u.last_login,
       LOWER(TRIM(COALESCE(efv.field_value, ''))) AS dup_extra_norm,
       TRIM(COALESCE(efv.field_value, '')) AS duplicate_label
FROM user u
INNER JOIN extra_field_values efv ON efv.item_id = u.id AND efv.field_id = :extraFieldId
INNER JOIN (
    SELECT LOWER(TRIM(COALESCE(efv2.field_value, ''))) AS d_value
    FROM extra_field_values efv2
    INNER JOIN user u2 ON u2.id = efv2.item_id
    WHERE efv2.field_id = :extraFieldId
      AND u2.active <> :softDeleted
      AND TRIM(COALESCE(efv2.field_value, '')) <> ''
    GROUP BY LOWER(TRIM(COALESCE(efv2.field_value, '')))
    HAVING COUNT(*) > 1
) d ON d.d_value = LOWER(TRIM(COALESCE(efv.field_value, '')))
WHERE u.active <> :softDeleted
ORDER BY duplicate_label ASC, u.created_at ASC, u.id ASC
SQL;
            $params['extraFieldId'] = $extraFieldId;
            $types['extraFieldId'] = Types::INTEGER;
        }

        return $connection->executeQuery($sql, $params, $types)->fetchAllAssociative();
    }

    /**
     * @param int[] $userIds
     *
     * @return array<int, int>
     */
    private function getDuplicateRelationCounts(string $table, array $userIds): array
    {
        if ([] === $userIds || !\in_array($table, ['course_rel_user', 'session_rel_user'], true)) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->executeQuery(
            'SELECT user_id, COUNT(*) AS qty FROM '.$table.' WHERE user_id IN (:ids) GROUP BY user_id',
            ['ids' => $userIds],
            ['ids' => ArrayParameterType::INTEGER],
        )->fetchAllAssociative();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['user_id']] = (int) $row['qty'];
        }

        return $counts;
    }

    /**
     * @param int[] $userIds
     * @param int[] $fieldIds
     *
     * @return array<int, array<int, string>>
     */
    private function getExtraFieldValues(array $userIds, array $fieldIds): array
    {
        if ([] === $userIds || [] === $fieldIds) {
            return [];
        }

        $rows = $this->entityManager->getConnection()->executeQuery(
            'SELECT item_id, field_id, field_value FROM extra_field_values '
            .'WHERE item_id IN (:userIds) AND field_id IN (:fieldIds)',
            ['userIds' => $userIds, 'fieldIds' => $fieldIds],
            ['userIds' => ArrayParameterType::INTEGER, 'fieldIds' => ArrayParameterType::INTEGER]
        )->fetchAllAssociative();

        $values = [];
        foreach ($rows as $row) {
            $values[(int) $row['item_id']][(int) $row['field_id']] = (string) ($row['field_value'] ?? '');
        }

        return $values;
    }

    /**
     * @return array{0: string, 1: string, 2: array<string, int>, 3: array<string, string>}
     */
    private function getUserAccessUrlSql(string $userAlias, string $urlAlias): array
    {
        if (!$this->accessUrlHelper->isMultiple()) {
            return ['', '', [], []];
        }

        $current = $this->accessUrlHelper->getCurrent();
        if (null === $current || null === $current->getId()) {
            return ['', '', [], []];
        }

        return [
            ' INNER JOIN access_url_rel_user '.$urlAlias.' ON '.$urlAlias.'.user_id = '.$userAlias.'.id ',
            ' AND '.$urlAlias.'.access_url_id = :accessUrlId ',
            ['accessUrlId' => (int) $current->getId()],
            ['accessUrlId' => Types::INTEGER],
        ];
    }

    private function getUserStatusLabel(int $status): string
    {
        return match ($status) {
            1 => $this->trans('Teacher'),
            3 => $this->trans('Sessions administrator'),
            4 => $this->trans('Human Resources Manager'),
            5 => $this->trans('Learner'),
            6 => $this->trans('Anonymous'),
            17 => $this->trans('Student boss'),
            20 => $this->trans('Invited'),
            default => (string) $status,
        };
    }

    private function getDuplicateUserRoleLabel(int $status): string
    {
        return match ($status) {
            1 => $this->trans('Teacher'),
            3 => $this->trans('SessionAdmin'),
            4 => $this->trans('Drh'),
            5 => $this->trans('Student'),
            6 => $this->trans('Anonymous'),
            7 => $this->trans('Invited'),
            default => (string) $status,
        };
    }

    private function getDuplicateActiveLabel(int $active): string
    {
        return match ($active) {
            1 => $this->trans('Active'),
            0 => $this->trans('Inactive'),
            -1 => 'Soft deleted',
            default => (string) $active,
        };
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        if ('' === trim($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof DateTimeImmutable ? $date : null;
    }

    private function toBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return int[]
     */
    private function normalizeIntList(mixed $value): array
    {
        $values = \is_array($value) ? $value : (preg_split('/[;,]/', (string) $value) ?: []);

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $item): int => (int) $item, $values),
            static fn (int $item): bool => $item > 0
        )));
    }

    private function normalizePositiveInt(mixed $value, int $default, int $minimum, int $maximum): int
    {
        $number = (int) $value;
        if ($number < $minimum || $number > $maximum) {
            return $default;
        }

        return $number;
    }

    private function trans(string $message): string
    {
        return $this->translator->trans($message);
    }
}
