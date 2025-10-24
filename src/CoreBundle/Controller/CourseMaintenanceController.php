<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Builder\Cc13Capabilities;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Builder\Cc13Export;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Imscc13Import;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRecycler;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use CourseManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, JsonResponse, Request, ResponseHeaderBag};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

use const ARRAY_FILTER_USE_BOTH;
use const DIRECTORY_SEPARATOR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

#[IsGranted('ROLE_TEACHER')]
#[Route('/course_maintenance/{node}', name: 'cm_', requirements: ['node' => '\d+'])]
class CourseMaintenanceController extends AbstractController
{
    /**
     * @var bool Debug flag (true by default). Toggle via ?debug=0|1 or X-Debug: 0|1
     */
    private bool $debug = true;

    #[Route('/import/options', name: 'import_options', methods: ['GET'])]
    public function importOptions(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);
        $this->logDebug('[importOptions] called', ['node' => $node, 'debug' => $this->debug]);

        return $this->json([
            'sources' => ['local', 'server'],
            'importOptions' => ['full_backup', 'select_items'],
            'sameName' => ['skip', 'rename', 'overwrite'],
            'defaults' => [
                'importOption' => 'full_backup',
                'sameName' => 'rename',
                'sameFileNameOption' => 2,
            ],
        ]);
    }

    #[Route('/import/upload', name: 'import_upload', methods: ['POST'])]
    public function importUpload(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        $file = $req->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'Invalid upload'], 400);
        }

        $maxBytes = 1024 * 1024 * 512;
        if ($file->getSize() > $maxBytes) {
            return $this->json(['error' => 'File too large'], 413);
        }

        $allowed = ['zip', 'mbz', 'gz', 'tgz'];
        $ext = strtolower($file->guessExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (!\in_array($ext, $allowed, true)) {
            return $this->json(['error' => 'Unsupported file type'], 415);
        }

        $this->logDebug('[importUpload] received', [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
        ]);

        $backupId = CourseArchiver::importUploadedFile($file->getRealPath());
        if (false === $backupId) {
            $this->logDebug('[importUpload] archive dir not writable');

            return $this->json(['error' => 'Archive directory is not writable'], 500);
        }

        $this->logDebug('[importUpload] stored', ['backupId' => $backupId]);

        return $this->json([
            'backupId' => $backupId,
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    #[Route('/import/server', name: 'import_server_pick', methods: ['POST'])]
    public function importServerPick(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);
        $payload = json_decode($req->getContent() ?: '{}', true);

        $filename = basename((string) ($payload['filename'] ?? ''));
        if ('' === $filename || preg_match('/[\/\\\]/', $filename)) {
            return $this->json(['error' => 'Invalid filename'], 400);
        }

        $path = rtrim(CourseArchiver::getBackupDir(), '/').'/'.$filename;
        $realBase = realpath(CourseArchiver::getBackupDir());
        $realPath = realpath($path);
        if (!$realBase || !$realPath || 0 !== strncmp($realBase, $realPath, \strlen($realBase)) || !is_file($realPath)) {
            $this->logDebug('[importServerPick] file not found or outside base', ['path' => $path]);

            return $this->json(['error' => 'File not found'], 404);
        }

        $this->logDebug('[importServerPick] ok', ['backupId' => $filename]);

        return $this->json(['backupId' => $filename, 'filename' => $filename]);
    }

    #[Route(
        '/import/{backupId}/resources',
        name: 'import_resources',
        requirements: ['backupId' => '.+'],
        methods: ['GET']
    )]
    public function importResources(int $node, string $backupId, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);
        $this->logDebug('[importResources] begin', ['node' => $node, 'backupId' => $backupId]);

        try {
            $course = $this->loadLegacyCourseForAnyBackup($backupId);

            $this->logDebug('[importResources] course loaded', [
                'has_resources' => \is_array($course->resources ?? null),
                'keys' => array_keys((array) ($course->resources ?? [])),
            ]);
            $this->logDebug('[importResources] resources snapshot', $this->snapshotResources($course));
            $this->logDebug('[importResources] forum counts', $this->snapshotForumCounts($course));

            $tree = $this->buildResourceTreeForVue($course);
            $this->logDebug(
                '[importResources] UI tree groups',
                array_map(fn ($g) => ['type' => $g['type'], 'title' => $g['title'], 'items' => \count($g['items'] ?? [])], $tree)
            );

            $warnings = [];
            if (empty($tree)) {
                $warnings[] = 'Backup has no selectable resources.';
            }

            return $this->json([
                'tree' => $tree,
                'warnings' => $warnings,
            ]);
        } catch (Throwable $e) {
            $this->logDebug('[importResources] exception', ['message' => $e->getMessage()]);

            return $this->json([
                'ok' => false,
                'tree' => [],
                'warnings' => ['Error reading backup: '.$e->getMessage()],
            ], 500);
        }
    }

    #[Route(
        '/import/{backupId}/restore',
        name: 'import_restore',
        requirements: ['backupId' => '.+'],
        methods: ['POST']
    )]
    public function importRestore(
        int $node,
        string $backupId,
        Request $req,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->setDebugFromRequest($req);
        $this->logDebug('[importRestore] begin', ['node' => $node, 'backupId' => $backupId]);

        try {
            $payload = json_decode($req->getContent() ?: '{}', true);
            $importOption = (string) ($payload['importOption'] ?? 'full_backup');
            $sameFileNameOption = (int) ($payload['sameFileNameOption'] ?? 2);

            /** @var array<string,array> $selectedResources */
            $selectedResources = (array) ($payload['resources'] ?? []);

            /** @var string[] $selectedTypes */
            $selectedTypes = array_map('strval', (array) ($payload['selectedTypes'] ?? []));

            $this->logDebug('[importRestore] input', [
                'importOption' => $importOption,
                'sameFileNameOption' => $sameFileNameOption,
                'selectedTypes' => $selectedTypes,
                'hasResourcesMap' => !empty($selectedResources),
            ]);

            $course = $this->loadLegacyCourseForAnyBackup($backupId);
            if (!\is_object($course) || empty($course->resources) || !\is_array($course->resources)) {
                return $this->json(['error' => 'Backup has no resources'], 400);
            }

            $resourcesAll = (array) $course->resources;
            $this->logDebug('[importRestore] BEFORE filter keys', array_keys($resourcesAll));

            // Detect source BEFORE any filtering (meta may be dropped by filters)
            $importSource = $this->getImportSource($course);
            $isMoodle = ('moodle' === $importSource);
            $this->logDebug('[importRestore] detected import source', ['import_source' => $importSource, 'isMoodle' => $isMoodle]);

            if ('select_items' === $importOption) {
                $this->hydrateLpDependenciesFromSnapshot($course, $resourcesAll);

                if (empty($selectedResources) && !empty($selectedTypes)) {
                    $selectedResources = $this->buildSelectionFromTypes($course, $selectedTypes);
                }

                $hasAny = false;
                foreach ($selectedResources as $ids) {
                    if (\is_array($ids) && !empty($ids)) {
                        $hasAny = true;

                        break;
                    }
                }
                if (!$hasAny) {
                    return $this->json(['error' => 'No resources selected'], 400);
                }

                $course = $this->filterLegacyCourseBySelection($course, $selectedResources);
                if (empty($course->resources) || 0 === \count((array) $course->resources)) {
                    return $this->json(['error' => 'Selection produced no resources to restore'], 400);
                }
            }

            $this->logDebug('[importRestore] AFTER filter keys', array_keys((array) $course->resources));

            // NON-MOODLE
            if (!$isMoodle) {
                $this->logDebug('[importRestore] non-Moodle backup -> using CourseRestorer');
                $this->normalizeBucketsForRestorer($course);

                $restorer = new CourseRestorer($course);
                $restorer->set_file_option($this->mapSameNameOption($sameFileNameOption));
                if (method_exists($restorer, 'setResourcesAllSnapshot')) {
                    $restorer->setResourcesAllSnapshot($resourcesAll);
                }
                if (method_exists($restorer, 'setDebug')) {
                    $restorer->setDebug($this->debug);
                }
                $restorer->restore();

                CourseArchiver::cleanBackupDir();

                $courseId = (int) ($restorer->destination_course_info['real_id'] ?? 0);
                $redirectUrl = \sprintf('/course/%d/home?sid=0&gid=0', $courseId);

                return $this->json([
                    'ok' => true,
                    'message' => 'Import finished',
                    'redirectUrl' => $redirectUrl,
                ]);
            }

            // MOODLE
            $this->logDebug('[importRestore] Moodle backup -> using MoodleImport.*');

            $backupPath = $this->resolveBackupPath($backupId);
            $ci = api_get_course_info();
            $cid = (int) ($ci['real_id'] ?? 0);
            $sid = 0;

            $presentBuckets = array_map('strtolower', array_keys((array) $course->resources));
            $present = static fn (string $k): bool => \in_array(strtolower($k), $presentBuckets, true);

            $wantedGroups = [];
            $mark = static function (array &$dst, bool $cond, string $key): void { if ($cond) { $dst[$key] = true; } };

            if ('full_backup' === $importOption) {
                $mark($wantedGroups, $present('link') || $present('link_category'), 'links');
                $mark($wantedGroups, $present('forum') || $present('forum_category'), 'forums');
                $mark($wantedGroups, $present('document'), 'documents');
                $mark($wantedGroups, $present('quiz') || $present('exercise'), 'quizzes');
                $mark($wantedGroups, $present('scorm'), 'scorm');
            } else {
                $mark($wantedGroups, $present('link'), 'links');
                $mark($wantedGroups, $present('forum') || $present('forum_category'), 'forums');
                $mark($wantedGroups, $present('document'), 'documents');
                $mark($wantedGroups, $present('quiz') || $present('exercise'), 'quizzes');
                $mark($wantedGroups, $present('scorm'), 'scorm');
            }

            if (empty($wantedGroups)) {
                CourseArchiver::cleanBackupDir();

                return $this->json([
                    'ok' => true,
                    'message' => 'Nothing to import for Moodle (no supported resource groups present)',
                    'stats' => new stdClass(),
                ]);
            }

            $importer = new MoodleImport(debug: $this->debug);
            $stats = [];

            // LINKS
            if (!empty($wantedGroups['links']) && method_exists($importer, 'restoreLinks')) {
                $stats['links'] = $importer->restoreLinks($backupPath, $em, $cid, $sid, $course);
            }

            // FORUMS
            if (!empty($wantedGroups['forums']) && method_exists($importer, 'restoreForums')) {
                $stats['forums'] = $importer->restoreForums($backupPath, $em, $cid, $sid, $course);
            }

            // DOCUMENTS
            if (!empty($wantedGroups['documents']) && method_exists($importer, 'restoreDocuments')) {
                $stats['documents'] = $importer->restoreDocuments(
                    $backupPath,
                    $em,
                    $cid,
                    $sid,
                    $sameFileNameOption,
                    $course
                );
            }

            // QUIZZES
            if (!empty($wantedGroups['quizzes']) && method_exists($importer, 'restoreQuizzes')) {
                $stats['quizzes'] = $importer->restoreQuizzes($backupPath, $em, $cid, $sid);
            }

            // SCORM
            if (!empty($wantedGroups['scorm']) && method_exists($importer, 'restoreScorm')) {
                $stats['scorm'] = $importer->restoreScorm($backupPath, $em, $cid, $sid);
            }

            CourseArchiver::cleanBackupDir();

            return $this->json([
                'ok' => true,
                'message' => 'Moodle import finished',
                'stats' => $stats,
            ]);
        } catch (Throwable $e) {
            $this->logDebug('[importRestore] exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return $this->json([
                'error' => 'Restore failed: '.$e->getMessage(),
                'details' => method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    #[Route('/copy/options', name: 'copy_options', methods: ['GET'])]
    public function copyOptions(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        $current = api_get_course_info();
        $courseList = CourseManager::getCoursesFollowedByUser(api_get_user_id());

        $courses = [];
        foreach ($courseList as $c) {
            if ((int) $c['real_id'] === (int) $current['real_id']) {
                continue;
            }
            $courses[] = ['id' => (string) $c['code'], 'code' => $c['code'], 'title' => $c['title']];
        }

        return $this->json([
            'courses' => $courses,
            'defaults' => [
                'copyOption' => 'full_copy',
                'includeUsers' => false,
                'resetDates' => true,
                'sameFileNameOption' => 2,
            ],
        ]);
    }

    #[Route('/copy/resources', name: 'copy_resources', methods: ['GET'])]
    public function copyResources(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);
        $sourceCourseCode = trim((string) $req->query->get('sourceCourseId', ''));
        if ('' === $sourceCourseCode) {
            return $this->json(['error' => 'Missing sourceCourseId'], 400);
        }

        $cb = new CourseBuilder();
        $cb->set_tools_to_build([
            'documents',
            'forums',
            'tool_intro',
            'links',
            'quizzes',
            'quiz_questions',
            'assets',
            'surveys',
            'survey_questions',
            'announcements',
            'events',
            'course_descriptions',
            'glossary',
            'wiki',
            'thematic',
            'attendance',
            'works',
            'gradebook',
            'learnpath_category',
            'learnpaths',
        ]);

        $course = $cb->build(0, $sourceCourseCode);

        $tree = $this->buildResourceTreeForVue($course);

        $warnings = [];
        if (empty($tree)) {
            $warnings[] = 'Source course has no resources.';
        }

        return $this->json(['tree' => $tree, 'warnings' => $warnings]);
    }

    #[Route('/copy/execute', name: 'copy_execute', methods: ['POST'])]
    public function copyExecute(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        try {
            $payload = json_decode($req->getContent() ?: '{}', true);

            $sourceCourseId = (string) ($payload['sourceCourseId'] ?? '');
            $copyOption = (string) ($payload['copyOption'] ?? 'full_copy');
            $sameFileNameOption = (int) ($payload['sameFileNameOption'] ?? 2);
            $selectedResourcesMap = (array) ($payload['resources'] ?? []);

            if ('' === $sourceCourseId) {
                return $this->json(['error' => 'Missing sourceCourseId'], 400);
            }

            $cb = new CourseBuilder('partial');
            $cb->set_tools_to_build([
                'documents',
                'forums',
                'tool_intro',
                'links',
                'quizzes',
                'quiz_questions',
                'assets',
                'surveys',
                'survey_questions',
                'announcements',
                'events',
                'course_descriptions',
                'glossary',
                'wiki',
                'thematic',
                'attendance',
                'works',
                'gradebook',
                'learnpath_category',
                'learnpaths',
            ]);
            $legacyCourse = $cb->build(0, $sourceCourseId);

            if ('select_items' === $copyOption) {
                $legacyCourse = $this->filterLegacyCourseBySelection($legacyCourse, $selectedResourcesMap);

                if (empty($legacyCourse->resources) || !\is_array($legacyCourse->resources)) {
                    return $this->json(['error' => 'Selection produced no resources to copy'], 400);
                }
            }

            error_log('$legacyCourse :::: '.print_r($legacyCourse, true));

            $restorer = new CourseRestorer($legacyCourse);
            $restorer->set_file_option($this->mapSameNameOption($sameFileNameOption));
            if (method_exists($restorer, 'setDebug')) {
                $restorer->setDebug($this->debug);
            }
            $restorer->restore();

            $dest = api_get_course_info();
            $redirectUrl = \sprintf('/course/%d/home', (int) $dest['real_id']);

            return $this->json([
                'ok' => true,
                'message' => 'Copy finished',
                'redirectUrl' => $redirectUrl,
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'error' => 'Copy failed: '.$e->getMessage(),
                'details' => method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    #[Route('/recycle/options', name: 'recycle_options', methods: ['GET'])]
    public function recycleOptions(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        // current course only
        $defaults = [
            'recycleOption' => 'select_items', // 'full_recycle' | 'select_items'
            'confirmNeeded' => true,           // show code-confirm input when full
        ];

        return $this->json(['defaults' => $defaults]);
    }

    #[Route('/recycle/resources', name: 'recycle_resources', methods: ['GET'])]
    public function recycleResources(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        // Build legacy Course from CURRENT course (not “source”)
        $cb = new CourseBuilder();
        $cb->set_tools_to_build([
            'documents', 'forums', 'tool_intro', 'links', 'quizzes', 'quiz_questions', 'assets', 'surveys',
            'survey_questions', 'announcements', 'events', 'course_descriptions', 'glossary', 'wiki',
            'thematic', 'attendance', 'works', 'gradebook', 'learnpath_category', 'learnpaths',
        ]);
        $course = $cb->build(0, api_get_course_id());

        $tree = $this->buildResourceTreeForVue($course);
        $warnings = empty($tree) ? ['This course has no resources.'] : [];

        return $this->json(['tree' => $tree, 'warnings' => $warnings]);
    }

    #[Route('/recycle/execute', name: 'recycle_execute', methods: ['POST'])]
    public function recycleExecute(Request $req, EntityManagerInterface $em): JsonResponse
    {
        try {
            $p = json_decode($req->getContent() ?: '{}', true);
            $recycleOption = (string) ($p['recycleOption'] ?? 'select_items'); // 'full_recycle' | 'select_items'
            $resourcesMap = (array) ($p['resources'] ?? []);
            $confirmCode = (string) ($p['confirm'] ?? '');

            $type = 'full_recycle' === $recycleOption ? 'full_backup' : 'select_items';

            if ('full_backup' === $type) {
                if ($confirmCode !== api_get_course_id()) {
                    return $this->json(['error' => 'Course code confirmation mismatch'], 400);
                }
            } else {
                if (empty($resourcesMap)) {
                    return $this->json(['error' => 'No resources selected'], 400);
                }
            }

            $courseCode = api_get_course_id();
            $courseInfo = api_get_course_info($courseCode);
            $courseId = (int) ($courseInfo['real_id'] ?? 0);
            if ($courseId <= 0) {
                return $this->json(['error' => 'Invalid course id'], 400);
            }

            $recycler = new CourseRecycler(
                $em,
                $courseCode,
                $courseId
            );

            $recycler->recycle($type, $resourcesMap);

            return $this->json([
                'ok' => true,
                'message' => 'Recycle finished',
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'error' => 'Recycle failed: '.$e->getMessage(),
            ], 500);
        }
    }

    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function deleteCourse(int $node, Request $req): JsonResponse
    {
        // Basic permission gate (adjust roles to your policy if needed)
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_TEACHER') && !$this->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return $this->json(['error' => 'You are not allowed to delete this course'], 403);
        }

        try {
            $payload = json_decode($req->getContent() ?: '{}', true);
            $confirm = trim((string) ($payload['confirm'] ?? ''));

            if ('' === $confirm) {
                return $this->json(['error' => 'Missing confirmation value'], 400);
            }

            // Current course
            $courseInfo = api_get_course_info();
            if (empty($courseInfo)) {
                return $this->json(['error' => 'Unable to resolve current course'], 400);
            }

            $officialCode = (string) ($courseInfo['official_code'] ?? '');
            $runtimeCode = (string) api_get_course_id();                 // often equals official code
            $sysCode = (string) ($courseInfo['sysCode'] ?? '');       // used by legacy delete

            if ('' === $sysCode) {
                return $this->json(['error' => 'Invalid course system code'], 400);
            }

            // Accept either official_code or api_get_course_id() as confirmation
            $matches = hash_equals($officialCode, $confirm) || hash_equals($runtimeCode, $confirm);
            if (!$matches) {
                return $this->json(['error' => 'Course code confirmation mismatch'], 400);
            }

            // Legacy delete (removes course data + unregisters members in this course)
            // Throws on failure or returns void
            CourseManager::delete_course($sysCode);

            // Best-effort cleanup of legacy course session flags
            try {
                $ses = $req->getSession();
                $ses?->remove('_cid');
                $ses?->remove('_real_cid');
            } catch (Throwable) {
                // swallow — not critical
            }

            // Decide where to send the user afterwards
            // You can use '/index.php' or a landing page
            $redirectUrl = '/index.php';

            return $this->json([
                'ok' => true,
                'message' => 'Course deleted successfully',
                'redirectUrl' => $redirectUrl,
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'error' => 'Failed to delete course: '.$e->getMessage(),
                'details' => method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    #[Route('/moodle/export/options', name: 'moodle_export_options', methods: ['GET'])]
    public function moodleExportOptions(int $node, Request $req, UserRepository $users): JsonResponse
    {
        $defaults = [
            'moodleVersion' => '4',
            'scope' => 'full',
            'admin' => $users->getDefaultAdminForExport(),
        ];

        return $this->json([
            'versions' => [
                ['value' => '3', 'label' => 'Moodle 3.x'],
                ['value' => '4', 'label' => 'Moodle 4.x'],
            ],
            'defaults' => $defaults,
        ]);
    }

    #[Route('/moodle/export/resources', name: 'moodle_export_resources', methods: ['GET'])]
    public function moodleExportResources(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        // Build legacy Course from CURRENT course (same approach as recycle)
        $cb = new CourseBuilder();
        $cb->set_tools_to_build([
            'documents', 'forums', 'tool_intro', 'links', 'quizzes', 'quiz_questions', 'assets', 'surveys',
            'survey_questions', 'announcements', 'events', 'course_descriptions', 'glossary', 'wiki',
            'thematic', 'attendance', 'works', 'gradebook', 'learnpath_category', 'learnpaths',
        ]);
        $course = $cb->build(0, api_get_course_id());

        $tree = $this->buildResourceTreeForVue($course);
        $warnings = empty($tree) ? ['This course has no resources.'] : [];

        return $this->json(['tree' => $tree, 'warnings' => $warnings]);
    }

    #[Route('/moodle/export/execute', name: 'moodle_export_execute', methods: ['POST'])]
    public function moodleExportExecute(int $node, Request $req, UserRepository $users): BinaryFileResponse|JsonResponse
    {
        $this->setDebugFromRequest($req);

        $p = json_decode($req->getContent() ?: '{}', true);
        $moodleVersion = (string) ($p['moodleVersion'] ?? '4');   // '3' | '4'
        $scope = (string) ($p['scope'] ?? 'full');        // 'full' | 'selected'
        $adminId = (int) ($p['adminId'] ?? 0);
        $adminLogin = trim((string) ($p['adminLogin'] ?? ''));
        $adminEmail = trim((string) ($p['adminEmail'] ?? ''));
        $selected = (array) ($p['resources'] ?? []);

        if (!\in_array($moodleVersion, ['3', '4'], true)) {
            return $this->json(['error' => 'Unsupported Moodle version'], 400);
        }
        if ('selected' === $scope && empty($selected)) {
            return $this->json(['error' => 'No resources selected'], 400);
        }

        if ($adminId <= 0 || '' === $adminLogin || '' === $adminEmail) {
            $adm = $users->getDefaultAdminForExport();
            $adminId = $adminId > 0 ? $adminId : (int) ($adm['id'] ?? 1);
            $adminLogin = '' !== $adminLogin ? $adminLogin : (string) ($adm['username'] ?? 'admin');
            $adminEmail = '' !== $adminEmail ? $adminEmail : (string) ($adm['email'] ?? 'admin@example.com');
        }

        // Build legacy Course from CURRENT course (same approach as recycle)
        $cb = new CourseBuilder();
        $cb->set_tools_to_build([
            'documents', 'links', 'quizzes', 'quiz_questions', 'surveys', 'survey_questions',
            'announcements', 'events', 'course_descriptions', 'glossary', 'wiki', 'thematic',
            'attendance', 'works', 'gradebook', 'learnpath_category', 'learnpaths', 'tool_intro',
            'forums',
        ]);
        $course = $cb->build(0, api_get_course_id());

        // IMPORTANT: when scope === 'selected', use the same robust selection filter as copy-course
        if ('selected' === $scope) {
            // This method trims buckets to only selected items and pulls needed deps (LP/quiz/survey)
            $course = $this->filterLegacyCourseBySelection($course, $selected);

            // Safety guard: fail if nothing remains after filtering
            if (empty($course->resources) || !\is_array($course->resources)) {
                return $this->json(['error' => 'Selection produced no resources to export'], 400);
            }
        }

        try {
            // Pass selection flag to exporter so it does NOT re-hydrate from a complete snapshot.
            $selectionMode = ('selected' === $scope);
            $exporter = new MoodleExport($course, $selectionMode);
            $exporter->setAdminUserData($adminId, $adminLogin, $adminEmail);

            $courseId = api_get_course_id();
            $exportDir = 'moodle_export_'.date('Ymd_His');
            $versionNum = ('3' === $moodleVersion) ? 3 : 4;

            $mbzPath = $exporter->export($courseId, $exportDir, $versionNum);

            $resp = new BinaryFileResponse($mbzPath);
            $resp->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                basename($mbzPath)
            );

            return $resp;
        } catch (Throwable $e) {
            return $this->json(['error' => 'Moodle export failed: '.$e->getMessage()], 500);
        }
    }

    #[Route('/cc13/export/options', name: 'cc13_export_options', methods: ['GET'])]
    public function cc13ExportOptions(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        return $this->json([
            'defaults' => ['scope' => 'full'],
            'supportedTypes' => Cc13Capabilities::exportableTypes(), // ['document','link','forum']
            'message' => 'Common Cartridge 1.3: documents (webcontent) and links (HTML stub as webcontent). Forums not exported yet.',
        ]);
    }

    #[Route('/cc13/export/resources', name: 'cc13_export_resources', methods: ['GET'])]
    public function cc13ExportResources(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        $cb = new CourseBuilder();
        $cb->set_tools_to_build(['documents', 'links', 'forums']);
        $course = $cb->build(0, api_get_course_id());

        $treeAll = $this->buildResourceTreeForVue($course);
        $tree = Cc13Capabilities::filterTree($treeAll);

        // Count exportables using "items"
        $exportableCount = 0;
        foreach ($tree as $group) {
            if (empty($group['items']) || !\is_array($group['items'])) {
                continue;
            }

            if (($group['type'] ?? '') === 'forum') {
                foreach ($group['items'] as $cat) {
                    foreach (($cat['items'] ?? []) as $forumNode) {
                        if (($forumNode['type'] ?? '') === 'forum') {
                            $exportableCount++;
                        }
                    }
                }
            } else {
                $exportableCount += \count($group['items'] ?? []);
            }
        }

        $warnings = [];
        if (0 === $exportableCount) {
            $warnings[] = 'This course has no CC 1.3 exportable resources (documents, links or forums).';
        }

        return $this->json([
            'supportedTypes' => Cc13Capabilities::exportableTypes(), // ['document','link','forum']
            'tree' => $tree,
            'preview' => ['counts' => ['total' => $exportableCount]],
            'warnings' => $warnings,
        ]);
    }

    #[Route('/cc13/export/execute', name: 'cc13_export_execute', methods: ['POST'])]
    public function cc13ExportExecute(int $node, Request $req): JsonResponse
    {
        $payload = json_decode((string) $req->getContent(), true) ?: [];
        // If the client sent "resources", treat as selected even if scope says "full".
        $scope = (string) ($payload['scope'] ?? (!empty($payload['resources']) ? 'selected' : 'full'));
        $selected = (array) ($payload['resources'] ?? []);

        // Normalize selection structure (documents/links/forums/…)
        $normSel = Cc13Capabilities::filterSelection($selected);

        // Builder setup
        $tools = ['documents', 'links', 'forums'];
        $cb = new CourseBuilder();

        $selectionMode = false;

        try {
            if ('selected' === $scope) {
                // Build a full snapshot first to expand any category-only selections.
                $cbFull = new CourseBuilder();
                $cbFull->set_tools_to_build($tools);
                $courseFull = $cbFull->build(0, api_get_course_id());

                $expanded = $this->expandCc13SelectionFromCategories($courseFull, $normSel);

                // Build per-tool ID map for CourseBuilder
                $map = [];
                if (!empty($expanded['documents'])) {
                    $map['documents'] = array_map('intval', array_keys($expanded['documents']));
                }
                if (!empty($expanded['links'])) {
                    $map['links'] = array_map('intval', array_keys($expanded['links']));
                }
                if (!empty($expanded['forums'])) {
                    $map['forums'] = array_map('intval', array_keys($expanded['forums']));
                }

                if (empty($map)) {
                    return $this->json(['error' => 'Please select at least one resource.'], 400);
                }

                $cb->set_tools_to_build($tools);
                $cb->set_tools_specific_id_list($map);
                $selectionMode = true;
            } else {
                $cb->set_tools_to_build($tools);
            }

            $course = $cb->build(0, api_get_course_id());

            // Safety net: if selection mode, ensure resources are filtered
            if ($selectionMode) {
                // Convert to the expected structure for filterCourseResources()
                $safeSelected = [
                    'documents' => array_fill_keys(array_map('intval', array_keys($normSel['documents'] ?? [])), true),
                    'links' => array_fill_keys(array_map('intval', array_keys($normSel['links'] ?? [])), true),
                    'forums' => array_fill_keys(array_map('intval', array_keys($normSel['forums'] ?? [])), true),
                ];
                // Also include expansions from categories
                $fullSnapshot = isset($courseFull) ? $courseFull : $course;
                $expandedAll = $this->expandCc13SelectionFromCategories($fullSnapshot, $normSel);
                foreach (['documents', 'links', 'forums'] as $k) {
                    foreach (array_keys($expandedAll[$k] ?? []) as $idStr) {
                        $safeSelected[$k][(int) $idStr] = true;
                    }
                }

                $this->filterCourseResources($course, $safeSelected);
                if (empty($course->resources) || !\is_array($course->resources)) {
                    return $this->json(['error' => 'Nothing to export after filtering your selection.'], 400);
                }
            }

            $exporter = new Cc13Export($course, $selectionMode, /* debug */ false);
            $imsccPath = $exporter->export(api_get_course_id());
            $fileName = basename($imsccPath);

            $downloadUrl = $this->generateUrl(
                'cm_cc13_export_download',
                ['node' => $node],
                UrlGeneratorInterface::ABSOLUTE_URL
            ).'?file='.rawurlencode($fileName);

            return $this->json([
                'ok' => true,
                'file' => $fileName,
                'downloadUrl' => $downloadUrl,
                'message' => 'Export finished.',
            ]);
        } catch (RuntimeException $e) {
            if (false !== stripos($e->getMessage(), 'Nothing to export')) {
                return $this->json(['error' => 'Nothing to export (no compatible resources found).'], 400);
            }

            return $this->json(['error' => 'CC 1.3 export failed: '.$e->getMessage()], 500);
        }
    }

    #[Route('/cc13/export/download', name: 'cc13_export_download', methods: ['GET'])]
    public function cc13ExportDownload(int $node, Request $req): BinaryFileResponse|JsonResponse
    {
        // Validate the filename we will serve
        $file = basename((string) $req->query->get('file', ''));
        // Example pattern: ABC123_cc13_20251017_195455.imscc
        if ('' === $file || !preg_match('/^[A-Za-z0-9_-]+_cc13_\d{8}_\d{6}\.imscc$/', $file)) {
            return $this->json(['error' => 'Invalid file'], 400);
        }

        $abs = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
        if (!is_file($abs)) {
            return $this->json(['error' => 'File not found'], 404);
        }

        // Stream file to the browser
        $resp = new BinaryFileResponse($abs);
        $resp->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file);
        // A sensible CC mime; many LMS aceptan zip también
        $resp->headers->set('Content-Type', 'application/vnd.ims.ccv1p3+imscc');

        return $resp;
    }

    #[Route('/cc13/import', name: 'cc13_import', methods: ['POST'])]
    public function cc13Import(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        try {
            $file = $req->files->get('file');
            if (!$file || !$file->isValid()) {
                return $this->json(['error' => 'Missing or invalid upload.'], 400);
            }

            $ext = strtolower(pathinfo($file->getClientOriginalName() ?? '', PATHINFO_EXTENSION));
            if (!\in_array($ext, ['imscc', 'zip'], true)) {
                return $this->json(['error' => 'Unsupported file type. Please upload .imscc or .zip'], 415);
            }

            // Move to a temp file
            $tmpZip = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.
                'cc13_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext;
            $file->move(\dirname($tmpZip), basename($tmpZip));

            // Extract
            $extractDir = Imscc13Import::unzip($tmpZip);

            // Detect and validate format
            $format = Imscc13Import::detectFormat($extractDir);
            if (Imscc13Import::FORMAT_IMSCC13 !== $format) {
                Imscc13Import::rrmdir($extractDir);
                @unlink($tmpZip);

                return $this->json(['error' => 'This package is not a Common Cartridge 1.3.'], 400);
            }

            // Execute import (creates Chamilo resources)
            $importer = new Imscc13Import();
            $importer->execute($extractDir);

            // Cleanup
            Imscc13Import::rrmdir($extractDir);
            @unlink($tmpZip);

            return $this->json([
                'ok' => true,
                'message' => 'CC 1.3 import completed successfully.',
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'error' => 'CC 1.3 import failed: '.$e->getMessage(),
            ], 500);
        }
    }

    // --------------------------------------------------------------------------------
    // Helpers to build the Vue-ready resource tree
    // --------------------------------------------------------------------------------

    /**
     * Copies the dependencies (document, link, quiz, etc.) to $course->resources
     * that reference the selected LearnPaths, taking the items from the full snapshot.
     *
     * It doesn't break anything if something is missing or comes in a different format: it's defensive.
     */
    private function hydrateLpDependenciesFromSnapshot(object $course, array $snapshot): void
    {
        if (empty($course->resources['learnpath']) || !\is_array($course->resources['learnpath'])) {
            return;
        }

        $depTypes = [
            'document', 'link', 'quiz', 'work', 'survey',
            'Forum_Category', 'forum', 'thread', 'post',
            'Exercise_Question', 'survey_question', 'Link_Category',
        ];

        $need = [];
        $addNeed = function (string $type, $id) use (&$need): void {
            $t = (string) $type;
            $i = is_numeric($id) ? (int) $id : (string) $id;
            if ('' === $i || 0 === $i) {
                return;
            }
            $need[$t] ??= [];
            $need[$t][$i] = true;
        };

        foreach ($course->resources['learnpath'] as $lpId => $lpWrap) {
            $lp = \is_object($lpWrap) && isset($lpWrap->obj) ? $lpWrap->obj : $lpWrap;

            if (\is_object($lpWrap) && !empty($lpWrap->linked_resources) && \is_array($lpWrap->linked_resources)) {
                foreach ($lpWrap->linked_resources as $t => $ids) {
                    if (!\is_array($ids)) {
                        continue;
                    }
                    foreach ($ids as $rid) {
                        $addNeed($t, $rid);
                    }
                }
            }

            $items = [];
            if (\is_object($lp) && !empty($lp->items) && \is_array($lp->items)) {
                $items = $lp->items;
            } elseif (\is_object($lpWrap) && !empty($lpWrap->items) && \is_array($lpWrap->items)) {
                $items = $lpWrap->items;
            }

            foreach ($items as $it) {
                $ito = \is_object($it) ? $it : (object) $it;

                if (!empty($ito->linked_resources) && \is_array($ito->linked_resources)) {
                    foreach ($ito->linked_resources as $t => $ids) {
                        if (!\is_array($ids)) {
                            continue;
                        }
                        foreach ($ids as $rid) {
                            $addNeed($t, $rid);
                        }
                    }
                }

                foreach (['document_id' => 'document', 'doc_id' => 'document', 'resource_id' => null, 'link_id' => 'link', 'quiz_id' => 'quiz', 'work_id' => 'work'] as $field => $typeGuess) {
                    if (isset($ito->{$field}) && '' !== $ito->{$field} && null !== $ito->{$field}) {
                        $rid = is_numeric($ito->{$field}) ? (int) $ito->{$field} : (string) $ito->{$field};
                        $t = $typeGuess ?: (string) ($ito->type ?? '');
                        if ('' !== $t) {
                            $addNeed($t, $rid);
                        }
                    }
                }

                if (!empty($ito->type) && isset($ito->ref)) {
                    $addNeed((string) $ito->type, $ito->ref);
                }
            }
        }

        if (empty($need)) {
            $core = ['document', 'link', 'quiz', 'work', 'survey', 'Forum_Category', 'forum', 'thread', 'post', 'Exercise_Question', 'survey_question', 'Link_Category'];
            foreach ($core as $k) {
                if (!empty($snapshot[$k]) && \is_array($snapshot[$k])) {
                    $course->resources[$k] ??= [];
                    if (0 === \count($course->resources[$k])) {
                        $course->resources[$k] = $snapshot[$k];
                    }
                }
            }
            $this->logDebug('[LP-deps] fallback filled from snapshot', [
                'bags' => array_keys(array_filter($course->resources, fn ($v, $k) => \in_array($k, $core, true) && \is_array($v) && \count($v) > 0, ARRAY_FILTER_USE_BOTH)),
            ]);

            return;
        }

        foreach ($need as $type => $idMap) {
            if (empty($snapshot[$type]) || !\is_array($snapshot[$type])) {
                continue;
            }

            $course->resources[$type] ??= [];

            foreach (array_keys($idMap) as $rid) {
                $src = $snapshot[$type][$rid]
                    ?? $snapshot[$type][(string) $rid]
                    ?? null;

                if (!$src) {
                    continue;
                }

                if (!isset($course->resources[$type][$rid]) && !isset($course->resources[$type][(string) $rid])) {
                    $course->resources[$type][$rid] = $src;
                }
            }
        }

        $this->logDebug('[LP-deps] hydrated', [
            'types' => array_keys($need),
            'counts' => array_map(fn ($t) => isset($course->resources[$t]) && \is_array($course->resources[$t]) ? \count($course->resources[$t]) : 0, array_keys($need)),
        ]);
    }

    /**
     * Build a Vue-friendly tree from legacy Course.
     */
    private function buildResourceTreeForVue(object $course): array
    {
        if ($this->debug) {
            $this->logDebug('[buildResourceTreeForVue] start');
        }

        $resources = \is_object($course) && isset($course->resources) && \is_array($course->resources)
            ? $course->resources
            : [];

        $legacyTitles = [];
        if (class_exists(CourseSelectForm::class) && method_exists(CourseSelectForm::class, 'getResourceTitleList')) {
            /** @var array<string,string> $legacyTitles */
            $legacyTitles = CourseSelectForm::getResourceTitleList();
        }
        $fallbackTitles = $this->getDefaultTypeTitles();
        $skipTypes = $this->getSkipTypeKeys();

        $tree = [];

        // Forums block
        $hasForumData =
            (!empty($resources['forum']) || !empty($resources['Forum']))
            || (!empty($resources['forum_category']) || !empty($resources['Forum_Category']))
            || (!empty($resources['forum_topic']) || !empty($resources['ForumTopic']))
            || (!empty($resources['thread']) || !empty($resources['post']) || !empty($resources['forum_post']));

        if ($hasForumData) {
            $tree[] = $this->buildForumTreeForVue(
                $course,
                $legacyTitles['forum'] ?? ($fallbackTitles['forum'] ?? 'Forums')
            );
            $skipTypes['forum'] = true;
            $skipTypes['forum_category'] = true;
            $skipTypes['forum_topic'] = true;
            $skipTypes['forum_post'] = true;
            $skipTypes['thread'] = true;
            $skipTypes['post'] = true;
        }

        // Links block (Category → Link)
        $hasLinkData =
            (!empty($resources['link']) || !empty($resources['Link']))
            || (!empty($resources['link_category']) || !empty($resources['Link_Category']));

        if ($hasLinkData) {
            $tree[] = $this->buildLinkTreeForVue(
                $course,
                $legacyTitles['link'] ?? ($fallbackTitles['link'] ?? 'Links')
            );
            // Prevent generic loop from adding separate "link" and "link_category" groups
            $skipTypes['link'] = true;
            $skipTypes['link_category'] = true;
        }

        // Other tools
        foreach ($resources as $rawType => $items) {
            if (!\is_array($items) || empty($items)) {
                continue;
            }
            $typeKey = $this->normalizeTypeKey($rawType);
            if (isset($skipTypes[$typeKey])) {
                continue;
            }

            $groupTitle = $legacyTitles[$typeKey] ?? ($fallbackTitles[$typeKey] ?? ucfirst($typeKey));
            $group = [
                'type' => $typeKey,
                'title' => (string) $groupTitle,
                'items' => [],
            ];

            if ('gradebook' === $typeKey) {
                $group['items'][] = [
                    'id' => 'all',
                    'label' => 'Gradebook (all)',
                    'extra' => new stdClass(),
                    'selectable' => true,
                ];
                $tree[] = $group;

                continue;
            }

            foreach ($items as $id => $obj) {
                if (!\is_object($obj)) {
                    continue;
                }

                $idKey = is_numeric($id) ? (int) $id : (string) $id;
                if ((\is_int($idKey) && $idKey <= 0) || (\is_string($idKey) && '' === $idKey)) {
                    continue;
                }

                if (!$this->isSelectableItem($typeKey, $obj)) {
                    continue;
                }

                $label = $this->resolveItemLabel($typeKey, $obj, \is_int($idKey) ? $idKey : 0);
                if ('document' === $typeKey) {
                    $e = $this->objectEntity($obj);
                    $rawPath = (string) ($e->path ?? '');
                    if ('' !== $rawPath) {
                        $rel = ltrim($rawPath, '/');
                        $rel = preg_replace('~^document/?~', '', $rel);
                        $filetype = (string) ($e->filetype ?? $e->file_type ?? '');
                        if ('folder' === $filetype) {
                            $rel = rtrim($rel, '/').'/';
                        }
                        if ('' !== $rel) {
                            $label = $rel;
                        }
                    }
                }
                if ('tool_intro' === $typeKey && '#0' === $label && \is_string($idKey)) {
                    $label = $idKey;
                }

                $extra = $this->buildExtra($typeKey, $obj);

                $group['items'][] = [
                    'id' => $idKey,
                    'label' => $label,
                    'extra' => $extra ?: new stdClass(),
                    'selectable' => true,
                ];
            }

            if (!empty($group['items'])) {
                usort(
                    $group['items'],
                    static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label'])
                );
                $tree[] = $group;
            }
        }

        // Preferred order
        $preferredOrder = [
            'announcement', 'document', 'course_description', 'learnpath', 'quiz', 'forum', 'glossary', 'link',
            'survey', 'thematic', 'work', 'attendance', 'wiki', 'calendar_event', 'tool_intro', 'gradebook',
        ];
        usort($tree, static function ($a, $b) use ($preferredOrder) {
            $ia = array_search($a['type'], $preferredOrder, true);
            $ib = array_search($b['type'], $preferredOrder, true);
            if (false !== $ia && false !== $ib) {
                return $ia <=> $ib;
            }
            if (false !== $ia) {
                return -1;
            }
            if (false !== $ib) {
                return 1;
            }

            return strcasecmp($a['title'], $b['title']);
        });

        if ($this->debug) {
            $this->logDebug(
                '[buildResourceTreeForVue] end groups',
                array_map(fn ($g) => ['type' => $g['type'], 'items' => \count($g['items'] ?? [])], $tree)
            );
        }

        return $tree;
    }

    /**
     * Build forum tree (Category → Forum → Topic) for the UI.
     * Uses only "items" (no "children") and sets UI hints (has_children, item_count).
     */
    private function buildForumTreeForVue(object $course, string $groupTitle): array
    {
        $this->logDebug('[buildForumTreeForVue] start');

        $res = \is_array($course->resources ?? null) ? $course->resources : [];

        // Buckets (defensive: accept legacy casings / aliases)
        $catRaw = $res['forum_category'] ?? $res['Forum_Category'] ?? [];
        $forumRaw = $res['forum'] ?? $res['Forum'] ?? [];
        $topicRaw = $res['forum_topic'] ?? $res['ForumTopic'] ?? ($res['thread'] ?? []);
        $postRaw = $res['forum_post'] ?? $res['Forum_Post'] ?? ($res['post'] ?? []);

        $this->logDebug('[buildForumTreeForVue] raw counts', [
            'categories' => \is_array($catRaw) ? \count($catRaw) : 0,
            'forums' => \is_array($forumRaw) ? \count($forumRaw) : 0,
            'topics' => \is_array($topicRaw) ? \count($topicRaw) : 0,
            'posts' => \is_array($postRaw) ? \count($postRaw) : 0,
        ]);

        // Quick classifiers (defensive)
        $isForum = function (object $o): bool {
            $e = (isset($o->obj) && \is_object($o->obj)) ? $o->obj : $o;
            if (isset($e->forum_title) && \is_string($e->forum_title)) {
                return true;
            }
            if (isset($e->default_view) || isset($e->allow_anonymous)) {
                return true;
            }
            if ((isset($e->forum_category) || isset($e->forum_category_id) || isset($e->category_id)) && !isset($e->forum_id)) {
                return true;
            }

            return false;
        };
        $isTopic = function (object $o): bool {
            $e = (isset($o->obj) && \is_object($o->obj)) ? $o->obj : $o;
            if (isset($e->forum_id) && (isset($e->thread_title) || isset($e->thread_date) || isset($e->poster_name))) {
                return true;
            }
            if (isset($e->forum_id) && !isset($e->forum_title)) {
                return true;
            }

            return false;
        };
        $getForumCategoryId = function (object $forum): int {
            $e = (isset($forum->obj) && \is_object($forum->obj)) ? $forum->obj : $forum;
            $cid = (int) ($e->forum_category ?? 0);
            if ($cid <= 0) {
                $cid = (int) ($e->forum_category_id ?? 0);
            }
            if ($cid <= 0) {
                $cid = (int) ($e->category_id ?? 0);
            }

            return $cid;
        };

        // Build categories
        $cats = [];
        foreach ($catRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            $label = $this->resolveItemLabel('forum_category', $this->objectEntity($obj), $id);
            $cats[$id] = [
                'id' => $id,
                'type' => 'forum_category',
                'label' => $label,
                'selectable' => false,
                'items' => [],
                // UI hints
                'has_children' => false,
                'item_count' => 0,
            ];
        }
        // Virtual "Uncategorized"
        $uncatKey = -9999;
        if (!isset($cats[$uncatKey])) {
            $cats[$uncatKey] = [
                'id' => $uncatKey,
                'type' => 'forum_category',
                'label' => 'Uncategorized',
                'selectable' => false,
                'items' => [],
                '_virtual' => true,
                'has_children' => false,
                'item_count' => 0,
            ];
        }

        // Forums
        $forums = [];
        foreach ($forumRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            if (!$isForum($obj)) {
                $this->logDebug('[buildForumTreeForVue] skipped non-forum in forum bucket', ['id' => $id]);

                continue;
            }
            $forums[$id] = $this->objectEntity($obj);
        }

        // Topics (+ post counts)
        $topics = [];
        $postCountByTopic = [];
        foreach ($topicRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            if ($isForum($obj) && !$isTopic($obj)) {
                $this->logDebug('[buildForumTreeForVue] WARNING: forum object found in topic bucket; skipping', ['id' => $id]);

                continue;
            }
            if (!$isTopic($obj)) {
                continue;
            }
            $topics[$id] = $this->objectEntity($obj);
        }
        foreach ($postRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            $e = $this->objectEntity($obj);
            $tid = (int) ($e->thread_id ?? 0);
            if ($tid > 0) {
                $postCountByTopic[$tid] = ($postCountByTopic[$tid] ?? 0) + 1;
            }
        }

        // Attach topics to forums and forums to categories
        foreach ($forums as $fid => $f) {
            $catId = $getForumCategoryId($f);
            if (!isset($cats[$catId])) {
                $catId = $uncatKey;
            }

            $forumNode = [
                'id' => $fid,
                'type' => 'forum',
                'label' => $this->resolveItemLabel('forum', $f, $fid),
                'extra' => $this->buildExtra('forum', $f) ?: new stdClass(),
                'selectable' => true,
                'items' => [],
                // UI hints
                'has_children' => false,
                'item_count' => 0,
                'ui_depth' => 2,
            ];

            foreach ($topics as $tid => $t) {
                if ((int) ($t->forum_id ?? 0) !== $fid) {
                    continue;
                }

                $author = (string) ($t->thread_poster_name ?? $t->poster_name ?? '');
                $date = (string) ($t->thread_date ?? '');
                $nPosts = (int) ($postCountByTopic[$tid] ?? 0);

                $topicLabel = $this->resolveItemLabel('forum_topic', $t, $tid);
                $meta = [];
                if ('' !== $author) {
                    $meta[] = $author;
                }
                if ('' !== $date) {
                    $meta[] = $date;
                }
                if ($meta) {
                    $topicLabel .= ' ('.implode(', ', $meta).')';
                }
                if ($nPosts > 0) {
                    $topicLabel .= ' — '.$nPosts.' post'.(1 === $nPosts ? '' : 's');
                }

                $forumNode['items'][] = [
                    'id' => $tid,
                    'type' => 'forum_topic',
                    'label' => $topicLabel,
                    'extra' => new stdClass(),
                    'selectable' => true,
                    'ui_depth' => 3,
                    'item_count' => 0,
                ];
            }

            if (!empty($forumNode['items'])) {
                usort($forumNode['items'], static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
                $forumNode['has_children'] = true;
                $forumNode['item_count'] = \count($forumNode['items']);
            }

            $cats[$catId]['items'][] = $forumNode;
        }

        // Remove empty virtual category; sort forums inside each category
        $catNodes = array_values(array_filter($cats, static function ($c) {
            if (!empty($c['_virtual']) && empty($c['items'])) {
                return false;
            }

            return true;
        }));

        // Flatten stray forums (defensive) and finalize UI hints
        foreach ($catNodes as &$cat) {
            if (!empty($cat['items'])) {
                $lift = [];
                foreach ($cat['items'] as &$forumNode) {
                    if (($forumNode['type'] ?? '') !== 'forum' || empty($forumNode['items'])) {
                        continue;
                    }
                    $keep = [];
                    foreach ($forumNode['items'] as $child) {
                        if (($child['type'] ?? '') === 'forum') {
                            $lift[] = $child;
                            $this->logDebug('[buildForumTreeForVue] flatten: lifted nested forum', [
                                'parent_forum_id' => $forumNode['id'] ?? null,
                                'lifted_forum_id' => $child['id'] ?? null,
                                'cat_id' => $cat['id'] ?? null,
                            ]);
                        } else {
                            $keep[] = $child;
                        }
                    }
                    $forumNode['items'] = $keep;
                    $forumNode['has_children'] = !empty($keep);
                    $forumNode['item_count'] = \count($keep);
                }
                unset($forumNode);

                foreach ($lift as $n) {
                    $cat['items'][] = $n;
                }
                usort($cat['items'], static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
            }

            // UI hints for category
            $cat['has_children'] = !empty($cat['items']);
            $cat['item_count'] = \count($cat['items'] ?? []);
        }
        unset($cat);

        $this->logDebug('[buildForumTreeForVue] end', ['categories' => \count($catNodes)]);

        return [
            'type' => 'forum',
            'title' => $groupTitle,
            'items' => $catNodes,
        ];
    }

    /**
     * Normalize a raw type to a lowercase key.
     */
    private function normalizeTypeKey(int|string $raw): string
    {
        if (\is_int($raw)) {
            return (string) $raw;
        }

        $s = strtolower(str_replace(['\\', ' '], ['/', '_'], (string) $raw));

        $map = [
            'forum_category' => 'forum_category',
            'forumtopic' => 'forum_topic',
            'forum_topic' => 'forum_topic',
            'forum_post' => 'forum_post',
            'thread' => 'forum_topic',
            'post' => 'forum_post',
            'exercise_question' => 'exercise_question',
            'surveyquestion' => 'survey_question',
            'surveyinvitation' => 'survey_invitation',
            'survey' => 'survey',
            'link_category' => 'link_category',
            'coursecopylearnpath' => 'learnpath',
            'coursecopytestcategory' => 'test_category',
            'coursedescription' => 'course_description',
            'session_course' => 'session_course',
            'gradebookbackup' => 'gradebook',
            'scormdocument' => 'scorm',
            'tool/introduction' => 'tool_intro',
            'tool_introduction' => 'tool_intro',
        ];

        return $map[$s] ?? $s;
    }

    /**
     * Keys to skip as top-level groups in UI.
     *
     * @return array<string,bool>
     */
    private function getSkipTypeKeys(): array
    {
        return [
            'forum_category' => true,
            'forum_topic' => true,
            'forum_post' => true,
            'thread' => true,
            'post' => true,
            'exercise_question' => true,
            'survey_question' => true,
            'survey_invitation' => true,
            'session_course' => true,
            'scorm' => true,
            'asset' => true,
            'link_category' => true,
        ];
    }

    /**
     * Default labels for groups.
     *
     * @return array<string,string>
     */
    private function getDefaultTypeTitles(): array
    {
        return [
            'announcement' => 'Announcements',
            'document' => 'Documents',
            'glossary' => 'Glossaries',
            'calendar_event' => 'Calendar events',
            'event' => 'Calendar events',
            'link' => 'Links',
            'course_description' => 'Course descriptions',
            'learnpath' => 'Parcours',
            'learnpath_category' => 'Learning path categories',
            'forum' => 'Forums',
            'forum_category' => 'Forum categories',
            'quiz' => 'Exercices',
            'test_category' => 'Test categories',
            'wiki' => 'Wikis',
            'thematic' => 'Thematics',
            'attendance' => 'Attendances',
            'work' => 'Works',
            'session_course' => 'Session courses',
            'gradebook' => 'Gradebook',
            'scorm' => 'SCORM packages',
            'survey' => 'Surveys',
            'survey_question' => 'Survey questions',
            'survey_invitation' => 'Survey invitations',
            'asset' => 'Assets',
            'tool_intro' => 'Tool introductions',
        ];
    }

    /**
     * Decide if an item is selectable (UI).
     */
    private function isSelectableItem(string $type, object $obj): bool
    {
        if ('document' === $type) {
            return true;
        }

        return true;
    }

    /**
     * Resolve label for an item with fallbacks.
     */
    private function resolveItemLabel(string $type, object $obj, int $fallbackId): string
    {
        $entity = $this->objectEntity($obj);

        foreach (['title', 'name', 'subject', 'question', 'display', 'code', 'description'] as $k) {
            if (isset($entity->{$k}) && \is_string($entity->{$k}) && '' !== trim($entity->{$k})) {
                return trim((string) $entity->{$k});
            }
        }

        if (isset($obj->params) && \is_array($obj->params)) {
            foreach (['title', 'name', 'subject', 'display', 'description'] as $k) {
                if (!empty($obj->params[$k]) && \is_string($obj->params[$k])) {
                    return (string) $obj->params[$k];
                }
            }
        }

        switch ($type) {
            case 'document':
                // 1) ruta cruda tal como viene del backup/DB
                $raw = (string) ($entity->path ?? $obj->path ?? '');
                if ('' !== $raw) {
                    // 2) normalizar a ruta relativa y quitar prefijo "document/" si viniera en el path del backup
                    $rel = ltrim($raw, '/');
                    $rel = preg_replace('~^document/?~', '', $rel);

                    // 3) carpeta ⇒ que termine con "/"
                    $fileType = (string) ($entity->file_type ?? $obj->file_type ?? '');
                    if ('folder' === $fileType) {
                        $rel = rtrim($rel, '/').'/';
                    }

                    // 4) si la ruta quedó vacía, usa basename como último recurso
                    return '' !== $rel ? $rel : basename($raw);
                }

                // fallback: título o nombre de archivo
                if (!empty($obj->title)) {
                    return (string) $obj->title;
                }

                break;

            case 'course_description':
                if (!empty($obj->title)) {
                    return (string) $obj->title;
                }
                $t = (int) ($obj->description_type ?? 0);
                $names = [
                    1 => 'Description',
                    2 => 'Objectives',
                    3 => 'Topics',
                    4 => 'Methodology',
                    5 => 'Course material',
                    6 => 'Resources',
                    7 => 'Assessment',
                    8 => 'Custom',
                ];

                return $names[$t] ?? ('#'.$fallbackId);

            case 'announcement':
                if (!empty($obj->title)) {
                    return (string) $obj->title;
                }

                break;

            case 'forum':
                if (!empty($entity->forum_title)) {
                    return (string) $entity->forum_title;
                }

                break;

            case 'forum_category':
                if (!empty($entity->cat_title)) {
                    return (string) $entity->cat_title;
                }

                break;

            case 'link':
                if (!empty($obj->title)) {
                    return (string) $obj->title;
                }
                if (!empty($obj->url)) {
                    return (string) $obj->url;
                }

                break;

            case 'survey':
                if (!empty($obj->title)) {
                    return trim((string) $obj->title);
                }

                break;

            case 'learnpath':
                if (!empty($obj->name)) {
                    return (string) $obj->name;
                }

                break;

            case 'thematic':
                if (isset($obj->params['title']) && \is_string($obj->params['title'])) {
                    return (string) $obj->params['title'];
                }

                break;

            case 'quiz':
                if (!empty($entity->title)) {
                    return (string) $entity->title;
                }

                break;

            case 'forum_topic':
                if (!empty($entity->thread_title)) {
                    return (string) $entity->thread_title;
                }

                break;
        }

        return '#'.$fallbackId;
    }

    /**
     * Extract wrapped entity (->obj) or the object itself.
     */
    private function objectEntity(object $resource): object
    {
        if (isset($resource->obj) && \is_object($resource->obj)) {
            return $resource->obj;
        }

        return $resource;
    }

    /**
     * Extra payload per item for UI (optional).
     */
    private function buildExtra(string $type, object $obj): array
    {
        $extra = [];

        $get = static function (object $o, string $k, $default = null) {
            return (isset($o->{$k}) && (\is_string($o->{$k}) || is_numeric($o->{$k}))) ? $o->{$k} : $default;
        };

        switch ($type) {
            case 'document':
                $extra['path'] = (string) ($get($obj, 'path', '') ?? '');
                $extra['filetype'] = (string) ($get($obj, 'file_type', '') ?? '');
                $extra['size'] = (string) ($get($obj, 'size', '') ?? '');

                break;

            case 'link':
                $extra['url'] = (string) ($get($obj, 'url', '') ?? '');
                $extra['target'] = (string) ($get($obj, 'target', '') ?? '');

                break;

            case 'forum':
                $entity = $this->objectEntity($obj);
                $extra['category_id'] = (string) ($entity->forum_category ?? '');
                $extra['default_view'] = (string) ($entity->default_view ?? '');

                break;

            case 'learnpath':
                $extra['name'] = (string) ($get($obj, 'name', '') ?? '');
                $extra['items'] = isset($obj->items) && \is_array($obj->items) ? array_map(static function ($i) {
                    return [
                        'id' => (int) ($i['id'] ?? 0),
                        'title' => (string) ($i['title'] ?? ''),
                        'type' => (string) ($i['item_type'] ?? ''),
                        'path' => (string) ($i['path'] ?? ''),
                    ];
                }, $obj->items) : [];

                break;

            case 'thematic':
                if (isset($obj->params) && \is_array($obj->params)) {
                    $extra['active'] = (string) ($obj->params['active'] ?? '');
                }

                break;

            case 'quiz':
                $entity = $this->objectEntity($obj);
                $extra['question_ids'] = isset($entity->question_ids) && \is_array($entity->question_ids)
                    ? array_map('intval', $entity->question_ids)
                    : [];

                break;

            case 'survey':
                $entity = $this->objectEntity($obj);
                $extra['question_ids'] = isset($entity->question_ids) && \is_array($entity->question_ids)
                    ? array_map('intval', $entity->question_ids)
                    : [];

                break;
        }

        return array_filter($extra, static fn ($v) => !('' === $v || null === $v || [] === $v));
    }

    // --------------------------------------------------------------------------------
    // Selection filtering (used by partial restore)
    // --------------------------------------------------------------------------------

    /**
     * Get first existing key from candidates.
     */
    private function firstExistingKey(array $orig, array $candidates): ?string
    {
        foreach ($candidates as $k) {
            if (isset($orig[$k]) && \is_array($orig[$k]) && !empty($orig[$k])) {
                return $k;
            }
        }

        return null;
    }

    /**
     * Filter legacy Course by UI selections (and pull dependencies).
     *
     * @param array $selected [type => [id => true]]
     */
    private function filterLegacyCourseBySelection(object $course, array $selected): object
    {
        $this->logDebug('[filterSelection] start', ['selected_types' => array_keys($selected)]);

        if (empty($course->resources) || !\is_array($course->resources)) {
            $this->logDebug('[filterSelection] course has no resources');

            return $course;
        }

        /** @var array<string,mixed> $orig */
        $orig = $course->resources;

        // Keep meta buckets (keys starting with "__") so we don't lose import_source, etc.
        $__metaBuckets = [];
        foreach ($orig as $k => $v) {
            if (\is_string($k) && str_starts_with($k, '__')) {
                $__metaBuckets[$k] = $v;
            }
        }

        $getBucket = static function (array $a, string $key): array {
            return (isset($a[$key]) && \is_array($a[$key])) ? $a[$key] : [];
        };

        // Forums flow
        if (!empty($selected) && !empty($selected['forum'])) {
            $selForums = array_fill_keys(array_map('strval', array_keys($selected['forum'])), true);
            if (!empty($selForums)) {
                $forums = $getBucket($orig, 'forum');
                $catsToKeep = [];

                foreach ($forums as $fid => $f) {
                    if (!isset($selForums[(string) $fid])) {
                        continue;
                    }
                    $e = (isset($f->obj) && \is_object($f->obj)) ? $f->obj : $f;
                    $cid = (int) ($e->forum_category ?? 0);
                    if ($cid > 0) {
                        $catsToKeep[$cid] = true;
                    }
                }

                $threads = $getBucket($orig, 'thread');
                $threadToKeep = [];
                foreach ($threads as $tid => $t) {
                    $e = (isset($t->obj) && \is_object($t->obj)) ? $t->obj : $t;
                    if (isset($selForums[(string) ($e->forum_id ?? '')])) {
                        $threadToKeep[(int) $tid] = true;
                    }
                }

                $posts = $getBucket($orig, 'post');
                $postToKeep = [];
                foreach ($posts as $pid => $p) {
                    $e = (isset($p->obj) && \is_object($p->obj)) ? $p->obj : $p;
                    if (isset($threadToKeep[(int) ($e->thread_id ?? 0)])) {
                        $postToKeep[(int) $pid] = true;
                    }
                }

                $out = [];
                foreach ($selected as $type => $ids) {
                    if (!\is_array($ids) || empty($ids)) {
                        continue;
                    }
                    $bucket = $getBucket($orig, (string) $type);
                    if (!empty($bucket)) {
                        $idsMap = array_fill_keys(array_map('strval', array_keys($ids)), true);
                        $out[$type] = array_intersect_key($bucket, $idsMap);
                    }
                }

                $forumCat = $getBucket($orig, 'Forum_Category');
                if (!empty($forumCat)) {
                    $out['Forum_Category'] = array_intersect_key(
                        $forumCat,
                        array_fill_keys(array_map('strval', array_keys($catsToKeep)), true)
                    );
                }

                $forumBucket = $getBucket($orig, 'forum');
                if (!empty($forumBucket)) {
                    $out['forum'] = array_intersect_key($forumBucket, $selForums);
                }

                $threadBucket = $getBucket($orig, 'thread');
                if (!empty($threadBucket)) {
                    $out['thread'] = array_intersect_key(
                        $threadBucket,
                        array_fill_keys(array_map('strval', array_keys($threadToKeep)), true)
                    );
                }

                $postBucket = $getBucket($orig, 'post');
                if (!empty($postBucket)) {
                    $out['post'] = array_intersect_key(
                        $postBucket,
                        array_fill_keys(array_map('strval', array_keys($postToKeep)), true)
                    );
                }

                if (!empty($out['forum']) && empty($out['Forum_Category']) && !empty($forumCat)) {
                    $out['Forum_Category'] = $forumCat;
                }

                // Preserve meta buckets
                if (!empty($__metaBuckets)) {
                    $out = array_filter($out);
                    $course->resources = $__metaBuckets + $out;
                } else {
                    $course->resources = array_filter($out);
                }

                $this->logDebug('[filterSelection] end', [
                    'kept_types' => array_keys($course->resources),
                    'forum_counts' => [
                        'Forum_Category' => \is_array($course->resources['Forum_Category'] ?? null) ? \count($course->resources['Forum_Category']) : 0,
                        'forum' => \is_array($course->resources['forum'] ?? null) ? \count($course->resources['forum']) : 0,
                        'thread' => \is_array($course->resources['thread'] ?? null) ? \count($course->resources['thread']) : 0,
                        'post' => \is_array($course->resources['post'] ?? null) ? \count($course->resources['post']) : 0,
                    ],
                ]);

                return $course;
            }
        }

        // Generic + quiz/survey/gradebook flows
        $alias = [
            'tool_intro' => 'Tool introduction',
        ];

        $keep = [];
        foreach ($selected as $type => $ids) {
            if (!\is_array($ids) || empty($ids)) {
                continue;
            }

            $legacyKey = $type;
            if (!isset($orig[$legacyKey]) && isset($alias[$type])) {
                $legacyKey = $alias[$type];
            }

            $bucket = $getBucket($orig, (string) $legacyKey);
            if (!empty($bucket)) {
                $idsMap = array_fill_keys(array_map('strval', array_keys($ids)), true);
                $keep[$legacyKey] = array_intersect_key($bucket, $idsMap);
            }
        }

        // Gradebook bucket
        $gbKey = $this->firstExistingKey($orig, ['gradebook', 'Gradebook', 'GradebookBackup', 'gradebookbackup']);
        if ($gbKey && !empty($selected['gradebook'])) {
            $gbBucket = $getBucket($orig, $gbKey);
            if (!empty($gbBucket)) {
                $selIds = array_keys(array_filter((array) $selected['gradebook']));
                $firstItem = reset($gbBucket);

                if (\in_array('all', $selIds, true) || !\is_object($firstItem)) {
                    $keep[$gbKey] = $gbBucket;
                    $this->logDebug('[filterSelection] kept full gradebook bucket', ['key' => $gbKey, 'count' => \count($gbBucket)]);
                } else {
                    $keep[$gbKey] = array_intersect_key($gbBucket, array_fill_keys(array_map('strval', $selIds), true));
                    $this->logDebug('[filterSelection] kept partial gradebook bucket', ['key' => $gbKey, 'count' => \count($keep[$gbKey])]);
                }
            }
        }

        // Quizzes → questions (+ images)
        $quizKey = $this->firstExistingKey($orig, ['quiz', 'Quiz']);
        if ($quizKey && !empty($keep[$quizKey])) {
            $questionKey = $this->firstExistingKey($orig, ['Exercise_Question', 'exercise_question', \defined('RESOURCE_QUIZQUESTION') ? RESOURCE_QUIZQUESTION : '']);
            if ($questionKey) {
                $qids = [];
                foreach ($keep[$quizKey] as $qid => $qwrap) {
                    $q = (isset($qwrap->obj) && \is_object($qwrap->obj)) ? $qwrap->obj : $qwrap;
                    if (!empty($q->question_ids) && \is_array($q->question_ids)) {
                        foreach ($q->question_ids as $sid) {
                            $qids[(string) $sid] = true;
                        }
                    }
                }

                if (!empty($qids)) {
                    $questionBucket = $getBucket($orig, $questionKey);
                    $selQ = array_intersect_key($questionBucket, $qids);
                    if (!empty($selQ)) {
                        $keep[$questionKey] = $selQ;
                        $this->logDebug('[filterSelection] pulled question bucket for quizzes', [
                            'quiz_count' => \count($keep[$quizKey]),
                            'question_key' => $questionKey,
                            'questions_kept' => \count($keep[$questionKey]),
                        ]);

                        $docKey = $this->firstExistingKey($orig, ['document', 'Document', \defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : '']);
                        if ($docKey) {
                            $docBucket = $getBucket($orig, $docKey);
                            $imageQuizBucket = (isset($docBucket['image_quiz']) && \is_array($docBucket['image_quiz'])) ? $docBucket['image_quiz'] : [];
                            if (!empty($imageQuizBucket)) {
                                $needed = [];
                                foreach ($keep[$questionKey] as $qid => $qwrap) {
                                    $q = (isset($qwrap->obj) && \is_object($qwrap->obj)) ? $qwrap->obj : $qwrap;
                                    $pic = (string) ($q->picture ?? '');
                                    if ('' !== $pic && isset($imageQuizBucket[$pic])) {
                                        $needed[$pic] = true;
                                    }
                                }
                                if (!empty($needed)) {
                                    $keep[$docKey] = $keep[$docKey] ?? [];
                                    $keep[$docKey]['image_quiz'] = array_intersect_key($imageQuizBucket, $needed);
                                    $this->logDebug('[filterSelection] included image_quiz docs for questions', [
                                        'count' => \count($keep[$docKey]['image_quiz']),
                                    ]);
                                }
                            }
                        }
                    }
                }
            } else {
                $this->logDebug('[filterSelection] quizzes selected but no question bucket found in backup');
            }
        }

        // Surveys → questions (+ invitations)
        $surveyKey = $this->firstExistingKey($orig, ['survey', 'Survey']);
        if ($surveyKey && !empty($keep[$surveyKey])) {
            $surveyQuestionKey = $this->firstExistingKey($orig, ['Survey_Question', 'survey_question', \defined('RESOURCE_SURVEYQUESTION') ? RESOURCE_SURVEYQUESTION : '']);
            $surveyInvitationKey = $this->firstExistingKey($orig, ['Survey_Invitation', 'survey_invitation', \defined('RESOURCE_SURVEYINVITATION') ? RESOURCE_SURVEYINVITATION : '']);

            if ($surveyQuestionKey) {
                $neededQids = [];
                $selSurveyIds = array_map('strval', array_keys($keep[$surveyKey]));

                foreach ($keep[$surveyKey] as $sid => $sWrap) {
                    $s = (isset($sWrap->obj) && \is_object($sWrap->obj)) ? $sWrap->obj : $sWrap;
                    if (!empty($s->question_ids) && \is_array($s->question_ids)) {
                        foreach ($s->question_ids as $qid) {
                            $neededQids[(string) $qid] = true;
                        }
                    }
                }

                if (empty($neededQids)) {
                    $surveyQBucket = $getBucket($orig, $surveyQuestionKey);
                    foreach ($surveyQBucket as $qid => $qWrap) {
                        $q = (isset($qWrap->obj) && \is_object($qWrap->obj)) ? $qWrap->obj : $qWrap;
                        $qSurveyId = (string) ($q->survey_id ?? '');
                        if ('' !== $qSurveyId && \in_array($qSurveyId, $selSurveyIds, true)) {
                            $neededQids[(string) $qid] = true;
                        }
                    }
                }

                if (!empty($neededQids)) {
                    $surveyQBucket = $getBucket($orig, $surveyQuestionKey);
                    $keep[$surveyQuestionKey] = array_intersect_key($surveyQBucket, $neededQids);
                    $this->logDebug('[filterSelection] pulled question bucket for surveys', [
                        'survey_count' => \count($keep[$surveyKey]),
                        'question_key' => $surveyQuestionKey,
                        'questions_kept' => \count($keep[$surveyQuestionKey]),
                    ]);
                } else {
                    $this->logDebug('[filterSelection] surveys selected but no matching questions found');
                }
            } else {
                $this->logDebug('[filterSelection] surveys selected but no question bucket found in backup');
            }

            if ($surveyInvitationKey) {
                $invBucket = $getBucket($orig, $surveyInvitationKey);
                if (!empty($invBucket)) {
                    $neededInv = [];
                    foreach ($invBucket as $iid => $invWrap) {
                        $inv = (isset($invWrap->obj) && \is_object($invWrap->obj)) ? $invWrap->obj : $invWrap;
                        $sid = (string) ($inv->survey_id ?? '');
                        if ('' !== $sid && isset($keep[$surveyKey][$sid])) {
                            $neededInv[(string) $iid] = true;
                        }
                    }
                    if (!empty($neededInv)) {
                        $keep[$surveyInvitationKey] = array_intersect_key($invBucket, $neededInv);
                        $this->logDebug('[filterSelection] included survey invitations', [
                            'invitations_kept' => \count($keep[$surveyInvitationKey]),
                        ]);
                    }
                }
            }
        }

        $docKey = $this->firstExistingKey($orig, ['document', 'Document', \defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : '']);
        if ($docKey && !empty($keep[$docKey])) {
            $docBucket = $getBucket($orig, $docKey);

            $foldersByRel = [];
            foreach ($docBucket as $fid => $res) {
                $e = (isset($res->obj) && \is_object($res->obj)) ? $res->obj : $res;
                $ftRaw = strtolower((string) ($e->file_type ?? $e->filetype ?? ''));
                $isFolder = ('folder' === $ftRaw);
                if (!$isFolder) {
                    $pTest = (string) ($e->path ?? '');
                    if ('' !== $pTest) {
                        $isFolder = ('/' === substr($pTest, -1));
                    }
                }
                if (!$isFolder) {
                    continue;
                }

                $p = (string) ($e->path ?? '');
                if ('' === $p) {
                    continue;
                }

                $frel = '/'.ltrim(substr($p, 8), '/');
                $frel = rtrim($frel, '/').'/';
                if ('//' !== $frel) {
                    $foldersByRel[$frel] = $fid;
                }
            }

            $needFolderIds = [];
            foreach ($keep[$docKey] as $id => $res) {
                $e = (isset($res->obj) && \is_object($res->obj)) ? $res->obj : $res;

                $ftRaw = strtolower((string) ($e->file_type ?? $e->filetype ?? ''));
                $isFolder = ('folder' === $ftRaw) || ('/' === substr((string) ($e->path ?? ''), -1));
                if ($isFolder) {
                    continue;
                }

                $p = (string) ($e->path ?? '');
                if ('' === $p) {
                    continue;
                }

                $rel = '/'.ltrim(substr($p, 8), '/');
                $dir = rtrim(\dirname($rel), '/');
                if ('' === $dir) {
                    continue;
                }

                $acc = '';
                foreach (array_filter(explode('/', $dir)) as $seg) {
                    $acc .= '/'.$seg;
                    $accKey = rtrim($acc, '/').'/';
                    if (isset($foldersByRel[$accKey])) {
                        $needFolderIds[$foldersByRel[$accKey]] = true;
                    }
                }
            }

            if (!empty($needFolderIds)) {
                $added = array_intersect_key($docBucket, $needFolderIds);
                $keep[$docKey] += $added;

                $this->logDebug('[filterSelection] added parent folders for selected documents', [
                    'doc_key' => $docKey,
                    'added_folders' => \count($added),
                ]);
            }
        }

        $lnkKey = $this->firstExistingKey(
            $orig,
            ['link', 'Link', \defined('RESOURCE_LINK') ? RESOURCE_LINK : '']
        );

        if ($lnkKey && !empty($keep[$lnkKey])) {
            $catIdsUsed = [];
            foreach ($keep[$lnkKey] as $lid => $lWrap) {
                $L = (isset($lWrap->obj) && \is_object($lWrap->obj)) ? $lWrap->obj : $lWrap;
                $cid = (int) ($L->category_id ?? 0);
                if ($cid > 0) {
                    $catIdsUsed[(string) $cid] = true;
                }
            }

            $catKey = $this->firstExistingKey(
                $orig,
                ['link_category', 'Link_Category', \defined('RESOURCE_LINKCATEGORY') ? (string) RESOURCE_LINKCATEGORY : '']
            );

            if ($catKey && !empty($catIdsUsed)) {
                $catBucket = $getBucket($orig, $catKey);
                if (!empty($catBucket)) {
                    $subset = array_intersect_key($catBucket, $catIdsUsed);
                    $keep[$catKey] = $subset;
                    $keep['link_category'] = $subset;

                    $this->logDebug('[filterSelection] pulled link categories for selected links', [
                        'link_key' => $lnkKey,
                        'category_key' => $catKey,
                        'links_kept' => \count($keep[$lnkKey]),
                        'cats_kept' => \count($subset),
                        'mirrored_to' => 'link_category',
                    ]);
                }
            } else {
                $this->logDebug('[filterSelection] link category bucket not found in backup');
            }
        }

        $keep = array_filter($keep);
        if (!empty($__metaBuckets)) {
            $course->resources = $__metaBuckets + $keep;
        } else {
            $course->resources = $keep;
        }

        $this->logDebug('[filterSelection] non-forum flow end', [
            'kept_types' => array_keys($course->resources),
        ]);

        return $course;
    }

    /**
     * Map UI options (1/2/3) to legacy file policy.
     */
    private function mapSameNameOption(int $opt): int
    {
        $opt = \in_array($opt, [1, 2, 3], true) ? $opt : 2;

        if (!\defined('FILE_SKIP')) {
            \define('FILE_SKIP', 1);
        }
        if (!\defined('FILE_RENAME')) {
            \define('FILE_RENAME', 2);
        }
        if (!\defined('FILE_OVERWRITE')) {
            \define('FILE_OVERWRITE', 3);
        }

        return match ($opt) {
            1 => FILE_SKIP,
            3 => FILE_OVERWRITE,
            default => FILE_RENAME,
        };
    }

    /**
     * Set debug mode from Request (query/header).
     */
    private function setDebugFromRequest(?Request $req): void
    {
        if (!$req) {
            return;
        }
        // Query param wins
        if ($req->query->has('debug')) {
            $this->debug = $req->query->getBoolean('debug');

            return;
        }
        // Fallback to header
        $hdr = $req->headers->get('X-Debug');
        if (null !== $hdr) {
            $val = trim((string) $hdr);
            $this->debug = ('' !== $val && '0' !== $val && 0 !== strcasecmp($val, 'false'));
        }
    }

    /**
     * Debug logger with stage + compact JSON payload.
     */
    private function logDebug(string $stage, mixed $payload = null): void
    {
        if (!$this->debug) {
            return;
        }
        $prefix = 'COURSE_DEBUG';
        if (null === $payload) {
            error_log("$prefix: $stage");

            return;
        }
        // Safe/short json
        $json = null;

        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (null !== $json && \strlen($json) > 8000) {
                $json = substr($json, 0, 8000).'…(truncated)';
            }
        } catch (Throwable $e) {
            $json = '[payload_json_error: '.$e->getMessage().']';
        }
        error_log("$prefix: $stage -> $json");
    }

    /**
     * Snapshot of resources bag for quick inspection.
     */
    private function snapshotResources(object $course, int $maxTypes = 20, int $maxItemsPerType = 3): array
    {
        $out = [];
        $res = \is_array($course->resources ?? null) ? $course->resources : [];
        $i = 0;
        foreach ($res as $type => $bag) {
            if ($i++ >= $maxTypes) {
                $out['__notice'] = 'types truncated';

                break;
            }
            $snap = ['count' => \is_array($bag) ? \count($bag) : 0, 'sample' => []];
            if (\is_array($bag)) {
                $j = 0;
                foreach ($bag as $id => $obj) {
                    if ($j++ >= $maxItemsPerType) {
                        $snap['sample'][] = ['__notice' => 'truncated'];

                        break;
                    }
                    $entity = (\is_object($obj) && isset($obj->obj) && \is_object($obj->obj)) ? $obj->obj : $obj;
                    $snap['sample'][] = [
                        'id' => (string) $id,
                        'cls' => \is_object($obj) ? $obj::class : \gettype($obj),
                        'entity_keys' => \is_object($entity) ? \array_slice(array_keys((array) $entity), 0, 12) : [],
                    ];
                }
            }
            $out[(string) $type] = $snap;
        }

        return $out;
    }

    /**
     * Snapshot of forum-family counters.
     */
    private function snapshotForumCounts(object $course): array
    {
        $r = \is_array($course->resources ?? null) ? $course->resources : [];
        $get = fn ($a, $b) => \is_array($r[$a] ?? $r[$b] ?? null) ? \count($r[$a] ?? $r[$b]) : 0;

        return [
            'Forum_Category' => $get('Forum_Category', 'forum_category'),
            'forum' => $get('forum', 'Forum'),
            'thread' => $get('thread', 'forum_topic'),
            'post' => $get('post', 'forum_post'),
        ];
    }

    /**
     * Builds the selection map [type => [id => true]] from high-level types.
     * Assumes that hydrateLpDependenciesFromSnapshot() has already been called, so
     * $course->resources contains LP + necessary dependencies (docs, links, quiz, etc.).
     *
     * @param object   $course        Legacy Course with already hydrated resources
     * @param string[] $selectedTypes Types marked by the UI (e.g., ['learnpath'])
     *
     * @return array<string, array<int|string, bool>>
     */
    private function buildSelectionFromTypes(object $course, array $selectedTypes): array
    {
        $selectedTypes = array_map(
            fn ($t) => $this->normalizeTypeKey((string) $t),
            $selectedTypes
        );

        $res = \is_array($course->resources ?? null) ? $course->resources : [];

        $coreDeps = [
            'document', 'link', 'quiz', 'work', 'survey',
            'Forum_Category', 'forum', 'thread', 'post',
            'exercise_question', 'survey_question', 'link_category',
        ];

        $presentKeys = array_fill_keys(array_map(
            fn ($k) => $this->normalizeTypeKey((string) $k),
            array_keys($res)
        ), true);

        $out = [];

        $addBucket = function (string $typeKey) use (&$out, $res): void {
            if (!isset($res[$typeKey]) || !\is_array($res[$typeKey]) || empty($res[$typeKey])) {
                return;
            }
            $ids = [];
            foreach ($res[$typeKey] as $id => $_) {
                $ids[(string) $id] = true;
            }
            if ($ids) {
                $out[$typeKey] = $ids;
            }
        };

        foreach ($selectedTypes as $t) {
            $addBucket($t);

            if ('learnpath' === $t) {
                foreach ($coreDeps as $depRaw) {
                    $dep = $this->normalizeTypeKey($depRaw);
                    if (isset($presentKeys[$dep])) {
                        $addBucket($dep);
                    }
                }
            }
        }

        $this->logDebug('[buildSelectionFromTypes] built', [
            'selectedTypes' => $selectedTypes,
            'kept_types' => array_keys($out),
        ]);

        return $out;
    }

    /**
     * Build link tree (Category → Link) for the UI.
     * Categories are not selectable; links are leaves (item_count = 0).
     */
    private function buildLinkTreeForVue(object $course, string $groupTitle): array
    {
        $this->logDebug('[buildLinkTreeForVue] start');

        $res = \is_array($course->resources ?? null) ? $course->resources : [];
        $catRaw = $res['link_category'] ?? $res['Link_Category'] ?? [];
        $linkRaw = $res['link'] ?? $res['Link'] ?? [];

        $this->logDebug('[buildLinkTreeForVue] raw counts', [
            'categories' => \is_array($catRaw) ? \count($catRaw) : 0,
            'links' => \is_array($linkRaw) ? \count($linkRaw) : 0,
        ]);

        $cats = [];
        foreach ($catRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            $e = $this->objectEntity($obj);
            $label = $this->resolveItemLabel('link_category', $e, $id);

            $cats[$id] = [
                'id' => $id,
                'type' => 'link_category',
                'label' => '' !== $label ? $label : ('Category #'.$id),
                'selectable' => false,
                'items' => [],
                'has_children' => false,
                'item_count' => 0,
            ];
        }

        // Virtual "Uncategorized"
        $uncatKey = -9999;
        if (!isset($cats[$uncatKey])) {
            $cats[$uncatKey] = [
                'id' => $uncatKey,
                'type' => 'link_category',
                'label' => 'Uncategorized',
                'selectable' => false,
                'items' => [],
                '_virtual' => true,
                'has_children' => false,
                'item_count' => 0,
            ];
        }

        // Assign links to categories
        foreach ($linkRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            $e = $this->objectEntity($obj);

            $cid = (int) ($e->category_id ?? 0);
            if (!isset($cats[$cid])) {
                $cid = $uncatKey;
            }

            $cats[$cid]['items'][] = [
                'id' => $id,
                'type' => 'link',
                'label' => $this->resolveItemLabel('link', $e, $id),
                'extra' => $this->buildExtra('link', $e) ?: new stdClass(),
                'selectable' => true,
                'item_count' => 0,
            ];
        }

        // Drop empty virtual category, sort, and finalize UI hints
        $catNodes = array_values(array_filter($cats, static function ($c) {
            if (!empty($c['_virtual']) && empty($c['items'])) {
                return false;
            }

            return true;
        }));

        foreach ($catNodes as &$c) {
            if (!empty($c['items'])) {
                usort($c['items'], static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
            }
            $c['has_children'] = !empty($c['items']);
            $c['item_count'] = \count($c['items'] ?? []);
        }
        unset($c);

        usort($catNodes, static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));

        $this->logDebug('[buildLinkTreeForVue] end', ['categories' => \count($catNodes)]);

        return [
            'type' => 'link',
            'title' => $groupTitle,
            'items' => $catNodes,
        ];
    }

    /**
     * Leaves only the items selected by the UI in $course->resources.
     * Expects $selected with the following form:
     * [
     * "documents" => ["123" => true, "124" => true],
     * "links" => ["7" => true],
     * "quiz" => ["45" => true],
     * ...
     * ].
     */
    private function filterCourseResources(object $course, array $selected): void
    {
        if (!isset($course->resources) || !\is_array($course->resources)) {
            return;
        }

        $typeMap = [
            'documents' => RESOURCE_DOCUMENT,
            'links' => RESOURCE_LINK,
            'quizzes' => RESOURCE_QUIZ,
            'quiz' => RESOURCE_QUIZ,
            'quiz_questions' => RESOURCE_QUIZQUESTION,
            'surveys' => RESOURCE_SURVEY,
            'survey' => RESOURCE_SURVEY,
            'survey_questions' => RESOURCE_SURVEYQUESTION,
            'announcements' => RESOURCE_ANNOUNCEMENT,
            'events' => RESOURCE_EVENT,
            'course_descriptions' => RESOURCE_COURSEDESCRIPTION,
            'glossary' => RESOURCE_GLOSSARY,
            'wiki' => RESOURCE_WIKI,
            'thematic' => RESOURCE_THEMATIC,
            'attendance' => RESOURCE_ATTENDANCE,
            'works' => RESOURCE_WORK,
            'gradebook' => RESOURCE_GRADEBOOK,
            'learnpaths' => RESOURCE_LEARNPATH,
            'learnpath_category' => RESOURCE_LEARNPATH_CATEGORY,
            'tool_intro' => RESOURCE_TOOL_INTRO,
            'forums' => RESOURCE_FORUM,
            'forum' => RESOURCE_FORUM,
            'forum_topic' => RESOURCE_FORUMTOPIC,
            'forum_post' => RESOURCE_FORUMPOST,
        ];

        $allowed = [];
        foreach ($selected as $k => $idsMap) {
            $key = $typeMap[$k] ?? $k;
            $allowed[$key] = array_fill_keys(array_map('intval', array_keys((array) $idsMap)), true);
        }

        foreach ($course->resources as $rtype => $bucket) {
            if (!isset($allowed[$rtype])) {
                continue;
            }
            $keep = $allowed[$rtype];
            $filtered = [];
            foreach ((array) $bucket as $id => $obj) {
                $iid = (int) ($obj->source_id ?? $id);
                if (isset($keep[$iid])) {
                    $filtered[$id] = $obj;
                }
            }
            $course->resources[$rtype] = $filtered;
        }
    }

    /**
     * Returns the absolute path of the backupId in the backup directory.
     */
    private function resolveBackupPath(string $backupId): string
    {
        $backupDir = rtrim(CourseArchiver::getBackupDir(), '/');

        return $backupDir.'/'.$backupId;
    }

    /**
     * Heuristic: Does it look like a Moodle package?
     * - If the extension (.mbz, .tgz, .gz) is used, we treat it as Moodle.
     * - If it's a .zip file but CourseArchiver fails or contains "moodle_backup.xml," it's also Moodle.
     */
    private function isLikelyMoodlePackage(string $path, ?Throwable $priorError = null): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (\in_array($ext, ['mbz', 'tgz', 'gz'], true)) {
            return true;
        }
        if ('zip' === $ext && $priorError) {
            return true;
        }

        return false;
    }

    /**
     * Load a legacy course from a Chamilo or Moodle backup, testing both readers.
     * - First try CourseArchiver (Chamilo).
     * - If that fails, retry MoodleImport.
     */
    private function loadLegacyCourseForAnyBackup(string $backupId): object
    {
        $path = $this->resolveBackupPath($backupId);
        $this->logDebug('[loadLegacyCourseForAnyBackup] try Chamilo first', ['path' => $path]);

        // Chamilo ZIP (course_info.dat)
        try {
            $course = CourseArchiver::readCourse($backupId, false);
            if (!\is_object($course) || empty($course->resources) || !\is_array($course->resources)) {
                throw new RuntimeException('Invalid Chamilo backup structure (empty resources)');
            }

            return $course;
        } catch (Throwable $e) {
            $this->logDebug('[loadLegacyCourseForAnyBackup] Chamilo reader failed, will try Moodle', ['err' => $e->getMessage()]);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!\in_array($ext, ['mbz', 'zip', 'gz', 'tgz'], true)) {
                throw $e;
            }
        }

        // Moodle (.mbz/.zip/.tgz)
        $this->logDebug('[loadLegacyCourseForAnyBackup] using MoodleImport');
        $importer = new MoodleImport(debug: $this->debug);
        $course = $importer->buildLegacyCourseFromMoodleArchive($path);

        if (!\is_object($course) || empty($course->resources) || !\is_array($course->resources)) {
            throw new RuntimeException('Moodle backup contains no importable resources');
        }

        return $course;
    }

    private function normalizeBucketsForRestorer(object $course): void
    {
        if (!isset($course->resources) || !\is_array($course->resources)) {
            return;
        }

        $map = [
            'link' => RESOURCE_LINK,
            'link_category' => RESOURCE_LINKCATEGORY,
            'forum' => RESOURCE_FORUM,
            'forum_category' => RESOURCE_FORUMCATEGORY,
            'forum_topic' => RESOURCE_FORUMTOPIC,
            'forum_post' => RESOURCE_FORUMPOST,
            'thread' => RESOURCE_FORUMTOPIC,
            'post' => RESOURCE_FORUMPOST,
            'document' => RESOURCE_DOCUMENT,
            'quiz' => RESOURCE_QUIZ,
            'exercise_question' => RESOURCE_QUIZQUESTION,
            'survey' => RESOURCE_SURVEY,
            'survey_question' => RESOURCE_SURVEYQUESTION,
            'tool_intro' => RESOURCE_TOOL_INTRO,
        ];

        $res = $course->resources;
        foreach ($map as $from => $to) {
            if (isset($res[$from]) && \is_array($res[$from])) {
                if (!isset($res[$to])) {
                    $res[$to] = $res[$from];
                }
                unset($res[$from]);
            }
        }

        $course->resources = $res;
    }

    /**
     * Read import_source without depending on filtered resources.
     * Falls back to $course->info['__import_source'] if needed.
     */
    private function getImportSource(object $course): string
    {
        $src = strtolower((string) ($course->resources['__meta']['import_source'] ?? ''));
        if ('' !== $src) {
            return $src;
        }

        // Fallbacks (defensive)
        return strtolower((string) ($course->info['__import_source'] ?? ''));
    }

    /**
     * Builds a CC 1.3 preview from the legacy Course (only the implemented one).
     * Returns a structure intended for rendering/committing before the actual export.
     */
    private function buildCc13Preview(object $course): array
    {
        $ims = [
            'supportedTypes' => Cc13Capabilities::exportableTypes(), // ['document']
            'resources' => [
                'webcontent' => [],
            ],
            'counts' => ['files' => 0, 'folders' => 0],
            'defaultSelection' => [
                'documents' => [],
            ],
        ];

        $res = \is_array($course->resources ?? null) ? $course->resources : [];
        $docKey = null;

        foreach (['document', 'Document', \defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : ''] as $cand) {
            if ($cand && isset($res[$cand]) && \is_array($res[$cand]) && !empty($res[$cand])) {
                $docKey = $cand;

                break;
            }
        }
        if (!$docKey) {
            return $ims;
        }

        foreach ($res[$docKey] as $iid => $wrap) {
            if (!\is_object($wrap)) {
                continue;
            }
            $e = (isset($wrap->obj) && \is_object($wrap->obj)) ? $wrap->obj : $wrap;

            $rawPath = (string) ($e->path ?? $e->full_path ?? '');
            if ('' === $rawPath) {
                continue;
            }
            $rel = ltrim(preg_replace('~^/?document/?~', '', $rawPath), '/');

            $fileType = strtolower((string) ($e->file_type ?? $e->filetype ?? ''));
            $isDir = ('folder' === $fileType) || ('/' === substr($rawPath, -1));

            $title = (string) ($e->title ?? $wrap->name ?? basename($rel));
            $ims['resources']['webcontent'][] = [
                'id' => (int) $iid,
                'cc_type' => 'webcontent',
                'title' => '' !== $title ? $title : basename($rel),
                'rel' => $rel,
                'is_dir' => $isDir,
                'would_be_manifest_entry' => !$isDir,
            ];

            if (!$isDir) {
                $ims['defaultSelection']['documents'][(int) $iid] = true;
                $ims['counts']['files']++;
            } else {
                $ims['counts']['folders']++;
            }
        }

        return $ims;
    }

    /**
     * Expand category selections (link/forum) to their item IDs using a full course snapshot.
     * Returns ['documents'=>[id=>true], 'links'=>[id=>true], 'forums'=>[id=>true]] merged with $normSel.
     */
    private function expandCc13SelectionFromCategories(object $course, array $normSel): array
    {
        $out = [
            'documents' => (array) ($normSel['documents'] ?? []),
            'links' => (array) ($normSel['links'] ?? []),
            'forums' => (array) ($normSel['forums'] ?? []),
        ];

        $res = \is_array($course->resources ?? null) ? $course->resources : [];

        // Link categories → link IDs
        if (!empty($normSel['link_category']) && \is_array($res['link'] ?? $res['Link'] ?? null)) {
            $selCats = array_fill_keys(array_map('strval', array_keys($normSel['link_category'])), true);
            $links = $res['link'] ?? $res['Link'];
            foreach ($links as $lid => $wrap) {
                if (!\is_object($wrap)) {
                    continue;
                }
                $e = (isset($wrap->obj) && \is_object($wrap->obj)) ? $wrap->obj : $wrap;
                $cid = (string) (int) ($e->category_id ?? 0);
                if (isset($selCats[$cid])) {
                    $out['links'][(string) $lid] = true;
                }
            }
        }

        // Forum categories → forum IDs
        if (!empty($normSel['forum_category']) && \is_array($res['forum'] ?? $res['Forum'] ?? null)) {
            $selCats = array_fill_keys(array_map('strval', array_keys($normSel['forum_category'])), true);
            $forums = $res['forum'] ?? $res['Forum'];
            foreach ($forums as $fid => $wrap) {
                if (!\is_object($wrap)) {
                    continue;
                }
                $e = (isset($wrap->obj) && \is_object($wrap->obj)) ? $wrap->obj : $wrap;
                $cid = (string) (int) ($e->forum_category ?? $e->forum_category_id ?? $e->category_id ?? 0);
                if (isset($selCats[$cid])) {
                    $out['forums'][(string) $fid] = true;
                }
            }
        }

        return $out;
    }
}
