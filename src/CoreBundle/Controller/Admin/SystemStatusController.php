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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
