<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use AllowDynamicProperties;
use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradeModel;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Tool\User;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\LearnPathCategory;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CAttendanceCalendarRelGroup;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEventAttachment;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiConf;
use Chamilo\CourseBundle\Repository\CAnnouncementAttachmentRepository;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use CourseManager;
use Database;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use DocumentManager;
use FilesystemIterator;
use learnpath;
use PhpZip\ZipFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use stdClass;
use SurveyManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

use const ENT_QUOTES;
use const FILEINFO_MIME_TYPE;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

/**
 * Class CourseRestorer.
 *
 * Class to restore items from a course object to a Chamilo-course
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @author Julio Montoya <gugli100@gmail.com> Several fixes/improvements
 */
#[AllowDynamicProperties]
class CourseRestorer
{
    /**
     * Debug flag (default: true). Toggle with setDebug().
     */
    private bool $debug = true;

    /**
     * The course-object.
     */
    public $course;
    public $destination_course_info;

    /**
     * What to do with files with same name (FILE_SKIP, FILE_RENAME, FILE_OVERWRITE).
     */
    public $file_option;
    public $set_tools_invisible_by_default;
    public $skip_content;

    /**
     * Restore order (keep existing order; docs first).
     */
    public $tools_to_restore = [
        'documents',
        'announcements',
        'attendance',
        'course_descriptions',
        'events',
        'forum_category',
        'forums',
        'glossary',
        'quizzes',
        'test_category',
        'links',
        'works',
        'surveys',
        'learnpath_category',
        'learnpaths',
        'scorm_documents',
        'tool_intro',
        'thematic',
        'wiki',
        'gradebook',
        'assets',
    ];

    /**
     * Setting per tool.
     */
    public $tool_copy_settings = [];

    /**
     * If true adds the text "copy" in the title of an item (only for LPs right now).
     */
    public $add_text_in_items = false;

    public $destination_course_id;
    public bool $copySessionContent = false;

    /**
     * Optional course origin id (legacy).
     */
    private $course_origin_id;

    /**
     * First teacher (owner) used for forums/posts.
     */
    private $first_teacher_id = 0;

    private array $htmlFoldersByCourseDir = [];

    /**
     * @var array<string,array>
     */
    private array $resources_all_snapshot = [];

    /**
     * @param Course $course
     */
    public function __construct($course)
    {
        $this->course = $course ?: (object)[];

        $code = (string) ($this->course->code ?? '');
        if ($code === '') {
            $code = api_get_course_id();
            $this->course->code = $code;
        }

        $courseInfo = $code !== '' ? api_get_course_info($code) : api_get_course_info();
        $this->course_origin_id = !empty($courseInfo) ? $courseInfo['real_id'] : null;

        $this->file_option = FILE_RENAME;
        $this->set_tools_invisible_by_default = false;
        $this->skip_content = [];

        $this->dlog('Ctor: initial course info', [
            'course_code' => $this->course->code ?? null,
            'origin_id' => $this->course_origin_id,
            'has_resources' => \is_array($this->course->resources ?? null),
            'resource_keys' => array_keys((array) ($this->course->resources ?? [])),
        ]);
    }

    /**
     * Set the file-option.
     *
     * @param int $option FILE_SKIP, FILE_RENAME or FILE_OVERWRITE
     */
    public function set_file_option($option = FILE_OVERWRITE): void
    {
        $this->file_option = $option;
        $this->dlog('File option set', ['file_option' => $this->file_option]);
    }

    /**
     * @param bool $status
     */
    public function set_add_text_in_items($status): void
    {
        $this->add_text_in_items = $status;
    }

    /**
     * @param array $array
     */
    public function set_tool_copy_settings($array): void
    {
        $this->tool_copy_settings = $array;
    }

    /**
     * Entry point.
     *
     * @param mixed $destination_course_code
     * @param mixed $session_id
     * @param mixed $update_course_settings
     * @param mixed $respect_base_content
     */
    public function restore(
        $destination_course_code = '',
        $session_id = 0,
        $update_course_settings = false,
        $respect_base_content = false
    ) {
        $this->dlog('Restore() called', [
            'destination_code' => $destination_course_code,
            'session_id' => (int) $session_id,
            'update_course_settings' => (bool) $update_course_settings,
            'respect_base_content' => (bool) $respect_base_content,
        ]);

        // Resolve destination course
        $course_info = '' === $destination_course_code
            ? api_get_course_info()
            : api_get_course_info($destination_course_code);

        if (empty($course_info) || empty($course_info['real_id'])) {
            $this->dlog('Destination course not resolved or missing real_id', ['course_info' => $course_info]);

            return false;
        }

        $this->destination_course_info = $course_info;
        $this->destination_course_id = (int) $course_info['real_id'];
        $this->destination_course_entity = api_get_course_entity($this->destination_course_id);

        // Resolve teacher for forum/thread/post ownership
        $this->first_teacher_id = api_get_user_id();
        $teacher_list = CourseManager::get_teacher_list_from_course_code($course_info['code']);
        if (!empty($teacher_list)) {
            foreach ($teacher_list as $t) {
                $this->first_teacher_id = (int) $t['user_id'];

                break;
            }
        }

        if (empty($this->course)) {
            $this->dlog('No source course found');

            return false;
        }

        // Encoding detection/normalization
        if (empty($this->course->encoding)) {
            $sample_text = $this->course->get_sample_text()."\n";
            $lines = explode("\n", $sample_text);
            foreach ($lines as $k => $line) {
                if (api_is_valid_ascii($line)) {
                    unset($lines[$k]);
                }
            }
            $sample_text = implode("\n", $lines);
            $this->course->encoding = api_detect_encoding($sample_text, $course_info['language']);
        }
        $this->course->to_system_encoding();
        $this->dlog('Encoding resolved', ['encoding' => $this->course->encoding ?? '']);

        // Normalize forum bags
        $this->normalizeForumKeys();
        $this->ensureDepsBagsFromSnapshot();
        // Dump a compact view of the resource bags before restoring
        $this->debug_course_resources_simple(null);

        // Restore tools
        foreach ($this->tools_to_restore as $tool) {
            $fn = 'restore_'.$tool;
            if (method_exists($this, $fn)) {
                $this->dlog('Starting tool restore', ['tool' => $tool]);

                try {
                    $this->{$fn}($session_id, $respect_base_content, $destination_course_code);
                } catch (Throwable $e) {
                    $this->dlog('Tool restore failed with exception', [
                        'tool' => $tool,
                        'error' => $e->getMessage(),
                    ]);
                    $this->resetDoctrineIfClosed();
                }
                $this->dlog('Finished tool restore', ['tool' => $tool]);
            } else {
                $this->dlog('Restore method not found for tool (skipping)', ['tool' => $tool]);
            }
        }

        // Optionally restore safe course settings
        if ($update_course_settings) {
            $this->dlog('Restoring course settings');
            $this->restore_course_settings($destination_course_code);
        }

        $this->dlog('Restore() finished', [
            'destination_course_id' => $this->destination_course_id,
        ]);

        return null;
    }

    /**
     * Restore only harmless course settings (Chamilo 2 entity-safe).
     */
    public function restore_course_settings(string $destination_course_code = ''): void
    {
        $this->dlog('restore_course_settings() called');

        $courseEntity = null;

        if ('' !== $destination_course_code) {
            $courseEntity = Container::getCourseRepository()->findOneByCode($destination_course_code);
        } else {
            if (!empty($this->destination_course_id)) {
                $courseEntity = api_get_course_entity((int) $this->destination_course_id);
            } else {
                $info = api_get_course_info();
                if (!empty($info['real_id'])) {
                    $courseEntity = api_get_course_entity((int) $info['real_id']);
                }
            }
        }

        if (!$courseEntity) {
            $this->dlog('No destination course entity found, skipping settings restore');

            return;
        }

        $src = $this->course->info ?? [];

        if (!empty($src['language'])) {
            $courseEntity->setCourseLanguage((string) $src['language']);
        }
        if (isset($src['visibility']) && '' !== $src['visibility']) {
            $courseEntity->setVisibility((int) $src['visibility']);
        }
        if (\array_key_exists('department_name', $src)) {
            $courseEntity->setDepartmentName((string) $src['department_name']);
        }
        if (\array_key_exists('department_url', $src)) {
            $courseEntity->setDepartmentUrl((string) $src['department_url']);
        }
        if (!empty($src['category_id'])) {
            $catRepo = Container::getCourseCategoryRepository();
            $cat = $catRepo?->find((int) $src['category_id']);
            if ($cat) {
                $courseEntity->setCategories(new ArrayCollection([$cat]));
            }
        }
        if (\array_key_exists('subscribe_allowed', $src)) {
            $courseEntity->setSubscribe((bool) $src['subscribe_allowed']);
        }
        if (\array_key_exists('unsubscribe', $src)) {
            $courseEntity->setUnsubscribe((bool) $src['unsubscribe']);
        }

        $em = Database::getManager();
        $em->persist($courseEntity);
        $em->flush();

        $this->dlog('Course settings restored');
    }

    /**
     * Restore documents.
     *
     * @param mixed $session_id
     * @param mixed $respect_base_content
     * @param mixed $destination_course_code
     */
    public function restore_documents($session_id = 0, $respect_base_content = false, $destination_course_code = ''): void
    {
        if (!$this->course->has_resources(RESOURCE_DOCUMENT)) {
            $this->dlog('restore_documents: no document resources');

            return;
        }

        $courseInfo   = $this->destination_course_info;
        $docRepo      = Container::getDocumentRepository();
        $courseEntity = api_get_course_entity($this->destination_course_id);
        $session      = api_get_session_entity((int) $session_id);
        $group        = api_get_group_entity(0);

        // Resolve the import root deterministically:
        $resolveImportRoot = function (): string {
            // explicit meta archiver_root
            $metaRoot = (string) ($this->course->resources['__meta']['archiver_root'] ?? '');
            if ($metaRoot !== '' && is_dir($metaRoot) && (is_file($metaRoot.'/course_info.dat') || is_dir($metaRoot.'/document'))) {
                $this->dlog('resolveImportRoot: using meta.archiver_root', ['dir' => $metaRoot]);

                return rtrim($metaRoot, '/');
            }

            // backup_path may be a dir or a zip
            $bp = (string) ($this->course->backup_path ?? '');
            if ($bp !== '') {
                if (is_dir($bp) && (is_file($bp.'/course_info.dat') || is_dir($bp.'/document'))) {
                    $this->dlog('resolveImportRoot: using backup_path (dir)', ['dir' => $bp]);

                    return rtrim($bp, '/');
                }

                // if backup_path is a .zip, try to find its extracted sibling under the same folder
                if (is_file($bp) && preg_match('/\.zip$/i', $bp)) {
                    $base = dirname($bp);
                    $cands = glob($base.'/CourseArchiver_*', GLOB_ONLYDIR) ?: [];
                    if (empty($cands) && is_dir($base)) {
                        // fallback in envs where glob is restricted
                        $tmp = array_diff(scandir($base) ?: [], ['.', '..']);
                        foreach ($tmp as $name) {
                            if (strpos($name, 'CourseArchiver_') === 0 && is_dir($base.'/'.$name)) {
                                $cands[] = $base.'/'.$name;
                            }
                        }
                    }
                    usort($cands, static function ($a, $b) {
                        return (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0);
                    });
                    foreach ($cands as $dir) {
                        if (is_file($dir.'/course_info.dat') || is_dir($dir.'/document')) {
                            $this->dlog('resolveImportRoot: using sibling CourseArchiver', ['dir' => $dir]);
                            // cache for later
                            $this->course->resources['__meta']['archiver_root'] = rtrim($dir, '/');

                            return rtrim($dir, '/');
                        }
                    }
                    $this->dlog('resolveImportRoot: no sibling CourseArchiver found next to zip', ['base' => $base]);
                }
            }

            $scanBase = $this->getCourseBackupsBase();
            if (is_dir($scanBase)) {
                $cands = glob($scanBase.'/CourseArchiver_*', GLOB_ONLYDIR) ?: [];
                if (empty($cands)) {
                    $tmp = array_diff(scandir($scanBase) ?: [], ['.', '..']);
                    foreach ($tmp as $name) {
                        if (strpos($name, 'CourseArchiver_') === 0 && is_dir($scanBase.'/'.$name)) {
                            $cands[] = $scanBase.'/'.$name;
                        }
                    }
                }
                usort($cands, static function ($a, $b) {
                    return (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0);
                });
                foreach ($cands as $dir) {
                    if (is_file($dir.'/course_info.dat') || is_dir($dir.'/document')) {
                        $this->dlog('resolveImportRoot: using scanned CourseArchiver', ['dir' => $dir, 'scanBase' => $scanBase]);
                        $this->course->resources['__meta']['archiver_root'] = rtrim($dir, '/');

                        return rtrim($dir, '/');
                    }
                }
            }

            $this->dlog('resolveImportRoot: no valid import root found, falling back to copy mode');

            return '';
        };

        $backupRoot = $resolveImportRoot();
        $copyMode   = $backupRoot === '';
        $srcRoot    = $copyMode ? null : ($backupRoot.'/');

        $this->dlog('restore_documents: begin', [
            'files'   => \count($this->course->resources[RESOURCE_DOCUMENT] ?? []),
            'session' => (int) $session_id,
            'mode'    => $copyMode ? 'copy' : 'import',
            'srcRoot' => $srcRoot,
        ]);

        $DBG = function (string $msg, array $ctx = []): void {
            // Keep these concise to avoid noisy logs in production
            error_log('[RESTORE:HTMLURL] '.$msg.(empty($ctx) ? '' : ' '.json_encode($ctx)));
        };

        // Helper: returns the logical path from source CDocument (starts with "/")
        $getLogicalPathFromSource = function ($sourceId) use ($docRepo): string {
            $doc = $docRepo->find((int) $sourceId);
            if ($doc && method_exists($doc, 'getPath')) {
                $p = (string) $doc->getPath();
                return $p !== '' && $p[0] === '/' ? $p : '/'.$p;
            }
            return '';
        };

        // Reserved top-level containers that must not leak into destination when copying
        $reservedTopFolders = ['certificates', 'learnpaths'];

        // Normalize any incoming "rel" to avoid internal reserved prefixes leaking into the destination tree.
        $normalizeRel = function (string $rel) use ($copyMode): string {
            // Always ensure a single leading slash
            $rel = '/'.ltrim($rel, '/');

            // Collapse any repeated /document/ prefixes (e.g., /document/document/…)
            while (preg_match('#^/document/#i', $rel)) {
                $rel = preg_replace('#^/document/#i', '/', $rel, 1);
            }

            // Flatten "/certificates/{portal}/{course}/..." → "/..."
            if (preg_match('#^/certificates/[^/]+/[^/]+(?:/(.*))?$#i', $rel, $m)) {
                $rest = $m[1] ?? '';
                return $rest === '' ? '/' : '/'.ltrim($rest, '/');
            }
            // Fallback: strip generic "certificates/" container if it still shows up
            if (preg_match('#^/certificates/(.*)$#i', $rel, $m)) {
                return '/'.ltrim($m[1], '/');
            }

            // Flatten "/{host}/{course}/..." → "/..." only in COPY mode
            if ($copyMode && preg_match('#^/([^/]+)/([^/]+)(?:/(.*))?$#', $rel, $m)) {
                $host   = $m[1];
                $course = $m[2];
                $rest   = $m[3] ?? '';

                $hostLooksLikeHostname = ($host === 'localhost') || str_contains($host, '.');
                $courseLooksLikeCode   = (bool) preg_match('/^[A-Za-z0-9_\-]{3,}$/', $course);

                if ($hostLooksLikeHostname && $courseLooksLikeCode) {
                    return $rest === '' ? '/' : '/'.ltrim($rest, '/');
                }
            }

            // Optionally flatten learnpath containers only in COPY mode
            if ($copyMode && preg_match('#^/(?:learnpaths?|lp)/[^/]+/(.*)$#i', $rel, $m)) {
                return '/'.ltrim($m[1], '/');
            }
            if ($copyMode && preg_match('#^/(?:learnpaths?|lp)/(.*)$#i', $rel, $m)) {
                return '/'.ltrim($m[1], '/');
            }

            // Nothing to normalize
            return $rel;
        };

        // Ensure a folder chain exists under Documents (skipping "document" as root)
        $ensureFolder = function (string $relPath) use ($docRepo, $courseEntity, $courseInfo, $session_id, $DBG) {
            $rel = '/'.ltrim($relPath, '/');
            if ('/' === $rel || '' === $rel) {
                return 0;
            }

            $parts = array_values(array_filter(explode('/', trim($rel, '/'))));
            // Skip "document" root if present
            $start = 0;
            if (isset($parts[0]) && 'document' === $parts[0]) {
                $start = 1;
            }

            $accum    = '';
            $parentId = 0;
            for ($i = $start; $i < \count($parts); $i++) {
                $seg   = $parts[$i];
                $accum = $accum.'/'.$seg;

                $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;
                $title     = $seg;

                $existing = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parentRes->getResourceNode(),
                    $courseEntity,
                    api_get_session_entity((int) $session_id),
                    api_get_group_entity(0)
                );

                if ($existing) {
                    $parentId = method_exists($existing, 'getIid') ? $existing->getIid() : 0;
                    continue;
                }

                $entity   = DocumentManager::addDocument(
                    ['real_id' => $courseInfo['real_id'], 'code' => $courseInfo['code']],
                    $accum,
                    'folder',
                    0,
                    $title,
                    null,
                    0,
                    null,
                    0,
                    (int) $session_id,
                    0,
                    false,
                    '',
                    $parentId,
                    ''
                );
                $parentId = method_exists($entity, 'getIid') ? $entity->getIid() : 0;

                $DBG('ensureFolder:create', ['accum' => $accum, 'iid' => $parentId]);
            }

            return $parentId;
        };

        // Robust HTML detection (extension sniff + small content probe + mimetype)
        $isHtmlFile = function (string $filePath, string $nameGuess): bool {
            $ext1 = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $ext2 = strtolower(pathinfo($nameGuess, PATHINFO_EXTENSION));
            if (\in_array($ext1, ['html', 'htm'], true) || \in_array($ext2, ['html', 'htm'], true)) {
                return true;
            }
            $peek = (string) @file_get_contents($filePath, false, null, 0, 2048);
            if ($peek === '') {
                return false;
            }
            $s = strtolower($peek);
            if (str_contains($s, '<html') || str_contains($s, '<!doctype html')) {
                return true;
            }
            if (\function_exists('finfo_open')) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                if ($fi) {
                    $mt = @finfo_buffer($fi, $peek) ?: '';
                    finfo_close($fi);
                    if (str_starts_with($mt, 'text/html')) {
                        return true;
                    }
                }
            }

