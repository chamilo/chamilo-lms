<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CourseBundle\Component\CourseCopy\Course;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRecycler;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use CourseManager;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

use const ARRAY_FILTER_USE_BOTH;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

#[Route(
    '/course_maintenance/{node}',
    name: 'cm_',
    requirements: ['node' => '\d+']
)]
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
        if (!$file) {
            $this->logDebug('[importUpload] missing file');

            return $this->json(['error' => 'Missing file'], 400);
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
        $filename = $payload['filename'] ?? null;
        if (!$filename) {
            $this->logDebug('[importServerPick] missing filename');

            return $this->json(['error' => 'Missing filename'], 400);
        }

        $path = rtrim(CourseArchiver::getBackupDir(), '/').'/'.$filename;
        if (!is_file($path)) {
            $this->logDebug('[importServerPick] file not found', ['path' => $path]);

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
            /** @var Course $course */
            $course = CourseArchiver::readCourse($backupId, false);

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

            if ($this->debug && $req->query->getBoolean('debug')) {
                $base = $this->getParameter('kernel.project_dir').'/var/log/course_backup_debug';
                @mkdir($base, 0775, true);
                @file_put_contents(
                    $base.'/'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $backupId).'.json',
                    json_encode([
                        'tree' => $tree,
                        'resources_keys' => array_keys((array) ($course->resources ?? [])),
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );
                $this->logDebug('[importResources] wrote debug snapshot to var/log/course_backup_debug');
            }

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
                'tree' => [],
                'warnings' => ['Error reading backup: '.$e->getMessage()],
            ], 200);
        }
    }

    #[Route(
        '/import/{backupId}/restore',
        name: 'import_restore',
        requirements: ['backupId' => '.+'],
        methods: ['POST']
    )]
    public function importRestore(int $node, string $backupId, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);
        $this->logDebug('[importRestore] begin', ['node' => $node, 'backupId' => $backupId]);

        try {
            // Read payload
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

            // Resolve file path
            $backupDir = CourseArchiver::getBackupDir();
            $this->logDebug('[importRestore] backup dir', $backupDir);
            $path = rtrim($backupDir, '/').'/'.$backupId;
            $this->logDebug('[importRestore] path exists?', [
                'path' => $path,
                'exists' => is_file($path),
                'readable' => is_readable($path),
            ]);

            // Load legacy Course
            /** @var Course $course */
            $course = CourseArchiver::readCourse($backupId, false);

            if (!\is_object($course) || empty($course->resources) || !\is_array($course->resources)) {
                $this->logDebug('[importRestore] course empty resources');

                return $this->json(['error' => 'Backup has no resources'], 400);
            }

            // Snapshots before filtering (debug)
            $this->logDebug('[importRestore] BEFORE filter keys', array_keys($course->resources));
            $this->logDebug('[importRestore] BEFORE forum counts', $this->snapshotForumCounts($course));
            $this->logDebug('[importRestore] BEFORE resources snapshot', $this->snapshotResources($course));

            $resourcesAll = (array) ($course->resources ?? []);
            $this->logDebug('[importRestore] resources_all snapshot captured', ['keys' => array_keys($resourcesAll)]);

            // Partial selection logic
            if ('select_items' === $importOption) {
                $this->hydrateLpDependenciesFromSnapshot($course, $resourcesAll ?? []);

                // If the UI sent only high-level types (e.g., ["learnpath"]) and no item map,
                // build a resources selection map from those types.
                if (empty($selectedResources) && !empty($selectedTypes)) {
                    if (method_exists($this, 'buildSelectionFromTypes')) {
                        $selectedResources = $this->buildSelectionFromTypes($course, $selectedTypes);
                    }
                    $this->logDebug('[importRestore] built selection from types', [
                        'selectedTypes' => $selectedTypes,
                        'built_keys' => array_keys($selectedResources),
                    ]);
                }

                // Validate selection map
                $hasAny = false;
                foreach ($selectedResources as $t => $ids) {
                    if (\is_array($ids) && !empty($ids)) {
                        $hasAny = true;

                        break;
                    }
                }
                if (!$hasAny) {
                    $this->logDebug('[importRestore] empty selection');

                    return $this->json(['error' => 'No resources selected'], 400);
                }

                // Filter legacy course by selection (keeps only selected buckets/ids).
                // Dependency pulling for LP/quizzes/surveys/etc. should be handled inside the Restorer,
                // using the full snapshot we pass below (no dynamic properties on $course).
                $course = $this->filterLegacyCourseBySelection($course, $selectedResources);

                if (empty($course->resources) || 0 === \count((array) $course->resources)) {
                    $this->logDebug('[importRestore] selection produced no resources');

                    return $this->json(['error' => 'Selection produced no resources to restore'], 400);
                }
            }

            // Snapshots after filtering (debug)
            $this->logDebug('[importRestore] AFTER filter keys', array_keys($course->resources));
            $this->logDebug('[importRestore] AFTER forum counts', $this->snapshotForumCounts($course));
            $this->logDebug('[importRestore] AFTER resources snapshot', $this->snapshotResources($course));

            // Restore
            $restorer = new CourseRestorer($course);
            $restorer->set_file_option($this->mapSameNameOption($sameFileNameOption));

            if (method_exists($restorer, 'setResourcesAllSnapshot')) {
                $restorer->setResourcesAllSnapshot($resourcesAll);
                $this->logDebug('[importRestore] restorer snapshot forwarded', ['keys' => array_keys($resourcesAll)]);
            }
            if (method_exists($restorer, 'setDebug')) {
                $restorer->setDebug($this->debug);
            }

            $restorer->restore();

            $this->logDebug('[importRestore] restore() finished', [
                'dest_course_id' => $restorer->destination_course_info['real_id'] ?? null,
            ]);

            // Cleanup temporary backup dir
            CourseArchiver::cleanBackupDir();

            // Redirect info
            $courseId = (int) ($restorer->destination_course_info['real_id'] ?? 0);
            $sessionId = 0;
            $groupId = 0;
            $redirectUrl = \sprintf('/course/%d/home?sid=%d&gid=%d', $courseId, $sessionId, $groupId);

            $this->logDebug('[importRestore] done, redirect', ['url' => $redirectUrl]);

            return $this->json([
                'ok' => true,
                'message' => 'Import finished',
                'redirectUrl' => $redirectUrl,
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
    public function moodleExportOptions(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        // Defaults for the UI
        $payload = [
            'versions' => [
                ['value' => '3', 'label' => 'Moodle 3.x'],
                ['value' => '4', 'label' => 'Moodle 4.x'],
            ],
            'defaults' => [
                'moodleVersion' => '4',
                'scope' => 'full', // 'full' | 'selected'
            ],
            // Optional friendly note until real export is implemented
            'message' => 'Moodle export endpoints are under construction.',
        ];

        return $this->json($payload);
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
    public function moodleExportExecute(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        // Read payload (basic validation)
        $p = json_decode($req->getContent() ?: '{}', true);
        $moodleVersion = (string) ($p['moodleVersion'] ?? '4');     // '3' | '4'
        $scope = (string) ($p['scope'] ?? 'full');          // 'full' | 'selected'
        $adminId = trim((string) ($p['adminId'] ?? ''));
        $adminLogin = trim((string) ($p['adminLogin'] ?? ''));
        $adminEmail = trim((string) ($p['adminEmail'] ?? ''));
        $resources = (array) ($p['resources'] ?? []);

        if ('' === $adminId || '' === $adminLogin || '' === $adminEmail) {
            return $this->json(['error' => 'Missing required fields (adminId, adminLogin, adminEmail)'], 400);
        }
        if ('selected' === $scope && empty($resources)) {
            return $this->json(['error' => 'No resources selected'], 400);
        }
        if (!\in_array($moodleVersion, ['3', '4'], true)) {
            return $this->json(['error' => 'Unsupported Moodle version'], 400);
        }

        // Stub response while implementation is in progress
        // Use 202 so the frontend can show a notice without treating it as a failure.
        return new JsonResponse([
            'ok' => false,
            'message' => 'Moodle export is under construction. No .mbz file was generated.',
            // you may also return a placeholder downloadUrl later
            // 'downloadUrl' => null,
        ], 202);
    }

    #[Route('/cc13/export/options', name: 'cc13_export_options', methods: ['GET'])]
    public function cc13ExportOptions(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        return $this->json([
            'defaults' => [
                'scope' => 'full', // 'full' | 'selected'
            ],
            'message' => 'Common Cartridge 1.3 export is under construction. You can already pick items and submit.',
        ]);
    }

    #[Route('/cc13/export/resources', name: 'cc13_export_resources', methods: ['GET'])]
    public function cc13ExportResources(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

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

    #[Route('/cc13/export/execute', name: 'cc13_export_execute', methods: ['POST'])]
    public function cc13ExportExecute(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        $p = json_decode($req->getContent() ?: '{}', true);
        $scope = (string) ($p['scope'] ?? 'full');   // 'full' | 'selected'
        $resources = (array) ($p['resources'] ?? []);

        if (!\in_array($scope, ['full', 'selected'], true)) {
            return $this->json(['error' => 'Unsupported scope'], 400);
        }
        if ('selected' === $scope && empty($resources)) {
            return $this->json(['error' => 'No resources selected'], 400);
        }

        // TODO: Generate IMS CC 1.3 cartridge (.imscc or .zip)
        // For now, return an informative 202 “under construction”.
        return new JsonResponse([
            'ok' => false,
            'message' => 'Common Cartridge 1.3 export is under construction. No file was generated.',
            // 'downloadUrl' => null, // set when implemented
        ], 202);
    }

    #[Route('/cc13/import', name: 'cc13_import', methods: ['POST'])]
    public function cc13Import(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        $file = $req->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Missing file'], 400);
        }
        $ext = strtolower(pathinfo($file->getClientOriginalName() ?? '', PATHINFO_EXTENSION));
        if (!\in_array($ext, ['imscc', 'zip'], true)) {
            return $this->json(['error' => 'Unsupported file type. Please upload .imscc or .zip'], 400);
        }

        // TODO: Parse/restore CC 1.3. For now, just acknowledge.
        return $this->json([
            'ok' => true,
            'message' => 'CC 1.3 import endpoint is under construction. File received successfully.',
        ]);
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
     * Build forum tree (Category → Forum → Topic).
     */
    private function buildForumTreeForVue(object $course, string $groupTitle): array
    {
        $this->logDebug('[buildForumTreeForVue] start');

        $res = \is_array($course->resources ?? null) ? $course->resources : [];

        // Buckets (accept legacy casings / aliases)
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

        // Classifiers (defensive)
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

        // Categories
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
                'children' => [],
            ];
        }
        $uncatKey = -9999;
        if (!isset($cats[$uncatKey])) {
            $cats[$uncatKey] = [
                'id' => $uncatKey,
                'type' => 'forum_category',
                'label' => 'Uncategorized',
                'selectable' => false,
                'children' => [],
                '_virtual' => true,
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
                $this->logDebug('[buildForumTreeForVue] skipped non-forum in forum bag', ['id' => $id]);

                continue;
            }
            $forums[$id] = $this->objectEntity($obj);
        }

        // Topics + post counts
        $topics = [];
        $postCountByTopic = [];
        foreach ($topicRaw as $id => $obj) {
            $id = (int) $id;
            if ($id <= 0 || !\is_object($obj)) {
                continue;
            }
            if ($isForum($obj) && !$isTopic($obj)) {
                $this->logDebug('[buildForumTreeForVue] WARNING: forum object found in topic bag; skipping', ['id' => $id]);

                continue;
            }
            if (!$isTopic($obj)) {
                $this->logDebug('[buildForumTreeForVue] skipped non-topic in topic bag', ['id' => $id]);

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

        // Forums → attach topics
        // Forums → attach topics
        foreach ($forums as $fid => $f) {
            $catId = $getForumCategoryId($f);
            if (!isset($cats[$catId])) {
                $catId = $uncatKey;
            }

            // Build the forum node first (children will be appended below)
            $forumNode = [
                'id' => $fid,
                'type' => 'forum',
                'label' => $this->resolveItemLabel('forum', $f, $fid),
                'extra' => $this->buildExtra('forum', $f) ?: new stdClass(),
                'selectable' => true,
                'children' => [],
                // UI hints (do not affect structure)
                'has_children' => false,  // will become true if a topic is attached
                'ui_depth' => 2,      // category=1, forum=2, topic=3 (purely informational)
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

                $forumNode['children'][] = [
                    'id' => $tid,
                    'type' => 'forum_topic',
                    'label' => $topicLabel,
                    'extra' => new stdClass(),
                    'selectable' => true,
                    'ui_depth' => 3,
                ];
            }

            // sort topics (if any) and mark has_children for UI
            if ($forumNode['children']) {
                usort($forumNode['children'], static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
                $forumNode['has_children'] = true; // <- tell UI to show a reserved toggle space
            }

            $cats[$catId]['children'][] = $forumNode;
        }

        // Drop empty virtual category and sort forums per category
        $catNodes = array_values(array_filter($cats, static function ($c) {
            if (!empty($c['_virtual']) && empty($c['children'])) {
                return false;
            }

            return true;
        }));

        // --------- FLATTEN STRAY FORUMS (defensive) ----------
        foreach ($catNodes as &$cat) {
            if (empty($cat['children'])) {
                continue;
            }

            $lift = [];           // forums to lift to category level
            foreach ($cat['children'] as $idx => &$forumNode) {
                if (($forumNode['type'] ?? '') !== 'forum') {
                    continue;
                }
                if (empty($forumNode['children'])) {
                    continue;
                }

                // scan children and lift any forum wrongly nested inside
                $keepChildren = [];
                foreach ($forumNode['children'] as $child) {
                    if (($child['type'] ?? '') === 'forum') {
                        // move this stray forum up to category level
                        $lift[] = $child;
                        $this->logDebug('[buildForumTreeForVue] flatten: lifted stray forum from inside another forum', [
                            'parent_forum_id' => $forumNode['id'] ?? null,
                            'lifted_forum_id' => $child['id'] ?? null,
                            'cat_id' => $cat['id'] ?? null,
                        ]);
                    } else {
                        $keepChildren[] = $child; // keep real topics
                    }
                }
                $forumNode['children'] = $keepChildren;
            }
            unset($forumNode);

            // Append lifted forums as siblings (top-level under the category)
            if ($lift) {
                foreach ($lift as $n) {
                    $cat['children'][] = $n;
                }
            }

            // sort forums at category level
            usort($cat['children'], static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
        }
        unset($cat);
        // --------- /FLATTEN STRAY FORUMS ----------

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
            'SurveyQuestion' => 'survey_question',
            'SurveyInvitation' => 'survey_invitation',
            'Survey' => 'survey',
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

                $course->resources = array_filter($out);

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

        $course->resources = array_filter($keep);
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
     * Build link tree (Category → Link). Categories are not selectable; links are.
     */
    private function buildLinkTreeForVue(object $course, string $groupTitle): array
    {
        $this->logDebug('[buildLinkTreeForVue] start');

        $res = \is_array($course->resources ?? null) ? $course->resources : [];

        // Buckets from backup (accept both legacy casings)
        $catRaw = $res['link_category'] ?? $res['Link_Category'] ?? [];
        $linkRaw = $res['link'] ?? $res['Link'] ?? [];

        $this->logDebug('[buildLinkTreeForVue] raw counts', [
            'categories' => \is_array($catRaw) ? \count($catRaw) : 0,
            'links' => \is_array($linkRaw) ? \count($linkRaw) : 0,
        ]);

        // Map of categories
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
                'children' => [],
            ];
        }

        // Virtual "Uncategorized" bucket
        $uncatKey = -9999;
        if (!isset($cats[$uncatKey])) {
            $cats[$uncatKey] = [
                'id' => $uncatKey,
                'type' => 'link_category',
                'label' => 'Uncategorized',
                'selectable' => false,
                'children' => [],
                '_virtual' => true,
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

            $cats[$cid]['children'][] = [
                'id' => $id,
                'type' => 'link',
                'label' => $this->resolveItemLabel('link', $e, $id),
                'extra' => $this->buildExtra('link', $e) ?: new stdClass(),
                'selectable' => true,
            ];
        }

        // Drop empty virtual category and sort
        $catNodes = array_values(array_filter($cats, static function ($c) {
            if (!empty($c['_virtual']) && empty($c['children'])) {
                return false;
            }

            return true;
        }));

        foreach ($catNodes as &$c) {
            if (!empty($c['children'])) {
                usort($c['children'], static fn ($a, $b) => strcasecmp((string) $a['label'], (string) $b['label']));
            }
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
}
