<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Diagnoser;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use function opcache_get_status;

use const DATE_ATOM;
use const PHP_SESSION_ACTIVE;

#[IsGranted('ROLE_ADMIN')]
final class SystemStatusController extends AbstractController
{
    /**
     * @var array<string, array{label: string, info: string, icon: string}>
     */
    private const array SECTIONS = [
        'chamilo' => [
            'label' => 'Chamilo',
            'info' => 'State of Chamilo requirements',
            'icon' => 'mdi-cog-outline',
        ],
        'php' => [
            'label' => 'PHP',
            'info' => 'State of PHP settings on the server',
            'icon' => 'mdi-language-php',
        ],
        'database' => [
            'label' => 'Database',
            'info' => 'Database server configuration and metadata',
            'icon' => 'mdi-database',
        ],
        'webserver' => [
            'label' => 'Web server',
            'info' => 'Information about your webserver configuration',
            'icon' => 'mdi-server',
        ],
        'paths' => [
            'label' => 'Paths',
            'info' => 'api_get_path() constants resolved on this portal',
            'icon' => 'mdi-folder-outline',
        ],
        'courses_space' => [
            'label' => 'Courses space',
            'info' => 'Disk usage per course vs disk quota',
            'icon' => 'mdi-folder-cog-outline',
        ],
    ];

    /**
     * Status keys exposed to the admin UI (hardcoded allowlist).
     * Full SHOW GLOBAL STATUS is fetched then filtered in PHP — no dynamic WHERE.
     *
     * @var list<string>
     */
    private const array DB_STATUS_KEYS = [
        'Aborted_connects',
        'Created_tmp_disk_tables',
        'Created_tmp_tables',
        'Innodb_buffer_pool_read_requests',
        'Innodb_buffer_pool_reads',
        'Innodb_row_lock_waits',
        'Opened_tables',
        'Qcache_hits',
        'Qcache_inserts',
        'Queries',
        'Questions',
        'Slow_queries',
        'Table_locks_immediate',
        'Table_locks_waited',
        'Threads_cached',
        'Threads_connected',
        'Threads_running',
        'Uptime',
    ];

    /**
     * @var list<string>
     */
    private const array DB_VARIABLE_KEYS = [
        'long_query_time',
        'max_connections',
        'query_cache_size',
        'query_cache_type',
        'slow_query_log',
        'version',
    ];

    /**
     * Localhost-only Apache mod_status paths (tried in order).
     *
     * @var list<string>
     */
    private const array WEBSERVER_APACHE_STATUS_PATHS = [
        '/server-status?auto',
    ];

    /**
     * Localhost-only Nginx stub_status paths (tried in order).
     *
     * @var list<string>
     */
    private const array WEBSERVER_NGINX_STATUS_PATHS = [
        '/nginx_status',
        '/stub_status',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settingsManager,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/admin/system-status-data', name: 'admin_system_status_data', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        // Diagnostics are read-only; release the session lock early for concurrent admin tabs.
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        $section = trim((string) $request->query->get('section', 'chamilo'));
        if (!isset(self::SECTIONS[$section])) {
            $section = 'chamilo';
        }

        $sections = [];
        foreach (self::SECTIONS as $key => $meta) {
            $sections[] = [
                'key' => $key,
                'label' => $this->translator->trans($meta['label']),
                'info' => $this->translator->trans($meta['info']),
                'icon' => $meta['icon'],
            ];
        }

        if ('courses_space' === $section) {
            $rowType = 'coursesSpace';
            $rows = $this->getCoursesSpaceRows();
        } elseif ('paths' === $section) {
            $rowType = 'paths';
            $rows = $this->getPathsRows();
        } else {
            $rowType = 'generic';
            $rows = $this->getGenericRows($section);
        }

        return $this->json([
            'sections' => $sections,
            'currentSection' => $section,
            'rowType' => $rowType,
            'rows' => $rows,
        ]);
    }

