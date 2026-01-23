<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Builder\Cc13Capabilities;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Builder\Cc13Export;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Imscc13Import;
use Chamilo\CourseBundle\Component\CourseCopy\Course;
use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRecycler;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use CourseManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use stdClass;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;
use UnserializeApi;
use ZipArchive;

use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_BOOL;
use const PATHINFO_EXTENSION;

#[IsGranted('ROLE_TEACHER')]
#[Route('/course_maintenance/{node}', name: 'cm_', requirements: ['node' => '\d+'])]
class CourseMaintenanceController extends AbstractCourseMaintenanceController
{
    private const IMPORT_ALLOWED_EXTENSIONS = ['zip', 'mbz', 'gz', 'tgz'];
    private const CC13_ALLOWED_EXTENSIONS = ['imscc', 'zip'];

    private const LEGACY_SNAPSHOT_TOOLS = [
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
    ];

    private const MOODLE_EXPORT_DEFAULT_TOOLS = [
        'documents', 'links', 'forums',
        'quizzes', 'quiz_questions',
        'surveys', 'survey_questions',
        'learnpaths', 'learnpath_category',
        'works', 'glossary',
        'course_descriptions',
    ];

    private const MOODLE_EXPORT_RESOURCE_PICKER_DEFAULT_TOOLS = [
        'documents', 'links', 'forums',
        'quizzes', 'quiz_questions',
        'surveys', 'survey_questions',
        'learnpaths', 'learnpath_category',
        'works', 'glossary',
        'tool_intro',
        'course_descriptions',
    ];

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
            return $this->jsonError('Invalid upload', 400);
        }

        $maxBytes = self::getPhpUploadLimitBytes();
        $fileSize = (int) ($file->getSize() ?? 0);
        if ($maxBytes > 0 && $fileSize > $maxBytes) {
            return $this->jsonError('File too large', 413, ['maxBytes' => $maxBytes]);
        }

        $ext = $this->detectUploadExtension($file->guessExtension(), $file->getClientOriginalName());
        if (!\in_array($ext, self::IMPORT_ALLOWED_EXTENSIONS, true)) {
            return $this->jsonError('Unsupported file type', 415, ['allowed' => self::IMPORT_ALLOWED_EXTENSIONS]);
        }

        $this->logDebug('[importUpload] received', [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'ext' => $ext,
        ]);

        $backupId = CourseArchiver::importUploadedFile($file->getRealPath());
        if (false === $backupId) {
            $this->logDebug('[importUpload] archive dir not writable');

            return $this->jsonError('Archive directory is not writable', 500);
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
        $payload = $this->getJsonPayload($req);

        $filename = $this->validateBackupFilename((string) ($payload['filename'] ?? ''));
        if ('' === $filename) {
            return $this->jsonError('Invalid filename', 400);
        }

        $path = rtrim((string) CourseArchiver::getBackupDir(), '/').'/'.$filename;
        $realBase = realpath((string) CourseArchiver::getBackupDir());
        $realPath = realpath($path);

        if (
            !$realBase
            || !$realPath
            || 0 !== strncmp($realBase, $realPath, \strlen($realBase))
            || !is_file($realPath)
        ) {
            $this->logDebug('[importServerPick] file not found or outside base', ['path' => $path]);

            return $this->jsonError('File not found', 404);
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

        $mode = strtolower((string) $req->query->get('mode', 'auto')); // 'auto' | 'dat' | 'moodle'
        $source = ('dat' === $mode) ? 'chamilo' : $mode;

        $course = $this->loadLegacyCourseForAnyBackup($backupId, $source);

        $this->logDebug('[importResources] course loaded', [
            'has_resources' => \is_array($course->resources ?? null),
            'keys' => array_keys((array) ($course->resources ?? [])),
        ]);

        $tree = $this->buildResourceTreeForVue($course);

        return $this->json([
            'tree' => $tree,
            'warnings' => empty($tree) ? ['Backup has no selectable resources.'] : [],
            'meta' => ['import_source' => $course->resources['__meta']['import_source'] ?? null],
        ]);
    }

    #[Route(
        '/import/{backupId}/restore',
        name: 'import_restore',
        requirements: ['backupId' => '.+'],
        methods: ['POST']
    )]
    public function importRestore(int $node, string $backupId, Request $req, EntityManagerInterface $em): JsonResponse
    {
        $this->setDebugFromRequest($req);
        $this->logDebug('[importRestore] begin', ['node' => $node, 'backupId' => $backupId]);

        try {
            $payload = $this->getJsonPayload($req);

            $mode = strtolower((string) ($payload['mode'] ?? 'auto'));
            $source = ('dat' === $mode) ? 'chamilo' : $mode;

            $importOption = (string) ($payload['importOption'] ?? 'full_backup'); // full_backup | select_items
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
                'mode' => $mode,
            ]);

            $course = $this->loadLegacyCourseForAnyBackup($backupId, $source);
            if (!\is_object($course) || empty($course->resources) || !\is_array($course->resources)) {
                return $this->jsonError('Backup has no resources', 400);
            }

            $resourcesAll = $course->resources;

            // Always hydrate LP dependencies (even in full_backup).
            $this->hydrateLpDependenciesFromSnapshot($course, $resourcesAll);

            // Detect source BEFORE any filtering
            $importSource = $this->getImportSource($course);
            $isMoodle = ('moodle' === $importSource);

            $this->logDebug('[importRestore] detected import source', [
                'import_source' => $importSource,
                'isMoodle' => $isMoodle,
            ]);

            // Selection (optional)
            if ('select_items' === $importOption) {
                $selectedResources = $this->ensureSelectionMap($course, $selectedResources, $selectedTypes);

                $course = $this->filterLegacyCourseBySelection($course, $selectedResources);
                if (empty($course->resources) || 0 === \count((array) $course->resources)) {
                    return $this->jsonError('Selection produced no resources to restore', 400);
                }
            }

            // NON-MOODLE -> CourseRestorer
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

            // MOODLE -> MoodleImport restore* family
            $this->logDebug('[importRestore] Moodle backup -> using MoodleImport.*');

            $backupPath = $this->resolveBackupPath($backupId);

            $ci = api_get_course_info();
            $cid = (int) ($ci['real_id'] ?? 0);
            $sid = 0;

            $wantedGroups = $this->computeWantedMoodleGroups($course);
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

            if (!empty($wantedGroups['links']) && method_exists($importer, 'restoreLinks')) {
                $stats['links'] = $importer->restoreLinks($backupPath, $em, $cid, $sid, $course);
            }
            if (!empty($wantedGroups['forums']) && method_exists($importer, 'restoreForums')) {
                $stats['forums'] = $importer->restoreForums($backupPath, $em, $cid, $sid, $course);
            }
            if (!empty($wantedGroups['documents']) && method_exists($importer, 'restoreDocuments')) {
                $stats['documents'] = $importer->restoreDocuments($backupPath, $em, $cid, $sid, $sameFileNameOption, $course);
            }
            if (!empty($wantedGroups['quizzes']) && method_exists($importer, 'restoreQuizzes')) {
                $stats['quizzes'] = $importer->restoreQuizzes($backupPath, $em, $cid, $sid);
            }
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
            if ((int) $c['real_id'] === (int) ($current['real_id'] ?? 0)) {
                continue;
            }
            $courses[] = [
                'id' => (string) $c['code'],
                'code' => (string) $c['code'],
                'title' => (string) $c['title'],
            ];
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
            return $this->jsonError('Missing sourceCourseId', 400);
        }

        $cb = new CourseBuilder();
        $cb->set_tools_to_build(self::LEGACY_SNAPSHOT_TOOLS);

        $course = $cb->build(0, $sourceCourseCode);
        $tree = $this->buildResourceTreeForVue($course);

        return $this->json([
            'tree' => $tree,
            'warnings' => empty($tree) ? ['Source course has no resources.'] : [],
        ]);
    }

    #[Route('/copy/execute', name: 'copy_execute', methods: ['POST'])]
    public function copyExecute(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        try {
            $payload = $this->getJsonPayload($req);

            $sourceCourseId = (string) ($payload['sourceCourseId'] ?? '');
            $copyOption = (string) ($payload['copyOption'] ?? 'full_copy'); // full_copy | select_items
            $sameFileNameOption = (int) ($payload['sameFileNameOption'] ?? 2);
            $selectedResourcesMap = (array) ($payload['resources'] ?? []);

            if ('' === $sourceCourseId) {
                return $this->jsonError('Missing sourceCourseId', 400);
            }

            $cb = new CourseBuilder('partial');
            $cb->set_tools_to_build(self::LEGACY_SNAPSHOT_TOOLS);

            $legacyCourse = $cb->build(0, $sourceCourseId);

            if ('select_items' === $copyOption) {
                $legacyCourse = $this->filterLegacyCourseBySelection($legacyCourse, $selectedResourcesMap);

                if (empty($legacyCourse->resources) || !\is_array($legacyCourse->resources)) {
                    return $this->jsonError('Selection produced no resources to copy', 400);
                }
            }

            $restorer = new CourseRestorer($legacyCourse);
            $restorer->set_file_option($this->mapSameNameOption($sameFileNameOption));

            if (method_exists($restorer, 'setDebug')) {
                $restorer->setDebug($this->debug);
            }

            $restorer->restore();

            $dest = api_get_course_info();
            $redirectUrl = \sprintf('/course/%d/home', (int) ($dest['real_id'] ?? 0));

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

        return $this->json([
            'defaults' => [
                'recycleOption' => 'select_items', // full_recycle | select_items
                'confirmNeeded' => true,           // show code-confirm input when full
            ],
        ]);
    }

    #[Route('/recycle/resources', name: 'recycle_resources', methods: ['GET'])]
    public function recycleResources(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        $cb = new CourseBuilder();
        $cb->set_tools_to_build(self::LEGACY_SNAPSHOT_TOOLS);

        $course = $cb->build(0, api_get_course_id());
        $tree = $this->buildResourceTreeForVue($course);

        return $this->json([
            'tree' => $tree,
            'warnings' => empty($tree) ? ['This course has no resources.'] : [],
        ]);
    }

    #[Route('/recycle/execute', name: 'recycle_execute', methods: ['POST'])]
    public function recycleExecute(Request $req, EntityManagerInterface $em): JsonResponse
    {
        $this->setDebugFromRequest($req);

        try {
            $p = $this->getJsonPayload($req);

            $recycleOption = (string) ($p['recycleOption'] ?? 'select_items'); // full_recycle | select_items
            $resourcesMap = (array) ($p['resources'] ?? []);
            $confirmCode = (string) ($p['confirm'] ?? '');

            $type = ('full_recycle' === $recycleOption) ? 'full_backup' : 'select_items';

            if ('full_backup' === $type) {
                if ($confirmCode !== api_get_course_id()) {
                    return $this->jsonError('Course code confirmation mismatch', 400);
                }
            } else {
                if (empty($resourcesMap)) {
                    return $this->jsonError('No resources selected', 400);
                }
            }

            $courseCode = api_get_course_id();
            $courseInfo = api_get_course_info($courseCode);
            $courseId = (int) ($courseInfo['real_id'] ?? 0);
            if ($courseId <= 0) {
                return $this->jsonError('Invalid course id', 400);
            }

            $recycler = new CourseRecycler($em, $courseCode, $courseId);
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
        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_TEACHER')
            && !$this->isGranted('ROLE_CURRENT_COURSE_TEACHER')
        ) {
            return $this->jsonError('You are not allowed to delete this course', 403);
        }

        $this->setDebugFromRequest($req);

        try {
            $payload = $this->getJsonPayload($req);
            $confirm = trim((string) ($payload['confirm'] ?? ''));

            if ('' === $confirm) {
                return $this->jsonError('Missing confirmation value', 400);
            }

            // Optional: also delete orphan documents that belong only to this course
            $deleteDocsRaw = $payload['delete_docs'] ?? 0;
            $deleteDocs = (bool) filter_var($deleteDocsRaw, FILTER_VALIDATE_BOOL);

            $courseInfo = api_get_course_info();
            if (empty($courseInfo)) {
                return $this->jsonError('Unable to resolve current course', 400);
            }

            $officialCode = (string) ($courseInfo['official_code'] ?? '');
            $runtimeCode = (string) api_get_course_id();
            $sysCode = (string) ($courseInfo['sysCode'] ?? '');

            if ('' === $sysCode) {
                return $this->jsonError('Invalid course system code', 400);
            }

            $matches = hash_equals($officialCode, $confirm) || hash_equals($runtimeCode, $confirm);
            if (!$matches) {
                return $this->jsonError('Course code confirmation mismatch', 400);
            }

            CourseManager::delete_course($sysCode, $deleteDocs);

            // Best-effort cleanup
            try {
                $ses = $req->getSession();
                $ses?->remove('_cid');
                $ses?->remove('_real_cid');
            } catch (Throwable) {
                // Not critical
            }

            return $this->json([
                'ok' => true,
                'message' => 'Course deleted successfully',
                'redirectUrl' => '/index.php',
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
        $this->setDebugFromRequest($req);

        $defaults = [
            'moodleVersion' => '4',
            'scope' => 'full',
            'admin' => $users->getDefaultAdminForExport(),
        ];

        $tools = [
            ['value' => 'documents', 'label' => 'Documents (files & root HTML pages)'],
            ['value' => 'links', 'label' => 'Links (URL)'],
            ['value' => 'forums', 'label' => 'Forums'],
            ['value' => 'quizzes', 'label' => 'Quizzes', 'implies' => ['quiz_questions']],
            ['value' => 'surveys', 'label' => 'Surveys', 'implies' => ['survey_questions']],
            ['value' => 'works', 'label' => 'Tasks'],
            ['value' => 'glossary', 'label' => 'Glossary'],
            ['value' => 'learnpaths', 'label' => 'Paths learning'],
            ['value' => 'tool_intro', 'label' => 'Course Introduction'],
            ['value' => 'course_description', 'label' => 'Course descriptions'],
        ];

        $defaults['tools'] = array_column($tools, 'value');

        return $this->json([
            'versions' => [
                ['value' => '3', 'label' => 'Moodle 3.x'],
                ['value' => '4', 'label' => 'Moodle 4.x'],
            ],
            'tools' => $tools,
            'defaults' => $defaults,
        ]);
    }

    #[Route('/moodle/export/resources', name: 'moodle_export_resources', methods: ['GET'])]
    public function moodleExportResources(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);
        $this->logDebug('[moodleExportResources] start', ['node' => $node]);

        try {
            $selectedTools = $this->normalizeSelectedTools($req->query->all('tools'));
            $toolsToBuild = !empty($selectedTools)
                ? $selectedTools
                : self::MOODLE_EXPORT_RESOURCE_PICKER_DEFAULT_TOOLS;

            // Policy: never show gradebook, always include tool_intro.
            $toolsToBuild = array_values(array_diff($toolsToBuild, ['gradebook']));
            if (!\in_array('tool_intro', $toolsToBuild, true)) {
                $toolsToBuild[] = 'tool_intro';
            }
            $toolsToBuild = array_values(array_unique($toolsToBuild));

            $this->logDebug('[moodleExportResources] tools to build', $toolsToBuild);

            $cb = new CourseBuilder();
            $cb->set_tools_to_build($toolsToBuild);
            $course = $cb->build(0, api_get_course_id());

            $tree = $this->buildResourceTreeForVue($course);

            if ($this->debug) {
                $this->logDebug(
                    '[moodleExportResources] tree summary',
                    array_map(
                        static fn ($g) => [
                            'type' => $g['type'] ?? '',
                            'title' => $g['title'] ?? '',
                            'items' => isset($g['items']) ? \count((array) $g['items']) : null,
                            'children' => isset($g['children']) ? \count((array) $g['children']) : null,
                        ],
                        $tree
                    )
                );
            }

            return $this->json([
                'tree' => $tree,
                'warnings' => empty($tree) ? ['This course has no resources.'] : [],
            ]);
        } catch (Throwable $e) {
            $this->logDebug('[moodleExportResources] exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return $this->json([
                'error' => 'Failed to build resource tree for Moodle export.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/moodle/export/execute', name: 'moodle_export_execute', methods: ['POST'])]
    public function moodleExportExecute(int $node, Request $req, UserRepository $users): BinaryFileResponse|JsonResponse
    {
        $this->setDebugFromRequest($req);

        $p = $this->getJsonPayload($req);

        $moodleVersion = (string) ($p['moodleVersion'] ?? '4'); // "3" | "4"
        $scope = (string) ($p['scope'] ?? 'full');              // "full" | "selected"

        $adminId = (int) ($p['adminId'] ?? 0);
        $adminLogin = trim((string) ($p['adminLogin'] ?? ''));
        $adminEmail = trim((string) ($p['adminEmail'] ?? ''));

        $selected = \is_array($p['resources'] ?? null) ? (array) $p['resources'] : [];
        $toolsInput = \is_array($p['tools'] ?? null) ? (array) $p['tools'] : [];

        if (!\in_array($moodleVersion, ['3', '4'], true)) {
            return $this->jsonError('Unsupported Moodle version', 400);
        }
        if ('selected' === $scope && empty($selected)) {
            return $this->jsonError('No resources selected', 400);
        }

        $tools = $this->normalizeSelectedTools($toolsInput);

        // If scope=selected, merge inferred tools from selection
        if ('selected' === $scope) {
            $inferred = $this->inferToolsFromSelection($selected);
            $tools = $this->normalizeSelectedTools(array_merge($tools, $inferred));
        }

        // Remove unsupported tools + dedupe
        $tools = array_values(array_unique(array_diff($tools, ['gradebook'])));

        $clientSentNoTools = empty($toolsInput);
        $toolsToBuild = ('full' === $scope && $clientSentNoTools) ? self::MOODLE_EXPORT_DEFAULT_TOOLS : $tools;

        // Always ensure tool_intro exists
        if (!\in_array('tool_intro', $toolsToBuild, true)) {
            $toolsToBuild[] = 'tool_intro';
        }
        $toolsToBuild = array_values(array_unique($toolsToBuild));

        $this->logDebug('[moodleExportExecute] course tools to build (final)', $toolsToBuild);

        if ($adminId <= 0 || '' === $adminLogin || '' === $adminEmail) {
            $adm = $users->getDefaultAdminForExport();
            $adminId = $adminId > 0 ? $adminId : (int) ($adm['id'] ?? 1);
            $adminLogin = '' !== $adminLogin ? $adminLogin : (string) ($adm['username'] ?? 'admin');
            $adminEmail = '' !== $adminEmail ? $adminEmail : (string) ($adm['email'] ?? 'admin@example.com');
        }

        $courseId = api_get_course_id();
        if (empty($courseId)) {
            return $this->jsonError('No active course context', 400);
        }

        $cb = new CourseBuilder();
        $cb->set_tools_to_build($toolsToBuild);
        $course = $cb->build(0, $courseId);

        if ('selected' === $scope) {
            $course = $this->filterLegacyCourseBySelection($course, $selected);
            if (empty($course->resources) || !\is_array($course->resources)) {
                return $this->jsonError('Selection produced no resources to export', 400);
            }
        }

        try {
            $selectionMode = ('selected' === $scope);

            $exporter = new MoodleExport($course, $selectionMode);
            $exporter->setAdminUserData($adminId, $adminLogin, $adminEmail);

            $exportDir = 'moodle_export_'.date('Ymd_His');
            $versionNum = ('3' === $moodleVersion) ? 3 : 4;

            $this->logDebug('[moodleExportExecute] starting exporter', [
                'courseId' => $courseId,
                'exportDir' => $exportDir,
                'versionNum' => $versionNum,
                'selection' => $selectionMode,
                'scope' => $scope,
            ]);

            $mbzPath = $exporter->export($courseId, $exportDir, $versionNum);

            if (!\is_string($mbzPath) || '' === $mbzPath || !is_file($mbzPath)) {
                return $this->jsonError('Moodle export failed: artifact not found', 500);
            }

            $resp = new BinaryFileResponse($mbzPath);
            $resp->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($mbzPath));
            $resp->headers->set('X-Moodle-Version', (string) $versionNum);
            $resp->headers->set('X-Export-Scope', $scope);
            $resp->headers->set('X-Selection-Mode', $selectionMode ? '1' : '0');

            return $resp;
        } catch (Throwable $e) {
            $this->logDebug('[moodleExportExecute] exception', [
                'message' => $e->getMessage(),
                'code' => (int) $e->getCode(),
            ]);

            return $this->jsonError('Moodle export failed: '.$e->getMessage(), 500);
        }
    }

    #[Route('/cc13/export/options', name: 'cc13_export_options', methods: ['GET'])]
    public function cc13ExportOptions(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        return $this->json([
            'defaults' => ['scope' => 'full'],
            'supportedTypes' => Cc13Capabilities::exportableTypes(),
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

        return $this->json([
            'supportedTypes' => Cc13Capabilities::exportableTypes(),
            'tree' => $tree,
            'preview' => ['counts' => ['total' => $exportableCount]],
            'warnings' => (0 === $exportableCount)
                ? ['This course has no CC 1.3 exportable resources (documents, links or forums).']
                : [],
        ]);
    }

    #[Route('/cc13/export/execute', name: 'cc13_export_execute', methods: ['POST'])]
    public function cc13ExportExecute(int $node, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);

        $payload = $this->getJsonPayload($req);

        $scope = (string) ($payload['scope'] ?? (!empty($payload['resources']) ? 'selected' : 'full'));
        $selected = (array) ($payload['resources'] ?? []);

        $normSel = Cc13Capabilities::filterSelection($selected);

        $tools = ['documents', 'links', 'forums'];
        $cb = new CourseBuilder();

        $selectionMode = false;

        try {
            /** @var Course|null $courseFull */
            $courseFull = null;

            if ('selected' === $scope) {
                $cbFull = new CourseBuilder();
                $cbFull->set_tools_to_build($tools);
                $courseFull = $cbFull->build(0, api_get_course_id());

                $expanded = $this->expandCc13SelectionFromCategories($courseFull, $normSel);

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
                    return $this->jsonError('Please select at least one resource.', 400);
                }

                $cb->set_tools_to_build($tools);
                $cb->set_tools_specific_id_list($map);
                $selectionMode = true;
            } else {
                $cb->set_tools_to_build($tools);
            }

            $course = $cb->build(0, api_get_course_id());

            if ($selectionMode) {
                $safeSelected = [
                    'documents' => array_fill_keys(array_map('intval', array_keys($normSel['documents'] ?? [])), true),
                    'links' => array_fill_keys(array_map('intval', array_keys($normSel['links'] ?? [])), true),
                    'forums' => array_fill_keys(array_map('intval', array_keys($normSel['forums'] ?? [])), true),
                ];

                $fullSnapshot = $courseFull ?: $course;
                $expandedAll = $this->expandCc13SelectionFromCategories($fullSnapshot, $normSel);

                foreach (['documents', 'links', 'forums'] as $k) {
                    if (empty($expandedAll[$k])) {
                        continue;
                    }
                    foreach (array_keys($expandedAll[$k]) as $idStr) {
                        $safeSelected[$k][(int) $idStr] = true;
                    }
                }

                $this->filterCourseResources($course, $safeSelected);

                if (empty($course->resources) || !\is_array($course->resources)) {
                    return $this->jsonError('Nothing to export after filtering your selection.', 400);
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
                return $this->jsonError('Nothing to export (no compatible resources found).', 400);
            }

            return $this->jsonError('CC 1.3 export failed: '.$e->getMessage(), 500);
        }
    }

    #[Route('/cc13/export/download', name: 'cc13_export_download', methods: ['GET'])]
    public function cc13ExportDownload(int $node, Request $req): BinaryFileResponse|JsonResponse
    {
        $file = basename((string) $req->query->get('file', ''));

        // Example pattern: ABC123_cc13_20251017_195455.imscc
        if ('' === $file || !preg_match('/^[A-Za-z0-9_-]+_cc13_\d{8}_\d{6}\.imscc$/', $file)) {
            return $this->jsonError('Invalid file', 400);
        }

        $abs = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file;
        if (!is_file($abs)) {
            return $this->jsonError('File not found', 404);
        }

        $resp = new BinaryFileResponse($abs);
        $resp->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file);
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
                return $this->jsonError('Missing or invalid upload.', 400);
            }

            $ext = strtolower(pathinfo((string) ($file->getClientOriginalName() ?? ''), PATHINFO_EXTENSION));
            if (!\in_array($ext, self::CC13_ALLOWED_EXTENSIONS, true)) {
                return $this->jsonError('Unsupported file type. Please upload .imscc or .zip', 415);
            }

            $tmpZip = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR
                .'cc13_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext;

            $file->move(\dirname($tmpZip), basename($tmpZip));

            $extractDir = Imscc13Import::unzip($tmpZip);

            $format = Imscc13Import::detectFormat($extractDir);
            if (Imscc13Import::FORMAT_IMSCC13 !== $format) {
                Imscc13Import::rrmdir($extractDir);
                @unlink($tmpZip);

                return $this->jsonError('This package is not a Common Cartridge 1.3.', 400);
            }

            $importer = new Imscc13Import();
            $importer->execute($extractDir);

            Imscc13Import::rrmdir($extractDir);
            @unlink($tmpZip);

            return $this->json([
                'ok' => true,
                'message' => 'CC 1.3 import completed successfully.',
            ]);
        } catch (Throwable $e) {
            return $this->jsonError('CC 1.3 import failed: '.$e->getMessage(), 500);
        }
    }

    #[Route(
        '/import/{backupId}/diagnose',
        name: 'import_diagnose',
        requirements: ['backupId' => '.+'],
        methods: ['GET']
    )]
    public function importDiagnose(int $node, string $backupId, Request $req): JsonResponse
    {
        $this->setDebugFromRequest($req);
        $this->logDebug('[importDiagnose] begin', ['node' => $node, 'backupId' => $backupId]);

        try {
            $path = $this->resolveBackupPath($backupId);
            if (!is_file($path)) {
                return $this->json([
                    'error' => 'Backup file not found',
                    'path' => $path,
                ], 404);
            }

            $ci = $this->readCourseInfoFromZip($path);
            if (empty($ci['ok'])) {
                $this->logDebug('[importDiagnose] course_info.dat not found or unreadable', $ci);

                return $this->json([
                    'meta' => [
                        'backupId' => $backupId,
                        'path' => $path,
                    ],
                    'zip' => [
                        'error' => $ci['error'] ?? 'unknown error',
                        'zip_list_sample' => $ci['zip_list_sample'] ?? [],
                        'num_files' => $ci['num_files'] ?? null,
                    ],
                ], 200);
            }

            $raw = (string) $ci['data'];
            $size = (int) ($ci['size'] ?? \strlen($raw));
            $md5 = md5($raw);

            $probe = $this->decodeCourseInfo($raw);

            $scan = [
                'has_graph' => false,
                'resources_keys' => [],
                'note' => 'No graph parsed',
            ];

            if (!empty($probe['is_serialized']) && isset($probe['value']) && \is_object($probe['value'])) {
                $course = $probe['value'];
                $scan['has_graph'] = true;
                $scan['resources_keys'] = (isset($course->resources) && \is_array($course->resources))
                    ? array_keys($course->resources)
                    : [];
                $scan['note'] = 'Parsed PHP serialized graph';
            } elseif (!empty($probe['is_json']) && \is_array($probe['json_preview'])) {
                $jp = $probe['json_preview'];
                $scan['has_graph'] = true;
                $scan['resources_keys'] = (isset($jp['resources']) && \is_array($jp['resources']))
                    ? array_keys($jp['resources'])
                    : [];
                $scan['note'] = 'Parsed JSON document';
            }

            $probeOut = $probe;
            unset($probeOut['value'], $probeOut['decoded']);

            $out = [
                'meta' => [
                    'backupId' => $backupId,
                    'path' => $path,
                    'node' => $node,
                ],
                'zip' => [
                    'name' => $ci['name'] ?? null,
                    'index' => $ci['index'] ?? null,
                ],
                'course_info_dat' => [
                    'size_bytes' => $size,
                    'md5' => $md5,
                ],
                'probe' => $probeOut,
                'scan' => $scan,
            ];

            $this->logDebug('[importDiagnose] done', [
                'encoding' => $probeOut['encoding'] ?? null,
                'has_graph' => $scan['has_graph'],
                'resources_keys' => $scan['resources_keys'],
            ]);

            return $this->json($out);
        } catch (Throwable $e) {
            $this->logDebug('[importDiagnose] exception', ['message' => $e->getMessage()]);

            return $this->jsonError('Diagnosis failed: '.$e->getMessage(), 500);
        }
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

    private function jsonError(string $message, int $status = 400, array $extra = []): JsonResponse
    {
        $payload = ['error' => $message] + $extra;

        return $this->json($payload, $status);
    }

    /**
     * @return array<string,mixed>
     */
    private function getJsonPayload(Request $req): array
    {
        $raw = (string) ($req->getContent() ?? '');
        $data = json_decode('' !== $raw ? $raw : '{}', true);

        return \is_array($data) ? $data : [];
    }

    private function detectUploadExtension(?string $guessed, ?string $originalName): string
    {
        $ext = strtolower((string) ($guessed ?? ''));
        if ('' !== $ext && 'bin' !== $ext) {
            return $ext;
        }

        return strtolower(pathinfo((string) ($originalName ?? ''), PATHINFO_EXTENSION));
    }

    private function validateBackupFilename(string $filename): string
    {
        $name = basename(trim($filename));
        if ('' === $name) {
            return '';
        }
        if (preg_match('/[\/\\\]/', $name)) {
            return '';
        }

        return $name;
    }

    /**
     * Ensure selection map exists; if only types were selected, build the map from types.
     *
     * @param array<string,array> $selectedResources
     * @param string[]            $selectedTypes
     *
     * @return array<string,array>
     */
    private function ensureSelectionMap(object $course, array $selectedResources, array $selectedTypes): array
    {
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
            throw new RuntimeException('No resources selected');
        }

        return $selectedResources;
    }

    /**
     * Resolve absolute path of a backupId inside the backups directory, with safety checks.
     */
    private function resolveBackupPath(string $backupId): string
    {
        $base = rtrim((string) CourseArchiver::getBackupDir(), DIRECTORY_SEPARATOR);
        $baseReal = realpath($base) ?: $base;

        $file = basename($backupId);
        $path = $baseReal.DIRECTORY_SEPARATOR.$file;

        $real = realpath($path);
        if (false !== $real && 0 === strncmp($real, $baseReal, \strlen($baseReal))) {
            return $real;
        }

        return $path;
    }

    /**
     * Load a legacy Course object from any backup:
     * - Chamilo: course_info.dat (strict -> fallback)
     * - Moodle: moodle_backup.xml or extensions (.mbz/.tgz/.gz) -> MoodleImport
     */
    private function loadLegacyCourseForAnyBackup(string $backupId, string $force = 'auto'): object
    {
        $path = $this->resolveBackupPath($backupId);

        $force = strtolower($force);
        if ('dat' === $force || 'chamilo' === $force) {
            $looksMoodle = false;
            $preferChamilo = true;
        } elseif ('moodle' === $force) {
            $looksMoodle = true;
            $preferChamilo = false;
        } else {
            $looksMoodle = $this->isMoodleByExt($path) || $this->zipHasMoodleBackupXml($path);
            $preferChamilo = $this->zipHasCourseInfoDat($path);
        }

        if ($preferChamilo || !$looksMoodle) {
            CourseArchiver::setDebug($this->debug);

            try {
                $course = CourseArchiver::readCourse($backupId, false);
                if (\is_object($course)) {
                    if (!isset($course->resources) || !\is_array($course->resources)) {
                        $course->resources = [];
                    }
                    $course->resources['__meta'] = (array) ($course->resources['__meta'] ?? []);
                    $course->resources['__meta']['import_source'] = 'chamilo';

                    return $course;
                }
            } catch (Throwable $e) {
                $this->logDebug('[loadLegacyCourseForAnyBackup] readCourse() failed', ['error' => $e->getMessage()]);
            }

            $ci = $this->readCourseInfoFromZip($path);
            if (empty($ci['ok'])) {
                if ($looksMoodle) {
                    $this->logDebug('[loadLegacyCourseForAnyBackup] no course_info.dat, trying MoodleImport as last resort');

                    return $this->loadMoodleCourseOrFail($path);
                }

                throw new RuntimeException('course_info.dat not found in backup');
            }

            $raw = (string) $ci['data'];
            $payload = base64_decode($raw, true);
            if (false === $payload) {
                $payload = $raw;
            }

            $payload = CourseArchiver::preprocessSerializedPayloadForTypedProps($payload);
            CourseArchiver::ensureLegacyAliases();

            set_error_handler(static function (): void {});

            try {
                if (class_exists(UnserializeApi::class)) {
                    $c = UnserializeApi::unserialize('course', $payload);
                } else {
                    $c = @unserialize($payload, ['allowed_classes' => true]);
                }
            } finally {
                restore_error_handler();
            }

            if (!\is_object($c ?? null)) {
                if ($looksMoodle) {
                    $this->logDebug('[loadLegacyCourseForAnyBackup] Chamilo fallback failed, trying MoodleImport');

                    return $this->loadMoodleCourseOrFail($path);
                }

                throw new RuntimeException('Could not unserialize course (fallback)');
            }

            if (!isset($c->resources) || !\is_array($c->resources)) {
                $c->resources = [];
            }
            $c->resources['__meta'] = (array) ($c->resources['__meta'] ?? []);
            $c->resources['__meta']['import_source'] = 'chamilo';

            return $c;
        }

        if ($looksMoodle) {
            $this->logDebug('[loadLegacyCourseForAnyBackup] using MoodleImport');

            return $this->loadMoodleCourseOrFail($path);
        }

        throw new RuntimeException('Unsupported package: neither course_info.dat nor moodle_backup.xml found.');
    }

    /**
     * Normalize resource buckets to the exact keys supported by CourseRestorer.
     * - Never drop data: merge buckets; keep __meta as-is.
     */
    private function normalizeBucketsForRestorer(object $course): void
    {
        if (!isset($course->resources) || !\is_array($course->resources)) {
            return;
        }

        $all = $course->resources;

        $meta = [];
        foreach ($all as $k => $v) {
            if (\is_string($k) && str_starts_with($k, '__')) {
                $meta[$k] = $v;
                unset($all[$k]);
            }
        }

        $out = $all;

        $merge = static function (array $dst, array $src): array {
            foreach ($src as $id => $obj) {
                if (!\array_key_exists($id, $dst)) {
                    $dst[$id] = $obj;
                }
            }

            return $dst;
        };

        $aliases = [
            'documents' => 'document',
            'document ' => 'document',
            'Document' => 'document',

            'tool introduction' => 'tool_intro',
            'tool_introduction' => 'tool_intro',
            'tool/introduction' => 'tool_intro',
            'tool intro' => 'tool_intro',
            'Tool introduction' => 'tool_intro',

            'forums' => 'forum',
            'Forum' => 'forum',
            'Forum_Category' => 'forum_category',
            'forumcategory' => 'forum_category',
            'thread' => 'forum_topic',
            'Thread' => 'forum_topic',
            'forumtopic' => 'forum_topic',
            'post' => 'forum_post',
            'Post' => 'forum_post',
            'forumpost' => 'forum_post',

            'links' => 'link',
            'link category' => 'link_category',

            'Exercise_Question' => 'exercise_question',
            'exercisequestion' => 'exercise_question',

            'surveys' => 'survey',
            'surveyquestion' => 'survey_question',

            'announcements' => 'announcement',
            'Announcements' => 'announcement',
        ];

        foreach ($all as $rawKey => $bucket) {
            if (!\is_array($bucket)) {
                continue;
            }

            $k = (string) $rawKey;
            $norm = strtolower(trim(strtr($k, ['\\' => '/', '-' => '_'])));
            $norm2 = str_replace('/', '_', $norm);

            $canonical = $aliases[$norm] ?? $aliases[$norm2] ?? null;
            if ($canonical && $canonical !== $rawKey) {
                $out[$canonical] = (isset($out[$canonical]) && \is_array($out[$canonical]))
                    ? $merge($out[$canonical], $bucket)
                    : $bucket;

                unset($out[$rawKey]);
            }
        }

        if (!isset($out['document'])) {
            if (isset($all['documents']) && \is_array($all['documents'])) {
                $out['document'] = $all['documents'];
            } elseif (isset($all['Document']) && \is_array($all['Document'])) {
                $out['document'] = $all['Document'];
            }
        }

        $order = [
            'announcement', 'document', 'link', 'link_category',
            'forum', 'forum_category', 'forum_topic', 'forum_post',
            'quiz', 'exercise_question',
            'survey', 'survey_question',
            'learnpath', 'tool_intro',
            'work',
        ];

        $weights = [];
        foreach ($order as $i => $key) {
            $weights[$key] = $i;
        }

        uksort($out, static function ($a, $b) use ($weights) {
            $wa = $weights[$a] ?? 9999;
            $wb = $weights[$b] ?? 9999;

            return $wa <=> $wb ?: strcasecmp((string) $a, (string) $b);
        });

        $course->resources = $meta + $out;

        $this->logDebug('[normalizeBucketsForRestorer] final keys', array_keys((array) $course->resources));
    }

    private function getImportSource(object $course): string
    {
        $src = strtolower((string) ($course->resources['__meta']['import_source'] ?? ''));
        if ('' !== $src) {
            return $src;
        }

        return strtolower((string) ($course->info['__import_source'] ?? ''));
    }

    private function computeWantedMoodleGroups(object $course): array
    {
        $presentBuckets = array_map('strtolower', array_keys((array) ($course->resources ?? [])));
        $present = static fn (string $k): bool => \in_array(strtolower($k), $presentBuckets, true);

        $wanted = [];
        $mark = static function (array &$dst, bool $cond, string $key): void {
            if ($cond) {
                $dst[$key] = true;
            }
        };

        // Tolerant (plural + legacy keys)
        $mark($wanted, $present('link') || $present('link_category'), 'links');
        $mark($wanted, $present('forum') || $present('forum_category'), 'forums');
        $mark($wanted, $present('document') || $present('documents'), 'documents');
        $mark($wanted, $present('quiz') || $present('exercise'), 'quizzes');
        $mark($wanted, $present('scorm'), 'scorm');

        return $wanted;
    }

    private function isMoodleByExt(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return \in_array($ext, ['mbz', 'tgz', 'gz'], true);
    }

    private function zipHasMoodleBackupXml(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!\in_array($ext, ['zip', 'mbz'], true)) {
            return false;
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($path)) {
            return false;
        }

        $idx = $zip->locateName('moodle_backup.xml', ZipArchive::FL_NOCASE);
        $zip->close();

        return false !== $idx;
    }

    private function zipHasCourseInfoDat(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!\in_array($ext, ['zip', 'mbz'], true)) {
            return false;
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($path)) {
            return false;
        }

        foreach (['course_info.dat', 'course/course_info.dat', 'backup/course_info.dat'] as $cand) {
            $idx = $zip->locateName($cand, ZipArchive::FL_NOCASE);
            if (false !== $idx) {
                $zip->close();

                return true;
            }
        }

        $zip->close();

        return false;
    }

    private function loadMoodleCourseOrFail(string $absPath): object
    {
        if (!class_exists(MoodleImport::class)) {
            throw new RuntimeException('MoodleImport class not available');
        }

        $importer = new MoodleImport(debug: $this->debug);

        if (!method_exists($importer, 'buildLegacyCourseFromMoodleArchive')) {
            throw new RuntimeException('MoodleImport::buildLegacyCourseFromMoodleArchive() not available');
        }

        $course = $importer->buildLegacyCourseFromMoodleArchive($absPath);

        if (!\is_object($course) || empty($course->resources) || !\is_array($course->resources)) {
            throw new RuntimeException('Moodle backup contains no importable resources');
        }

        $course->resources['__meta'] = (array) ($course->resources['__meta'] ?? []);
        $course->resources['__meta']['import_source'] = 'moodle';

        return $course;
    }

    private static function iniSizeToBytes(string $val): int
    {
        $val = trim($val);
        if ('' === $val) {
            return 0;
        }
        if ('0' === $val) {
            return 0;
        }

        if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([kmgt])?b?$/i', $val, $m)) {
            return (int) $val;
        }

        $num = (float) $m[1];
        $unit = strtolower((string) ($m[2] ?? ''));

        switch ($unit) {
            case 't':
                $num *= 1024;

                // no break
            case 'g':
                $num *= 1024;

                // no break
            case 'm':
                $num *= 1024;

                // no break
            case 'k':
                $num *= 1024;

                break;
        }

        return (int) round($num);
    }
}
