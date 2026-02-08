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
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Tool\User;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
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
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Chamilo\CourseBundle\Repository\CLinkCategoryRepository;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use CourseManager;
use Database;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
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
use Throwable;

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
    private bool $debug = false;

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
        $session_id = (int) $session_id;
        $this->dlog('Restore() called', [
            'destination_code' => $destination_course_code,
            'session_id' => $session_id,
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

        // Normalize forum bags + snapshot helpers
        $this->normalizeForumKeys();
        $this->ensureDepsBagsFromSnapshot();

        // Dump a compact view of the resource bags before restoring
        $this->debug_course_resources_simple(null);
        $deferredTools = ['gradebook'];
        $tools = $this->tools_to_restore ?? [];
        if (!is_array($tools)) {
            $tools = (array) $tools;
        }

        $toolsNow = [];
        $toolsLater = [];

        foreach ($tools as $tool) {
            if (in_array($tool, $deferredTools, true)) {
                $toolsLater[] = $tool;
            } else {
                $toolsNow[] = $tool;
            }
        }

        $this->dlog('Tool restore order resolved', [
            'tools_now' => $toolsNow,
            'tools_later' => $toolsLater,
        ]);

        // Local helper to call restore methods with the correct argument count
        $callRestore = function (string $tool) use ($session_id, $respect_base_content, $destination_course_code): void {
            $fn = 'restore_'.$tool;
            if (!method_exists($this, $fn)) {
                $this->dlog('Restore method not found for tool (skipping)', ['tool' => $tool, 'method' => $fn]);
                return;
            }

            $this->dlog('Starting tool restore', ['tool' => $tool, 'method' => $fn]);

            try {
                // call with the number of params the method actually accepts
                $args = [$session_id, $respect_base_content, $destination_course_code];
                $ref = new \ReflectionMethod($this, $fn);
                $argc = $ref->getNumberOfParameters();
                $callArgs = array_slice($args, 0, $argc);

                $this->dlog('Calling restore method', [
                    'tool' => $tool,
                    'method' => $fn,
                    'argc' => $argc,
                    'args' => $callArgs,
                ]);

                $this->{$fn}(...$callArgs);
            } catch (Throwable $e) {
                $this->dlog('Tool restore failed with exception', [
                    'tool' => $tool,
                    'method' => $fn,
                    'error' => $e->getMessage(),
                ]);
                $this->resetDoctrineIfClosed();
            }

            $this->dlog('Finished tool restore', ['tool' => $tool, 'method' => $fn]);
        };

        // Restore tools (except deferred ones)
        foreach ($toolsNow as $tool) {
            $callRestore((string) $tool);
        }

        // Restore deferred tools LAST (gradebook)
        foreach ($toolsLater as $tool) {
            $this->dlog('Starting deferred tool restore (must run last)', ['tool' => (string) $tool]);
            $callRestore((string) $tool);
            $this->dlog('Finished deferred tool restore', ['tool' => (string) $tool]);
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
        // Resolve the documents bucket robustly (RESOURCE_DOCUMENT vs document(s))
        $docBucketKey = null;
        $bucketCandidates = [];

        if (\defined('RESOURCE_DOCUMENT')) {
            $bucketCandidates[] = RESOURCE_DOCUMENT;
        }
        $bucketCandidates[] = 'documents';
        $bucketCandidates[] = 'document';

        foreach ($bucketCandidates as $cand) {
            if (isset($this->course->resources[$cand]) && \is_array($this->course->resources[$cand])) {
                $docBucketKey = $cand;
                break;
            }
        }

        if (null === $docBucketKey) {
            $this->dlog('restore_documents: no document bucket found', [
                'available_keys' => \is_array($this->course->resources ?? null) ? array_keys($this->course->resources) : [],
            ]);

            return;
        }

        /** @var array $docResources */
        $docResources =& $this->course->resources[$docBucketKey];

        if (empty($docResources)) {
            $this->dlog('restore_documents: document bucket is empty', ['bucket' => $docBucketKey]);

            return;
        }

        try {
            if (\method_exists($this->course, 'has_resources') && \defined('RESOURCE_DOCUMENT')) {
                if (!$this->course->has_resources(RESOURCE_DOCUMENT)) {
                    $this->dlog('restore_documents: resource map did not declare documents; restoring anyway', [
                        'bucket' => $docBucketKey,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $this->dlog('restore_documents: has_resources check failed; restoring anyway', [
                'bucket' => $docBucketKey,
                'err'    => $e->getMessage(),
            ]);
        }

        $courseInfo   = $this->destination_course_info;
        $docRepo      = Container::getDocumentRepository();
        $courseEntity = api_get_course_entity($this->destination_course_id);
        $session      = api_get_session_entity((int) $session_id);
        $group        = api_get_group_entity(0);

        $DBG = function (string $msg, array $ctx = []): void {
            error_log('[RESTORE:DOCS] '.$msg.(empty($ctx) ? '' : ' '.json_encode($ctx)));
        };

        // Normalize group context: the documents provider treats "no group" as rl.group IS NULL.
        $normalizeGroupCtx = function ($groupEntity) {
            if ($groupEntity instanceof \Chamilo\CourseBundle\Entity\CGroup) {
                if (method_exists($groupEntity, 'getIid') && (int) $groupEntity->getIid() > 0) {
                    return $groupEntity;
                }

                return null;
            }

            return null;
        };

        $groupCtx = $normalizeGroupCtx($group);

        // Resolve the import root deterministically:
        $resolveImportRoot = function (): string {
            $metaRoot = (string) ($this->course->resources['__meta']['archiver_root'] ?? '');
            if ($metaRoot !== '' && is_dir($metaRoot) && (is_file($metaRoot.'/course_info.dat') || is_dir($metaRoot.'/document'))) {
                $this->dlog('resolveImportRoot: using meta.archiver_root', ['dir' => $metaRoot]);

                return rtrim($metaRoot, '/');
            }

            $bp = (string) ($this->course->backup_path ?? '');
            if ($bp !== '') {
                if (is_dir($bp) && (is_file($bp.'/course_info.dat') || is_dir($bp.'/document'))) {
                    $this->dlog('resolveImportRoot: using backup_path (dir)', ['dir' => $bp]);

                    return rtrim($bp, '/');
                }

                if (is_file($bp) && preg_match('/\.zip$/i', $bp)) {
                    $base = dirname($bp);
                    $cands = glob($base.'/CourseArchiver_*', GLOB_ONLYDIR) ?: [];
                    if (empty($cands) && is_dir($base)) {
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
            'bucket'  => $docBucketKey,
            'files'   => \count($docResources),
            'session' => (int) $session_id,
            'mode'    => $copyMode ? 'copy' : 'import',
            'srcRoot' => $srcRoot,
        ]);

        // Ensure ResourceLink.parent is set for the current context (course+session+group null)
        $syncContextLinkParent = function (int $childDocIid, int $parentFolderDocIid) use ($docRepo, $courseEntity, $session, $groupCtx, $DBG): void {
            try {
                if ($childDocIid <= 0) {
                    return;
                }

                $em = Container::getEntityManager();

                /** @var \Chamilo\CoreBundle\Repository\ResourceLinkRepository $linkRepo */
                $linkRepo = $em->getRepository(\Chamilo\CoreBundle\Entity\ResourceLink::class);

                $child = $docRepo->find($childDocIid);
                if (!$child || null === $child->getResourceNode()) {
                    return;
                }

                $usergroup = null;
                $user = null;

                $childLink = $linkRepo->findLinkForResourceInContext($child, $courseEntity, $session, $groupCtx, $usergroup, $user);
                if (null === $childLink) {
                    return;
                }

                $parentLink = null;
                if ($parentFolderDocIid > 0) {
                    $parent = $docRepo->find($parentFolderDocIid);
                    if ($parent && null !== $parent->getResourceNode()) {
                        $parentLink = $linkRepo->findLinkForResourceInContext($parent, $courseEntity, $session, $groupCtx, $usergroup, $user);
                    }
                }

                if ($childLink->getParent() !== $parentLink) {
                    $childLink->setParent($parentLink);
                    $em->persist($childLink);
                    $em->flush();

                    $DBG('rl.parent.synced', ['iid' => $childDocIid, 'parentIid' => $parentFolderDocIid]);
                }
            } catch (\Throwable $e) {
                $DBG('rl.parent.sync.failed', ['iid' => $childDocIid, 'err' => $e->getMessage()]);
            }
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

        // Hide the /learning_path folder in Documents (draft visibility on ResourceLink).
        $forceDraftVisibilityForLearningPathRoot = function (int $docIid) use ($docRepo, $courseEntity, $session, $groupCtx, $DBG): void {
            try {
                $doc = $docRepo->find($docIid);
                if (!$doc || !method_exists($doc, 'getResourceNode') || null === $doc->getResourceNode()) {
                    return;
                }

                $em = Container::getEntityManager();
                $rlRepo = $em->getRepository(\Chamilo\CoreBundle\Entity\ResourceLink::class);

                $link = $rlRepo->findOneBy([
                    'resourceNode' => $doc->getResourceNode(),
                    'course'       => $courseEntity,
                    'session'      => $session,
                    'group'        => $groupCtx,
                ]);

                if ($link && method_exists($link, 'getVisibility') && method_exists($link, 'setVisibility')) {
                    if ((int) $link->getVisibility() !== 0) {
                        $link->setVisibility(0);
                        $em->flush();
                        $DBG('learning_path.hidden', ['iid' => $docIid]);
                    }
                }
            } catch (\Throwable $e) {
                $DBG('learning_path.hide.failed', ['iid' => $docIid, 'err' => $e->getMessage()]);
            }
        };

        // Top-level folders we never want to import as visible Documents (even in import mode)
        $alwaysSkipTopFolders = [];

        // Reserved containers that must not leak into destination when copying
        $reservedTopFolders = ['certificates', 'learnpaths'];

        // Normalize any incoming "rel"
        $normalizeRel = function (string $rel) use ($copyMode): string {
            $rel = '/'.ltrim($rel, '/');

            while (preg_match('#^/document/#i', $rel)) {
                $rel = preg_replace('#^/document/#i', '/', $rel, 1);
            }

            if ($copyMode) {
                if (preg_match('#^/certificates/[^/]+/[^/]+(?:/(.*))?$#i', $rel, $m)) {
                    $rest = $m[1] ?? '';

                    return $rest === '' ? '/' : '/'.ltrim($rest, '/');
                }
                if (preg_match('#^/certificates/(.*)$#i', $rel, $m)) {
                    return '/'.ltrim($m[1], '/');
                }
            }

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

            if ($copyMode && preg_match('#^/(?:learnpaths?|lp)/[^/]+/(.*)$#i', $rel, $m)) {
                return '/'.ltrim($m[1], '/');
            }
            if ($copyMode && preg_match('#^/(?:learnpaths?|lp)/(.*)$#i', $rel, $m)) {
                return '/'.ltrim($m[1], '/');
            }

            return $rel;
        };

        // Ensure a folder chain exists under Documents (skipping "document" as root)
        $ensureFolder = function (string $relPath) use ($docRepo, $courseEntity, $courseInfo, $session_id, $session, $groupCtx, $DBG, $syncContextLinkParent) {
            $rel = '/'.ltrim($relPath, '/');
            if ('/' === $rel || '' === $rel) {
                return 0;
            }

            $parts = array_values(array_filter(explode('/', trim($rel, '/'))));
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
                    $session,
                    $groupCtx
                );

                if ($existing) {
                    $parentId = method_exists($existing, 'getIid') ? $existing->getIid() : 0;
                    continue;
                }

                $oldParentId = $parentId;

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
                $parentId = method_exists($entity, 'getIid') ? $entity->getIid() : 0;

                if ($parentId > 0) {
                    $syncContextLinkParent((int) $parentId, (int) $oldParentId);
                }

                $DBG('ensureFolder:create', ['accum' => $accum, 'iid' => $parentId]);
            }

            return $parentId;
        };

        // Robust HTML detection
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

        // Create folders first
        $folders = [];

        foreach ($docResources as $k => $item) {
            if (FOLDER !== $item->file_type) {
                continue;
            }

            if ($copyMode && !empty($item->source_id)) {
                $rel = $getLogicalPathFromSource($item->source_id);
                if ($rel === '') {
                    $rel = '/'.ltrim(substr($item->path, 8), '/');
                }
            } else {
                $rel = '/'.ltrim(substr($item->path, 8), '/');
            }

            $rel = $normalizeRel($rel);

            if ($rel === '/' || $rel === '') {
                continue;
            }

            $firstSeg = strtolower((string) (explode('/', trim($rel, '/'))[0] ?? ''));
            if (\in_array($firstSeg, $alwaysSkipTopFolders, true)) {
                $this->dlog('restore_documents: skipping reserved folder', ['folder' => $firstSeg, 'rel' => $rel]);
                continue;
            }

            if ($copyMode && \in_array($firstSeg, $reservedTopFolders, true)) {
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
                $title = $seg;

                if ($i === \count($parts) - 1 && !empty($item->title)) {
                    $itemTitle = (string) $item->title;
                    if (0 === strcasecmp($itemTitle, $seg)) {
                        $title = $itemTitle;
                    }
                }

                $existing = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parentResource->getResourceNode(),
                    $courseEntity,
                    $session,
                    $groupCtx
                );

                if ($existing) {
                    $iid = method_exists($existing, 'getIid') ? $existing->getIid() : 0;
                } else {
                    $oldParentId = $parentId;
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

                    if ($iid > 0) {
                        $syncContextLinkParent((int) $iid, (int) $oldParentId);
                    }
                }

                $folders[$accum] = $iid;
                if ($i === \count($parts) - 1) {
                    $docResources[$k]->destination_id = $iid;
                }
                $parentId = $iid;

                if ('/learning_path' === strtolower($accum)) {
                    $forceDraftVisibilityForLearningPathRoot((int) $iid);
                }
            }
        }

        //    Pass A: non-HTML files (build URL maps)
        //    Pass B: HTML files (rewrite using URL maps)
        $urlMapByRel  = [];
        $urlMapByBase = [];

        $addToMaps = function (string $srcRelKey, int $iid) use (&$urlMapByRel, &$urlMapByBase, $docRepo): void {
            if ($srcRelKey === '' || $iid <= 0) {
                return;
            }

            $doc = $docRepo->find($iid);
            if (!$doc) {
                return;
            }

            $url = $docRepo->getResourceFileUrl($doc);
            if (!$url) {
                return;
            }

            if (!isset($urlMapByRel[$srcRelKey])) {
                $urlMapByRel[$srcRelKey] = $url;
            }

            $base = basename($srcRelKey);
            if ($base !== '' && (!isset($urlMapByBase[$base]) || !$urlMapByBase[$base])) {
                $urlMapByBase[$base] = $url;
            }
        };

        $resolveSrcPath = function ($item) use ($copyMode, $srcRoot, $docRepo): ?string {
            if ($copyMode) {
                $srcDoc = null;
                if (!empty($item->source_id)) {
                    $srcDoc = $docRepo->find((int) $item->source_id);
                }
                if (!$srcDoc) {
                    return null;
                }

                return $this->resourceFileAbsPathFromDocument($srcDoc) ?: null;
            }

            $p = $srcRoot.(string) $item->path;
            if (is_file($p) && is_readable($p)) {
                return $p;
            }

            $altRoot = rtrim((string) ($this->course->resources['__meta']['archiver_root'] ?? ''), '/').'/';
            if ($altRoot && $altRoot !== $srcRoot) {
                $p2 = $altRoot.(string) $item->path;
                if (is_file($p2) && is_readable($p2)) {
                    return $p2;
                }
            }

            return null;
        };

        $processOne = function (int $k, $item, bool $wantHtml) use (
            $copyMode,
            $docRepo,
            $courseEntity,
            $courseInfo,
            $session,
            $groupCtx,
            $session_id,
            &$folders,
            $ensureFolder,
            $getLogicalPathFromSource,
            $normalizeRel,
            $reservedTopFolders,
            $alwaysSkipTopFolders,
            $isHtmlFile,
            &$urlMapByRel,
            &$urlMapByBase,
            $addToMaps,
            $resolveSrcPath,
            $DBG,
            $syncContextLinkParent,
            &$docResources
        ): void {
            if (DOCUMENT !== $item->file_type) {
                return;
            }

            $srcRelKey = (string) ($item->path ?? '');

            if (!empty($item->destination_id)) {
                $existing = $docRepo->find((int) $item->destination_id);
                if ($existing) {
                    $addToMaps($srcRelKey, (int) $item->destination_id);

                    return;
                }
                $item->destination_id = 0;
            }

            $srcPath = $resolveSrcPath($item);
            if (!$srcPath || !is_file($srcPath) || !is_readable($srcPath)) {
                return;
            }

            $rawTitle = $item->title ?: basename((string) $item->path);
            $isHtml = $isHtmlFile($srcPath, (string) $rawTitle);

            if ($wantHtml !== $isHtml) {
                return;
            }

            if ($copyMode && !empty($item->source_id)) {
                $rel = $getLogicalPathFromSource($item->source_id);
                if ($rel === '') {
                    $rel = '/'.ltrim(substr((string) $item->path, 8), '/');
                }
            } else {
                $rel = '/'.ltrim(substr((string) $item->path, 8), '/');
            }

            $rel = $normalizeRel($rel);

            $firstSeg = strtolower((string) (explode('/', trim($rel, '/'))[0] ?? ''));
            if (\in_array($firstSeg, $alwaysSkipTopFolders, true)) {
                return;
            }
            if ($copyMode && \in_array($firstSeg, $reservedTopFolders, true)) {
                return;
            }

            $parentRel = rtrim(\dirname($rel), '/');
            $parentId  = $folders[$parentRel] ?? 0;
            if (!$parentId) {
                $parentId = $ensureFolder($parentRel);
                $folders[$parentRel] = $parentId;
            }

            $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;

            $baseTitle  = (string) $rawTitle;
            $finalTitle = $baseTitle;

            $findExisting = function (string $t) use ($docRepo, $parentRes, $courseEntity, $session, $groupCtx) {
                $e = $docRepo->findCourseResourceByTitle($t, $parentRes->getResourceNode(), $courseEntity, $session, $groupCtx);

                return $e && method_exists($e, 'getIid') ? (int) $e->getIid() : null;
            };

            $existsIid = $findExisting($finalTitle);
            if ($existsIid) {
                if (\defined('FILE_SKIP') && FILE_SKIP === (int) $this->file_option) {
                    $docResources[$k]->destination_id = (int) $existsIid;
                    $addToMaps($srcRelKey, (int) $existsIid);

                    return;
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

            if (basename($rel) !== $finalTitle) {
                $rel = ($parentRel === '' || $parentRel === '/')
                    ? '/'.$finalTitle
                    : $parentRel.'/'.$finalTitle;
            }

            $content  = '';
            $realPath = '';

            if ($isHtml) {
                $raw = @file_get_contents($srcPath) ?: '';
                if (\defined('UTF8_CONVERT') && UTF8_CONVERT) {
                    $raw = utf8_encode($raw);
                }

                $courseDir = (string) ($courseInfo['directory'] ?? $courseInfo['code'] ?? '');

                $rew = ChamiloHelper::rewriteLegacyCourseUrlsWithMap(
                    $raw,
                    $courseDir,
                    $urlMapByRel,
                    $urlMapByBase
                );

                $content = $rew['html'];

                $DBG('html.rewrite', [
                    'file'     => $finalTitle,
                    'replaced' => (int) ($rew['replaced'] ?? 0),
                    'misses'   => (int) ($rew['misses'] ?? 0),
                ]);
            } else {
                $realPath = $srcPath;
            }

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

            $iid = method_exists($entity, 'getIid') ? (int) $entity->getIid() : 0;

            $docResources[$k]->destination_id = $iid;
            $addToMaps($srcRelKey, $iid);

            if ($iid > 0) {
                $syncContextLinkParent((int) $iid, (int) $parentId);
            }
        };

        // Pass A: non-HTML
        foreach ($docResources as $k => $item) {
            $processOne((int) $k, $item, false);
        }

        // Pass B: HTML
        foreach ($docResources as $k => $item) {
            $processOne((int) $k, $item, true);
        }

        $this->dlog('restore_documents: end', [
            'bucket'     => $docBucketKey,
            'mapByRel'   => \count($urlMapByRel),
            'mapByBase'  => \count($urlMapByBase),
        ]);
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
        $bucketKeys = ['Forum_Category', 'forum_category', 'ForumCategory', 'FORUM_CATEGORY'];

        // Resolve the real bucket key (so we write destination_id back consistently).
        $bucketKey = null;
        foreach ($bucketKeys as $k) {
            if (isset($this->course->resources[$k]) && is_array($this->course->resources[$k])) {
                $bucketKey = $k;
                break;
            }
        }

        $bag = $bucketKey ? ($this->course->resources[$bucketKey] ?? []) : [];
        if (empty($bag)) {
            $this->dlog('restore_forum_category: empty bag');
            return;
        }

        $em = Database::getManager();
        $catRepo = Container::getForumCategoryRepository();

        /** @var CourseEntity|null $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);
        if (!$courseEntity instanceof CourseEntity) {
            $this->dlog('restore_forum_category: missing destination course entity', [
                'course_id' => (int) $this->destination_course_id,
            ]);
            return;
        }

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $session_id ? api_get_session_entity((int) $session_id) : null;

        $resolvedSid = (int) ($sessionEntity?->getId() ?? 0);

        $this->dlog('restore_forum_category: begin', [
            'count' => count($bag),
            'session_arg' => (int) $session_id,
            'resolved_sid' => $resolvedSid,
            'bucket' => (string) $bucketKey,
            'respect_base_content' => (bool) $respect_base_content,
            'policy' => (int) ($this->file_option ?? -1),
        ]);

        $findExistingCategory = function (string $title, ?SessionEntity $sess) use ($catRepo, $courseEntity): ?CForumCategory {
            $qb = $catRepo->createQueryBuilder('c')
                ->innerJoin('c.resourceNode', 'n')
                ->innerJoin('n.resourceLinks', 'l')
                ->andWhere('l.course = :course')->setParameter('course', $courseEntity)
                ->andWhere('c.title = :title')->setParameter('title', $title)
                ->setMaxResults(1);

            if ($sess) {
                $qb->andWhere('l.session = :session')->setParameter('session', $sess);
            } else {
                $qb->andWhere('l.session IS NULL');
            }

            return $qb->getQuery()->getOneOrNullResult();
        };

        $isTitleTaken = function (string $title, ?SessionEntity $sess) use ($findExistingCategory): bool {
            return (bool) $findExistingCategory($title, $sess);
        };

        foreach ($bag as $srcCatId => $res) {
            if (!is_object($res)) {
                continue;
            }
            if (!empty($res->destination_id)) {
                continue;
            }

            $obj = is_object($res->obj ?? null) ? $res->obj : (object) [];

            $title = trim((string) ($obj->cat_title ?? $obj->title ?? ''));
            if ($title === '') {
                $title = 'Forum category #'.(int) $srcCatId;
            }

            $comment = (string) ($obj->cat_comment ?? $obj->description ?? '');
            $comment = $this->rewriteHtmlForCourse($comment, (int) $session_id, '[forums.cat]');

            // same-session category first
            $existing = $findExistingCategory($title, $sessionEntity);

            // Optionally allow reuse from base content when restoring into a session
            if (!$existing && $respect_base_content && $sessionEntity) {
                $existing = $findExistingCategory($title, null);
            }

            if ($existing) {
                $destIid = (int) $existing->getIid();

                if ((int) ($this->file_option ?? FILE_RENAME) === FILE_SKIP) {
                    $this->course->resources[$bucketKey][$srcCatId] ??= new stdClass();
                    $this->course->resources[$bucketKey][$srcCatId]->destination_id = $destIid;
                    $res->destination_id = $destIid;

                    $this->dlog('restore_forum_category: exists -> skip', [
                        'src_cat_id' => (int) $srcCatId,
                        'dst_cat_iid' => $destIid,
                        'title' => $title,
                        'resolved_sid' => $resolvedSid,
                    ]);
                    continue;
                }

                if ((int) ($this->file_option ?? FILE_RENAME) === FILE_OVERWRITE) {
                    // Safe overwrite: update the existing category content instead of deleting (forums may depend on it).
                    $existing->setCatComment($comment);
                    $em->flush();

                    $this->course->resources[$bucketKey][$srcCatId] ??= new stdClass();
                    $this->course->resources[$bucketKey][$srcCatId]->destination_id = $destIid;
                    $res->destination_id = $destIid;

                    $this->dlog('restore_forum_category: exists -> updated (overwrite)', [
                        'src_cat_id' => (int) $srcCatId,
                        'dst_cat_iid' => $destIid,
                        'title' => $title,
                        'resolved_sid' => $resolvedSid,
                    ]);
                    continue;
                }

                // FILE_RENAME (default): generate a unique title
                $base = $title;
                $i = 1;
                $try = $base.' ('.$i.')';
                while ($isTitleTaken($try, $sessionEntity)) {
                    $i++;
                    $try = $base.' ('.$i.')';
                }
                $title = $try;
            }

            $cat = new CForumCategory();
            $cat->setTitle($title);
            $cat->setCatComment($comment);
            $cat->setParent($courseEntity);
            $cat->addCourseLink($courseEntity, $sessionEntity);

            // Use repository create to ensure resource node/link plumbing is created.
            $catRepo->create($cat);
            $em->flush();

            $destIid = (int) ($cat->getIid() ?? 0);

            $this->course->resources[$bucketKey][$srcCatId] ??= new stdClass();
            $this->course->resources[$bucketKey][$srcCatId]->destination_id = $destIid;
            $res->destination_id = $destIid;

            $this->dlog('restore_forum_category: created', [
                'src_cat_id' => (int) $srcCatId,
                'dst_cat_iid' => $destIid,
                'title' => $title,
                'resolved_sid' => $resolvedSid,
            ]);
        }

        $this->dlog('restore_forum_category: done', [
            'count' => count($bag),
            'resolved_sid' => $resolvedSid,
        ]);
    }

    /**
     * Restore forums and their topics/posts.
     */
    public function restore_forums(int $sessionId = 0): void
    {
        $forumBucketKeys = ['forum', 'Forum', 'Forum_Forum', 'FORUM'];

        // Resolve bucket key for forums (so we write destination_id back consistently).
        $forumBucketKey = null;
        foreach ($forumBucketKeys as $k) {
            if (isset($this->course->resources[$k]) && is_array($this->course->resources[$k])) {
                $forumBucketKey = $k;
                break;
            }
        }

        $forumsBag = $forumBucketKey ? ($this->course->resources[$forumBucketKey] ?? []) : [];
        if (empty($forumsBag)) {
            $this->dlog('restore_forums: empty forums bag');
            return;
        }

        $topicsBag = $this->getBag(['thread', 'Forum_Topic', 'forum_topic', 'FORUM_TOPIC']);
        $postsBag  = $this->getBag(['post', 'Forum_Post', 'forum_post', 'FORUM_POST']);

        // Index threads by source forum_id (one pass).
        $threadsByForum = [];
        foreach ($topicsBag as $srcThreadId => $topicRes) {
            if (!is_object($topicRes) || !is_object($topicRes->obj)) {
                continue;
            }
            $fid = (int) ($topicRes->obj->forum_id ?? 0);
            if ($fid > 0) {
                $threadsByForum[$fid][] = (int) $srcThreadId;
            }
        }

        // Index posts by source thread_id (one pass).
        $postsByThread = [];
        foreach ($postsBag as $srcPostId => $postRes) {
            if (!is_object($postRes) || !is_object($postRes->obj)) {
                continue;
            }
            $tid = (int) ($postRes->obj->thread_id ?? 0);
            if ($tid > 0) {
                $postsByThread[$tid][] = (int) $srcPostId;
            }
        }

        $em = Database::getManager();
        $catRepo = Container::getForumCategoryRepository();
        $forumRepo = Container::getForumRepository();

        /** @var CourseEntity|null $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);
        if (!$courseEntity instanceof CourseEntity) {
            $this->dlog('restore_forums: missing destination course entity', [
                'course_id' => (int) $this->destination_course_id,
            ]);
            return;
        }

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int) $sessionId) : null;
        $resolvedSid = (int) ($sessionEntity?->getId() ?? 0);

        // Build category map from the forum-category bucket (source cat iid => destination cat iid).
        $catBucketKeys = ['Forum_Category', 'forum_category', 'ForumCategory', 'FORUM_CATEGORY'];
        $catBucketKey = null;
        foreach ($catBucketKeys as $k) {
            if (isset($this->course->resources[$k]) && is_array($this->course->resources[$k])) {
                $catBucketKey = $k;
                break;
            }
        }

        $catBag = $catBucketKey ? ($this->course->resources[$catBucketKey] ?? []) : [];
        $catMap = [];
        foreach ($catBag as $srcCatId => $res) {
            $dst = (int) ($res->destination_id ?? 0);
            if ($dst > 0) {
                $catMap[(int) $srcCatId] = $dst;
            }
        }

        $this->dlog('restore_forums: begin', [
            'forums' => count($forumsBag),
            'session_arg' => (int) $sessionId,
            'resolved_sid' => $resolvedSid,
            'forum_bucket' => (string) $forumBucketKey,
            'cat_map_count' => count($catMap),
            'policy' => (int) ($this->file_option ?? -1),
        ]);

        $findExistingForum = function (CForumCategory $category, string $title) use ($forumRepo, $courseEntity, $sessionEntity): ?CForum {
            $qb = $forumRepo->createQueryBuilder('f')
                ->innerJoin('f.resourceNode', 'n')
                ->innerJoin('n.resourceLinks', 'l')
                ->andWhere('l.course = :course')->setParameter('course', $courseEntity)
                ->andWhere('f.forumCategory = :cat')->setParameter('cat', $category)
                ->andWhere('f.title = :title')->setParameter('title', $title)
                ->setMaxResults(1);

            if ($sessionEntity) {
                $qb->andWhere('l.session = :session')->setParameter('session', $sessionEntity);
            } else {
                $qb->andWhere('l.session IS NULL');
            }

            return $qb->getQuery()->getOneOrNullResult();
        };

        $ensureDefaultCategory = function () use ($catRepo, $em, $courseEntity, $sessionEntity): CForumCategory {
            // Reuse "General" if it already exists in this (course, session) scope.
            $qb = $catRepo->createQueryBuilder('c')
                ->innerJoin('c.resourceNode', 'n')
                ->innerJoin('n.resourceLinks', 'l')
                ->andWhere('l.course = :course')->setParameter('course', $courseEntity)
                ->andWhere('c.title = :title')->setParameter('title', 'General')
                ->setMaxResults(1);

            if ($sessionEntity) {
                $qb->andWhere('l.session = :session')->setParameter('session', $sessionEntity);
            } else {
                $qb->andWhere('l.session IS NULL');
            }

            $existing = $qb->getQuery()->getOneOrNullResult();
            if ($existing instanceof CForumCategory) {
                return $existing;
            }

            $cat = new CForumCategory();
            $cat->setTitle('General');
            $cat->setCatComment('');
            $cat->setParent($courseEntity);
            $cat->addCourseLink($courseEntity, $sessionEntity);

            $catRepo->create($cat);
            $em->flush();

            return $cat;
        };

        foreach ($forumsBag as $srcForumId => $forumRes) {
            if (!is_object($forumRes) || !is_object($forumRes->obj)) {
                continue;
            }

            if ((int) ($forumRes->destination_id ?? 0) > 0) {
                continue;
            }

            $p = (array) $forumRes->obj;

            // Resolve destination category.
            $dstCategory = null;
            $srcCatId = (int) ($p['forum_category'] ?? 0);

            if ($srcCatId > 0 && isset($catMap[$srcCatId])) {
                $dstCategory = $catRepo->find((int) $catMap[$srcCatId]);
            }

            if (!$dstCategory instanceof CForumCategory) {
                $dstCategory = $ensureDefaultCategory();
            }

            $title = trim((string) ($p['forum_title'] ?? ''));
            if ($title === '') {
                $title = 'Forum #'.(int) $srcForumId;
            }

            $forumComment = (string) ($p['forum_comment'] ?? '');
            $forumComment = $this->rewriteHtmlForCourse($forumComment, (int) $sessionId, '[forums.forum]');

            // Duplicate policy: match by (course+session link) + category + title
            $existingForum = $findExistingForum($dstCategory, $title);
            if ($existingForum) {
                $dstForumIid = (int) $existingForum->getIid();

                $policy = (int) ($this->file_option ?? FILE_RENAME);

                if ($policy === FILE_SKIP) {
                    $this->course->resources[$forumBucketKey][$srcForumId] ??= new stdClass();
                    $this->course->resources[$forumBucketKey][$srcForumId]->destination_id = $dstForumIid;
                    $forumRes->destination_id = $dstForumIid;

                    $this->dlog('restore_forums: exists -> skip', [
                        'src_forum_id' => (int) $srcForumId,
                        'dst_forum_iid' => $dstForumIid,
                        'title' => $title,
                        'resolved_sid' => $resolvedSid,
                    ]);
                    continue;
                }

                if ($policy === FILE_OVERWRITE) {
                    // Safe overwrite: update forum fields, avoid duplicating threads/posts.
                    $existingForum->setForumComment($forumComment);
                    $existingForum->setAllowAnonymous((int) ($p['allow_anonymous'] ?? 0));
                    $existingForum->setAllowEdit((int) ($p['allow_edit'] ?? 0));
                    $existingForum->setApprovalDirectPost((string) ($p['approval_direct_post'] ?? '0'));
                    $existingForum->setAllowAttachments((int) ($p['allow_attachments'] ?? 1));
                    $existingForum->setAllowNewThreads((int) ($p['allow_new_threads'] ?? 1));
                    $existingForum->setDefaultView((string) ($p['default_view'] ?? 'flat'));
                    $existingForum->setForumOfGroup((string) ($p['forum_of_group'] ?? '0'));
                    $existingForum->setForumGroupPublicPrivate((string) ($p['forum_group_public_private'] ?? 'public'));
                    $existingForum->setModerated((bool) ($p['moderated'] ?? false));
                    $existingForum->setStartTime($this->toUtcDateTime($p['start_time'] ?? null));
                    $existingForum->setEndTime($this->toUtcDateTime($p['end_time'] ?? null));

                    $em->flush();

                    $this->course->resources[$forumBucketKey][$srcForumId] ??= new stdClass();
                    $this->course->resources[$forumBucketKey][$srcForumId]->destination_id = $dstForumIid;
                    $forumRes->destination_id = $dstForumIid;

                    $this->dlog('restore_forums: exists -> updated (overwrite)', [
                        'src_forum_id' => (int) $srcForumId,
                        'dst_forum_iid' => $dstForumIid,
                        'title' => $title,
                        'resolved_sid' => $resolvedSid,
                    ]);
                    continue;
                }

                // FILE_RENAME: generate a unique title within same scope+category
                $base = $title;
                $i = 1;
                $try = $base.' ('.$i.')';
                while ($findExistingForum($dstCategory, $try)) {
                    $i++;
                    $try = $base.' ('.$i.')';
                }
                $title = $try;
            }

            $forum = new CForum();
            $forum->setTitle($title);
            $forum->setForumComment($forumComment);
            $forum->setForumCategory($dstCategory);
            $forum->setAllowAnonymous((int) ($p['allow_anonymous'] ?? 0));
            $forum->setAllowEdit((int) ($p['allow_edit'] ?? 0));
            $forum->setApprovalDirectPost((string) ($p['approval_direct_post'] ?? '0'));
            $forum->setAllowAttachments((int) ($p['allow_attachments'] ?? 1));
            $forum->setAllowNewThreads((int) ($p['allow_new_threads'] ?? 1));
            $forum->setDefaultView((string) ($p['default_view'] ?? 'flat'));
            $forum->setForumOfGroup((string) ($p['forum_of_group'] ?? '0'));
            $forum->setForumGroupPublicPrivate((string) ($p['forum_group_public_private'] ?? 'public'));
            $forum->setModerated((bool) ($p['moderated'] ?? false));
            $forum->setStartTime($this->toUtcDateTime($p['start_time'] ?? null));
            $forum->setEndTime($this->toUtcDateTime($p['end_time'] ?? null));
            $forum->setParent($dstCategory);

            $forum->addCourseLink($courseEntity, $sessionEntity);

            $forumRepo->create($forum);
            $em->flush();

            $dstForumIid = (int) ($forum->getIid() ?? 0);

            $this->course->resources[$forumBucketKey][$srcForumId] ??= new stdClass();
            $this->course->resources[$forumBucketKey][$srcForumId]->destination_id = $dstForumIid;
            $forumRes->destination_id = $dstForumIid;

            $this->dlog('restore_forums: created forum', [
                'src_forum_id' => (int) $srcForumId,
                'dst_forum_iid' => $dstForumIid,
                'dst_cat_iid' => (int) $dstCategory->getIid(),
                'title' => $title,
                'resolved_sid' => $resolvedSid,
            ]);

            // Restore topics/posts
            foreach (($threadsByForum[(int) $srcForumId] ?? []) as $srcThreadId) {
                $this->restore_topic(
                    (int) $srcThreadId,
                    $dstForumIid,
                    (int) $sessionId,
                    $postsByThread[$srcThreadId] ?? null
                );
            }
        }

        $this->dlog('restore_forums: done', [
            'forums' => count($forumsBag),
            'resolved_sid' => $resolvedSid,
        ]);
    }

    /**
     * Restore a forum topic (thread).
     */
    public function restore_topic(int $srcThreadId, int $dstForumId, int $sessionId = 0, ?array $srcPostIds = null): ?int
    {
        $topicsBag = $this->getBag(['thread', 'Forum_Topic', 'forum_topic', 'FORUM_TOPIC']);
        $topicRes = $topicsBag[$srcThreadId] ?? null;
        if (!$topicRes || !is_object($topicRes->obj)) {
            $this->dlog('restore_topic: missing topic object', ['src_thread_id' => $srcThreadId]);
            return null;
        }

        $em = Database::getManager();
        $forumRepo = Container::getForumRepository();
        $threadRepo = Container::getForumThreadRepository();
        $postRepo = Container::getForumPostRepository();

        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $sessionId);

        // Fallback poster user (consistent with your current approach)
        $user = api_get_user_entity($this->first_teacher_id);

        /** @var CForum|null $forum */
        $forum = $forumRepo->find($dstForumId);
        if (!$forum) {
            $this->dlog('restore_topic: destination forum not found', ['dst_forum_id' => $dstForumId]);
            return null;
        }

        $p = (array) $topicRes->obj;

        $threadDate = $this->toUtcDateTime($p['thread_date'] ?? null) ?: new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));

        $thread = (new CForumThread())
            ->setTitle((string) ($p['thread_title'] ?? "Thread #$srcThreadId"))
            ->setForum($forum)
            ->setUser($user)
            ->setThreadDate($threadDate)
            ->setThreadSticky((bool) ($p['thread_sticky'] ?? false))
            ->setThreadTitleQualify((string) ($p['thread_title_qualify'] ?? ''))
            ->setThreadQualifyMax((float) ($p['thread_qualify_max'] ?? 0))
            ->setThreadWeight((float) ($p['thread_weight'] ?? 0))
            ->setThreadPeerQualify((bool) ($p['thread_peer_qualify'] ?? false))
            ->setParent($forum)
            ->addCourseLink($course, $session);

        $threadRepo->create($thread);
        $em->flush();

        $dstThreadIid = (int) $thread->getIid();

        $this->course->resources['thread'][$srcThreadId] ??= new stdClass();
        $this->course->resources['thread'][$srcThreadId]->destination_id = $dstThreadIid;

        $this->dlog('restore_topic: created', [
            'src_thread_id' => $srcThreadId,
            'dst_thread_iid' => $dstThreadIid,
            'dst_forum_iid' => (int) $forum->getIid(),
        ]);

        // Restore posts
        $postsBag = $this->getBag(['post', 'Forum_Post', 'forum_post', 'FORUM_POST']);
        if (null === $srcPostIds) {
            // Fallback scan (only if caller didn't pass indexed post IDs)
            $srcPostIds = [];
            foreach ($postsBag as $srcPostId => $postRes) {
                if (!is_object($postRes) || !is_object($postRes->obj)) {
                    continue;
                }
                if ((int) ($postRes->obj->thread_id ?? 0) === $srcThreadId) {
                    $srcPostIds[] = (int) $srcPostId;
                }
            }
        }

        // Sort by post_date then id (stable)
        usort($srcPostIds, function (int $a, int $b) use ($postsBag): int {
            $pa = is_object($postsBag[$a]->obj ?? null) ? (array) $postsBag[$a]->obj : [];
            $pb = is_object($postsBag[$b]->obj ?? null) ? (array) $postsBag[$b]->obj : [];
            $da = (string) ($pa['post_date'] ?? '');
            $db = (string) ($pb['post_date'] ?? '');
            if ($da === $db) {
                return $a <=> $b;
            }
            return strcmp($da, $db);
        });

        foreach ($srcPostIds as $srcPostId) {
            $this->restore_post($srcPostId, $dstThreadIid, $dstForumId, $sessionId);
        }

        // Second pass: ensure parents are linked even if order was weird
        foreach ($srcPostIds as $srcPostId) {
            $postRes = $postsBag[$srcPostId] ?? null;
            if (!$postRes || !is_object($postRes->obj)) {
                continue;
            }

            $pp = (array) $postRes->obj;
            $srcParentId = (int) ($pp['post_parent_id'] ?? 0);
            if ($srcParentId <= 0) {
                continue;
            }

            $dstChildId = (int) (($this->course->resources['post'][$srcPostId]->destination_id ?? 0));
            $dstParentId = (int) (($this->course->resources['post'][$srcParentId]->destination_id ?? 0));

            if ($dstChildId > 0 && $dstParentId > 0) {
                $child = $postRepo->find($dstChildId);
                $parent = $postRepo->find($dstParentId);
                if ($child && $parent) {
                    $child->setPostParent($parent);
                    $em->persist($child);
                }
            }
        }
        $em->flush();

        // Update thread last post
        $last = $postRepo->findOneBy(['thread' => $thread], ['postDate' => 'DESC']);
        if ($last) {
            $thread->setThreadLastPost($last);
            $em->persist($thread);
            $em->flush();
        }

        return $dstThreadIid;
    }

    /**
     * Restore a forum post.
     */
    public function restore_post(int $srcPostId, int $dstThreadId, int $dstForumId, int $sessionId = 0): ?int
    {
        $postsBag = $this->getBag(['post', 'Forum_Post', 'forum_post', 'FORUM_POST']);
        $postRes = $postsBag[$srcPostId] ?? null;
        if (!$postRes || !is_object($postRes->obj)) {
            $this->dlog('restore_post: missing post object', ['src_post_id' => $srcPostId]);
            return null;
        }

        $em = Database::getManager();
        $forumRepo = Container::getForumRepository();
        $threadRepo = Container::getForumThreadRepository();
        $postRepo = Container::getForumPostRepository();

        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $sessionId);

        // Fallback poster user (consistent with current approach)
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

        $postDate = $this->toUtcDateTime($p['post_date'] ?? null) ?: new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));

        $status = (int) ($p['status'] ?? CForumPost::STATUS_VALIDATED);
        if (!in_array($status, [CForumPost::STATUS_VALIDATED, CForumPost::STATUS_WAITING_MODERATION, CForumPost::STATUS_REJECTED], true)) {
            $status = CForumPost::STATUS_VALIDATED;
        }

        $visible = (bool) ($p['visible'] ?? true);

        $post = (new CForumPost())
            ->setTitle((string) ($p['post_title'] ?? "Post #$srcPostId"))
            ->setPostText($postText)
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($user)
            ->setPostDate($postDate)
            ->setPostNotification((bool) ($p['post_notification'] ?? false))
            ->setVisible($visible)
            ->setStatus($status)
            ->setParent($thread)
            ->addCourseLink($course, $session);

        $postRepo->create($post);
        $em->flush();

        $this->course->resources['post'][$srcPostId] ??= new stdClass();
        $this->course->resources['post'][$srcPostId]->destination_id = (int) $post->getIid();
        $this->dlog('restore_post: created', [
            'src_post_id' => (int) $srcPostId,
            'dst_post_iid' => (int) $post->getIid(),
            'dst_thread_id' => (int) $thread->getIid(),
            'dst_forum_id' => (int) $forum->getIid(),
            'visible' => (int) $visible,
            'status' => (int) $status,
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
        $policy = $this->normalizeFilePolicy($this->file_option ?? null);

        if (0 === (int) $id) {
            $this->dlog('restore_link_category: source category id=0 (no category), returning 0', []);
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
            $this->dlog('restore_link_category: no category bucket found in course->resources', []);
            return 0;
        }

        $bucket = $resources[$catKey];

        // Build indexes to locate wrapper reliably
        $byIntKey = [];
        foreach ($bucket as $k => $wrap) {
            $ik = is_numeric($k) ? (int) $k : 0;
            if ($ik > 0) {
                $byIntKey[$ik] = $wrap;
            }
        }

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
            ]);
            return 0;
        }

        // Already mapped?
        if ((int) ($srcCat->destination_id ?? 0) > 0) {
            return (int) $srcCat->destination_id;
        }

        // Unwrap/normalize fields from mkLegacyItem wrapper
        $e = (isset($srcCat->obj) && \is_object($srcCat->obj)) ? $srcCat->obj : $srcCat;
        $title = trim((string) ($e->title ?? $e->category_title ?? ($srcCat->extra['title'] ?? '') ?? ''));
        if ('' === $title) {
            $title = 'Links';
        }
        $description = (string) ($e->description ?? ($srcCat->extra['description'] ?? '') ?? '');

        /** @var EntityManagerInterface $em */
        $em = Database::getManager();

        /** @var CLinkCategoryRepository $catRepo */
        $catRepo = Container::getLinkCategoryRepository();

        /** @var CourseEntity $course */
        $course = api_get_course_entity((int) $this->destination_course_id);

        /** @var SessionEntity|null $session */
        $session = $sessionId ? api_get_session_entity($sessionId) : null;

        // Look for existing category under same course + session scope (by title)
        $existing = null;
        $candidates = $catRepo->findBy(['title' => $title]);
        if (!empty($candidates)) {
            $courseNode = $course->getResourceNode();
            foreach ($candidates as $cand) {
                if (!$cand instanceof CLinkCategory) {
                    continue;
                }

                $node = method_exists($cand, 'getResourceNode') ? $cand->getResourceNode() : null;
                $parent = $node && method_exists($node, 'getParent') ? $node->getParent() : null;
                if (!$parent || !$courseNode || $parent->getId() !== $courseNode->getId()) {
                    continue;
                }

                $link = $cand->getFirstResourceLinkFromCourseSession($course, $session);
                if (null !== $link) {
                    $existing = $cand;
                    break;
                }
            }
        }

        if ($existing instanceof CLinkCategory) {
            if (1 === $policy) { // SKIP
                $destIid = (int) $existing->getIid();
                $srcCat->destination_id = $destIid;
                $this->dlog('restore_link_category: reuse (SKIP)', [
                    'src_cat_id' => $iid,
                    'dst_cat_id' => $destIid,
                    'title' => $title,
                ]);

                return $destIid;
            }

            if (3 === $policy) { // OVERWRITE => update existing
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

                $this->dlog('restore_link_category: overwritten', [
                    'src_cat_id' => $iid,
                    'dst_cat_id' => $destIid,
                    'title' => $title,
                ]);

                return $destIid;
            }

            // RENAME
            $base = $title;
            $n = 2;
            $candidate = $base.' ('.$n.')';
            while (true) {
                $dup = $catRepo->findBy(['title' => $candidate]);
                $taken = false;

                if (!empty($dup)) {
                    $courseNode = $course->getResourceNode();
                    foreach ($dup as $cand) {
                        if (!$cand instanceof CLinkCategory) {
                            continue;
                        }
                        $node = $cand->getResourceNode();
                        $parent = $node && method_exists($node, 'getParent') ? $node->getParent() : null;
                        if ($parent && $courseNode && $parent->getId() === $courseNode->getId()) {
                            $link = $cand->getFirstResourceLinkFromCourseSession($course, $session);
                            if (null !== $link) {
                                $taken = true;
                                break;
                            }
                        }
                    }
                }

                if (!$taken) {
                    break;
                }

                $n++;
                $candidate = $base.' ('.$n.')';
                if ($n > 5000) {
                    $this->dlog('restore_link_category: rename safeguard triggered, returning 0', [
                        'src_cat_id' => $iid,
                        'base_title' => $base,
                    ]);
                    return 0;
                }
            }

            $this->dlog('restore_link_category: duplicate detected, policy=RENAME', [
                'src_cat_id' => $iid,
                'from' => $title,
                'to' => $candidate,
            ]);

            $title = $candidate;
        }

        // Create new category
        $cat = (new CLinkCategory())
            ->setTitle($title)
            ->setDescription($description)
        ;

        // Required order: setParent() before addCourseLink()
        if (method_exists($cat, 'setParent')) {
            $cat->setParent($course);
        }
        if (method_exists($cat, 'addCourseLink')) {
            $cat->addCourseLink($course, $session);
        }

        $em->persist($cat);
        $em->flush();

        $destIid = (int) $cat->getIid();
        $srcCat->destination_id = $destIid;

        $this->dlog('restore_link_category: created', [
            'src_cat_id' => $iid,
            'dst_cat_id' => $destIid,
            'title' => $title,
            'bucket' => $catKey,
            'session_id' => $sessionId,
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

        $sessionId = (int) $session_id;
        $policy = $this->normalizeFilePolicy($this->file_option ?? null);

        $resources = $this->course->resources ?? [];
        $items = $resources[RESOURCE_LINK] ?? [];
        $count = \is_array($items) ? \count($items) : 0;

        $this->dlog('restore_links: begin', [
            'count' => $count,
            'policy' => $policy,
            'session_id' => $sessionId,
        ]);

        /** @var EntityManagerInterface $em */
        $em = Database::getManager();

        /** @var CLinkRepository $linkRepo */
        $linkRepo = Container::getLinkRepository();

        /** @var CLinkCategoryRepository $catRepo */
        $catRepo = Container::getLinkCategoryRepository();

        /** @var CourseEntity $course */
        $course = api_get_course_entity((int) $this->destination_course_id);

        /** @var SessionEntity|null $session */
        $session = $sessionId ? api_get_session_entity($sessionId) : null;

        if (!\is_array($items)) {
            $this->dlog('restore_links: invalid bucket type, aborting', [
                'bucket_type' => \gettype($items),
            ]);
            return;
        }

        // Duplicate finder for same course + session scope (title + url + category)
        $findDuplicate = function (string $t, string $u, ?CLinkCategory $cat) use ($linkRepo, $course, $session): ?CLink {
            $criteria = [
                'title' => $t,
                'url' => $u,
                'category' => ($cat instanceof CLinkCategory ? $cat : null),
            ];

            $candidates = $linkRepo->findBy($criteria);
            if (empty($candidates)) {
                return null;
            }

            $courseNode = $course->getResourceNode();
            foreach ($candidates as $cand) {
                if (!$cand instanceof CLink) {
                    continue;
                }

                $node = $cand->getResourceNode();
                $parent = $node && method_exists($node, 'getParent') ? $node->getParent() : null;
                if (!$parent || !$courseNode || $parent->getId() !== $courseNode->getId()) {
                    continue;
                }

                $link = $cand->getFirstResourceLinkFromCourseSession($course, $session);
                if (null !== $link) {
                    return $cand;
                }
            }

            return null;
        };

        foreach ($items as $oldLinkId => $link) {
            $oldLinkId = (int) $oldLinkId;

            if (!\is_object($link)) {
                $this->dlog('restore_links: skipping invalid legacy item (not object)', [
                    'src_link_id' => $oldLinkId,
                ]);
                continue;
            }

            $mapped = (int) ($link->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_links: already mapped, skipping', [
                    'src_link_id' => $oldLinkId,
                    'dst_link_id' => $mapped,
                ]);
                continue;
            }

            // mkLegacyItem payload should be in ->extra (keep direct props as fallback)
            $rawUrl = (string) ($link->url ?? ($link->extra['url'] ?? ''));
            $rawTitle = (string) ($link->title ?? ($link->extra['title'] ?? ''));
            $rawDesc = (string) ($link->description ?? ($link->extra['description'] ?? ''));
            $target = isset($link->target) ? (string) $link->target : (string) ($link->extra['target'] ?? '');

            // Prefer explicit category_id
            $catSrcId = (int) ($link->category_id ?? ($link->extra['category_id'] ?? 0));

            // Fallback to linked_resources (if present)
            if ($catSrcId <= 0 && isset($link->linked_resources['Link_Category'][0])) {
                $catSrcId = (int) $link->linked_resources['Link_Category'][0];
            }
            if ($catSrcId <= 0 && isset($link->linked_resources['link_category'][0])) {
                $catSrcId = (int) $link->linked_resources['link_category'][0];
            }

            $onHome = (bool) ($link->on_homepage ?? ($link->extra['on_homepage'] ?? false));

            $url = trim($rawUrl);
            $title = '' !== trim($rawTitle) ? trim($rawTitle) : $url;

            if ('' === $url) {
                $this->dlog('restore_links: skipped (empty URL)', [
                    'src_link_id' => $oldLinkId,
                    'extra_keys' => isset($link->extra) ? implode(',', array_keys((array) $link->extra)) : '',
                ]);
                continue;
            }

            // Resolve/create category (optional)
            $category = null;
            if ($catSrcId > 0) {
                $dstCatIid = (int) $this->restore_link_category($catSrcId, $sessionId);
                if ($dstCatIid > 0) {
                    $category = $catRepo->find($dstCatIid);
                } else {
                    $this->dlog('restore_links: category not available, using null', [
                        'src_link_id' => $oldLinkId,
                        'src_cat_id' => $catSrcId,
                    ]);
                }
            }

            // Dedupe (same course + session scope)
            $existing = $findDuplicate($title, $url, $category);

            if ($existing instanceof CLink) {
                if (1 === $policy) { // SKIP
                    $destIid = (int) $existing->getIid();
                    $link->destination_id = $destIid;

                    $this->dlog('restore_links: reuse (SKIP)', [
                        'src_link_id' => $oldLinkId,
                        'dst_link_id' => $destIid,
                        'title' => $title,
                        'url' => $url,
                    ]);
                    continue;
                }

                if (3 === $policy) { // OVERWRITE => update existing
                    $descHtml = $this->rewriteHtmlForCourse($rawDesc, $sessionId, '[links.link.overwrite]');

                    $existing
                        ->setUrl($url)
                        ->setTitle($title)
                        ->setDescription($descHtml)
                        ->setTarget((string) ($target ?? ''))
                        ->setCategory($category instanceof CLinkCategory ? $category : null)
                    ;

                    // Required order: setParent() before addCourseLink()
                    if (method_exists($existing, 'setParent')) {
                        $existing->setParent($course);
                    }
                    if (method_exists($existing, 'addCourseLink')) {
                        $existing->addCourseLink($course, $session);
                    }

                    $em->persist($existing);
                    $em->flush();

                    $destIid = (int) $existing->getIid();
                    $link->destination_id = $destIid;

                    $this->dlog('restore_links: overwritten', [
                        'src_link_id' => $oldLinkId,
                        'dst_link_id' => $destIid,
                        'title' => $title,
                        'url' => $url,
                    ]);

                    continue;
                }

                // RENAME
                $base = $title;
                $n = 2;
                $candidate = $base.' ('.$n.')';
                while ($findDuplicate($candidate, $url, $category)) {
                    $n++;
                    $candidate = $base.' ('.$n.')';
                    if ($n > 5000) {
                        $this->dlog('restore_links: rename safeguard triggered, skipping item', [
                            'src_link_id' => $oldLinkId,
                            'base_title' => $base,
                        ]);
                        continue 2;
                    }
                }

                $this->dlog('restore_links: duplicate detected, policy=RENAME', [
                    'src_link_id' => $oldLinkId,
                    'from' => $title,
                    'to' => $candidate,
                ]);

                $title = $candidate;
            }

            $descHtml = $this->rewriteHtmlForCourse($rawDesc, $sessionId, '[links.link.create]');

            // Create new link
            $entity = (new CLink())
                ->setUrl($url)
                ->setTitle($title)
                ->setDescription($descHtml)
                ->setTarget((string) ($target ?? ''))
            ;

            // Required order: setParent() before addCourseLink()
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

            $destIid = (int) $entity->getIid();
            $link->destination_id = $destIid;

            $this->dlog('restore_links: created', [
                'src_link_id' => $oldLinkId,
                'dst_link_id' => $destIid,
                'title' => $title,
                'url' => $url,
                'category' => $category ? $category->getTitle() : null,
            ]);

            // Homepage flag: build exports it, but restoring the actual "shortcut/homepage" placement
            // depends on how the platform stores it (not provided here). Keep only a trace log.
            if ($onHome) {
                $this->dlog('restore_links: on_homepage requested but no restore implementation provided, ignoring', [
                    'dst_link_id' => $destIid,
                    'title' => $title,
                ]);
            }
        }

        // Write back mutated items
        $this->course->resources[RESOURCE_LINK] = $items;

        $this->dlog('restore_links: end', [
            'policy' => $policy,
            'session_id' => $sessionId,
        ]);
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

        $bagKey = null;
        $candidates = [];

        if (\defined('RESOURCE_TOOL_INTRO')) {
            $candidates[] = RESOURCE_TOOL_INTRO;
        }

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
            'session_id' => $sessionId,
        ]);

        $em      = Database::getManager();
        $course  = api_get_course_entity($this->destination_course_id);
        $session = $sessionId ? api_get_session_entity($sessionId) : null;

        $toolRepo   = $em->getRepository(Tool::class);
        $cToolRepo  = $em->getRepository(CTool::class);
        $introRepo  = $em->getRepository(CToolIntro::class);

        foreach ($resources[$bagKey] as $rawId => $tIntro) {
            $toolKey = trim((string) ($tIntro->id ?? ''));
            if ('' === $toolKey || '0' === $toolKey) {
                $toolKey = (string) $rawId;
            }
            $alias = strtolower($toolKey);
            if ('homepage' === $alias || 'course_home' === $alias) {
                $toolKey = 'course_homepage';
            }

            $mapped = (int) ($tIntro->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_tool_intro: already mapped, skipping', [
                    'tool' => $toolKey,
                    'dst_id' => $mapped,
                ]);
                continue;
            }

            $introHtml = $this->rewriteHtmlForCourse(
                (string) ($tIntro->intro_text ?? ''),
                $sessionId,
                '[tool_intro.intro]'
            );

            // Resolve global Tool entity
            $toolEntity = $toolRepo->findOneBy(['title' => $toolKey]);
            if (!$toolEntity) {
                $toolEntity = $toolRepo->findOneBy(['title' => strtolower($toolKey)])
                    ?: $toolRepo->findOneBy(['title' => ucfirst(strtolower($toolKey))]);
            }
            if (!$toolEntity) {
                $this->dlog('restore_tool_intro: missing Tool entity, skipping', ['tool' => $toolKey]);
                continue;
            }

            // Ensure CTool exists for destination course/session context
            $cTool = $cToolRepo->findOneBy([
                'course'  => $course,
                'session' => $session,
                'title'   => $toolKey,
            ]);

            if (!$cTool) {
                // Try to reuse base tool position if it exists
                $position = 1;
                $baseTool = $cToolRepo->findOneBy([
                    'course'  => $course,
                    'session' => null,
                    'title'   => $toolKey,
                ]);
                if ($baseTool) {
                    $position = (int) $baseTool->getPosition();
                }

                $cTool = (new CTool())
                    ->setTool($toolEntity)
                    ->setTitle($toolKey)
                    ->setCourse($course)
                    ->setSession($session)
                    ->setPosition($position)
                    ->setParent($course)
                    ->addCourseLink($course, $session);
                $em->persist($cTool);
                $em->flush();

                $this->dlog('restore_tool_intro: CTool created', [
                    'tool' => $toolKey,
                    'ctool_id' => (int) $cTool->getIid(),
                    'position' => $position,
                ]);
            }

            // Find intro for this session-specific CTool
            $intro = $introRepo->findOneBy(['courseTool' => $cTool]);

            if ($intro) {
                if (FILE_SKIP === $this->file_option) {
                    $this->dlog('restore_tool_intro: reuse existing (SKIP)', [
                        'tool' => $toolKey,
                        'intro_id' => (int) $intro->getIid(),
                    ]);
                } else {
                    $intro->setIntroText($introHtml);

                    // Ensure session context link exists (important for future exports)
                    $intro->setParent($course);
                    $intro->addCourseLink($course, $session);

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
                $intro->addCourseLink($course, $session);

                $em->persist($intro);
                $em->flush();

                $this->dlog('restore_tool_intro: intro created', [
                    'tool' => $toolKey,
                    'intro_id' => (int) $intro->getIid(),
                ]);
            }

            // Mark destination id on the exported object
            $tIntro->destination_id = (int) $intro->getIid();
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
    public function restore_course_descriptions(int $sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_COURSEDESCRIPTION)) {
            return;
        }

        $sessionId = (int) $sessionId;

        $items = $this->course->resources[RESOURCE_COURSEDESCRIPTION] ?? [];
        $count = is_array($items) ? count($items) : 0;

        $this->dlog('restore_course_descriptions: begin', [
            'count' => $count,
            'session_id' => $sessionId,
        ]);

        /** @var EntityManagerInterface $em */
        $em = \Database::getManager();

        $repo = \Chamilo\CoreBundle\Framework\Container::getCourseDescriptionRepository();

        // ✅ IMPORTANT: Use the Doctrine entity FQCN explicitly (avoid CourseCopy\Course collision)
        $courseEntity = null;
        $destinationCourseId = (int) ($this->destination_course_id ?? 0);

        if ($destinationCourseId > 0) {
            $courseEntity = $em->find(CourseEntity::class, $destinationCourseId);
        }

        if (!$courseEntity instanceof CourseEntity) {
            $destinationCode = (string) ($this->destination_course_code ?? $this->destination_code ?? '');
            if ('' !== $destinationCode) {
                $info = api_get_course_info($destinationCode);
                $resolvedId = (int) ($info['real_id'] ?? $info['id'] ?? 0);
                if ($resolvedId > 0) {
                    $courseEntity = $em->find(CourseEntity::class, $resolvedId);
                }
            }
        }

        if (!$courseEntity instanceof CourseEntity) {
            $this->dlog('restore_course_descriptions: cannot resolve destination course entity, aborting', [
                'destination_course_id' => $destinationCourseId,
                'destination_course_code' => (string) ($this->destination_course_code ?? $this->destination_code ?? ''),
            ]);
            return;
        }

        $sessionEntity = null;
        if ($sessionId > 0) {
            $sessionEntity = api_get_session_entity($sessionId);
        }

        $groupEntity = api_get_group_entity();

        // Normalize file_option: 1=SKIP, 2=RENAME, 3=OVERWRITE
        $policy = 1;
        $rawPolicy = $this->file_option ?? null;

        if (is_int($rawPolicy) || ctype_digit((string) $rawPolicy)) {
            $policy = (int) $rawPolicy;
        } else {
            $raw = strtoupper(trim((string) $rawPolicy));
            if ('FILE_SKIP' === $raw) {
                $policy = 1;
            } elseif ('FILE_RENAME' === $raw) {
                $policy = 2;
            } elseif ('FILE_OVERWRITE' === $raw) {
                $policy = 3;
            }
        }

        if (defined('FILE_SKIP') && $rawPolicy === FILE_SKIP) {
            $policy = 1;
        } elseif (defined('FILE_RENAME') && $rawPolicy === FILE_RENAME) {
            $policy = 2;
        } elseif (defined('FILE_OVERWRITE') && $rawPolicy === FILE_OVERWRITE) {
            $policy = 3;
        }

        if (!in_array($policy, [1, 2, 3], true)) {
            $this->dlog('restore_course_descriptions: invalid file_option, defaulting to SKIP', [
                'raw' => $rawPolicy,
            ]);
            $policy = 1;
        }

        if (!is_array($items)) {
            $this->dlog('restore_course_descriptions: invalid resource list, aborting', []);
            return;
        }

        // Preload existing by type in SAME scope (session vs base)
        $existingByType = [];
        $existingTitles = [];

        try {
            $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);
            $existing = $qb->getQuery()->getResult();

            foreach ($existing as $e) {
                if (!$e instanceof CCourseDescription) {
                    continue;
                }

                // If restoring to a session, only consider items really linked to that session
                if ($sessionId > 0 && $sessionEntity) {
                    $link = $e->getFirstResourceLinkFromCourseSession($courseEntity, $sessionEntity);
                    if (null === $link) {
                        continue;
                    }
                }

                $t = (int) $e->getDescriptionType();
                if ($t < 1 || $t > 8) {
                    $t = CCourseDescription::TYPE_DESCRIPTION;
                }

                if (!isset($existingByType[$t])) {
                    $existingByType[$t] = $e;
                }

                $ttl = trim((string) $e->getTitle());
                if ('' !== $ttl) {
                    $existingTitles[$ttl] = true;
                }
            }
        } catch (\Throwable $e) {
            $this->dlog('restore_course_descriptions: failed to preload existing, continuing', [
                'error' => $e->getMessage(),
            ]);
            $existingByType = [];
            $existingTitles = [];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $renamed = 0;

        foreach ($items as $oldId => $cd) {
            $oldId = (int) $oldId;

            if (!is_object($cd)) {
                $this->dlog('restore_course_descriptions: skipping invalid item (not object)', [
                    'src_id' => $oldId,
                ]);
                continue;
            }

            $mapped = (int) ($cd->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_course_descriptions: already mapped, skipping', [
                    'src_id' => $oldId,
                    'dst_id' => $mapped,
                ]);
                continue;
            }

            $title = trim((string) ($cd->title ?? ''));
            if ('' === $title) {
                $title = 'Description';
            }

            $rawContent = (string) ($cd->content ?? '');
            $content = $this->rewriteHtmlForCourse($rawContent, $sessionId, '[course_description.content]');

            $type = (int) ($cd->description_type ?? CCourseDescription::TYPE_DESCRIPTION);
            if ($type < 1 || $type > 8) {
                $type = CCourseDescription::TYPE_DESCRIPTION;
            }

            $progress = (int) ($cd->progress ?? 0);
            if ($progress < 0) {
                $progress = 0;
            }

            $existing = $existingByType[$type] ?? null;

            if ($existing instanceof CCourseDescription) {
                if (1 === $policy) { // SKIP
                    $destIid = (int) $existing->getIid();
                    $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId]->destination_id = $destIid;

                    $this->dlog('restore_course_descriptions: reuse (SKIP)', [
                        'src_id' => $oldId,
                        'dst_id' => $destIid,
                        'type' => $type,
                        'title' => (string) $existing->getTitle(),
                    ]);

                    $skipped++;
                    continue;
                }

                if (3 === $policy) { // OVERWRITE
                    $existing->setSkipSearchIndex(true);

                    $existing
                        ->setTitle($title)
                        ->setContent($content)
                        ->setDescriptionType($type)
                        ->setProgress($progress)
                    ;

                    $existing->setParent($courseEntity);
                    $existing->addCourseLink($courseEntity, $sessionEntity, $groupEntity);

                    $em->persist($existing);
                    $em->flush();

                    $destIid = (int) $existing->getIid();
                    $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId]->destination_id = $destIid;

                    $this->dlog('restore_course_descriptions: overwrite', [
                        'src_id' => $oldId,
                        'dst_id' => $destIid,
                        'type' => $type,
                        'title' => (string) $existing->getTitle(),
                    ]);

                    $updated++;
                    continue;
                }

                // RENAME => create new with unique title
                $base = $title;
                $candidate = $base;
                $n = 2;
                while (isset($existingTitles[$candidate])) {
                    $candidate = $base.' ('.$n.')';
                    $n++;
                    if ($n > 5000) {
                        $this->dlog('restore_course_descriptions: rename safeguard triggered, skipping', [
                            'src_id' => $oldId,
                            'base_title' => $base,
                            'type' => $type,
                        ]);
                        continue 2;
                    }
                }

                if ($candidate !== $title) {
                    $this->dlog('restore_course_descriptions: duplicate detected, policy=RENAME', [
                        'src_id' => $oldId,
                        'from' => $title,
                        'to' => $candidate,
                        'type' => $type,
                    ]);
                    $renamed++;
                }

                $title = $candidate;
                $existingTitles[$title] = true;
            } else {
                $existingTitles[$title] = true;
            }

            $entity = new CCourseDescription();
            $entity->setSkipSearchIndex(true);

            $entity
                ->setTitle($title)
                ->setContent($content)
                ->setDescriptionType($type)
                ->setProgress($progress)
            ;

            $entity->setParent($courseEntity);
            $entity->addCourseLink($courseEntity, $sessionEntity, $groupEntity);

            $em->persist($entity);
            $em->flush();

            $destIid = (int) $entity->getIid();
            $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId]->destination_id = $destIid;

            $existingByType[$type] = $entity;

            $this->dlog('restore_course_descriptions: created', [
                'src_id' => $oldId,
                'dst_id' => $destIid,
                'type' => $type,
                'title' => $title,
            ]);

            $created++;
        }

        $this->dlog('restore_course_descriptions: end', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'renamed' => $renamed,
            'policy' => $policy,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Restore announcements into the destination course.
     */
    public function restore_announcements(int $sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_ANNOUNCEMENT)) {
            return;
        }

        $sessionId = (int) $sessionId;
        $items = $this->course->resources[RESOURCE_ANNOUNCEMENT] ?? [];
        $count = is_array($items) ? count($items) : 0;

        $this->dlog('restore_announcements: begin', [
            'count' => $count,
            'session_id' => $sessionId,
        ]);

        /** @var EntityManagerInterface $em */
        $em = \Database::getManager();

        // Resolve destination course entity safely (avoid CourseCopy\Course collisions).
        $courseEntity = null;
        $destinationCourseId = (int) ($this->destination_course_id ?? 0);

        if ($destinationCourseId > 0) {
            $courseEntity = $em->find(CourseEntity::class, $destinationCourseId);
        }

        if (!$courseEntity instanceof CourseEntity) {
            $destinationCode = (string) ($this->destination_course_code ?? $this->destination_code ?? '');
            if ('' !== $destinationCode) {
                $info = api_get_course_info($destinationCode);
                $resolvedId = (int) ($info['real_id'] ?? $info['id'] ?? 0);
                if ($resolvedId > 0) {
                    $courseEntity = $em->find(CourseEntity::class, $resolvedId);
                }
            }
        }

        if (!$courseEntity instanceof CourseEntity) {
            $this->dlog('restore_announcements: cannot resolve destination course entity, aborting', [
                'destination_course_id' => $destinationCourseId,
                'destination_course_code' => (string) ($this->destination_course_code ?? $this->destination_code ?? ''),
            ]);
            return;
        }

        $sessionEntity = $sessionId > 0 ? api_get_session_entity($sessionId) : null;
        $groupEntity = api_get_group_entity();

        $annRepo = \Chamilo\CoreBundle\Framework\Container::getAnnouncementRepository();

        // Normalize file_option (supports constants and numeric fallback 1/2/3).
        $policy = 1;
        $rawPolicy = $this->file_option ?? null;

        if (is_int($rawPolicy) || ctype_digit((string) $rawPolicy)) {
            $policy = (int) $rawPolicy;
        } else {
            $raw = strtoupper(trim((string) $rawPolicy));
            if ('FILE_SKIP' === $raw) {
                $policy = 1;
            } elseif ('FILE_RENAME' === $raw) {
                $policy = 2;
            } elseif ('FILE_OVERWRITE' === $raw) {
                $policy = 3;
            }
        }

        if (defined('FILE_SKIP') && $rawPolicy === FILE_SKIP) {
            $policy = 1;
        } elseif (defined('FILE_RENAME') && $rawPolicy === FILE_RENAME) {
            $policy = 2;
        } elseif (defined('FILE_OVERWRITE') && $rawPolicy === FILE_OVERWRITE) {
            $policy = 3;
        }

        if (!in_array($policy, [1, 2, 3], true)) {
            $this->dlog('restore_announcements: invalid file_option, defaulting to SKIP', [
                'raw' => $rawPolicy,
            ]);
            $policy = 1;
        }

        // Origin path inside extracted backup (ZIP/import mode). In copy mode this is empty.
        $originPath = '';
        if (!empty($this->course->backup_path)) {
            $originPath = rtrim((string) $this->course->backup_path, '/').'/upload/announcements';
        }

        // Preload existing announcements by title in this course/session
        $existingByTitle = [];
        try {
            $qb = $annRepo->getResourcesByCourse($courseEntity, $sessionEntity);
            $existing = $qb->getQuery()->getResult();

            foreach ($existing as $e) {
                if (!$e instanceof CAnnouncement) {
                    continue;
                }
                $t = trim((string) $e->getTitle());
                if ('' !== $t && !isset($existingByTitle[$t])) {
                    $existingByTitle[$t] = $e;
                }
            }
        } catch (\Throwable $e) {
            $this->dlog('restore_announcements: failed to preload existing announcements, continuing', [
                'error' => $e->getMessage(),
            ]);
            $existingByTitle = [];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $renamed = 0;
        $attachmentsCreated = 0;

        if (!is_array($items)) {
            $this->dlog('restore_announcements: invalid resource list, aborting', []);
            return;
        }

        foreach ($items as $oldId => $a) {
            $oldId = (int) $oldId;

            if (!is_object($a)) {
                $this->dlog('restore_announcements: skipping invalid item (not object)', [
                    'src_id' => $oldId,
                ]);
                continue;
            }

            $mapped = (int) ($a->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_announcements: already mapped, skipping', [
                    'src_id' => $oldId,
                    'dst_id' => $mapped,
                ]);
                continue;
            }

            $title = trim((string) ($a->title ?? ''));
            if ('' === $title) {
                $title = 'Announcement';
            }

            $contentHtml = (string) ($a->content ?? '');
            $rawDate = trim((string) ($a->date ?? ''));
            $emailSent = (bool) ($a->email_sent ?? false);

            $endDate = null;
            if ('' !== $rawDate) {
                try {
                    $endDate = new \DateTime($rawDate);
                } catch (\Throwable) {
                    $endDate = null;
                    $this->dlog('restore_announcements: invalid end date, ignoring', [
                        'src_id' => $oldId,
                        'raw_date' => $rawDate,
                    ]);
                }
            }

            $existing = $existingByTitle[$title] ?? null;

            if ($existing instanceof CAnnouncement) {
                if (1 === $policy) { // SKIP
                    $destId = (int) $existing->getIid();
                    $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId]->destination_id = $destId;

                    $this->dlog('restore_announcements: reuse (SKIP)', [
                        'src_id' => $oldId,
                        'dst_id' => $destId,
                        'title' => (string) $existing->getTitle(),
                    ]);

                    // Still ensure attachment rows exist (metadata only).
                    $attachmentsCreated += $this->restoreAnnouncementAttachments(
                        $a,
                        $existing,
                        $courseEntity,
                        $sessionEntity,
                        $groupEntity,
                        $originPath,
                        $em
                    );

                    $skipped++;
                    continue;
                }

                if (2 === $policy) { // RENAME
                    $base = $title;
                    $n = 2;
                    $candidate = $base.' ('.$n.')';
                    while (isset($existingByTitle[$candidate])) {
                        $n++;
                        $candidate = $base.' ('.$n.')';
                        if ($n > 5000) {
                            $this->dlog('restore_announcements: rename safeguard triggered, skipping', [
                                'src_id' => $oldId,
                                'base_title' => $base,
                            ]);
                            continue 2;
                        }
                    }

                    $this->dlog('restore_announcements: duplicate detected, policy=RENAME', [
                        'src_id' => $oldId,
                        'from' => $title,
                        'to' => $candidate,
                    ]);

                    $title = $candidate;
                    $renamed++;
                    $existing = null; // force create new
                }
            }

            $contentRewritten = $this->rewriteHtmlForCourse($contentHtml, $sessionId, '[announcements.content]');

            $entity = null;
            $mode = 'created';

            if ($existing instanceof CAnnouncement && 3 === $policy) {
                $entity = $existing;
                $mode = 'overwrite';
            } else {
                $entity = new CAnnouncement();
                $mode = 'created';
            }

            $entity->setTitle($title);
            $entity->setContent($contentRewritten);

            // Keep linkage consistent
            $entity->setParent($courseEntity);
            $entity->addCourseLink($courseEntity, $sessionEntity, $groupEntity);

            $entity->setEmailSent((bool) $emailSent);

            if ($endDate instanceof \DateTimeInterface) {
                $entity->setEndDate($endDate);
            } elseif ('overwrite' === $mode) {
                $entity->setEndDate(null);
            }

            $em->persist($entity);
            $em->flush();

            $destId = (int) $entity->getIid();
            $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId]->destination_id = $destId;

            $existingByTitle[$title] = $entity;

            if ('overwrite' === $mode) {
                $updated++;
            } else {
                $created++;
            }

            $this->dlog('restore_announcements: '.$mode, [
                'src_id' => $oldId,
                'dst_id' => $destId,
                'title' => $title,
            ]);

            $attachmentsCreated += $this->restoreAnnouncementAttachments(
                $a,
                $entity,
                $courseEntity,
                $sessionEntity,
                $groupEntity,
                $originPath,
                $em
            );
        }

        $this->dlog('restore_announcements: end', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'renamed' => $renamed,
            'attachments_created' => $attachmentsCreated,
            'policy' => $policy,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Restore announcement attachments (metadata).
     * - Creates missing CAnnouncementAttachment rows
     * - Avoids duplicates (path|filename)
     * - Tries to verify file existence inside the extracted package (import mode)
     */
    private function restoreAnnouncementAttachments(
        object                              $payload,
        CAnnouncement                       $announcement,
        CourseEntity                        $courseEntity,
        ?\Chamilo\CoreBundle\Entity\Session $sessionEntity,
                                            $groupEntity,
        string                              $originPath,
        EntityManagerInterface              $em
    ): int {
        $created = 0;

        // Build a normalized list of attachments from payload.
        $attachments = [];

        // New format: $payload->attachments = [ ['path'=>..., 'filename'=>..., 'size'=>..., 'comment'=>...], ... ]
        if (isset($payload->attachments) && is_array($payload->attachments)) {
            foreach ($payload->attachments as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $p = trim((string) ($row['path'] ?? ''));
                $f = trim((string) ($row['filename'] ?? ''));
                if ('' === $p || '' === $f) {
                    continue;
                }

                $attachments[] = [
                    'path' => $p,
                    'filename' => $f,
                    'size' => (int) ($row['size'] ?? 0),
                    'comment' => (string) ($row['comment'] ?? ''),
                ];
            }
        }

        // Legacy single attachment fields (optional)
        if (empty($attachments) && !empty($payload->attachment_path) && !empty($payload->attachment_filename)) {
            $attachments[] = [
                'path' => trim((string) $payload->attachment_path),
                'filename' => trim((string) $payload->attachment_filename),
                'size' => (int) ($payload->attachment_size ?? 0),
                'comment' => (string) ($payload->attachment_comment ?? ''),
            ];
        }

        if (empty($attachments)) {
            return 0;
        }

        // Existing keys in destination announcement
        $existingKeys = [];
        foreach ($announcement->getAttachments() as $exAtt) {
            if (!$exAtt instanceof \Chamilo\CourseBundle\Entity\CAnnouncementAttachment) {
                continue;
            }
            $k = trim((string) $exAtt->getPath()).'|'.trim((string) $exAtt->getFilename());
            if ('' !== $k) {
                $existingKeys[$k] = true;
            }
        }

        foreach ($attachments as $row) {
            $p = trim((string) $row['path']);
            $f = trim((string) $row['filename']);
            if ('' === $p || '' === $f) {
                continue;
            }

            $key = $p.'|'.$f;
            if (isset($existingKeys[$key])) {
                continue;
            }

            $att = new \Chamilo\CourseBundle\Entity\CAnnouncementAttachment();
            $att->setAnnouncement($announcement);
            $att->setPath($p);
            $att->setFilename($f);
            $att->setSize((int) ($row['size'] ?? 0));
            $att->setComment((string) ($row['comment'] ?? ''));

            $att->setParent($courseEntity);
            $att->addCourseLink($courseEntity, $sessionEntity, $groupEntity);

            // Validate file presence in import mode (ZIP extracted).
            if ('' !== $originPath) {
                $abs = rtrim($originPath, '/').'/'.ltrim($p, '/');

                $found = false;
                if (is_file($abs) && is_readable($abs)) {
                    // Some backups may store "path" as direct file.
                    $found = true;
                } elseif (is_dir($abs)) {
                    // Most common: path is a folder; file is inside.
                    $try = rtrim($abs, '/').'/'.$f;
                    if (is_file($try) && is_readable($try)) {
                        $found = true;
                    }
                }

                $this->dlog('restore_announcements: attachment '.($found ? 'found' : 'missing').' in package', [
                    'dst_announcement_id' => (int) $announcement->getIid(),
                    'path' => $p,
                    'filename' => $f,
                    'originPath' => $originPath,
                ]);
            }

            $em->persist($att);
            $announcement->addAttachment($att);

            $existingKeys[$key] = true;
            $created++;
        }

        if ($created > 0) {
            $em->flush();
        }

        return $created;
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

        /** @var CourseEntity $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);

        /** @var SessionEntity|null $sessionEntity */
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

            $linkSession = $respect_base_content
                ? $sessionEntity
                : (!empty($session_id) ? $sessionEntity : api_get_session_entity());

            $entity = (new CQuiz())
                ->setParent($courseEntity)
                ->addCourseLink($courseEntity, $linkSession, api_get_group_entity())
                ->setTitle((string) ($quiz->title ?? ''))
                ->setDescription($description)
                ->setType(isset($quiz->quiz_type) ? (int) $quiz->quiz_type : (int) ($quiz->type ?? 0))
                ->setRandom((int) ($quiz->random ?? 0))
                ->setRandomAnswers((bool) ($quiz->random_answers ?? false))
                ->setResultsDisabled((int) ($quiz->results_disabled ?? 0))
                ->setMaxAttempt((int) ($quiz->max_attempt ?? 1))
                ->setFeedbackType((int) ($quiz->feedback_type ?? 0))
                ->setExpiredTime((int) ($quiz->expired_time ?? 0))
                ->setReviewAnswers((int) ($quiz->review_answers ?? 0))
                ->setRandomByCategory((int) ($quiz->random_by_category ?? 0))
                ->setTextWhenFinished($textFinished)
                ->setTextWhenFinishedFailure($textFinishedKo)
                ->setDisplayCategoryName((int) ($quiz->display_category_name ?? 0))
                ->setSaveCorrectAnswers(isset($quiz->save_correct_answers) ? (int) $quiz->save_correct_answers : 0)
                ->setPropagateNeg((int) ($quiz->propagate_neg ?? 0))
                ->setHideQuestionTitle((bool) ($quiz->hide_question_title ?? false))
                ->setHideQuestionNumber((int) ($quiz->hide_question_number ?? 0))
                ->setPreventBackwards((int) ($quiz->prevent_backwards ?? 0))
                ->setShowPreviousButton((bool) ($quiz->show_previous_button ?? true))
                ->setHideAttemptsTable((bool) ($quiz->hide_attempts_table ?? false))
                ->setPageResultConfiguration((array) ($quiz->page_result_configuration ?? []))
                ->setDisplayChartDegreeCertainty((int) ($quiz->display_chart_degree_certainty ?? 0))
                ->setSendEmailChartDegreeCertainty((int) ($quiz->send_email_chart_degree_certainty ?? 0))
                ->setNotDisplayBalancePercentageCategorieQuestion((int) ($quiz->not_display_balance_percentage_categorie_question ?? 0))
                ->setDisplayChartDegreeCertaintyCategory((int) ($quiz->display_chart_degree_certainty_category ?? 0))
                ->setGatherQuestionsCategories((int) ($quiz->gather_questions_categories ?? 0))
                ->setDuration(isset($quiz->duration) ? (int) $quiz->duration : null)
                ->setStartTime(!empty($quiz->start_time) ? new DateTime((string) $quiz->start_time) : null)
                ->setEndTime(!empty($quiz->end_time) ? new DateTime((string) $quiz->end_time) : null)
            ;

            if (isset($quiz->access_condition) && '' !== (string) $quiz->access_condition) {
                $entity->setAccessCondition($rw((string) $quiz->access_condition, 'QZ.access'));
            }
            if (isset($quiz->pass_percentage) && '' !== (string) $quiz->pass_percentage && null !== $quiz->pass_percentage) {
                $entity->setPassPercentage((int) $quiz->pass_percentage);
            }
            if (isset($quiz->question_selection_type) && '' !== (string) $quiz->question_selection_type && null !== $quiz->question_selection_type) {
                $entity->setQuestionSelectionType((int) $quiz->question_selection_type);
            }
            if ('true' === api_get_setting('exercise.allow_notification_setting_per_exercise')) {
                $entity->setNotifications((string) ($quiz->notifications ?? ''));
            }
            if (isset($quiz->autolaunch)) {
                $entity->setAutoLaunch((bool) $quiz->autolaunch);
            }

            $em->persist($entity);
            $em->flush();

            $newQuizId = (int) $entity->getIid();
            $this->course->resources[RESOURCE_QUIZ][$id]->destination_id = $newQuizId;

            $qCount = isset($quiz->question_ids) ? \count((array) $quiz->question_ids) : 0;
            error_log('RESTORE_QUIZ: Created quiz iid='.$newQuizId.' title="'.(string) ($quiz->title ?? '').'" with '.$qCount.' question ids.');

            $order = 0;
            if (!empty($quiz->question_ids)) {
                foreach ($quiz->question_ids as $index => $question_id) {
                    $qid = $this->restore_quiz_question((int) $question_id, (int) $session_id);
                    if (!$qid) {
                        error_log('RESTORE_QUIZ: restore_quiz_question returned 0 for src_question_id='.(int) $question_id);
                        continue;
                    }

                    $question_order = !empty($quiz->question_orders[$index])
                        ? (int) $quiz->question_orders[$index]
                        : $order;

                    $order++;

                    $questionEntity = $em->getRepository(CQuizQuestion::class)->find($qid);
                    if (!$questionEntity) {
                        error_log('RESTORE_QUIZ: Question entity not found after insert. qid='.(int) $qid);
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
                error_log('RESTORE_QUIZ: No questions bound to quiz src_id='.(int) $id.' (title="'.(string) ($quiz->title ?? '').'").');
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

        $wrap = $resources[RESOURCE_QUIZQUESTION][$id] ?? null;
        if (!\is_object($wrap)) {
            error_log('RESTORE_QUESTION: Question not found in resources. src_id='.(int) $id);
            return 0;
        }

        // No method_exists: use destination_id as restore marker.
        $already = (int) ($this->course->resources[RESOURCE_QUIZQUESTION][$id]->destination_id ?? 0);
        if ($already > 0) {
            return $already;
        }

        /** @var CourseEntity $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = !empty($session_id) ? api_get_session_entity((int) $session_id) : api_get_session_entity();

        // Pick the actual payload object
        $question = isset($wrap->obj) ? $wrap->obj : $wrap;

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

        // Rewrite statement & description (+ feedback/extra)
        $questionText = $rw($question->question ?? '', 'QZ.Q.text');
        $descText = $rw($question->description ?? '', 'QZ.Q.desc');
        $feedbackText = $rw($question->feedback ?? '', 'QZ.Q.feedback');
        $extraText = $rw($question->extra ?? '', 'QZ.Q.extra');

        // Picture mapping (kept as in your code)
        $imageNewId = '';
        if (!empty($question->picture)) {
            if (isset($resources[RESOURCE_DOCUMENT]['image_quiz'][$question->picture])) {
                $imageNewId = (string) $resources[RESOURCE_DOCUMENT]['image_quiz'][$question->picture]['destination_id'];
            } elseif (isset($resources[RESOURCE_DOCUMENT][$question->picture])) {
                $imageNewId = (string) $resources[RESOURCE_DOCUMENT][$question->picture]->destination_id;
            }
        }

        $qType = (int) ($question->quiz_type ?? $question->type ?? 0);

        $entity = (new CQuizQuestion())
            ->setParent($courseEntity)
            ->addCourseLink($courseEntity, $sessionEntity, api_get_group_entity())
            ->setQuestion((string) $questionText)
            ->setDescription((string) $descText)
            ->setPonderation((float) ($question->ponderation ?? 0))
            ->setPosition((int) ($question->position ?? 1))
            ->setType($qType)
            ->setPicture((string) $imageNewId)
            ->setLevel((int) ($question->level ?? 1))
            ->setExtra((string) $extraText)
            ->setFeedback((string) $feedbackText)
            ->setQuestionCode((string) ($question->question_code ?? ''))
            ->setDuration(isset($question->duration) ? (int) $question->duration : null)
            ->setParentMediaId(isset($question->parent_media_id) ? (int) $question->parent_media_id : null)
        ;

        $em->persist($entity);
        $em->flush();

        $new_id = (int) $entity->getIid();
        if (!$new_id) {
            error_log('RESTORE_QUESTION: Failed to obtain new question iid for src_id='.(int) $id);
            return 0;
        }

        $answers = (array) ($question->answers ?? []);
        error_log('RESTORE_QUESTION: Creating question src_id='.(int) $id.' dst_iid='.$new_id.' answers_count='.\count($answers));

        // Matching family remap support
        $isMatchingFamily = \in_array($qType, [DRAGGABLE, MATCHING, MATCHING_DRAGGABLE], true);
        $correctMapDstToSrc = [];     // dstAnsId => srcCorrectRef
        $allSrcAnswersById = [];      // srcAnsId => rewritten text
        $dstAnswersByIdText = [];     // dstAnsId => rewritten text

        if ($isMatchingFamily) {
            foreach ($answers as $a) {
                if (isset($a['id'])) {
                    $allSrcAnswersById[(int) $a['id']] = $rw($a['answer'] ?? '', 'QZ.Q.ans.all');
                }
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

            if (isset($a['correct']) && null !== $a['correct'] && '' !== (string) $a['correct']) {
                $ans->setCorrect((int) $a['correct']);
            }

            $em->persist($ans);
            $em->flush();

            if ($isMatchingFamily) {
                $dstId = (int) $ans->getIid();
                $correctMapDstToSrc[$dstId] = $a['correct'] ?? null;
                $dstAnswersByIdText[$dstId] = (string) $ansText;
            }
        }

        // Remap correct references (matching family)
        if ($isMatchingFamily && !empty($correctMapDstToSrc)) {
            foreach ($entity->getAnswers() as $dstAns) {
                $dstAid = (int) $dstAns->getIid();
                $srcRef = $correctMapDstToSrc[$dstAid] ?? null;
                if (null === $srcRef) {
                    continue;
                }

                $srcRef = (int) $srcRef;
                if (!isset($allSrcAnswersById[$srcRef])) {
                    continue;
                }

                $needle = $allSrcAnswersById[$srcRef];
                $newDst = null;

                foreach ($dstAnswersByIdText as $candId => $txt) {
                    if ($txt === $needle) {
                        $newDst = (int) $candId;
                        break;
                    }
                }

                if (null !== $newDst) {
                    $dstAns->setCorrect((int) $newDst);
                    $em->persist($dstAns);
                }
            }
            $em->flush();
        }

        // MULTIPLE_ANSWER_TRUE_FALSE: restore options (supports array or object formats)
        if (\defined('MULTIPLE_ANSWER_TRUE_FALSE') && MULTIPLE_ANSWER_TRUE_FALSE === $qType) {
            $newOptByOld = [];

            $opts = $question->question_options ?? [];
            if (is_iterable($opts)) {
                foreach ($opts as $optItem) {
                    $oldId = null;
                    $name = '';
                    $pos = 0;

                    if (is_array($optItem)) {
                        $oldId = isset($optItem['id']) ? (int) $optItem['id'] : null;
                        $name = (string) ($optItem['name'] ?? '');
                        $pos = (int) ($optItem['position'] ?? 0);
                    } elseif (is_object($optItem)) {
                        $optObj = $optItem->obj ?? $optItem;
                        $oldId = isset($optObj->id) ? (int) $optObj->id : null;
                        $name = (string) ($optObj->name ?? '');
                        $pos = (int) ($optObj->position ?? 0);
                    }

                    if (null === $oldId) {
                        continue;
                    }

                    $optTitle = $rw($name, 'QZ.Q.opt');

                    $optEntity = (new CQuizQuestionOption())
                        ->setQuestion($entity)
                        ->setTitle((string) $optTitle)
                        ->setPosition((int) $pos)
                    ;

                    $em->persist($optEntity);
                    $em->flush();

                    $newOptByOld[$oldId] = (int) $optEntity->getIid();
                }

                foreach ($entity->getAnswers() as $dstAns) {
                    $corr = $dstAns->getCorrect();
                    if (null !== $corr && isset($newOptByOld[(int) $corr])) {
                        $dstAns->setCorrect((int) $newOptByOld[(int) $corr]);
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

        foreach ($resources[RESOURCE_SURVEY] as $legacySurveyId => $surveyWrap) {
            if (isset($surveyWrap->destination_id) && (int) $surveyWrap->destination_id > 0) {
                $this->debug && error_log(
                    'COURSE_DEBUG: restore_surveys: already restored, skipping legacy_survey_id='
                    .(int) $legacySurveyId.' dst_id='.(int) $surveyWrap->destination_id
                );
                continue;
            }

            // Prefer payload in ->obj when present (consistent with other tools)
            $surveyObj = (isset($surveyWrap->obj) && \is_object($surveyWrap->obj)) ? $surveyWrap->obj : $surveyWrap;

            try {
                $code = (string) ($surveyObj->code ?? '');
                $lang = (string) ($surveyObj->lang ?? '');

                $title = $rewrite($surveyObj->title ?? '', ':survey.title');
                $subtitle = $rewrite($surveyObj->subtitle ?? '', ':survey.subtitle');
                $intro = $rewrite($surveyObj->intro ?? '', ':survey.intro');
                $surveyThanks = $rewrite($surveyObj->surveythanks ?? '', ':survey.thanks');
                $inviteMail = $rewrite($surveyObj->invite_mail ?? '', ':survey.invite_mail');
                $reminderMail = $rewrite($surveyObj->reminder_mail ?? '', ':survey.reminder_mail');
                $mailSubject = $rewrite($surveyObj->mail_subject ?? '', ':survey.mail_subject');

                $accessCondition = '';
                if (isset($surveyObj->access_condition) && '' !== (string) $surveyObj->access_condition) {
                    $accessCondition = $rewrite((string) $surveyObj->access_condition, ':survey.access_condition');
                }

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
                $surveyType = isset($surveyObj->survey_type) ? (int) $surveyObj->survey_type : null;
                $showFormProfile = isset($surveyObj->show_form_profile) ? (int) $surveyObj->show_form_profile : null;
                $formFields = isset($surveyObj->form_fields) ? (string) $surveyObj->form_fields : null;
                $duration = isset($surveyObj->duration) ? (int) $surveyObj->duration : null;
                $surveyVersion = isset($surveyObj->survey_version) ? (string) $surveyObj->survey_version : '';
                $isMandatory = isset($surveyObj->is_mandatory) ? (bool) $surveyObj->is_mandatory : false;

                $existing = null;
                try {
                    $candidate = $surveyRepo->findOneBy(['code' => $code, 'lang' => $lang]);
                    if ($candidate instanceof CSurvey) {
                        $link = $candidate->getFirstResourceLinkFromCourseSession($courseEntity, $sessionEntity);
                        if (null !== $link) {
                            $existing = $candidate;
                        }
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
                    ->setIsShared((string) ($surveyObj->is_shared ?? '0'))
                    ->setTemplate((string) ($surveyObj->template ?? 'template'))
                    ->setIntro($intro)
                    ->setSurveythanks($surveyThanks)
                    ->setCreationDate($creationDate)
                    ->setInvited(0)
                    ->setAnswered(0)
                    ->setInviteMail((string) $inviteMail)
                    ->setReminderMail((string) $reminderMail)
                    ->setMailSubject((string) $mailSubject)
                    ->setOneQuestionPerPage($onePerPage)
                    ->setShuffle($shuffle)
                    ->setAnonymous($anonymous)
                    ->setDisplayQuestionNumber($displayQuestionNumber)
                    ->setIsMandatory($isMandatory)
                ;

                // Avoid setting "now" when null (entity setters default null->now).
                if ($availFrom instanceof DateTime) {
                    $newSurvey->setAvailFrom($availFrom);
                }
                if ($availTill instanceof DateTime) {
                    $newSurvey->setAvailTill($availTill);
                }

                if ('' !== $accessCondition) {
                    $newSurvey->setAccessCondition($accessCondition);
                }

                if ('' !== $surveyVersion) {
                    $newSurvey->setSurveyVersion($surveyVersion);
                }

                if (null !== $surveyType) {
                    $newSurvey->setSurveyType($surveyType);
                }
                if (null !== $showFormProfile) {
                    $newSurvey->setShowFormProfile($showFormProfile);
                }
                if (null !== $formFields) {
                    $newSurvey->setFormFields($formFields);
                }
                if (null !== $duration) {
                    $newSurvey->setDuration($duration);
                }
                if (null !== $visibleResults) {
                    $newSurvey->setVisibleResults($visibleResults);
                }

                // Required by AbstractResource::addCourseLink()
                $newSurvey->setParent($courseEntity);
                $newSurvey->addCourseLink($courseEntity, $sessionEntity);

                $em->persist($newSurvey);
                $em->flush();

                $newId = (int) $newSurvey->getIid();

                // Mark restored (this is the key to prevent second copy)
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

        // No method_exists: use destination_id as restoration marker.
        if (isset($qWrap->destination_id) && (int) $qWrap->destination_id > 0) {
            return (int) $qWrap->destination_id;
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
            $type = 'open';
            if (isset($q->survey_question_type) && '' !== (string) $q->survey_question_type) {
                $type = (string) $q->survey_question_type;
            } elseif (isset($q->type) && '' !== (string) $q->type) {
                $type = (string) $q->type;
            }

            $question = new CSurveyQuestion();
            $question
                ->setSurvey($survey)
                ->setSurveyQuestion((string) $questionText)
                ->setSurveyQuestionComment((string) $commentText)
                ->setType($type)
                ->setDisplay((string) ($q->display ?? 'vertical'))
                ->setSort((int) ($q->sort ?? 0))
                ->setIsMandatory((bool) ($q->is_required ?? false))
            ;

            if (isset($q->shared_question_id) && null !== $q->shared_question_id) {
                $question->setSharedQuestionId((int) $q->shared_question_id);
            }
            if (isset($q->max_value) && null !== $q->max_value) {
                $question->setMaxValue((int) $q->max_value);
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
                    ->setOptionText((string) $optText)
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

        foreach ($resources[RESOURCE_LEARNPATH_CATEGORY] as $id => $item) {
            // Support both: $item->object (entity) or plain exported payload fields.
            $title = '';

            if (isset($item->object) && $item->object instanceof CLpCategory) {
                $title = trim((string) $item->object->getTitle());
            } else {
                $title = trim((string) ($item->title ?? $item->name ?? ''));
            }

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
            $pkg = $this->findScormPackageForEntry($sc);
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
            } else {
                $lpTitle = (string) ($sc->name ?? $lpTitle);
                if ('' === trim($lpTitle)) {
                    $lpTitle = 'Untitled';
                }
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
        // Ensure we have a resources snapshot (either internal or from the course)
        $this->ensureDepsBagsFromSnapshot();
        $all = $this->getAllResources(); // Uses snapshot if available

        $docBag  = $all[RESOURCE_DOCUMENT] ?? [];
        $quizBag = $all[RESOURCE_QUIZ] ?? [];
        $linkBag = $all[RESOURCE_LINK] ?? [];
        $survBag = $all[RESOURCE_SURVEY] ?? [];
        $workBag = $all[RESOURCE_WORK] ?? [];
        $forumB  = $all['forum'] ?? [];

        $this->dlog('LP: deps (after ensure/snapshot)', [
            'document'            => \count($docBag),
            'quiz'                => \count($quizBag),
            'link'                => \count($linkBag),
            'student_publication' => \count($workBag),
            'survey'              => \count($survBag),
            'forum'               => \count($forumB),
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
            'document'            => ['document', RESOURCE_DOCUMENT],
            'quiz'                => ['quiz', RESOURCE_QUIZ],
            'exercise'            => ['quiz', RESOURCE_QUIZ],
            'link'                => ['link', RESOURCE_LINK],
            'weblink'             => ['link', RESOURCE_LINK],
            'url'                 => ['link', RESOURCE_LINK],
            'work'                => ['works', RESOURCE_WORK],
            'student_publication' => ['works', RESOURCE_WORK],
            'survey'              => ['survey', RESOURCE_SURVEY],
            'forum'               => ['forum', 'forum'],
            // scorm/sco are not resolved here
        ];

        // ID collectors per dependency kind
        $need = [
            RESOURCE_DOCUMENT => [],
            RESOURCE_QUIZ     => [],
            RESOURCE_LINK     => [],
            RESOURCE_WORK     => [],
            RESOURCE_SURVEY   => [],
            'forum'           => [],
        ];

        $takeId = static function ($v) {
            if (null === $v || '' === $v) {
                return null;
            }

            return \ctype_digit((string) $v) ? (int) $v : null;
        };

        // Collect deps from LP items
        foreach ($lpBag as $srcLpId => $lpWrap) {
            $items = \is_array($lpWrap->items ?? null) ? $lpWrap->items : [];
            foreach ($items as $it) {
                $itype = \strtolower((string) ($it['item_type'] ?? ''));
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
                $kk = \strtolower((string) $k);
                if (isset($type2bags[$kk])) {
                    [, $bag] = $type2bags[$kk];
                } else {
                    // Sometimes exporter uses bag names directly (document/quiz/link/works/survey/forum)
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
            if (!empty($this->course->resources[RESOURCE_QUIZ]) && !isset($this->course->resources[RESOURCE_QUIZQUESTION])) {
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

            // Minimal forum support if LP points to forums
            if (!empty($this->course->resources['forum'])) {
                foreach (['Forum_Category', 'thread', 'post'] as $k) {
                    if (!isset($this->course->resources[$k]) && isset($all[$k])) {
                        $this->course->resources[$k] = $all[$k];
                    }
                }
            }
        }

        $this->dlog('LP: minimal deps prepared', [
            'document'            => \count($this->course->resources[RESOURCE_DOCUMENT] ?? []),
            'quiz'                => \count($this->course->resources[RESOURCE_QUIZ] ?? []),
            'link'                => \count($this->course->resources[RESOURCE_LINK] ?? []),
            'student_publication' => \count($this->course->resources[RESOURCE_WORK] ?? []),
            'survey'              => \count($this->course->resources[RESOURCE_SURVEY] ?? []),
            'forum'               => \count($this->course->resources['forum'] ?? []),
        ]);

        // Restore ONLY those minimal bags ---
        if (!empty($this->course->resources[RESOURCE_DOCUMENT])) {
            $this->restore_documents($session_id, (bool) $respect_base_content, $destination_course_code);
        }
        if (!empty($this->course->resources[RESOURCE_QUIZ])) {
            $this->restore_quizzes($session_id, (bool) $respect_base_content);
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

        // Create LP + items with resolved paths to new destination iids ---
        $em = Database::getManager();

        $courseEnt = api_get_course_entity((int) $this->destination_course_id);
        if (!$courseEnt) {
            $this->dlog('LP: destination course entity not found', ['course_id' => (int) $this->destination_course_id]);

            return;
        }

        $sessionEnt = api_get_session_entity((int) $session_id);
        $lpRepo = Container::getLpRepository();
        $lpItemRepo = Container::getLpItemRepository();
        $docRepo = Container::getDocumentRepository();
        $lpCatRepo = method_exists(Container::class, 'getLpCategoryRepository') ? Container::getLpCategoryRepository() : null;

        // Optional repos for title fallbacks (defensive)
        $quizRepo  = method_exists(Container::class, 'getQuizRepository') ? Container::getQuizRepository() : null;
        $linkRepo  = method_exists(Container::class, 'getLinkRepository') ? Container::getLinkRepository() : null;
        $forumRepo = method_exists(Container::class, 'getForumRepository') ? Container::getForumRepository() : null;
        $surveyRepo = method_exists(Container::class, 'getSurveyRepository') ? Container::getSurveyRepository() : null;
        $workRepo  = method_exists(Container::class, 'getStudentPublicationRepository') ? Container::getStudentPublicationRepository() : null;

        $getDst = function (string $bag, $legacyId): int {
            $wrap = $this->course->resources[$bag][$legacyId] ?? null;

            return ($wrap && isset($wrap->destination_id)) ? (int) $wrap->destination_id : 0;
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

                return ($hit && method_exists($hit, 'getIid')) ? (int) $hit->getIid() : 0;
            } catch (\Throwable $e) {
                $this->dlog('LP: document title lookup failed', ['title' => $title, 'err' => $e->getMessage()]);

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

                    return ($hit && method_exists($hit, 'getIid')) ? (int) $hit->getIid() : 0;
                } catch (\Throwable $e) {
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

                    return ($hit && method_exists($hit, 'getIid')) ? (int) $hit->getIid() : 0;
                } catch (\Throwable $e) {
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
                        : ($forumRepo->findOneBy(['forum_title' => $title]) ?? $forumRepo->findOneBy(['title' => $title]));

                    return ($hit && method_exists($hit, 'getIid')) ? (int) $hit->getIid() : 0;
                } catch (\Throwable $e) {
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

                    return ($hit && method_exists($hit, 'getIid')) ? (int) $hit->getIid() : 0;
                } catch (\Throwable $e) {
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

                    return ($hit && method_exists($hit, 'getIid')) ? (int) $hit->getIid() : 0;
                } catch (\Throwable $e) {
                    return 0;
                }
            },
        ];

        $resolvePath = function (array $it) use ($getDst, $findDocIidByTitle, $findByTitle): string {
            $itype = \strtolower((string) ($it['item_type'] ?? ''));
            $raw = $it['path'] ?? ($it['ref'] ?? ($it['identifierref'] ?? ''));
            $title = \trim((string) ($it['title'] ?? ''));

            switch ($itype) {
                case 'document':
                    if (\ctype_digit((string) $raw)) {
                        $nid = $getDst(RESOURCE_DOCUMENT, (int) $raw);

                        return $nid ? (string) $nid : '';
                    }
                    if (\is_string($raw) && \str_starts_with((string) $raw, 'document/')) {
                        return (string) $raw;
                    }
                    $maybe = $findDocIidByTitle('' !== $title ? $title : (string) $raw);

                    return $maybe ? (string) $maybe : '';

                case 'quiz':
                case 'exercise':
                    $id = \ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst(RESOURCE_QUIZ, $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['quiz']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                case 'link':
                case 'weblink':
                case 'url':
                    $id = \ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst(RESOURCE_LINK, $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['link']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                case 'work':
                case 'student_publication':
                    $id = \ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst(RESOURCE_WORK, $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['work']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                case 'survey':
                    $id = \ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst(RESOURCE_SURVEY, $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['survey']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                case 'forum':
                    $id = \ctype_digit((string) $raw) ? (int) $raw : 0;
                    $nid = $id ? $getDst('forum', $id) : 0;
                    if ($nid) {
                        return (string) $nid;
                    }
                    $nid = $findByTitle['forum']('' !== $title ? $title : (string) $raw);

                    return $nid ? (string) $nid : '';

                default:
                    // Keep whatever was exported (SCORM/SCO, directories, unknown types, etc.)
                    return (string) $raw;
            }
        };

        foreach ($lpBag as $srcLpId => $lpWrap) {
            // Support both object and array wrappers
            $title  = (string) ($lpWrap->title ?? $lpWrap->name ?? ('LP '.$srcLpId));
            $desc   = (string) ($lpWrap->description ?? '');
            $lpType = (int) ($lpWrap->lp_type ?? $lpWrap->type ?? ($lpWrap->lpType ?? 1));

            $lp = (new CLp())
                ->setLpType($lpType)
                ->setTitle($title)
                ->setParent($courseEnt);

            // Optional: hydrate more LP fields (safe via method_exists)
            if (method_exists($lp, 'setRef') && isset($lpWrap->ref)) {
                $lp->setRef((string) $lpWrap->ref);
            }
            if (method_exists($lp, 'setPath') && isset($lpWrap->path)) {
                $lp->setPath((string) $lpWrap->path);
            }
            if (method_exists($lp, 'setDescription')) {
                $lp->setDescription($desc);
            }
            if (method_exists($lp, 'setAutolaunch') && isset($lpWrap->autolaunch)) {
                $lp->setAutolaunch((int) $lpWrap->autolaunch);
            }
            if (method_exists($lp, 'setPreventReinit') && isset($lpWrap->prevent_reinit)) {
                $lp->setPreventReinit((bool) $lpWrap->prevent_reinit);
            }
            if (method_exists($lp, 'setForceCommit') && isset($lpWrap->force_commit)) {
                $lp->setForceCommit((bool) $lpWrap->force_commit);
            }
            if (method_exists($lp, 'setDefaultViewMod') && isset($lpWrap->default_view_mod)) {
                $lp->setDefaultViewMod((string) $lpWrap->default_view_mod);
            }
            if (method_exists($lp, 'setDefaultEncoding') && isset($lpWrap->default_encoding)) {
                $lp->setDefaultEncoding((string) $lpWrap->default_encoding);
            }
            if (method_exists($lp, 'setContentLocal') && isset($lpWrap->content_local)) {
                $lp->setContentLocal((string) $lpWrap->content_local);
            }
            if (method_exists($lp, 'setContentMaker') && isset($lpWrap->content_maker)) {
                $lp->setContentMaker((string) $lpWrap->content_maker);
            }
            if (method_exists($lp, 'setContentLicense') && isset($lpWrap->content_license)) {
                $lp->setContentLicense((string) $lpWrap->content_license);
            }
            if (method_exists($lp, 'setJsLib') && isset($lpWrap->js_lib)) {
                $lp->setJsLib((string) $lpWrap->js_lib);
            }
            if (method_exists($lp, 'setDebug') && isset($lpWrap->debug)) {
                $lp->setDebug((bool) $lpWrap->debug);
            }
            if (method_exists($lp, 'setAuthor') && isset($lpWrap->author)) {
                $lp->setAuthor((string) $lpWrap->author);
            }
            if (method_exists($lp, 'setUseMaxScore') && isset($lpWrap->use_max_score)) {
                $lp->setUseMaxScore((int) $lpWrap->use_max_score);
            }

            // Optional: set category if categories were restored and repo exists
            if ($lpCatRepo && method_exists($lp, 'setCategory')) {
                $srcCatId = (int) ($lpWrap->category_id ?? 0);
                if ($srcCatId > 0) {
                    $dstCatId = $getDst(RESOURCE_LEARNPATH_CATEGORY, $srcCatId);
                    if ($dstCatId > 0) {
                        $catEnt = $lpCatRepo->find($dstCatId);
                        if ($catEnt) {
                            $lp->setCategory($catEnt);
                        }
                    }
                }
            }

            // Create links (kept defensive)
            if (method_exists($lp, 'addCourseLink')) {
                $lp->addCourseLink($courseEnt, $sessionEnt);
            }

            $lpRepo->createLp($lp);
            $em->flush();

            $this->course->resources[RESOURCE_LEARNPATH][$srcLpId]->destination_id = (int) $lp->getIid();

            // Root container item (created by repo/service)
            $root = $lpItemRepo->getRootItem($lp->getIid());
            if (!$root) {
                $this->dlog('LP: root item not found, skip items creation', ['lp_iid' => (int) $lp->getIid(), 'title' => $title]);

                continue;
            }

            $items = \is_array($lpWrap->items ?? null) ? $lpWrap->items : [];

            // Compatibility mode: if exporter provides "level/lvl", keep the old logic.
            $hasLevel = false;
            foreach ($items as $it) {
                if (isset($it['level']) || isset($it['lvl'])) {
                    $hasLevel = true;
                    break;
                }
            }

            $createdCount = 0;

            if ($hasLevel) {
                // ---- Old logic (level-based), kept for backward compatibility ----
                $parents = [0 => $root];
                $order = 0;

                foreach ($items as $it) {
                    $lvl = (int) ($it['level'] ?? $it['lvl'] ?? 0);
                    $pItem = $parents[$lvl] ?? $root;

                    $itype   = (string) ($it['item_type'] ?? 'dir');
                    $itTitle = (string) ($it['title'] ?? '');
                    $path    = $resolvePath($it);
                    $ref     = (string) ($it['ref'] ?? ($it['identifier'] ?? ''));

                    $item = (new CLpItem())
                        ->setLp($lp)
                        ->setParent($pItem)
                        ->setItemType($itype)
                        ->setTitle($itTitle)
                        ->setPath($path)
                        ->setRef($ref)
                        ->setDisplayOrder(++$order);

                    // Optional fields (only if present in payload)
                    if (isset($it['description']) && method_exists($item, 'setDescription')) {
                        $item->setDescription((string) $it['description']);
                    }
                    if (isset($it['min_score']) && method_exists($item, 'setMinScore')) {
                        $item->setMinScore((float) $it['min_score']);
                    }
                    if (array_key_exists('max_score', $it) && method_exists($item, 'setMaxScore')) {
                        $item->setMaxScore(null !== $it['max_score'] ? (float) $it['max_score'] : 0.0);
                    }
                    if (array_key_exists('mastery_score', $it) && method_exists($item, 'setMasteryScore')) {
                        if (null !== $it['mastery_score']) {
                            $item->setMasteryScore((float) $it['mastery_score']);
                        }
                    }
                    if (isset($it['parameters'])) {
                        $item->setParameters((string) $it['parameters']);
                    }
                    if (isset($it['prerequisite'])) {
                        $item->setPrerequisite((string) $it['prerequisite']);
                    }
                    if (isset($it['launch_data'])) {
                        $item->setLaunchData((string) $it['launch_data']);
                    }
                    if (isset($it['audio']) && method_exists($item, 'setAudio')) {
                        $item->setAudio((string) $it['audio']);
                    }

                    $lpItemRepo->create($item);
                    $parents[$lvl + 1] = $item;
                    $createdCount++;
                }

                $em->flush();
            } else {
                // Index items by legacy "id" and group children by legacy "parent_item_id"
                $byId = [];
                $children = [];

                foreach ($items as $it) {
                    $legacyItemId = (int) ($it['id'] ?? 0);
                    if ($legacyItemId <= 0) {
                        continue;
                    }

                    $byId[$legacyItemId] = $it;

                    $pLegacyId = (int) ($it['parent_item_id'] ?? 0);
                    if (!isset($children[$pLegacyId])) {
                        $children[$pLegacyId] = [];
                    }
                    $children[$pLegacyId][] = $legacyItemId;
                }

                // Sort children lists by "display_order" if present (fallback to legacy id)
                foreach ($children as $pId => $list) {
                    \usort($list, function (int $a, int $b) use ($byId): int {
                        $da = (int) ($byId[$a]['display_order'] ?? $a);
                        $db = (int) ($byId[$b]['display_order'] ?? $b);

                        return $da <=> $db;
                    });
                    $children[$pId] = $list;
                }

                $createdMap = []; // legacyItemId => CLpItem
                $fallbackOrder = 0;

                $createBranch = function (int $parentLegacyId, CLpItem $parentEntity) use (
                    &$createBranch,
                    &$createdMap,
                    &$children,
                    &$byId,
                    &$fallbackOrder,
                    $lp,
                    $lpItemRepo,
                    $resolvePath
                ): void {
                    $kids = $children[$parentLegacyId] ?? [];
                    foreach ($kids as $legacyChildId) {
                        $it = $byId[$legacyChildId] ?? null;
                        if (!$it) {
                            continue;
                        }

                        $itype   = (string) ($it['item_type'] ?? 'dir');
                        $itTitle = (string) ($it['title'] ?? '');
                        $path    = $resolvePath($it);
                        $ref     = (string) ($it['ref'] ?? ($it['identifier'] ?? ''));

                        $order = isset($it['display_order']) ? (int) $it['display_order'] : (++$fallbackOrder);

                        $item = (new CLpItem())
                            ->setLp($lp)
                            ->setParent($parentEntity)
                            ->setItemType($itype)
                            ->setTitle($itTitle)
                            ->setPath($path)
                            ->setRef($ref)
                            ->setDisplayOrder($order);

                        // Optional fields (only if present in payload)
                        if (isset($it['description']) && method_exists($item, 'setDescription')) {
                            $item->setDescription((string) $it['description']);
                        }
                        if (isset($it['min_score']) && method_exists($item, 'setMinScore')) {
                            $item->setMinScore((float) $it['min_score']);
                        }
                        if (array_key_exists('max_score', $it) && method_exists($item, 'setMaxScore')) {
                            $item->setMaxScore(null !== $it['max_score'] ? (float) $it['max_score'] : 0.0);
                        }
                        if (array_key_exists('mastery_score', $it) && method_exists($item, 'setMasteryScore')) {
                            if (null !== $it['mastery_score']) {
                                $item->setMasteryScore((float) $it['mastery_score']);
                            }
                        }
                        if (isset($it['parameters'])) {
                            $item->setParameters((string) $it['parameters']);
                        }
                        if (isset($it['prerequisite'])) {
                            $item->setPrerequisite((string) $it['prerequisite']);
                        }
                        if (isset($it['launch_data'])) {
                            $item->setLaunchData((string) $it['launch_data']);
                        }
                        if (isset($it['audio']) && method_exists($item, 'setAudio')) {
                            $item->setAudio((string) $it['audio']);
                        }

                        $lpItemRepo->create($item);

                        $createdMap[$legacyChildId] = $item;

                        // Recurse into its children (if any)
                        $createBranch($legacyChildId, $item);
                    }
                };

                // Build the tree from legacy parent id = 0 (root level in your payload)
                $createBranch(0, $root);

                $createdCount = \count($createdMap);

                $em->flush();
            }

            $this->dlog('LP: items created', [
                'lp_iid' => (int) $lp->getIid(),
                'items'  => (int) $createdCount,
                'title'  => $title,
            ]);
        }
    }

    /**
     * Normalize file policy to: 1=SKIP, 2=RENAME, 3=OVERWRITE.
     * Accepts ints, numeric strings, "FILE_SKIP|FILE_RENAME|FILE_OVERWRITE", or constants.
     */
    private function normalizeFilePolicy($rawPolicy): int
    {
        $policy = 1;

        if (\is_int($rawPolicy) || \ctype_digit((string) $rawPolicy)) {
            $policy = (int) $rawPolicy;
        } else {
            $raw = \strtoupper(\trim((string) $rawPolicy));
            if ('FILE_SKIP' === $raw) {
                $policy = 1;
            } elseif ('FILE_RENAME' === $raw) {
                $policy = 2;
            } elseif ('FILE_OVERWRITE' === $raw) {
                $policy = 3;
            }
        }

        if (\defined('FILE_SKIP') && $rawPolicy === FILE_SKIP) {
            $policy = 1;
        } elseif (\defined('FILE_RENAME') && $rawPolicy === FILE_RENAME) {
            $policy = 2;
        } elseif (\defined('FILE_OVERWRITE') && $rawPolicy === FILE_OVERWRITE) {
            $policy = 3;
        }

        if (!\in_array($policy, [1, 2, 3], true)) {
            $this->dlog('normalizeFilePolicy: invalid file_option, defaulting to SKIP', [
                'raw' => $rawPolicy,
            ]);
            $policy = 1;
        }

        return $policy;
    }

    /**
     * Restore Glossary resources for the destination course.
     *
     * @param mixed $sessionId
     */
    public function restore_glossary($sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_GLOSSARY)) {
            $this->dlog('restore_glossary: no glossary resources in backup, skipping', []);
            return;
        }

        $sessionId = (int) $sessionId;
        $policy = $this->normalizeFilePolicy($this->file_option ?? null);

        /** @var EntityManagerInterface $em */
        $em = Database::getManager();

        /** @var CourseEntity $course */
        $course = api_get_course_entity((int) $this->destination_course_id);

        /** @var SessionEntity|null $session */
        $session = $sessionId ? api_get_session_entity($sessionId) : null;

        /** @var CGlossaryRepository $repo */
        $repo = $em->getRepository(CGlossary::class);

        $items = $this->course->resources[RESOURCE_GLOSSARY] ?? [];
        $count = \is_array($items) ? \count($items) : 0;

        $this->dlog('restore_glossary: begin', [
            'count' => $count,
            'policy' => $policy,
            'session_id' => $sessionId,
        ]);

        if (!\is_array($items)) {
            $this->dlog('restore_glossary: invalid bucket type, aborting', [
                'bucket_type' => \gettype($items),
            ]);
            return;
        }

        // Duplicate finder within same course + session scope (by title)
        $findDuplicate = function (string $title) use ($repo, $course, $session): ?CGlossary {
            $candidates = $repo->findBy(['title' => $title]);
            if (empty($candidates)) {
                return null;
            }

            $courseNode = $course->getResourceNode();
            foreach ($candidates as $cand) {
                if (!$cand instanceof CGlossary) {
                    continue;
                }

                $node = $cand->getResourceNode();
                $parent = $node && method_exists($node, 'getParent') ? $node->getParent() : null;
                if (!$parent || !$courseNode || $parent->getId() !== $courseNode->getId()) {
                    continue;
                }

                // Must match the same course/session link scope
                $link = $cand->getFirstResourceLinkFromCourseSession($course, $session);
                if (null !== $link) {
                    return $cand;
                }
            }

            return null;
        };

        $setMapped = function (int $legacyId, int $destIid) use (&$items): void {
            if (isset($items[$legacyId]) && \is_object($items[$legacyId])) {
                $items[$legacyId]->destination_id = $destIid;
                return;
            }

            // Fallback if legacy entry isn't an object
            $items[$legacyId] ??= new \stdClass();
            $items[$legacyId]->destination_id = $destIid;
        };

        foreach ($items as $legacyId => $gls) {
            $legacyId = (int) $legacyId;

            try {
                if (!\is_object($gls)) {
                    $this->dlog('restore_glossary: skipping invalid legacy item (not object)', [
                        'src_id' => $legacyId,
                    ]);
                    continue;
                }

                $mapped = (int) ($gls->destination_id ?? 0);
                if ($mapped > 0) {
                    $this->dlog('restore_glossary: already mapped, skipping', [
                        'src_id' => $legacyId,
                        'dst_id' => $mapped,
                    ]);
                    continue;
                }

                // build_glossary created a Glossary legacy object (not mkLegacyItem),
                // so prefer ->name / ->title / ->description, but keep fallbacks.
                $title = \trim((string) ($gls->name ?? $gls->title ?? ($gls->extra['title'] ?? '')));
                if ('' === $title) {
                    $title = 'Glossary term';
                }

                $desc = (string) ($gls->description ?? ($gls->extra['description'] ?? ''));

                // Rewrite HTML always
                $desc = $this->rewriteHtmlForCourse($desc, $sessionId, '[glossary.term]');

                $existing = $findDuplicate($title);

                if ($existing instanceof CGlossary) {
                    if (1 === $policy) { // SKIP
                        $destIid = (int) $existing->getIid();
                        $setMapped($legacyId, $destIid);

                        $this->dlog('restore_glossary: reuse (SKIP)', [
                            'src_id' => $legacyId,
                            'dst_id' => $destIid,
                            'title' => $title,
                        ]);
                        continue;
                    }

                    if (3 === $policy) { // OVERWRITE => update existing (do NOT delete)
                        $existing->setDescription($desc);

                        // Ensure linkage for this scope
                        if (method_exists($existing, 'setParent')) {
                            $existing->setParent($course);
                        }
                        if (method_exists($existing, 'addCourseLink')) {
                            $existing->addCourseLink($course, $session);
                        }

                        $em->persist($existing);
                        $em->flush();

                        $destIid = (int) $existing->getIid();
                        $setMapped($legacyId, $destIid);

                        $this->dlog('restore_glossary: overwritten', [
                            'src_id' => $legacyId,
                            'dst_id' => $destIid,
                            'title' => $title,
                        ]);
                        continue;
                    }

                    // RENAME
                    if (2 === $policy) {
                        $base = $title;
                        $n = 2;
                        $candidate = $base.' ('.$n.')';
                        while ($findDuplicate($candidate)) {
                            $n++;
                            $candidate = $base.' ('.$n.')';
                            if ($n > 5000) {
                                $this->dlog('restore_glossary: rename safeguard triggered, skipping item', [
                                    'src_id' => $legacyId,
                                    'base_title' => $base,
                                ]);
                                continue 2;
                            }
                        }
                        $this->dlog('restore_glossary: duplicate detected, policy=RENAME', [
                            'src_id' => $legacyId,
                            'from' => $title,
                            'to' => $candidate,
                        ]);
                        $title = $candidate;
                        $existing = null;
                    }
                }

                // Create new
                $entity = (new CGlossary())
                    ->setTitle($title)
                    ->setDescription($desc)
                ;

                // Required order: setParent() before addCourseLink()
                if (method_exists($entity, 'setParent')) {
                    $entity->setParent($course);
                }
                if (method_exists($entity, 'addCourseLink')) {
                    $entity->addCourseLink($course, $session);
                }

                $em->persist($entity);
                $em->flush();

                $destIid = (int) $entity->getIid();
                $setMapped($legacyId, $destIid);

                $this->dlog('restore_glossary: created', [
                    'src_id' => $legacyId,
                    'dst_id' => $destIid,
                    'title' => $title,
                ]);
            } catch (\Throwable $e) {
                $this->dlog('restore_glossary: failed (ignored)', [
                    'src_id' => $legacyId,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // Write back possibly updated array (for safety if $items was local copy)
        $this->course->resources[RESOURCE_GLOSSARY] = $items;

        $this->dlog('restore_glossary: end', [
            'policy' => $policy,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Restore Wiki resources for the destination course.
     *
     * @param mixed $sessionId
     */
    public function restore_wiki($sessionId = 0): void
    {
        // Detect which bucket exists and use it consistently for destination_id mapping.
        $bucketKey = null;
        if (isset($this->course->resources[RESOURCE_WIKI]) && is_array($this->course->resources[RESOURCE_WIKI])) {
            $bucketKey = RESOURCE_WIKI;
        } elseif (isset($this->course->resources['wiki']) && is_array($this->course->resources['wiki'])) {
            $bucketKey = 'wiki';
        }

        $bag = $bucketKey !== null ? ($this->course->resources[$bucketKey] ?? []) : [];

        if (empty($bag)) {
            $this->dlog('restore_wiki: empty bag');
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = Database::getManager();

        /** @var CWikiRepository $repo */
        $repo = $em->getRepository(CWiki::class);

        /** @var ObjectRepository $confRepo */
        $confRepo = $em->getRepository(CWikiConf::class);

        /** @var CourseEntity|null $courseEntity */
        $courseEntity = api_get_course_entity($this->destination_course_id);
        if (!$courseEntity instanceof CourseEntity) {
            $this->dlog('restore_wiki: missing destination course entity', [
                'course_id' => (int) $this->destination_course_id,
            ]);
            return;
        }

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int) $sessionId) : null;

        $cid = (int) $this->destination_course_id;
        $sid = (int) ($sessionEntity?->getId() ?? 0);

        // Preserve wiki version grouping inside this restore run.
        $pageIdMap = [];            // source page_id -> destination page_id
        $logicalPageIdMap = [];     // reflink|groupId -> destination page_id
        $basePageIdCache = [];      // reflink|groupId -> base destination page_id (session=0)
        $confCreatedForPage = [];   // destination page_id -> bool

        $this->dlog('restore_wiki: begin', [
            'count' => count($bag),
            'session_arg' => (int) $sessionId,
            'resolved_sid' => $sid,
            'bucket' => (string) $bucketKey,
            'policy' => (int) ($this->file_option ?? -1),
        ]);

        // Helper: find base page_id for the same reflink+group (session=0).
        $resolveBasePageId = function (string $reflink, int $groupId) use ($repo, $cid, &$basePageIdCache): int {
            $key = $reflink.'|'.$groupId;
            if (array_key_exists($key, $basePageIdCache)) {
                return (int) $basePageIdCache[$key];
            }

            $qb = $repo->createQueryBuilder('w')
                ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId)
                ->andWhere('COALESCE(w.sessionId,0) = 0')
                ->addOrderBy('w.version', 'DESC')
                ->addOrderBy('w.iid', 'DESC')
                ->setMaxResults(1);

            /** @var CWiki|null $base */
            $base = $qb->getQuery()->getOneOrNullResult();
            $basePid = 0;

            if ($base instanceof CWiki) {
                $basePid = (int) (($base->getPageId() ?: $base->getIid()) ?: 0);
            }

            $basePageIdCache[$key] = $basePid;

            return $basePid;
        };

        foreach ($bag as $legacyId => $res) {
            if (!is_object($res)) {
                $this->dlog('restore_wiki: skip non-object resource', [
                    'legacy_id' => (string) $legacyId,
                    'type' => gettype($res),
                    'resolved_sid' => $sid,
                ]);
                continue;
            }

            // In backups, destination_id is often -1 meaning "not restored yet".
            // Only skip if destination_id is a real mapped id (> 0).
            $destId = $res->destination_id ?? null;
            if (null !== $destId && (int) $destId > 0) {
                $this->dlog('restore_wiki: skip already mapped', [
                    'legacy_id' => (string) $legacyId,
                    'destination_id' => (int) $destId,
                    'resolved_sid' => $sid,
                ]);
                continue;
            }

            // Prefer res->obj; fallback to res fields directly (legacy/old backups).
            $obj = is_object($res->obj ?? null) ? $res->obj : (object) [];
            $src = !empty((array) $obj) ? $obj : $res;

            try {
                $rawTitle = (string) ($src->title ?? $src->name ?? '');
                $reflink = (string) ($src->reflink ?? '');
                $content = (string) ($src->content ?? '');
                $comment = (string) ($src->comment ?? '');
                $progress = (string) ($src->progress ?? '');
                $version = (int) ($src->version ?? 1);

                $groupId = (int) ($src->group_id ?? $src->groupId ?? 0);
                $userId = (int) ($src->user_id ?? $src->userId ?? api_get_user_id());

                // Source page_id (used to keep versions grouped).
                $srcPageId = (int) ($src->page_id ?? $src->pageId ?? 0);
                if ($srcPageId <= 0) {
                    $srcPageId = (int) $legacyId;
                }

                // HTML rewrite
                $content = $this->rewriteHtmlForCourse($content, (int) $sessionId, '[wiki.page]');

                if ('' === trim($rawTitle)) {
                    $rawTitle = 'Wiki page';
                }
                if ('' === trim($content)) {
                    $content = '<p>&nbsp;</p>';
                }

                // Slug maker
                $makeSlug = static function (string $s): string {
                    $s = strtolower(trim($s));
                    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s) ?: '';
                    $s = trim($s, '-');

                    return '' === $s ? 'page' : $s;
                };
                $reflink = '' !== trim($reflink) ? $makeSlug($reflink) : $makeSlug($rawTitle);
                $logicalKey = $reflink.'|'.$groupId;

                // Existence check by (course + session + group + reflink)
                $qbExists = $repo->createQueryBuilder('w')
                    ->select('w.iid')
                    ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                    ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                    ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId);

                if ($sid > 0) {
                    $qbExists->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                } else {
                    $qbExists->andWhere('COALESCE(w.sessionId,0) = 0');
                }

                $exists = (bool) $qbExists->getQuery()->getOneOrNullResult();

                if ($exists) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            // Map to latest page id
                            $qbLast = $repo->createQueryBuilder('w')
                                ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                                ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId)
                                ->orderBy('w.version', 'DESC')
                                ->setMaxResults(1);

                            if ($sid > 0) {
                                $qbLast->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                            } else {
                                $qbLast->andWhere('COALESCE(w.sessionId,0) = 0');
                            }

                            /** @var CWiki|null $last */
                            $last = $qbLast->getQuery()->getOneOrNullResult();
                            $dest = $last ? (int) ($last->getPageId() ?: $last->getIid()) : 0;

                            $this->course->resources[$bucketKey][$legacyId] ??= new stdClass();
                            $this->course->resources[$bucketKey][$legacyId]->destination_id = $dest;
                            $res->destination_id = $dest;

                            $this->dlog('restore_wiki: exists -> skip', [
                                'reflink' => $reflink,
                                'page_id' => $dest,
                                'resolved_sid' => $sid,
                            ]);
                            continue 2;

                        case FILE_RENAME:
                            $baseSlug = $reflink;
                            $baseTitle = $rawTitle;
                            $i = 1;

                            $isTaken = function (string $slug) use ($repo, $cid, $sid, $groupId): bool {
                                $qb = $repo->createQueryBuilder('w')
                                    ->select('w.iid')
                                    ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                                    ->andWhere('w.reflink = :r')->setParameter('r', $slug)
                                    ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId)
                                    ->setMaxResults(1);

                                if ($sid > 0) {
                                    $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                                } else {
                                    $qb->andWhere('COALESCE(w.sessionId,0) = 0');
                                }

                                return (bool) $qb->getQuery()->getOneOrNullResult();
                            };

                            $trySlug = $baseSlug.'-'.$i;
                            while ($isTaken($trySlug)) {
                                $trySlug = $baseSlug.'-'.(++$i);
                            }
                            $reflink = $trySlug;
                            $rawTitle = $baseTitle.' ('.$i.')';

                            // Recompute logical key after rename
                            $logicalKey = $reflink.'|'.$groupId;

                            $this->dlog('restore_wiki: renamed', [
                                'reflink' => $reflink,
                                'title' => $rawTitle,
                                'resolved_sid' => $sid,
                            ]);
                            break;

                        case FILE_OVERWRITE:
                            $qbAll = $repo->createQueryBuilder('w')
                                ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                                ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId);

                            if ($sid > 0) {
                                $qbAll->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                            } else {
                                $qbAll->andWhere('COALESCE(w.sessionId,0) = 0');
                            }
                            foreach ($qbAll->getQuery()->getResult() as $old) {
                                $em->remove($old);
                            }
                            $em->flush();

                            $this->dlog('restore_wiki: removed old pages (overwrite)', [
                                'reflink' => $reflink,
                                'resolved_sid' => $sid,
                            ]);
                            break;

                        default:
                            $this->dlog('restore_wiki: unknown file_option -> skip', [
                                'file_option' => (int) ($this->file_option ?? -1),
                                'resolved_sid' => $sid,
                            ]);
                            continue 2;
                    }
                }

                // Create new wiki row (possibly a version).
                $wiki = new CWiki();
                $wiki->setCId($cid);
                $wiki->setSessionId($sid);
                $wiki->setGroupId($groupId);
                $wiki->setReflink($reflink);
                $wiki->setTitle($rawTitle);
                $wiki->setContent($content);
                $wiki->setComment($comment);
                $wiki->setProgress($progress);
                $wiki->setVersion($version > 0 ? $version : 1);
                $wiki->setUserId($userId);

                // Timestamps (best-effort)
                try {
                    $dtimeStr = (string) ($src->dtime ?? '');
                    $wiki->setDtime('' !== trim($dtimeStr) ? new DateTime($dtimeStr) : new DateTime('now', new DateTimeZone('UTC')));
                } catch (Throwable) {
                    $wiki->setDtime(new DateTime('now', new DateTimeZone('UTC')));
                }

                // Defaults
                $wiki->setIsEditing(0);
                $wiki->setTimeEdit(null);
                $wiki->setHits((int) ($src->hits ?? 0));
                $wiki->setAddlock((int) ($src->addlock ?? 1));
                $wiki->setEditlock((int) ($src->editlock ?? 0));
                $wiki->setVisibility((int) ($src->visibility ?? 1));
                $wiki->setAddlockDisc((int) ($src->addlock_disc ?? 1));
                $wiki->setVisibilityDisc((int) ($src->visibility_disc ?? 1));
                $wiki->setRatinglockDisc((int) ($src->ratinglock_disc ?? 1));
                $wiki->setAssignment((int) ($src->assignment ?? 0));
                $wiki->setScore(isset($src->score) ? (int) $src->score : 0);
                $wiki->setLinksto((string) ($src->linksto ?? ''));
                $wiki->setTag((string) ($src->tag ?? ''));
                $wiki->setUserIp((string) ($src->user_ip ?? api_get_real_ip()));

                // Optional linking (keep what already works)
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

                // Decide destination page_id:
                // - If session restore and base page exists for same reflink+group, reuse the base page_id.
                // - Otherwise keep internal mapping by source page_id.
                $destPageId = (int) ($logicalPageIdMap[$logicalKey] ?? 0);

                if ($destPageId <= 0 && $sid > 0) {
                    $basePid = (int) $resolveBasePageId($reflink, $groupId);
                    if ($basePid > 0) {
                        $destPageId = $basePid;
                        $logicalPageIdMap[$logicalKey] = $destPageId;
                        $this->dlog('restore_wiki: using base page_id for session override', [
                            'reflink' => $reflink,
                            'gid' => $groupId,
                            'base_page_id' => $basePid,
                            'sid' => $sid,
                        ]);
                    }
                }

                if ($destPageId <= 0) {
                    $destPageId = (int) ($pageIdMap[$srcPageId] ?? 0);
                }

                if ($destPageId <= 0) {
                    // First time we see this page in this restore run.
                    $destPageId = (int) ($wiki->getIid() ?? 0);
                    $pageIdMap[$srcPageId] = $destPageId;
                }

                // Keep logical mapping consistent too
                if ($destPageId > 0) {
                    $logicalPageIdMap[$logicalKey] = $destPageId;
                }

                $wiki->setPageId($destPageId);
                $em->flush();

                // CWikiConf has no session/group columns: only create if missing in DB.
                if (!isset($confCreatedForPage[$destPageId])) {
                    $existingConf = $confRepo->findOneBy([
                        'cId' => $cid,
                        'pageId' => $destPageId,
                    ]);

                    if (!$existingConf) {
                        $conf = new CWikiConf();
                        $conf->setCId($cid);
                        $conf->setPageId($destPageId);

                        $conf->setTask((string) ($src->task ?? ''));
                        $conf->setFeedback1((string) ($src->feedback1 ?? ''));
                        $conf->setFeedback2((string) ($src->feedback2 ?? ''));
                        $conf->setFeedback3((string) ($src->feedback3 ?? ''));
                        $conf->setFprogress1((string) ($src->fprogress1 ?? ''));
                        $conf->setFprogress2((string) ($src->fprogress2 ?? ''));
                        $conf->setFprogress3((string) ($src->fprogress3 ?? ''));

                        if (isset($src->max_size)) {
                            $conf->setMaxSize((int) $src->max_size);
                        }
                        $conf->setMaxText(isset($src->max_text) ? (int) $src->max_text : 0);
                        $conf->setMaxVersion(isset($src->max_version) ? (int) $src->max_version : 0);

                        try {
                            $conf->setStartdateAssig(!empty($src->startdate_assig) ? new DateTime((string) $src->startdate_assig) : null);
                        } catch (Throwable) {
                            $conf->setStartdateAssig(null);
                        }

                        try {
                            $conf->setEnddateAssig(!empty($src->enddate_assig) ? new DateTime((string) $src->enddate_assig) : null);
                        } catch (Throwable) {
                            $conf->setEnddateAssig(null);
                        }

                        $conf->setDelayedsubmit(isset($src->delayedsubmit) ? (int) $src->delayedsubmit : 0);

                        $em->persist($conf);
                        $em->flush();

                        $this->dlog('restore_wiki: conf created', [
                            'page_id' => $destPageId,
                            'cid' => $cid,
                        ]);
                    } else {
                        $this->dlog('restore_wiki: conf already exists in DB', [
                            'page_id' => $destPageId,
                            'cid' => $cid,
                        ]);
                    }

                    $confCreatedForPage[$destPageId] = true;
                }

                // Map destination id back to the resource bag
                $this->course->resources[$bucketKey][$legacyId] ??= new stdClass();
                $this->course->resources[$bucketKey][$legacyId]->destination_id = $destPageId;
                $res->destination_id = $destPageId;

                $this->dlog('restore_wiki: created', [
                    'iid' => (int) ($wiki->getIid() ?? 0),
                    'page_id' => $destPageId,
                    'reflink' => $reflink,
                    'title' => $rawTitle,
                    'version' => (int) ($wiki->getVersion() ?? 0),
                    'resolved_sid' => $sid,
                ]);
            } catch (Throwable $e) {
                $this->dlog('restore_wiki: failed', [
                    'error' => $e->getMessage(),
                    'legacy_id' => (string) $legacyId,
                    'resolved_sid' => $sid,
                ]);
                continue;
            }
        }

        $this->dlog('restore_wiki: done', [
            'count' => count($bag),
            'resolved_sid' => $sid,
        ]);
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

        $repo = Container::getAttendanceRepository();
        $resources = $this->course->resources;

        $this->debug && error_log('COURSE_DEBUG: restore_attendance: begin count='.count($resources[RESOURCE_ATTENDANCE] ?? []).' session='.(int) $sessionId);

        $findExisting = function (string $title) use ($repo, $courseEntity, $sessionEntity): ?CAttendance {
            $qb = $repo->createQueryBuilder('resource')
                ->innerJoin('resource.resourceNode', 'n')
                ->innerJoin('n.resourceLinks', 'l')
                ->andWhere('l.course = :course')->setParameter('course', $courseEntity)
                ->andWhere('resource.title = :title')->setParameter('title', $title)
                ->setMaxResults(1);

            if ($sessionEntity) {
                $qb->andWhere('l.session = :session')->setParameter('session', $sessionEntity);
            } else {
                $qb->andWhere('l.session IS NULL');
            }

            return $qb->getQuery()->getOneOrNullResult();
        };

        $makeUniqueTitle = function (string $base) use ($findExisting): string {
            $base = trim($base) !== '' ? $base : 'Attendance';
            if (!$findExisting($base)) {
                return $base;
            }

            $i = 1;
            do {
                $try = $base.' ('.$i.')';
                $i++;
            } while ($findExisting($try));

            return $try;
        };

        foreach (($resources[RESOURCE_ATTENDANCE] ?? []) as $legacyId => $att) {
            try {
                $p = (array) ($att->params ?? []);

                $title = trim((string) ($p['title'] ?? $p['name'] ?? ''));
                if ('' === $title) {
                    $title = 'Attendance';
                }

                $desc = (string) ($p['description'] ?? '');
                $desc = $this->rewriteHtmlForCourse($desc, (int) $sessionId, '[attendance.main]');

                $active = (int) ($p['active'] ?? 1);
                $qualTitle = (string) ($p['attendance_qualify_title'] ?? '');
                $qualMax = (int) ($p['attendance_qualify_max'] ?? 0);
                $weight = (float) ($p['attendance_weight'] ?? 0.0);
                $locked = (int) ($p['locked'] ?? 0);
                $requireUnique = (bool) ($p['require_unique'] ?? false);

                $existing = $findExisting($title);
                $policy = (int) ($this->file_option ?? FILE_RENAME);

                if ($existing) {
                    $dstIid = (int) $existing->getIid();

                    if ($policy === FILE_SKIP) {
                        $this->course->resources[RESOURCE_ATTENDANCE][$legacyId] ??= new stdClass();
                        $this->course->resources[RESOURCE_ATTENDANCE][$legacyId]->destination_id = $dstIid;
                        $att->destination_id = $dstIid;

                        $this->debug && error_log('COURSE_DEBUG: restore_attendance: exists -> skip src='.(int)$legacyId.' dst='.$dstIid);
                        continue;
                    }

                    if ($policy === FILE_OVERWRITE) {
                        // Update fields
                        $existing
                            ->setTitle($title)
                            ->setDescription($desc)
                            ->setActive($active)
                            ->setAttendanceQualifyTitle($qualTitle)
                            ->setAttendanceQualifyMax($qualMax)
                            ->setAttendanceWeight($weight)
                            ->setLocked($locked)
                            ->setRequireUnique($requireUnique);

                        // Remove old calendars (and their sheets via DB cascades)
                        foreach ($existing->getCalendars() as $oldCal) {
                            $em->remove($oldCal);
                        }

                        $em->flush();

                        // Map
                        $this->course->resources[RESOURCE_ATTENDANCE][$legacyId] ??= new stdClass();
                        $this->course->resources[RESOURCE_ATTENDANCE][$legacyId]->destination_id = $dstIid;
                        $att->destination_id = $dstIid;

                        // Re-create calendars below using $target = $existing
                        $target = $existing;
                    } else {
                        // RENAME
                        $title = $makeUniqueTitle($title);
                        $target = null;
                    }
                } else {
                    $target = null;
                }

                if (!$target instanceof CAttendance) {
                    $a = (new CAttendance())
                        ->setTitle($title)
                        ->setDescription($desc)
                        ->setActive($active)
                        ->setAttendanceQualifyTitle($qualTitle)
                        ->setAttendanceQualifyMax($qualMax)
                        ->setAttendanceWeight($weight)
                        ->setLocked($locked)
                        ->setRequireUnique($requireUnique);

                    $a->setParent($courseEntity);
                    $a->setCreator(api_get_user_entity());
                    $a->addCourseLink($courseEntity, $sessionEntity);

                    $repo->create($a);
                    $em->flush();

                    $target = $a;

                    $this->course->resources[RESOURCE_ATTENDANCE][$legacyId] ??= new stdClass();
                    $this->course->resources[RESOURCE_ATTENDANCE][$legacyId]->destination_id = (int) $a->getIid();
                    $att->destination_id = (int) $a->getIid();

                    $this->debug && error_log('COURSE_DEBUG: restore_attendance: created iid='.(int)$a->getIid().' title="'.$title.'"');
                }

                // Calendars
                $calList = (array) ($att->attendance_calendar ?? $att->attendance_calendar_list ?? []);
                foreach ($calList as $c) {
                    $c = is_array($c) ? $c : (array) $c;

                    $dt = $this->toUtcDateTime($c['date_time'] ?? $c['dateTime'] ?? $c['start_date'] ?? null);
                    $done = (bool) ($c['done_attendance'] ?? $c['doneAttendance'] ?? false);
                    $blocked = (bool) ($c['blocked'] ?? false);
                    $duration = array_key_exists('duration', $c) ? (null !== $c['duration'] ? (int) $c['duration'] : null) : null;

                    $cal = (new CAttendanceCalendar())
                        ->setAttendance($target)
                        ->setDateTime($dt)
                        ->setDoneAttendance($done)
                        ->setBlocked($blocked)
                        ->setDuration($duration);

                    $em->persist($cal);
                }

                $em->flush();

                $this->debug && error_log('COURSE_DEBUG: restore_attendance: calendars restored count='.count($calList).' attendance_iid='.(int)$target->getIid());
            } catch (Throwable $e) {
                error_log('COURSE_DEBUG: restore_attendance: failed: '.$e->getMessage());
                continue;
            }
        }

        $this->debug && error_log('COURSE_DEBUG: restore_attendance: end');
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

        $repo = Container::getThematicRepository();
        $resources = $this->course->resources;

        $this->debug && error_log('COURSE_DEBUG: restore_thematic: begin count='.count($resources[RESOURCE_THEMATIC] ?? []).' session='.(int) $sessionId);

        $findExisting = function (string $title) use ($repo, $courseEntity, $sessionEntity): ?CThematic {
            $qb = $repo->createQueryBuilder('resource')
                ->innerJoin('resource.resourceNode', 'n')
                ->innerJoin('n.resourceLinks', 'l')
                ->andWhere('l.course = :course')->setParameter('course', $courseEntity)
                ->andWhere('resource.title = :title')->setParameter('title', $title)
                ->setMaxResults(1);

            if ($sessionEntity) {
                $qb->andWhere('l.session = :session')->setParameter('session', $sessionEntity);
            } else {
                $qb->andWhere('l.session IS NULL');
            }

            return $qb->getQuery()->getOneOrNullResult();
        };

        $makeUniqueTitle = function (string $base) use ($findExisting): string {
            $base = trim($base) !== '' ? $base : 'Thematic';
            if (!$findExisting($base)) {
                return $base;
            }

            $i = 1;
            do {
                $try = $base.' ('.$i.')';
                $i++;
            } while ($findExisting($try));

            return $try;
        };

        foreach (($resources[RESOURCE_THEMATIC] ?? []) as $legacyId => $t) {
            try {
                $p = (array) ($t->params ?? []);

                $title = trim((string) ($p['title'] ?? $p['name'] ?? ''));
                if ('' === $title) {
                    $title = 'Thematic';
                }

                $content = (string) ($p['content'] ?? '');
                $active = (bool) ($p['active'] ?? true);

                $content = $this->rewriteHtmlForCourse($content, (int) $sessionId, '[thematic.main]');

                $existing = $findExisting($title);
                $policy = (int) ($this->file_option ?? FILE_RENAME);

                if ($existing) {
                    $dstIid = (int) $existing->getIid();

                    if ($policy === FILE_SKIP) {
                        $this->course->resources[RESOURCE_THEMATIC][$legacyId] ??= new stdClass();
                        $this->course->resources[RESOURCE_THEMATIC][$legacyId]->destination_id = $dstIid;
                        $t->destination_id = $dstIid;

                        $this->debug && error_log('COURSE_DEBUG: restore_thematic: exists -> skip src='.(int)$legacyId.' dst='.$dstIid);
                        continue;
                    }

                    if ($policy === FILE_OVERWRITE) {
                        // Update root fields
                        $existing->setTitle($title)->setContent($content)->setActive($active);

                        // Remove old children to avoid duplication
                        foreach ($existing->getAdvances() as $oldAdv) {
                            $em->remove($oldAdv);
                        }
                        foreach ($existing->getPlans() as $oldPlan) {
                            $em->remove($oldPlan);
                        }
                        $em->flush();

                        $thematic = $existing;

                        $this->course->resources[RESOURCE_THEMATIC][$legacyId] ??= new stdClass();
                        $this->course->resources[RESOURCE_THEMATIC][$legacyId]->destination_id = $dstIid;
                        $t->destination_id = $dstIid;
                    } else {
                        // RENAME
                        $title = $makeUniqueTitle($title);
                        $thematic = null;
                    }
                } else {
                    $thematic = null;
                }

                if (!$thematic instanceof CThematic) {
                    $thematic = (new CThematic())
                        ->setTitle($title)
                        ->setContent($content)
                        ->setActive($active);

                    $thematic->setParent($courseEntity);
                    $thematic->setCreator(api_get_user_entity());
                    $thematic->addCourseLink($courseEntity, $sessionEntity);

                    $repo->create($thematic);
                    $em->flush();

                    $this->course->resources[RESOURCE_THEMATIC][$legacyId] ??= new stdClass();
                    $this->course->resources[RESOURCE_THEMATIC][$legacyId]->destination_id = (int) $thematic->getIid();
                    $t->destination_id = (int) $thematic->getIid();

                    $this->debug && error_log('COURSE_DEBUG: restore_thematic: created iid='.(int)$thematic->getIid().' title="'.$title.'"');
                }

                // Advances
                $advList = (array) ($t->thematic_advance_list ?? $t->thematic_advance ?? []);
                foreach ($advList as $adv) {
                    $adv = is_array($adv) ? $adv : (array) $adv;

                    $advContent = (string) ($adv['content'] ?? '');
                    $advContent = $this->rewriteHtmlForCourse($advContent, (int) $sessionId, '[thematic.advance]');

                    $startDate = $this->toUtcDateTime($adv['start_date'] ?? $adv['startDate'] ?? null);

                    $duration = (int) ($adv['duration'] ?? 1);
                    $doneAdvance = (bool) ($adv['done_advance'] ?? $adv['doneAdvance'] ?? false);

                    $advance = (new CThematicAdvance())
                        ->setThematic($thematic)
                        ->setContent($advContent)
                        ->setStartDate($startDate)
                        ->setDuration($duration)
                        ->setDoneAdvance($doneAdvance);

                    // Link attendance (SOURCE id -> DESTINATION iid)
                    $srcAttId = (int) ($adv['attendance_id'] ?? 0);
                    if ($srcAttId > 0) {
                        $dstAttIid = $this->resolveDestinationIid(RESOURCE_ATTENDANCE, $srcAttId);
                        if ($dstAttIid > 0) {
                            $dstAtt = $em->getRepository(CAttendance::class)->find($dstAttIid);
                            if ($dstAtt instanceof CAttendance) {
                                $advance->setAttendance($dstAtt);
                            }
                        }
                    }

                    // Room is global id (keep as-is)
                    $roomId = (int) ($adv['room_id'] ?? 0);
                    if ($roomId > 0) {
                        $room = $em->getRepository(Room::class)->find($roomId);
                        if ($room instanceof Room) {
                            $advance->setRoom($room);
                        }
                    }

                    $em->persist($advance);
                }

                // Plans
                $planList = (array) ($t->thematic_plan_list ?? $t->thematic_plan ?? []);
                foreach ($planList as $pl) {
                    $pl = is_array($pl) ? $pl : (array) $pl;

                    $plTitle = trim((string) ($pl['title'] ?? ''));
                    if ('' === $plTitle) {
                        $plTitle = 'Plan';
                    }

                    $plDesc = (string) ($pl['description'] ?? '');
                    $plDesc = $this->rewriteHtmlForCourse($plDesc, (int) $sessionId, '[thematic.plan]');

                    $descType = (int) ($pl['description_type'] ?? $pl['descriptionType'] ?? 0);

                    $plan = (new CThematicPlan())
                        ->setThematic($thematic)
                        ->setTitle($plTitle)
                        ->setDescription($plDesc)
                        ->setDescriptionType($descType);

                    $em->persist($plan);
                }

                $em->flush();

                $this->debug && error_log(
                    'COURSE_DEBUG: restore_thematic: restored iid='.(int)$thematic->getIid().
                    ' advances='.count($advList).' plans='.count($planList)
                );
            } catch (Throwable $e) {
                error_log('COURSE_DEBUG: restore_thematic: failed: '.$e->getMessage());
                continue;
            }
        }

        $this->debug && error_log('COURSE_DEBUG: restore_thematic: end');
    }

    /**
     * Normalize any value (string|DateTimeInterface|timestamp|null) into a UTC DateTime.
     */
    private function toUtcDateTime($value): DateTime
    {
        $tz = new DateTimeZone('UTC');

        if ($value instanceof DateTimeInterface) {
            $dt = DateTimeImmutable::createFromInterface($value)->setTimezone($tz);
            return new DateTime($dt->format('Y-m-d H:i:s'), $tz);
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $ts = (int) $value;
            $dt = (new DateTimeImmutable('@'.$ts))->setTimezone($tz);
            return new DateTime($dt->format('Y-m-d H:i:s'), $tz);
        }

        $s = trim((string) $value);
        if ($s === '') {
            return new DateTime('now', $tz);
        }

        try {
            $dt = new DateTime($s, $tz);
            $dt->setTimezone($tz);
            return $dt;
        } catch (Throwable) {
            return new DateTime('now', $tz);
        }
    }

    /**
     * Resolve destination iid for a source id within a bucket using destination_id + params['id'] fallback.
     */
    private function resolveDestinationIid(string $bucket, int $srcId): int
    {
        $bag = $this->course->resources[$bucket] ?? [];
        if (isset($bag[$srcId]) && is_object($bag[$srcId]) && !empty($bag[$srcId]->destination_id)) {
            return (int) $bag[$srcId]->destination_id;
        }

        foreach ($bag as $key => $res) {
            if (!is_object($res)) {
                continue;
            }
            $p = (array) ($res->params ?? []);
            $id = (int) ($p['id'] ?? $key);
            if ($id === $srcId && !empty($res->destination_id)) {
                return (int) $res->destination_id;
            }
        }

        return 0;
    }

    /**
     * Restore Student Publications (works) from backup selection.
     * - Honors file policy: FILE_SKIP (1), FILE_RENAME (2), FILE_OVERWRITE (3)
     * - Dedupe across multiple restore calls: if the same item was already restored, reuse it and adjust links.
     * - If "copy only session items" is enabled: skip base restore (sid=0) and enforce session-only link.
     */
    public function restore_works(int $sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_WORK)) {
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = \Database::getManager();

        $copyOnlySessionItems = (bool) (
            $this->course->copy_only_session_items
            ?? $this->course->copyOnlySessionItems
            ?? false
        );

        // If user asked to copy only session items, never restore works for sid=0.
        if ($copyOnlySessionItems && 0 === (int) $sessionId) {
            $this->dlog('restore_works: skipped base restore because copy_only_session_items is enabled', []);
            return;
        }

        // Resolve destination course entity safely.
        $courseEntity = api_get_course_entity((int) $this->destination_course_id);
        if (!$courseEntity instanceof CourseEntity) {
            $destinationCourseId = (int) ($this->destination_course_id ?? 0);
            if ($destinationCourseId > 0) {
                $courseEntity = $em->find(CourseEntity::class, $destinationCourseId);
            }
        }

        if (!$courseEntity instanceof CourseEntity) {
            $destinationCode = (string) ($this->destination_course_code ?? $this->destination_code ?? '');
            if ('' !== $destinationCode) {
                $info = api_get_course_info($destinationCode);
                $resolvedId = (int) ($info['real_id'] ?? $info['id'] ?? 0);
                if ($resolvedId > 0) {
                    $courseEntity = $em->find(CourseEntity::class, $resolvedId);
                }
            }
        }

        if (!$courseEntity instanceof CourseEntity) {
            $this->dlog('restore_works: cannot resolve destination course entity, aborting', [
                'destination_course_id' => (int) ($this->destination_course_id ?? 0),
                'destination_course_code' => (string) ($this->destination_course_code ?? $this->destination_code ?? ''),
            ]);
            return;
        }

        $sessionEntity = $sessionId ? api_get_session_entity((int) $sessionId) : null;

        /** @var \Chamilo\CoreBundle\Repository\CStudentPublicationRepository $pubRepo */
        $pubRepo = \Chamilo\CoreBundle\Framework\Container::getStudentPublicationRepository();

        $FILE_SKIP = \defined('FILE_SKIP') ? (int) FILE_SKIP : 1;
        $FILE_RENAME = \defined('FILE_RENAME') ? (int) FILE_RENAME : 2;
        $FILE_OVERWRITE = \defined('FILE_OVERWRITE') ? (int) FILE_OVERWRITE : 3;

        $filePolicy = (int) ($this->file_option ?? $FILE_RENAME);

        $mappedBySource = [];
        // enforce session-only link when copy_only_session_items is enabled.
        $ensureSessionOnlyLink = function (CStudentPublication $pub) use ($em, $courseEntity, $sessionEntity, $copyOnlySessionItems): void {
            if (!$copyOnlySessionItems || !$sessionEntity) {
                return;
            }

            // Ensure session link exists.
            try {
                if (!$pub->getFirstResourceLinkFromCourseSession($courseEntity, $sessionEntity)) {
                    $pub->addCourseLink($courseEntity, $sessionEntity);
                }
            } catch (\Throwable) {
                // Ignore link creation failures.
            }

            // Remove base link (session IS NULL) to avoid showing it in base course and causing "exists" conflicts.
            try {
                if (method_exists($pub, 'getResourceNode') && $pub->getResourceNode()) {
                    $node = $pub->getResourceNode();
                    if (method_exists($node, 'getResourceLinks')) {
                        foreach ($node->getResourceLinks() as $rl) {
                            // Defensive checks
                            if (!method_exists($rl, 'getCourse') || !method_exists($rl, 'getSession')) {
                                continue;
                            }
                            $rlCourse = $rl->getCourse();
                            $rlSession = $rl->getSession();

                            $sameCourse = $rlCourse && method_exists($rlCourse, 'getId') && (int) $rlCourse->getId() === (int) $courseEntity->getId();
                            $isBase = null === $rlSession;

                            if ($sameCourse && $isBase) {
                                $em->remove($rl);
                            }
                        }
                        $em->flush();
                    }
                }
            } catch (\Throwable) {
                // Ignore base-link removal failures.
            }
        };

        // strict "exists" check, avoiding "session IS NULL" when we are restoring session-only.
        $findExistingStrict = function (string $title) use ($em, $courseEntity, $sessionEntity, $sessionId): ?CStudentPublication {
            $qb = $em->createQueryBuilder();
            $qb
                ->select('w')
                ->from(CStudentPublication::class, 'w')
                ->innerJoin('w.resourceNode', 'rn')
                ->innerJoin('rn.resourceLinks', 'rl')
                ->andWhere('w.filetype = :ft')
                ->andWhere('w.publicationParent IS NULL')
                ->andWhere('w.active IN (0,1)')
                ->andWhere('w.title = :title')
                ->andWhere('rl.course = :course')
                ->setParameter('ft', 'folder')
                ->setParameter('title', $title)
                ->setParameter('course', $courseEntity)
                ->setMaxResults(1)
            ;

            if ((int) $sessionId > 0) {
                $qb->andWhere('rl.session = :session')->setParameter('session', $sessionEntity);
            } else {
                $qb->andWhere('rl.session IS NULL');
            }

            return $qb->getQuery()->getOneOrNullResult();
        };

        $this->dlog('restore_works: begin', [
            'count' => \count($this->course->resources[RESOURCE_WORK] ?? []),
            'policy' => $filePolicy,
            'session_id' => (int) $sessionId,
            'copy_only_session_items' => (bool) $copyOnlySessionItems,
        ]);

        foreach (($this->course->resources[RESOURCE_WORK] ?? []) as $legacyId => $obj) {
            $legacyId = (int) $legacyId;

            try {
                $p = (array) ($obj->params ?? []);

                // Use explicit source iid if present.
                $sourceIid = (int) ($p['iid'] ?? $legacyId);

                // If already restored earlier (even in another call), don't create again.
                $alreadyDst = (int) (($obj->destination_id ?? -1) ?: -1);
                if ($alreadyDst > 0) {
                    $pub = $em->find(CStudentPublication::class, $alreadyDst);
                    if ($pub instanceof CStudentPublication) {
                        $ensureSessionOnlyLink($pub);
                    }

                    $this->dlog('restore_works: reused already restored item', [
                        'src_id' => $legacyId,
                        'src_iid' => $sourceIid,
                        'dst_iid' => $alreadyDst,
                    ]);
                    continue;
                }

                // Dedupe within this call (in case build produced duplicates).
                if (isset($mappedBySource[$sourceIid])) {
                    $this->course->resources[RESOURCE_WORK][$legacyId] ??= new \stdClass();
                    $this->course->resources[RESOURCE_WORK][$legacyId]->destination_id = (int) $mappedBySource[$sourceIid];

                    $this->dlog('restore_works: duplicate source entry ignored', [
                        'src_id' => $legacyId,
                        'src_iid' => $sourceIid,
                        'dst_iid' => (int) $mappedBySource[$sourceIid],
                    ]);
                    continue;
                }

                $title = trim((string) ($p['title'] ?? 'Work'));
                if ('' === $title) {
                    $title = 'Work';
                }
                $baseTitle = $title;

                $rawDescription = (string) ($p['description'] ?? '');
                $rewrittenDescription = $this->rewriteHtmlForCourse($rawDescription, (int) $sessionId, '[work.description]');

                $enableQualification = filter_var(($p['enable_qualification'] ?? false), FILTER_VALIDATE_BOOLEAN);
                $addToCalendar = filter_var(($p['add_to_calendar'] ?? false), FILTER_VALIDATE_BOOLEAN);

                $expiresOn = null;
                if (!empty($p['expires_on'])) {
                    try { $expiresOn = new \DateTime((string) $p['expires_on']); } catch (\Throwable) { $expiresOn = null; }
                }

                $endsOn = null;
                if (!empty($p['ends_on'])) {
                    try { $endsOn = new \DateTime((string) $p['ends_on']); } catch (\Throwable) { $endsOn = null; }
                }

                if ($expiresOn && $endsOn && $endsOn < $expiresOn) {
                    $endsOn = clone $expiresOn;
                }

                $weight = isset($p['weight']) ? (float) $p['weight'] : 0.0;
                $qualification = isset($p['qualification']) ? (float) $p['qualification'] : 0.0;
                $allowText = isset($p['allow_text_assignment']) ? (int) $p['allow_text_assignment'] : 0;

                $defaultVisibility = filter_var(($p['default_visibility'] ?? false), FILTER_VALIDATE_BOOLEAN);
                $studentMayDelete = filter_var(($p['student_delete_own_publication'] ?? false), FILTER_VALIDATE_BOOLEAN);

                $extensions = isset($p['extensions']) ? trim((string) $p['extensions']) : '';
                $extensions = '' !== $extensions ? $extensions : null;

                $groupCategoryWorkId = isset($p['group_category_work_id']) ? (int) $p['group_category_work_id'] : 0;
                $postGroupId = isset($p['post_group_id']) ? (int) $p['post_group_id'] : 0;

                $creatorId = (int) ($p['user_id'] ?? 0);
                if ($creatorId <= 0) {
                    $creatorId = (int) api_get_user_id();
                }
                $creatorRef = $em->getReference(\Chamilo\CoreBundle\Entity\User::class, $creatorId);

                // Strict exists check: session-only must NOT match base (session NULL).
                $existing = $findExistingStrict($title);

                if ($existing) {
                    if ($filePolicy === $FILE_SKIP) {
                        $dstIid = (int) $existing->getIid();
                        $mappedBySource[$sourceIid] = $dstIid;

                        $this->course->resources[RESOURCE_WORK][$legacyId] ??= new \stdClass();
                        $this->course->resources[RESOURCE_WORK][$legacyId]->destination_id = $dstIid;

                        $ensureSessionOnlyLink($existing);

                        $this->dlog('restore_works: skipped (exists)', [
                            'src_id' => $legacyId,
                            'src_iid' => $sourceIid,
                            'dst_iid' => $dstIid,
                            'title' => (string) $existing->getTitle(),
                        ]);
                        continue;
                    }

                    if ($filePolicy === $FILE_RENAME) {
                        $n = 1;
                        while (true) {
                            $candidate = $baseTitle.' ('.$n.')';
                            $dup = $findExistingStrict($candidate);

                            if (!$dup) {
                                $title = $candidate;
                                break;
                            }

                            $n++;
                            if ($n > 5000) {
                                $this->dlog('restore_works: rename safeguard triggered, skipping', [
                                    'src_id' => $legacyId,
                                    'src_iid' => $sourceIid,
                                    'base_title' => $baseTitle,
                                ]);
                                continue 2;
                            }
                        }

                        $existing = null; // force create
                    }
                    // FILE_OVERWRITE keeps $existing
                }

                if (!$existing) {
                    $pub = (new CStudentPublication())
                        ->setTitle($title)
                        ->setDescription($rewrittenDescription)
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
                        ->setUser($creatorRef)
                    ;

                    $pub->setParent($courseEntity);
                    $pub->addCourseLink($courseEntity, $sessionEntity);

                    $em->persist($pub);

                    $assignment = (new CStudentPublicationAssignment())
                        ->setPublication($pub)
                        ->setEnableQualification($enableQualification || $qualification > 0.0)
                        ->setExpiresOn($expiresOn)
                        ->setEndsOn($endsOn)
                    ;

                    $em->persist($assignment);
                    $em->flush();

                    $ensureSessionOnlyLink($pub);
                    if ($addToCalendar) {
                        try {
                            $link = $pub->getFirstResourceLink();
                            if ($link) {
                                $eventTitle = sprintf(get_lang('Handing over of task %s'), $pub->getTitle());

                                $content = (string) $pub->getDescription();
                                $content = $this->rewriteHtmlForCourse($content, (int) $sessionId, '[work.calendar]');

                                $start = $expiresOn ? clone $expiresOn : new \DateTime('now', new \DateTimeZone('UTC'));
                                $end = $expiresOn ? clone $expiresOn : new \DateTime('now', new \DateTimeZone('UTC'));

                                $event = (new CCalendarEvent())
                                    ->setTitle($eventTitle)
                                    ->setContent($content)
                                    ->setParent($courseEntity)
                                    ->setCreator($creatorRef)
                                    ->addLink(clone $link)
                                    ->setStartDate($start)
                                    ->setEndDate($end)
                                    ->setColor(CCalendarEvent::COLOR_STUDENT_PUBLICATION)
                                ;

                                $em->persist($event);
                                $em->flush();

                                $assignment->setEventCalendarId((int) $event->getIid());
                                $em->flush();
                            }
                        } catch (\Throwable $e) {
                            $this->dlog('restore_works: calendar failed (ignored)', [
                                'src_id' => $legacyId,
                                'dst_iid' => (int) $pub->getIid(),
                                'err' => $e->getMessage(),
                            ]);
                        }
                    }

                    $dstIid = (int) $pub->getIid();
                    $mappedBySource[$sourceIid] = $dstIid;

                    $this->course->resources[RESOURCE_WORK][$legacyId] ??= new \stdClass();
                    $this->course->resources[RESOURCE_WORK][$legacyId]->destination_id = $dstIid;

                    $this->dlog('restore_works: created', [
                        'src_id' => $legacyId,
                        'src_iid' => $sourceIid,
                        'dst_iid' => $dstIid,
                        'title' => (string) $pub->getTitle(),
                    ]);

                    continue;
                }

                // Overwrite existing
                $existing
                    ->setDescription($rewrittenDescription)
                    ->setWeight($weight)
                    ->setQualification($qualification)
                    ->setAllowTextAssignment($allowText)
                    ->setDefaultVisibility($defaultVisibility)
                    ->setStudentDeleteOwnPublication($studentMayDelete)
                    ->setExtensions($extensions)
                    ->setGroupCategoryWorkId($groupCategoryWorkId)
                    ->setPostGroupId($postGroupId)
                ;

                $em->persist($existing);
                $em->flush();

                $ensureSessionOnlyLink($existing);

                $dstIid = (int) $existing->getIid();
                $mappedBySource[$sourceIid] = $dstIid;

                $this->course->resources[RESOURCE_WORK][$legacyId] ??= new \stdClass();
                $this->course->resources[RESOURCE_WORK][$legacyId]->destination_id = $dstIid;

                $this->dlog('restore_works: overwritten', [
                    'src_id' => $legacyId,
                    'src_iid' => $sourceIid,
                    'dst_iid' => $dstIid,
                    'title' => (string) $existing->getTitle(),
                ]);
            } catch (\Throwable $e) {
                $this->dlog('restore_works: failed', [
                    'src_id' => $legacyId,
                    'err' => $e->getMessage(),
                ]);
                continue;
            }
        }

        $this->dlog('restore_works: end');
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
        if ('' === $html) {
            return '';
        }

        $courseEntity = api_get_course_entity($this->destination_course_id);
        $sessionEntity = $sessionId ? api_get_session_entity($sessionId) : null;
        $groupEntity = api_get_group_entity();

        $docRepo = Container::getDocumentRepository();

        $courseDir = (string) ($this->course->courseDir ?? ($this->course->info['path'] ?? ''));
        $courseDir = trim($courseDir, '/');
        $courseDir = preg_replace('#^courses/#i', '', $courseDir) ?: $courseDir;

        $DBG = function (string $tag, array $ctx = []) use ($dbgTag): void {
            $this->dlog('HTMLRW'.$dbgTag.': '.$tag, $ctx);
        };

        $srcRoot = $this->getHtmlRewriteSourceRoot($courseDir, $DBG);

        $DBG('html.rewrite.context', [
            'courseDir' => $courseDir,
            'srcRoot' => $srcRoot,
            'srcRootIsDir' => ('' !== $srcRoot) && is_dir($srcRoot),
        ]);

        if ('' === $srcRoot || !is_dir($srcRoot)) {
            $DBG('html.rewrite.skip', ['reason' => 'No valid srcRoot directory found']);
            return $html;
        }

        if (!isset($this->htmlFoldersByCourseDir[$courseDir]) || !is_array($this->htmlFoldersByCourseDir[$courseDir])) {
            $this->htmlFoldersByCourseDir[$courseDir] = [];
        }

        // pass-by-reference array must be a variable (not an expression)
        $folders = &$this->htmlFoldersByCourseDir[$courseDir];

        // file_option (skip/overwrite/rename). Keep a safe default if not set.
        $fileOption = 0;
        if (isset($this->course->file_option)) {
            $fileOption = (int) $this->course->file_option;
        } elseif (isset($this->file_option)) {
            $fileOption = (int) $this->file_option;
        }

        // Ensure folder callback expected by ChamiloHelper
        $ensureFolder = function (string $parentRelPath) use (
            &$folders,
            $docRepo,
            $courseEntity,
            $sessionEntity,
            $groupEntity,
            $sessionId,
            $DBG
        ): int {
            $parentRelPath = '/'.trim($parentRelPath, '/');
            if ('/' === $parentRelPath) {
                return 0;
            }

            // If already cached
            if (!empty($folders[$parentRelPath])) {
                return (int) $folders[$parentRelPath];
            }

            // Create nested folders progressively: /A, /A/B, /A/B/C
            $parts = array_values(array_filter(explode('/', trim($parentRelPath, '/'))));
            $currentPath = '';
            $parentId = 0;

            // Course info array for DocumentManager
            $courseInfo = [
                'real_id' => method_exists($courseEntity, 'getId') ? (int) $courseEntity->getId() : 0,
                'code' => method_exists($courseEntity, 'getCode') ? (string) $courseEntity->getCode() : null,
            ];

            foreach ($parts as $p) {
                $currentPath .= '/'.$p;

                if (!empty($folders[$currentPath])) {
                    $parentId = (int) $folders[$currentPath];
                    continue;
                }

                $title = $p;

                // Try to find existing folder under current parent
                $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;
                $existing = $docRepo->findCourseResourceByTitle(
                    $title,
                    $parentRes->getResourceNode(),
                    $courseEntity,
                    $sessionEntity,
                    $groupEntity
                );

                if ($existing && method_exists($existing, 'getIid')) {
                    $parentId = (int) $existing->getIid();
                    $folders[$currentPath] = $parentId;
                    $DBG('html.ensureFolder.reuse', ['path' => $currentPath, 'iid' => $parentId]);
                    continue;
                }

                // Create folder
                try {
                    $entity = DocumentManager::addDocument(
                        $courseInfo,
                        $currentPath,
                        'folder',
                        0,
                        $title,
                        null,
                        0,
                        null,
                        0,
                        (int) $sessionId,
                        0,
                        false,
                        '',
                        $parentId,
                        null
                    );

                    $iid = ($entity && method_exists($entity, 'getIid')) ? (int) $entity->getIid() : 0;
                    if ($iid > 0) {
                        $folders[$currentPath] = $iid;
                        $parentId = $iid;
                        $DBG('html.ensureFolder.created', ['path' => $currentPath, 'iid' => $iid]);
                    }
                } catch (Throwable $e) {
                    $DBG('html.ensureFolder.error', [
                        'path' => $currentPath,
                        'message' => $e->getMessage(),
                        'class' => get_class($e),
                    ]);
                }
            }

            return $parentId;
        };

        try {
            // Call with the EXACT signature you pasted:
            // (html, courseDir, srcRoot, &$folders, $ensureFolder, $docRepo, $courseEntity, $session, $group, session_id, file_option, dbg)
            $map = ChamiloHelper::buildUrlMapForHtmlFromPackage(
                $html,
                $courseDir,
                $srcRoot,
                $folders,
                $ensureFolder,
                $docRepo,
                $courseEntity,
                $sessionEntity,
                $groupEntity,
                (int) $sessionId,
                (int) $fileOption,
                $DBG
            );

            $byRel = $map['byRel'] ?? [];
            $byBase = $map['byBase'] ?? [];

            $DBG('html.rewrite.map', [
                'byRel' => is_array($byRel) ? count($byRel) : 0,
                'byBase' => is_array($byBase) ? count($byBase) : 0,
            ]);

            $result = ChamiloHelper::rewriteLegacyCourseUrlsWithMap($html, $courseDir, $byRel, $byBase);

            $DBG('html.rewrite.result', [
                'replaced' => (int) ($result['replaced'] ?? 0),
                'misses' => (int) ($result['misses'] ?? 0),
            ]);

            return (string) ($result['html'] ?? $html);
        } catch (Throwable $e) {
            $DBG('html.rewrite.error', [
                'message' => $e->getMessage(),
                'class' => get_class($e),
            ]);
        }

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
    private function gb_resolveDestinationId($type, int $legacyId): int
    {
        if (empty($type)) {
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

        // Most resources use destination_id, but some may store destination_iid
        $destId = 0;
        if (isset($res->destination_id)) {
            $destId = (int) $res->destination_id;
        }
        if ($destId <= 0 && isset($res->destination_iid)) {
            $destId = (int) $res->destination_iid;
        }

        return $destId > 0 ? $destId : 0;
    }

    /**
     * Map GradebookLink type → RESOURCE_* bucket used in $this->course->resources.
     */
    private function gb_guessResourceTypeByLinkType(int $linkType): ?string
    {
        return match ($linkType) {
            LINK_EXERCISE => RESOURCE_QUIZ,
            LINK_HOTPOTATOES => RESOURCE_QUIZ,

            // Student publications / works (assignments)
            LINK_STUDENTPUBLICATION => RESOURCE_WORK,

            // Learning paths
            LINK_LEARNPATH => RESOURCE_LEARNPATH,

            // Attendance
            LINK_ATTENDANCE => RESOURCE_ATTENDANCE,

            // Surveys
            LINK_SURVEY => RESOURCE_SURVEY,

            // Forum thread links require a thread/topic restore + mapping.
            // If you don't restore topics/threads, keep it null to avoid broken links.
            LINK_FORUM_THREAD => null,

            // Not supported / not mapped
            LINK_DROPBOX => null,
            LINK_PORTFOLIO => null,

            default => null,
        };
    }

    public function restore_gradebook(int $sessionId = 0): void
    {
        // Always restore it in destination to avoid an empty gradebook.
        if (\in_array($this->file_option, [FILE_SKIP, FILE_RENAME], true)) {
            $this->dlog('restore_gradebook: forcing overwrite policy', ['file_option' => (int) $this->file_option]);
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

        // Debug payload overview (helps detect empty export)
        $payloadCats = 0;
        $payloadEvals = 0;
        $payloadLinks = 0;
        foreach ($this->course->resources[RESOURCE_GRADEBOOK] as $gbItem) {
            $categories = (array) ($gbItem->categories ?? []);
            $payloadCats += \count($categories);

            foreach ($categories as $rawCat) {
                $c = \is_array($rawCat) ? $rawCat : (array) $rawCat;
                $payloadEvals += \count((array) ($c['evaluations'] ?? []));
                $payloadLinks += \count((array) ($c['links'] ?? []));
            }
        }
        $this->dlog('restore_gradebook: payload overview', [
            'session_id' => (int) $sessionId,
            'categories' => $payloadCats,
            'evaluations' => $payloadEvals,
            'links' => $payloadLinks,
        ]);

        // Clean destination categories for this course/session (overwrite semantics)
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

        // Create categories
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

                // Optional fields
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

        // Wire parents
        foreach ($this->course->resources[RESOURCE_GRADEBOOK] as $gbItem) {
            $categories = (array) ($gbItem->categories ?? []);
            foreach ($categories as $rawCat) {
                $c = \is_array($rawCat) ? $rawCat : (array) $rawCat;

                $oldId = (int) ($c['id'] ?? $c['iid'] ?? 0);
                $parentOld = (int) ($c['parent_id'] ?? $c['parentId'] ?? 0);

                if ($oldId > 0 && $parentOld > 0 && isset($oldIdToNewCat[$oldId], $oldIdToNewCat[$parentOld])) {
                    $cat = $oldIdToNewCat[$oldId];
                    $cat->setParent($oldIdToNewCat[$parentOld]);
                    $em->persist($cat);
                }
            }
        }
        $em->flush();

        // Evaluations and Links
        foreach ($this->course->resources[RESOURCE_GRADEBOOK] as $gbItem) {
            $categories = (array) ($gbItem->categories ?? []);
            foreach ($categories as $rawCat) {
                $c = \is_array($rawCat) ? $rawCat : (array) $rawCat;

                $oldId = (int) ($c['id'] ?? $c['iid'] ?? 0);
                if ($oldId <= 0 || !isset($oldIdToNewCat[$oldId])) {
                    continue;
                }

                $dstCat = $oldIdToNewCat[$oldId];

                // Evaluations
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
                        ->setType((string) ($e['type'] ?? 'evaluation'))
                        ->setVisible((int) ($e['visible'] ?? 1))
                        ->setLocked((int) ($e['locked'] ?? 0))
                    ;

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

                // Links
                foreach ((array) ($c['links'] ?? []) as $rawLink) {
                    $l = \is_array($rawLink) ? $rawLink : (array) $rawLink;

                    $linkType = (int) ($l['type'] ?? $l['link_type'] ?? 0);
                    $legacyRef = (int) ($l['ref_id'] ?? $l['refId'] ?? 0);

                    if ($linkType <= 0 || $legacyRef <= 0) {
                        $this->dlog('restore_gradebook: skipping link (missing type/ref)', $l);
                        continue;
                    }

                    $resourceType = $this->gb_guessResourceTypeByLinkType($linkType);
                    $newRefId = $this->gb_resolveDestinationId($resourceType, $legacyRef);

                    if ($resourceType === null) {
                        $this->dlog('restore_gradebook: skipping link (type not mapped)', [
                            'type' => $linkType,
                            'legacyRef' => $legacyRef,
                        ]);
                        continue;
                    }

                    if ($newRefId <= 0) {
                        $this->dlog('restore_gradebook: skipping link (no destination id)', [
                            'type' => $linkType,
                            'legacyRef' => $legacyRef,
                            'resourceType' => (string) $resourceType,
                        ]);
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

        // Direct mapping from SCORM bucket
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

        // typical folders with *.zip
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

        // look for imsmanifest.xml anywhere, then zip that folder
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

    /**
     * Returns the correct source root used to resolve legacy HTML dependencies
     * from the extracted CourseArchiver folder (local), not from a legacy server absolute path.
     */
    private function getHtmlRewriteSourceRoot(string $courseDir, ?callable $DBG = null): string
    {
        $candidates = [];

        // 1) Preferred: __meta.archiver_root (extracted CourseArchiver folder)
        $meta = $this->course->resources['__meta'] ?? null;
        if (is_array($meta) && !empty($meta['archiver_root'])) {
            $candidates[] = (string) $meta['archiver_root'];
        } elseif (is_object($meta) && !empty($meta->archiver_root)) {
            $candidates[] = (string) $meta->archiver_root;
        }

        // 2) If your class has resolveImportRoot(), try it
        if (method_exists($this, 'resolveImportRoot')) {
            try {
                $resolved = $this->resolveImportRoot();
                if (is_array($resolved) && !empty($resolved['dir'])) {
                    $candidates[] = (string) $resolved['dir'];
                } elseif (is_string($resolved) && '' !== $resolved) {
                    $candidates[] = $resolved;
                }
            } catch (Throwable $e) {
                if (null !== $DBG) {
                    $DBG('html.sourceRoot.resolveImportRoot.error', [
                        'message' => $e->getMessage(),
                        'class' => get_class($e),
                    ]);
                }
            }
        }

        // 3) Legacy backup_path (only if it exists locally)
        if (!empty($this->course->backup_path)) {
            $candidates[] = (string) $this->course->backup_path;
        }

        foreach ($candidates as $candidate) {
            $candidate = rtrim(trim((string) $candidate), '/');
            if ('' === $candidate || !is_dir($candidate)) {
                continue;
            }

            // Most common: extracted folder contains "document/" directly
            if (is_dir($candidate.'/document')) {
                if (null !== $DBG) {
                    $DBG('html.sourceRoot.selected', ['srcRoot' => $candidate, 'reason' => 'has_document_dir']);
                }
                return $candidate;
            }

            // Alternative: candidate/<courseDir>/document/
            if ('' !== $courseDir && is_dir($candidate.'/'.$courseDir.'/document')) {
                $srcRoot = $candidate.'/'.$courseDir;
                if (null !== $DBG) {
                    $DBG('html.sourceRoot.selected', ['srcRoot' => $srcRoot, 'reason' => 'root_contains_courseDir']);
                }
                return $srcRoot;
            }
        }

        if (null !== $DBG) {
            $DBG('html.sourceRoot.notFound', ['courseDir' => $courseDir]);
        }

        return '';
    }

    /**
     * Return the first non-empty resource bag found for the given keys.
     *
     * @param array<int, string> $keys
     */
    private function getBag(array $keys): array
    {
        foreach ($keys as $k) {
            $bag = $this->course->resources[$k] ?? null;
            if (!empty($bag) && is_array($bag)) {
                return $bag;
            }
        }

        return [];
    }

    /**
     * Best-effort resolver for SCORM package zip.
     * - If $srcLpId > 0: uses existing findScormPackageForLp()
     * - Else: uses scorm bucket entry path/name (folder or zip)
     *
     * @return array{zip:string,temp:bool}
     */
    private function findScormPackageForEntry(object $sc): array
    {
        $srcLpId = (int) ($sc->source_lp_id ?? 0);
        if ($srcLpId > 0 && method_exists($this, 'findScormPackageForLp')) {
            /** @var array{zip:string,temp:bool} $pkg */
            $pkg = $this->findScormPackageForLp($srcLpId);
            if (!empty($pkg['zip'])) {
                return $pkg;
            }
        }

        $backupPath = (string) ($this->course->backup_path ?? '');
        if ('' === $backupPath) {
            return ['zip' => '', 'temp' => false];
        }

        $scormDir = rtrim($backupPath, '/').'/scorm';

        $name = trim((string) ($sc->name ?? ''));
        $path = trim((string) ($sc->path ?? ''));
        $folder = trim(str_replace('\\', '/', $path), '/');
        if ('' === $folder) {
            $folder = $name;
        }
        if ('' === $folder) {
            return ['zip' => '', 'temp' => false];
        }

        $abs = $scormDir.'/'.$folder;

        // Direct zip file
        if (is_file($abs) && str_ends_with(strtolower($abs), '.zip')) {
            return ['zip' => $abs, 'temp' => false];
        }
        if (is_file($abs.'.zip')) {
            return ['zip' => $abs.'.zip', 'temp' => false];
        }

        // Folder -> temp zip
        if (is_dir($abs)) {
            return $this->zipDirectoryToTemp($abs, 'scorm_'.$folder);
        }

        return ['zip' => '', 'temp' => false];
    }

    /**
     * Zip a directory to a temporary zip file.
     *
     * @return array{zip:string,temp:bool}
     */
    private function zipDirectoryToTemp(string $dirAbs, string $prefix): array
    {
        if (!class_exists(\ZipArchive::class)) {
            error_log('RESTORE_SCORM_ZIP: ZipArchive is not available, cannot zip directory');
            return ['zip' => '', 'temp' => false];
        }

        $tmp = rtrim(sys_get_temp_dir(), '/').'/'.$prefix.'_'.uniqid('', true).'.zip';

        $zip = new \ZipArchive();
        if (true !== $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            error_log('RESTORE_SCORM_ZIP: Failed to create temp zip: '.$tmp);
            return ['zip' => '', 'temp' => false];
        }

        $dirAbs = rtrim($dirAbs, '/');
        $baseLen = strlen($dirAbs) + 1;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirAbs, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $fs) {
            /** @var \SplFileInfo $fs */
            $abs = $fs->getPathname();
            $rel = substr($abs, $baseLen);

            if ($fs->isDir()) {
                $zip->addEmptyDir(rtrim($rel, '/'));
                continue;
            }

            if ($fs->isFile()) {
                $zip->addFile($abs, $rel);
            }
        }

        $zip->close();

        return ['zip' => $tmp, 'temp' => true];
    }
}