    /**
     * Lightweight, read-only OPcache + APCu diagnostics for the PHP section.
     *
     * Intentionally exposes only aggregate counters/memory figures — no script
     * lists, no cache keys, no reset/invalidate actions (no attack surface).
     */
    #[Route('/admin/system-status-cache-data', name: 'admin_system_status_cache_data', methods: ['GET'])]
    public function cacheData(): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        return $this->json([
            'fetchedAt' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
            'opcache' => $this->getOpcacheStats(),
            'apcu' => $this->getApcuStats(),
        ]);
    }

    /**
     * Lightweight, read-only MySQL/MariaDB load metrics for the Database section.
     *
     * Uses SHOW GLOBAL STATUS / VARIABLES only (no PROCESS / performance_schema).
     * Rates (QPS, etc.) are computed client-side by diffing consecutive polls.
     * Privilege scope is derived server-side — raw SHOW GRANTS strings are never returned
     * (they can contain password hashes).
     */
    #[Route('/admin/system-status-database-data', name: 'admin_system_status_database_data', methods: ['GET'])]
    public function databaseData(): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        try {
            $server = $this->getDatabaseServerStats();
        } catch (Throwable) {
            $server = $this->emptyDatabaseServerStats('status_unavailable');
        }

        try {
            $privileges = $this->getDatabasePrivilegeScope();
        } catch (Throwable) {
            $privileges = [
                'scope' => 'database',
                'hasGlobalPrivileges' => false,
                'resolved' => false,
                'unavailable' => $this->databasePrivilegeUnavailableCapabilities(),
            ];
        }

        return $this->json([
            'fetchedAt' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
            'server' => $server,
            'privileges' => $privileges,
        ]);
    }

    /**
     * Lightweight Apache/Nginx load metrics for the Web server section.
     *
     * Requires the engine status module to answer on localhost (no configurable URL —
     * fixed paths only). Detects Apache vs Nginx from SERVER_SOFTWARE.
     * Cumulative counters are returned so the UI can compute live rates between polls.
     */
    #[Route('/admin/system-status-webserver-data', name: 'admin_system_status_webserver_data', methods: ['GET'])]
    public function webserverData(Request $request): JsonResponse
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_write_close();
        }

        try {
            $payload = $this->getWebserverLoadStats($request);
        } catch (Throwable) {
            $payload = $this->emptyWebserverLoadStats(
                detected: null,
                software: $this->readServerSoftware($request),
                scannedPaths: [],
                reason: 'status_unavailable',
            );
        }

        return $this->json([
            'fetchedAt' => (new DateTimeImmutable('now'))->format(DATE_ATOM),
            ...$payload,
        ]);
    }

    /**
     * @return array{
     *     available: bool,
     *     reason: string|null,
     *     version: string|null,
     *     counters: array<string, int|null>,
     *     variables: array<string, string|null>,
     *     derived: array{
     *         bufferPoolHitRatePercent: float|null,
     *         tmpTablesOnDiskPercent: float|null,
     *         threadsConnectedPercent: float|null,
     *         tableLockWaitPercent: float|null
     *     },
     *     queryCache: array{available: bool},
     *     slowQueries: array{
     *         count: int|null,
     *         longQueryTime: string|null,
     *         slowQueryLog: string|null
     *     }
     * }
     */
    private function emptyDatabaseServerStats(?string $reason = null): array
    {
        $emptyCounters = [];
        foreach (self::DB_STATUS_KEYS as $key) {
            $emptyCounters[$key] = null;
        }

        $emptyVariables = [];
        foreach (self::DB_VARIABLE_KEYS as $key) {
            $emptyVariables[$key] = null;
        }

        return [
            'available' => false,
            'reason' => $reason,
            'version' => null,
            'counters' => $emptyCounters,
            'variables' => $emptyVariables,
            'derived' => [
                'bufferPoolHitRatePercent' => null,
                'tmpTablesOnDiskPercent' => null,
                'threadsConnectedPercent' => null,
                'tableLockWaitPercent' => null,
            ],
            'queryCache' => [
                'available' => false,
            ],
            'slowQueries' => [
                'count' => null,
                'longQueryTime' => null,
                'slowQueryLog' => null,
            ],
        ];
    }

    /**
     * Aggregate MySQL/MariaDB server counters + derived ratios.
     *
     * Each STATUS / VARIABLES key is optional: missing or unreadable values become
     * null so the UI can show "—" without failing the whole panel.
     *
     * @return array{
     *     available: bool,
     *     reason: string|null,
     *     version: string|null,
     *     counters: array<string, int|null>,
     *     variables: array<string, string|null>,
     *     derived: array{
     *         bufferPoolHitRatePercent: float|null,
     *         tmpTablesOnDiskPercent: float|null,
     *         threadsConnectedPercent: float|null,
     *         tableLockWaitPercent: float|null
     *     },
     *     queryCache: array{available: bool},
     *     slowQueries: array{
     *         count: int|null,
     *         longQueryTime: string|null,
     *         slowQueryLog: string|null
     *     }
     * }
     */
    private function getDatabaseServerStats(): array
    {
        try {
            $connection = $this->em->getConnection();
            $platform = $connection->getDatabasePlatform();
            $driver = strtolower(str_replace('Platform', '', (new ReflectionClass($platform))->getShortName()));
        } catch (Throwable) {
            return $this->emptyDatabaseServerStats('platform_unknown');
        }

        // Only MySQL / MariaDB expose SHOW GLOBAL STATUS with these counters.
        if (!str_contains($driver, 'mysql') && !str_contains($driver, 'mariadb')) {
            return $this->emptyDatabaseServerStats('unsupported_platform');
        }

        // STATUS and VARIABLES are independent: one can fail without blanking the other.
        $statusRows = [];
        $variableRows = [];
        $statusOk = false;
        $variablesOk = false;

        try {
            $statusRaw = $connection->fetchAllKeyValue('SHOW GLOBAL STATUS');
            if (\is_array($statusRaw)) {
                $statusRows = $statusRaw;
                $statusOk = [] !== $statusRows;
            }
        } catch (Throwable) {
            $statusOk = false;
        }

        try {
            $variableRaw = $connection->fetchAllKeyValue('SHOW GLOBAL VARIABLES');
            if (\is_array($variableRaw)) {
                $variableRows = $variableRaw;
                $variablesOk = [] !== $variableRows;
            }
        } catch (Throwable) {
            $variablesOk = false;
        }

        if (!$statusOk && !$variablesOk) {
            return $this->emptyDatabaseServerStats('status_unavailable');
        }

        $counters = [];
        foreach (self::DB_STATUS_KEYS as $key) {
            if ($statusOk && \array_key_exists($key, $statusRows) && is_numeric($statusRows[$key])) {
                $counters[$key] = (int) $statusRows[$key];
            } else {
                $counters[$key] = null;
            }
        }

        $variables = [];
        foreach (self::DB_VARIABLE_KEYS as $key) {
            if ($variablesOk && \array_key_exists($key, $variableRows) && null !== $variableRows[$key]) {
                $variables[$key] = (string) $variableRows[$key];
            } else {
                $variables[$key] = null;
            }
        }

        // Version: prefer GLOBAL VARIABLES, then DBAL/server fallbacks — never fail hard.
        $version = $variables['version'] ?? null;
        if (null === $version || '' === $version) {
            try {
                $versionCandidate = $connection->fetchOne('SELECT VERSION()');
                if (\is_string($versionCandidate) && '' !== $versionCandidate) {
                    $version = $versionCandidate;
                    $variables['version'] = $version;
                }
            } catch (Throwable) {
                // Connection::getServerVersion() used to be the fallback here,
                // but it is private in DBAL 3 (method_exists() reports true for
                // private methods, so the call only ever raised an Error that
                // the catch turned back into null). There is no public API left
                // to ask the driver, and SELECT VERSION() only fails when the
                // connection itself is down.
                $version = null;
            }
        }

        $bufferPoolHitRate = null;
        $readRequests = $counters['Innodb_buffer_pool_read_requests'];
        $reads = $counters['Innodb_buffer_pool_reads'];
        if (null !== $readRequests && null !== $reads && $readRequests > 0) {
            $bufferPoolHitRate = round(100 * (1 - ($reads / $readRequests)), 3);
            if ($bufferPoolHitRate < 0.0) {
                $bufferPoolHitRate = 0.0;
            }
            if ($bufferPoolHitRate > 100.0) {
                $bufferPoolHitRate = 100.0;
            }
        }

        $tmpOnDiskPercent = null;
        $tmpTables = $counters['Created_tmp_tables'];
        $tmpDisk = $counters['Created_tmp_disk_tables'];
        if (null !== $tmpTables && null !== $tmpDisk && $tmpTables > 0) {
            $tmpOnDiskPercent = round(100 * ($tmpDisk / $tmpTables), 2);
        }

        $threadsConnectedPercent = null;
        $threadsConnected = $counters['Threads_connected'];
        $maxConnections = isset($variables['max_connections']) && is_numeric($variables['max_connections'])
            ? (int) $variables['max_connections']
            : null;
        if (null !== $threadsConnected && null !== $maxConnections && $maxConnections > 0) {
            $threadsConnectedPercent = round(100 * ($threadsConnected / $maxConnections), 2);
        }

        $tableLockWaitPercent = null;
        $locksWaited = $counters['Table_locks_waited'];
        $locksImmediate = $counters['Table_locks_immediate'];
        if (null !== $locksWaited && null !== $locksImmediate) {
            $lockTotal = $locksWaited + $locksImmediate;
            if ($lockTotal > 0) {
                $tableLockWaitPercent = round(100 * ($locksWaited / $lockTotal), 3);
            }
        }

        // Query cache may be absent (MySQL 8 removed it) or off — hide the block unless enabled.
        $queryCacheTypeRaw = $variables['query_cache_type'] ?? null;
        $queryCacheType = null !== $queryCacheTypeRaw ? strtoupper(trim($queryCacheTypeRaw)) : '';
        $queryCacheAvailable = '' !== $queryCacheType
            && 'OFF' !== $queryCacheType
            && '0' !== $queryCacheType;

        // Panel is usable if we got STATUS (primary), or at least variables/version.
        $available = $statusOk || null !== $version;

        return [
            'available' => $available,
            'reason' => $available ? null : 'status_unavailable',
            'version' => $version,
            'counters' => $counters,
            'variables' => $variables,
            'derived' => [
                'bufferPoolHitRatePercent' => $bufferPoolHitRate,
                'tmpTablesOnDiskPercent' => $tmpOnDiskPercent,
                'threadsConnectedPercent' => $threadsConnectedPercent,
                'tableLockWaitPercent' => $tableLockWaitPercent,
            ],
            'queryCache' => [
                'available' => $queryCacheAvailable,
            ],
            'slowQueries' => [
                'count' => $counters['Slow_queries'],
                'longQueryTime' => $variables['long_query_time'] ?? null,
                'slowQueryLog' => $variables['slow_query_log'] ?? null,
            ],
        ];
    }

    /**
     * @return list<array{capability: string, reason: string}>
     */
    private function databasePrivilegeUnavailableCapabilities(): array
    {
        // capability values are technical identifiers shown via t() in the Vue panel
        // (unknown keys fall back to the English identifier).
        return [
            [
                'capability' => 'OS load',
                'reason' => 'No SQL primitive for host CPU or disk load',
            ],
            [
                'capability' => 'InnoDB engine status',
                'reason' => 'Requires PROCESS privilege',
            ],
            [
                'capability' => 'performance_schema',
                'reason' => 'Requires SELECT privilege on performance_schema',
            ],
            [
                'capability' => 'Full processlist',
                'reason' => 'Requires PROCESS privilege (otherwise only own threads)',
            ],
        ];
    }

    /**
     * Derived privilege scope only — never returns raw SHOW GRANTS strings
     * (they can contain IDENTIFIED BY PASSWORD hashes).
     *
     * @return array{
     *     scope: string,
     *     hasGlobalPrivileges: bool,
     *     resolved: bool,
     *     unavailable: list<array{capability: string, reason: string}>
     * }
     */
    private function getDatabasePrivilegeScope(): array
    {
        $unavailable = $this->databasePrivilegeUnavailableCapabilities();
        $hasGlobalPrivileges = false;
        $resolved = false;

        try {
            $connection = $this->em->getConnection();
            $grants = $connection->fetchFirstColumn('SHOW GRANTS FOR CURRENT_USER()');
            $resolved = true;
            foreach ($grants as $grant) {
                if (!\is_string($grant) || '' === $grant) {
                    continue;
                }

                // Never inspect or retain password-hash material beyond the ON clause.
                if (!preg_match('/^GRANT\s+(.+?)\s+ON\s+(\S+)\s+TO\b/i', $grant, $matches)) {
                    continue;
                }

                $privilegesRaw = strtoupper(trim($matches[1]));
                $onTarget = strtoupper(trim($matches[2], '`"\''));

                if ('*.*' !== $onTarget) {
                    continue;
                }

                $parts = array_filter(
                    array_map(static fn (string $p): string => trim($p), explode(',', $privilegesRaw)),
                    static fn (string $p): bool => '' !== $p && 'USAGE' !== $p
                );

                if ([] !== $parts) {
                    $hasGlobalPrivileges = true;

                    break;
                }
            }
        } catch (Throwable) {
            // Grants unreadable: keep defaults, mark unresolved, never surface raw errors.
            $resolved = false;
        }

        return [
            'scope' => $hasGlobalPrivileges ? 'global' : 'database',
            'hasGlobalPrivileges' => $hasGlobalPrivileges,
            'resolved' => $resolved,
            'unavailable' => $unavailable,
        ];
    }

    /**
     * @return array{
     *     detected: string|null,
     *     software: string|null,
     *     scannedPaths: list<string>,
     *     status: array{
     *         available: bool,
     *         reason: string|null,
     *         path: string|null,
     *         engine: string|null,
     *         apache: array<string, mixed>|null,
     *         nginx: array<string, mixed>|null
     *     }
     * }
     */
    private function getWebserverLoadStats(Request $request): array
    {
        $software = $this->readServerSoftware($request);
        $detected = $this->detectWebserverEngine($software);

        if (null === $detected) {
            return $this->emptyWebserverLoadStats(
                detected: null,
                software: $software,
                scannedPaths: [],
                reason: 'unsupported_server',
            );
        }

        $scannedPaths = 'apache' === $detected
            ? self::WEBSERVER_APACHE_STATUS_PATHS
            : self::WEBSERVER_NGINX_STATUS_PATHS;

        $ports = $this->webserverProbePorts($request);
        $hosts = ['127.0.0.1', '[::1]'];

        foreach ($scannedPaths as $path) {
            $body = $this->fetchLocalhostStatusBody($path, $hosts, $ports);
            if (null === $body) {
                continue;
            }

            if ('apache' === $detected) {
                $apache = $this->parseApacheStatusAuto($body);
                if (null === $apache) {
                    continue;
                }

                return [
                    'detected' => 'apache',
                    'software' => $software,
                    'scannedPaths' => $scannedPaths,
                    'status' => [
                        'available' => true,
                        'reason' => null,
                        'path' => $path,
                        'engine' => 'apache',
                        'apache' => $apache,
                        'nginx' => null,
                    ],
                ];
            }

            $nginx = $this->parseNginxStubStatus($body);
            if (null === $nginx) {
                continue;
            }

            return [
                'detected' => 'nginx',
                'software' => $software,
                'scannedPaths' => $scannedPaths,
                'status' => [
                    'available' => true,
                    'reason' => null,
                    'path' => $path,
                    'engine' => 'nginx',
                    'apache' => null,
                    'nginx' => $nginx,
                ],
            ];
        }

        return $this->emptyWebserverLoadStats(
            detected: $detected,
            software: $software,
            scannedPaths: $scannedPaths,
            reason: 'status_unavailable',
        );
    }

    /**
     * @param list<string> $scannedPaths
     *
     * @return array{
     *     detected: string|null,
     *     software: string|null,
     *     scannedPaths: list<string>,
     *     status: array{
     *         available: bool,
     *         reason: string|null,
     *         path: string|null,
     *         engine: string|null,
     *         apache: null,
     *         nginx: null
     *     }
     * }
     */
    private function emptyWebserverLoadStats(
        ?string $detected,
        ?string $software,
        array $scannedPaths,
        string $reason,
    ): array {
        return [
            'detected' => $detected,
            'software' => $software,
            'scannedPaths' => $scannedPaths,
            'status' => [
                'available' => false,
                'reason' => $reason,
                'path' => null,
                'engine' => $detected,
                'apache' => null,
                'nginx' => null,
            ],
        ];
    }

    private function readServerSoftware(Request $request): ?string
    {
        $raw = trim((string) $request->server->get('SERVER_SOFTWARE', ''));

        return '' !== $raw ? $raw : null;
    }

    /**
     * @return 'apache'|'nginx'|null
     */
    private function detectWebserverEngine(?string $software): ?string
    {
        if (null === $software || '' === $software) {
            return null;
        }

        $lower = strtolower($software);
        if (str_contains($lower, 'apache') || str_contains($lower, 'httpd')) {
            return 'apache';
        }
        if (str_contains($lower, 'nginx')) {
            return 'nginx';
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function webserverProbePorts(Request $request): array
    {
        $ports = [];
        $requestPort = (int) $request->server->get('SERVER_PORT', 0);
        if ($requestPort > 0 && $requestPort < 65536) {
            $ports[] = $requestPort;
        }
        // Prefer plain HTTP status listeners (typical localhost allowlists).
        foreach ([80, 8080] as $fallback) {
            if (!\in_array($fallback, $ports, true)) {
                $ports[] = $fallback;
            }
        }

        return $ports;
    }

    /**
     * Fetch a status body from hardcoded localhost hosts/ports only (no user URL).
     *
     * @param list<string> $hosts
     * @param list<int>    $ports
     */
    private function fetchLocalhostStatusBody(string $path, array $hosts, array $ports): ?string
    {
        if (!str_starts_with($path, '/')) {
            return null;
        }

        $client = HttpClient::create([
            'timeout' => 1.5,
            'max_redirects' => 0,
            'headers' => [
                'User-Agent' => 'Chamilo-SystemStatus/1.0',
                'Accept' => 'text/plain,text/*;q=0.9,*/*;q=0.1',
            ],
        ]);

        foreach ($hosts as $host) {
            if ('127.0.0.1' !== $host && '[::1]' !== $host) {
                continue;
            }

            foreach ($ports as $port) {
                if ($port <= 0 || $port > 65535) {
                    continue;
                }

                $url = \sprintf('http://%s:%d%s', $host, $port, $path);

                try {
                    $response = $client->request('GET', $url);
                    if (200 !== $response->getStatusCode()) {
                        continue;
                    }

                    $body = $response->getContent(false);
                    if (\is_string($body) && '' !== trim($body)) {
                        return $body;
                    }
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Parse Apache mod_status machine-readable output (?auto).
     *
     * @return array{
     *     serverVersion: string|null,
     *     serverMpm: string|null,
     *     uptimeSeconds: int|null,
     *     totalAccesses: int|null,
     *     totalKBytes: int|null,
     *     reqPerSec: float|null,
     *     bytesPerSec: float|null,
     *     bytesPerReq: float|null,
     *     busyWorkers: int|null,
     *     idleWorkers: int|null,
     *     gracefulWorkers: int|null,
     *     cpuLoad: float|null,
     *     load1: float|null,
     *     load5: float|null,
     *     load15: float|null,
     *     workersBusyPercent: float|null,
     *     scoreboard: array{
     *         waiting: int,
     *         starting: int,
     *         reading: int,
     *         sending: int,
     *         keepalive: int,
     *         dns: int,
     *         closing: int,
     *         logging: int,
     *         graceful: int,
     *         idleCleanup: int,
     *         open: int,
     *         other: int,
     *         totalSlots: int
     *     }|null
     * }|null
     */
    private function parseApacheStatusAuto(string $body): ?array
    {
        // Reject HTML error pages and unrelated content.
        if (!preg_match('/^\s*(?:ServerVersion|BusyWorkers|Scoreboard|Total Accesses)\s*:/mi', $body)) {
            return null;
        }

        $fields = [];
        foreach (preg_split("/\r\n|\n|\r/", $body) ?: [] as $line) {
            $line = trim($line);
            if ('' === $line || !str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = explode(':', $line, 2);
            $fields[trim($key)] = trim($value);
        }

        if ([] === $fields) {
            return null;
        }

        $int = static function (array $fields, string $key): ?int {
            if (!isset($fields[$key]) || !is_numeric($fields[$key])) {
                return null;
            }

            return (int) $fields[$key];
        };
        $float = static function (array $fields, string $key): ?float {
            if (!isset($fields[$key]) || !is_numeric($fields[$key])) {
                return null;
            }

            return (float) $fields[$key];
        };

        $busy = $int($fields, 'BusyWorkers');
        $idle = $int($fields, 'IdleWorkers');
        $workersBusyPercent = null;
        if (null !== $busy && null !== $idle) {
            $workerTotal = $busy + $idle;
            if ($workerTotal > 0) {
                $workersBusyPercent = round(100 * $busy / $workerTotal, 2);
            }
        }

        $scoreboard = null;
        if (isset($fields['Scoreboard']) && '' !== $fields['Scoreboard']) {
            $scoreboard = $this->summarizeApacheScoreboard($fields['Scoreboard']);
        }

        // Require at least one load-related signal so random text cannot pass.
        if (null === $busy && null === $int($fields, 'Total Accesses') && null === $scoreboard) {
            return null;
        }

        return [
            'serverVersion' => $fields['ServerVersion'] ?? null,
            'serverMpm' => $fields['ServerMPM'] ?? null,
            'uptimeSeconds' => $int($fields, 'ServerUptimeSeconds') ?? $int($fields, 'Uptime'),
            'totalAccesses' => $int($fields, 'Total Accesses'),
            'totalKBytes' => $int($fields, 'Total kBytes'),
            'reqPerSec' => $float($fields, 'ReqPerSec'),
            'bytesPerSec' => $float($fields, 'BytesPerSec'),
            'bytesPerReq' => $float($fields, 'BytesPerReq'),
            'busyWorkers' => $busy,
            'idleWorkers' => $idle,
            'gracefulWorkers' => $int($fields, 'GracefulWorkers'),
            'cpuLoad' => $float($fields, 'CPULoad'),
            'load1' => $float($fields, 'Load1'),
            'load5' => $float($fields, 'Load5'),
            'load15' => $float($fields, 'Load15'),
            'workersBusyPercent' => $workersBusyPercent,
            'scoreboard' => $scoreboard,
        ];
    }

    /**
     * @return array{
     *     waiting: int,
     *     starting: int,
     *     reading: int,
     *     sending: int,
     *     keepalive: int,
     *     dns: int,
     *     closing: int,
     *     logging: int,
     *     graceful: int,
     *     idleCleanup: int,
     *     open: int,
     *     other: int,
     *     totalSlots: int
     * }
     */
    private function summarizeApacheScoreboard(string $scoreboard): array
    {
        $counts = [
            'waiting' => 0,
            'starting' => 0,
            'reading' => 0,
            'sending' => 0,
            'keepalive' => 0,
            'dns' => 0,
            'closing' => 0,
            'logging' => 0,
            'graceful' => 0,
            'idleCleanup' => 0,
            'open' => 0,
            'other' => 0,
            'totalSlots' => 0,
        ];

        $map = [
            '_' => 'waiting',
            'S' => 'starting',
            'R' => 'reading',
            'W' => 'sending',
            'K' => 'keepalive',
            'D' => 'dns',
            'C' => 'closing',
            'L' => 'logging',
            'G' => 'graceful',
            'I' => 'idleCleanup',
            '.' => 'open',
        ];

        $length = \strlen($scoreboard);
        $counts['totalSlots'] = $length;
        for ($i = 0; $i < $length; ++$i) {
            $ch = $scoreboard[$i];
            if (isset($map[$ch])) {
                ++$counts[$map[$ch]];
            } else {
                ++$counts['other'];
            }
        }

        return $counts;
    }

    /**
     * Parse Nginx stub_status plain-text body.
     *
     * @return array{
     *     activeConnections: int|null,
     *     accepts: int|null,
     *     handled: int|null,
     *     requests: int|null,
     *     reading: int|null,
     *     writing: int|null,
     *     waiting: int|null
     * }|null
     */
    private function parseNginxStubStatus(string $body): ?array
    {
        if (!preg_match('/Active connections:\s*(\d+)/i', $body, $activeMatch)) {
            return null;
        }

        $accepts = null;
        $handled = null;
        $requests = null;
        if (preg_match('/server\s+accepts\s+handled\s+requests\s+(\d+)\s+(\d+)\s+(\d+)/is', $body, $counters)) {
            $accepts = (int) $counters[1];
            $handled = (int) $counters[2];
            $requests = (int) $counters[3];
        }

        $reading = null;
        $writing = null;
        $waiting = null;
        if (preg_match('/Reading:\s*(\d+)\s+Writing:\s*(\d+)\s+Waiting:\s*(\d+)/i', $body, $states)) {
            $reading = (int) $states[1];
            $writing = (int) $states[2];
            $waiting = (int) $states[3];
        }

        return [
            'activeConnections' => (int) $activeMatch[1],
            'accepts' => $accepts,
            'handled' => $handled,
            'requests' => $requests,
            'reading' => $reading,
            'writing' => $writing,
            'waiting' => $waiting,
        ];
    }

    /**
     * Aggregate OPcache stats only (opcache_get_status(false) — never dumps scripts).
     *
     * @return array{
     *     available: bool,
     *     enabled: bool,
     *     full: bool|null,
     *     memoryUsedBytes: int|null,
     *     memoryFreeBytes: int|null,
     *     memoryWastedBytes: int|null,
     *     memoryUsedPercent: float|null,
     *     cachedScripts: int|null,
     *     cachedKeys: int|null,
     *     maxCachedKeys: int|null,
     *     hits: int|null,
     *     misses: int|null,
     *     hitRatePercent: float|null,
     *     oomRestarts: int|null,
     *     hashRestarts: int|null,
     *     manualRestarts: int|null,
     *     internedStringsUsedBytes: int|null,
     *     internedStringsFreeBytes: int|null,
     *     internedStringsNumber: int|null,
     *     internedStringsBufferSize: int|null
     * }
     */
    private function getOpcacheStats(): array
    {
        $empty = [
            'available' => false,
            'enabled' => false,
            'full' => null,
            'memoryUsedBytes' => null,
            'memoryFreeBytes' => null,
            'memoryWastedBytes' => null,
            'memoryUsedPercent' => null,
            'cachedScripts' => null,
            'cachedKeys' => null,
            'maxCachedKeys' => null,
            'hits' => null,
            'misses' => null,
            'hitRatePercent' => null,
            'oomRestarts' => null,
            'hashRestarts' => null,
            'manualRestarts' => null,
            'internedStringsUsedBytes' => null,
            'internedStringsFreeBytes' => null,
            'internedStringsNumber' => null,
            'internedStringsBufferSize' => null,
        ];

        $extensionLoaded = \extension_loaded('Zend OPcache') || \extension_loaded('opcache');
        $functionExists = \function_exists('opcache_get_status');
        if (!$extensionLoaded || !$functionExists) {
            return $empty;
        }

        // false = do NOT return the per-script list (large + info-leaky).
        $status = @\opcache_get_status(false);
        if (false === $status || !\is_array($status)) {
            $iniEnabled = (bool) (int) \ini_get('opcache.enable');

            return array_merge($empty, [
                'available' => true,
                'enabled' => $iniEnabled,
            ]);
        }

        $enabled = !empty($status['opcache_enabled']);
        $memory = \is_array($status['memory_usage'] ?? null) ? $status['memory_usage'] : [];
        $stats = \is_array($status['opcache_statistics'] ?? null) ? $status['opcache_statistics'] : [];
        $interned = \is_array($status['interned_strings_usage'] ?? null) ? $status['interned_strings_usage'] : [];

        $used = isset($memory['used_memory']) ? (int) $memory['used_memory'] : null;
        $free = isset($memory['free_memory']) ? (int) $memory['free_memory'] : null;
        $wasted = isset($memory['wasted_memory']) ? (int) $memory['wasted_memory'] : null;
        $total = null;
        $usedPercent = null;
        if (null !== $used && null !== $free && null !== $wasted) {
            $total = $used + $free + $wasted;
            if ($total > 0) {
                $usedPercent = round(100 * ($used + $wasted) / $total, 2);
            }
        }

        $hits = isset($stats['hits']) ? (int) $stats['hits'] : null;
        $misses = isset($stats['misses']) ? (int) $stats['misses'] : null;
        $hitRate = null;
        if (null !== $hits && null !== $misses) {
            $denom = $hits + $misses;
            $hitRate = $denom > 0 ? round(100 * $hits / $denom, 2) : 100.0;
        } elseif (isset($stats['opcache_hit_rate'])) {
            $hitRate = round((float) $stats['opcache_hit_rate'], 2);
        }

        return [
            'available' => true,
            'enabled' => $enabled,
            'full' => isset($status['cache_full']) ? (bool) $status['cache_full'] : null,
            'memoryUsedBytes' => $used,
            'memoryFreeBytes' => $free,
            'memoryWastedBytes' => $wasted,
            'memoryUsedPercent' => $usedPercent,
            'cachedScripts' => isset($stats['num_cached_scripts']) ? (int) $stats['num_cached_scripts'] : null,
            'cachedKeys' => isset($stats['num_cached_keys']) ? (int) $stats['num_cached_keys'] : null,
            'maxCachedKeys' => isset($stats['max_cached_keys']) ? (int) $stats['max_cached_keys'] : null,
            'hits' => $hits,
            'misses' => $misses,
            'hitRatePercent' => $hitRate,
            'oomRestarts' => isset($stats['oom_restarts']) ? (int) $stats['oom_restarts'] : null,
            'hashRestarts' => isset($stats['hash_restarts']) ? (int) $stats['hash_restarts'] : null,
            'manualRestarts' => isset($stats['manual_restarts']) ? (int) $stats['manual_restarts'] : null,
            'internedStringsUsedBytes' => isset($interned['used_memory']) ? (int) $interned['used_memory'] : null,
            'internedStringsFreeBytes' => isset($interned['free_memory']) ? (int) $interned['free_memory'] : null,
            'internedStringsNumber' => isset($interned['number_of_strings']) ? (int) $interned['number_of_strings'] : null,
            'internedStringsBufferSize' => isset($interned['buffer_size']) ? (int) $interned['buffer_size'] : null,
        ];
    }

    /**
     * Aggregate APCu stats only (limited mode — never dumps cache keys/values).
     *
     * @return array{
     *     available: bool,
     *     enabled: bool,
     *     memorySizeBytes: int|null,
     *     memoryAvailableBytes: int|null,
     *     memoryUsedBytes: int|null,
     *     memoryUsedPercent: float|null,
     *     numSlots: int|null,
     *     numHits: int|null,
     *     numMisses: int|null,
     *     hitRatePercent: float|null,
     *     numInserts: int|null,
     *     numEntries: int|null,
     *     numExpunges: int|null,
     *     startTime: string|null
     * }
     */
    private function getApcuStats(): array
    {
        $empty = [
            'available' => false,
            'enabled' => false,
            'memorySizeBytes' => null,
            'memoryAvailableBytes' => null,
            'memoryUsedBytes' => null,
            'memoryUsedPercent' => null,
            'numSlots' => null,
            'numHits' => null,
            'numMisses' => null,
            'hitRatePercent' => null,
            'numInserts' => null,
            'numEntries' => null,
            'numExpunges' => null,
            'startTime' => null,
        ];

        if (!\extension_loaded('apcu') || !\function_exists('apcu_cache_info')) {
            return $empty;
        }

        $enabled = \function_exists('apcu_enabled') ? (bool) apcu_enabled() : (bool) (int) \ini_get('apc.enabled');
        if (!$enabled) {
            return array_merge($empty, [
                'available' => true,
                'enabled' => false,
            ]);
        }

        // limited=true: no entry dump (keys/values would be an attack surface).
        $info = @apcu_cache_info(true);
        if (false === $info || !\is_array($info)) {
            return array_merge($empty, [
                'available' => true,
                'enabled' => true,
            ]);
        }

        $sma = null;
        if (\function_exists('apcu_sma_info')) {
            $smaRaw = @apcu_sma_info(true);
            if (false !== $smaRaw && \is_array($smaRaw)) {
                $sma = $smaRaw;
            }
        }

        $memSize = isset($sma['seg_size'], $sma['num_seg'])
            ? (int) $sma['seg_size'] * (int) $sma['num_seg']
            : (isset($sma['seg_size']) ? (int) $sma['seg_size'] : null);
        $memAvail = isset($sma['avail_mem']) ? (int) $sma['avail_mem'] : null;
        $memUsed = null;
        $memUsedPercent = null;
        if (null !== $memSize && null !== $memAvail) {
            $memUsed = max(0, $memSize - $memAvail);
            if ($memSize > 0) {
                $memUsedPercent = round(100 * $memUsed / $memSize, 2);
            }
        }

        $hits = isset($info['num_hits']) ? (int) $info['num_hits'] : null;
        $misses = isset($info['num_misses']) ? (int) $info['num_misses'] : null;
        $hitRate = null;
        if (null !== $hits && null !== $misses) {
            $denom = $hits + $misses;
            $hitRate = $denom > 0 ? round(100 * $hits / $denom, 2) : 100.0;
        }

        $startTime = null;
        if (isset($info['start_time']) && is_numeric($info['start_time'])) {
            $startTime = (new DateTimeImmutable('@'.(int) $info['start_time']))
                ->setTimezone(new DateTimeZone(date_default_timezone_get()))
                ->format(DATE_ATOM)
            ;
        }

        return [
            'available' => true,
            'enabled' => true,
            'memorySizeBytes' => $memSize,
            'memoryAvailableBytes' => $memAvail,
            'memoryUsedBytes' => $memUsed,
            'memoryUsedPercent' => $memUsedPercent,
            'numSlots' => isset($info['num_slots']) ? (int) $info['num_slots'] : null,
            'numHits' => $hits,
            'numMisses' => $misses,
            'hitRatePercent' => $hitRate,
            'numInserts' => isset($info['num_inserts']) ? (int) $info['num_inserts'] : null,
            'numEntries' => isset($info['num_entries']) ? (int) $info['num_entries'] : null,
            'numExpunges' => isset($info['expunges']) ? (int) $info['expunges'] : null,
            'startTime' => $startTime,
        ];
    }

    /**
     * @return list<array{status: string, section: string, title: string, url: string|null, current: string, expected: string, comment: string}>
     */
    private function getGenericRows(string $section): array
    {
        $diagnoser = (new Diagnoser())->withStructuredOutput();
        $method = 'get_'.$section.'_data';

        /** @var list<array{status: string, section: string, title: string, url: string|null, current: string, expected: string, comment: string}> $rows */
        return $diagnoser->{$method}();
    }

    /**
     * @return list<array{path: string, constant: string}>
     */
    private function getPathsRows(): array
    {
        $data = (new Diagnoser())->get_paths_data();
        $rows = [];

        foreach (($data['data'] ?? []) as $constant => $path) {
            $rows[] = [
                'path' => (string) $path,
                'constant' => (string) $constant,
            ];
        }

        return $rows;
    }

    /**
     * Courses space via DQL (no raw SQL), matching legacy semantics:
     * - cap 1000 courses ordered by lastVisit DESC, code ASC
     * - used size from ResourceFile, de-duplicated per (course, file)
     * - used MB ceil, min 1 when any bytes present
     * - quota: course.diskQuota (MB) if > 0 else platform default.
     *
     * @return list<array{id: int, code: string, usedMb: int, quotaMb: int, lastVisit: string|null}>
     */
    private function getCoursesSpaceRows(): array
    {
        $courses = $this->em->createQueryBuilder()
            ->select('c.id', 'c.code', 'c.diskQuota', 'c.lastVisit')
            ->from(Course::class, 'c')
            ->orderBy('c.lastVisit', 'DESC')
            ->addOrderBy('c.code', 'ASC')
            ->setMaxResults(1000)
            ->getQuery()
            ->getArrayResult()
        ;

        $usageRows = $this->em->createQueryBuilder()
            ->select('IDENTITY(rl.course) AS courseId', 'rf.id AS fileId', 'rf.size AS size')
            ->from(ResourceLink::class, 'rl')
            ->innerJoin('rl.resourceNode', 'rn')
            ->innerJoin(ResourceFile::class, 'rf', 'WITH', 'rf.resourceNode = rn')
            ->where('rl.course IS NOT NULL')
            ->groupBy('rl.course')
            ->addGroupBy('rf.id')
            ->addGroupBy('rf.size')
            ->getQuery()
            ->getArrayResult()
        ;

        $usedBytesByCourse = [];
        foreach ($usageRows as $usageRow) {
            $courseId = (int) $usageRow['courseId'];
            $usedBytesByCourse[$courseId] = ($usedBytesByCourse[$courseId] ?? 0) + (int) ($usageRow['size'] ?? 0);
        }

        $defaultQuotaMb = $this->resolveDefaultCourseQuotaMb();
        $rows = [];

        foreach ($courses as $course) {
            $id = (int) $course['id'];
            $bytes = $usedBytesByCourse[$id] ?? 0;
            $usedMb = $bytes > 0 ? max(1, (int) ceil($bytes / (1024 * 1024))) : 0;

            $diskQuota = (int) ($course['diskQuota'] ?? 0);
            $quotaMb = $diskQuota > 0 ? $diskQuota : $defaultQuotaMb;

            $lastVisit = $course['lastVisit'] ?? null;
            if ($lastVisit instanceof DateTimeInterface) {
                $lastVisitIso = $lastVisit->format(DATE_ATOM);
            } elseif (null !== $lastVisit && '' !== $lastVisit) {
                $lastVisitIso = (string) $lastVisit;
            } else {
                $lastVisitIso = null;
            }

            $rows[] = [
                'id' => $id,
                'code' => (string) $course['code'],
                'usedMb' => $usedMb,
                'quotaMb' => $quotaMb,
                'lastVisit' => $lastVisitIso,
            ];
        }

        return $rows;
    }

    /**
     * Platform default course quota in MB from SettingsManager.
     */
    private function resolveDefaultCourseQuotaMb(): int
    {
        $candidates = [
            'course.course_quota',
            'document.default_document_quotum',
            'document.default_document_quota',
            'document.default_course_quota',
        ];

        foreach ($candidates as $key) {
            try {
                $raw = (string) $this->settingsManager->getSetting($key);
            } catch (Throwable) {
                continue;
            }

            if ('' === $raw || '0' === $raw) {
                continue;
            }

            $mb = $this->parseQuotaRawToMb($raw);
            if ($mb > 0) {
                return $mb;
            }
        }

        return 0;
    }

    private function parseQuotaRawToMb(string $raw): int
    {
        $s = strtolower(trim($raw));

        if (preg_match('/^\d+$/', $s)) {
            $num = (int) $s;

            // Large integers look like bytes.
            return ($num >= 1048576) ? (int) ceil($num / 1048576) : $num;
        }

        if (preg_match('/^\s*(\d+)\s*([mg])(?:b)?\s*$/i', $s, $m)) {
            $num = (int) $m[1];
            $unit = strtolower($m[2]);

            return 'g' === $unit ? $num * 1024 : $num;
        }

        if (preg_match('/(\d+)/', $s, $m)) {
            $num = (int) $m[1];

            return ($num >= 1048576) ? (int) ceil($num / 1048576) : $num;
        }

        return 0;
    }
}
