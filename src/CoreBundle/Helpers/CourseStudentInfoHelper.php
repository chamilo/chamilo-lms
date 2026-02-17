<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Category;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;
use Tracking;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const WEB_PATH;

final class CourseStudentInfoHelper
{
    private const LOG_PREFIX = '[CourseStudentInfoHelper]';
    private const TOOL_TABLE = 'tool';
    private const TOOL_TITLE_CACHE_KEY = 'course_student_info_tool_id_title_map_v1';

    /**
     * Hard limit to avoid log storms when listing many courses.
     */
    private const LOG_LIMIT = 600;

    private static int $logCount = 0;

    /**
     * Standard table in Chamilo 2 for resources visibility and placement.
     */
    private const RESOURCE_LINK_TABLE = 'resource_link';

    private bool $showDebug = false;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Connection $connection,
        private readonly CacheInterface $cache,
        private readonly KernelInterface $kernel,
        private readonly SettingsManager $settingsManager,
        bool $showDebug = false
    ) {
        // Single flag: enable debug logs either explicitly or when Symfony kernel is in debug mode.
        $this->showDebug = $showDebug || (method_exists($this->kernel, 'isDebug') && $this->kernel->isDebug());
    }

    /**
     * Returns computed student tracking data for a course + optional session.
     */
    public function getStudentInfo(int $userId, int $courseId, int $sessionId = 0): array
    {
        $flags = $this->getStudentInfoFlags();
        if (!$this->isAnyFlagEnabled($flags)) {
            // Setting disabled: do not compute anything.
            return $this->emptyResult();
        }

        $this->log('getStudentInfo: called', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'env' => $this->kernel->getEnvironment(),
            'flags' => $flags,
        ]);

        if ($userId <= 0 || $courseId <= 0) {
            $this->log('getStudentInfo: invalid arguments, returning emptyResult', [
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);

            return $this->emptyResult();
        }

        try {
            /** @var Course|null $course */
            $course = $this->em->getRepository(Course::class)->find($courseId);
        } catch (Throwable $e) {
            $this->log('getStudentInfo: failed loading course, returning emptyResult', [
                'course_id' => $courseId,
                'exception' => $e->getMessage(),
            ]);

            return $this->emptyResult();
        }

        if (!$course) {
            $this->log('getStudentInfo: course not found, returning emptyResult', [
                'course_id' => $courseId,
            ]);

            return $this->emptyResult();
        }

        $this->log('getStudentInfo: course loaded', [
            'course_id' => (int) $course->getId(),
            'course_code' => (string) $course->getCode(),
            'course_title' => method_exists($course, 'getTitle') ? (string) $course->getTitle() : null,
        ]);

        return $this->getStudentInfoForCourse($userId, $course, $sessionId);
    }

    /**
     * Same as getStudentInfo(), but avoids re-fetching the Course entity.
     */
    public function getStudentInfoForCourse(int $userId, Course $course, int $sessionId = 0): array
    {
        $flags = $this->getStudentInfoFlags();
        if (!$this->isAnyFlagEnabled($flags)) {
            // Setting disabled: do not compute anything.
            return $this->emptyResult();
        }

        $courseId = (int) $course->getId();

        if ($userId <= 0 || $courseId <= 0) {
            $this->log('getStudentInfoForCourse: invalid arguments, returning emptyResult', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
            ]);

            return $this->emptyResult();
        }

        // Add flags bitmask to avoid serving stale values when toggling the JSON setting.
        $mask = $this->flagsBitmask($flags);
        $cacheKey = \sprintf('course_student_info_u%d_c%d_s%d_f%d', $userId, $courseId, $sessionId, $mask);

        $start = microtime(true);
        $memStart = memory_get_usage(true);

        $computed = false;

        $this->log('getStudentInfoForCourse: cache get begin', [
            'cache_key' => $cacheKey,
            'user_id' => $userId,
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'flags' => $flags,
        ]);

        $result = $this->cache->get($cacheKey, function (ItemInterface $item) use ($userId, $course, $courseId, $sessionId, $flags, &$computed) {
            $computed = true;

            // Keep it short to avoid stale UI while browsing.
            $item->expiresAfter(300);

            $this->log('cache MISS: computing student info', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'flags' => $flags,
            ]);

            $session = null;
            if ($sessionId > 0) {
                try {
                    /** @var Session|null $session */
                    $session = $this->em->getRepository(Session::class)->find($sessionId);
                    $this->log('compute: session loaded', [
                        'session_id' => $sessionId,
                        'session_found' => (bool) $session,
                    ]);
                } catch (Throwable $e) {
                    $this->log('compute: failed loading session (will continue with null session)', [
                        'session_id' => $sessionId,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            // Compute only what is enabled by the platform setting.
            $progress = $flags['progress'] ? $this->computeProgress($userId, $course, $session) : 0.0;

            $score = $flags['score'] ? $this->computeScoreLatest($userId, $course, $session) : null;
            $bestScore = $flags['score'] ? $this->computeScoreBest($userId, $course, $session) : null;

            $certificateAvailable = $flags['certificate'] ? $this->computeCertificateAvailable($userId, $course, $sessionId) : false;

            // Completion rule: certificate OR progress >= 100 (but only if those signals are enabled).
            $completed = ($flags['certificate'] && $certificateAvailable) || ($flags['progress'] && $progress >= 100.0);

            // Extra fields (optional).
            $timeSpentSeconds = $this->computeTimeSpentSeconds($userId, $course, $sessionId);

            // hasNewContent is intentionally computed outside cache in this method
            // to stay near real-time for single-course requests.
            $hasNewContent = $this->computeHasNewContent($userId, $course, $sessionId);

            $payload = [
                'progress' => $progress,
                'score' => $score,
                'bestScore' => $bestScore,
                'timeSpentSeconds' => $timeSpentSeconds,
                'certificateAvailable' => $certificateAvailable,
                'completed' => $completed,
                'hasNewContent' => $hasNewContent,
            ];

            $this->log('cache MISS: computed result', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'result' => $payload,
            ]);

            return $payload;
        });

        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $memEnd = memory_get_usage(true);

        $this->log('getStudentInfoForCourse: cache get end', [
            'computed' => $computed ? 'MISS' : 'HIT',
            'cache_key' => $cacheKey,
            'duration_ms' => $durationMs,
            'mem_delta' => ($memEnd - $memStart),
            'mem_end' => $memEnd,
        ]);

        if (!\is_array($result)) {
            $this->log('getStudentInfoForCourse: unexpected cache result type, returning emptyResult', [
                'type' => \gettype($result),
            ]);

            return $this->emptyResult();
        }

        // Ensure keys exist and types are safe.
        $safe = $this->sanitizeResult($result);

        // Refresh "hasNewContent" outside cache (it changes often and should be near real-time).
        $safe['hasNewContent'] = $this->computeHasNewContent($userId, $course, $sessionId);

        // Enforce flags again (in case something cached got produced with a different combination in the past).
        return $this->applyFlagsToResult($safe, $flags);
    }

    /**
     * Batch version for many courses:
     * - Returns [courseId => studentInfoArray]
     * - Optimizes hasNewContent using 2 global queries (last access + resource_link max change).
     * - Keeps per-course cache for progress/score/certificate/timeSpent.
     */
    public function getStudentInfoBatchForCourses(int $userId, array $courseIds, int $sessionId = 0): array
    {
        $flags = $this->getStudentInfoFlags();
        if (!$this->isAnyFlagEnabled($flags)) {
            // Setting disabled: return empty results for requested courses.
            $out = [];
            foreach ($courseIds as $cid) {
                $cid = (int) $cid;
                if ($cid > 0) {
                    $out[(string) $cid] = $this->emptyResult();
                }
            }

            return $out;
        }

        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        $courseIds = array_filter($courseIds, static fn (int $id) => $id > 0);
        if (empty($courseIds) || $userId <= 0) {
            return [];
        }

        // Safety limit.
        if (\count($courseIds) > 300) {
            $courseIds = \array_slice($courseIds, 0, 300);
        }

        // Compute hasNewContent in batch.
        $hasNewMap = $this->computeHasNewContentBatch($userId, $courseIds, $sessionId);

        // Load Course entities in one query (needed for Tracking + certificate checks).
        /** @var Course[] $courses */
        $courses = $this->em->getRepository(Course::class)->findBy(['id' => $courseIds]);
        $courseMap = [];
        foreach ($courses as $c) {
            $courseMap[(int) $c->getId()] = $c;
        }

        $mask = $this->flagsBitmask($flags);
        $out = [];

        foreach ($courseIds as $cid) {
            $course = $courseMap[$cid] ?? null;
            if (!$course instanceof Course) {
                continue;
            }

            $cacheKey = \sprintf('course_student_info_u%d_c%d_s%d_f%d', $userId, $cid, $sessionId, $mask);

            $payload = $this->cache->get($cacheKey, function (ItemInterface $item) use ($userId, $course, $sessionId, $flags) {
                // Short TTL to keep UI fresh while browsing.
                $item->expiresAfter(300);

                $session = null;
                if ($sessionId > 0) {
                    try {
                        /** @var Session|null $session */
                        $session = $this->em->getRepository(Session::class)->find($sessionId);
                    } catch (Throwable $e) {
                        $session = null;
                    }
                }

                $progress = $flags['progress'] ? $this->computeProgress($userId, $course, $session) : 0.0;
                $score = $flags['score'] ? $this->computeScoreLatest($userId, $course, $session) : null;
                $bestScore = $flags['score'] ? $this->computeScoreBest($userId, $course, $session) : null;
                $certificateAvailable = $flags['certificate'] ? $this->computeCertificateAvailable($userId, $course, $sessionId) : false;
                $completed = ($flags['certificate'] && $certificateAvailable) || ($flags['progress'] && $progress >= 100.0);
                $timeSpentSeconds = $this->computeTimeSpentSeconds($userId, $course, $sessionId);

                // hasNewContent is injected outside cache in batch mode.
                return [
                    'progress' => $progress,
                    'score' => $score,
                    'bestScore' => $bestScore,
                    'timeSpentSeconds' => $timeSpentSeconds,
                    'certificateAvailable' => $certificateAvailable,
                    'completed' => $completed,
                    'hasNewContent' => false,
                ];
            });

            $safe = $this->sanitizeResult(\is_array($payload) ? $payload : []);
            $safe = $this->applyFlagsToResult($safe, $flags);

            // Inject hasNewContent from the batch map (near real-time, without per-course queries).
            $safe['hasNewContent'] = (bool) ($hasNewMap[$cid] ?? false);

            $out[(string) $cid] = $safe;
        }

        return $out;
    }

    /**
     * Returns a list of "tools" (grouped by resource type) that have changed since last access.
     * Intended to be called on-demand when the user opens the notifications popover.
     */
    public function getNewContentToolsForCourse(int $userId, Course $course, int $sessionId = 0, int $limit = 20): array
    {
        $courseId = (int) $course->getId();
        if ($userId <= 0 || $courseId <= 0) {
            return [];
        }

        $firstCourseAccess = $this->getFirstCourseAccessDateTime($userId, $courseId, $sessionId);
        if (!$firstCourseAccess instanceof DateTimeImmutable) {
            return [];
        }

        $toolAccessMap = $this->fetchLastAccessPerToolMapFromTrackLastAccess($userId, $courseId, $sessionId);
        $typeRows = $this->fetchLastChangeByTypeForCourse($userId, $courseId, $sessionId);

        $toolMap = [];

        foreach ($typeRows as $row) {
            $toolId = (int) ($row['tool_id'] ?? -1);
            $typeTitle = (string) ($row['type_title'] ?? '');
            $lastChange = $this->parseDateTime((string) ($row['last_change'] ?? ''));

            if (!$lastChange instanceof DateTimeImmutable) {
                continue;
            }

            $toolInfo = $this->mapToolIdAndTypeTitleToTool($toolId, $typeTitle, $courseId, $sessionId);
            if (!$toolInfo || empty($toolInfo['trackTools'])) {
                continue;
            }

            $key = trim((string) ($toolInfo['key'] ?? ''));
            if ('' === $key) {
                continue;
            }

            $baseline = $this->pickBestToolAccessOrFallback(
                $toolAccessMap,
                (array) $toolInfo['trackTools'],
                $firstCourseAccess
            );

            if ($lastChange->getTimestamp() <= $baseline->getTimestamp()) {
                continue;
            }

            $cntRow = $this->fetchNewContentTypeCountSince(
                $userId,
                $courseId,
                $sessionId,
                $baseline,
                $toolId,
                $typeTitle
            );

            $cnt = (int) ($cntRow['cnt'] ?? 0);
            $lastChangeRaw = (string) ($cntRow['last_change'] ?? '');
            $lastChangeUsed = '' !== trim($lastChangeRaw) ? $lastChangeRaw : $lastChange->format('Y-m-d H:i:s');

            if (!isset($toolMap[$key])) {
                $toolMap[$key] = [
                    'key' => $key,
                    'label' => (string) ($toolInfo['label'] ?? $key),
                    'url' => $toolInfo['url'] ?? null,
                    'count' => $cnt > 0 ? $cnt : null,
                    'lastChange' => $lastChangeUsed,
                ];

                continue;
            }

            $existing = $toolMap[$key];

            $prevCount = isset($existing['count']) && is_numeric($existing['count']) ? (int) $existing['count'] : 0;
            $newCount = $prevCount + max(0, $cnt);
            $existing['count'] = $newCount > 0 ? $newCount : null;

            $existingTs = isset($existing['lastChange']) ? (strtotime((string) $existing['lastChange']) ?: 0) : 0;
            $newTs = '' !== trim($lastChangeUsed) ? (strtotime($lastChangeUsed) ?: 0) : 0;
            if ($newTs > $existingTs) {
                $existing['lastChange'] = $lastChangeUsed;
            }

            if (null === ($existing['url'] ?? null) && null !== ($toolInfo['url'] ?? null)) {
                $existing['url'] = $toolInfo['url'];
            }

            $toolMap[$key] = $existing;
        }

        $out = array_values($toolMap);

        usort($out, static function (array $a, array $b): int {
            $tsa = isset($a['lastChange']) ? (strtotime((string) $a['lastChange']) ?: 0) : 0;
            $tsb = isset($b['lastChange']) ? (strtotime((string) $b['lastChange']) ?: 0) : 0;

            return $tsb <=> $tsa;
        });

        if ($limit > 0 && \count($out) > $limit) {
            $out = \array_slice($out, 0, $limit);
        }

        return $out;
    }

    /**
     * Returns a human label of the last access used for the new-content detection.
     * Useful for UI debugging.
     */
    public function getLastAccessLabelForCourse(int $userId, int $courseId, int $sessionId = 0): ?string
    {
        $dt = $this->getLastCourseAccessDateTime($userId, $courseId, $sessionId);
        if (!$dt instanceof DateTimeImmutable) {
            return null;
        }

        return $dt->format('Y-m-d H:i:s');
    }

    private function mapToolIdAndTypeTitleToTool(int $toolId, string $typeTitle, int $courseId, int $sessionId): ?array
    {
        $t = $this->normalizeTitle($typeTitle);
        $toolTitle = $this->normalizeTitle($this->getToolTitleById($toolId) ?? '');

        if ('' === $toolTitle || 'user' === $toolTitle) {
            return null;
        }

        if (!$this->isCountableTypeTitle($t)) {
            return null;
        }

        // Introductions
        if ('tool_intro' === $t || 'introductions' === $t || 'tool_intro' === $toolTitle) {
            return [
                'key' => 'tool_intro',
                'label' => 'Course introduction',
                'url' => $this->buildLegacyToolUrl('course_home', $courseId, $sessionId),
                'trackTools' => ['ctoolintro', 'course_home'],
            ];
        }

        // Links: count ONLY real link items (resource_type.title = "links")
        if ('link' === $toolTitle) {
            if ('links' !== $t) {
                return null;
            }

            return [
                'key' => 'links',
                'label' => 'Links',
                'url' => $this->buildLegacyToolUrl('links', $courseId, $sessionId),
                'trackTools' => ['link'],
            ];
        }

        // Learning paths
        if ('learnpath' === $toolTitle || 'lps' === $t || str_starts_with($t, 'lp_')) {
            return [
                'key' => 'learnpaths',
                'label' => 'Learning paths',
                'url' => $this->buildLegacyToolUrl('learnpaths', $courseId, $sessionId),
                'trackTools' => ['learnpath'],
            ];
        }

        // Exercises / Quiz
        if (
            'quiz' === $toolTitle
            || 'exercises' === $t
            || 'questions' === $t
            || 'attempt_file' === $t
            || 'attempt_feedback' === $t
        ) {
            return [
                'key' => 'exercises',
                'label' => 'Exercises',
                'url' => $this->buildLegacyToolUrl('exercises', $courseId, $sessionId),
                'trackTools' => ['quiz', 'exercise'],
            ];
        }

        // Forums
        if ('forum' === $toolTitle || 'forums' === $t || str_starts_with($t, 'forum_')) {
            return [
                'key' => 'forums',
                'label' => 'Forums',
                'url' => $this->buildLegacyToolUrl('forums', $courseId, $sessionId),
                'trackTools' => ['forum'],
            ];
        }

        // Wikis
        if ('wiki' === $toolTitle || 'wikis' === $t || 'wiki' === $t) {
            return [
                'key' => 'wikis',
                'label' => 'Wikis',
                'url' => $this->buildLegacyToolUrl('wikis', $courseId, $sessionId),
                'trackTools' => ['wiki'],
            ];
        }

        // Gradebook
        if ('gradebook' === $toolTitle || 'gradebooks' === $t || 'gradebook_links' === $t || 'gradebook_evaluations' === $t) {
            return [
                'key' => 'gradebook',
                'label' => 'Gradebook',
                'url' => $this->buildLegacyToolUrl('gradebook', $courseId, $sessionId),
                'trackTools' => ['gradebook'],
            ];
        }

        // Groups
        if ('group' === $toolTitle || 'groups' === $t) {
            return [
                'key' => 'groups',
                'label' => 'Groups',
                'url' => $this->buildLegacyToolUrl('groups', $courseId, $sessionId),
                'trackTools' => ['group'],
            ];
        }

        // Documents
        if ('document' === $toolTitle || 'files' === $t || 'documents' === $t) {
            return [
                'key' => 'documents',
                'label' => 'Documents',
                'url' => $this->buildLegacyToolUrl('documents', $courseId, $sessionId),
                'trackTools' => ['document'],
            ];
        }

        // Surveys
        if ('survey' === $toolTitle || 'surveys' === $t || 'survey_questions' === $t) {
            return [
                'key' => 'surveys',
                'label' => 'Surveys',
                'url' => $this->buildLegacyToolUrl('surveys', $courseId, $sessionId),
                'trackTools' => ['survey'],
            ];
        }

        // Attendance
        if ('attendance' === $toolTitle || 'attendances' === $t) {
            return [
                'key' => 'attendances',
                'label' => 'Attendances',
                'url' => $this->buildLegacyToolUrl('attendances', $courseId, $sessionId),
                'trackTools' => ['attendance'],
            ];
        }

        // Dropbox
        if ('dropbox' === $toolTitle || 'dropbox' === $t) {
            return [
                'key' => 'dropbox',
                'label' => 'Dropbox',
                'url' => $this->buildLegacyToolUrl('dropbox', $courseId, $sessionId),
                'trackTools' => ['dropbox'],
            ];
        }

        // Default: do not guess track tool names.
        return [
            'key' => $toolTitle,
            'label' => ucfirst($toolTitle),
            'url' => null,
            'trackTools' => [],
        ];
    }

    private function mapToolIdToTool(int $toolId, string $resourceTitles, int $courseId, int $sessionId): array
    {
        $titles = $this->normalizeTitle($resourceTitles);
        $toolTitle = $this->normalizeTitle($this->getToolTitleById($toolId) ?? '');

        // Primary mapping using tool.title (stable intent), not numeric ids.
        return match ($toolTitle) {
            'document' => [
                'key' => 'documents',
                'label' => 'Documents',
                'url' => $this->buildLegacyToolUrl('documents', $courseId, $sessionId),
            ],
            'learnpath' => [
                'key' => 'learnpaths',
                'label' => 'Learning paths',
                'url' => $this->buildLegacyToolUrl('learnpaths', $courseId, $sessionId),
            ],
            'quiz' => [
                'key' => 'exercises',
                'label' => 'Exercises',
                'url' => $this->buildLegacyToolUrl('exercises', $courseId, $sessionId),
            ],
            'forum' => [
                'key' => 'forums',
                'label' => 'Forums',
                'url' => $this->buildLegacyToolUrl('forums', $courseId, $sessionId),
            ],
            'student_publication' => [
                'key' => 'assignments',
                'label' => 'Assignments',
                'url' => $this->buildLegacyToolUrl('assignments', $courseId, $sessionId),
            ],
            'wiki' => [
                'key' => 'wikis',
                'label' => 'Wikis',
                'url' => $this->buildLegacyToolUrl('wikis', $courseId, $sessionId),
            ],
            'link' => [
                'key' => 'links',
                'label' => 'Links',
                'url' => $this->buildLegacyToolUrl('links', $courseId, $sessionId),
            ],
            'survey' => [
                'key' => 'surveys',
                'label' => 'Surveys',
                'url' => $this->buildLegacyToolUrl('surveys', $courseId, $sessionId),
            ],
            'gradebook' => [
                'key' => 'gradebook',
                'label' => 'Gradebook',
                'url' => $this->buildLegacyToolUrl('gradebook', $courseId, $sessionId),
            ],
            'group' => [
                'key' => 'groups',
                'label' => 'Groups',
                'url' => $this->buildLegacyToolUrl('groups', $courseId, $sessionId),
            ],
            default => $this->mapToolFallbackByTitles($toolId, $titles, $courseId, $sessionId),
        };
    }

    private function mapToolFallbackByTitles(int $toolId, string $titles, int $courseId, int $sessionId): array
    {
        if ('' === $titles) {
            return ['key' => 'other', 'label' => 'Other', 'url' => null];
        }

        if (str_contains($titles, 'files')) {
            return ['key' => 'documents', 'label' => 'Documents', 'url' => $this->buildLegacyToolUrl('documents', $courseId, $sessionId)];
        }

        if (str_contains($titles, 'lps') || str_contains($titles, 'lp_')) {
            return ['key' => 'learnpaths', 'label' => 'Learning paths', 'url' => $this->buildLegacyToolUrl('learnpaths', $courseId, $sessionId)];
        }

        if (str_contains($titles, 'exercises') || str_contains($titles, 'questions')) {
            return ['key' => 'exercises', 'label' => 'Exercises', 'url' => $this->buildLegacyToolUrl('exercises', $courseId, $sessionId)];
        }

        if (str_contains($titles, 'forums') || str_contains($titles, 'forum_')) {
            return ['key' => 'forums', 'label' => 'Forums', 'url' => $this->buildLegacyToolUrl('forums', $courseId, $sessionId)];
        }

        if (str_contains($titles, 'wikis') || str_contains($titles, 'wiki')) {
            return ['key' => 'wikis', 'label' => 'Wikis', 'url' => $this->buildLegacyToolUrl('wikis', $courseId, $sessionId)];
        }

        if (str_contains($titles, 'surveys') || str_contains($titles, 'survey')) {
            return ['key' => 'surveys', 'label' => 'Surveys', 'url' => $this->buildLegacyToolUrl('surveys', $courseId, $sessionId)];
        }

        return [
            'key' => 'tool_'.$toolId,
            'label' => 'Tool '.$toolId,
            'url' => null,
        ];
    }

    private function fetchNewContentToolsSince(
        int $userId,
        int $courseId,
        int $sessionId,
        DateTimeImmutable $since,
        int $limit
    ): array {
        $sinceStr = $since->format('Y-m-d H:i:s');
        $nowSql = 'NOW()';

        $sessionSql = '';
        if ($sessionId > 0) {
            $sessionSql = ' AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sid)';
        } else {
            $sessionSql = ' AND (rl.session_id IS NULL OR rl.session_id = 0)';
        }

        $sql = 'SELECT
                COALESCE(rt.tool_id, -1) AS tool_id,
                COUNT(*) AS cnt,
                MAX(GREATEST(COALESCE(rl.updated_at, rl.created_at), rl.created_at)) AS last_change,
                GROUP_CONCAT(DISTINCT COALESCE(NULLIF(TRIM(rt.title), \'\'), CONCAT(\'type_\', rn.resource_type_id)) ORDER BY rt.title SEPARATOR \', \') AS resource_titles
            FROM '.self::RESOURCE_LINK_TABLE.' rl
            INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
            LEFT JOIN resource_type rt ON rt.id = rn.resource_type_id
            WHERE rl.c_id = :cid
              AND rl.deleted_at IS NULL
              AND (rl.visibility IS NULL OR rl.visibility <> 0)
              AND (rl.start_visibility_at IS NULL OR rl.start_visibility_at <= '.$nowSql.')
              AND (rl.end_visibility_at IS NULL OR rl.end_visibility_at >= '.$nowSql.')
              AND (rl.updated_at > :since OR rl.created_at > :since)
              AND (rl.user_id IS NULL OR rl.user_id = 0 OR rl.user_id = :uid)
              AND (rl.group_id IS NULL OR rl.group_id = 0)
              AND (rl.usergroup_id IS NULL OR rl.usergroup_id = 0)
              '.$sessionSql.'
            GROUP BY COALESCE(rt.tool_id, -1)
            ORDER BY last_change DESC
            LIMIT '.(int) $limit;

        $this->log('fetchNewContentToolsSince: executing', [
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'since' => $sinceStr,
            'limit' => (int) $limit,
        ]);

        try {
            $rows = $this->connection->fetchAllAssociative($sql, [
                'cid' => $courseId,
                'uid' => $userId,
                'sid' => $sessionId,
                'since' => $sinceStr,
            ]);

            $this->log('fetchNewContentToolsSince: done', [
                'rows' => \count($rows),
            ]);

            return $rows;
        } catch (Throwable $e) {
            $this->log('fetchNewContentToolsSince: query failed', [
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'since' => $sinceStr,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Query resource_link entries changed since $since, grouped by resource_node.resource_type.
     */
    private function fetchNewContentTypesSince(
        int $userId,
        int $courseId,
        int $sessionId,
        DateTimeImmutable $since,
        int $limit
    ): array {
        $sinceStr = $since->format('Y-m-d H:i:s');
        $nowSql = 'NOW()';

        $sessionSql = '';
        if ($sessionId > 0) {
            $sessionSql = ' AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sid)';
        } else {
            $sessionSql = ' AND (rl.session_id IS NULL OR rl.session_id = 0)';
        }

        // SAFER:
        // We group by tool_id + resource_type.title to avoid mixing different types under the same tool_id (e.g. tool_id=11).
        $sql = 'SELECT
            COALESCE(rt.tool_id, -1) AS tool_id,
            COALESCE(rt.title, \'\') AS type_title,
            COUNT(DISTINCT rn.id) AS cnt,
            MAX(GREATEST(
                COALESCE(rn.updated_at, rn.created_at),
                rn.created_at,
                rl.created_at
            )) AS last_change
        FROM '.self::RESOURCE_LINK_TABLE.' rl
        INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
        LEFT JOIN resource_type rt ON rt.id = rn.resource_type_id
        WHERE rl.c_id = :cid
          AND rl.deleted_at IS NULL
          AND rl.visibility = 2
          AND (rl.start_visibility_at IS NULL OR rl.start_visibility_at <= '.$nowSql.')
          AND (rl.end_visibility_at IS NULL OR rl.end_visibility_at >= '.$nowSql.')
          AND (
              rn.updated_at > :since
              OR rn.created_at > :since
              OR rl.created_at > :since
          )
          AND (rl.user_id IS NULL OR rl.user_id = 0 OR rl.user_id = :uid)
          AND (rl.group_id IS NULL OR rl.group_id = 0)
          AND (rl.usergroup_id IS NULL OR rl.usergroup_id = 0)
          '.$sessionSql.'
        GROUP BY COALESCE(rt.tool_id, -1), COALESCE(rt.title, \'\')
        ORDER BY last_change DESC
        LIMIT '.(int) $limit;

        try {
            return $this->connection->fetchAllAssociative($sql, [
                'cid' => $courseId,
                'uid' => $userId,
                'sid' => $sessionId,
                'since' => $sinceStr,
            ]);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Builds a legacy PHP tool URL (best-effort).
     * If your platform uses different routes, adapt the mapping here only.
     */
    private function buildLegacyToolUrl(string $toolKey, int $courseId, int $sessionId): ?string
    {
        $base = \function_exists('api_get_path') ? (string) api_get_path(WEB_PATH) : '/';

        $qs = http_build_query([
            'cid' => $courseId,
            'sid' => $sessionId,
            'gid' => 0,
        ]);

        $course = $this->em->getRepository(Course::class)->find($courseId);
        $parentResourceNodeId = $course->getResourceNode()->getId();

        return match ($toolKey) {
            'course_home' => $base.'course/'.$courseId.'/home?'.$qs,
            'documents' => $base.'resources/document/'.$parentResourceNodeId.'/?'.$qs,
            'learnpaths' => $base.'resources/lp/'.$parentResourceNodeId.'/?'.$qs,
            'exercises' => $base.'main/exercise/exercise.php?'.$qs,
            'forums' => $base.'main/forum/index.php?'.$qs,
            'wikis' => $base.'main/wiki/index.php?'.$qs,
            'links' => $base.'resources/links/'.$parentResourceNodeId.'/?'.$qs,
            'surveys' => $base.'main/survey/survey_list.php?'.$qs,
            'gradebook' => $base.'main/gradebook/index.php?'.$qs,
            'attendances' => $base.'resources/attendance/'.$parentResourceNodeId.'/?'.$qs,
            'dropbox' => $base.'resources/dropbox/'.$parentResourceNodeId.'/received?'.$qs,

            default => null,
        };
    }

    private function getLastCourseAccessMapForCourses(int $userId, array $courseIds, int $sessionId): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        $courseIds = array_filter($courseIds, static fn (int $id) => $id > 0);

        if (empty($courseIds)) {
            return [];
        }

        // Prefer track_e_lastaccess if it exists.
        $tableLastAccess = 'track_e_lastaccess';
        if ($this->tableExists($tableLastAccess)) {
            $map = $this->fetchLastAccessMapFromTrackLastAccess($userId, $courseIds, $sessionId);

            // Optional fallback to session 0 only (never "any session").
            if ($sessionId > 0) {
                $missing = array_values(array_diff($courseIds, array_keys($map)));
                if (!empty($missing)) {
                    $fallback = $this->fetchLastAccessMapFromTrackLastAccess($userId, $missing, 0);
                    foreach ($fallback as $cid => $dt) {
                        if (!isset($map[$cid])) {
                            $map[$cid] = $dt;
                        }
                    }
                }
            }

            return $map;
        }

        // Fallback to track_e_course_access if it exists.
        $tableCourseAccess = 'track_e_course_access';
        if ($this->tableExists($tableCourseAccess)) {
            $map = $this->fetchLastAccessMapFromTrackCourseAccess($userId, $courseIds, $sessionId);

            if ($sessionId > 0) {
                $missing = array_values(array_diff($courseIds, array_keys($map)));
                if (!empty($missing)) {
                    $fallback = $this->fetchLastAccessMapFromTrackCourseAccess($userId, $missing, 0);
                    foreach ($fallback as $cid => $dt) {
                        if (!isset($map[$cid])) {
                            $map[$cid] = $dt;
                        }
                    }
                }
            }

            return $map;
        }

        return [];
    }

    private function fetchLastAccessMapFromTrackLastAccess(int $userId, array $courseIds, int $sessionId): array
    {
        try {
            $sql = 'SELECT c_id, MAX(access_date) AS last_access
                    FROM track_e_lastaccess
                    WHERE access_user_id = :uid
                      AND session_id = :sid
                      AND c_id IN (:cids)
                    GROUP BY c_id';

            $rows = $this->connection->fetchAllAssociative($sql, [
                'uid' => $userId,
                'sid' => $sessionId,
                'cids' => $courseIds,
            ], [
                'cids' => ArrayParameterType::INTEGER,
            ]);

            $out = [];
            foreach ($rows as $row) {
                $cid = (int) ($row['c_id'] ?? 0);
                $raw = (string) ($row['last_access'] ?? '');
                if ($cid > 0 && '' !== trim($raw)) {
                    $out[$cid] = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw) ?: new DateTimeImmutable($raw);
                }
            }

            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function fetchLastAccessMapFromTrackCourseAccess(int $userId, array $courseIds, int $sessionId): array
    {
        try {
            $sql = 'SELECT c_id, MAX(login_course_date) AS last_access
                    FROM track_e_course_access
                    WHERE user_id = :uid
                      AND session_id = :sid
                      AND c_id IN (:cids)
                    GROUP BY c_id';

            $rows = $this->connection->fetchAllAssociative($sql, [
                'uid' => $userId,
                'sid' => $sessionId,
                'cids' => $courseIds,
            ], [
                'cids' => ArrayParameterType::INTEGER,
            ]);

            $out = [];
            foreach ($rows as $row) {
                $cid = (int) ($row['c_id'] ?? 0);
                $raw = (string) ($row['last_access'] ?? '');
                if ($cid > 0 && '' !== trim($raw)) {
                    $out[$cid] = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw) ?: new DateTimeImmutable($raw);
                }
            }

            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function getResourceLinkLastChangeMapForCourses(int $userId, array $courseIds, int $sessionId): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        $courseIds = array_filter($courseIds, static fn (int $id) => $id > 0);

        if (empty($courseIds)) {
            return [];
        }

        $nowSql = 'NOW()';

        // Session filter:
        // - When sessionId > 0: accept global rows (NULL/0) OR session-specific rows
        // - When sessionId = 0: accept only global rows (NULL/0)
        $sessionSql = '';
        if ($sessionId > 0) {
            $sessionSql = ' AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sid)';
        } else {
            $sessionSql = ' AND (rl.session_id IS NULL OR rl.session_id = 0)';
        }

        // We compute the latest change timestamp per course (created_at/updated_at).
        $sql = 'SELECT
            rl.c_id,
            MAX(GREATEST(
                COALESCE(rn.updated_at, rn.created_at),
                rn.created_at,
                rl.created_at
            )) AS last_change
        FROM '.self::RESOURCE_LINK_TABLE.' rl
        INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
        WHERE rl.c_id IN (:cids)
          AND rl.deleted_at IS NULL
          AND rl.visibility = 2
          AND (rl.start_visibility_at IS NULL OR rl.start_visibility_at <= '.$nowSql.')
          AND (rl.end_visibility_at IS NULL OR rl.end_visibility_at >= '.$nowSql.')
          AND (rl.user_id IS NULL OR rl.user_id = 0 OR rl.user_id = :uid)
          AND (rl.group_id IS NULL OR rl.group_id = 0)
          AND (rl.usergroup_id IS NULL OR rl.usergroup_id = 0)
          '.$sessionSql.'
        GROUP BY rl.c_id';

        try {
            $rows = $this->connection->fetchAllAssociative($sql, [
                'cids' => $courseIds,
                'uid' => $userId,
                'sid' => $sessionId,
            ], [
                'cids' => ArrayParameterType::INTEGER,
            ]);

            $out = [];
            foreach ($rows as $row) {
                $cid = (int) ($row['c_id'] ?? 0);
                $raw = (string) ($row['last_change'] ?? '');
                if ($cid > 0 && '' !== trim($raw)) {
                    $out[$cid] = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw) ?: new DateTimeImmutable($raw);
                }
            }

            return $out;
        } catch (DbalException|Throwable $e) {
            return [];
        }
    }

    private function emptyResult(): array
    {
        return [
            'progress' => 0.0,
            'score' => null,
            'bestScore' => null,
            'timeSpentSeconds' => null,
            'certificateAvailable' => false,
            'completed' => false,
            'hasNewContent' => false,
        ];
    }

    private function sanitizeResult(array $result): array
    {
        $progress = isset($result['progress']) && is_numeric($result['progress']) ? (float) $result['progress'] : 0.0;
        $progress = $this->clampPercent($progress);

        $score = (isset($result['score']) && is_numeric($result['score'])) ? (float) $result['score'] : null;
        $bestScore = (isset($result['bestScore']) && is_numeric($result['bestScore'])) ? (float) $result['bestScore'] : null;

        $time = (isset($result['timeSpentSeconds']) && is_numeric($result['timeSpentSeconds'])) ? (int) $result['timeSpentSeconds'] : null;
        if (null !== $time && $time < 0) {
            $time = null;
        }

        $cert = (bool) ($result['certificateAvailable'] ?? false);
        $completed = (bool) ($result['completed'] ?? ($cert || $progress >= 100.0));
        $hasNew = (bool) ($result['hasNewContent'] ?? false);

        return [
            'progress' => $progress,
            'score' => null !== $score ? round($score, 2) : null,
            'bestScore' => null !== $bestScore ? round($bestScore, 2) : null,
            'timeSpentSeconds' => $time,
            'certificateAvailable' => $cert,
            'completed' => $completed,
            'hasNewContent' => $hasNew,
        ];
    }

    private function applyFlagsToResult(array $result, array $flags): array
    {
        if (!$flags['progress']) {
            $result['progress'] = 0.0;
        }

        if (!$flags['score']) {
            $result['score'] = null;
            $result['bestScore'] = null;
        }

        if (!$flags['certificate']) {
            $result['certificateAvailable'] = false;
        }

        // Completed only makes sense if at least one completion signal is enabled.
        if (!($flags['progress'] || $flags['certificate'])) {
            $result['completed'] = false;
        } else {
            $progress = is_numeric($result['progress'] ?? null) ? (float) $result['progress'] : 0.0;
            $cert = (bool) ($result['certificateAvailable'] ?? false);

            $result['completed'] = ($flags['certificate'] && $cert) || ($flags['progress'] && $progress >= 100.0);
        }

        return $result;
    }

    private function clampPercent(float $value): float
    {
        $v = max(0.0, min(100.0, $value));

        return round($v, 1);
    }

    private function computeProgress(int $userId, Course $course, ?Session $session): float
    {
        $sessionId = $session?->getId() ?? 0;
        $this->log('computeProgress: start', [
            'user_id' => $userId,
            'course_id' => (int) $course->getId(),
            'session_id' => $sessionId,
        ]);

        try {
            $value = Tracking::get_avg_student_progress(
                $userId,
                $course,
                [],
                $session,
                false,
                false
            );

            $this->log('computeProgress: Tracking returned', [
                'raw' => $value,
                'is_numeric' => is_numeric($value),
            ]);

            if (is_numeric($value)) {
                return $this->clampPercent((float) $value);
            }
        } catch (Throwable $e) {
            $this->log('computeProgress: exception (returning 0)', [
                'exception' => $e->getMessage(),
            ]);
        }

        return 0.0;
    }

    private function computeScoreLatest(int $userId, Course $course, ?Session $session): ?float
    {
        $sessionId = $session?->getId() ?? 0;
        $this->log('computeScoreLatest: start', [
            'user_id' => $userId,
            'course_id' => (int) $course->getId(),
            'session_id' => $sessionId,
        ]);

        try {
            $value = Tracking::get_avg_student_score(
                $userId,
                $course,
                [],
                $session,
                false,
                true,   // get_only_latest_attempt_results
                false   // getOnlyBestAttempt
            );

            $this->log('computeScoreLatest: Tracking returned', [
                'raw' => $value,
                'type' => \gettype($value),
                'is_numeric' => is_numeric($value),
            ]);

            if (is_numeric($value)) {
                return round((float) $value, 2);
            }

            // Common legacy return is '-' (string) when no score can be computed.
            if ('-' === $value) {
                $this->log('computeScoreLatest: legacy "-" returned (no score)', []);
            }
        } catch (Throwable $e) {
            $this->log('computeScoreLatest: exception (returning null)', [
                'exception' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function computeScoreBest(int $userId, Course $course, ?Session $session): ?float
    {
        $sessionId = $session?->getId() ?? 0;
        $this->log('computeScoreBest: start', [
            'user_id' => $userId,
            'course_id' => (int) $course->getId(),
            'session_id' => $sessionId,
        ]);

        try {
            $value = Tracking::get_avg_student_score(
                $userId,
                $course,
                [],
                $session,
                false,
                false,  // get_only_latest_attempt_results
                true    // getOnlyBestAttempt
            );

            $this->log('computeScoreBest: Tracking returned', [
                'raw' => $value,
                'type' => \gettype($value),
                'is_numeric' => is_numeric($value),
            ]);

            if (is_numeric($value)) {
                return round((float) $value, 2);
            }

            if ('-' === $value) {
                $this->log('computeScoreBest: legacy "-" returned (no score)', []);
            }
        } catch (Throwable $e) {
            $this->log('computeScoreBest: exception (returning null)', [
                'exception' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function computeTimeSpentSeconds(int $userId, Course $course, int $sessionId): ?int
    {
        $this->log('computeTimeSpentSeconds: start', [
            'user_id' => $userId,
            'course_code' => (string) $course->getCode(),
            'session_id' => $sessionId,
        ]);

        try {
            $courseCode = $course->getCode();
            $value = Tracking::get_time_spent_on_the_course($userId, $courseCode, $sessionId);

            $this->log('computeTimeSpentSeconds: Tracking returned', [
                'raw' => $value,
                'is_numeric' => is_numeric($value),
            ]);

            $n = is_numeric($value) ? (int) $value : null;

            return (null !== $n && $n >= 0) ? $n : null;
        } catch (Throwable $e) {
            $this->log('computeTimeSpentSeconds: exception (returning null)', [
                'exception' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function computeCertificateAvailable(int $userId, Course $course, int $sessionId): bool
    {
        $this->log('computeCertificateAvailable: start', [
            'user_id' => $userId,
            'course_id' => (int) $course->getId(),
            'session_id' => $sessionId,
        ]);

        $categoryId = $this->findRootGradebookCategoryId($course, $sessionId);

        $this->log('computeCertificateAvailable: root category id resolved', [
            'category_id' => $categoryId,
        ]);

        if (!$categoryId) {
            return false;
        }

        try {
            $category = Category::load($categoryId);
            if (!$category) {
                $this->log('computeCertificateAvailable: Category::load returned empty', [
                    'category_id' => $categoryId,
                ]);

                return false;
            }

            if (method_exists($category, 'setCourseId')) {
                $category->setCourseId((int) $course->getId());
            }
            if (method_exists($category, 'set_session_id')) {
                $category->set_session_id($sessionId);
            }

            if (method_exists($category, 'is_certificate_available')) {
                $available = (bool) $category->is_certificate_available($userId);

                $this->log('computeCertificateAvailable: is_certificate_available returned', [
                    'available' => $available,
                ]);

                return $available;
            }

            $this->log('computeCertificateAvailable: missing is_certificate_available method', []);
        } catch (Throwable $e) {
            $this->log('computeCertificateAvailable: exception', [
                'exception' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Detects if the user has "new content" since their last course access.
     *
     * Safe and standard:
     * - Uses resource_link (no legacy content tables).
     * - Uses track_e_lastaccess first (Chamilo-style tool access), then track_e_course_access as fallback.
     * - If anything fails, returns false.
     */
    private function computeHasNewContent(int $userId, Course $course, int $sessionId): bool
    {
        $courseId = (int) $course->getId();

        $this->log('computeHasNewContent: start', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'session_id' => $sessionId,
        ]);

        try {
            $firstCourseAccess = $this->getFirstCourseAccessDateTime($userId, $courseId, $sessionId);
            if (!$firstCourseAccess instanceof DateTimeImmutable) {
                $this->log('computeHasNewContent: no first course access found (returning false)', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'session_id' => $sessionId,
                ]);

                return false;
            }

            $toolAccessMap = $this->fetchLastAccessPerToolMapFromTrackLastAccess($userId, $courseId, $sessionId);
            $typeRows = $this->fetchLastChangeByTypeForCourse($userId, $courseId, $sessionId);

            foreach ($typeRows as $row) {
                $toolId = (int) ($row['tool_id'] ?? -1);
                $typeTitle = (string) ($row['type_title'] ?? '');
                $lastChange = $this->parseDateTime((string) ($row['last_change'] ?? ''));

                if (!$lastChange instanceof DateTimeImmutable) {
                    continue;
                }

                $toolInfo = $this->mapToolIdAndTypeTitleToTool($toolId, $typeTitle, $courseId, $sessionId);
                if (!$toolInfo || empty($toolInfo['trackTools'])) {
                    continue;
                }

                $baseline = $this->pickBestToolAccessOrFallback(
                    $toolAccessMap,
                    (array) $toolInfo['trackTools'],
                    $firstCourseAccess
                );

                if ($lastChange->getTimestamp() > $baseline->getTimestamp()) {
                    return true;
                }
            }

            return false;
        } catch (Throwable $e) {
            $this->log('computeHasNewContent: exception (returning false)', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Checks if there exists any resource_link entry newer than $since.
     */
    private function existsNewResourceLinkSince(
        int $courseId,
        int $userId,
        int $sessionId,
        DateTimeImmutable $since
    ): bool {
        $sinceStr = $since->format('Y-m-d H:i:s');
        $nowSql = 'NOW()';

        // Session filter:
        // - When sessionId > 0: accept global rows (NULL/0) OR session-specific rows
        // - When sessionId = 0: accept only global rows (NULL/0)
        $sessionSql = '';
        if ($sessionId > 0) {
            $sessionSql = ' AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sid)';
        } else {
            $sessionSql = ' AND (rl.session_id IS NULL OR rl.session_id = 0)';
        }

        // Visibility:
        // In practice, resource_link.visibility is not always binary; do not hardcode "= 1".
        // Treat any non-zero visibility as "not hidden" for this detection.
        $sql = 'SELECT 1
            FROM '.self::RESOURCE_LINK_TABLE.' rl
            INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
            WHERE rl.c_id = :cid
              AND rl.deleted_at IS NULL
              AND rl.visibility = 2
              AND (rl.start_visibility_at IS NULL OR rl.start_visibility_at <= '.$nowSql.')
              AND (rl.end_visibility_at IS NULL OR rl.end_visibility_at >= '.$nowSql.')
              AND (
                  rn.updated_at > :since
                  OR rn.created_at > :since
                  OR rl.created_at > :since
              )
              AND (rl.user_id IS NULL OR rl.user_id = 0 OR rl.user_id = :uid)
              AND (rl.group_id IS NULL OR rl.group_id = 0)
              AND (rl.usergroup_id IS NULL OR rl.usergroup_id = 0)
              '.$sessionSql.'
            LIMIT 1';

        $params = [
            'cid' => $courseId,
            'uid' => $userId,
            'sid' => $sessionId,
            'since' => $sinceStr,
        ];

        $this->log('existsNewResourceLinkSince: executing probe query', [
            'course_id' => $courseId,
            'session_id' => $sessionId,
            'since' => $sinceStr,
        ]);

        try {
            $row = $this->connection->fetchOne($sql, $params);
            $hasNew = false !== $row && null !== $row;

            // Extra debug context (only when showDebug is enabled).
            if ($this->showDebug) {
                $sqlAgg = 'SELECT
                              COUNT(*) AS cnt,
                              MAX(rl.updated_at) AS max_updated_at,
                              MAX(rl.created_at) AS max_created_at
                           FROM '.self::RESOURCE_LINK_TABLE.' rl
                           WHERE rl.c_id = :cid
                             AND rl.deleted_at IS NULL
                             AND (rl.visibility IS NULL OR rl.visibility <> 0)
                             AND (rl.start_visibility_at IS NULL OR rl.start_visibility_at <= '.$nowSql.')
                             AND (rl.end_visibility_at IS NULL OR rl.end_visibility_at >= '.$nowSql.')
                             AND (rl.user_id IS NULL OR rl.user_id = 0 OR rl.user_id = :uid)
                             AND (rl.group_id IS NULL OR rl.group_id = 0)
                             AND (rl.usergroup_id IS NULL OR rl.usergroup_id = 0)
                             '.$sessionSql;

                $agg = $this->connection->fetchAssociative($sqlAgg, [
                    'cid' => $courseId,
                    'uid' => $userId,
                    'sid' => $sessionId,
                ]);

                $this->log('existsNewResourceLinkSince: probe result', [
                    'course_id' => $courseId,
                    'session_id' => $sessionId,
                    'since' => $sinceStr,
                    'has_new' => $hasNew,
                    'agg' => $agg ?: null,
                ]);
            }

            return $hasNew;
        } catch (DbalException|Throwable $e) {
            $this->log('existsNewResourceLinkSince: query failed (returning false)', [
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'since' => $sinceStr,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reads the last course access date for a user.
     *
     * Strategy:
     * 1) track_e_lastaccess (tool-level, Chamilo-style)
     * 2) track_e_course_access (fallback)
     */
    private function getLastCourseAccessDateTime(int $userId, int $courseId, int $sessionId): ?DateTimeImmutable
    {
        $tableLastAccess = 'track_e_lastaccess';
        if ($this->tableExists($tableLastAccess)) {
            $this->log('getLastCourseAccessDateTime: using track_e_lastaccess', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
            ]);

            $dt = $this->getLastAccessFromTrackLastAccess($userId, $courseId, $sessionId);
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }

            // Optional fallback to session 0 only (never "any session").
            if ($sessionId > 0) {
                $this->log('getLastCourseAccessDateTime: fallback to session 0 in track_e_lastaccess', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'session_id' => $sessionId,
                ]);

                return $this->getLastAccessFromTrackLastAccess($userId, $courseId, 0);
            }

            return null;
        }

        $tableCourseAccess = 'track_e_course_access';
        if ($this->tableExists($tableCourseAccess)) {
            $this->log('getLastCourseAccessDateTime: using track_e_course_access', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
            ]);

            $dt = $this->getLastAccessFromTrackCourseAccess($userId, $courseId, $sessionId);
            if ($dt instanceof DateTimeImmutable) {
                return $dt;
            }

            if ($sessionId > 0) {
                $this->log('getLastCourseAccessDateTime: fallback to session 0 in track_e_course_access', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'session_id' => $sessionId,
                ]);

                return $this->getLastAccessFromTrackCourseAccess($userId, $courseId, 0);
            }

            return null;
        }

        $this->log('getLastCourseAccessDateTime: no tracking table found', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'session_id' => $sessionId,
        ]);

        return null;
    }

    private function getLastAccessFromTrackLastAccess(int $userId, int $courseId, int $sessionId): ?DateTimeImmutable
    {
        try {
            $sql = 'SELECT MAX(access_date) AS last_access
                    FROM track_e_lastaccess
                    WHERE access_user_id = :uid AND c_id = :cid AND session_id = :sid';

            $raw = $this->connection->fetchOne($sql, [
                'uid' => $userId,
                'cid' => $courseId,
                'sid' => $sessionId,
            ]);

            if (!\is_string($raw) || '' === trim($raw)) {
                $this->log('getLastCourseAccessDateTime: track_e_lastaccess empty', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'session_id' => $sessionId,
                ]);

                return null;
            }

            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw) ?: new DateTimeImmutable($raw);

            $this->log('getLastCourseAccessDateTime: resolved from track_e_lastaccess', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'last_access' => $dt->format('Y-m-d H:i:s'),
            ]);

            return $dt;
        } catch (Throwable $e) {
            $this->log('getLastCourseAccessDateTime: track_e_lastaccess query failed', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getLastAccessFromTrackCourseAccess(int $userId, int $courseId, int $sessionId): ?DateTimeImmutable
    {
        try {
            $sql = 'SELECT MAX(login_course_date) AS last_access
                    FROM track_e_course_access
                    WHERE user_id = :uid AND c_id = :cid AND session_id = :sid';

            $raw = $this->connection->fetchOne($sql, [
                'uid' => $userId,
                'cid' => $courseId,
                'sid' => $sessionId,
            ]);

            if (!\is_string($raw) || '' === trim($raw)) {
                $this->log('getLastCourseAccessDateTime: track_e_course_access empty', [
                    'user_id' => $userId,
                    'course_id' => $courseId,
                    'session_id' => $sessionId,
                ]);

                return null;
            }

            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw) ?: new DateTimeImmutable($raw);

            $this->log('getLastCourseAccessDateTime: resolved from track_e_course_access', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'last_access' => $dt->format('Y-m-d H:i:s'),
            ]);

            return $dt;
        } catch (Throwable $e) {
            $this->log('getLastCourseAccessDateTime: track_e_course_access query failed', [
                'user_id' => $userId,
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $sm = method_exists($this->connection, 'createSchemaManager')
                ? $this->connection->createSchemaManager()
                : $this->connection->getSchemaManager();

            return $sm->tablesExist([$tableName]);
        } catch (Throwable $e) {
            $this->log('tableExists: schema lookup failed (assuming false)', [
                'table' => $tableName,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function findRootGradebookCategoryId(Course $course, int $sessionId): ?int
    {
        $courseId = (int) $course->getId();

        $this->log('findRootGradebookCategoryId: start', [
            'course_id' => $courseId,
            'session_id' => $sessionId,
        ]);

        try {
            $qb = $this->em->createQueryBuilder()
                ->select('gc.id')
                ->from(GradebookCategory::class, 'gc')
                ->andWhere('gc.course = :course')
                ->setParameter('course', $course)
                // Root category: parent is NULL, but support legacy parent_id=0 safely
                ->andWhere('(gc.parent IS NULL OR IDENTITY(gc.parent) = 0)')
            ;

            if ($sessionId > 0) {
                // Session specific: match exactly (support id compare even if relation hydration is tricky)
                $qb->andWhere('IDENTITY(gc.session) = :sid')
                    ->setParameter('sid', $sessionId)
                ;
            } else {
                // Non-session: allow NULL or 0
                $qb->andWhere('(gc.session IS NULL OR IDENTITY(gc.session) = 0)');
            }

            $qb->orderBy('gc.id', 'DESC')
                ->setMaxResults(1)
            ;

            $id = (int) $qb->getQuery()->getSingleScalarResult();

            $this->log('findRootGradebookCategoryId: resolved', [
                'category_id' => $id,
            ]);

            return $id;
        } catch (NonUniqueResultException|NoResultException $e) {
            $this->log('findRootGradebookCategoryId: no result', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        } catch (Throwable $e) {
            $this->log('findRootGradebookCategoryId: exception', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getStudentInfoFlags(): array
    {
        // Defaults (same intent as v1 JSON structure).
        $defaults = [
            'score' => false,
            'progress' => false,
            'certificate' => false,
        ];

        try {
            $raw = $this->settingsManager->getSetting('course.course_student_info');
        } catch (Throwable $e) {
            $this->log('getStudentInfoFlags: failed reading setting (using defaults)', [
                'exception' => $e->getMessage(),
            ]);

            return $defaults;
        }

        $data = null;

        if (\is_array($raw)) {
            $data = $raw;
        } elseif (\is_string($raw) && '' !== trim($raw)) {
            $decoded = json_decode($raw, true);
            if (\is_array($decoded)) {
                $data = $decoded;
            }
        }

        if (!\is_array($data)) {
            return $defaults;
        }

        return [
            'score' => $this->toBool($data['score'] ?? false),
            'progress' => $this->toBool($data['progress'] ?? false),
            'certificate' => $this->toBool($data['certificate'] ?? false),
        ];
    }

    private function toBool(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        if (\is_string($value)) {
            $v = strtolower(trim($value));

            return 'true' === $v || '1' === $v || 'yes' === $v || 'on' === $v;
        }

        return false;
    }

    private function isAnyFlagEnabled(array $flags): bool
    {
        return (bool) (($flags['progress'] ?? false) || ($flags['score'] ?? false) || ($flags['certificate'] ?? false));
    }

    private function flagsBitmask(array $flags): int
    {
        $m = 0;
        if (!empty($flags['progress'])) {
            $m |= 1;
        }
        if (!empty($flags['score'])) {
            $m |= 2;
        }
        if (!empty($flags['certificate'])) {
            $m |= 4;
        }

        return $m;
    }

    private function isHasNewContentLog(string $message): bool
    {
        $needles = [
            'computeHasNewContent',
            'existsNewResourceLinkSince',
            'getLastCourseAccessDateTime',
            'track_e_lastaccess',
            'track_e_course_access',
            'getNewContentToolsForCourse',
            'fetchNewContentToolsSince',
            'mapToolIdToTool',
            'mapToolIdAndTypeTitleToTool',
            'fetchLastChangeByTypeForCourse',
            'fetchLastChangeByTypeForCourses',
            'fetchNewContentTypeCountSince',
        ];

        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function log(string $message, array $context = []): void
    {
        // Only log when debug flag is enabled.
        if (!$this->showDebug) {
            return;
        }

        // Only keep logs from the hasNewContent process to avoid noise.
        if (!$this->isHasNewContentLog($message)) {
            return;
        }

        if (self::$logCount >= self::LOG_LIMIT) {
            return;
        }
        self::$logCount++;

        $payload = '';
        if (!empty($context)) {
            $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (false !== $json) {
                $payload = ' '.$json;
            }
        }

        error_log(self::LOG_PREFIX.' '.$message.$payload);
    }

    private function parseDateTime(string $raw): ?DateTimeImmutable
    {
        $raw = trim($raw);
        if ('' === $raw) {
            return null;
        }

        return DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw) ?: new DateTimeImmutable($raw);
    }

    private function getFirstCourseAccessDateTime(int $userId, int $courseId, int $sessionId): ?DateTimeImmutable
    {
        // First prefer track_e_lastaccess (tool-level), taking MIN() across tools.
        if ($this->tableExists('track_e_lastaccess')) {
            $sql = 'SELECT MIN(access_date) AS first_access
                FROM track_e_lastaccess
                WHERE access_user_id = :uid AND c_id = :cid AND session_id = :sid';

            try {
                $raw = $this->connection->fetchOne($sql, [
                    'uid' => $userId,
                    'cid' => $courseId,
                    'sid' => $sessionId,
                ]);

                if (\is_string($raw) && '' !== trim($raw)) {
                    return $this->parseDateTime($raw);
                }
            } catch (Throwable) {
                // Ignore and fallback
            }
        }

        // Fallback: track_e_course_access MIN(login_course_date)
        if ($this->tableExists('track_e_course_access')) {
            $sql = 'SELECT MIN(login_course_date) AS first_access
                FROM track_e_course_access
                WHERE user_id = :uid AND c_id = :cid AND session_id = :sid';

            try {
                $raw = $this->connection->fetchOne($sql, [
                    'uid' => $userId,
                    'cid' => $courseId,
                    'sid' => $sessionId,
                ]);

                if (\is_string($raw) && '' !== trim($raw)) {
                    return $this->parseDateTime($raw);
                }
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private function fetchLastAccessPerToolMapFromTrackLastAccess(int $userId, int $courseId, int $sessionId): array
    {
        if (!$this->tableExists('track_e_lastaccess')) {
            return [];
        }

        $sql = 'SELECT access_tool, MAX(access_date) AS last_access
            FROM track_e_lastaccess
            WHERE access_user_id = :uid AND c_id = :cid AND session_id = :sid
            GROUP BY access_tool';

        try {
            $rows = $this->connection->fetchAllAssociative($sql, [
                'uid' => $userId,
                'cid' => $courseId,
                'sid' => $sessionId,
            ]);

            $out = [];
            foreach ($rows as $r) {
                $tool = (string) ($r['access_tool'] ?? '');
                $raw = (string) ($r['last_access'] ?? '');
                if ('' === trim($tool) || '' === trim($raw)) {
                    continue;
                }
                $dt = $this->parseDateTime($raw);
                if ($dt) {
                    $out[$tool] = $dt;
                }
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }

    private function pickBestToolAccessOrFallback(array $toolAccessMap, array $trackTools, DateTimeImmutable $fallback): DateTimeImmutable
    {
        $best = null;

        foreach ($trackTools as $name) {
            $name = \is_string($name) ? trim($name) : '';
            if ('' === $name) {
                continue;
            }
            $dt = $toolAccessMap[$name] ?? null;
            if ($dt instanceof DateTimeImmutable) {
                if (null === $best || $dt->getTimestamp() > $best->getTimestamp()) {
                    $best = $dt;
                }
            }
        }

        return $best ?? $fallback;
    }

    /**
     * Fetch the latest change timestamp per (tool_title, resource_type.title) for ONE course.
     */
    private function fetchLastChangeByTypeForCourse(int $userId, int $courseId, int $sessionId): array
    {
        $nowSql = 'NOW()';

        $sessionSql = ($sessionId > 0)
            ? ' AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sid)'
            : ' AND (rl.session_id IS NULL OR rl.session_id = 0)';

        $sql = 'SELECT
        COALESCE(rt.tool_id, -1) AS tool_id,
        COALESCE(rt.title, \'\') AS type_title,
        MAX(GREATEST(
            COALESCE(rn.updated_at, rn.created_at),
            rn.created_at,
            rl.created_at
        )) AS last_change
    FROM '.self::RESOURCE_LINK_TABLE.' rl
    INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
    LEFT JOIN resource_type rt ON rt.id = rn.resource_type_id
    WHERE rl.c_id = :cid
      AND rl.deleted_at IS NULL
      AND rl.visibility = 2
      AND (rl.start_visibility_at IS NULL OR rl.start_visibility_at <= '.$nowSql.')
      AND (rl.end_visibility_at IS NULL OR rl.end_visibility_at >= '.$nowSql.')
      AND (rl.user_id IS NULL OR rl.user_id = 0 OR rl.user_id = :uid)
      AND (rl.group_id IS NULL OR rl.group_id = 0)
      AND (rl.usergroup_id IS NULL OR rl.usergroup_id = 0)
      '.$sessionSql.'
    GROUP BY COALESCE(rt.tool_id, -1), COALESCE(rt.title, \'\')';

        try {
            return $this->connection->fetchAllAssociative($sql, [
                'cid' => $courseId,
                'uid' => $userId,
                'sid' => $sessionId,
            ]);
        } catch (Throwable $e) {
            $this->log('fetchLastChangeByTypeForCourse: query failed', [
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Count a specific (tool_title, type_title) since $since.
     * We keep tool_title as an OPTIONAL filter, because some installations may have
     * types mapped under unexpected tools (we don't want false negatives).
     */
    private function fetchNewContentTypeCountSince(
        int $userId,
        int $courseId,
        int $sessionId,
        DateTimeImmutable $since,
        int $toolId,
        string $typeTitle
    ): array {
        $sinceStr = $since->format('Y-m-d H:i:s');
        $nowSql = 'NOW()';

        $sessionSql = ($sessionId > 0)
            ? ' AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sid)'
            : ' AND (rl.session_id IS NULL OR rl.session_id = 0)';

        $sql = 'SELECT
        COUNT(DISTINCT rn.id) AS cnt,
        MAX(GREATEST(
            COALESCE(rn.updated_at, rn.created_at),
            rn.created_at,
            rl.created_at
        )) AS last_change
    FROM '.self::RESOURCE_LINK_TABLE.' rl
    INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
    LEFT JOIN resource_type rt ON rt.id = rn.resource_type_id
    WHERE rl.c_id = :cid
      AND rl.deleted_at IS NULL
      AND rl.visibility = 2
      AND (rl.start_visibility_at IS NULL OR rl.start_visibility_at <= '.$nowSql.')
      AND (rl.end_visibility_at IS NULL OR rl.end_visibility_at >= '.$nowSql.')
      AND (
            rn.updated_at > :since
         OR rn.created_at > :since
         OR rl.created_at > :since
      )
      AND COALESCE(rt.tool_id, -1) = :tool_id
      AND COALESCE(rt.title, \'\') = :type_title
      AND (rl.user_id IS NULL OR rl.user_id = 0 OR rl.user_id = :uid)
      AND (rl.group_id IS NULL OR rl.group_id = 0)
      AND (rl.usergroup_id IS NULL OR rl.usergroup_id = 0)
      '.$sessionSql;

        try {
            $row = $this->connection->fetchAssociative($sql, [
                'cid' => $courseId,
                'uid' => $userId,
                'sid' => $sessionId,
                'since' => $sinceStr,
                'tool_id' => $toolId,
                'type_title' => $typeTitle,
            ]);

            return \is_array($row) ? $row : [];
        } catch (Throwable $e) {
            $this->log('fetchNewContentTypeCountSince: query failed', [
                'course_id' => $courseId,
                'session_id' => $sessionId,
                'since' => $sinceStr,
                'tool_id' => $toolId,
                'type_title' => $typeTitle,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function fetchFirstCourseAccessMapFromTrackLastAccess(int $userId, array $courseIds, int $sessionId): array
    {
        if (!$this->tableExists('track_e_lastaccess')) {
            return [];
        }

        $sql = 'SELECT c_id, MIN(access_date) AS first_access
            FROM track_e_lastaccess
            WHERE access_user_id = :uid
              AND session_id = :sid
              AND c_id IN (:cids)
            GROUP BY c_id';

        try {
            $rows = $this->connection->fetchAllAssociative($sql, [
                'uid' => $userId,
                'sid' => $sessionId,
                'cids' => $courseIds,
            ], [
                'cids' => ArrayParameterType::INTEGER,
            ]);

            $out = [];
            foreach ($rows as $row) {
                $cid = (int) ($row['c_id'] ?? 0);
                $raw = (string) ($row['first_access'] ?? '');
                $dt = $this->parseDateTime($raw);
                if ($cid > 0 && $dt) {
                    $out[$cid] = $dt;
                }
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }

    private function fetchLastAccessPerToolMapForCourses(int $userId, array $courseIds, int $sessionId): array
    {
        if (!$this->tableExists('track_e_lastaccess')) {
            return [];
        }

        $sql = 'SELECT c_id, access_tool, MAX(access_date) AS last_access
            FROM track_e_lastaccess
            WHERE access_user_id = :uid
              AND session_id = :sid
              AND c_id IN (:cids)
            GROUP BY c_id, access_tool';

        try {
            $rows = $this->connection->fetchAllAssociative($sql, [
                'uid' => $userId,
                'sid' => $sessionId,
                'cids' => $courseIds,
            ], [
                'cids' => ArrayParameterType::INTEGER,
            ]);

            $out = [];
            foreach ($rows as $r) {
                $cid = (int) ($r['c_id'] ?? 0);
                $tool = (string) ($r['access_tool'] ?? '');
                $raw = (string) ($r['last_access'] ?? '');

                if ($cid <= 0 || '' === trim($tool)) {
                    continue;
                }

                $dt = $this->parseDateTime($raw);
                if ($dt) {
                    $out[$cid] ??= [];
                    $out[$cid][$tool] = $dt;
                }
            }

            return $out;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Fetch the latest change timestamp per (course_id, tool_title, resource_type.title) for MANY courses.
     */
    private function fetchLastChangeByTypeForCourses(int $userId, array $courseIds, int $sessionId): array
    {
        $nowSql = 'NOW()';

        $sessionSql = ($sessionId > 0)
            ? ' AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sid)'
            : ' AND (rl.session_id IS NULL OR rl.session_id = 0)';

        $sql = 'SELECT
        rl.c_id,
        COALESCE(rt.tool_id, -1) AS tool_id,
        COALESCE(rt.title, \'\') AS type_title,
        MAX(GREATEST(
            COALESCE(rn.updated_at, rn.created_at),
            rn.created_at,
            rl.created_at
        )) AS last_change
    FROM '.self::RESOURCE_LINK_TABLE.' rl
    INNER JOIN resource_node rn ON rn.id = rl.resource_node_id
    LEFT JOIN resource_type rt ON rt.id = rn.resource_type_id
    WHERE rl.c_id IN (:cids)
      AND rl.deleted_at IS NULL
      AND rl.visibility = 2
      AND (rl.start_visibility_at IS NULL OR rl.start_visibility_at <= '.$nowSql.')
      AND (rl.end_visibility_at IS NULL OR rl.end_visibility_at >= '.$nowSql.')
      AND (rl.user_id IS NULL OR rl.user_id = 0 OR rl.user_id = :uid)
      AND (rl.group_id IS NULL OR rl.group_id = 0)
      AND (rl.usergroup_id IS NULL OR rl.usergroup_id = 0)
      '.$sessionSql.'
    GROUP BY rl.c_id, COALESCE(rt.tool_id, -1), COALESCE(rt.title, \'\')';

        try {
            return $this->connection->fetchAllAssociative($sql, [
                'cids' => $courseIds,
                'uid' => $userId,
                'sid' => $sessionId,
            ], [
                'cids' => ArrayParameterType::INTEGER,
            ]);
        } catch (Throwable $e) {
            $this->log('fetchLastChangeByTypeForCourses: query failed', [
                'session_id' => $sessionId,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Normalize internal titles (DB titles, resource_type titles, etc.).
     */
    private function normalizeTitle(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    /**
     * Returns a map [tool_id => tool_title] from DB.
     * Cached because it almost never changes and avoids repeated queries.
     */
    private function getToolIdToTitleMap(): array
    {
        $value = $this->cache->get(self::TOOL_TITLE_CACHE_KEY, function (ItemInterface $item) {
            // Tool definitions change rarely; keep this cache longer than student-info.
            $item->expiresAfter(3600);

            try {
                $rows = $this->connection->fetchAllAssociative(
                    'SELECT id, title FROM '.self::TOOL_TABLE
                );

                $map = [];
                foreach ($rows as $r) {
                    $id = isset($r['id']) ? (int) $r['id'] : 0;
                    $title = $this->normalizeTitle((string) ($r['title'] ?? ''));
                    if ($id > 0 && '' !== $title) {
                        $map[$id] = $title;
                    }
                }

                $this->log('getToolIdToTitleMap: loaded tool titles', [
                    'count' => \count($map),
                ]);

                return $map;
            } catch (Throwable $e) {
                $this->log('getToolIdToTitleMap: failed loading tool titles', [
                    'exception' => $e->getMessage(),
                ]);

                return [];
            }
        });

        return \is_array($value) ? $value : [];
    }

    /**
     * Returns the tool.title for a given tool.id (or null if unknown).
     */
    private function getToolTitleById(int $toolId): ?string
    {
        if ($toolId <= 0) {
            return null;
        }

        $map = $this->getToolIdToTitleMap();
        $title = $map[$toolId] ?? null;

        return (null !== $title && '' !== $title) ? $title : null;
    }

    private function computeHasNewContentBatch(int $userId, array $courseIds, int $sessionId): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        $courseIds = array_filter($courseIds, static fn (int $id) => $id > 0);
        if (empty($courseIds) || $userId <= 0) {
            return [];
        }

        $firstAccessMap = $this->fetchFirstCourseAccessMapFromTrackLastAccess($userId, $courseIds, $sessionId);
        if (empty($firstAccessMap)) {
            $out = [];
            foreach ($courseIds as $cid) {
                $out[$cid] = false;
            }

            return $out;
        }

        $toolAccessMap = $this->fetchLastAccessPerToolMapForCourses($userId, $courseIds, $sessionId);
        $changeRows = $this->fetchLastChangeByTypeForCourses($userId, $courseIds, $sessionId);

        $out = [];
        foreach ($courseIds as $cid) {
            $out[$cid] = false;
        }

        foreach ($changeRows as $row) {
            $cid = (int) ($row['c_id'] ?? 0);
            if ($cid <= 0 || !isset($out[$cid])) {
                continue;
            }

            $firstCourseAccess = $firstAccessMap[$cid] ?? null;
            if (!$firstCourseAccess instanceof DateTimeImmutable) {
                continue;
            }

            $toolId = (int) ($row['tool_id'] ?? -1);
            $typeTitle = (string) ($row['type_title'] ?? '');
            $lastChange = $this->parseDateTime((string) ($row['last_change'] ?? ''));

            if (!$lastChange instanceof DateTimeImmutable) {
                continue;
            }

            $toolInfo = $this->mapToolIdAndTypeTitleToTool($toolId, $typeTitle, $cid, $sessionId);
            if (!$toolInfo || empty($toolInfo['trackTools'])) {
                continue;
            }

            $courseToolAccessMap = \is_array($toolAccessMap[$cid] ?? null) ? $toolAccessMap[$cid] : [];
            $baseline = $this->pickBestToolAccessOrFallback(
                $courseToolAccessMap,
                (array) $toolInfo['trackTools'],
                $firstCourseAccess
            );

            if ($lastChange->getTimestamp() > $baseline->getTimestamp()) {
                $out[$cid] = true;
            }
        }

        return $out;
    }

    private function isCountableTypeTitle(string $typeTitle): bool
    {
        $t = $this->normalizeTitle($typeTitle);

        if ('' === $t) {
            return false;
        }

        // Generic rule: category types are not "content items" in the UI.
        if (str_ends_with($t, '_categories')) {
            return false;
        }

        return true;
    }
}