            return false;
        };

        // Create folders found in the backup (keep behavior but skip "document" root)
        $folders = [];
        foreach ($this->course->resources[RESOURCE_DOCUMENT] as $k => $item) {
            if (FOLDER !== $item->file_type) {
                continue;
            }

            // Build destination folder path:
            // - In copy mode prefer the logical path from the source document (stable),
            //   otherwise strip leading "document/" from archive path.
            if ($copyMode && !empty($item->source_id)) {
                $rel = $getLogicalPathFromSource($item->source_id);
                if ($rel === '') {
                    $rel = '/'.ltrim(substr($item->path, 8), '/');
                }
            } else {
                // Strip leading "document/"
                $rel = '/'.ltrim(substr($item->path, 8), '/');
            }

            $origRelX = $rel;
            $rel = $normalizeRel($rel);
            if ($rel !== $origRelX) {
                $DBG('normalizeRel:folder', ['from' => $origRelX, 'to' => $rel]);
            }

            if ($rel === '/') {
                continue;
            }

            // Avoid creating internal system folders at root in copy mode
            $firstSeg = explode('/', trim($rel, '/'))[0] ?? '';
            if ($copyMode && in_array($firstSeg, $reservedTopFolders, true)) {
                continue;
            }

            $parts    = array_values(array_filter(explode('/', $rel)));
            $accum    = '';
            $parentId = 0;

            foreach ($parts as $i => $seg) {
                $accum .= '/'.$seg;

                if (isset($folders[$accum])) {
                    $parentId = $folders[$accum];
                    continue;
                }

                $parentResource = $parentId ? $docRepo->find($parentId) : $courseEntity;
                $title          = ($i === \count($parts) - 1) ? ($item->title ?: $seg) : $seg;

                $existing = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parentResource->getResourceNode(),
                    $courseEntity,
                    $session,
                    $group
                );

                if ($existing) {
                    $iid = method_exists($existing, 'getIid') ? $existing->getIid() : 0;
                    $this->dlog('restore_documents: reuse folder', ['title' => $title, 'iid' => $iid]);
                } else {
                    $entity = DocumentManager::addDocument(
                        ['real_id' => $courseInfo['real_id'], 'code' => $courseInfo['code']],
                        $accum,
                        'folder',
                        0,
                        $title,
                        null,
                        0,
                        null,
                        0,
                        (int) $session_id,
                        0,
                        false,
                        '',
                        $parentId,
                        ''
                    );
                    $iid = method_exists($entity, 'getIid') ? $entity->getIid() : 0;
                    $this->dlog('restore_documents: created folder', ['title' => $title, 'iid' => $iid]);
                }

                $folders[$accum] = $iid;
                if ($i === \count($parts) - 1) {
                    $this->course->resources[RESOURCE_DOCUMENT][$k]->destination_id = $iid;
                }
                $parentId = $iid;
            }
        }

        // GLOBAL PRE-SCAN: build URL maps for HTML dependencies (only in import-from-package mode)
        $urlMapByRel  = [];
        $urlMapByBase = [];

        foreach ($this->course->resources[RESOURCE_DOCUMENT] as $k => $item) {
            if (DOCUMENT !== $item->file_type || $copyMode) {
                continue;
            }

            $rawTitle = $item->title ?: basename((string) $item->path);
            $srcPath  = $srcRoot.$item->path;

            // Fallback: if primary root is wrong, try archiver_root
            if ((!is_file($srcPath) || !is_readable($srcPath))) {
                $altRoot = rtrim((string) ($this->course->resources['__meta']['archiver_root'] ?? ''), '/').'/';
                if ($altRoot && $altRoot !== $srcRoot && is_readable($altRoot.$item->path)) {
                    $srcPath = $altRoot.$item->path;
                    $this->dlog('restore_documents: pre-scan fallback to alt root', ['src' => $srcPath]);
                }
            }

            if (!is_file($srcPath) || !is_readable($srcPath)) {
                continue;
            }

            if (!$isHtmlFile($srcPath, $rawTitle)) {
                continue;
            }

            $html = (string) @file_get_contents($srcPath);
            if ($html === '') {
                continue;
            }

            $maps = ChamiloHelper::buildUrlMapForHtmlFromPackage(
                $html,
                ($courseInfo['directory'] ?? $courseInfo['code'] ?? ''),
                $srcRoot,
                $folders,
                $ensureFolder,
                $docRepo,
                $courseEntity,
                $session,
                $group,
                (int) $session_id,
                (int) $this->file_option,
                $DBG
            );

            foreach ($maps['byRel'] as $kRel => $vUrl) {
                if (!isset($urlMapByRel[$kRel])) {
                    $urlMapByRel[$kRel] = $vUrl;
                }
            }
            foreach ($maps['byBase'] as $kBase => $vUrl) {
                if (!isset($urlMapByBase[$kBase])) {
                    $urlMapByBase[$kBase] = $vUrl;
                }
            }
        }
        $DBG('global.map.stats', ['byRel' => \count($urlMapByRel), 'byBase' => \count($urlMapByBase)]);

        // Import files from backup (rewrite HTML BEFORE creating the Document)
        foreach ($this->course->resources[RESOURCE_DOCUMENT] as $k => $item) {
            if (DOCUMENT !== $item->file_type) {
                continue;
            }

            $srcPath  = null;
            $rawTitle = $item->title ?: basename((string) $item->path);

            if ($copyMode) {
                // Copy from existing document (legacy copy flow)
                $srcDoc = null;
                if (!empty($item->source_id)) {
                    $srcDoc = $docRepo->find((int) $item->source_id);
                }
                if (!$srcDoc) {
                    $this->dlog('restore_documents: source CDocument not found by source_id', ['source_id' => $item->source_id ?? null]);
                    continue;
                }
                $srcPath = $this->resourceFileAbsPathFromDocument($srcDoc);
                if (!$srcPath) {
                    $this->dlog('restore_documents: source file not readable from ResourceFile', ['source_id' => (int) $item->source_id]);
                    continue;
                }
            } else {
                // Import from extracted package
                $srcPath = $srcRoot.$item->path;

                // Fallback to archiver_root if primary root is wrong
                if (!is_file($srcPath) || !is_readable($srcPath)) {
                    $altRoot = rtrim((string) ($this->course->resources['__meta']['archiver_root'] ?? ''), '/').'/';
                    if ($altRoot && $altRoot !== $srcRoot && is_readable($altRoot.$item->path)) {
                        $srcPath = $altRoot.$item->path;
                        $this->dlog('restore_documents: fallback to alt root', ['src' => $srcPath]);
                    }
                }

                if (!is_file($srcPath) || !is_readable($srcPath)) {
                    $this->dlog('restore_documents: source file not found/readable', ['src' => $srcPath]);
                    continue;
                }
            }

            $isHtml = $isHtmlFile($srcPath, $rawTitle);

            // Build destination file path:
            // - In copy mode base on the logical source path
            // - Otherwise, strip "document/" from archive path
            if ($copyMode && !empty($item->source_id)) {
                $rel = $getLogicalPathFromSource($item->source_id);
                if ($rel === '') {
                    $rel = '/'.ltrim(substr($item->path, 8), '/'); // fallback
                }
            } else {
                $rel = '/'.ltrim(substr($item->path, 8), '/'); // remove "document" prefix
            }

            $origRelF = $rel;
            $rel = $normalizeRel($rel); // <- critical: flatten internal containers in copy mode
            if ($rel !== $origRelF) {
                $DBG('normalizeRel:file', ['from' => $origRelF, 'to' => $rel]);
            }

            // If it still comes from a reserved top-level folder, flatten to the basename (safety)
            $firstSeg = explode('/', trim($rel, '/'))[0] ?? '';
            if ($copyMode && in_array($firstSeg, $reservedTopFolders, true)) {
                $rel = '/'.basename($rel);
            }

            $parentRel = rtrim(\dirname($rel), '/');

            // Avoid re-copying already mapped non-HTML assets (images, binaries) if we already created them
            if (!empty($item->destination_id) && !$isHtml) {
                $maybeExisting = $docRepo->find((int) $item->destination_id);
                if ($maybeExisting) {
                    $this->dlog('restore_documents: already mapped asset, skipping', [
                        'src' => $item->path ?? null,
                        'dst_iid' => (int) $item->destination_id,
                    ]);
                    continue;
                } else {
                    $item->destination_id = 0;
                }
            }

            $parentId  = $folders[$parentRel] ?? 0;
            if (!$parentId) {
                $parentId            = $ensureFolder($parentRel);
                $folders[$parentRel] = $parentId;
            }
            $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;

            $baseTitle  = $rawTitle;
            $finalTitle = $baseTitle;

            $findExisting = function (string $t) use ($docRepo, $parentRes, $courseEntity, $session, $group) {
                $e = $docRepo->findCourseResourceByTitle($t, $parentRes->getResourceNode(), $courseEntity, $session, $group);
                return $e && method_exists($e, 'getIid') ? $e->getIid() : null;
            };

            $existsIid = $findExisting($finalTitle);
            if ($existsIid) {
                $this->dlog('restore_documents: collision', ['title' => $finalTitle, 'policy' => $this->file_option]);
                if (FILE_SKIP === $this->file_option) {
                    $this->course->resources[RESOURCE_DOCUMENT][$k]->destination_id = $existsIid;
                    continue;
                }
                $pi   = pathinfo($baseTitle);
                $name = $pi['filename'] ?? $baseTitle;
                $ext2 = (isset($pi['extension']) && $pi['extension'] !== '') ? '.'.$pi['extension'] : '';
                $i    = 1;
                while ($findExisting($finalTitle)) {
                    $finalTitle = $name.'_'.$i.$ext2;
                    $i++;
                }
            }

            // Build content or set realPath for binary files
            $content  = '';
            $realPath = '';

            if ($isHtml) {
                $raw = @file_get_contents($srcPath) ?: '';
                if (\defined('UTF8_CONVERT') && UTF8_CONVERT) {
                    $raw = utf8_encode($raw);
                }

                // Rewrite using maps (exact rel + basename fallback) BEFORE addDocument
                $DBG('html:rewrite:before', [
                    'title' => $finalTitle,
                    'byRel' => \count($urlMapByRel),
                    'byBase' => \count($urlMapByBase),
                ]);
                $rew = ChamiloHelper::rewriteLegacyCourseUrlsWithMap(
                    $raw,
                    ($courseInfo['directory'] ?? $courseInfo['code'] ?? ''),
                    $urlMapByRel,
                    $urlMapByBase
                );
                $DBG('html:rewrite:after', ['title' => $finalTitle, 'replaced' => $rew['replaced'], 'misses' => $rew['misses']]);

                $content = $rew['html'];
            } else {
                $realPath = $srcPath;
            }

            try {
                $entity = DocumentManager::addDocument(
                    ['real_id' => $courseInfo['real_id'], 'code' => $courseInfo['code']],
                    $rel,
                    'file',
                    (int) ($item->size ?? 0),
                    $finalTitle,
                    $item->comment ?? '',
                    0,
                    null,
                    0,
                    (int) $session_id,
                    0,
                    false,
                    $content,
                    $parentId,
                    $realPath
                );
                $iid = method_exists($entity, 'getIid') ? $entity->getIid() : 0;
                $this->course->resources[RESOURCE_DOCUMENT][$k]->destination_id = $iid;

                $this->dlog('restore_documents: file created', [
                    'title' => $finalTitle,
                    'iid'   => $iid,
                    'mode'  => $copyMode ? 'copy' : 'import',
                ]);
            } catch (\Throwable $e) {
                $this->dlog('restore_documents: file create failed', ['title' => $finalTitle, 'error' => $e->getMessage()]);
            }
        }

        $this->dlog('restore_documents: end');
    }

    /**
     * Restore forum categories in the destination course.
     *
     * @param mixed $session_id
     * @param mixed $respect_base_content
     * @param mixed $destination_course_code
     */
    public function restore_forum_category($session_id = 0, $respect_base_content = false, $destination_course_code = ''): void
    {
        $bag = $this->course->resources['Forum_Category']
            ?? $this->course->resources['forum_category']
            ?? [];

        if (empty($bag)) {
            $this->dlog('restore_forum_category: empty bag');

            return;
        }

        $em = Database::getManager();
        $catRepo = Container::getForumCategoryRepository();
        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $session_id);

        foreach ($bag as $id => $res) {
            if (!empty($res->destination_id)) {
                continue;
            }

            $obj = \is_object($res->obj ?? null) ? $res->obj : (object) [];
            $title = (string) ($obj->cat_title ?? $obj->title ?? "Forum category #$id");
            $comment = (string) ($obj->cat_comment ?? $obj->description ?? '');

            // Reescritura/creación de dependencias en contenido HTML (document/*) vía helper
            $comment = $this->rewriteHtmlForCourse($comment, (int) $session_id, '[forums.cat]');

            $existing = $catRepo->findOneBy(['title' => $title, 'resourceNode.parent' => $course->getResourceNode()]);
            if ($existing) {
                $destIid = (int) $existing->getIid();
                $this->course->resources['Forum_Category'][$id] ??= new stdClass();
                $this->course->resources['Forum_Category'][$id]->destination_id = $destIid;
                $this->dlog('restore_forum_category: reuse existing', ['title' => $title, 'iid' => $destIid]);

                continue;
            }

            $cat = (new CForumCategory())
                ->setTitle($title)
                ->setCatComment($comment)
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;

            $catRepo->create($cat);
            $em->flush();

            $this->course->resources['Forum_Category'][$id] ??= new stdClass();
            $this->course->resources['Forum_Category'][$id]->destination_id = (int) $cat->getIid();
            $this->dlog('restore_forum_category: created', ['title' => $title, 'iid' => (int) $cat->getIid()]);
        }

        $this->dlog('restore_forum_category: done', ['count' => \count($bag)]);
    }

    /**
     * Restore forums and their topics/posts.
     */
    public function restore_forums(int $sessionId = 0): void
    {
        $forumsBag = $this->course->resources['forum'] ?? [];
        if (empty($forumsBag)) {
            $this->dlog('restore_forums: empty forums bag');

            return;
        }

        $em = Database::getManager();
        $catRepo = Container::getForumCategoryRepository();
        $forumRepo = Container::getForumRepository();

        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity($sessionId);

        $catBag = $this->course->resources['Forum_Category'] ?? $this->course->resources['forum_category'] ?? [];
        $catMap = [];

        if (!empty($catBag)) {
            foreach ($catBag as $srcCatId => $res) {
                if ((int) $res->destination_id > 0) {
                    $catMap[(int) $srcCatId] = (int) $res->destination_id;

                    continue;
                }

                $obj = \is_object($res->obj ?? null) ? $res->obj : (object) [];
                $title = (string) ($obj->cat_title ?? $obj->title ?? "Forum category #$srcCatId");
                $comment = (string) ($obj->cat_comment ?? $obj->description ?? '');

                $comment = $this->rewriteHtmlForCourse($comment, (int) $sessionId, '[forums.cat@forums]');

                $cat = (new CForumCategory())
                    ->setTitle($title)
                    ->setCatComment($comment)
                    ->setParent($course)
                    ->addCourseLink($course, $session)
                ;

                $catRepo->create($cat);
                $em->flush();

                $destIid = (int) $cat->getIid();
                $catMap[(int) $srcCatId] = $destIid;

                $this->course->resources['Forum_Category'][$srcCatId] ??= new stdClass();
                $this->course->resources['Forum_Category'][$srcCatId]->destination_id = $destIid;

                $this->dlog('restore_forums: created category', [
                    'src_id' => (int) $srcCatId, 'iid' => $destIid, 'title' => $title,
                ]);
            }
        }

        $defaultCategory = null;
        $ensureDefault = function () use (&$defaultCategory, $course, $session, $catRepo, $em): CForumCategory {
            if ($defaultCategory instanceof CForumCategory) {
                return $defaultCategory;
            }
            $defaultCategory = (new CForumCategory())
                ->setTitle('General')
                ->setCatComment('')
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;
            $catRepo->create($defaultCategory);
            $em->flush();

            return $defaultCategory;
        };

        foreach ($forumsBag as $srcForumId => $forumRes) {
            if (!\is_object($forumRes) || !\is_object($forumRes->obj)) {
                continue;
            }

            if ((int) ($forumRes->destination_id ?? 0) > 0) {
                $this->dlog('restore_forums: already mapped, skipping', [
                    'src_forum_id' => (int) $srcForumId,
                    'dst_forum_iid' => (int) $forumRes->destination_id,
                ]);
                continue;
            }

            $p = (array) $forumRes->obj;

            $dstCategory = null;
            $srcCatId = (int) ($p['forum_category'] ?? 0);
            if ($srcCatId > 0 && isset($catMap[$srcCatId])) {
                $dstCategory = $catRepo->find($catMap[$srcCatId]);
            }
            if (!$dstCategory && 1 === \count($catMap)) {
                $onlyDestIid = (int) reset($catMap);
                $dstCategory = $catRepo->find($onlyDestIid);
            }
            if (!$dstCategory) {
                $dstCategory = $ensureDefault();
            }

            $forumComment = (string) ($p['forum_comment'] ?? '');
            $forumComment = $this->rewriteHtmlForCourse($forumComment, (int) $sessionId, '[forums.forum]');

            $forum = (new CForum())
                ->setTitle($p['forum_title'] ?? ('Forum #'.$srcForumId))
                ->setForumComment($forumComment)
                ->setForumCategory($dstCategory)
                ->setAllowAnonymous((int) ($p['allow_anonymous'] ?? 0))
                ->setAllowEdit((int) ($p['allow_edit'] ?? 0))
                ->setApprovalDirectPost((string) ($p['approval_direct_post'] ?? '0'))
                ->setAllowAttachments((int) ($p['allow_attachments'] ?? 1))
                ->setAllowNewThreads((int) ($p['allow_new_threads'] ?? 1))
                ->setDefaultView($p['default_view'] ?? 'flat')
                ->setForumOfGroup((string) ($p['forum_of_group'] ?? 0))
                ->setForumGroupPublicPrivate($p['forum_group_public_private'] ?? 'public')
                ->setModerated((bool) ($p['moderated'] ?? false))
                ->setStartTime(!empty($p['start_time']) && '0000-00-00 00:00:00' !== $p['start_time']
                    ? api_get_utc_datetime($p['start_time'], true, true) : null)
                ->setEndTime(!empty($p['end_time']) && '0000-00-00 00:00:00' !== $p['end_time']
                    ? api_get_utc_datetime($p['end_time'], true, true) : null)
                ->setParent($dstCategory ?: $course)
                ->addCourseLink($course, $session)
            ;

            $forumRepo->create($forum);
            $em->flush();

            $this->course->resources['forum'][$srcForumId] ??= new stdClass();
            $this->course->resources['forum'][$srcForumId]->destination_id = (int) $forum->getIid();
            $this->dlog('restore_forums: created forum', [
                'src_forum_id' => (int) $srcForumId,
                'dst_forum_iid' => (int) $forum->getIid(),
                'category_iid' => (int) $dstCategory->getIid(),
            ]);

            $topicsBag = $this->course->resources['thread'] ?? [];
            foreach ($topicsBag as $srcThreadId => $topicRes) {
                if (!\is_object($topicRes) || !\is_object($topicRes->obj)) {
                    continue;
                }
                if ((int) $topicRes->obj->forum_id === (int) $srcForumId) {
                    $tid = $this->restore_topic((int) $srcThreadId, (int) $forum->getIid(), $sessionId);
                    $this->dlog('restore_forums: topic restored', [
                        'src_thread_id' => (int) $srcThreadId,
                        'dst_thread_iid' => (int) ($tid ?? 0),
                        'dst_forum_iid' => (int) $forum->getIid(),
                    ]);
                }
            }
        }

        $this->dlog('restore_forums: done', ['forums' => \count($forumsBag)]);
    }

    /**
     * Restore a forum topic (thread).
     */
    public function restore_topic(int $srcThreadId, int $dstForumId, int $sessionId = 0): ?int
    {
        $topicsBag = $this->course->resources['thread'] ?? [];
        $topicRes = $topicsBag[$srcThreadId] ?? null;
        if (!$topicRes || !\is_object($topicRes->obj)) {
            $this->dlog('restore_topic: missing topic object', ['src_thread_id' => $srcThreadId]);

            return null;
        }

        $em = Database::getManager();
        $forumRepo = Container::getForumRepository();
        $threadRepo = Container::getForumThreadRepository();
        $postRepo = Container::getForumPostRepository();

        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $sessionId);
        $user = api_get_user_entity($this->first_teacher_id);

        /** @var CForum|null $forum */
        $forum = $forumRepo->find($dstForumId);
        if (!$forum) {
            $this->dlog('restore_topic: destination forum not found', ['dst_forum_id' => $dstForumId]);

            return null;
        }

        $p = (array) $topicRes->obj;

        $thread = (new CForumThread())
            ->setTitle((string) ($p['thread_title'] ?? "Thread #$srcThreadId"))
            ->setForum($forum)
            ->setUser($user)
            ->setThreadDate(new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC')))
            ->setThreadSticky((bool) ($p['thread_sticky'] ?? false))
            ->setThreadTitleQualify((string) ($p['thread_title_qualify'] ?? ''))
            ->setThreadQualifyMax((float) ($p['thread_qualify_max'] ?? 0))
            ->setThreadWeight((float) ($p['thread_weight'] ?? 0))
            ->setThreadPeerQualify((bool) ($p['thread_peer_qualify'] ?? false))
            ->setParent($forum)
            ->addCourseLink($course, $session)
        ;

        $threadRepo->create($thread);
        $em->flush();

        $this->course->resources['thread'][$srcThreadId] ??= new stdClass();
        $this->course->resources['thread'][$srcThreadId]->destination_id = (int) $thread->getIid();
        $this->dlog('restore_topic: created', [
            'src_thread_id' => $srcThreadId,
            'dst_thread_iid' => (int) $thread->getIid(),
            'dst_forum_iid' => (int) $forum->getIid(),
        ]);

        $postsBag = $this->course->resources['post'] ?? [];
        foreach ($postsBag as $srcPostId => $postRes) {
            if (!\is_object($postRes) || !\is_object($postRes->obj)) {
                continue;
            }
            if ((int) $postRes->obj->thread_id === (int) $srcThreadId) {
                $pid = $this->restore_post((int) $srcPostId, (int) $thread->getIid(), (int) $forum->getIid(), $sessionId);
                $this->dlog('restore_topic: post restored', [
                    'src_post_id' => (int) $srcPostId,
                    'dst_post_iid' => (int) ($pid ?? 0),
                ]);
            }
        }

        $last = $postRepo->findOneBy(['thread' => $thread], ['postDate' => 'DESC']);
        if ($last) {
            $thread->setThreadLastPost($last);
            $em->persist($thread);
            $em->flush();
        }

        return (int) $thread->getIid();
    }

    /**
     * Restore a forum post.
     */
    public function restore_post(int $srcPostId, int $dstThreadId, int $dstForumId, int $sessionId = 0): ?int
    {
        $postsBag = $this->course->resources['post'] ?? [];
        $postRes = $postsBag[$srcPostId] ?? null;
        if (!$postRes || !\is_object($postRes->obj)) {
            $this->dlog('restore_post: missing post object', ['src_post_id' => $srcPostId]);

            return null;
        }

        $em = Database::getManager();
        $forumRepo = Container::getForumRepository();
        $threadRepo = Container::getForumThreadRepository();
        $postRepo = Container::getForumPostRepository();

        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $sessionId);
        $user = api_get_user_entity($this->first_teacher_id);

        $thread = $threadRepo->find($dstThreadId);
        $forum = $forumRepo->find($dstForumId);
        if (!$thread || !$forum) {
            $this->dlog('restore_post: destination thread/forum not found', [
                'dst_thread_id' => $dstThreadId,
                'dst_forum_id' => $dstForumId,
            ]);

            return null;
        }

        $p = (array) $postRes->obj;

        $postText = (string) ($p['post_text'] ?? '');
        $postText = $this->rewriteHtmlForCourse($postText, (int) $sessionId, '[forums.post]');

        $post = (new CForumPost())
            ->setTitle((string) ($p['post_title'] ?? "Post #$srcPostId"))
            ->setPostText($postText)
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($user)
            ->setPostDate(new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC')))
            ->setPostNotification((bool) ($p['post_notification'] ?? false))
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
            ->setParent($thread)
            ->addCourseLink($course, $session)
        ;

        if (!empty($p['post_parent_id'])) {
            $parentDestId = (int) ($postsBag[$p['post_parent_id']]->destination_id ?? 0);
            if ($parentDestId > 0) {
                $parent = $postRepo->find($parentDestId);
                if ($parent) {
                    $post->setPostParent($parent);
                }
            }
        }

        $postRepo->create($post);
        $em->flush();

        $this->course->resources['post'][$srcPostId] ??= new stdClass();
        $this->course->resources['post'][$srcPostId]->destination_id = (int) $post->getIid();
        $this->dlog('restore_post: created', [
            'src_post_id' => (int) $srcPostId,
            'dst_post_iid' => (int) $post->getIid(),
            'dst_thread_id' => (int) $thread->getIid(),
            'dst_forum_id' => (int) $forum->getIid(),
        ]);

        return (int) $post->getIid();
    }

    /**
     * Restore a link category.
     *
     * @param mixed $id
     * @param mixed $sessionId
     */
    public function restore_link_category($id, $sessionId = 0): int
    {
        $sessionId = (int) $sessionId;

        if (0 === (int) $id) {
            $this->dlog('restore_link_category: source category is 0 (no category), returning 0');

            return 0;
        }

        $resources = $this->course->resources ?? [];

        // Resolve the actual bucket key present in this backup
        $candidateKeys = ['link_category', 'Link_Category'];
        if (\defined('RESOURCE_LINKCATEGORY') && RESOURCE_LINKCATEGORY) {
            $candidateKeys[] = (string) RESOURCE_LINKCATEGORY;
        }

        $catKey = null;
        foreach ($candidateKeys as $k) {
            if (isset($resources[$k]) && \is_array($resources[$k])) {
                $catKey = $k;

                break;
            }
        }

        if (null === $catKey) {
            $this->dlog('restore_link_category: no category bucket in course->resources');

            return 0;
        }

        // Locate the category wrapper by 3 strategies: array key, wrapper->source_id, inner obj->id
        $bucket = $resources[$catKey];

        // by integer array key
        $byIntKey = [];
        foreach ($bucket as $k => $wrap) {
            $ik = is_numeric($k) ? (int) $k : 0;
            if ($ik > 0) {
                $byIntKey[$ik] = $wrap;
            }
        }

        // by wrapper->source_id
        $bySourceId = [];
        foreach ($bucket as $wrap) {
            if (!\is_object($wrap)) {
                continue;
            }
            $sid = isset($wrap->source_id) ? (int) $wrap->source_id : 0;
            if ($sid > 0) {
                $bySourceId[$sid] = $wrap;
            }
        }

        // by inner entity id (obj->id)
        $byObjId = [];
        foreach ($bucket as $wrap) {
            if (\is_object($wrap) && isset($wrap->obj) && \is_object($wrap->obj)) {
                $oid = isset($wrap->obj->id) ? (int) $wrap->obj->id : 0;
                if ($oid > 0) {
                    $byObjId[$oid] = $wrap;
                }
            }
        }

        $iid = (int) $id;
        $srcCat = $byIntKey[$iid]
            ?? $bySourceId[$iid]
            ?? $byObjId[$iid]
            ?? ($bucket[(string) $id] ?? ($bucket[$id] ?? null));

        if (!\is_object($srcCat)) {
            $this->dlog('restore_link_category: source category object not found', [
                'asked_id' => $iid,
                'bucket' => $catKey,
                'keys_seen' => \array_slice(array_keys((array) $bucket), 0, 12),
                'index_hit' => [
                    'byIntKey' => isset($byIntKey[$iid]),
                    'bySourceId' => isset($bySourceId[$iid]),
                    'byObjId' => isset($byObjId[$iid]),
                ],
            ]);

            return 0;
        }

        // Already mapped?
        if ((int) $srcCat->destination_id > 0) {
            return (int) $srcCat->destination_id;
        }

        // Unwrap/normalize fields
        $e = (isset($srcCat->obj) && \is_object($srcCat->obj)) ? $srcCat->obj : $srcCat;
        $title = trim((string) ($e->title ?? $e->category_title ?? ($srcCat->extra['title'] ?? '') ?? ''));
        if ('' === $title) {
            $title = 'Links';
        }
        $description = (string) ($e->description ?? ($srcCat->extra['description'] ?? '') ?? '');

        $em = Database::getManager();
        $catRepo = Container::getLinkCategoryRepository();
        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $sessionId);

        // Look for an existing category under the same course (by title)
        $existing = null;
        $candidates = $catRepo->findBy(['title' => $title]);
        if (!empty($candidates)) {
            $courseNode = $course->getResourceNode();
            foreach ($candidates as $cand) {
                $node = method_exists($cand, 'getResourceNode') ? $cand->getResourceNode() : null;
                $parent = $node && method_exists($node, 'getParent') ? $node->getParent() : null;
                if ($parent && $courseNode && $parent->getId() === $courseNode->getId()) {
                    $existing = $cand;

                    break;
                }
            }
        }

        if ($existing) {
            if (FILE_SKIP === $this->file_option) {
                $destIid = (int) $existing->getIid();
                // Write back to the SAME wrapper we located
                $srcCat->destination_id = $destIid;
                $this->dlog('restore_link_category: reuse (SKIP)', [
                    'src_cat_id' => $iid, 'dst_cat_id' => $destIid, 'title' => $title,
                ]);

                return $destIid;
            }

            if (FILE_OVERWRITE === $this->file_option) {
                $existing->setDescription($description);
                if (method_exists($existing, 'setParent')) {
                    $existing->setParent($course);
                }
                if (method_exists($existing, 'addCourseLink')) {
                    $existing->addCourseLink($course, $session);
                }
                $em->persist($existing);
                $em->flush();

                $destIid = (int) $existing->getIid();
                $srcCat->destination_id = $destIid;
                $this->dlog('restore_link_category: overwrite', [
                    'src_cat_id' => $iid, 'dst_cat_id' => $destIid, 'title' => $title,
                ]);

                return $destIid;
            }

            // FILE_RENAME policy
            $base = $title;
            $i = 1;
            $exists = true;
            do {
                $title = $base.' ('.$i++.')';
                $exists = false;
                foreach ($catRepo->findBy(['title' => $title]) as $cand) {
                    $node = method_exists($cand, 'getResourceNode') ? $cand->getResourceNode() : null;
                    $parent = $node && method_exists($node, 'getParent') ? $node->getParent() : null;
                    if ($parent && $parent->getId() === $course->getResourceNode()->getId()) {
                        $exists = true;

                        break;
                    }
                }
            } while ($exists);
        }

        // Create new category
        $cat = (new CLinkCategory())
            ->setTitle($title)
            ->setDescription($description)
        ;

        if (method_exists($cat, 'setParent')) {
            $cat->setParent($course);
        }
        if (method_exists($cat, 'addCourseLink')) {
            $cat->addCourseLink($course, $session);
        }

        $em->persist($cat);
        $em->flush();

        $destIid = (int) $cat->getIid();

        // Write back to the SAME wrapper we located (object is by reference)
        $srcCat->destination_id = $destIid;

        $this->dlog('restore_link_category: created', [
            'src_cat_id' => $iid, 'dst_cat_id' => $destIid, 'title' => $title, 'bucket' => $catKey,
        ]);

        return $destIid;
    }

    /**
     * Restore course links.
     *
     * @param mixed $session_id
     */
    public function restore_links($session_id = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_LINK)) {
            return;
        }

        $resources = $this->course->resources;
        $count = \is_array($resources[RESOURCE_LINK] ?? null) ? \count($resources[RESOURCE_LINK]) : 0;

        $this->dlog('restore_links: begin', ['count' => $count]);

        $em = Database::getManager();
        $linkRepo = Container::getLinkRepository();
        $catRepo = Container::getLinkCategoryRepository();
        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $session_id);

        // Safe duplicate finder (no dot-path in criteria; filter parent in PHP)
        $findDuplicate = function (string $t, string $u, ?CLinkCategory $cat) use ($linkRepo, $course) {
            $criteria = ['title' => $t, 'url' => $u, 'category' => ($cat instanceof CLinkCategory ? $cat : null)];
            $candidates = $linkRepo->findBy($criteria);
            if (empty($candidates)) {
                return null;
            }
            $courseNode = $course->getResourceNode();
            foreach ($candidates as $cand) {
                $node = method_exists($cand, 'getResourceNode') ? $cand->getResourceNode() : null;
                $parent = $node && method_exists($node, 'getParent') ? $node->getParent() : null;
                if ($parent && $courseNode && $parent->getId() === $courseNode->getId()) {
                    return $cand;
                }
            }

            return null;
        };

        foreach ($resources[RESOURCE_LINK] as $oldLinkId => $link) {

            $mapped = (int) ($this->course->resources[RESOURCE_LINK][$oldLinkId]->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_links: already mapped, skipping', [
                    'src_link_id' => (int) $oldLinkId,
                    'dst_link_id' => $mapped,
                ]);
                continue;
            }

            $rawUrl = (string) ($link->url ?? ($link->extra['url'] ?? ''));
            $rawTitle = (string) ($link->title ?? ($link->extra['title'] ?? ''));
            $rawDesc = (string) ($link->description ?? ($link->extra['description'] ?? ''));
            $target = isset($link->target) ? (string) $link->target : null;

            // Prefer plain category_id, fallback to linked_resources if needed
            $catSrcId = (int) ($link->category_id ?? 0);
            if ($catSrcId <= 0 && isset($link->linked_resources['Link_Category'][0])) {
                $catSrcId = (int) $link->linked_resources['Link_Category'][0];
            }
            if ($catSrcId <= 0 && isset($link->linked_resources['link_category'][0])) {
                $catSrcId = (int) $link->linked_resources['link_category'][0];
            }

            $onHome = (bool) ($link->on_homepage ?? false);

            $url = trim($rawUrl);
            $title = '' !== trim($rawTitle) ? trim($rawTitle) : $url;

            if ('' === $url) {
                $this->dlog('restore_links: skipped (empty URL)', [
                    'src_link_id' => (int) $oldLinkId,
                    'has_obj' => !empty($link->has_obj),
                    'extra_keys' => isset($link->extra) ? implode(',', array_keys((array) $link->extra)) : '',
                ]);

                continue;
            }

            // Resolve / create destination category if source had one; otherwise null
            $category = null;
            if ($catSrcId > 0) {
                $dstCatIid = (int) $this->restore_link_category($catSrcId, (int) $session_id);
                if ($dstCatIid > 0) {
                    $category = $catRepo->find($dstCatIid);
                } else {
                    $this->dlog('restore_links: category not available, using null', [
                        'src_link_id' => (int) $oldLinkId,
                        'src_cat_id' => (int) $catSrcId,
                    ]);
                }
            }

            // Dedup (title + url + category in same course)
            $existing = $findDuplicate($title, $url, $category);

            if ($existing) {
                if (FILE_SKIP === $this->file_option) {
                    $destIid = (int) $existing->getIid();
                    $this->course->resources[RESOURCE_LINK][$oldLinkId] ??= new stdClass();
                    $this->course->resources[RESOURCE_LINK][$oldLinkId]->destination_id = $destIid;

                    $this->dlog('restore_links: reuse (SKIP)', [
                        'src_link_id' => (int) $oldLinkId,
                        'dst_link_id' => $destIid,
                        'title' => $title,
                        'url' => $url,
                    ]);

                    continue;
                }

                if (FILE_OVERWRITE === $this->file_option) {
                    $descHtml = $this->rewriteHtmlForCourse($rawDesc, (int) $session_id, '[links.link.overwrite]');

                    $existing
                        ->setUrl($url)
                        ->setTitle($title)
                        ->setDescription($descHtml)
                        ->setTarget((string) ($target ?? ''))
                    ;

                    if (method_exists($existing, 'setParent')) {
                        $existing->setParent($course);
                    }
                    if (method_exists($existing, 'addCourseLink')) {
                        $existing->addCourseLink($course, $session);
                    }
                    $existing->setCategory($category); // may be null

                    $em->persist($existing);
                    $em->flush();

                    $destIid = (int) $existing->getIid();
                    $this->course->resources[RESOURCE_LINK][$oldLinkId] ??= new stdClass();
                    $this->course->resources[RESOURCE_LINK][$oldLinkId]->destination_id = $destIid;

                    $this->dlog('restore_links: overwrite', [
                        'src_link_id' => (int) $oldLinkId,
                        'dst_link_id' => $destIid,
                        'title' => $title,
                        'url' => $url,
                    ]);

                    continue;
                }

                // FILE_RENAME flow
                $base = $title;
                $i = 1;
                do {
                    $title = $base.' ('.$i.')';
                    $i++;
                } while ($findDuplicate($title, $url, $category));
            }

            $descHtml = $this->rewriteHtmlForCourse($rawDesc, (int) $session_id, '[links.link.create]');

            // Create new link entity
            $entity = (new CLink())
                ->setUrl($url)
                ->setTitle($title)
                ->setDescription($descHtml)
                ->setTarget((string) ($target ?? ''))
            ;

            if (method_exists($entity, 'setParent')) {
                $entity->setParent($course);
            }
            if (method_exists($entity, 'addCourseLink')) {
                $entity->addCourseLink($course, $session);
            }

            if ($category instanceof CLinkCategory) {
                $entity->setCategory($category);
            }

            $em->persist($entity);
            $em->flush();

            // Map destination id back into resources
            $destIid = (int) $entity->getIid();
            $this->course->resources[RESOURCE_LINK][$oldLinkId] ??= new stdClass();
            $this->course->resources[RESOURCE_LINK][$oldLinkId]->destination_id = $destIid;

            $this->dlog('restore_links: created', [
                'src_link_id' => (int) $oldLinkId,
                'dst_link_id' => $destIid,
                'title' => $title,
                'url' => $url,
                'category' => $category ? $category->getTitle() : null,
            ]);

            if (!empty($onHome)) {
                try {
                    $em->persist($entity);
                    $em->flush();
                } catch (Throwable $e) {
                    error_log('COURSE_DEBUG: restore_links: homepage flag handling failed: '.$e->getMessage());
                }
            }
        }

        $this->dlog('restore_links: end');
    }

    /**
     * Restore tool introductions.
     *
     * Accept multiple bucket spellings to be robust against controller normalization:
     * - RESOURCE_TOOL_INTRO (if defined)
     * - 'Tool introduction' (legacy)
     * - 'tool_intro' / 'tool introduction' / 'tool_introduction'
     *
     * @param mixed $sessionId
     */
    public function restore_tool_intro($sessionId = 0): void
    {
        $resources = $this->course->resources ?? [];

        // Detect the right bucket key (be generous with aliases)
        $bagKey = null;
        $candidates = [];

        if (\defined('RESOURCE_TOOL_INTRO')) {
            $candidates[] = RESOURCE_TOOL_INTRO;
        }

        // Common spellings seen in exports / normalizers
        $candidates = array_merge($candidates, [
            'Tool introduction',
            'tool introduction',
            'tool_introduction',
            'tool/intro',
            'tool_intro',
        ]);

        foreach ($candidates as $k) {
            if (!empty($resources[$k]) && \is_array($resources[$k])) {
                $bagKey = $k;
                break;
            }
        }

        if (null === $bagKey) {
            $this->dlog('restore_tool_intro: no matching bucket found', [
                'available_keys' => array_keys((array) $resources),
            ]);
            return;
        }

        $sessionId = (int) $sessionId;
        $this->dlog('restore_tool_intro: begin', [
            'bucket' => $bagKey,
            'count'  => \count($resources[$bagKey]),
        ]);

        $em      = Database::getManager();
        $course  = api_get_course_entity($this->destination_course_id);
        $session = $sessionId ? api_get_session_entity($sessionId) : null;

        $toolRepo   = $em->getRepository(Tool::class);
        $cToolRepo  = $em->getRepository(CTool::class);
        $introRepo  = $em->getRepository(CToolIntro::class);

        foreach ($resources[$bagKey] as $rawId => $tIntro) {
            // Resolve tool key (id may be missing in some dumps)
            $toolKey = trim((string) ($tIntro->id ?? ''));
            if ('' === $toolKey || '0' === $toolKey) {
                $toolKey = (string) $rawId;
            }
            $alias = strtolower($toolKey);

            // Normalize common aliases to platform keys
            if ('homepage' === $alias || 'course_home' === $alias) {
                $toolKey = 'course_homepage';
            }

            $this->dlog('restore_tool_intro: resolving tool key', [
                'raw_id'   => (string) $rawId,
                'obj_id'   => isset($tIntro->id) ? (string) $tIntro->id : null,
                'toolKey'  => $toolKey,
            ]);

            // Already mapped?
            $mapped = (int) ($tIntro->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_tool_intro: already mapped, skipping', [
                    'src_id' => $toolKey, 'dst_id' => $mapped,
                ]);
                continue;
            }

            // Rewrite HTML using centralized helper (keeps document links consistent)
            $introHtml = $this->rewriteHtmlForCourse((string) ($tIntro->intro_text ?? ''), $sessionId, '[tool_intro.intro]');

            // Find platform Tool entity by title; try a couple of fallbacks
            $toolEntity = $toolRepo->findOneBy(['title' => $toolKey]);
            if (!$toolEntity) {
                // Fallbacks: lower/upper case attempts
                $toolEntity = $toolRepo->findOneBy(['title' => strtolower($toolKey)])
                    ?: $toolRepo->findOneBy(['title' => ucfirst(strtolower($toolKey))]);
            }
            if (!$toolEntity) {
                $this->dlog('restore_tool_intro: missing Tool entity, skipping', ['tool' => $toolKey]);
                continue;
            }

            // Ensure a CTool exists for this course/session+tool
            $cTool = $cToolRepo->findOneBy([
                'course'  => $course,
                'session' => $session,
                'title'   => $toolKey,
            ]);

            if (!$cTool) {
                $cTool = (new CTool())
                    ->setTool($toolEntity)
                    ->setTitle($toolKey)
                    ->setCourse($course)
                    ->setSession($session)
                    ->setPosition(1)
                    ->setVisibility(true)
                    ->setParent($course)
                    ->setCreator($course->getCreator() ?? null)
                    ->addCourseLink($course, $session);
                $em->persist($cTool);
                $em->flush();

                $this->dlog('restore_tool_intro: CTool created', [
                    'tool' => $toolKey,
                    'ctool_id' => (int) $cTool->getIid(),
                ]);
            }

            // Create/overwrite intro according to file policy
            $intro = $introRepo->findOneBy(['courseTool' => $cTool]);

            if ($intro) {
                if (FILE_SKIP === $this->file_option) {
                    $this->dlog('restore_tool_intro: reuse existing (SKIP)', [
                        'tool' => $toolKey,
                        'intro_id' => (int) $intro->getIid(),
                    ]);
                } else {
                    $intro->setIntroText($introHtml);
                    $em->persist($intro);
                    $em->flush();

                    $this->dlog('restore_tool_intro: intro overwritten', [
                        'tool' => $toolKey,
                        'intro_id' => (int) $intro->getIid(),
                    ]);
                }
            } else {
                $intro = (new CToolIntro())
                    ->setCourseTool($cTool)
                    ->setIntroText($introHtml)
                    ->setParent($course);
                $em->persist($intro);
                $em->flush();

                $this->dlog('restore_tool_intro: intro created', [
                    'tool' => $toolKey,
                    'intro_id' => (int) $intro->getIid(),
                ]);
            }

            // Map destination id back into the bucket used
            $this->course->resources[$bagKey][$rawId] ??= new \stdClass();
            $this->course->resources[$bagKey][$rawId]->destination_id = (int) $intro->getIid();
        }

        $this->dlog('restore_tool_intro: end');
    }

    /**
     * Restore calendar events.
     */
    public function restore_events(int $sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_EVENT)) {
            return;
        }

        $resources = $this->course->resources ?? [];
        $bag = $resources[RESOURCE_EVENT] ?? [];
        $count = \is_array($bag) ? \count($bag) : 0;

        $this->dlog('restore_events: begin', ['count' => $count]);

        /** @var EntityManagerInterface $em */
        $em = Database::getManager();
        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity($sessionId);
        $group = api_get_group_entity();
        $eventRepo = Container::getCalendarEventRepository();
        $attachRepo = Container::getCalendarEventAttachmentRepository();

        // Dedupe by title inside same course/session
        $findExistingByTitle = function (string $title) use ($eventRepo, $course, $session) {
            $qb = $eventRepo->getResourcesByCourse($course, $session, null, null, true, true);
            $qb->andWhere('resource.title = :t')->setParameter('t', $title)->setMaxResults(1);

            return $qb->getQuery()->getOneOrNullResult();
        };

        $originPath = rtrim((string) ($this->course->backup_path ?? ''), '/').'/upload/calendar/';

        foreach ($bag as $oldId => $raw) {
            // Already mapped?
            $mapped = (int) ($raw->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_events: already mapped, skipping', ['src_id' => (int) $oldId, 'dst_id' => $mapped]);

                continue;
            }

            // Normalize + rewrite content
            $title = trim((string) ($raw->title ?? ''));
            if ('' === $title) {
                $title = 'Event';
            }

            $content = $this->rewriteHtmlForCourse((string) ($raw->content ?? ''), $sessionId, '[events.content]');

            // Dates
            $allDay = (bool) ($raw->all_day ?? false);
            $start = null;
            $end = null;

            try {
                $s = (string) ($raw->start_date ?? '');
                if ('' !== $s) {
                    $start = new DateTime($s);
                }
            } catch (Throwable) {
            }

            try {
                $e = (string) ($raw->end_date ?? '');
                if ('' !== $e) {
                    $end = new DateTime($e);
                }
            } catch (Throwable) {
            }

            // Dedupe policy
            $existing = $findExistingByTitle($title);
            if ($existing) {
                switch ($this->file_option) {
                    case FILE_SKIP:
                        $destId = (int) $existing->getIid();
                        $this->course->resources[RESOURCE_EVENT][$oldId] ??= new stdClass();
                        $this->course->resources[RESOURCE_EVENT][$oldId]->destination_id = $destId;
                        $this->dlog('restore_events: reuse (SKIP)', ['src_id' => (int) $oldId, 'dst_id' => $destId, 'title' => $existing->getTitle()]);
                        $this->restoreEventAttachments($raw, $existing, $originPath, $attachRepo, $em);

                        continue 2;

                    case FILE_OVERWRITE:
                        $existing
                            ->setTitle($title)
                            ->setContent($content)
                            ->setAllDay($allDay)
                            ->setParent($course)
                            ->addCourseLink($course, $session, $group)
                        ;
                        $existing->setStartDate($start);
                        $existing->setEndDate($end);

                        $em->persist($existing);
                        $em->flush();

                        $this->course->resources[RESOURCE_EVENT][$oldId] ??= new stdClass();
                        $this->course->resources[RESOURCE_EVENT][$oldId]->destination_id = (int) $existing->getIid();

                        $this->dlog('restore_events: overwrite', ['src_id' => (int) $oldId, 'dst_id' => (int) $existing->getIid(), 'title' => $title]);

                        $this->restoreEventAttachments($raw, $existing, $originPath, $attachRepo, $em);

                        continue 2;

                    case FILE_RENAME:
                    default:
                        $base = $title;
                        $i = 1;
                        $candidate = $base;
                        while ($findExistingByTitle($candidate)) {
                            $candidate = $base.' ('.(++$i).')';
                        }
                        $title = $candidate;

                        break;
                }
            }

            // Create new event
            $entity = (new CCalendarEvent())
                ->setTitle($title)
                ->setContent($content)
                ->setAllDay($allDay)
                ->setParent($course)
                ->addCourseLink($course, $session, $group)
            ;

            $entity->setStartDate($start);
            $entity->setEndDate($end);

            $em->persist($entity);
            $em->flush();

            $destId = (int) $entity->getIid();
            $this->course->resources[RESOURCE_EVENT][$oldId] ??= new stdClass();
            $this->course->resources[RESOURCE_EVENT][$oldId]->destination_id = $destId;

            $this->dlog('restore_events: created', ['src_id' => (int) $oldId, 'dst_id' => $destId, 'title' => $title]);

            // Attachments
            $this->restoreEventAttachments($raw, $entity, $originPath, $attachRepo, $em);
        }

        $this->dlog('restore_events: end');
    }

    /**
     * Handle event attachments.
     *
     * @param mixed $attachRepo
     */
    private function restoreEventAttachments(
        object $raw,
        CCalendarEvent $entity,
        string $originPath,
        $attachRepo,
        EntityManagerInterface $em
    ): void {
        // Helper to actually persist + move file
        $persistAttachmentFromFile = function (string $src, string $filename, ?string $comment) use ($entity, $attachRepo, $em): void {
            if (!is_file($src) || !is_readable($src)) {
                $this->dlog('restore_events: attachment source not readable', ['src' => $src]);

                return;
            }

            // Avoid duplicate filenames on same event
            foreach ($entity->getAttachments() as $att) {
                if ($att->getFilename() === $filename) {
                    $this->dlog('restore_events: attachment already exists, skipping', ['filename' => $filename]);

                    return;
                }
            }

            $attachment = (new CCalendarEventAttachment())
                ->setFilename($filename)
                ->setComment($comment ?? '')
                ->setEvent($entity)
                ->setParent($entity)
                ->addCourseLink(
                    api_get_course_entity($this->destination_course_id),
                    api_get_session_entity(0),
                    api_get_group_entity()
                )
            ;

            $em->persist($attachment);
            $em->flush();

            if (method_exists($attachRepo, 'addFileFromLocalPath')) {
                $attachRepo->addFileFromLocalPath($attachment, $src);
            } else {
                $dstDir = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/upload/calendar/';
                @mkdir($dstDir, 0775, true);
                $newName = uniqid('calendar_', true);
                @copy($src, $dstDir.$newName);
            }

            $this->dlog('restore_events: attachment created', [
                'event_id' => (int) $entity->getIid(),
                'filename' => $filename,
            ]);
        };

        // modern backup fields on object
        if (!empty($raw->attachment_path)) {
            $src = rtrim($originPath, '/').'/'.$raw->attachment_path;
            $filename = (string) ($raw->attachment_filename ?? basename($src));
            $comment = (string) ($raw->attachment_comment ?? '');
            $persistAttachmentFromFile($src, $filename, $comment);

            return;
        }

        // legacy lookup from old course tables when ->orig present
        if (!empty($this->course->orig)) {
            $table = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
            $sql = 'SELECT path, comment, filename
                FROM '.$table.'
                WHERE c_id = '.$this->destination_course_id.'
                  AND agenda_id = '.(int) ($raw->source_id ?? 0);
            $res = Database::query($sql);
            while ($row = Database::fetch_object($res)) {
                $src = rtrim($originPath, '/').'/'.$row->path;
                $persistAttachmentFromFile($src, (string) $row->filename, (string) $row->comment);
            }
        }
    }

    /**
     * Restore course descriptions.
     *
     * @param mixed $session_id
     */
    public function restore_course_descriptions($session_id = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_COURSEDESCRIPTION)) {
            return;
        }

        $resources = $this->course->resources ?? [];
        $count = \is_array($resources[RESOURCE_COURSEDESCRIPTION] ?? null)
            ? \count($resources[RESOURCE_COURSEDESCRIPTION]) : 0;

        $this->dlog('restore_course_descriptions: begin', ['count' => $count]);

        $em = Database::getManager();
        $repo = Container::getCourseDescriptionRepository();
        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $session_id);

        $findByTypeInCourse = function (int $type) use ($repo, $course, $session) {
            if (method_exists($repo, 'findByTypeInCourse')) {
                return $repo->findByTypeInCourse($type, $course, $session);
            }
            $qb = $repo->getResourcesByCourse($course, $session)
                ->andWhere('resource.descriptionType = :t')
                ->setParameter('t', $type)
            ;

            return $qb->getQuery()->getResult();
        };

        $findByTitleInCourse = function (string $title) use ($repo, $course, $session) {
            $qb = $repo->getResourcesByCourse($course, $session)
                ->andWhere('resource.title = :t')
                ->setParameter('t', $title)
                ->setMaxResults(1)
            ;

            return $qb->getQuery()->getOneOrNullResult();
        };

        foreach ($resources[RESOURCE_COURSEDESCRIPTION] as $oldId => $cd) {
            // Already mapped?
            $mapped = (int) ($cd->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_course_descriptions: already mapped, skipping', ['src_id' => (int) $oldId, 'dst_id' => $mapped]);

                continue;
            }

            // Normalize + rewrite
            $rawTitle = (string) ($cd->title ?? '');
            $rawContent = (string) ($cd->content ?? '');
            $type = (int) ($cd->description_type ?? CCourseDescription::TYPE_DESCRIPTION);

            $title = '' !== trim($rawTitle) ? trim($rawTitle) : $rawTitle;
            $content = $this->rewriteHtmlForCourse($rawContent, (int) $session_id, '[course_description.content]');

            // Policy by type
            $existingByType = $findByTypeInCourse($type);
            $existingOne = $existingByType[0] ?? null;

            if ($existingOne) {
                switch ($this->file_option) {
                    case FILE_SKIP:
                        $destIid = (int) $existingOne->getIid();
                        $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId] ??= new stdClass();
                        $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId]->destination_id = $destIid;

                        $this->dlog('restore_course_descriptions: reuse (SKIP)', [
                            'src_id' => (int) $oldId,
                            'dst_id' => $destIid,
                            'type' => $type,
                            'title' => (string) $existingOne->getTitle(),
                        ]);

                        continue 2;

                    case FILE_OVERWRITE:
                        $existingOne
                            ->setTitle('' !== $title ? $title : (string) $existingOne->getTitle())
                            ->setContent($content)
                            ->setDescriptionType($type)
                            ->setProgress((int) ($cd->progress ?? 0))
                        ;
                        $existingOne->setParent($course)->addCourseLink($course, $session);

                        $em->persist($existingOne);
                        $em->flush();

                        $destIid = (int) $existingOne->getIid();
                        $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId] ??= new stdClass();
                        $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId]->destination_id = $destIid;

                        $this->dlog('restore_course_descriptions: overwrite', [
                            'src_id' => (int) $oldId,
                            'dst_id' => $destIid,
                            'type' => $type,
                            'title' => (string) $existingOne->getTitle(),
                        ]);

                        continue 2;

                    case FILE_RENAME:
                    default:
                        $base = '' !== $title ? $title : (string) ($cd->extra['title'] ?? 'Description');
                        $i = 1;
                        $candidate = $base;
                        while ($findByTitleInCourse($candidate)) {
                            $candidate = $base.' ('.(++$i).')';
                        }
                        $title = $candidate;

                        break;
                }
            }

            // Create new
            $entity = (new CCourseDescription())
                ->setTitle($title)
                ->setContent($content)
                ->setDescriptionType($type)
                ->setProgress((int) ($cd->progress ?? 0))
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;

            $em->persist($entity);
            $em->flush();

            $destIid = (int) $entity->getIid();
            $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId] ??= new stdClass();
            $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId]->destination_id = $destIid;

            $this->dlog('restore_course_descriptions: created', [
                'src_id' => (int) $oldId,
                'dst_id' => $destIid,
                'type' => $type,
                'title' => $title,
            ]);
        }

        $this->dlog('restore_course_descriptions: end');
    }

    /**
     * Restore announcements into the destination course.
     *
     * @param mixed $sessionId
     */
    public function restore_announcements($sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_ANNOUNCEMENT)) {
            return;
        }

        $sessionId = (int) $sessionId;
        $resources = $this->course->resources;

        $count = \is_array($resources[RESOURCE_ANNOUNCEMENT] ?? null)
            ? \count($resources[RESOURCE_ANNOUNCEMENT])
            : 0;

        $this->dlog('restore_announcements: begin', ['count' => $count]);

        /** @var EntityManagerInterface $em */
        $em = Database::getManager();
        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity($sessionId);
        $group = api_get_group_entity();
        $annRepo = Container::getAnnouncementRepository();
        $attachRepo = Container::getAnnouncementAttachmentRepository();

        // Origin path for ZIP/imported attachments (kept as-is)
        $originPath = rtrim($this->course->backup_path ?? '', '/').'/upload/announcements/';

        // Finder: existing announcement by title in this course/session
        $findExistingByTitle = function (string $title) use ($annRepo, $course, $session) {
            $qb = $annRepo->getResourcesByCourse($course, $session);
            $qb->andWhere('resource.title = :t')->setParameter('t', $title)->setMaxResults(1);

            return $qb->getQuery()->getOneOrNullResult();
        };

        foreach ($resources[RESOURCE_ANNOUNCEMENT] as $oldId => $a) {
            // Already mapped?
            $mapped = (int) ($a->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_announcements: already mapped, skipping', [
                    'src_id' => (int) $oldId, 'dst_id' => $mapped,
                ]);

                continue;
            }

            $title = trim((string) ($a->title ?? ''));
            if ('' === $title) {
                $title = 'Announcement';
            }

            $contentHtml = (string) ($a->content ?? '');

            // Parse optional end date
            $endDate = null;

            try {
                $rawDate = (string) ($a->date ?? '');
                if ('' !== $rawDate) {
                    $endDate = new DateTime($rawDate);
                }
            } catch (Throwable $e) {
                $endDate = null;
            }

            $emailSent = (bool) ($a->email_sent ?? false);

            $existing = $findExistingByTitle($title);
            if ($existing) {
                switch ($this->file_option) {
                    case FILE_SKIP:
                        $destId = (int) $existing->getIid();
                        $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId] ??= new stdClass();
                        $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId]->destination_id = $destId;
                        $this->dlog('restore_announcements: reuse (SKIP)', [
                            'src_id' => (int) $oldId, 'dst_id' => $destId, 'title' => $existing->getTitle(),
                        ]);
                        // Still try to restore attachments on the reused entity
                        $this->restoreAnnouncementAttachments($a, $existing, $originPath, $attachRepo, $em);

                        continue 2;

                    case FILE_OVERWRITE:
                        // Continue to overwrite below
                        break;

                    case FILE_RENAME:
                    default:
                        // Rename to avoid collision
                        $base = $title;
                        $i = 1;
                        $candidate = $base;
                        while ($findExistingByTitle($candidate)) {
                            $i++;
                            $candidate = $base.' ('.$i.')';
                        }
                        $title = $candidate;

                        break;
                }
            }

            // Rewrite HTML content using centralized helper (replaces manual mapping logic)
            // Note: keeps attachments restoration logic unchanged.
            $contentRewritten = $this->rewriteHtmlForCourse($contentHtml, $sessionId, '[announcements.content]');

            // Create or reuse entity
            $entity = $existing ?: (new CAnnouncement());
            $entity
                ->setTitle($title)
                ->setContent($contentRewritten) // content already rewritten
                ->setParent($course)
                ->addCourseLink($course, $session, $group)
                ->setEmailSent($emailSent)
            ;

            if ($endDate instanceof DateTimeInterface) {
                $entity->setEndDate($endDate);
            }

            $em->persist($entity);
            $em->flush();

            $destId = (int) $entity->getIid();
            $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId] ??= new stdClass();
            $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId]->destination_id = $destId;

            $this->dlog($existing ? 'restore_announcements: overwrite' : 'restore_announcements: created', [
                'src_id' => (int) $oldId, 'dst_id' => $destId, 'title' => $title,
            ]);

            // Handle binary attachments from backup or source
            $this->restoreAnnouncementAttachments($a, $entity, $originPath, $attachRepo, $em);
        }

        $this->dlog('restore_announcements: end');
    }

    /**
     * Create/update CAnnouncementAttachment + ResourceFile for each attachment of an announcement.
     * Sources:
     *  - COPY mode (no ZIP):   from source announcement's ResourceFiles
     *  - IMPORT mode (ZIP):    from /upload/announcements/* inside the package.
     *
     * Policies (by filename within the same announcement):
     *  - FILE_SKIP:       skip if filename exists
     *  - FILE_OVERWRITE:  reuse existing CAnnouncementAttachment and replace its ResourceFile
     *  - FILE_RENAME:     create a new CAnnouncementAttachment with incremental suffix
     */
    private function restoreAnnouncementAttachments(
        object $a,
        CAnnouncement $entity,
        string $originPath,
        CAnnouncementAttachmentRepository $attachRepo,
        EntityManagerInterface $em
    ): void {
        $copyMode = empty($this->course->backup_path);

        $findExistingByName = static function (CAnnouncement $ann, string $name) {
            foreach ($ann->getAttachments() as $att) {
                if ($att->getFilename() === $name) {
                    return $att;
                }
            }

            return null;
        };

        /**
         * Decide target entity + final filename according to file policy.
         * Returns [CAnnouncementAttachment|null $target, string|null $finalName, bool $isOverwrite].
         */
        $decideTarget = function (string $proposed, CAnnouncement $ann) use ($findExistingByName): array {
            $policy = (int) $this->file_option;

            $existing = $findExistingByName($ann, $proposed);
            if (!$existing) {
                return [null, $proposed, false];
            }

            if (\defined('FILE_SKIP') && FILE_SKIP === $policy) {
                return [null, null, false];
            }
            if (\defined('FILE_OVERWRITE') && FILE_OVERWRITE === $policy) {
                return [$existing, $proposed, true];
            }

            $pi = pathinfo($proposed);
            $base = $pi['filename'] ?? $proposed;
            $ext = isset($pi['extension']) && '' !== $pi['extension'] ? ('.'.$pi['extension']) : '';
            $i = 1;
            do {
                $candidate = $base.'_'.$i.$ext;
                $i++;
            } while ($findExistingByName($ann, $candidate));

            return [null, $candidate, false];
        };

        $createAttachment = function (string $filename, string $comment, int $size) use ($entity, $em) {
            $att = (new CAnnouncementAttachment())
                ->setFilename($filename)
                ->setPath(uniqid('announce_', true))
                ->setComment($comment)
                ->setSize($size)
                ->setAnnouncement($entity)
                ->setParent($entity)
                ->addCourseLink(
                    api_get_course_entity($this->destination_course_id),
                    api_get_session_entity(0),
                    api_get_group_entity()
                )
            ;
            $em->persist($att);
            $em->flush();

            return $att;
        };

        /**
         * Search helper: try a list of absolute paths, then recursive search in a base dir by filename.
         * Returns ['src'=>abs, 'filename'=>..., 'comment'=>..., 'size'=>int] or null.
         */
        $resolveSourceFile = function (array $candidates, array $fallbackDirs, string $filename) {
            // 1) direct candidates (absolute paths)
            foreach ($candidates as $meta) {
                if (!empty($meta['src']) && is_file($meta['src']) && is_readable($meta['src'])) {
                    $meta['filename'] = $meta['filename'] ?: basename($meta['src']);
                    $meta['size'] = (int) ($meta['size'] ?: (filesize($meta['src']) ?: 0));

                    return $meta;
                }
            }

            // 2) recursive search by filename inside fallback dirs
            $filename = trim($filename);
            if ('' !== $filename) {
                foreach ($fallbackDirs as $base) {
                    $base = rtrim($base, '/').'/';
                    if (!is_dir($base)) {
                        continue;
                    }
                    $it = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    foreach ($it as $f) {
                        if ($f->isFile() && $f->getFilename() === $filename) {
                            return [
                                'src' => $f->getRealPath(),
                                'filename' => $filename,
                                'comment' => (string) ($candidates[0]['comment'] ?? ''),
                                'size' => (int) ($candidates[0]['size'] ?? (filesize($f->getRealPath()) ?: 0)),
                            ];
                        }
                    }
                }
            }

            return null;
        };

        $storeBinaryFromPath = function (
            CAnnouncementAttachment $target,
            string $absPath
        ) use ($attachRepo): void {
            // This exists in your ResourceRepository
            $attachRepo->addFileFromPath($target, $target->getFilename(), $absPath, true);
        };

        // ---------------------- COPY MODE (course->course) ----------------------
        if ($copyMode) {
            $srcAttachmentIds = [];

            if (!empty($a->attachment_source_id)) {
                $srcAttachmentIds[] = (int) $a->attachment_source_id;
            }
            if (!empty($a->attachment_source_ids) && \is_array($a->attachment_source_ids)) {
                foreach ($a->attachment_source_ids as $sid) {
                    $sid = (int) $sid;
                    if ($sid > 0) {
                        $srcAttachmentIds[] = $sid;
                    }
                }
            }
            if (empty($srcAttachmentIds) && !empty($a->source_id)) {
                $srcAnn = Container::getAnnouncementRepository()->find((int) $a->source_id);
                if ($srcAnn) {
                    $srcAtts = Container::getAnnouncementAttachmentRepository()->findBy(['announcement' => $srcAnn]);
                    foreach ($srcAtts as $sa) {
                        $srcAttachmentIds[] = (int) $sa->getIid();
                    }
                }
            }

            if (empty($srcAttachmentIds)) {
                $this->dlog('restore_announcements: no source attachments found in COPY mode', [
                    'dst_announcement_id' => (int) $entity->getIid(),
                ]);

                return;
            }

            $attRepo = Container::getAnnouncementAttachmentRepository();

            foreach (array_unique($srcAttachmentIds) as $sid) {
                /** @var CAnnouncementAttachment|null $srcAtt */
                $srcAtt = $attRepo->find((int) $sid);
                if (!$srcAtt) {
                    continue;
                }

                $abs = $this->resourceFileAbsPathFromAnnouncementAttachment($srcAtt);
                if (!$abs) {
                    $this->dlog('restore_announcements: source attachment file not readable', ['src_att_id' => $sid]);

                    continue;
                }

                $proposed = $srcAtt->getFilename() ?: basename($abs);
                [$targetAttachment, $finalName, $isOverwrite] = $decideTarget($proposed, $entity);

                if (null === $finalName) {
                    $this->dlog('restore_announcements: skipped due to FILE_SKIP policy', [
                        'src_att_id' => $sid,
                        'filename' => $proposed,
                    ]);

                    continue;
                }

                if (null === $targetAttachment) {
                    $targetAttachment = $createAttachment(
                        $finalName,
                        (string) $srcAtt->getComment(),
                        (int) ($srcAtt->getSize() ?: (is_file($abs) ? filesize($abs) : 0))
                    );
                } else {
                    $targetAttachment
                        ->setComment((string) $srcAtt->getComment())
                        ->setSize((int) ($srcAtt->getSize() ?: (is_file($abs) ? filesize($abs) : 0)))
                    ;
                    $em->persist($targetAttachment);
                    $em->flush();
                }

                $storeBinaryFromPath($targetAttachment, $abs);

                $this->dlog('restore_announcements: attachment '.($isOverwrite ? 'overwritten' : 'copied').' from ResourceFile', [
                    'dst_announcement_id' => (int) $entity->getIid(),
                    'filename' => $targetAttachment->getFilename(),
                    'size' => $targetAttachment->getSize(),
                ]);
            }

            return;
        }

        $candidates = [];

        // Primary (from serialized record)
        if (!empty($a->attachment_path)) {
            $maybe = rtrim($originPath, '/').'/'.$a->attachment_path;
            $filename = (string) ($a->attachment_filename ?? '');
            if (is_file($maybe)) {
                $candidates[] = [
                    'src' => $maybe,
                    'filename' => '' !== $filename ? $filename : basename($maybe),
                    'comment' => (string) ($a->attachment_comment ?? ''),
                    'size' => (int) ($a->attachment_size ?? (filesize($maybe) ?: 0)),
                ];
            } elseif (is_dir($maybe)) {
                $try = '' !== $filename ? $maybe.'/'.$filename : '';
                if ('' !== $try && is_file($try)) {
                    $candidates[] = [
                        'src' => $try,
                        'filename' => $filename,
                        'comment' => (string) ($a->attachment_comment ?? ''),
                        'size' => (int) ($a->attachment_size ?? (filesize($try) ?: 0)),
                    ];
                } else {
                    $files = [];
                    foreach (new FilesystemIterator($maybe, FilesystemIterator::SKIP_DOTS) as $f) {
                        if ($f->isFile()) {
                            $files[] = $f->getRealPath();
                        }
                    }
                    if (1 === \count($files)) {
                        $one = $files[0];
                        $candidates[] = [
                            'src' => $one,
                            'filename' => '' !== $filename ? $filename : basename($one),
                            'comment' => (string) ($a->attachment_comment ?? ''),
                            'size' => (int) ($a->attachment_size ?? (filesize($one) ?: 0)),
                        ];
                    }
                }
            }
        }

        // Fallback DB snapshot
        if (!empty($this->course->orig)) {
            $table = Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
            $sql = 'SELECT path, comment, size, filename
                FROM '.$table.'
                WHERE c_id = '.$this->destination_course_id.'
                  AND announcement_id = '.(int) ($a->source_id ?? 0);
            $res = Database::query($sql);
            while ($row = Database::fetch_object($res)) {
                $base = rtrim($originPath, '/').'/'.$row->path;
                $abs = null;

                if (is_file($base)) {
                    $abs = $base;
                } elseif (is_dir($base)) {
                    $try = $base.'/'.$row->filename;
                    if (is_file($try)) {
                        $abs = $try;
                    } else {
                        $files = [];
                        foreach (new FilesystemIterator($base, FilesystemIterator::SKIP_DOTS) as $f) {
                            if ($f->isFile()) {
                                $files[] = $f->getRealPath();
                            }
                        }
                        if (1 === \count($files)) {
                            $abs = $files[0];
                        }
                    }
                }

                if ($abs && is_readable($abs)) {
                    $candidates[] = [
                        'src' => $abs,
                        'filename' => (string) $row->filename,
                        'comment' => (string) $row->comment,
                        'size' => (int) ($row->size ?: (filesize($abs) ?: 0)),
                    ];
                }
            }
        }

        $fallbackDirs = [
            rtrim($this->course->backup_path ?? '', '/').'/upload/announcements',
            rtrim($this->course->backup_path ?? '', '/').'/upload',
        ];

        $preferredFilename = (string) ($a->attachment_filename ?? '');
        if ('' === $preferredFilename && !empty($candidates)) {
            $preferredFilename = (string) ($candidates[0]['filename'] ?? '');
        }

        $resolved = $resolveSourceFile($candidates, $fallbackDirs, $preferredFilename);
        if (!$resolved) {
            $this->dlog('restore_announcements: no ZIP attachments could be resolved', [
                'dst_announcement_id' => (int) $entity->getIid(),
                'originPath' => $originPath,
                'hint' => 'Check upload/announcements and upload paths inside the package',
            ]);

            return;
        }

        $proposed = $resolved['filename'] ?: basename($resolved['src']);
        [$targetAttachment, $finalName, $isOverwrite] = $decideTarget($proposed, $entity);

        if (null === $finalName) {
            $this->dlog('restore_announcements: skipped due to FILE_SKIP policy (ZIP)', [
                'filename' => $proposed,
            ]);

            return;
        }

        if (null === $targetAttachment) {
            $targetAttachment = $createAttachment(
                $finalName,
                (string) $resolved['comment'],
                (int) $resolved['size']
            );
        } else {
            $targetAttachment
                ->setComment((string) $resolved['comment'])
                ->setSize((int) $resolved['size'])
            ;
            $em->persist($targetAttachment);
            $em->flush();
        }

        $storeBinaryFromPath($targetAttachment, $resolved['src']);

        $this->dlog('restore_announcements: attachment '.($isOverwrite ? 'overwritten' : 'stored (ZIP)'), [
            'announcement_id' => (int) $entity->getIid(),
            'filename' => $targetAttachment->getFilename(),
            'size' => $targetAttachment->getSize(),
            'src' => $resolved['src'],
        ]);
    }

    /**
     * Restore quizzes and their questions into the destination course.
     *
     * @param mixed $session_id
     * @param mixed $respect_base_content
     */
    public function restore_quizzes($session_id = 0, $respect_base_content = false): void
    {
        if (!$this->course->has_resources(RESOURCE_QUIZ)) {
            error_log('RESTORE_QUIZ: No quiz resources in backup.');

            return;
        }

        $em = Database::getManager();
        $resources = $this->course->resources;
        $courseEntity = api_get_course_entity($this->destination_course_id);
        $sessionEntity = !empty($session_id) ? api_get_session_entity((int) $session_id) : api_get_session_entity();

        // Safe wrapper around rewriteHtmlForCourse
        $rw = function (?string $html, string $dbgTag = 'QZ') use ($session_id) {
            if (null === $html || false === $html || '' === $html) {
                return '';
            }

            try {
                return $this->rewriteHtmlForCourse((string) $html, (int) $session_id, $dbgTag);
            } catch (Throwable $e) {
                error_log('RESTORE_QUIZ: rewriteHtmlForCourse failed: '.$e->getMessage());

                return (string) $html;
            }
        };

        // Backward compat alias for legacy key
        if (empty($this->course->resources[RESOURCE_QUIZQUESTION])
            && !empty($this->course->resources['Exercise_Question'])) {
            $this->course->resources[RESOURCE_QUIZQUESTION] = $this->course->resources['Exercise_Question'];
            $resources = $this->course->resources;
            error_log('RESTORE_QUIZ: Aliased Exercise_Question -> RESOURCE_QUIZQUESTION for restore.');
        }

        foreach ($resources[RESOURCE_QUIZ] as $id => $quizWrap) {
            if ((int) ($this->course->resources[RESOURCE_QUIZ][$id]->destination_id ?? 0) > 0) {
                $this->dlog('RESTORE_QUIZ: already mapped, skipping', ['src_quiz_id' => (int) $id]);
                continue;
            }
            $quiz = isset($quizWrap->obj) ? $quizWrap->obj : $quizWrap;

            // Rewrite HTML-bearing fields
            $description = $rw($quiz->description ?? '', 'QZ.desc');
            $textFinished = $rw($quiz->text_when_finished ?? '', 'QZ.done.ok');
            $textFinishedKo = $rw($quiz->text_when_finished_failure ?? '', 'QZ.done.ko');

            // Normalize dates
            $quiz->start_time = (property_exists($quiz, 'start_time') && '0000-00-00 00:00:00' !== $quiz->start_time)
                ? $quiz->start_time
                : null;
            $quiz->end_time = (property_exists($quiz, 'end_time') && '0000-00-00 00:00:00' !== $quiz->end_time)
                ? $quiz->end_time
                : null;

            global $_custom;
            if (!empty($_custom['exercises_clean_dates_when_restoring'])) {
                $quiz->start_time = null;
                $quiz->end_time = null;
            }

            if (-1 === (int) $id) {
                $this->course->resources[RESOURCE_QUIZ][$id]->destination_id = -1;
                error_log('RESTORE_QUIZ: Skipping virtual quiz (id=-1).');

                continue;
            }

            $entity = (new CQuiz())
                ->setParent($courseEntity)
                ->addCourseLink(
                    $courseEntity,
                    $respect_base_content ? $sessionEntity : (!empty($session_id) ? $sessionEntity : api_get_session_entity()),
                    api_get_group_entity()
                )
                ->setTitle((string) $quiz->title)
                ->setDescription($description)
                ->setType(isset($quiz->quiz_type) ? (int) $quiz->quiz_type : (int) $quiz->type)
                ->setRandom((int) $quiz->random)
                ->setRandomAnswers((bool) $quiz->random_answers)
                ->setResultsDisabled((int) $quiz->results_disabled)
                ->setMaxAttempt((int) $quiz->max_attempt)
                ->setFeedbackType((int) $quiz->feedback_type)
                ->setExpiredTime((int) $quiz->expired_time)
                ->setReviewAnswers((int) $quiz->review_answers)
                ->setRandomByCategory((int) $quiz->random_by_category)
                ->setTextWhenFinished($textFinished)
                ->setTextWhenFinishedFailure($textFinishedKo)
                ->setDisplayCategoryName((int) ($quiz->display_category_name ?? 0))
                ->setSaveCorrectAnswers(isset($quiz->save_correct_answers) ? (int) $quiz->save_correct_answers : 0)
                ->setPropagateNeg((int) $quiz->propagate_neg)
                ->setHideQuestionTitle((bool) ($quiz->hide_question_title ?? false))
                ->setHideQuestionNumber((int) ($quiz->hide_question_number ?? 0))
                ->setStartTime(!empty($quiz->start_time) ? new DateTime((string) $quiz->start_time) : null)
                ->setEndTime(!empty($quiz->end_time) ? new DateTime((string) $quiz->end_time) : null)
            ;

            if (isset($quiz->access_condition) && '' !== $quiz->access_condition) {
                $entity->setAccessCondition((string) $quiz->access_condition);
            }
            if (isset($quiz->pass_percentage) && '' !== $quiz->pass_percentage && null !== $quiz->pass_percentage) {
                $entity->setPassPercentage((int) $quiz->pass_percentage);
            }
            if (isset($quiz->question_selection_type) && '' !== $quiz->question_selection_type && null !== $quiz->question_selection_type) {
                $entity->setQuestionSelectionType((int) $quiz->question_selection_type);
            }
            if ('true' === api_get_setting('exercise.allow_notification_setting_per_exercise')) {
                $entity->setNotifications((string) ($quiz->notifications ?? ''));
            }

            $em->persist($entity);
            $em->flush();

            $newQuizId = (int) $entity->getIid();
            $this->course->resources[RESOURCE_QUIZ][$id]->destination_id = $newQuizId;

            $qCount = isset($quiz->question_ids) ? \count((array) $quiz->question_ids) : 0;
            error_log('RESTORE_QUIZ: Created quiz iid='.$newQuizId.' title="'.(string) $quiz->title.'" with '.$qCount.' question ids.');

            $order = 0;
            if (!empty($quiz->question_ids)) {
                foreach ($quiz->question_ids as $index => $question_id) {
                    $qid = $this->restore_quiz_question($question_id, (int) $session_id);
                    if (!$qid) {
                        error_log('RESTORE_QUIZ: restore_quiz_question returned 0 for src_question_id='.$question_id);

                        continue;
                    }

                    $question_order = !empty($quiz->question_orders[$index])
                        ? (int) $quiz->question_orders[$index]
                        : $order;

                    $order++;

                    $questionEntity = $em->getRepository(CQuizQuestion::class)->find($qid);
                    if (!$questionEntity) {
                        error_log('RESTORE_QUIZ: Question entity not found after insert. qid='.$qid);

                        continue;
                    }

                    $rel = (new CQuizRelQuestion())
                        ->setQuiz($entity)
                        ->setQuestion($questionEntity)
                        ->setQuestionOrder($question_order)
                    ;

                    $em->persist($rel);
                    $em->flush();
                }
            } else {
                error_log('RESTORE_QUIZ: No questions bound to quiz src_id='.$id.' (title="'.(string) $quiz->title.'").');
            }
        }
    }

    /**
     * Restore quiz-questions. Returns new question IID.
     *
     * @param mixed $id
     */
    public function restore_quiz_question($id, int $session_id = 0)
    {
        $em = Database::getManager();
        $resources = $this->course->resources;

        if (empty($resources[RESOURCE_QUIZQUESTION]) && !empty($resources['Exercise_Question'])) {
            $resources[RESOURCE_QUIZQUESTION] = $this->course->resources[RESOURCE_QUIZQUESTION]
                = $this->course->resources['Exercise_Question'];
            error_log('RESTORE_QUESTION: Aliased Exercise_Question -> RESOURCE_QUIZQUESTION for restore.');
        }

        /** @var object|null $question */
        $question = $resources[RESOURCE_QUIZQUESTION][$id] ?? null;
        if (!\is_object($question)) {
            error_log('RESTORE_QUESTION: Question not found in resources. src_id='.$id);

            return 0;
        }
        if (method_exists($question, 'is_restored') && $question->is_restored()) {
            return (int) $question->destination_id;
        }

        $courseEntity = api_get_course_entity($this->destination_course_id);

        // Safe wrapper around rewriteHtmlForCourse
        $rw = function (?string $html, string $dbgTag = 'QZ.Q') use ($session_id) {
            if (null === $html || false === $html || '' === $html) {
                return '';
            }

            try {
                return $this->rewriteHtmlForCourse((string) $html, (int) $session_id, $dbgTag);
            } catch (Throwable $e) {
                error_log('RESTORE_QUESTION: rewriteHtmlForCourse failed: '.$e->getMessage());

                return (string) $html;
            }
        };

        // Rewrite statement & description
        $question->description = $rw($question->description ?? '', 'QZ.Q.desc');
        $question->question = $rw($question->question ?? '', 'QZ.Q.text');

        // Picture mapping (kept as in your code)
        $imageNewId = '';
        if (!empty($question->picture)) {
            if (isset($resources[RESOURCE_DOCUMENT]['image_quiz'][$question->picture])) {
                $imageNewId = (string) $resources[RESOURCE_DOCUMENT]['image_quiz'][$question->picture]['destination_id'];
            } elseif (isset($resources[RESOURCE_DOCUMENT][$question->picture])) {
                $imageNewId = (string) $resources[RESOURCE_DOCUMENT][$question->picture]->destination_id;
            }
        }

        $qType = (int) ($question->quiz_type ?? $question->type);
        $entity = (new CQuizQuestion())
            ->setParent($courseEntity)
            ->addCourseLink($courseEntity, api_get_session_entity(), api_get_group_entity())
            ->setQuestion($question->question)
            ->setDescription($question->description)
            ->setPonderation((float) ($question->ponderation ?? 0))
            ->setPosition((int) ($question->position ?? 1))
            ->setType($qType)
            ->setPicture($imageNewId)
            ->setLevel((int) ($question->level ?? 1))
            ->setExtra((string) ($question->extra ?? ''))
        ;

        $em->persist($entity);
        $em->flush();

        $new_id = (int) $entity->getIid();
        if (!$new_id) {
            error_log('RESTORE_QUESTION: Failed to obtain new question iid for src_id='.$id);

            return 0;
        }

        $answers = (array) ($question->answers ?? []);
        error_log('RESTORE_QUESTION: Creating question src_id='.$id.' dst_iid='.$new_id.' answers_count='.\count($answers));

        $isMatchingFamily = \in_array($qType, [DRAGGABLE, MATCHING, MATCHING_DRAGGABLE], true);
        $correctMapSrcToDst = []; // dstAnsId => srcCorrectRef
        $allSrcAnswersById = []; // srcAnsId => text
        $dstAnswersByIdText = []; // dstAnsId => text

        if ($isMatchingFamily) {
            foreach ($answers as $a) {
                $allSrcAnswersById[$a['id']] = $rw($a['answer'] ?? '', 'QZ.Q.ans.all');
            }
        }

        foreach ($answers as $a) {
            $ansText = $rw($a['answer'] ?? '', 'QZ.Q.ans');
            $comment = $rw($a['comment'] ?? '', 'QZ.Q.ans.cmt');

            $ans = (new CQuizAnswer())
                ->setQuestion($entity)
                ->setAnswer((string) $ansText)
                ->setComment((string) $comment)
                ->setPonderation((float) ($a['ponderation'] ?? 0))
                ->setPosition((int) ($a['position'] ?? 0))
                ->setHotspotCoordinates(isset($a['hotspot_coordinates']) ? (string) $a['hotspot_coordinates'] : null)
                ->setHotspotType(isset($a['hotspot_type']) ? (string) $a['hotspot_type'] : null)
            ;

            if (isset($a['correct']) && '' !== $a['correct'] && null !== $a['correct']) {
                $ans->setCorrect((int) $a['correct']);
            }

            $em->persist($ans);
            $em->flush();

            if ($isMatchingFamily) {
                $correctMapSrcToDst[(int) $ans->getIid()] = $a['correct'] ?? null;
                $dstAnswersByIdText[(int) $ans->getIid()] = $ansText;
            }
        }

        if ($isMatchingFamily && $correctMapSrcToDst) {
            foreach ($entity->getAnswers() as $dstAns) {
                $dstAid = (int) $dstAns->getIid();
                $srcRef = $correctMapSrcToDst[$dstAid] ?? null;
                if (null === $srcRef) {
                    continue;
                }

                if (isset($allSrcAnswersById[$srcRef])) {
                    $needle = $allSrcAnswersById[$srcRef];
                    $newDst = null;
                    foreach ($dstAnswersByIdText as $candId => $txt) {
                        if ($txt === $needle) {
                            $newDst = $candId;

                            break;
                        }
                    }
                    if (null !== $newDst) {
                        $dstAns->setCorrect((int) $newDst);
                        $em->persist($dstAns);
                    }
                }
            }
            $em->flush();
        }

        if (\defined('MULTIPLE_ANSWER_TRUE_FALSE') && MULTIPLE_ANSWER_TRUE_FALSE === $qType) {
            $newOptByOld = [];
            if (isset($question->question_options) && is_iterable($question->question_options)) {
                foreach ($question->question_options as $optWrap) {
                    $opt = $optWrap->obj ?? $optWrap;
                    $optTitle = $rw($opt->name ?? '', 'QZ.Q.opt'); // rewrite option title too
                    $optEntity = (new CQuizQuestionOption())
                        ->setQuestion($entity)
                        ->setTitle((string) $optTitle)
                        ->setPosition((int) $opt->position)
                    ;
                    $em->persist($optEntity);
                    $em->flush();
                    $newOptByOld[$opt->id] = (int) $optEntity->getIid();
                }
                foreach ($entity->getAnswers() as $dstAns) {
                    $corr = $dstAns->getCorrect();
                    if (null !== $corr && isset($newOptByOld[$corr])) {
                        $dstAns->setCorrect((int) $newOptByOld[$corr]);
                        $em->persist($dstAns);
                    }
                }
                $em->flush();
            }
        }

        $this->course->resources[RESOURCE_QUIZQUESTION][$id]->destination_id = $new_id;

        return $new_id;
    }

    /**
     * Restore surveys from backup into the destination course.
     *
     * @param mixed $sessionId
     */
    public function restore_surveys($sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_SURVEY)) {
            $this->debug && error_log('COURSE_DEBUG: restore_surveys: no survey resources in backup.');

            return;
        }

        $em = Database::getManager();
        $surveyRepo = Container::getSurveyRepository();

        /** @var CourseEntity $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int) $sessionId) : null;

        $sid = (int) ($sessionEntity?->getId() ?? 0);

        $rewrite = function (?string $html, string $tag = '') use ($sid) {
            if (null === $html || '' === $html) {
                return '';
            }

            return $this->rewriteHtmlForCourse((string) $html, $sid, $tag);
        };

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_SURVEY] as $legacySurveyId => $surveyObj) {
            try {
                $code = (string) ($surveyObj->code ?? '');
                $lang = (string) ($surveyObj->lang ?? '');

                $title = $rewrite($surveyObj->title ?? '', ':survey.title');
                $subtitle = $rewrite($surveyObj->subtitle ?? '', ':survey.subtitle');
                $intro = $rewrite($surveyObj->intro ?? '', ':survey.intro');
                $surveyThanks = $rewrite($surveyObj->surveythanks ?? '', ':survey.thanks');

                $onePerPage = !empty($surveyObj->one_question_per_page);
                $shuffle = isset($surveyObj->shuffle) ? (bool) $surveyObj->shuffle : (!empty($surveyObj->suffle));
                $anonymous = (string) ((int) ($surveyObj->anonymous ?? 0));

                try {
                    $creationDate = !empty($surveyObj->creation_date) ? new DateTime((string) $surveyObj->creation_date) : new DateTime();
                } catch (Throwable) {
                    $creationDate = new DateTime();
                }

                try {
                    $availFrom = !empty($surveyObj->avail_from) ? new DateTime((string) $surveyObj->avail_from) : null;
                } catch (Throwable) {
                    $availFrom = null;
                }

                try {
                    $availTill = !empty($surveyObj->avail_till) ? new DateTime((string) $surveyObj->avail_till) : null;
                } catch (Throwable) {
                    $availTill = null;
                }

                $visibleResults = isset($surveyObj->visible_results) ? (int) $surveyObj->visible_results : null;
                $displayQuestionNumber = isset($surveyObj->display_question_number) ? (bool) $surveyObj->display_question_number : true;

                $existing = null;

                try {
                    if (method_exists($surveyRepo, 'findOneByCodeAndLangInCourse')) {
                        $existing = $surveyRepo->findOneByCodeAndLangInCourse($courseEntity, $code, $lang);
                    } else {
                        $existing = $surveyRepo->findOneBy(['code' => $code, 'lang' => $lang]);
                    }
                } catch (Throwable $e) {
                    $this->debug && error_log('COURSE_DEBUG: restore_surveys: duplicate check skipped: '.$e->getMessage());
                }

                if ($existing instanceof CSurvey) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            $this->course->resources[RESOURCE_SURVEY][$legacySurveyId]->destination_id = (int) $existing->getIid();
                            $this->debug && error_log("COURSE_DEBUG: restore_surveys: survey exists code='$code' (skip).");

                            continue 2;

                        case FILE_RENAME:
                            $base = '' !== $code ? $code.'_' : 'survey_';
                            $i = 1;
                            $try = $base.$i;
                            while (!$this->is_survey_code_available($try)) {
                                $try = $base.(++$i);
                            }
                            $code = $try;
                            $this->debug && error_log("COURSE_DEBUG: restore_surveys: renaming to '$code'.");

                            break;

                        case FILE_OVERWRITE:
                            SurveyManager::deleteSurvey($existing);
                            $em->flush();
                            $this->debug && error_log('COURSE_DEBUG: restore_surveys: existing survey deleted (overwrite).');

                            break;

                        default:
                            $this->course->resources[RESOURCE_SURVEY][$legacySurveyId]->destination_id = (int) $existing->getIid();

                            continue 2;
                    }
                }

                // --- Create survey ---
                $newSurvey = new CSurvey();
                $newSurvey
                    ->setCode($code)
                    ->setTitle($title)
                    ->setSubtitle($subtitle)
                    ->setLang($lang)
                    ->setAvailFrom($availFrom)
                    ->setAvailTill($availTill)
                    ->setIsShared((string) ($surveyObj->is_shared ?? '0'))
                    ->setTemplate((string) ($surveyObj->template ?? 'template'))
                    ->setIntro($intro)
                    ->setSurveythanks($surveyThanks)
                    ->setCreationDate($creationDate)
                    ->setInvited(0)
                    ->setAnswered(0)
                    ->setInviteMail((string) ($surveyObj->invite_mail ?? ''))
                    ->setReminderMail((string) ($surveyObj->reminder_mail ?? ''))
                    ->setOneQuestionPerPage($onePerPage)
                    ->setShuffle($shuffle)
                    ->setAnonymous($anonymous)
                    ->setDisplayQuestionNumber($displayQuestionNumber)
                ;

                if (method_exists($newSurvey, 'setParent')) {
                    $newSurvey->setParent($courseEntity);
                }
                if (method_exists($newSurvey, 'addCourseLink')) {
                    $newSurvey->addCourseLink($courseEntity, $sessionEntity);
                }

                if (method_exists($surveyRepo, 'create')) {
                    $surveyRepo->create($newSurvey);
                } else {
                    $em->persist($newSurvey);
                    $em->flush();
                }

                if (null !== $visibleResults && method_exists($newSurvey, 'setVisibleResults')) {
                    $newSurvey->setVisibleResults($visibleResults);
                    $em->flush();
                }

                $newId = (int) $newSurvey->getIid();
                $this->course->resources[RESOURCE_SURVEY][$legacySurveyId]->destination_id = $newId;

                // Restore questions
                $questionIds = \is_array($surveyObj->question_ids ?? null) ? $surveyObj->question_ids : [];
                if (empty($questionIds) && !empty($resources[RESOURCE_SURVEYQUESTION])) {
                    foreach ($resources[RESOURCE_SURVEYQUESTION] as $qid => $qWrap) {
                        $q = (isset($qWrap->obj) && \is_object($qWrap->obj)) ? $qWrap->obj : $qWrap;
                        if ((int) ($q->survey_id ?? 0) === (int) $legacySurveyId) {
                            $questionIds[] = (int) $qid;
                        }
                    }
                }

                foreach ($questionIds as $legacyQid) {
                    $this->restore_survey_question((int) $legacyQid, $newId, $sid);
                }

                $this->debug && error_log("COURSE_DEBUG: restore_surveys: created survey iid={$newId}, questions=".\count($questionIds));
            } catch (Throwable $e) {
                error_log('COURSE_DEBUG: restore_surveys: failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Restore survey-questions. $survey_id is the NEW iid.
     *
     * @param mixed $id
     * @param mixed $survey_id
     */
    public function restore_survey_question($id, $survey_id, ?int $sid = null)
    {
        $resources = $this->course->resources;
        $qWrap = $resources[RESOURCE_SURVEYQUESTION][$id] ?? null;

        if (!$qWrap || !\is_object($qWrap)) {
            $this->debug && error_log("COURSE_DEBUG: restore_survey_question: legacy question $id not found.");

            return 0;
        }
        if (method_exists($qWrap, 'is_restored') && $qWrap->is_restored()) {
            return $qWrap->destination_id;
        }

        $surveyRepo = Container::getSurveyRepository();
        $em = Database::getManager();

        $survey = $surveyRepo->find((int) $survey_id);
        if (!$survey instanceof CSurvey) {
            $this->debug && error_log("COURSE_DEBUG: restore_survey_question: target survey $survey_id not found.");

            return 0;
        }

        $sid = (int) ($sid ?? api_get_session_id());

        $rewrite = function (?string $html, string $tag = '') use ($sid) {
            if (null === $html || '' === $html) {
                return '';
            }

            return $this->rewriteHtmlForCourse((string) $html, $sid, $tag);
        };

        $q = (isset($qWrap->obj) && \is_object($qWrap->obj)) ? $qWrap->obj : $qWrap;

        $questionText = $rewrite($q->survey_question ?? '', ':survey.q');
        $commentText = $rewrite($q->survey_question_comment ?? '', ':survey.qc');

        try {
            $question = new CSurveyQuestion();
            $question
                ->setSurvey($survey)
                ->setSurveyQuestion($questionText)
                ->setSurveyQuestionComment($commentText)
                ->setType((string) ($q->survey_question_type ?? $q->type ?? 'open'))
                ->setDisplay((string) ($q->display ?? 'vertical'))
                ->setSort((int) ($q->sort ?? 0))
            ;

            if (isset($q->shared_question_id) && method_exists($question, 'setSharedQuestionId')) {
                $question->setSharedQuestionId((int) $q->shared_question_id);
            }
            if (isset($q->max_value) && method_exists($question, 'setMaxValue')) {
                $question->setMaxValue((int) $q->max_value);
            }
            if (isset($q->is_required)) {
                if (method_exists($question, 'setIsMandatory')) {
                    $question->setIsMandatory((bool) $q->is_required);
                } elseif (method_exists($question, 'setIsRequired')) {
                    $question->setIsRequired((bool) $q->is_required);
                }
            }

            $em->persist($question);
            $em->flush();

            // Options (value NOT NULL: default to 0 if missing)
            $answers = \is_array($q->answers ?? null) ? $q->answers : [];
            foreach ($answers as $idx => $answer) {
                $optText = $rewrite($answer['option_text'] ?? '', ':survey.opt');
                $value = isset($answer['value']) && null !== $answer['value'] ? (int) $answer['value'] : 0;
                $sort = (int) ($answer['sort'] ?? ($idx + 1));

                $opt = new CSurveyQuestionOption();
                $opt
                    ->setSurvey($survey)
                    ->setQuestion($question)
                    ->setOptionText($optText)
                    ->setSort($sort)
                    ->setValue($value)
                ;

                $em->persist($opt);
            }
            $em->flush();

            $this->course->resources[RESOURCE_SURVEYQUESTION][$id]->destination_id = (int) $question->getIid();

            return (int) $question->getIid();
        } catch (Throwable $e) {
            error_log('COURSE_DEBUG: restore_survey_question: failed: '.$e->getMessage());

            return 0;
        }
    }

    public function restore_learnpath_category(int $sessionId = 0, bool $baseContent = false): void
    {
        $reuseExisting = false;
        if (isset($this->tool_copy_settings['learnpath_category']['reuse_existing'])
            && true === $this->tool_copy_settings['learnpath_category']['reuse_existing']) {
            $reuseExisting = true;
        }

        if (!$this->course->has_resources(RESOURCE_LEARNPATH_CATEGORY)) {
            return;
        }

        $tblLpCategory = Database::get_course_table(TABLE_LP_CATEGORY);
        $resources = $this->course->resources;

        /** @var LearnPathCategory $item */
        foreach ($resources[RESOURCE_LEARNPATH_CATEGORY] as $id => $item) {
            /** @var CLpCategory|null $lpCategory */
            $lpCategory = $item->object;
            if (!$lpCategory) {
                continue;
            }

            $title = trim((string) $lpCategory->getTitle());
            if ('' === $title) {
                continue;
            }

            $categoryId = 0;

            $existing = Database::select(
                'iid',
                $tblLpCategory,
                [
                    'WHERE' => [
                        'c_id = ? AND name = ?' => [$this->destination_course_id, $title],
                    ],
                ],
                'first'
            );

            if ($reuseExisting && !empty($existing) && !empty($existing['iid'])) {
                $categoryId = (int) $existing['iid'];
            } else {
                $values = [
                    'c_id' => $this->destination_course_id,
                    'name' => $title,
                ];
                $categoryId = (int) learnpath::createCategory($values);
            }

            if ($categoryId > 0) {
                $this->course->resources[RESOURCE_LEARNPATH_CATEGORY][$id]->destination_id = $categoryId;
            }
        }
    }

    /**
     * Restore SCORM ZIPs under Documents (Learning paths) for traceability.
     * Accepts real zips and on-the-fly temporary ones (temp will be deleted after upload).
     */
    public function restore_scorm_documents(): void
    {
        $logp = 'RESTORE_SCORM_ZIP: ';

        $getBucket = function (string $type) {
            if (!empty($this->course->resources[$type]) && \is_array($this->course->resources[$type])) {
                return $this->course->resources[$type];
            }
            foreach ($this->course->resources ?? [] as $k => $v) {
                if (\is_string($k) && strtolower($k) === strtolower($type) && \is_array($v)) {
                    return $v;
                }
            }

            return [];
        };

        $docRepo = Container::getDocumentRepository();
        $em = Database::getManager();

        $courseInfo = $this->destination_course_info;
        if (empty($courseInfo) || empty($courseInfo['real_id'])) {
            error_log($logp.'missing courseInfo/real_id');

            return;
        }

        $courseEntity = api_get_course_entity((int) $courseInfo['real_id']);
        if (!$courseEntity) {
            error_log($logp.'api_get_course_entity failed');

            return;
        }

        $sid = property_exists($this, 'current_session_id') ? (int) $this->current_session_id : 0;
        $session = api_get_session_entity($sid);

        $entries = [];

        // A) direct SCORM bucket
        $scormBucket = $getBucket(RESOURCE_SCORM);
        foreach ($scormBucket as $sc) {
            $entries[] = $sc;
        }

        // B) also try LPs that are SCORM
        $lpBucket = $getBucket(RESOURCE_LEARNPATH);
        foreach ($lpBucket as $srcLpId => $lpObj) {
            $lpType = (int) ($lpObj->lp_type ?? $lpObj->type ?? 1);
            if (CLp::SCORM_TYPE === $lpType) {
                $entries[] = (object) [
                    'source_lp_id' => (int) $srcLpId,
                    'lp_id_dest' => (int) ($lpObj->destination_id ?? 0),
                ];
            }
        }

        error_log($logp.'entries='.\count($entries));
        if (empty($entries)) {
            return;
        }

        $lpTop = $docRepo->ensureLearningPathSystemFolder($courseEntity, $session);

        foreach ($entries as $sc) {
            // Locate package (zip or folder → temp zip)
            $srcLpId = (int) ($sc->source_lp_id ?? 0);
            $pkg = $this->findScormPackageForLp($srcLpId);
            if (empty($pkg['zip'])) {
                error_log($logp.'No package (zip/folder) found for a SCORM entry');

                continue;
            }
            $zipAbs = $pkg['zip'];
            $zipTemp = (bool) $pkg['temp'];

            // Map LP title/dest for folder name
            $lpId = 0;
            $lpTitle = 'Untitled';
            if (!empty($sc->lp_id_dest)) {
                $lpId = (int) $sc->lp_id_dest;
            } elseif ($srcLpId && !empty($lpBucket[$srcLpId]->destination_id)) {
                $lpId = (int) $lpBucket[$srcLpId]->destination_id;
            }
            $lpEntity = $lpId ? Container::getLpRepository()->find($lpId) : null;
            if ($lpEntity) {
                $lpTitle = $lpEntity->getTitle() ?: $lpTitle;
            }

            $cleanTitle = preg_replace('/\s+/', ' ', trim(str_replace(['/', '\\'], '-', (string) $lpTitle))) ?: 'Untitled';
            $folderTitleBase = \sprintf('SCORM - %d - %s', $lpId ?: 0, $cleanTitle);
            $folderTitle = $folderTitleBase;

            $exists = $docRepo->findChildNodeByTitle($lpTop, $folderTitle);
            if ($exists) {
                if (FILE_SKIP === $this->file_option) {
                    error_log($logp."Skip due to folder name collision: '$folderTitle'");
                    if ($zipTemp) {
                        @unlink($zipAbs);
                    }

                    continue;
                }
                if (FILE_RENAME === $this->file_option) {
                    $i = 1;
                    do {
                        $folderTitle = $folderTitleBase.' ('.$i.')';
                        $exists = $docRepo->findChildNodeByTitle($lpTop, $folderTitle);
                        $i++;
                    } while ($exists);
                }
                if (FILE_OVERWRITE === $this->file_option && $lpEntity) {
                    $docRepo->purgeScormZip($courseEntity, $lpEntity);
                    $em->flush();
                }
            }

            // Upload ZIP under Documents
            $uploaded = new UploadedFile(
                $zipAbs,
                basename($zipAbs),
                'application/zip',
                null,
                true
            );
            $lpFolder = $docRepo->ensureFolder(
                $courseEntity,
                $lpTop,
                $folderTitle,
                ResourceLink::VISIBILITY_DRAFT,
                $session
            );
            $docRepo->createFileInFolder(
                $courseEntity,
                $lpFolder,
                $uploaded,
                \sprintf('SCORM ZIP for LP #%d', $lpId),
                ResourceLink::VISIBILITY_DRAFT,
                $session
            );
            $em->flush();

            if ($zipTemp) {
                @unlink($zipAbs);
            }
            error_log($logp."ZIP stored under folder '$folderTitle'");
        }
    }

    /**
     * Restore learnpaths with minimal dependencies hydration and robust path resolution.
     *
     * @param mixed $session_id
     * @param mixed $respect_base_content
     * @param mixed $destination_course_code
     */
    public function restore_learnpaths($session_id = 0, $respect_base_content = false, $destination_course_code = ''): void
    {
        // 0) Ensure we have a resources snapshot (either internal or from the course)
        $this->ensureDepsBagsFromSnapshot();
        $all = $this->getAllResources(); // <- uses snapshot if available

        $docBag = $all[RESOURCE_DOCUMENT] ?? [];
        $quizBag = $all[RESOURCE_QUIZ] ?? [];
        $linkBag = $all[RESOURCE_LINK] ?? [];
        $survBag = $all[RESOURCE_SURVEY] ?? [];
        $workBag = $all[RESOURCE_WORK] ?? [];
        $forumB = $all['forum'] ?? [];

        $this->dlog('LP: deps (after ensure/snapshot)', [
            'document' => \count($docBag),
            'quiz' => \count($quizBag),
            'link' => \count($linkBag),
            'student_publication' => \count($workBag),
            'survey' => \count($survBag),
            'forum' => \count($forumB),
        ]);

        // Quick exit if no LPs selected
        $lpBag = $this->course->resources[RESOURCE_LEARNPATH] ?? [];
        if (empty($lpBag)) {
            $this->dlog('LP: nothing to restore (bag is empty).');

            return;
        }

        // Full snapshot to lookup deps without forcing user selection
        // Must be available BEFORE filtering in the import controller (controller already forwards it).
        $all = $this->getAllResources();

        // Map normalized resource types to bags (no extra validations)
        $type2bags = [
            'document' => ['document', RESOURCE_DOCUMENT],
            'quiz' => ['quiz', RESOURCE_QUIZ],
            'exercise' => ['quiz', RESOURCE_QUIZ],
            'link' => ['link', RESOURCE_LINK],
            'weblink' => ['link', RESOURCE_LINK],
            'url' => ['link', RESOURCE_LINK],
            'work' => ['works', RESOURCE_WORK],
            'student_publication' => ['works', RESOURCE_WORK],
            'survey' => ['survey', RESOURCE_SURVEY],
            'forum' => ['forum', 'forum'],
            // scorm/sco not handled here
        ];

        // ID collectors per dependency kind
        $need = [
            RESOURCE_DOCUMENT => [],
            RESOURCE_QUIZ => [],
            RESOURCE_LINK => [],
            RESOURCE_WORK => [],
            RESOURCE_SURVEY => [],
            'forum' => [],
        ];

        $takeId = static function ($v) {
            if (null === $v || '' === $v) {
                return null;
            }

            return ctype_digit((string) $v) ? (int) $v : null;
        };

        // Collect deps from LP items
        foreach ($lpBag as $srcLpId => $lpWrap) {
            $items = \is_array($lpWrap->items ?? null) ? $lpWrap->items : [];
            foreach ($items as $it) {
                $itype = strtolower((string) ($it['item_type'] ?? ''));
                $raw = $it['path'] ?? ($it['ref'] ?? ($it['identifierref'] ?? ''));
                $id = $takeId($raw);

                if (null === $id) {
                    continue;
                }
                if (!isset($type2bags[$itype])) {
                    continue;
                }

                [, $bag] = $type2bags[$itype];
                $need[$bag][$id] = true;
            }
        }

        // Collect deps from linked_resources (export helper)
        foreach ($lpBag as $srcLpId => $lpWrap) {
            $linked = \is_array($lpWrap->linked_resources ?? null) ? $lpWrap->linked_resources : [];
            foreach ($linked as $k => $ids) {
                // normalize key to a known bag with $type2bags
                $kk = strtolower($k);
                if (isset($type2bags[$kk])) {
                    [, $bag] = $type2bags[$kk];
                } else {
                    // sometimes exporter uses bag names directly (document/quiz/link/works/survey/forum)
                    $bag = $kk;
                }

                if (!isset($need[$bag])) {
                    continue;
                }
                if (!\is_array($ids)) {
                    continue;
                }

                foreach ($ids as $legacyId) {
                    $id = $takeId($legacyId);
                    if (null !== $id) {
                        $need[$bag][$id] = true;
                    }
                }
            }
        }

        // Build minimal bags from the snapshot using ONLY needed IDs
        $filterBag = static function (array $sourceBag, array $idSet): array {
            if (empty($idSet)) {
                return [];
            }
            $out = [];
            foreach ($idSet as $legacyId => $_) {
                if (isset($sourceBag[$legacyId])) {
                    $out[$legacyId] = $sourceBag[$legacyId];
                }
            }

            return $out;
        };

        // Inject minimal bags only if the selected set didn't include them.
        if (!isset($this->course->resources[RESOURCE_DOCUMENT])) {
            $src = $all[RESOURCE_DOCUMENT] ?? [];
            $this->course->resources[RESOURCE_DOCUMENT] = $filterBag($src, $need[RESOURCE_DOCUMENT]);
        }
        if (!isset($this->course->resources[RESOURCE_QUIZ])) {
            $src = $all[RESOURCE_QUIZ] ?? [];
            $this->course->resources[RESOURCE_QUIZ] = $filterBag($src, $need[RESOURCE_QUIZ]);
            if (!empty($this->course->resources[RESOURCE_QUIZ])
                && !isset($this->course->resources[RESOURCE_QUIZQUESTION])) {
                $this->course->resources[RESOURCE_QUIZQUESTION] =
                    $all[RESOURCE_QUIZQUESTION] ?? ($all['Exercise_Question'] ?? []);
            }
        }
        if (!isset($this->course->resources[RESOURCE_LINK])) {
            $src = $all[RESOURCE_LINK] ?? [];
            $this->course->resources[RESOURCE_LINK] = $filterBag($src, $need[RESOURCE_LINK]);
            if (!isset($this->course->resources[RESOURCE_LINKCATEGORY]) && isset($all[RESOURCE_LINKCATEGORY])) {
                $this->course->resources[RESOURCE_LINKCATEGORY] = $all[RESOURCE_LINKCATEGORY];
            }
        }
        if (!isset($this->course->resources[RESOURCE_WORK])) {
            $src = $all[RESOURCE_WORK] ?? [];
            $this->course->resources[RESOURCE_WORK] = $filterBag($src, $need[RESOURCE_WORK]);
        }
        if (!isset($this->course->resources[RESOURCE_SURVEY])) {
            $src = $all[RESOURCE_SURVEY] ?? [];
            $this->course->resources[RESOURCE_SURVEY] = $filterBag($src, $need[RESOURCE_SURVEY]);
            if (!isset($this->course->resources[RESOURCE_SURVEYQUESTION]) && isset($all[RESOURCE_SURVEYQUESTION])) {
                $this->course->resources[RESOURCE_SURVEYQUESTION] = $all[RESOURCE_SURVEYQUESTION];
            }
        }
        if (!isset($this->course->resources['forum'])) {
            $src = $all['forum'] ?? [];
            $this->course->resources['forum'] = $filterBag($src, $need['forum']);
            // minimal forum support if LP points to forums
            if (!empty($this->course->resources['forum'])) {
                foreach (['Forum_Category', 'thread', 'post'] as $k) {
                    if (!isset($this->course->resources[$k]) && isset($all[$k])) {
                        $this->course->resources[$k] = $all[$k];
                    }
                }
            }
        }

        $this->dlog('LP: minimal deps prepared', [
            'document' => \count($this->course->resources[RESOURCE_DOCUMENT] ?? []),
            'quiz' => \count($this->course->resources[RESOURCE_QUIZ] ?? []),
            'link' => \count($this->course->resources[RESOURCE_LINK] ?? []),
            'student_publication' => \count($this->course->resources[RESOURCE_WORK] ?? []),
            'survey' => \count($this->course->resources[RESOURCE_SURVEY] ?? []),
            'forum' => \count($this->course->resources['forum'] ?? []),
        ]);

        // --- 3) Restore ONLY those minimal bags ---
        if (!empty($this->course->resources[RESOURCE_DOCUMENT])) {
            $this->restore_documents($session_id, false, $destination_course_code);
        }
        if (!empty($this->course->resources[RESOURCE_QUIZ])) {
            $this->restore_quizzes($session_id, false);
        }
        if (!empty($this->course->resources[RESOURCE_LINK])) {
            $this->restore_links($session_id);
        }
        if (!empty($this->course->resources[RESOURCE_WORK])) {
            $this->restore_works($session_id);
        }
        if (!empty($this->course->resources[RESOURCE_SURVEY])) {
            $this->restore_surveys($session_id);
        }
        if (!empty($this->course->resources['forum'])) {
            $this->restore_forums($session_id);
        }

        // --- 4) Create LP + items with resolved paths to new destination iids ---
        $em = Database::getManager();
        $courseEnt = api_get_course_entity($this->destination_course_id);
        $sessionEnt = api_get_session_entity((int) $session_id);
        $lpRepo = Container::getLpRepository();
        $lpItemRepo = Container::getLpItemRepository();
        $docRepo = Container::getDocumentRepository();

        // Optional repos for title fallbacks
        $quizRepo = method_exists(Container::class, 'getQuizRepository') ? Container::getQuizRepository() : null;
        $linkRepo = method_exists(Container::class, 'getLinkRepository') ? Container::getLinkRepository() : null;
        $forumRepo = method_exists(Container::class, 'getForumRepository') ? Container::getForumRepository() : null;
        $surveyRepo = method_exists(Container::class, 'getSurveyRepository') ? Container::getSurveyRepository() : null;
        $workRepo = method_exists(Container::class, 'getStudentPublicationRepository') ? Container::getStudentPublicationRepository() : null;

        $getDst = function (string $bag, $legacyId): int {
            $wrap = $this->course->resources[$bag][$legacyId] ?? null;

            return $wrap && isset($wrap->destination_id) ? (int) $wrap->destination_id : 0;
        };

        $findDocIidByTitle = function (string $title) use ($docRepo, $courseEnt, $sessionEnt): int {
            if ('' === $title) {
                return 0;
            }

            try {
                $hit = $docRepo->findCourseResourceByTitle(
                    $title,
                    $courseEnt->getResourceNode(),
                    $courseEnt,
                    $sessionEnt,
                    api_get_group_entity()
                );

                return $hit && method_exists($hit, 'getIid') ? (int) $hit->getIid() : 0;
            } catch (Throwable $e) {
                $this->dlog('LP: doc title lookup failed', ['title' => $title, 'err' => $e->getMessage()]);

                return 0;
            }
        };

        // Generic title finders (defensive: method_exists checks)
        $findByTitle = [
            'quiz' => function (string $title) use ($quizRepo, $courseEnt, $sessionEnt): int {
                if (!$quizRepo || '' === $title) {
                    return 0;
                }

                try {
                    $hit = method_exists($quizRepo, 'findOneByTitleInCourse')
                        ? $quizRepo->findOneByTitleInCourse($title, $courseEnt, $sessionEnt)
                        : $quizRepo->findOneBy(['title' => $title]);

                    return $hit && method_exists($hit, 'getIid') ? (int) $hit->getIid() : 0;
                } catch (Throwable $e) {
                    return 0;
                }
            },
            'link' => function (string $title) use ($linkRepo, $courseEnt): int {
                if (!$linkRepo || '' === $title) {
                    return 0;
                }

                try {
                    $hit = method_exists($linkRepo, 'findOneByTitleInCourse')
                        ? $linkRepo->findOneByTitleInCourse($title, $courseEnt, null)
                        : $linkRepo->findOneBy(['title' => $title]);

                    return $hit && method_exists($hit, 'getIid') ? (int) $hit->getIid() : 0;
                } catch (Throwable $e) {
                    return 0;
                }
            },
            'forum' => function (string $title) use ($forumRepo, $courseEnt): int {
                if (!$forumRepo || '' === $title) {
                    return 0;
                }

                try {
                    $hit = method_exists($forumRepo, 'findOneByTitleInCourse')
                        ? $forumRepo->findOneByTitleInCourse($title, $courseEnt, null)
                        : $forumRepo->findOneBy(['forum_title' => $title]) ?? $forumRepo->findOneBy(['title' => $title]);

                    return $hit && method_exists($hit, 'getIid') ? (int) $hit->getIid() : 0;
                } catch (Throwable $e) {
                    return 0;
                }
            },
            'survey' => function (string $title) use ($surveyRepo, $courseEnt): int {
                if (!$surveyRepo || '' === $title) {
                    return 0;
                }

                try {
                    $hit = method_exists($surveyRepo, 'findOneByTitleInCourse')
                        ? $surveyRepo->findOneByTitleInCourse($title, $courseEnt, null)
                        : $surveyRepo->findOneBy(['title' => $title]);

                    return $hit && method_exists($hit, 'getIid') ? (int) $hit->getIid() : 0;
                } catch (Throwable $e) {
                    return 0;
                }
            },
            'work' => function (string $title) use ($workRepo, $courseEnt): int {
                if (!$workRepo || '' === $title) {
                    return 0;
                }

                try {
                    $hit = method_exists($workRepo, 'findOneByTitleInCourse')
                        ? $workRepo->findOneByTitleInCourse($title, $courseEnt, null)
                        : $workRepo->findOneBy(['title' => $title]);

                    return $hit && method_exists($hit, 'getIid') ? (int) $hit->getIid() : 0;
                } catch (Throwable $e) {
                    return 0;
                }
            },
        ];

        $resolvePath = function (array $it) use ($getDst, $findDocIidByTitle, $findByTitle): string {
            $itype = strtolower((string) ($it['item_type'] ?? ''));
            $raw = $it['path'] ?? ($it['ref'] ?? ($it['identifierref'] ?? ''));
            $title = trim((string) ($it['title'] ?? ''));

            switch ($itype) {
                case 'document':
                    if (ctype_digit((string) $raw)) {
                        $nid = $getDst(RESOURCE_DOCUMENT, (int) $raw);

                        return $nid ? (string) $nid : '';
                    }
                    if (\is_string($raw) && str_starts_with((string) $raw, 'document/')) {
                        return (string) $raw;
                    }
                    $maybe = $findDocIidByTitle('' !== $title ? $title : (string) $raw);

                    return $maybe ? (string) $maybe : '';

                case 'quiz':
                case 'exercise':
                    $id = ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst(RESOURCE_QUIZ, $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['quiz']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                case 'link':
                case 'weblink':
                case 'url':
                    $id = ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst(RESOURCE_LINK, $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['link']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                case 'work':
                case 'student_publication':
                    $id = ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst(RESOURCE_WORK, $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['work']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                case 'survey':
                    $id = ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst(RESOURCE_SURVEY, $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['survey']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                case 'forum':
                    $id = ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst('forum', $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['forum']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                default:
                    // keep whatever was exported
                    return (string) $raw;
            }
        };

        foreach ($lpBag as $srcLpId => $lpWrap) {
            $title = (string) ($lpWrap->name ?? $lpWrap->title ?? ('LP '.$srcLpId));
            $desc = (string) ($lpWrap->description ?? '');
            $lpType = (int) ($lpWrap->lp_type ?? $lpWrap->type ?? 1);

            $lp = (new CLp())
                ->setLpType($lpType)
                ->setTitle($title)
                ->setParent($courseEnt)
            ;

            if (method_exists($lp, 'addCourseLink')) {
                $lp->addCourseLink($courseEnt, $sessionEnt);
            }
            if (method_exists($lp, 'setDescription')) {
                $lp->setDescription($desc);
            }

            $lpRepo->createLp($lp);
            $em->flush();

            $this->course->resources[RESOURCE_LEARNPATH][$srcLpId]->destination_id = (int) $lp->getIid();

            $root = $lpItemRepo->getRootItem($lp->getIid());
            $parents = [0 => $root];
            $items = \is_array($lpWrap->items ?? null) ? $lpWrap->items : [];
            $order = 0;

            foreach ($items as $it) {
                $lvl = (int) ($it['level'] ?? 0);
                $pItem = $parents[$lvl] ?? $root;

                $itype = (string) ($it['item_type'] ?? 'dir');
                $itTitle = (string) ($it['title'] ?? '');
                $path = $resolvePath($it);

                $item = (new CLpItem())
                    ->setLp($lp)
                    ->setParent($pItem)
                    ->setItemType($itype)
                    ->setTitle($itTitle)
                    ->setPath($path)
                    ->setRef((string) ($it['identifier'] ?? ''))
                    ->setDisplayOrder(++$order)
                ;

                if (isset($it['parameters'])) {
                    $item->setParameters((string) $it['parameters']);
                }
                if (isset($it['prerequisite'])) {
                    $item->setPrerequisite((string) $it['prerequisite']);
                }
                if (isset($it['launch_data'])) {
                    $item->setLaunchData((string) $it['launch_data']);
                }

                $lpItemRepo->create($item);
                $parents[$lvl + 1] = $item;
            }

            $em->flush();

            $this->dlog('LP: items created', [
                'lp_iid' => (int) $lp->getIid(),
                'items' => $order,
                'title' => $title,
            ]);
        }
    }

    /**
     * Restore Glossary resources for the destination course.
     *
     * @param mixed $sessionId
     */
    public function restore_glossary($sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_GLOSSARY)) {
            $this->debug && error_log('COURSE_DEBUG: restore_glossary: no glossary resources in backup.');

            return;
        }

        $em = Database::getManager();

        /** @var CGlossaryRepository $repo */
        $repo = $em->getRepository(CGlossary::class);

        /** @var CourseEntity $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int) $sessionId) : null;

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_GLOSSARY] as $legacyId => $gls) {
            try {
                $title = (string) ($gls->name ?? $gls->title ?? '');
                $desc = (string) ($gls->description ?? '');
                $order = (int) ($gls->display_order ?? 0);

                // Normalize title
                if ('' === $title) {
                    $title = 'Glossary term';
                }

                // HTML rewrite (always)
                $desc = $this->rewriteHtmlForCourse($desc, (int) $sessionId, '[glossary.term]');

                // Look up existing by title in this course + (optional) session
                if (method_exists($repo, 'getResourcesByCourse')) {
                    $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity)
                        ->andWhere('resource.title = :title')->setParameter('title', $title)
                        ->setMaxResults(1)
                    ;
                    $existing = $qb->getQuery()->getOneOrNullResult();
                } else {
                    $existing = $repo->findOneBy(['title' => $title]);
                }

                if ($existing instanceof CGlossary) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            $this->course->resources[RESOURCE_GLOSSARY][$legacyId] ??= new stdClass();
                            $this->course->resources[RESOURCE_GLOSSARY][$legacyId]->destination_id = (int) $existing->getIid();
                            $this->debug && error_log("COURSE_DEBUG: restore_glossary: exists title='{$title}' (skip).");

                            continue 2;

                        case FILE_RENAME:
                            // Generate a unique title inside the course/session
                            $base = $title;
                            $try = $base;
                            $i = 1;
                            $isTaken = static function ($repo, $courseEntity, $sessionEntity, $titleTry) {
                                if (method_exists($repo, 'getResourcesByCourse')) {
                                    $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity)
                                        ->andWhere('resource.title = :t')->setParameter('t', $titleTry)
                                        ->setMaxResults(1)
                                    ;

                                    return (bool) $qb->getQuery()->getOneOrNullResult();
                                }

                                return (bool) $repo->findOneBy(['title' => $titleTry]);
                            };
                            while ($isTaken($repo, $courseEntity, $sessionEntity, $try)) {
                                $try = $base.' ('.($i++).')';
                            }
                            $title = $try;
                            $this->debug && error_log("COURSE_DEBUG: restore_glossary: renaming to '{$title}'.");

                            break;

                        case FILE_OVERWRITE:
                            $em->remove($existing);
                            $em->flush();
                            $this->debug && error_log('COURSE_DEBUG: restore_glossary: existing term deleted (overwrite).');

                            break;

                        default:
                            $this->course->resources[RESOURCE_GLOSSARY][$legacyId] ??= new stdClass();
                            $this->course->resources[RESOURCE_GLOSSARY][$legacyId]->destination_id = (int) $existing->getIid();

                            continue 2;
                    }
                }

                // Create
                $entity = (new CGlossary())
                    ->setTitle($title)
                    ->setDescription($desc)
                ;

                if (method_exists($entity, 'setParent')) {
                    $entity->setParent($courseEntity);
                }
                if (method_exists($entity, 'addCourseLink')) {
                    $entity->addCourseLink($courseEntity, $sessionEntity);
                }

                if (method_exists($repo, 'create')) {
                    $repo->create($entity);
                } else {
                    $em->persist($entity);
                    $em->flush();
                }

                if ($order && method_exists($entity, 'setDisplayOrder')) {
                    $entity->setDisplayOrder($order);
                    $em->flush();
                }

                $newId = (int) $entity->getIid();
                $this->course->resources[RESOURCE_GLOSSARY][$legacyId] ??= new stdClass();
                $this->course->resources[RESOURCE_GLOSSARY][$legacyId]->destination_id = $newId;

                $this->debug && error_log("COURSE_DEBUG: restore_glossary: created term iid={$newId}, title='{$title}'");
            } catch (Throwable $e) {
                error_log('COURSE_DEBUG: restore_glossary: failed: '.$e->getMessage());

                continue;
            }
        }
    }

    /**
     * Restore Wiki resources for the destination course.
     *
     * @param mixed $sessionId
     */
    public function restore_wiki($sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_WIKI)) {
            $this->debug && error_log('COURSE_DEBUG: restore_wiki: no wiki resources in backup.');

            return;
        }

        $em = Database::getManager();

        /** @var CWikiRepository $repo */
        $repo = $em->getRepository(CWiki::class);

        /** @var CourseEntity $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int) $sessionId) : null;

        $cid = (int) $this->destination_course_id;
        $sid = (int) ($sessionEntity?->getId() ?? 0);

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_WIKI] as $legacyId => $w) {
            try {
                $rawTitle = (string) ($w->title ?? $w->name ?? '');
                $reflink = (string) ($w->reflink ?? '');
                $content = (string) ($w->content ?? '');
                $comment = (string) ($w->comment ?? '');
                $progress = (string) ($w->progress ?? '');
                $version = (int) ($w->version ?? 1);
                $groupId = (int) ($w->group_id ?? 0);
                $userId = (int) ($w->user_id ?? api_get_user_id());

                // HTML rewrite
                $content = $this->rewriteHtmlForCourse($content, (int) $sessionId, '[wiki.page]');

                if ('' === $rawTitle) {
                    $rawTitle = 'Wiki page';
                }
                if ('' === $content) {
                    $content = '<p>&nbsp;</p>';
                }

                // slug maker
                $makeSlug = static function (string $s): string {
                    $s = strtolower(trim($s));
                    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s) ?: '';
                    $s = trim($s, '-');

                    return '' === $s ? 'page' : $s;
                };
                $reflink = '' !== $reflink ? $makeSlug($reflink) : $makeSlug($rawTitle);

                // existence check
                $qbExists = $repo->createQueryBuilder('w')
                    ->select('w.iid')
                    ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                    ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                    ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId)
                ;
                if ($sid > 0) {
                    $qbExists->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                } else {
                    $qbExists->andWhere('COALESCE(w.sessionId,0) = 0');
                }

                $exists = (bool) $qbExists->getQuery()->getOneOrNullResult();

                if ($exists) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            // map to latest page id
                            $qbLast = $repo->createQueryBuilder('w')
                                ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                                ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId)
                                ->orderBy('w.version', 'DESC')->setMaxResults(1)
                            ;
                            if ($sid > 0) {
                                $qbLast->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                            } else {
                                $qbLast->andWhere('COALESCE(w.sessionId,0) = 0');
                            }

                            /** @var CWiki|null $last */
                            $last = $qbLast->getQuery()->getOneOrNullResult();
                            $dest = $last ? (int) ($last->getPageId() ?: $last->getIid()) : 0;

                            $this->course->resources[RESOURCE_WIKI][$legacyId] ??= new stdClass();
                            $this->course->resources[RESOURCE_WIKI][$legacyId]->destination_id = $dest;

                            $this->debug && error_log("COURSE_DEBUG: restore_wiki: exists → skip (page_id={$dest}).");

                            continue 2;

                        case FILE_RENAME:
                            $baseSlug = $reflink;
                            $baseTitle = $rawTitle;
                            $i = 1;
                            $trySlug = $baseSlug.'-'.$i;
                            $isTaken = function (string $slug) use ($repo, $cid, $sid, $groupId): bool {
                                $qb = $repo->createQueryBuilder('w')
                                    ->select('w.iid')
                                    ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                                    ->andWhere('w.reflink = :r')->setParameter('r', $slug)
                                    ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId)
                                ;
                                if ($sid > 0) {
                                    $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                                } else {
                                    $qb->andWhere('COALESCE(w.sessionId,0) = 0');
                                }
                                $qb->setMaxResults(1);

                                return (bool) $qb->getQuery()->getOneOrNullResult();
                            };
                            while ($isTaken($trySlug)) {
                                $trySlug = $baseSlug.'-'.(++$i);
                            }
                            $reflink = $trySlug;
                            $rawTitle = $baseTitle.' ('.$i.')';
                            $this->debug && error_log("COURSE_DEBUG: restore_wiki: renamed to '{$reflink}' / '{$rawTitle}'.");

                            break;

                        case FILE_OVERWRITE:
                            $qbAll = $repo->createQueryBuilder('w')
                                ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                                ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId)
                            ;
                            if ($sid > 0) {
                                $qbAll->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                            } else {
                                $qbAll->andWhere('COALESCE(w.sessionId,0) = 0');
                            }
                            foreach ($qbAll->getQuery()->getResult() as $old) {
                                $em->remove($old);
                            }
                            $em->flush();
                            $this->debug && error_log('COURSE_DEBUG: restore_wiki: removed old pages (overwrite).');

                            break;

                        default:
                            $this->debug && error_log('COURSE_DEBUG: restore_wiki: unknown file_option → skip.');

                            continue 2;
                    }
                }

                // Create new page (one version)
                $wiki = new CWiki();
                $wiki->setCId($cid);
                $wiki->setSessionId($sid);
                $wiki->setGroupId($groupId);
                $wiki->setReflink($reflink);
                $wiki->setTitle($rawTitle);
                $wiki->setContent($content);  // already rewritten
                $wiki->setComment($comment);
                $wiki->setProgress($progress);
                $wiki->setVersion($version > 0 ? $version : 1);
                $wiki->setUserId($userId);

                // timestamps
                try {
                    $dtimeStr = (string) ($w->dtime ?? '');
                    $wiki->setDtime('' !== $dtimeStr ? new DateTime($dtimeStr) : new DateTime('now', new DateTimeZone('UTC')));
                } catch (Throwable) {
                    $wiki->setDtime(new DateTime('now', new DateTimeZone('UTC')));
                }

                $wiki->setIsEditing(0);
                $wiki->setTimeEdit(null);
                $wiki->setHits((int) ($w->hits ?? 0));
                $wiki->setAddlock((int) ($w->addlock ?? 1));
                $wiki->setEditlock((int) ($w->editlock ?? 0));
                $wiki->setVisibility((int) ($w->visibility ?? 1));
                $wiki->setAddlockDisc((int) ($w->addlock_disc ?? 1));
                $wiki->setVisibilityDisc((int) ($w->visibility_disc ?? 1));
                $wiki->setRatinglockDisc((int) ($w->ratinglock_disc ?? 1));
                $wiki->setAssignment((int) ($w->assignment ?? 0));
                $wiki->setScore(isset($w->score) ? (int) $w->score : 0);
                $wiki->setLinksto((string) ($w->linksto ?? ''));
                $wiki->setTag((string) ($w->tag ?? ''));
                $wiki->setUserIp((string) ($w->user_ip ?? api_get_real_ip()));

                if (method_exists($wiki, 'setParent')) {
                    $wiki->setParent($courseEntity);
                }
                if (method_exists($wiki, 'setCreator')) {
                    $wiki->setCreator(api_get_user_entity());
                }
                $groupEntity = $groupId ? api_get_group_entity($groupId) : null;
                if (method_exists($wiki, 'addCourseLink')) {
                    $wiki->addCourseLink($courseEntity, $sessionEntity, $groupEntity);
                }

                $em->persist($wiki);
                $em->flush();

                // Page id
                if (empty($w->page_id)) {
                    $wiki->setPageId((int) $wiki->getIid());
                } else {
                    $pid = (int) $w->page_id;
                    $wiki->setPageId($pid > 0 ? $pid : (int) $wiki->getIid());
                }
                $em->flush();

                // Conf row
                $conf = new CWikiConf();
                $conf->setCId($cid);
                $conf->setPageId((int) $wiki->getPageId());
                $conf->setTask((string) ($w->task ?? ''));
                $conf->setFeedback1((string) ($w->feedback1 ?? ''));
                $conf->setFeedback2((string) ($w->feedback2 ?? ''));
                $conf->setFeedback3((string) ($w->feedback3 ?? ''));
                $conf->setFprogress1((string) ($w->fprogress1 ?? ''));
                $conf->setFprogress2((string) ($w->fprogress2 ?? ''));
                $conf->setFprogress3((string) ($w->fprogress3 ?? ''));
                $conf->setMaxText(isset($w->max_text) ? (int) $w->max_text : 0);
                $conf->setMaxVersion(isset($w->max_version) ? (int) $w->max_version : 0);

                try {
                    $conf->setStartdateAssig(!empty($w->startdate_assig) ? new DateTime((string) $w->startdate_assig) : null);
                } catch (Throwable) {
                    $conf->setStartdateAssig(null);
                }

                try {
                    $conf->setEnddateAssig(!empty($w->enddate_assig) ? new DateTime((string) $w->enddate_assig) : null);
                } catch (Throwable) {
                    $conf->setEnddateAssig(null);
                }
                $conf->setDelayedsubmit(isset($w->delayedsubmit) ? (int) $w->delayedsubmit : 0);

                $em->persist($conf);
                $em->flush();

                $this->course->resources[RESOURCE_WIKI][$legacyId] ??= new stdClass();
                $this->course->resources[RESOURCE_WIKI][$legacyId]->destination_id = (int) $wiki->getPageId();

                $this->debug && error_log('COURSE_DEBUG: restore_wiki: created page iid='.(int) $wiki->getIid().' page_id='.(int) $wiki->getPageId()." reflink='{$reflink}'");
            } catch (Throwable $e) {
                error_log('COURSE_DEBUG: restore_wiki: failed: '.$e->getMessage());

                continue;
            }
        }
    }

    /**
     * Restore "Thematic" resources for the destination course.
     *
     * @param mixed $sessionId
     */
    public function restore_thematic($sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_THEMATIC)) {
            $this->debug && error_log('COURSE_DEBUG: restore_thematic: no thematic resources.');

            return;
        }

        $em = Database::getManager();

        /** @var CourseEntity $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int) $sessionId) : null;

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_THEMATIC] as $legacyId => $t) {
            try {
                $p = (array) ($t->params ?? []);

                $title = trim((string) ($p['title'] ?? $p['name'] ?? ''));
                $content = (string) ($p['content'] ?? '');
                $active = (bool) ($p['active'] ?? true);

                if ('' === $title) {
                    $title = 'Thematic';
                }

                // Rewrite embedded HTML so referenced files/images are valid in the new course
                $content = $this->rewriteHtmlForCourse($content, (int) $sessionId, '[thematic.main]');

                // Create Thematic root
                $thematic = (new CThematic())
                    ->setTitle($title)
                    ->setContent($content)
                    ->setActive($active)
                ;

                // Set ownership and course linkage if available
                if (method_exists($thematic, 'setParent')) {
                    $thematic->setParent($courseEntity);
                }
                if (method_exists($thematic, 'setCreator')) {
                    $thematic->setCreator(api_get_user_entity());
                }
                if (method_exists($thematic, 'addCourseLink')) {
                    $thematic->addCourseLink($courseEntity, $sessionEntity);
                }

                $em->persist($thematic);
                $em->flush();

                // Map new IID back to resources
                $this->course->resources[RESOURCE_THEMATIC][$legacyId] ??= new stdClass();
                $this->course->resources[RESOURCE_THEMATIC][$legacyId]->destination_id = (int) $thematic->getIid();

                // Restore "advances" (timeline slots)
                $advList = (array) ($t->thematic_advance_list ?? []);
                foreach ($advList as $adv) {
                    if (!\is_array($adv)) {
                        $adv = (array) $adv;
                    }

                    $advContent = (string) ($adv['content'] ?? '');
                    // Rewrite HTML inside advance content
                    $advContent = $this->rewriteHtmlForCourse($advContent, (int) $sessionId, '[thematic.advance]');

                    $rawStart = (string) ($adv['start_date'] ?? $adv['startDate'] ?? '');

                    try {
                        $startDate = '' !== $rawStart ? new DateTime($rawStart) : new DateTime('now', new DateTimeZone('UTC'));
                    } catch (Throwable) {
                        $startDate = new DateTime('now', new DateTimeZone('UTC'));
                    }

                    $duration = (int) ($adv['duration'] ?? 1);
                    $doneAdvance = (bool) ($adv['done_advance'] ?? $adv['doneAdvance'] ?? false);

                    $advance = (new CThematicAdvance())
                        ->setThematic($thematic)
                        ->setContent($advContent)
                        ->setStartDate($startDate)
                        ->setDuration($duration)
                        ->setDoneAdvance($doneAdvance)
                    ;

                    // Optional links to attendance/room if present
                    $attId = (int) ($adv['attendance_id'] ?? 0);
                    if ($attId > 0) {
                        $att = $em->getRepository(CAttendance::class)->find($attId);
                        if ($att) {
                            $advance->setAttendance($att);
                        }
                    }
                    $roomId = (int) ($adv['room_id'] ?? 0);
                    if ($roomId > 0) {
                        $room = $em->getRepository(Room::class)->find($roomId);
                        if ($room) {
                            $advance->setRoom($room);
                        }
                    }

                    $em->persist($advance);
                }

                // Restore "plans" (structured descriptions)
                $planList = (array) ($t->thematic_plan_list ?? []);
                foreach ($planList as $pl) {
                    if (!\is_array($pl)) {
                        $pl = (array) $pl;
                    }

                    $plTitle = trim((string) ($pl['title'] ?? ''));
                    if ('' === $plTitle) {
                        $plTitle = 'Plan';
                    }

                    $plDesc = (string) ($pl['description'] ?? '');
                    // Rewrite HTML inside plan description
                    $plDesc = $this->rewriteHtmlForCourse($plDesc, (int) $sessionId, '[thematic.plan]');

                    $descType = (int) ($pl['description_type'] ?? $pl['descriptionType'] ?? 0);

                    $plan = (new CThematicPlan())
                        ->setThematic($thematic)
                        ->setTitle($plTitle)
                        ->setDescription($plDesc)
                        ->setDescriptionType($descType)
                    ;

                    $em->persist($plan);
                }

                // Flush once per thematic (advances + plans)
                $em->flush();

                $this->debug && error_log(
                    'COURSE_DEBUG: restore_thematic: created thematic iid='.(int) $thematic->getIid().
                    ' (advances='.\count($advList).', plans='.\count($planList).')'
                );
            } catch (Throwable $e) {
                error_log('COURSE_DEBUG: restore_thematic: failed: '.$e->getMessage());

                continue;
            }
        }
    }

    /**
     * Restore "Attendance" resources (register + calendar slots).
     *
     * @param mixed $sessionId
     */
    public function restore_attendance($sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_ATTENDANCE)) {
            $this->debug && error_log('COURSE_DEBUG: restore_attendance: no attendance resources.');

            return;
        }

        $em = Database::getManager();

        /** @var CourseEntity $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int) $sessionId) : null;

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_ATTENDANCE] as $legacyId => $att) {
            try {
                $p = (array) ($att->params ?? []);

                $title = trim((string) ($p['title'] ?? 'Attendance'));
                $desc = (string) ($p['description'] ?? '');
                $active = (int) ($p['active'] ?? 1);

                // Normalize title
                if ('' === $title) {
                    $title = 'Attendance';
                }

                // Rewrite HTML in description (links to course files, etc.)
                $desc = $this->rewriteHtmlForCourse($desc, (int) $sessionId, '[attendance.main]');

                // Optional grading attributes
                $qualTitle = isset($p['attendance_qualify_title']) ? (string) $p['attendance_qualify_title'] : null;
                $qualMax = (int) ($p['attendance_qualify_max'] ?? 0);
                $weight = (float) ($p['attendance_weight'] ?? 0.0);
                $locked = (int) ($p['locked'] ?? 0);

                // Create attendance entity
                $a = (new CAttendance())
                    ->setTitle($title)
                    ->setDescription($desc)
                    ->setActive($active)
                    ->setAttendanceQualifyTitle($qualTitle ?? '')
                    ->setAttendanceQualifyMax($qualMax)
                    ->setAttendanceWeight($weight)
                    ->setLocked($locked)
                ;

                // Link to course & creator if supported
                if (method_exists($a, 'setParent')) {
                    $a->setParent($courseEntity);
                }
                if (method_exists($a, 'setCreator')) {
                    $a->setCreator(api_get_user_entity());
                }
                if (method_exists($a, 'addCourseLink')) {
                    $a->addCourseLink($courseEntity, $sessionEntity);
                }

                $em->persist($a);
                $em->flush();

                // Map new IID back
                $this->course->resources[RESOURCE_ATTENDANCE][$legacyId] ??= new stdClass();
                $this->course->resources[RESOURCE_ATTENDANCE][$legacyId]->destination_id = (int) $a->getIid();

                // Restore calendar entries (slots)
                $calList = (array) ($att->attendance_calendar ?? []);
                foreach ($calList as $c) {
                    if (!\is_array($c)) {
                        $c = (array) $c;
                    }

                    // Date/time normalization with fallbacks
                    $rawDt = (string) ($c['date_time'] ?? $c['dateTime'] ?? $c['start_date'] ?? '');

                    try {
                        $dt = '' !== $rawDt ? new DateTime($rawDt) : new DateTime('now', new DateTimeZone('UTC'));
                    } catch (Throwable) {
                        $dt = new DateTime('now', new DateTimeZone('UTC'));
                    }

                    $done = (bool) ($c['done_attendance'] ?? $c['doneAttendance'] ?? false);
                    $blocked = (bool) ($c['blocked'] ?? false);
                    $duration = isset($c['duration']) ? (int) $c['duration'] : null;

                    $cal = (new CAttendanceCalendar())
                        ->setAttendance($a)
                        ->setDateTime($dt)
                        ->setDoneAttendance($done)
                        ->setBlocked($blocked)
                        ->setDuration($duration)
                    ;

                    $em->persist($cal);
                    $em->flush();

                    // Optionally attach a group to the calendar slot
                    $groupId = (int) ($c['group_id'] ?? 0);
                    if ($groupId > 0) {
                        try {
                            $repo = $em->getRepository(CAttendanceCalendarRelGroup::class);
                            if (method_exists($repo, 'addGroupToCalendar')) {
                                $repo->addGroupToCalendar((int) $cal->getIid(), $groupId);
                            }
                        } catch (Throwable $e) {
                            $this->debug && error_log('COURSE_DEBUG: restore_attendance: calendar group link skipped: '.$e->getMessage());
                        }
                    }
                }

                // Flush at the end for this attendance
                $em->flush();
                $this->debug && error_log('COURSE_DEBUG: restore_attendance: created attendance iid='.(int) $a->getIid().' (cal='.\count($calList).')');
            } catch (Throwable $e) {
                error_log('COURSE_DEBUG: restore_attendance: failed: '.$e->getMessage());

                continue;
            }
        }
    }

    /**
     * Restore Student Publications (works) from backup selection.
     * - Honors file policy: FILE_SKIP (1), FILE_RENAME (2), FILE_OVERWRITE (3)
     * - Creates a fresh ResourceNode for new items to avoid unique key collisions
     * - Keeps existing behavior: HTML rewriting, optional calendar event, destination_id mapping
     * - NO entity manager reopen helper (we avoid violations proactively).
     */
    public function restore_works(int $sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_WORK)) {
            return;
        }

        $em = Database::getManager();

        /** @var CourseEntity $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity($sessionId) : null;

        /** @var CStudentPublicationRepository $pubRepo */
        $pubRepo = Container::getStudentPublicationRepository();

        // Same-name policy already mapped at controller/restorer level
        $filePolicy = $this->file_option ?? (\defined('FILE_RENAME') ? FILE_RENAME : 2);

        $this->dlog('restore_works: begin', [
            'count' => \count($this->course->resources[RESOURCE_WORK] ?? []),
            'policy' => $filePolicy,
        ]);

        // Helper: generate a unique title within (course, session) scope
        $makeUniqueTitle = function (string $base) use ($pubRepo, $courseEntity, $sessionEntity): string {
            $t = '' !== $base ? $base : 'Work';
            $n = 0;
            $title = $t;
            while (true) {
                $qb = $pubRepo->findAllByCourse($courseEntity, $sessionEntity, $title, null, 'folder');
                $exists = $qb
                    ->andWhere('resource.publicationParent IS NULL')
                    ->andWhere('resource.active IN (0,1)')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult()
                ;
                if (!$exists) {
                    return $title;
                }
                $n++;
                $title = $t.' ('.$n.')';
            }
        };

        // Helper: create a fresh ResourceNode for the publication
        $createResourceNode = function (string $title) use ($em, $courseEntity, $sessionEntity) {
            $nodeClass = ResourceNode::class;
            $node = new $nodeClass();
            if (method_exists($node, 'setTitle')) {
                $node->setTitle($title);
            }
            if (method_exists($node, 'setCourse')) {
                $node->setCourse($courseEntity);
            }
            if (method_exists($node, 'addCourseLink')) {
                $node->addCourseLink($courseEntity, $sessionEntity);
            }
            if (method_exists($node, 'setResourceType')) {
                $node->setResourceType('student_publication');
            }
            $em->persist($node);

            // flush is deferred to the publication flush
            return $node;
        };

        foreach ($this->course->resources[RESOURCE_WORK] as $legacyId => $obj) {
            try {
                $p = (array) ($obj->params ?? []);

                $title = trim((string) ($p['title'] ?? 'Work'));
                if ('' === $title) {
                    $title = 'Work';
                }
                $originalTitle = $title;

                $description = (string) ($p['description'] ?? '');
                // HTML rewrite (assignment description)
                $description = $this->rewriteHtmlForCourse($description, (int) $sessionId, '[work.description]');

                $enableQualification = (bool) ($p['enable_qualification'] ?? false);
                $addToCalendar = 1 === (int) ($p['add_to_calendar'] ?? 0);

                $expiresOn = !empty($p['expires_on']) ? new DateTime($p['expires_on']) : null;
                $endsOn = !empty($p['ends_on']) ? new DateTime($p['ends_on']) : null;

                $weight = isset($p['weight']) ? (float) $p['weight'] : 0.0;
                $qualification = isset($p['qualification']) ? (float) $p['qualification'] : 0.0;
                $allowText = (int) ($p['allow_text_assignment'] ?? 0);
                $defaultVisibility = (bool) ($p['default_visibility'] ?? 0);
                $studentMayDelete = (bool) ($p['student_delete_own_publication'] ?? 0);
                $extensions = isset($p['extensions']) ? (string) $p['extensions'] : null;
                $groupCategoryWorkId = (int) ($p['group_category_work_id'] ?? 0);
                $postGroupId = (int) ($p['post_group_id'] ?? 0);

                // Check for existing root folder with same title
                $existingQb = $pubRepo->findAllByCourse($courseEntity, $sessionEntity, $title, null, 'folder');
                $existing = $existingQb
                    ->andWhere('resource.publicationParent IS NULL')
                    ->andWhere('resource.active IN (0,1)')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult()
                ;

                // Apply same-name policy proactively (avoid unique violations)
                if ($existing) {
                    if ($filePolicy === (\defined('FILE_SKIP') ? FILE_SKIP : 1)) {
                        $this->dlog('WORK: skip existing title', ['title' => $title, 'src_id' => $legacyId]);
                        $this->course->resources[RESOURCE_WORK][$legacyId] ??= new stdClass();
                        $this->course->resources[RESOURCE_WORK][$legacyId]->destination_id = (int) $existing->getIid();

                        continue;
                    }
                    if ($filePolicy === (\defined('FILE_RENAME') ? FILE_RENAME : 2)) {
                        $title = $makeUniqueTitle($title);
                        $existing = null; // force a new one
                    }
                // FILE_OVERWRITE: keep $existing and update below
                } else {
                    // No existing — still ensure uniqueness to avoid slug/node collisions
                    $title = $makeUniqueTitle($title);
                }

                if (!$existing) {
                    // Create NEW publication (folder) + NEW resource node
                    $pub = (new CStudentPublication())
                        ->setTitle($title)
                        ->setDescription($description)  // already rewritten
                        ->setFiletype('folder')
                        ->setContainsFile(0)
                        ->setWeight($weight)
                        ->setQualification($qualification)
                        ->setAllowTextAssignment($allowText)
                        ->setDefaultVisibility($defaultVisibility)
                        ->setStudentDeleteOwnPublication($studentMayDelete)
                        ->setExtensions($extensions)
                        ->setGroupCategoryWorkId($groupCategoryWorkId)
                        ->setPostGroupId($postGroupId)
                    ;

                    if (method_exists($pub, 'setParent')) {
                        $pub->setParent($courseEntity);
                    }
                    if (method_exists($pub, 'setCreator')) {
                        $pub->setCreator(api_get_user_entity());
                    }
                    if (method_exists($pub, 'addCourseLink')) {
                        $pub->addCourseLink($courseEntity, $sessionEntity);
                    }
                    if (method_exists($pub, 'setResourceNode')) {
                        $pub->setResourceNode($createResourceNode($title));
                    }

                    $em->persist($pub);

                    try {
                        $em->flush();
                    } catch (UniqueConstraintViolationException $e) {
                        // As a last resort, rename once and retry quickly (no EM reopen)
                        $this->dlog('WORK: unique violation on create, retry once with renamed title', [
                            'src_id' => $legacyId,
                            'err' => $e->getMessage(),
                        ]);
                        $newTitle = $makeUniqueTitle($title);
                        if (method_exists($pub, 'setTitle')) {
                            $pub->setTitle($newTitle);
                        }
                        if (method_exists($pub, 'setResourceNode')) {
                            $pub->setResourceNode($createResourceNode($newTitle));
                        }
                        $em->persist($pub);
                        $em->flush();
                    }

                    // Create Assignment row
                    $assignment = (new CStudentPublicationAssignment())
                        ->setPublication($pub)
                        ->setEnableQualification($enableQualification || $qualification > 0)
                    ;
                    if ($expiresOn) {
                        $assignment->setExpiresOn($expiresOn);
                    }
                    if ($endsOn) {
                        $assignment->setEndsOn($endsOn);
                    }

                    $em->persist($assignment);
                    $em->flush();

                    // Optional calendar entry
                    if ($addToCalendar) {
                        $eventTitle = \sprintf(get_lang('Handing over of task %s'), $pub->getTitle());

                        $publicationUrl = null;
                        $uuid = $pub->getResourceNode()?->getUuid();
                        if ($uuid) {
                            if (property_exists($this, 'router') && $this->router instanceof RouterInterface) {
                                try {
                                    $publicationUrl = $this->router->generate(
                                        'student_publication_view',
                                        ['uuid' => (string) $uuid],
                                        UrlGeneratorInterface::ABSOLUTE_PATH
                                    );
                                } catch (Throwable) {
                                    $publicationUrl = '/r/student_publication/'.$uuid;
                                }
                            } else {
                                $publicationUrl = '/r/student_publication/'.$uuid;
                            }
                        }

                        $contentBlock = \sprintf(
                            '<div>%s</div> %s',
                            $publicationUrl
                                ? \sprintf('<a href="%s">%s</a>', $publicationUrl, htmlspecialchars($pub->getTitle(), ENT_QUOTES))
                                : htmlspecialchars($pub->getTitle(), ENT_QUOTES),
                            $pub->getDescription()
                        );
                        $contentBlock = $this->rewriteHtmlForCourse($contentBlock, (int) $sessionId, '[work.calendar]');

                        $start = $expiresOn ? clone $expiresOn : new DateTime('now', new DateTimeZone('UTC'));
                        $end = $expiresOn ? clone $expiresOn : new DateTime('now', new DateTimeZone('UTC'));

                        $color = CCalendarEvent::COLOR_STUDENT_PUBLICATION;
                        if ($colors = api_get_setting('agenda.agenda_colors')) {
                            if (!empty($colors['student_publication'])) {
                                $color = $colors['student_publication'];
                            }
                        }

                        $event = (new CCalendarEvent())
                            ->setTitle($eventTitle)
                            ->setContent($contentBlock)
                            ->setParent($courseEntity)
                            ->setCreator($pub->getCreator())
                            ->addLink(clone $pub->getFirstResourceLink())
                            ->setStartDate($start)
                            ->setEndDate($end)
                            ->setColor($color)
                        ;

                        $em->persist($event);
                        $em->flush();

                        $assignment->setEventCalendarId((int) $event->getIid());
                        $em->flush();
                    }

                    // Map destination for LP path resolution
                    $this->course->resources[RESOURCE_WORK][$legacyId] ??= new stdClass();
                    $this->course->resources[RESOURCE_WORK][$legacyId]->destination_id = (int) $pub->getIid();

                    $this->dlog('restore_works: created', [
                        'src_id' => (int) $legacyId,
                        'dst_iid' => (int) $pub->getIid(),
                        'title' => $pub->getTitle(),
                    ]);
                } else {
                    // FILE_OVERWRITE: update existing
                    $existing
                        ->setDescription($this->rewriteHtmlForCourse((string) $description, (int) $sessionId, '[work.description.overwrite]'))
                        ->setWeight($weight)
                        ->setQualification($qualification)
                        ->setAllowTextAssignment($allowText)
                        ->setDefaultVisibility($defaultVisibility)
                        ->setStudentDeleteOwnPublication($studentMayDelete)
                        ->setExtensions($extensions)
                        ->setGroupCategoryWorkId($groupCategoryWorkId)
                        ->setPostGroupId($postGroupId)
                    ;

                    // Ensure it has a ResourceNode
                    if (method_exists($existing, 'getResourceNode') && method_exists($existing, 'setResourceNode')) {
                        if (!$existing->getResourceNode()) {
                            $existing->setResourceNode($createResourceNode($existing->getTitle() ?: $originalTitle));
                        }
                    }

                    $em->persist($existing);
                    $em->flush();

                    // Assignment row
                    $assignment = $existing->getAssignment();
                    if (!$assignment) {
                        $assignment = new CStudentPublicationAssignment();
                        $assignment->setPublication($existing);
                        $em->persist($assignment);
                    }
                    $assignment->setEnableQualification($enableQualification || $qualification > 0);
                    $assignment->setExpiresOn($expiresOn);
                    $assignment->setEndsOn($endsOn);
                    if (!$addToCalendar) {
                        $assignment->setEventCalendarId(0);
                    }
                    $em->flush();

                    $this->course->resources[RESOURCE_WORK][$legacyId] ??= new stdClass();
                    $this->course->resources[RESOURCE_WORK][$legacyId]->destination_id = (int) $existing->getIid();

                    $this->dlog('restore_works: overwritten existing', [
                        'src_id' => (int) $legacyId,
                        'dst_iid' => (int) $existing->getIid(),
                        'title' => $existing->getTitle(),
                    ]);
                }
            } catch (Throwable $e) {
                $this->dlog('restore_works: failed', [
                    'src_id' => (int) $legacyId,
                    'err' => $e->getMessage(),
                ]);

                // Do NOT try to reopen EM here (as requested) — just continue gracefully
                continue;
            }
        }

        $this->dlog('restore_works: end');
    }

    /**
     * Restore the Gradebook structure (categories, evaluations, links).
     * Overwrites destination gradebook for the course/session.
     */
    public function restore_gradebook(int $sessionId = 0): void
    {
        // Only meaningful with OVERWRITE semantics (skip/rename make little sense here)
        if (\in_array($this->file_option, [FILE_SKIP, FILE_RENAME], true)) {
            return;
        }

        if (!$this->course->has_resources(RESOURCE_GRADEBOOK)) {
            $this->dlog('restore_gradebook: no gradebook resources');

            return;
        }

        /** @var EntityManagerInterface $em */
        $em = Database::getManager();

        /** @var Course $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity($sessionId) : null;

        /** @var User $currentUser */
        $currentUser = api_get_user_entity();

        $catRepo = $em->getRepository(GradebookCategory::class);

        // Clean destination categories when overwriting
        try {
            $existingCats = $catRepo->findBy([
                'course' => $courseEntity,
                'session' => $sessionEntity,
            ]);
            foreach ($existingCats as $cat) {
                $em->remove($cat);
            }
            $em->flush();
            $this->dlog('restore_gradebook: destination cleaned', ['removed' => \count($existingCats)]);
        } catch (Throwable $e) {
            $this->dlog('restore_gradebook: clean failed (continuing)', ['error' => $e->getMessage()]);
        }

        $oldIdToNewCat = [];

        // First pass: create categories
        foreach ($this->course->resources[RESOURCE_GRADEBOOK] as $gbItem) {
            $categories = (array) ($gbItem->categories ?? []);
            foreach ($categories as $rawCat) {
                $c = \is_array($rawCat) ? $rawCat : (array) $rawCat;

                $oldId = (int) ($c['id'] ?? $c['iid'] ?? 0);
                $title = (string) ($c['title'] ?? 'Category');
                $desc = (string) ($c['description'] ?? '');
                $weight = (float) ($c['weight'] ?? 0.0);
                $visible = (bool) ($c['visible'] ?? true);
                $locked = (int) ($c['locked'] ?? 0);

                // Rewrite HTML in category description
                $desc = $this->rewriteHtmlForCourse($desc, (int) $sessionId, '[gradebook.category]');

                $new = (new GradebookCategory())
                    ->setCourse($courseEntity)
                    ->setSession($sessionEntity)
                    ->setUser($currentUser)
                    ->setTitle($title)
                    ->setDescription($desc)
                    ->setWeight($weight)
                    ->setVisible($visible)
                    ->setLocked($locked)
                ;

                // Optional flags (mirror legacy fields)
                if (isset($c['generate_certificates'])) {
                    $new->setGenerateCertificates((bool) $c['generate_certificates']);
                }
                if (isset($c['generateCertificates'])) {
                    $new->setGenerateCertificates((bool) $c['generateCertificates']);
                }
                if (isset($c['certificate_validity_period'])) {
                    $new->setCertificateValidityPeriod((int) $c['certificate_validity_period']);
                }
                if (isset($c['certificateValidityPeriod'])) {
                    $new->setCertificateValidityPeriod((int) $c['certificateValidityPeriod']);
                }
                if (isset($c['is_requirement'])) {
                    $new->setIsRequirement((bool) $c['is_requirement']);
                }
                if (isset($c['isRequirement'])) {
                    $new->setIsRequirement((bool) $c['isRequirement']);
                }
                if (isset($c['default_lowest_eval_exclude'])) {
                    $new->setDefaultLowestEvalExclude((bool) $c['default_lowest_eval_exclude']);
                }
                if (isset($c['defaultLowestEvalExclude'])) {
                    $new->setDefaultLowestEvalExclude((bool) $c['defaultLowestEvalExclude']);
                }
                if (\array_key_exists('minimum_to_validate', $c)) {
                    $new->setMinimumToValidate((int) $c['minimum_to_validate']);
                }
                if (\array_key_exists('minimumToValidate', $c)) {
                    $new->setMinimumToValidate((int) $c['minimumToValidate']);
                }
                if (\array_key_exists('gradebooks_to_validate_in_dependence', $c)) {
                    $new->setGradeBooksToValidateInDependence((int) $c['gradebooks_to_validate_in_dependence']);
                }
                if (\array_key_exists('gradeBooksToValidateInDependence', $c)) {
                    $new->setGradeBooksToValidateInDependence((int) $c['gradeBooksToValidateInDependence']);
                }
                if (\array_key_exists('allow_skills_by_subcategory', $c)) {
                    $new->setAllowSkillsBySubcategory((int) $c['allow_skills_by_subcategory']);
                }
                if (\array_key_exists('allowSkillsBySubcategory', $c)) {
                    $new->setAllowSkillsBySubcategory((int) $c['allowSkillsBySubcategory']);
                }
                if (!empty($c['grade_model_id'])) {
                    $gm = $em->find(GradeModel::class, (int) $c['grade_model_id']);
                    if ($gm) {
                        $new->setGradeModel($gm);
                    }
                }

                $em->persist($new);
                $em->flush();

                if ($oldId > 0) {
                    $oldIdToNewCat[$oldId] = $new;
                }
            }
        }

        // Second pass: wire category parents
        foreach ($this->course->resources[RESOURCE_GRADEBOOK] as $gbItem) {
            $categories = (array) ($gbItem->categories ?? []);
            foreach ($categories as $rawCat) {
                $c = \is_array($rawCat) ? $rawCat : (array) $rawCat;
                $oldId = (int) ($c['id'] ?? $c['iid'] ?? 0);
                $parentOld = (int) ($c['parent_id'] ?? $c['parentId'] ?? 0);
                if ($oldId > 0 && isset($oldIdToNewCat[$oldId]) && $parentOld > 0 && isset($oldIdToNewCat[$parentOld])) {
                    $cat = $oldIdToNewCat[$oldId];
                    $cat->setParent($oldIdToNewCat[$parentOld]);
                    $em->persist($cat);
                }
            }
        }
        $em->flush();

        // Evaluations and Links per category
        foreach ($this->course->resources[RESOURCE_GRADEBOOK] as $gbItem) {
            $categories = (array) ($gbItem->categories ?? []);
            foreach ($categories as $rawCat) {
                $c = \is_array($rawCat) ? $rawCat : (array) $rawCat;
                $oldId = (int) ($c['id'] ?? $c['iid'] ?? 0);
                if ($oldId <= 0 || !isset($oldIdToNewCat[$oldId])) {
                    continue;
                }

                $dstCat = $oldIdToNewCat[$oldId];

                // Evaluations (rewrite description HTML)
                foreach ((array) ($c['evaluations'] ?? []) as $rawEval) {
                    $e = \is_array($rawEval) ? $rawEval : (array) $rawEval;

                    $evalDesc = (string) ($e['description'] ?? '');
                    $evalDesc = $this->rewriteHtmlForCourse($evalDesc, (int) $sessionId, '[gradebook.evaluation]');

                    $eval = (new GradebookEvaluation())
                        ->setCourse($courseEntity)
                        ->setCategory($dstCat)
                        ->setTitle((string) ($e['title'] ?? 'Evaluation'))
                        ->setDescription($evalDesc)
                        ->setWeight((float) ($e['weight'] ?? 0.0))
                        ->setMax((float) ($e['max'] ?? 100.0))
                        ->setType((string) ($e['type'] ?? 'manual'))
                        ->setVisible((int) ($e['visible'] ?? 1))
                        ->setLocked((int) ($e['locked'] ?? 0))
                    ;

                    // Optional statistics fields
                    if (isset($e['best_score'])) {
                        $eval->setBestScore((float) $e['best_score']);
                    }
                    if (isset($e['average_score'])) {
                        $eval->setAverageScore((float) $e['average_score']);
                    }
                    if (isset($e['score_weight'])) {
                        $eval->setScoreWeight((float) $e['score_weight']);
                    }
                    if (isset($e['min_score'])) {
                        $eval->setMinScore((float) $e['min_score']);
                    }

                    $em->persist($eval);
                }

                // Links to course tools (resolve destination IID for each)
                foreach ((array) ($c['links'] ?? []) as $rawLink) {
                    $l = \is_array($rawLink) ? $rawLink : (array) $rawLink;

                    $linkType = (int) ($l['type'] ?? $l['link_type'] ?? 0);
                    $legacyRef = (int) ($l['ref_id'] ?? $l['refId'] ?? 0);
                    if ($linkType <= 0 || $legacyRef <= 0) {
                        $this->dlog('restore_gradebook: skipping link (missing type/ref)', $l);

                        continue;
                    }

                    // Map link type → resource bucket, then resolve legacyId → newId
                    $resourceType = $this->gb_guessResourceTypeByLinkType($linkType);
                    $newRefId = $this->gb_resolveDestinationId($resourceType, $legacyRef);
                    if ($newRefId <= 0) {
                        $this->dlog('restore_gradebook: skipping link (no destination id)', ['type' => $linkType, 'legacyRef' => $legacyRef]);

                        continue;
                    }

                    $link = (new GradebookLink())
                        ->setCourse($courseEntity)
                        ->setCategory($dstCat)
                        ->setType($linkType)
                        ->setRefId($newRefId)
                        ->setWeight((float) ($l['weight'] ?? 0.0))
                        ->setVisible((int) ($l['visible'] ?? 1))
                        ->setLocked((int) ($l['locked'] ?? 0))
                    ;

                    // Optional statistics fields
                    if (isset($l['best_score'])) {
                        $link->setBestScore((float) $l['best_score']);
                    }
                    if (isset($l['average_score'])) {
                        $link->setAverageScore((float) $l['average_score']);
                    }
                    if (isset($l['score_weight'])) {
                        $link->setScoreWeight((float) $l['score_weight']);
                    }
                    if (isset($l['min_score'])) {
                        $link->setMinScore((float) $l['min_score']);
                    }

                    $em->persist($link);
                }

                $em->flush();
            }
        }

        $this->dlog('restore_gradebook: done');
    }

    /**
     * Restore course assets (not included in documents).
     */
    public function restore_assets(): void
    {
        if ($this->course->has_resources(RESOURCE_ASSET)) {
            $resources = $this->course->resources;
            $path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/';

            foreach ($resources[RESOURCE_ASSET] as $asset) {
                if (is_file($this->course->backup_path.'/'.$asset->path)
                    && is_readable($this->course->backup_path.'/'.$asset->path)
                    && is_dir(\dirname($path.$asset->path))
                    && is_writable(\dirname($path.$asset->path))
                ) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            break;

                        case FILE_OVERWRITE:
                            copy(
                                $this->course->backup_path.'/'.$asset->path,
                                $path.$asset->path
                            );

                            break;
                    }
                }
            }
        }
    }

    /**
     * Get all resources from snapshot or live course object.
     *
     * @return array<string,array>
     */
    public function getAllResources(): array
    {
        // Prefer the previously captured snapshot if present; otherwise fall back to current course->resources
        return !empty($this->resources_all_snapshot)
            ? $this->resources_all_snapshot
            : (array) ($this->course->resources ?? []);
    }

    /**
     * Back-fill empty dependency bags from the snapshot into $this->course->resources.
     */
    private function ensureDepsBagsFromSnapshot(): void
    {
        // Read the authoritative set of resources (snapshot or live)
        $all = $this->getAllResources();

        // Reference the course resources by reference to update in place
        $c = &$this->course->resources;

        // Ensure these resource bags exist; if missing/empty, copy them from snapshot
        foreach (['document', 'link', 'quiz', 'work', 'survey', 'Forum_Category', 'forum', 'thread', 'post', 'Exercise_Question', 'survey_question', 'Link_Category'] as $k) {
            $cur = $c[$k] ?? [];
            if ((!\is_array($cur) || 0 === \count($cur)) && !empty($all[$k]) && \is_array($all[$k])) {
                // Back-fill from snapshot to keep dependencies consistent
                $c[$k] = $all[$k];
            }
        }
    }

    /**
     * Rewrite HTML content so legacy course URLs point to destination course documents.
     *
     * Returns the (possibly) rewritten HTML.
     */
    private function rewriteHtmlForCourse(string $html, int $sessionId, string $dbgTag = ''): string
    {
        // Nothing to do if the HTML is empty
        if ('' === $html) {
            return '';
        }

        // Resolve context entities (course/session/group) and repositories
        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $sessionId);
        $group = api_get_group_entity();
        $docRepo = Container::getDocumentRepository();

        // Determine course directory and source root (when importing from a ZIP/package)
        $courseDir = (string) ($this->course->info['path'] ?? '');
        $srcRoot = rtrim((string) ($this->course->backup_path ?? ''), '/');

        // Cache of created folder IIDs per course dir to avoid duplicate folder creation
        if (!isset($this->htmlFoldersByCourseDir[$courseDir])) {
            $this->htmlFoldersByCourseDir[$courseDir] = [];
        }
        $folders = &$this->htmlFoldersByCourseDir[$courseDir];

        // Small debug helper bound to the current dbgTag
        $DBG = function (string $tag, array $ctx = []) use ($dbgTag): void {
            $this->dlog('HTMLRW'.$dbgTag.': '.$tag, $ctx);
        };

        // Ensure a folder chain exists under /document and return parent IID (0 means root)
        $ensureFolder = function (string $relPath) use (&$folders, $course, $session, $DBG) {
            // Ignore empty/root markers
            if ('/' === $relPath || '/document' === $relPath) {
                return 0;
            }

            // Reuse cached IID if we already created/resolved this path
            if (!empty($folders[$relPath])) {
                return (int) $folders[$relPath];
            }

            try {
                // Create the folder via DocumentManager; parent is resolved by the path
                $entity = DocumentManager::addDocument(
                    ['real_id' => $course->getId(), 'code' => method_exists($course, 'getCode') ? $course->getCode() : null],
                    $relPath,
                    'folder',
                    0,
                    basename($relPath),
                    null,
                    0,
                    null,
                    0,
                    (int) ($session?->getId() ?? 0)
                );

                // Cache the created IID if available
                $iid = method_exists($entity, 'getIid') ? (int) $entity->getIid() : 0;
                if ($iid > 0) {
                    $folders[$relPath] = $iid;
                }

                return $iid;
            } catch (Throwable $e) {
                // Do not interrupt restore flow if folder creation fails
                $DBG('ensureFolder.error', ['relPath' => $relPath, 'err' => $e->getMessage()]);

                return 0;
            }
        };

        // Only rewrite when we are importing from a package (ZIP) with a known source root
        if ('' !== $srcRoot) {
            try {
                // Build a URL map for all legacy references found in the HTML
                $mapDoc = ChamiloHelper::buildUrlMapForHtmlFromPackage(
                    $html,
                    $courseDir,
                    $srcRoot,
                    $folders,
                    $ensureFolder,
                    $docRepo,
                    $course,
                    $session,
                    $group,
                    (int) $sessionId,
                    (int) $this->file_option,
                    $DBG
                );

                // Rewrite the HTML using both exact (byRel) and basename (byBase) maps
                $rr = ChamiloHelper::rewriteLegacyCourseUrlsWithMap(
                    $html,
                    $courseDir,
                    $mapDoc['byRel'] ?? [],
                    $mapDoc['byBase'] ?? []
                );

                // Log replacement stats for troubleshooting
                $DBG('zip.rewrite', ['replaced' => $rr['replaced'] ?? 0, 'misses' => $rr['misses'] ?? 0]);

                // Return rewritten HTML when available; otherwise the original
                return (string) ($rr['html'] ?? $html);
            } catch (Throwable $e) {
                // Fall back to original HTML if anything fails during mapping/rewrite
                $DBG('zip.error', ['err' => $e->getMessage()]);

                return $html;
            }
        }

        // If no package source root, return the original HTML unchanged
        return $html;
    }

    /**
     * Centralized logger controlled by $this->debug.
     */
    private function dlog(string $message, array $context = []): void
    {
        if (!$this->debug) {
            return;
        }
        $ctx = '';
        if (!empty($context)) {
            try {
                $ctx = ' '.json_encode(
                        $context,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
                    );
            } catch (Throwable $e) {
                $ctx = ' [context_json_failed: '.$e->getMessage().']';
            }
        }
        error_log('COURSE_DEBUG: '.$message.$ctx);
    }

    /**
     * Public setter for the debug flag.
     */
    public function setDebug(?bool $on = true): void
    {
        $this->debug = (bool) $on;
        $this->dlog('Debug flag changed', ['debug' => $this->debug]);
    }

    /**
     * Given a RESOURCE_* bucket and legacy id, return destination id (if that item was restored).
     */
    private function gb_resolveDestinationId(?int $type, int $legacyId): int
    {
        if (null === $type) {
            return 0;
        }
        if (!$this->course->has_resources($type)) {
            return 0;
        }
        $bucket = $this->course->resources[$type] ?? [];
        if (!isset($bucket[$legacyId])) {
            return 0;
        }
        $res = $bucket[$legacyId];
        $destId = (int) ($res->destination_id ?? 0);

        return $destId > 0 ? $destId : 0;
    }

    /**
     * Map GradebookLink type → RESOURCE_* bucket used in $this->course->resources.
     */
    private function gb_guessResourceTypeByLinkType(int $linkType): ?int
    {
        return match ($linkType) {
            LINK_EXERCISE => RESOURCE_QUIZ,
            LINK_STUDENTPUBLICATION => RESOURCE_WORK,
            LINK_LEARNPATH => RESOURCE_LEARNPATH,
            LINK_FORUM_THREAD => RESOURCE_FORUMTOPIC,
            LINK_ATTENDANCE => RESOURCE_ATTENDANCE,
            LINK_SURVEY => RESOURCE_SURVEY,
            LINK_HOTPOTATOES => RESOURCE_QUIZ,
            default => null,
        };
    }

    /**
     * Add this setter to forward the full resources snapshot from the controller.
     */
    public function setResourcesAllSnapshot(array $snapshot): void
    {
        // Keep a private property like $this->resources_all_snapshot
        // (declare it if you don't have it: private array $resources_all_snapshot = [];)
        $this->resources_all_snapshot = $snapshot;
        $this->dlog('Restorer: all-resources snapshot injected', [
            'keys' => array_keys($snapshot),
        ]);
    }

    /**
     * Zip a SCORM folder (must contain imsmanifest.xml) into a temp ZIP.
     * Returns absolute path to the temp ZIP or null on error.
     */
    private function zipScormFolder(string $folderAbs): ?string
    {
        $folderAbs = rtrim($folderAbs, '/');
        $manifest = $folderAbs.'/imsmanifest.xml';
        if (!is_file($manifest)) {
            error_log("SCORM ZIPPER: 'imsmanifest.xml' not found in folder: $folderAbs");

            return null;
        }

        $tmpZip = sys_get_temp_dir().'/scorm_'.uniqid('', true).'.zip';

        try {
            $zip = new ZipFile();
            // Put folder contents at the ZIP root – important for SCORM imports
            $zip->addDirRecursive($folderAbs, '');
            $zip->saveAsFile($tmpZip);
            $zip->close();
        } catch (Throwable $e) {
            error_log('SCORM ZIPPER: Failed to create temp zip: '.$e->getMessage());

            return null;
        }

        if (!is_file($tmpZip) || 0 === filesize($tmpZip)) {
            @unlink($tmpZip);
            error_log("SCORM ZIPPER: Temp zip is empty or missing: $tmpZip");

            return null;
        }

        return $tmpZip;
    }

    /**
     * Find a SCORM package for a given LP.
     * It returns ['zip' => <abs path or null>, 'temp' => true if zip is temporary].
     *
     * Search order:
     *  1) resources[SCORM] entries bound to this LP (zip or path).
     *     - If 'path' is a folder containing imsmanifest.xml, it will be zipped on the fly.
     *  2) Heuristics: scan typical folders for *.zip
     *  3) Heuristics: scan backup recursively for an imsmanifest.xml, then zip that folder.
     */
    private function findScormPackageForLp(int $srcLpId): array
    {
        $out = ['zip' => null, 'temp' => false];
        $base = rtrim($this->course->backup_path, '/');

        // 1) Direct mapping from SCORM bucket
        if (!empty($this->course->resources[RESOURCE_SCORM]) && \is_array($this->course->resources[RESOURCE_SCORM])) {
            foreach ($this->course->resources[RESOURCE_SCORM] as $sc) {
                $src = isset($sc->source_lp_id) ? (int) $sc->source_lp_id : 0;
                $dst = isset($sc->lp_id_dest) ? (int) $sc->lp_id_dest : 0;
                $match = ($src && $src === $srcLpId);

                if (
                    !$match
                    && $dst
                    && !empty($this->course->resources[RESOURCE_LEARNPATH][$srcLpId]->destination_id)
                ) {
                    $match = ($dst === (int) $this->course->resources[RESOURCE_LEARNPATH][$srcLpId]->destination_id);
                }
                if (!$match) {
                    continue;
                }

                $cands = [];
                if (!empty($sc->zip)) {
                    $cands[] = $base.'/'.ltrim((string) $sc->zip, '/');
                }
                if (!empty($sc->path)) {
                    $cands[] = $base.'/'.ltrim((string) $sc->path, '/');
                }

                foreach ($cands as $abs) {
                    if (is_file($abs) && is_readable($abs)) {
                        $out['zip'] = $abs;
                        $out['temp'] = false;

                        return $out;
                    }
                    if (is_dir($abs) && is_readable($abs)) {
                        $tmp = $this->zipScormFolder($abs);
                        if ($tmp) {
                            $out['zip'] = $tmp;
                            $out['temp'] = true;

                            return $out;
                        }
                    }
                }
            }
        }

        // 2) Heuristic: typical folders with *.zip
        foreach (['/scorm', '/document/scorm', '/documents/scorm'] as $dir) {
            $full = $base.$dir;
            if (!is_dir($full)) {
                continue;
            }
            $glob = glob($full.'/*.zip') ?: [];
            if (!empty($glob)) {
                $out['zip'] = $glob[0];
                $out['temp'] = false;

                return $out;
            }
        }

        // 3) Heuristic: look for imsmanifest.xml anywhere, then zip that folder
        $riiFlags = FilesystemIterator::SKIP_DOTS;

        try {
            $rii = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, $riiFlags),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($rii as $f) {
                if ($f->isFile() && 'imsmanifest.xml' === strtolower($f->getFilename())) {
                    $folder = $f->getPath();
                    $tmp = $this->zipScormFolder($folder);
                    if ($tmp) {
                        $out['zip'] = $tmp;
                        $out['temp'] = true;

                        return $out;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('SCORM FINDER: Recursive scan failed: '.$e->getMessage());
        }

        return $out;
    }

    /**
     * Check if a survey code is available.
     *
     * @param mixed $survey_code
     *
     * @return bool
     */
    public function is_survey_code_available($survey_code)
    {
        $survey_code = (string) $survey_code;
        $surveyRepo = Container::getSurveyRepository();

        try {
            // If a survey with this code exists, it's not available
            $hit = $surveyRepo->findOneBy(['code' => $survey_code]);

            return $hit ? false : true;
        } catch (Throwable $e) {
            // Fallback to "available" on repository failure
            $this->debug && error_log('COURSE_DEBUG: is_survey_code_available: fallback failed: '.$e->getMessage());

            return true;
        }
    }

    /**
     * Resolve absolute filesystem path for an announcement attachment.
     */
    private function resourceFileAbsPathFromAnnouncementAttachment(CAnnouncementAttachment $att): ?string
    {
        // Get the resource node linked to this attachment
        $node = $att->getResourceNode();
        if (!$node) {
            return null; // No node, nothing to resolve
        }

        // Get the first physical resource file
        $file = $node->getFirstResourceFile();
        if (!$file) {
            return null; // No physical file bound
        }

        /** @var ResourceNodeRepository $rnRepo */
        $rnRepo = Container::$container->get(ResourceNodeRepository::class);

        // Relative path stored by the repository
        $rel = $rnRepo->getFilename($file);
        if (!$rel) {
            return null; // Missing relative path
        }

        // Compose absolute path inside the project upload base
        $abs = $this->projectUploadBase().$rel;

        // Return only if readable to avoid runtime errors
        return is_readable($abs) ? $abs : null;
    }

    /**
     * Compact dump of resources: keys, per-bag counts and one sample (trimmed).
     */
    private function debug_course_resources_simple(?string $focusBag = null, int $maxObjFields = 10): void
    {
        try {
            $resources = \is_array($this->course->resources ?? null) ? $this->course->resources : [];

            $safe = function ($data): string {
                try {
                    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '[json_encode_failed]';
                } catch (Throwable $e) {
                    return '[json_exception: '.$e->getMessage().']';
                }
            };
            $short = function ($v, int $max = 200) {
                if (\is_string($v)) {
                    $s = trim($v);

                    return mb_strlen($s) > $max ? (mb_substr($s, 0, $max).'…('.mb_strlen($s).' chars)') : $s;
                }
                if (is_numeric($v) || \is_bool($v) || null === $v) {
                    return $v;
                }

                return '['.\gettype($v).']';
            };
            $sample = function ($item) use ($short, $maxObjFields) {
                $out = [
                    'source_id' => null,
                    'destination_id' => null,
                    'type' => null,
                    'has_obj' => false,
                    'obj_fields' => [],
                    'has_item_props' => false,
                    'extra' => [],
                ];
                if (\is_object($item) || \is_array($item)) {
                    $arr = (array) $item;
                    $out['source_id'] = $arr['source_id'] ?? null;
                    $out['destination_id'] = $arr['destination_id'] ?? null;
                    $out['type'] = $arr['type'] ?? null;
                    $out['has_item_props'] = !empty($arr['item_properties']);

                    $obj = $arr['obj'] ?? null;
                    if (\is_object($obj) || \is_array($obj)) {
                        $out['has_obj'] = true;
                        $objArr = (array) $obj;
                        $fields = [];
                        $i = 0;
                        foreach ($objArr as $k => $v) {
                            if ($i++ >= $maxObjFields) {
                                $fields['__notice'] = 'truncated';

                                break;
                            }
                            $fields[$k] = $short($v);
                        }
                        $out['obj_fields'] = $fields;
                    }
                    foreach (['path', 'title', 'comment'] as $k) {
                        if (isset($arr[$k])) {
                            $out['extra'][$k] = $short($arr[$k]);
                        }
                    }
                } else {
                    $out['extra']['_type'] = \gettype($item);
                }

                return $out;
            };

            $this->dlog('Resources overview', ['keys' => array_keys($resources)]);

            foreach ($resources as $bagName => $bag) {
                if (!\is_array($bag)) {
                    $this->dlog('Bag not an array, skipping', ['bag' => $bagName, 'type' => \gettype($bag)]);

                    continue;
                }
                $count = \count($bag);
                $this->dlog('Bag count', ['bag' => $bagName, 'count' => $count]);

                if ($count > 0) {
                    $firstKey = array_key_first($bag);
                    $firstVal = $bag[$firstKey];
                    $s = $sample($firstVal);
                    $s['__first_key'] = $firstKey;
                    $s['__class'] = \is_object($firstVal) ? $firstVal::class : \gettype($firstVal);
                    $this->dlog('Bag sample', ['bag' => $bagName, 'sample' => $s]);
                }

                if (null !== $focusBag && $focusBag === $bagName) {
                    $preview = [];
                    $i = 0;
                    foreach ($bag as $k => $v) {
                        if ($i++ >= 10) {
                            $preview[] = ['__notice' => 'truncated-after-10-items'];

                            break;
                        }
                        $preview[] = ['key' => $k, 'sample' => $sample($v)];
                    }
                    $this->dlog('Bag deep preview', ['bag' => $bagName, 'items' => $preview]);
                }
            }
        } catch (Throwable $e) {
            $this->dlog('Failed to dump resources', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get absolute base path where ResourceFiles are stored in the project.
     */
    private function projectUploadBase(): string
    {
        /** @var KernelInterface $kernel */
        $kernel = Container::$container->get('kernel');

        // Resource uploads live under var/upload/resource (Symfony project dir)
        return rtrim($kernel->getProjectDir(), '/').'/var/upload/resource';
    }

    /**
     * Resolve the absolute file path for a CDocument's first ResourceFile, if readable.
     */
    private function resourceFileAbsPathFromDocument(CDocument $doc): ?string
    {
        // Each CDocument references a ResourceNode; bail out if missing
        $node = $doc->getResourceNode();
        if (!$node) {
            return null;
        }

        // Use the first ResourceFile attached to the node
        $file = $node->getFirstResourceFile();
        if (!$file) {
            return null;
        }

        /** @var ResourceNodeRepository $rnRepo */
        $rnRepo = Container::$container->get(ResourceNodeRepository::class);

        // Repository provides the relative path for the resource file
        $rel = $rnRepo->getFilename($file);
        if (!$rel) {
            return null;
        }

        // Compose absolute path and validate readability
        $abs = $this->projectUploadBase().$rel;

        return is_readable($abs) ? $abs : null;
    }

    /**
     * Normalize forum keys so internal bags are always available.
     */
    private function normalizeForumKeys(): void
    {
        if (!\is_array($this->course->resources ?? null)) {
            $this->course->resources = [];

            return;
        }
        $r = $this->course->resources;

        // Categories
        if (!isset($r['Forum_Category']) && isset($r['forum_category'])) {
            $r['Forum_Category'] = $r['forum_category'];
        }

        // Forums
        if (!isset($r['forum']) && isset($r['Forum'])) {
            $r['forum'] = $r['Forum'];
        }

        // Topics
        if (!isset($r['thread']) && isset($r['forum_topic'])) {
            $r['thread'] = $r['forum_topic'];
        } elseif (!isset($r['thread']) && isset($r['Forum_Thread'])) {
            $r['thread'] = $r['Forum_Thread'];
        }

        // Posts
        if (!isset($r['post']) && isset($r['forum_post'])) {
            $r['post'] = $r['forum_post'];
        } elseif (!isset($r['post']) && isset($r['Forum_Post'])) {
            $r['post'] = $r['Forum_Post'];
        }

        $this->course->resources = $r;
        $this->dlog('Forum keys normalized', [
            'has_Forum_Category' => isset($r['Forum_Category']),
            'forum_count' => isset($r['forum']) && \is_array($r['forum']) ? \count($r['forum']) : 0,
            'thread_count' => isset($r['thread']) && \is_array($r['thread']) ? \count($r['thread']) : 0,
            'post_count' => isset($r['post']) && \is_array($r['post']) ? \count($r['post']) : 0,
        ]);
    }

    /**
     * Reset Doctrine if the EntityManager is closed; otherwise clear it.
     */
    private function resetDoctrineIfClosed(): void
    {
        try {
            // Get the current EntityManager
            $em = Database::getManager();

            if (!$em->isOpen()) {
                // If closed, reset the manager to recover from fatal transaction errors
                $registry = Container::$container->get('doctrine');
                $registry->resetManager();
            } else {
                // If open, just clear to free managed entities and avoid memory leaks
                $em->clear();
            }
        } catch (Throwable $e) {
            // Never break the flow due to maintenance logic
            error_log('COURSE_DEBUG: resetDoctrineIfClosed failed: '.$e->getMessage());
        }
    }

    private function getCourseBackupsBase(): string
    {
        try {
            if (method_exists(CourseArchiver::class, 'getBackupDir')) {
                $dir = rtrim(CourseArchiver::getBackupDir(), '/');
                if ($dir !== '') {
                    return $dir;
                }
            }
        } catch (\Throwable $e) {
        }

        return rtrim(api_get_path(SYS_ARCHIVE_PATH), '/').'/course_backups';
    }
}
