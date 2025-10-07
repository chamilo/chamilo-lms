<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use AllowDynamicProperties;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradeModel;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\Course as CourseEntity;
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
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use CourseManager;
use Database;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use DocumentManager;
use GroupManager;
use learnpath;
use PhpZip\ZipFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

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
    /** Debug flag (default: true). Toggle with setDebug(). */
    private bool $debug = true;

    /**
     * The course-object.
     */
    public $course;
    public $destination_course_info;

    /** What to do with files with same name (FILE_SKIP, FILE_RENAME, FILE_OVERWRITE). */
    public $file_option;
    public $set_tools_invisible_by_default;
    public $skip_content;

    /** Restore order (keep existing order; docs first). */
    public $tools_to_restore = [
        'documents',
        'announcements',
        'attendance',
        'course_descriptions',
        'events',
        'forum_category',
        'forums',
        // 'forum_topics',
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

    /** Setting per tool */
    public $tool_copy_settings = [];

    /** If true adds the text "copy" in the title of an item (only for LPs right now). */
    public $add_text_in_items = false;

    public $destination_course_id;
    public bool $copySessionContent = false;

    /** Optional course origin id (legacy). */
    private $course_origin_id = null;

    /** First teacher (owner) used for forums/posts. */
    private $first_teacher_id = 0;

    /** Destination course entity cache. */
    private $destination_course_entity;

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
                $ctx = ' ' . json_encode(
                        $context,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
                    );
            } catch (\Throwable $e) {
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
     * CourseRestorer constructor.
     *
     * @param Course $course
     */
    public function __construct($course)
    {
        // Read env constant/course hint if present
        if (defined('COURSE_RESTORER_DEBUG')) {
            $this->debug = (bool) constant('COURSE_RESTORER_DEBUG');
        }

        $this->course = $course;
        $courseInfo = api_get_course_info($this->course->code);
        $this->course_origin_id = !empty($courseInfo) ? $courseInfo['real_id'] : null;

        $this->file_option = FILE_RENAME;
        $this->set_tools_invisible_by_default = false;
        $this->skip_content = [];

        $this->dlog('Ctor: initial course info', [
            'course_code' => $this->course->code ?? null,
            'origin_id'   => $this->course_origin_id,
            'has_resources' => is_array($this->course->resources ?? null),
            'resource_keys' => array_keys((array) ($this->course->resources ?? [])),
        ]);
    }

    /**
     * Set the file-option.
     *
     * @param int $option FILE_SKIP, FILE_RENAME or FILE_OVERWRITE
     */
    public function set_file_option($option = FILE_OVERWRITE)
    {
        $this->file_option = $option;
        $this->dlog('File option set', ['file_option' => $this->file_option]);
    }

    /**
     * @param bool $status
     */
    public function set_add_text_in_items($status)
    {
        $this->add_text_in_items = $status;
    }

    /**
     * @param array $array
     */
    public function set_tool_copy_settings($array)
    {
        $this->tool_copy_settings = $array;
    }

    /** Normalize forum keys so internal bags are always available. */
    private function normalizeForumKeys(): void
    {
        if (!is_array($this->course->resources ?? null)) {
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
            'forum_count'        => isset($r['forum']) && is_array($r['forum']) ? count($r['forum']) : 0,
            'thread_count'       => isset($r['thread']) && is_array($r['thread']) ? count($r['thread']) : 0,
            'post_count'         => isset($r['post']) && is_array($r['post']) ? count($r['post']) : 0,
        ]);
    }

    private function resetDoctrineIfClosed(): void
    {
        try {
            $em = \Database::getManager();
            if (!$em->isOpen()) {
                $registry = Container::$container->get('doctrine');
                $registry->resetManager();
            } else {
                $em->clear();
            }
        } catch (\Throwable $e) {
            error_log('COURSE_DEBUG: resetDoctrineIfClosed failed: '.$e->getMessage());
        }
    }

    /**
     * Entry point.
     */
    public function restore(
        $destination_course_code = '',
        $session_id = 0,
        $update_course_settings = false,
        $respect_base_content = false
    ) {
        $this->dlog('Restore() called', [
            'destination_code'     => $destination_course_code,
            'session_id'           => (int) $session_id,
            'update_course_settings' => (bool) $update_course_settings,
            'respect_base_content' => (bool) $respect_base_content,
        ]);

        // Resolve destination course
        $course_info = $destination_course_code === ''
            ? api_get_course_info()
            : api_get_course_info($destination_course_code);

        if (empty($course_info) || empty($course_info['real_id'])) {
            $this->dlog('Destination course not resolved or missing real_id', ['course_info' => $course_info]);
            return false;
        }

        $this->destination_course_info  = $course_info;
        $this->destination_course_id    = (int) $course_info['real_id'];
        $this->destination_course_entity = api_get_course_entity($this->destination_course_id);

        // Resolve teacher for forum/thread/post ownership
        $this->first_teacher_id = api_get_user_id();
        $teacher_list = CourseManager::get_teacher_list_from_course_code($course_info['code']);
        if (!empty($teacher_list)) {
            foreach ($teacher_list as $t) { $this->first_teacher_id = (int) $t['user_id']; break; }
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
                if (api_is_valid_ascii($line)) { unset($lines[$k]); }
            }
            $sample_text = implode("\n", $lines);
            $this->course->encoding = api_detect_encoding($sample_text, $course_info['language']);
        }
        $this->course->to_system_encoding();
        $this->dlog('Encoding resolved', ['encoding' => $this->course->encoding ?? '']);

        // Normalize forum bags
        $this->normalizeForumKeys();

        // Dump a compact view of the resource bags before restoring
        $this->debug_course_resources_simple(null);

        // Restore tools
        foreach ($this->tools_to_restore as $tool) {
            $fn = 'restore_'.$tool;
            if (method_exists($this, $fn)) {
                $this->dlog('Starting tool restore', ['tool' => $tool]);
                try {
                    $this->{$fn}($session_id, $respect_base_content, $destination_course_code);
                } catch (\Throwable $e) {
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

        if ($destination_course_code !== '') {
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
        if (isset($src['visibility']) && $src['visibility'] !== '') {
            $courseEntity->setVisibility((int) $src['visibility']);
        }
        if (array_key_exists('department_name', $src)) {
            $courseEntity->setDepartmentName((string) $src['department_name']);
        }
        if (array_key_exists('department_url', $src)) {
            $courseEntity->setDepartmentUrl((string) $src['department_url']);
        }
        if (!empty($src['category_id'])) {
            $catRepo = Container::getCourseCategoryRepository();
            $cat = $catRepo?->find((int) $src['category_id']);
            if ($cat) {
                $courseEntity->setCategories(new ArrayCollection([$cat]));
            }
        }
        if (array_key_exists('subscribe_allowed', $src)) {
            $courseEntity->setSubscribe((bool) $src['subscribe_allowed']);
        }
        if (array_key_exists('unsubscribe', $src)) {
            $courseEntity->setUnsubscribe((bool) $src['unsubscribe']);
        }

        $em = Database::getManager();
        $em->persist($courseEntity);
        $em->flush();

        $this->dlog('Course settings restored');
    }

    private function projectUploadBase(): string
    {
        /** @var KernelInterface $kernel */
        $kernel = Container::$container->get('kernel');
        return rtrim($kernel->getProjectDir(), '/').'/var/upload/resource';
    }

    private function resourceFileAbsPathFromDocument(CDocument $doc): ?string
    {
        $node = $doc->getResourceNode();
        if (!$node) return null;

        $file = $node->getFirstResourceFile();
        if (!$file) return null;

        /** @var ResourceNodeRepository $rnRepo */
        $rnRepo = Container::$container->get(ResourceNodeRepository::class);
        $rel    = $rnRepo->getFilename($file);
        if (!$rel) return null;

        $abs = $this->projectUploadBase().$rel;
        return is_readable($abs) ? $abs : null;
    }

    /**
     * Restore documents.
     */
    public function restore_documents($session_id = 0, $respect_base_content = false, $destination_course_code = '')
    {
        if (!$this->course->has_resources(RESOURCE_DOCUMENT)) {
            $this->dlog('restore_documents: no document resources');
            return;
        }

        $courseInfo   = $this->destination_course_info;
        $docRepo      = Container::getDocumentRepository();
        $courseEntity = api_get_course_entity($courseInfo['real_id']);
        $session      = api_get_session_entity((int)$session_id);
        $group        = api_get_group_entity(0);

        $copyMode = empty($this->course->backup_path);
        $srcRoot  = $copyMode ? null : rtrim((string)$this->course->backup_path, '/').'/';

        $this->dlog('restore_documents: begin', [
            'files'   => count($this->course->resources[RESOURCE_DOCUMENT] ?? []),
            'session' => (int) $session_id,
            'mode'    => $copyMode ? 'copy' : 'import',
            'srcRoot' => $srcRoot,
        ]);

        // 1) folders
        $folders = [];
        foreach ($this->course->resources[RESOURCE_DOCUMENT] as $k => $item) {
            if ($item->file_type !== FOLDER) { continue; }

            $rel = '/'.ltrim(substr($item->path, 8), '/');
            if ($rel === '/') { continue; }

            $parts = array_values(array_filter(explode('/', $rel)));
            $accum = '';
            $parentId = 0;

            foreach ($parts as $i => $seg) {
                $accum .= '/'.$seg;
                if (isset($folders[$accum])) { $parentId = $folders[$accum]; continue; }

                $parentResource = $parentId ? $docRepo->find($parentId) : $courseEntity;
                $title = ($i === count($parts)-1) ? ($item->title ?: $seg) : $seg;

                $existing = $docRepo->findCourseResourceByTitle(
                    $title, $parentResource->getResourceNode(), $courseEntity, $session, $group
                );

                if ($existing) {
                    $iid = method_exists($existing,'getIid') ? $existing->getIid() : 0;
                    $this->dlog('restore_documents: reuse folder', ['title' => $title, 'iid' => $iid]);
                } else {
                    $entity = DocumentManager::addDocument(
                        ['real_id'=>$courseInfo['real_id'],'code'=>$courseInfo['code']],
                        $accum, 'folder', 0, $title, null, 0, null, 0, (int)$session_id, 0, false, '', $parentId, ''
                    );
                    $iid = method_exists($entity,'getIid') ? $entity->getIid() : 0;
                    $this->dlog('restore_documents: created folder', ['title' => $title, 'iid' => $iid]);
                }

                $folders[$accum] = $iid;
                if ($i === count($parts)-1) {
                    $this->course->resources[RESOURCE_DOCUMENT][$k]->destination_id = $iid;
                }
                $parentId = $iid;
            }
        }

        // 2) files
        foreach ($this->course->resources[RESOURCE_DOCUMENT] as $k => $item) {
            if ($item->file_type !== DOCUMENT) { continue; }

            $srcPath  = null;
            $rawTitle = $item->title ?: basename((string)$item->path);
            $ext      = strtolower(pathinfo($rawTitle, PATHINFO_EXTENSION));
            $isHtml   = in_array($ext, ['html','htm'], true);

            if ($copyMode) {
                $srcDoc = null;
                if (!empty($item->source_id)) {
                    $srcDoc = $docRepo->find((int)$item->source_id);
                }
                if (!$srcDoc) {
                    $this->dlog('restore_documents: source CDocument not found by source_id', ['source_id' => $item->source_id ?? null]);
                    continue;
                }
                $srcPath = $this->resourceFileAbsPathFromDocument($srcDoc);
                if (!$srcPath) {
                    $this->dlog('restore_documents: source file not readable from ResourceFile', ['source_id' => (int)$item->source_id]);
                    continue;
                }
            } else {
                $srcPath = $srcRoot.$item->path;
                if (!is_file($srcPath) || !is_readable($srcPath)) {
                    $this->dlog('restore_documents: source file not found/readable', ['src' => $srcPath]);
                    continue;
                }
            }

            $rel       = '/'.ltrim(substr($item->path, 8), '/');
            $parentRel = rtrim(dirname($rel), '/');
            $parentId  = $folders[$parentRel] ?? 0;
            $parentRes = $parentId ? $docRepo->find($parentId) : $courseEntity;

            $baseTitle  = $rawTitle;
            $finalTitle = $baseTitle;

            $findExisting = function($t) use ($docRepo,$parentRes,$courseEntity,$session,$group){
                $e = $docRepo->findCourseResourceByTitle($t, $parentRes->getResourceNode(), $courseEntity, $session, $group);
                return $e && method_exists($e,'getIid') ? $e->getIid() : null;
            };

            $existsIid = $findExisting($finalTitle);
            if ($existsIid) {
                $this->dlog('restore_documents: collision', ['title' => $finalTitle, 'policy' => $this->file_option]);
                if ($this->file_option === FILE_SKIP) {
                    $this->course->resources[RESOURCE_DOCUMENT][$k]->destination_id = $existsIid;
                    continue;
                }
                $pi   = pathinfo($baseTitle);
                $name = $pi['filename'] ?? $baseTitle;
                $ext2 = isset($pi['extension']) && $pi['extension'] !== '' ? '.'.$pi['extension'] : '';
                $i=1;
                while ($findExisting($finalTitle)) { $finalTitle = $name.'_'.$i.$ext2; $i++; }
            }

            $content  = '';
            $realPath = '';
            if ($isHtml) {
                $raw = @file_get_contents($srcPath) ?: '';
                if (defined('UTF8_CONVERT') && UTF8_CONVERT) { $raw = utf8_encode($raw); }
                $content = DocumentManager::replaceUrlWithNewCourseCode(
                    $raw,
                    $this->course->code,
                    $this->course->destination_path,
                    $this->course->backup_path,
                    $this->course->info['path']
                );
            } else {
                $realPath = $srcPath;
            }

            try {
                $entity = DocumentManager::addDocument(
                    ['real_id'=>$courseInfo['real_id'],'code'=>$courseInfo['code']],
                    $rel,
                    'file',
                    (int)($item->size ?? 0),
                    $finalTitle,
                    $item->comment ?? '',
                    0,
                    null,
                    0,
                    (int)$session_id,
                    0,
                    false,
                    $content,
                    $parentId,
                    $realPath
                );
                $iid = method_exists($entity,'getIid') ? $entity->getIid() : 0;
                $this->course->resources[RESOURCE_DOCUMENT][$k]->destination_id = $iid;
                $this->dlog('restore_documents: file created', [
                    'title' => $finalTitle,
                    'iid'   => $iid,
                    'mode'  => $copyMode ? 'copy' : 'import'
                ]);
            } catch (\Throwable $e) {
                $this->dlog('restore_documents: file create failed', ['title' => $finalTitle, 'error' => $e->getMessage()]);
            }
        }

        $this->dlog('restore_documents: end');
    }

    /**
     * Compact dump of resources: keys, per-bag counts and one sample (trimmed).
     */
    private function debug_course_resources_simple(?string $focusBag = null, int $maxObjFields = 10): void
    {
        try {
            $resources = is_array($this->course->resources ?? null) ? $this->course->resources : [];

            $safe = function ($data): string {
                try {
                    return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '[json_encode_failed]';
                } catch (\Throwable $e) {
                    return '[json_exception: '.$e->getMessage().']';
                }
            };
            $short = function ($v, int $max = 200) {
                if (is_string($v)) {
                    $s = trim($v);
                    return mb_strlen($s) > $max ? (mb_substr($s, 0, $max).'…('.mb_strlen($s).' chars)') : $s;
                }
                if (is_numeric($v) || is_bool($v) || $v === null) return $v;
                return '['.gettype($v).']';
            };
            $sample = function ($item) use ($short, $maxObjFields) {
                $out = [
                    'source_id'      => null,
                    'destination_id' => null,
                    'type'           => null,
                    'has_obj'        => false,
                    'obj_fields'     => [],
                    'has_item_props' => false,
                    'extra'          => [],
                ];
                if (is_object($item) || is_array($item)) {
                    $arr = (array)$item;
                    $out['source_id']      = $arr['source_id']      ?? null;
                    $out['destination_id'] = $arr['destination_id'] ?? null;
                    $out['type']           = $arr['type']           ?? null;
                    $out['has_item_props'] = !empty($arr['item_properties']);

                    $obj = $arr['obj'] ?? null;
                    if (is_object($obj) || is_array($obj)) {
                        $out['has_obj'] = true;
                        $objArr = (array)$obj;
                        $fields = [];
                        $i = 0;
                        foreach ($objArr as $k => $v) {
                            if ($i++ >= $maxObjFields) { $fields['__notice'] = 'truncated'; break; }
                            $fields[$k] = $short($v);
                        }
                        $out['obj_fields'] = $fields;
                    }
                    foreach (['path','title','comment'] as $k) {
                        if (isset($arr[$k])) $out['extra'][$k] = $short($arr[$k]);
                    }
                } else {
                    $out['extra']['_type'] = gettype($item);
                }
                return $out;
            };

            $this->dlog('Resources overview', ['keys' => array_keys($resources)]);

            foreach ($resources as $bagName => $bag) {
                if (!is_array($bag)) {
                    $this->dlog("Bag not an array, skipping", ['bag' => $bagName, 'type' => gettype($bag)]);
                    continue;
                }
                $count = count($bag);
                $this->dlog('Bag count', ['bag' => $bagName, 'count' => $count]);

                if ($count > 0) {
                    $firstKey = array_key_first($bag);
                    $firstVal = $bag[$firstKey];
                    $s = $sample($firstVal);
                    $s['__first_key'] = $firstKey;
                    $s['__class']     = is_object($firstVal) ? get_class($firstVal) : gettype($firstVal);
                    $this->dlog('Bag sample', ['bag' => $bagName, 'sample' => $s]);
                }

                if ($focusBag !== null && $focusBag === $bagName) {
                    $preview = [];
                    $i = 0;
                    foreach ($bag as $k => $v) {
                        if ($i++ >= 10) { $preview[] = ['__notice' => 'truncated-after-10-items']; break; }
                        $preview[] = ['key' => $k, 'sample' => $sample($v)];
                    }
                    $this->dlog('Bag deep preview', ['bag' => $bagName, 'items' => $preview]);
                }
            }
        } catch (\Throwable $e) {
            $this->dlog('Failed to dump resources', ['error' => $e->getMessage()]);
        }
    }

    public function restore_forum_category($session_id = 0, $respect_base_content = false, $destination_course_code = ''): void
    {
        $bag = $this->course->resources['Forum_Category']
            ?? $this->course->resources['forum_category']
            ?? [];

        if (empty($bag)) {
            $this->dlog('restore_forum_category: empty bag');
            return;
        }

        $em      = Database::getManager();
        $catRepo = Container::getForumCategoryRepository();
        $course  = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int)$session_id);

        foreach ($bag as $id => $res) {
            if (!empty($res->destination_id)) { continue; }

            $obj     = is_object($res->obj ?? null) ? $res->obj : (object)[];
            $title   = (string)($obj->cat_title ?? $obj->title ?? "Forum category #$id");
            $comment = (string)($obj->cat_comment ?? $obj->description ?? '');

            $existing = $catRepo->findOneBy(['title' => $title, 'resourceNode.parent' => $course->getResourceNode()]);
            if ($existing) {
                $destIid = (int)$existing->getIid();
                if (!isset($this->course->resources['Forum_Category'])) {
                    $this->course->resources['Forum_Category'] = [];
                }
                $this->course->resources['Forum_Category'][$id]->destination_id = $destIid;
                $this->dlog('restore_forum_category: reuse existing', ['title' => $title, 'iid' => $destIid]);
                continue;
            }

            $cat = (new CForumCategory())
                ->setTitle($title)
                ->setCatComment($comment)
                ->setParent($course)
                ->addCourseLink($course, $session);

            $catRepo->create($cat);
            $em->flush();

            $this->course->resources['Forum_Category'][$id]->destination_id = (int)$cat->getIid();
            $this->dlog('restore_forum_category: created', ['title' => $title, 'iid' => (int)$cat->getIid()]);
        }

        $this->dlog('restore_forum_category: done', ['count' => count($bag)]);
    }

    public function restore_forums(int $sessionId = 0): void
    {
        $forumsBag = $this->course->resources['forum'] ?? [];
        if (empty($forumsBag)) {
            $this->dlog('restore_forums: empty forums bag');
            return;
        }

        $em        = Database::getManager();
        $catRepo   = Container::getForumCategoryRepository();
        $forumRepo = Container::getForumRepository();

        $course  = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity($sessionId);

        // Build/ensure categories
        $catBag = $this->course->resources['Forum_Category'] ?? $this->course->resources['forum_category'] ?? [];
        $catMap = [];

        if (!empty($catBag)) {
            foreach ($catBag as $srcCatId => $res) {
                if (!empty($res->destination_id)) {
                    $catMap[(int)$srcCatId] = (int)$res->destination_id;
                    continue;
                }

                $obj     = is_object($res->obj ?? null) ? $res->obj : (object)[];
                $title   = (string)($obj->cat_title ?? $obj->title ?? "Forum category #$srcCatId");
                $comment = (string)($obj->cat_comment ?? $obj->description ?? '');

                $cat = (new CForumCategory())
                    ->setTitle($title)
                    ->setCatComment($comment)
                    ->setParent($course)
                    ->addCourseLink($course, $session);

                $catRepo->create($cat);
                $em->flush();

                $destIid = (int)$cat->getIid();
                $catMap[(int)$srcCatId] = $destIid;

                if (!isset($this->course->resources['Forum_Category'])) {
                    $this->course->resources['Forum_Category'] = [];
                }
                $this->course->resources['Forum_Category'][$srcCatId]->destination_id = $destIid;

                $this->dlog('restore_forums: created category', ['src_id' => (int)$srcCatId, 'iid' => $destIid, 'title' => $title]);
            }
        }

        // Default category "General" if needed
        $defaultCategory = null;
        $ensureDefault = function() use (&$defaultCategory, $course, $session, $catRepo, $em): CForumCategory {
            if ($defaultCategory instanceof CForumCategory) {
                return $defaultCategory;
            }
            $defaultCategory = (new CForumCategory())
                ->setTitle('General')
                ->setCatComment('')
                ->setParent($course)
                ->addCourseLink($course, $session);
            $catRepo->create($defaultCategory);
            $em->flush();
            return $defaultCategory;
        };

        // Create forums and their topics
        foreach ($forumsBag as $srcForumId => $forumRes) {
            if (!is_object($forumRes) || !is_object($forumRes->obj)) { continue; }
            $p = (array)$forumRes->obj;

            $dstCategory = null;
            $srcCatId = (int)($p['forum_category'] ?? 0);
            if ($srcCatId > 0 && isset($catMap[$srcCatId])) {
                $dstCategory = $catRepo->find($catMap[$srcCatId]);
            }
            if (!$dstCategory && count($catMap) === 1) {
                $onlyDestIid = (int)reset($catMap);
                $dstCategory = $catRepo->find($onlyDestIid);
            }
            if (!$dstCategory) {
                $dstCategory = $ensureDefault();
            }

            $forum = (new CForum())
                ->setTitle($p['forum_title'] ?? ('Forum #'.$srcForumId))
                ->setForumComment((string)($p['forum_comment'] ?? ''))
                ->setForumCategory($dstCategory)
                ->setAllowAnonymous((int)($p['allow_anonymous'] ?? 0))
                ->setAllowEdit((int)($p['allow_edit'] ?? 0))
                ->setApprovalDirectPost((string)($p['approval_direct_post'] ?? '0'))
                ->setAllowAttachments((int)($p['allow_attachments'] ?? 1))
                ->setAllowNewThreads((int)($p['allow_new_threads'] ?? 1))
                ->setDefaultView($p['default_view'] ?? 'flat')
                ->setForumOfGroup((string)($p['forum_of_group'] ?? 0))
                ->setForumGroupPublicPrivate($p['forum_group_public_private'] ?? 'public')
                ->setModerated((bool)($p['moderated'] ?? false))
                ->setStartTime(!empty($p['start_time']) && $p['start_time'] !== '0000-00-00 00:00:00'
                    ? api_get_utc_datetime($p['start_time'], true, true) : null)
                ->setEndTime(!empty($p['end_time']) && $p['end_time'] !== '0000-00-00 00:00:00'
                    ? api_get_utc_datetime($p['end_time'], true, true) : null)
                ->setParent($dstCategory ?: $course)
                ->addCourseLink($course, $session);

            $forumRepo->create($forum);
            $em->flush();

            $this->course->resources['forum'][$srcForumId]->destination_id = (int)$forum->getIid();
            $this->dlog('restore_forums: created forum', [
                'src_forum_id' => (int)$srcForumId,
                'dst_forum_iid'=> (int)$forum->getIid(),
                'category_iid' => (int)$dstCategory->getIid(),
            ]);

            // Topics of this forum
            $topicsBag = $this->course->resources['thread'] ?? [];
            foreach ($topicsBag as $srcThreadId => $topicRes) {
                if (!is_object($topicRes) || !is_object($topicRes->obj)) { continue; }
                if ((int)$topicRes->obj->forum_id === (int)$srcForumId) {
                    $tid = $this->restore_topic((int)$srcThreadId, (int)$forum->getIid(), $sessionId);
                    $this->dlog('restore_forums: topic restored', [
                        'src_thread_id' => (int)$srcThreadId,
                        'dst_thread_iid'=> (int)($tid ?? 0),
                        'dst_forum_iid' => (int)$forum->getIid(),
                    ]);
                }
            }
        }

        $this->dlog('restore_forums: done', ['forums' => count($forumsBag)]);
    }

    public function restore_topic(int $srcThreadId, int $dstForumId, int $sessionId = 0): ?int
    {
        $topicsBag = $this->course->resources['thread'] ?? [];
        $topicRes  = $topicsBag[$srcThreadId] ?? null;
        if (!$topicRes || !is_object($topicRes->obj)) {
            $this->dlog('restore_topic: missing topic object', ['src_thread_id' => $srcThreadId]);
            return null;
        }

        $em         = Database::getManager();
        $forumRepo  = Container::getForumRepository();
        $threadRepo = Container::getForumThreadRepository();
        $postRepo   = Container::getForumPostRepository();

        $course  = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int)$sessionId);
        $user    = api_get_user_entity($this->first_teacher_id);

        /** @var CForum|null $forum */
        $forum = $forumRepo->find($dstForumId);
        if (!$forum) {
            $this->dlog('restore_topic: destination forum not found', ['dst_forum_id' => $dstForumId]);
            return null;
        }

        $p = (array)$topicRes->obj;

        $thread = (new CForumThread())
            ->setTitle((string)($p['thread_title'] ?? "Thread #$srcThreadId"))
            ->setForum($forum)
            ->setUser($user)
            ->setThreadDate(new \DateTime(api_get_utc_datetime(), new \DateTimeZone('UTC')))
            ->setThreadSticky((bool)($p['thread_sticky'] ?? false))
            ->setThreadTitleQualify((string)($p['thread_title_qualify'] ?? ''))
            ->setThreadQualifyMax((float)($p['thread_qualify_max'] ?? 0))
            ->setThreadWeight((float)($p['thread_weight'] ?? 0))
            ->setThreadPeerQualify((bool)($p['thread_peer_qualify'] ?? false))
            ->setParent($forum)
            ->addCourseLink($course, $session);

        $threadRepo->create($thread);
        $em->flush();

        $this->course->resources['thread'][$srcThreadId]->destination_id = (int)$thread->getIid();
        $this->dlog('restore_topic: created', [
            'src_thread_id' => $srcThreadId,
            'dst_thread_iid'=> (int)$thread->getIid(),
            'dst_forum_iid' => (int)$forum->getIid(),
        ]);

        // Posts
        $postsBag = $this->course->resources[ 'post'] ?? [];
        foreach ($postsBag as $srcPostId => $postRes) {
            if (!is_object($postRes) || !is_object($postRes->obj)) { continue; }
            if ((int)$postRes->obj->thread_id === (int)$srcThreadId) {
                $pid = $this->restore_post((int)$srcPostId, (int)$thread->getIid(), (int)$forum->getIid(), $sessionId);
                $this->dlog('restore_topic: post restored', ['src_post_id' => (int)$srcPostId, 'dst_post_iid' => (int)($pid ?? 0)]);
            }
        }

        $last = $postRepo->findOneBy(['thread' => $thread], ['postDate' => 'DESC']);
        if ($last) {
            $thread->setThreadLastPost($last);
            $em->persist($thread);
            $em->flush();
        }

        return (int)$thread->getIid();
    }

    public function restore_post(int $srcPostId, int $dstThreadId, int $dstForumId, int $sessionId = 0): ?int
    {
        $postsBag = $this->course->resources['post'] ?? [];
        $postRes  = $postsBag[$srcPostId] ?? null;
        if (!$postRes || !is_object($postRes->obj)) {
            $this->dlog('restore_post: missing post object', ['src_post_id' => $srcPostId]);
            return null;
        }

        $em         = Database::getManager();
        $forumRepo  = Container::getForumRepository();
        $threadRepo = Container::getForumThreadRepository();
        $postRepo   = Container::getForumPostRepository();

        $course  = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int)$sessionId);
        $user    = api_get_user_entity($this->first_teacher_id);

        $thread = $threadRepo->find($dstThreadId);
        $forum  = $forumRepo->find($dstForumId);
        if (!$thread || !$forum) {
            $this->dlog('restore_post: destination thread/forum not found', [
                'dst_thread_id' => $dstThreadId,
                'dst_forum_id'  => $dstForumId,
            ]);
            return null;
        }

        $p = (array)$postRes->obj;

        $post = (new CForumPost())
            ->setTitle((string)($p['post_title'] ?? "Post #$srcPostId"))
            ->setPostText((string)($p['post_text'] ?? ''))
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($user)
            ->setPostDate(new \DateTime(api_get_utc_datetime(), new \DateTimeZone('UTC')))
            ->setPostNotification((bool)($p['post_notification'] ?? false))
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
            ->setParent($thread)
            ->addCourseLink($course, $session);

        if (!empty($p['post_parent_id'])) {
            $parentDestId = (int)($postsBag[$p['post_parent_id']]->destination_id ?? 0);
            if ($parentDestId > 0) {
                $parent = $postRepo->find($parentDestId);
                if ($parent) {
                    $post->setPostParent($parent);
                }
            }
        }

        $postRepo->create($post);
        $em->flush();

        $this->course->resources['post'][$srcPostId]->destination_id = (int)$post->getIid();
        $this->dlog('restore_post: created', [
            'src_post_id'   => (int)$srcPostId,
            'dst_post_iid'  => (int)$post->getIid(),
            'dst_thread_id' => (int)$thread->getIid(),
            'dst_forum_id'  => (int)$forum->getIid(),
        ]);

        return (int)$post->getIid();
    }

    public function restore_link_category($id, $sessionId = 0)
    {
        $sessionId = (int) $sessionId;

        // "No category" short-circuit (legacy used 0 as 'uncategorized').
        if (0 === (int) $id) {
            $this->dlog('restore_link_category: source category is 0 (no category), returning 0');

            return 0;
        }

        $resources = $this->course->resources ?? [];
        $srcCat = $resources[RESOURCE_LINKCATEGORY][$id] ?? null;

        if (!is_object($srcCat)) {
            error_log('COURSE_DEBUG: restore_link_category: source category object not found for id ' . $id);

            return 0;
        }

        // Already restored?
        if (!empty($srcCat->destination_id)) {
            return (int) $srcCat->destination_id;
        }

        $em = Database::getManager();
        $catRepo = Container::getLinkCategoryRepository();
        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity($sessionId);

        // Normalize incoming values
        $title = (string) ($srcCat->title ?? $srcCat->category_title ?? 'Links');
        $description = (string) ($srcCat->description ?? '');

        // Try to find existing category by *title* under this course (we'll filter by course parent in PHP)
        $candidates = $catRepo->findBy(['title' => $title]);

        $existing = null;
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

        // Collision handling
        if ($existing) {
            switch ($this->file_option) {
                case FILE_SKIP:
                    $destIid = (int) $existing->getIid();
                    $this->course->resources[RESOURCE_LINKCATEGORY][$id]->destination_id = $destIid;
                    $this->dlog('restore_link_category: reuse (SKIP)', [
                        'src_cat_id' => (int) $id,
                        'dst_cat_id' => $destIid,
                        'title' => $title,
                    ]);

                    return $destIid;

                case FILE_OVERWRITE:
                    // Update description (keep title)
                    $existing->setDescription($description);
                    // Ensure course/session link
                    if (method_exists($existing, 'setParent')) {
                        $existing->setParent($course);
                    }
                    if (method_exists($existing, 'addCourseLink')) {
                        $existing->addCourseLink($course, $session);
                    }

                    $em->persist($existing);
                    $em->flush();

                    $destIid = (int) $existing->getIid();
                    $this->course->resources[RESOURCE_LINKCATEGORY][$id]->destination_id = $destIid;
                    $this->dlog('restore_link_category: overwrite', [
                        'src_cat_id' => (int) $id,
                        'dst_cat_id' => $destIid,
                        'title' => $title,
                    ]);

                    return $destIid;

                case FILE_RENAME:
                default:
                    // Create a new unique title inside the same course parent
                    $base = $title;
                    $i = 1;
                    do {
                        $title = $base . ' (' . $i . ')';
                        $candidates = $catRepo->findBy(['title' => $title]);
                        $exists = false;

                        if (!empty($candidates)) {
                            $courseNode = $course->getResourceNode();
                            foreach ($candidates as $cand) {
                                $node = method_exists($cand, 'getResourceNode') ? $cand->getResourceNode() : null;
                                $parent = $node && method_exists($node, 'getParent') ? $node->getParent() : null;
                                if ($parent && $courseNode && $parent->getId() === $courseNode->getId()) {
                                    $exists = true;
                                    break;
                                }
                            }
                        }

                        $i++;
                    } while ($exists);
                    break;
            }
        }

        // Create new category
        $cat = (new CLinkCategory())
            ->setTitle($title)
            ->setDescription($description);

        if (method_exists($cat, 'setParent')) {
            $cat->setParent($course); // parent ResourceNode: Course
        }
        if (method_exists($cat, 'addCourseLink')) {
            $cat->addCourseLink($course, $session); // visibility link (course, session)
        }

        $em->persist($cat);
        $em->flush();

        $destIid = (int) $cat->getIid();
        $this->course->resources[RESOURCE_LINKCATEGORY][$id]->destination_id = $destIid;

        $this->dlog('restore_link_category: created', [
            'src_cat_id' => (int) $id,
            'dst_cat_id' => $destIid,
            'title' => (string) $title,
        ]);

        return $destIid;
    }

    public function restore_links($session_id = 0)
    {
        if (!$this->course->has_resources(RESOURCE_LINK)) {
            return;
        }

        $resources = $this->course->resources;
        $count = is_array($resources[RESOURCE_LINK] ?? null) ? count($resources[RESOURCE_LINK]) : 0;

        $this->dlog('restore_links: begin', ['count' => $count]);

        $em = Database::getManager();
        $linkRepo = Container::getLinkRepository();
        $catRepo = Container::getLinkCategoryRepository();
        $course = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $session_id);

        // Safe duplicate finder (no dot-path in criteria; filter parent in PHP)
        $findDuplicate = function (string $t, string $u, ?CLinkCategory $cat) use ($linkRepo, $course) {
            $criteria = ['title' => $t, 'url' => $u];
            $criteria['category'] = $cat instanceof CLinkCategory ? $cat : null;

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
            // Normalize (accept values from object or "extra")
            $rawUrl = (string) ($link->url ?? ($link->extra['url'] ?? ''));
            $rawTitle = (string) ($link->title ?? ($link->extra['title'] ?? ''));
            $rawDesc = (string) ($link->description ?? ($link->extra['description'] ?? ''));
            $target = isset($link->target) ? (string) $link->target : null;
            $catSrcId = (int) ($link->category_id ?? 0);
            $onHome = (bool) ($link->on_homepage ?? false);

            $url = trim($rawUrl);
            $title = trim($rawTitle) !== '' ? trim($rawTitle) : $url;

            if ($url === '') {
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

            // Duplicate handling (title + url + category in same course)
            $existing = $findDuplicate($title, $url, $category);

            if ($existing) {
                if ($this->file_option === FILE_SKIP) {
                    $destIid = (int) $existing->getIid();
                    $this->course->resources[RESOURCE_LINK][$oldLinkId] ??= new \stdClass();
                    $this->course->resources[RESOURCE_LINK][$oldLinkId]->destination_id = $destIid;

                    $this->dlog('restore_links: reuse (SKIP)', [
                        'src_link_id' => (int) $oldLinkId,
                        'dst_link_id' => $destIid,
                        'title' => $title,
                        'url' => $url,
                    ]);

                    continue;
                }

                if ($this->file_option === FILE_OVERWRITE) {
                    // Update main fields (keep position/shortcut logic outside)
                    $existing
                        ->setUrl($url)
                        ->setTitle($title)
                        ->setDescription($rawDesc) // rewrite to assets after flush
                        ->setTarget((string) ($target ?? ''));

                    if (method_exists($existing, 'setParent')) {
                        $existing->setParent($course);
                    }
                    if (method_exists($existing, 'addCourseLink')) {
                        $existing->addCourseLink($course, $session);
                    }
                    $existing->setCategory($category); // can be null

                    $em->persist($existing);
                    $em->flush();

                    // Now rewrite legacy "document/..." URLs inside description to Assets
                    try {
                        $backupRoot = $this->course->backup_path ?? '';
                        $extraRoots = array_filter([
                            $this->course->destination_path ?? '',
                            $this->course->origin_path ?? '',
                        ]);
                        $rewritten = ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                            $rawDesc,
                            $existing,
                            $backupRoot,
                            $extraRoots
                        );

                        if ($rewritten !== $rawDesc) {
                            $existing->setDescription($rewritten);
                            $em->persist($existing);
                            $em->flush();
                        }
                    } catch (\Throwable $e) {
                        error_log('COURSE_DEBUG: restore_links: asset rewrite failed (overwrite): ' . $e->getMessage());
                    }

                    $destIid = (int) $existing->getIid();
                    $this->course->resources[RESOURCE_LINK][$oldLinkId] ??= new \stdClass();
                    $this->course->resources[RESOURCE_LINK][$oldLinkId]->destination_id = $destIid;

                    $this->dlog('restore_links: overwrite', [
                        'src_link_id' => (int) $oldLinkId,
                        'dst_link_id' => $destIid,
                        'title' => $title,
                        'url' => $url,
                    ]);

                    continue;
                }

                // FILE_RENAME (default): make title unique among same course/category
                $base = $title;
                $i = 1;
                do {
                    $title = $base . ' (' . $i . ')';
                    $i++;
                } while ($findDuplicate($title, $url, $category));
            }

            // Create new link entity
            $entity = (new CLink())
                ->setUrl($url)
                ->setTitle($title)
                ->setDescription($rawDesc) // rewrite to assets after first flush
                ->setTarget((string) ($target ?? ''));

            if (method_exists($entity, 'setParent')) {
                $entity->setParent($course); // parent ResourceNode: Course
            }
            if (method_exists($entity, 'addCourseLink')) {
                $entity->addCourseLink($course, $session); // visibility (course, session)
            }

            if ($category instanceof CLinkCategory) {
                $entity->setCategory($category);
            }

            // Persist to create the ResourceNode; we need it for Asset attachment
            $em->persist($entity);
            $em->flush();

            // Rewrite legacy "document/..." URLs inside description to Assets, then save if changed
            try {
                $backupRoot = $this->course->backup_path ?? '';
                $extraRoots = array_filter([
                    $this->course->destination_path ?? '',
                    $this->course->origin_path ?? '',
                ]);
                $rewritten = ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                    $rawDesc,
                    $entity,
                    (string) $backupRoot,
                    $extraRoots
                );

                if ($rewritten !== (string) $rawDesc) {
                    $entity->setDescription($rewritten);
                    $em->persist($entity);
                    $em->flush();
                }
            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: restore_links: asset rewrite failed (create): ' . $e->getMessage());
            }

            // Map destination id back into resources
            $destIid = (int) $entity->getIid();

            if (!isset($this->course->resources[RESOURCE_LINK][$oldLinkId])) {
                $this->course->resources[RESOURCE_LINK][$oldLinkId] = new \stdClass();
            }
            $this->course->resources[RESOURCE_LINK][$oldLinkId]->destination_id = $destIid;

            $this->dlog('restore_links: created', [
                'src_link_id' => (int) $oldLinkId,
                'dst_link_id' => $destIid,
                'title' => $title,
                'url' => $url,
                'category' => $category ? $category->getTitle() : null,
            ]);

            // Optional: emulate "show on homepage" by ensuring ResourceLink exists (UI/Controller handles real shortcut)
            if (!empty($onHome)) {
                try {
                    // Ensure resource link is persisted (it already is via addCourseLink)
                    // Any actual shortcut creation should be delegated to the appropriate service/controller.
                    $em->persist($entity);
                    $em->flush();
                } catch (\Throwable $e) {
                    error_log('COURSE_DEBUG: restore_links: homepage flag handling failed: ' . $e->getMessage());
                }
            }
        }

        $this->dlog('restore_links: end');
    }

    public function restore_tool_intro($sessionId = 0)
    {
        $resources = $this->course->resources ?? [];
        $bagKey = null;
        if ($this->course->has_resources(RESOURCE_TOOL_INTRO)) {
            $bagKey = RESOURCE_TOOL_INTRO;
        } elseif (!empty($resources['Tool introduction'])) {
            $bagKey = 'Tool introduction';
        }
        if ($bagKey === null || empty($resources[$bagKey]) || !is_array($resources[$bagKey])) {
            return;
        }

        $sessionId = (int) $sessionId;
        $this->dlog('restore_tool_intro: begin', ['count' => count($resources[$bagKey])]);

        $em      = \Database::getManager();
        $course  = api_get_course_entity($this->destination_course_id);
        $session = $sessionId ? api_get_session_entity($sessionId) : null;

        $toolRepo  = $em->getRepository(Tool::class);
        $cToolRepo = $em->getRepository(CTool::class);
        $introRepo = $em->getRepository(CToolIntro::class);

        $rewriteContent = function (string $html) {
            if ($html === '') return '';
            try {
                if (class_exists(ChamiloHelper::class)
                    && method_exists(ChamiloHelper::class, 'rewriteLegacyCourseUrlsToAssets')
                ) {
                    return ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                        $html,
                        api_get_course_entity($this->destination_course_id),
                        (string)($this->course->backup_path ?? ''),
                        array_filter([
                            (string)($this->course->destination_path ?? ''),
                            (string)($this->course->info['path'] ?? ''),
                        ])
                    );
                }
            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: rewriteLegacyCourseUrlsToAssets failed (tool_intro): '.$e->getMessage());
            }

            $out = \DocumentManager::replaceUrlWithNewCourseCode(
                $html,
                $this->course->code,
                $this->course->destination_path,
                $this->course->backup_path,
                $this->course->info['path']
            );
            return $out === false ? '' : $out;
        };

        foreach ($resources[$bagKey] as $rawId => $tIntro) {
            // prefer source->id only if non-empty AND not "0"; otherwise use the bag key ($rawId)
            $toolKey = trim((string)($tIntro->id ?? ''));
            if ($toolKey === '' || $toolKey === '0') {
                $toolKey = (string)$rawId;
            }

            // normalize a couple of common aliases defensively
            $alias = strtolower($toolKey);
            if ($alias === 'homepage' || $alias === 'course_home') {
                $toolKey = 'course_homepage';
            }

            // log exactly what we got to avoid future confusion
            $this->dlog('restore_tool_intro: resolving tool key', [
                'raw_id'  => (string)$rawId,
                'obj_id'  => isset($tIntro->id) ? (string)$tIntro->id : null,
                'toolKey' => $toolKey,
            ]);

            $mapped = $tIntro->destination_id ?? 0;
            if ($mapped > 0) {
                $this->dlog('restore_tool_intro: already mapped, skipping', ['src_id' => $toolKey, 'dst_id' => $mapped]);
                continue;
            }

            $introHtml = $rewriteContent($tIntro->intro_text ?? '');

            // find core Tool by title (e.g., 'course_homepage')
            $toolEntity = $toolRepo->findOneBy(['title' => $toolKey]);
            if (!$toolEntity) {
                $this->dlog('restore_tool_intro: missing Tool entity, skipping', ['tool' => $toolKey]);
                continue;
            }

            // find or create the CTool row for this course+session+title
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
                    ->addCourseLink($course);

                $em->persist($cTool);
                $em->flush();

                $this->dlog('restore_tool_intro: CTool created', [
                    'tool'     => $toolKey,
                    'ctool_id' => (int)$cTool->getIid(),
                ]);
            }

            $intro = $introRepo->findOneBy(['courseTool' => $cTool]);

            if ($intro) {
                if ($this->file_option === FILE_SKIP) {
                    $this->dlog('restore_tool_intro: reuse existing (SKIP)', [
                        'tool'     => $toolKey,
                        'intro_id' => (int)$intro->getIid(),
                    ]);
                } else {
                    $intro->setIntroText($introHtml);
                    $em->persist($intro);
                    $em->flush();

                    $this->dlog('restore_tool_intro: intro overwritten', [
                        'tool'     => $toolKey,
                        'intro_id' => (int)$intro->getIid(),
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
                    'tool'     => $toolKey,
                    'intro_id' => (int)$intro->getIid(),
                ]);
            }

            // map destination back into the legacy resource bag
            if (!isset($this->course->resources[$bagKey][$rawId])) {
                $this->course->resources[$bagKey][$rawId] = new \stdClass();
            }
            $this->course->resources[$bagKey][$rawId]->destination_id = (int)$intro->getIid();
        }

        $this->dlog('restore_tool_intro: end');
    }


    public function restore_events(int $sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_EVENT)) {
            return;
        }

        $resources  = $this->course->resources ?? [];
        $bag        = $resources[RESOURCE_EVENT] ?? [];
        $count      = is_array($bag) ? count($bag) : 0;

        $this->dlog('restore_events: begin', ['count' => $count]);

        /** @var EntityManagerInterface $em */
        $em          = \Database::getManager();
        $course      = api_get_course_entity($this->destination_course_id);
        $session     = api_get_session_entity($sessionId);
        $group       = api_get_group_entity();
        $eventRepo   = Container::getCalendarEventRepository();
        $attachRepo  = Container::getCalendarEventAttachmentRepository();

        // Content rewrite helper (prefer new helper if available)
        $rewriteContent = function (?string $html): string {
            $html = $html ?? '';
            if ($html === '') {
                return '';
            }
            try {
                if (method_exists(ChamiloHelper::class, 'rewriteLegacyCourseUrlsToAssets')) {
                    return ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                        $html,
                        api_get_course_entity($this->destination_course_id),
                        $this->course->backup_path ?? '',
                        array_filter([
                            $this->course->destination_path ?? '',
                            (string) ($this->course->info['path'] ?? ''),
                        ])
                    );
                }
            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: rewriteLegacyCourseUrlsToAssets failed: '.$e->getMessage());
            }

            $out = \DocumentManager::replaceUrlWithNewCourseCode(
                $html,
                $this->course->code,
                $this->course->destination_path,
                $this->course->backup_path,
                $this->course->info['path']
            );

            return $out === false ? '' : (string) $out;
        };

        // Dedupe by title inside same course/session (honor sameFileNameOption)
        $findExistingByTitle = function (string $title) use ($eventRepo, $course, $session) {
            $qb = $eventRepo->getResourcesByCourse($course, $session, null, null, true, true);
            $qb->andWhere('resource.title = :t')->setParameter('t', $title)->setMaxResults(1);
            return $qb->getQuery()->getOneOrNullResult();
        };

        // Attachment source in backup zip (calendar)
        $originPath = rtrim((string)($this->course->backup_path ?? ''), '/').'/upload/calendar/';

        foreach ($bag as $oldId => $raw) {
            // Skip if already mapped to a positive destination id
            $mapped = (int) ($raw->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_events: already mapped, skipping', ['src_id' => (int)$oldId, 'dst_id' => $mapped]);
                continue;
            }

            // Normalize input
            $title = trim((string)($raw->title ?? ''));
            if ($title === '') {
                $title = 'Event';
            }

            $content = $rewriteContent((string)($raw->content ?? ''));

            // Dates: accept various formats; allow empty endDate
            $allDay   = (bool)($raw->all_day ?? false);
            $start    = null;
            $end      = null;
            try {
                $s = (string)($raw->start_date ?? '');
                if ($s !== '') { $start = new \DateTime($s); }
            } catch (\Throwable $e) { $start = null; }
            try {
                $e = (string)($raw->end_date ?? '');
                if ($e !== '') { $end = new \DateTime($e); }
            } catch (\Throwable $e) { $end = null; }

            // Dedupe policy
            $existing = $findExistingByTitle($title);
            if ($existing) {
                switch ($this->file_option) {
                    case FILE_SKIP:
                        $destId = (int)$existing->getIid();
                        $this->course->resources[RESOURCE_EVENT][$oldId] ??= new \stdClass();
                        $this->course->resources[RESOURCE_EVENT][$oldId]->destination_id = $destId;
                        $this->dlog('restore_events: reuse (SKIP)', [
                            'src_id' => (int)$oldId, 'dst_id' => $destId, 'title' => $existing->getTitle()
                        ]);
                        // Try to add missing attachments (no duplicates by filename)
                        $this->restoreEventAttachments($raw, $existing, $originPath, $attachRepo, $em);
                        break;

                    case FILE_OVERWRITE:
                        $existing
                            ->setTitle($title)
                            ->setContent($content)
                            ->setAllDay($allDay)
                            ->setParent($course)
                            ->addCourseLink($course, $session, $group);

                        $existing->setStartDate($start);
                        $existing->setEndDate($end);

                        $em->persist($existing);
                        $em->flush();

                        $this->course->resources[RESOURCE_EVENT][$oldId] ??= new \stdClass();
                        $this->course->resources[RESOURCE_EVENT][$oldId]->destination_id = (int)$existing->getIid();

                        $this->dlog('restore_events: overwrite', [
                            'src_id' => (int)$oldId, 'dst_id' => (int)$existing->getIid(), 'title' => $title
                        ]);

                        $this->restoreEventAttachments($raw, $existing, $originPath, $attachRepo, $em);
                        break;

                    case FILE_RENAME:
                    default:
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

            // Create new entity in course context
            $entity = (new CCalendarEvent())
                ->setTitle($title)
                ->setContent($content)
                ->setAllDay($allDay)
                ->setParent($course)
                ->addCourseLink($course, $session, $group);

            $entity->setStartDate($start);
            $entity->setEndDate($end);

            $em->persist($entity);
            $em->flush();

            // Map new id
            $destId = (int)$entity->getIid();
            $this->course->resources[RESOURCE_EVENT][$oldId] ??= new \stdClass();
            $this->course->resources[RESOURCE_EVENT][$oldId]->destination_id = $destId;

            $this->dlog('restore_events: created', ['src_id' => (int)$oldId, 'dst_id' => $destId, 'title' => $title]);

            // Attachments (backup modern / legacy)
            $this->restoreEventAttachments($raw, $entity, $originPath, $attachRepo, $em);

            // (Optional) Repeat rules / reminders:
            // If your backup exports recurrence/reminders, parse here and populate CCalendarEventRepeat / AgendaReminder.
            // $this->restoreEventRecurrenceAndReminders($raw, $entity, $em);
        }

        $this->dlog('restore_events: end');
    }

    private function restoreEventAttachments(
        object $raw,
        CCalendarEvent $entity,
        string $originPath,
        $attachRepo,
        EntityManagerInterface $em
    ): void {
        // Helper to actually persist + move file
        $persistAttachmentFromFile = function (string $src, string $filename, ?string $comment) use ($entity, $attachRepo, $em) {
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
                );

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
                'event_id' => (int)$entity->getIid(),
                'filename' => $filename,
            ]);
        };

        // Case 1: modern backup fields on object
        if (!empty($raw->attachment_path)) {
            $src = rtrim($originPath, '/').'/'.$raw->attachment_path;
            $filename = (string)($raw->attachment_filename ?? basename($src));
            $comment  = (string)($raw->attachment_comment ?? '');
            $persistAttachmentFromFile($src, $filename, $comment);
            return;
        }

        // Case 2: legacy lookup from old course tables when ->orig present
        if (!empty($this->course->orig)) {
            $table = \Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
            $sql = 'SELECT path, comment, filename
                FROM '.$table.'
                WHERE c_id = '.$this->destination_course_id.'
                  AND agenda_id = '.(int)($raw->source_id ?? 0);
            $res = \Database::query($sql);
            while ($row = \Database::fetch_object($res)) {
                $src = rtrim($originPath, '/').'/'.$row->path;
                $persistAttachmentFromFile($src, (string)$row->filename, (string)$row->comment);
            }
        }
    }

    public function restore_course_descriptions($session_id = 0)
    {
        if (!$this->course->has_resources(RESOURCE_COURSEDESCRIPTION)) {
            return;
        }

        $resources = $this->course->resources;
        $count = is_array($resources[RESOURCE_COURSEDESCRIPTION] ?? null)
            ? count($resources[RESOURCE_COURSEDESCRIPTION])
            : 0;

        $this->dlog('restore_course_descriptions: begin', ['count' => $count]);

        $em      = \Database::getManager();
        $repo    = Container::getCourseDescriptionRepository();
        $course  = api_get_course_entity($this->destination_course_id);
        $session = api_get_session_entity((int) $session_id);

        $rewriteContent = function (string $html) use ($course) {
            if ($html === '') {
                return '';
            }
            if (method_exists(ChamiloHelper::class, 'rewriteLegacyCourseUrlsToAssets')) {
                try {
                    return ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                        $html,
                        $course,
                        $this->course->backup_path ?? '',
                        array_filter([
                            $this->course->destination_path ?? '',
                            (string)($this->course->info['path'] ?? ''),
                        ])
                    );
                } catch (\Throwable $e) {
                    error_log('COURSE_DEBUG: rewriteLegacyCourseUrlsToAssets failed: '.$e->getMessage());
                }
            }
            $out = \DocumentManager::replaceUrlWithNewCourseCode(
                $html,
                $this->course->code,
                $this->course->destination_path,
                $this->course->backup_path,
                $this->course->info['path']
            );

            return $out === false ? '' : $out;
        };

        $findByTypeInCourse = function (int $type) use ($repo, $course, $session) {
            if (method_exists($repo, 'findByTypeInCourse')) {
                return $repo->findByTypeInCourse($type, $course, $session);
            }
            $qb = $repo->getResourcesByCourse($course, $session)->andWhere('resource.descriptionType = :t')->setParameter('t', $type);
            return $qb->getQuery()->getResult();
        };

        $findByTitleInCourse = function (string $title) use ($repo, $course, $session) {
            $qb = $repo->getResourcesByCourse($course, $session)
                ->andWhere('resource.title = :t')
                ->setParameter('t', $title)
                ->setMaxResults(1);
            return $qb->getQuery()->getOneOrNullResult();
        };

        foreach ($resources[RESOURCE_COURSEDESCRIPTION] as $oldId => $cd) {
            $mapped = (int)($cd->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_course_descriptions: already mapped, skipping', [
                    'src_id' => (int)$oldId,
                    'dst_id' => $mapped,
                ]);
                continue;
            }

            $rawTitle   = (string)($cd->title ?? '');
            $rawContent = (string)($cd->content ?? '');
            $type       = (int)($cd->description_type ?? CCourseDescription::TYPE_DESCRIPTION);
            $title      = trim($rawTitle) !== '' ? trim($rawTitle) : $rawTitle;
            $content    = $rewriteContent($rawContent);

            $existingByType = $findByTypeInCourse($type);
            $existingOne    = $existingByType[0] ?? null;

            if ($existingOne) {
                switch ($this->file_option) {
                    case FILE_SKIP:
                        $destIid = (int)$existingOne->getIid();
                        $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId] ??= new \stdClass();
                        $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId]->destination_id = $destIid;

                        $this->dlog('restore_course_descriptions: reuse (SKIP)', [
                            'src_id' => (int)$oldId,
                            'dst_id' => $destIid,
                            'type'   => $type,
                            'title'  => (string)$existingOne->getTitle(),
                        ]);
                        break;

                    case FILE_OVERWRITE:
                        $existingOne
                            ->setTitle($title !== '' ? $title : (string)$existingOne->getTitle())
                            ->setContent($content)
                            ->setDescriptionType($type)
                            ->setProgress((int)($cd->progress ?? 0));
                        $existingOne->setParent($course)->addCourseLink($course, $session);

                        $em->persist($existingOne);
                        $em->flush();

                        $destIid = (int)$existingOne->getIid();
                        $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId] ??= new \stdClass();
                        $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId]->destination_id = $destIid;

                        $this->dlog('restore_course_descriptions: overwrite', [
                            'src_id' => (int)$oldId,
                            'dst_id' => $destIid,
                            'type'   => $type,
                            'title'  => (string)$existingOne->getTitle(),
                        ]);
                        break;

                    case FILE_RENAME:
                    default:
                        $base = $title !== '' ? $title : (string)($cd->extra['title'] ?? 'Description');
                        $i = 1;
                        $candidate = $base;
                        while ($findByTitleInCourse($candidate)) {
                            $i++;
                            $candidate = $base.' ('.$i.')';
                        }
                        $title = $candidate;
                        break;
                }
            }

            $entity = (new CCourseDescription())
                ->setTitle($title)
                ->setContent($content)
                ->setDescriptionType($type)
                ->setProgress((int)($cd->progress ?? 0))
                ->setParent($course)
                ->addCourseLink($course, $session);

            $em->persist($entity);
            $em->flush();

            $destIid = (int)$entity->getIid();

            if (!isset($this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId])) {
                $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId] = new \stdClass();
            }
            $this->course->resources[RESOURCE_COURSEDESCRIPTION][$oldId]->destination_id = $destIid;

            $this->dlog('restore_course_descriptions: created', [
                'src_id' => (int)$oldId,
                'dst_id' => $destIid,
                'type'   => $type,
                'title'  => $title,
            ]);
        }

        $this->dlog('restore_course_descriptions: end');
    }

    private function resourceFileAbsPathFromAnnouncementAttachment(CAnnouncementAttachment $att): ?string
    {
        $node = $att->getResourceNode();
        if (!$node) return null;

        $file = $node->getFirstResourceFile();
        if (!$file) return null;

        /** @var ResourceNodeRepository $rnRepo */
        $rnRepo = Container::$container->get(ResourceNodeRepository::class);
        $rel    = $rnRepo->getFilename($file);
        if (!$rel) return null;

        $abs = $this->projectUploadBase().$rel;
        return is_readable($abs) ? $abs : null;
    }

    public function restore_announcements($sessionId = 0)
    {
        if (!$this->course->has_resources(RESOURCE_ANNOUNCEMENT)) {
            return;
        }

        $sessionId = (int) $sessionId;
        $resources = $this->course->resources;

        $count = is_array($resources[RESOURCE_ANNOUNCEMENT] ?? null)
            ? count($resources[RESOURCE_ANNOUNCEMENT])
            : 0;

        $this->dlog('restore_announcements: begin', ['count' => $count]);

        /** @var EntityManagerInterface $em */
        $em         = \Database::getManager();
        $course     = api_get_course_entity($this->destination_course_id);
        $session    = api_get_session_entity($sessionId);
        $group      = api_get_group_entity();
        $annRepo    = Container::getAnnouncementRepository();
        $attachRepo = Container::getAnnouncementAttachmentRepository();

        $rewriteContent = function (string $html) {
            if ($html === '') return '';
            try {
                if (class_exists(ChamiloHelper::class)
                    && method_exists(ChamiloHelper::class, 'rewriteLegacyCourseUrlsToAssets')) {
                    return ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                        $html,
                        api_get_course_entity($this->destination_course_id),
                        $this->course->backup_path ?? '',
                        array_filter([
                            $this->course->destination_path ?? '',
                            (string)($this->course->info['path'] ?? ''),
                        ])
                    );
                }
            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: rewriteLegacyCourseUrlsToAssets failed: '.$e->getMessage());
            }

            $out = \DocumentManager::replaceUrlWithNewCourseCode(
                $html,
                $this->course->code,
                $this->course->destination_path,
                $this->course->backup_path,
                $this->course->info['path']
            );

            return $out === false ? '' : $out;
        };

        $findExistingByTitle = function (string $title) use ($annRepo, $course, $session) {
            $qb = $annRepo->getResourcesByCourse($course, $session);
            $qb->andWhere('resource.title = :t')->setParameter('t', $title)->setMaxResults(1);
            return $qb->getQuery()->getOneOrNullResult();
        };

        $originPath = rtrim($this->course->backup_path ?? '', '/').'/upload/announcements/';

        foreach ($resources[RESOURCE_ANNOUNCEMENT] as $oldId => $a) {
            $mapped = (int)($a->destination_id ?? 0);
            if ($mapped > 0) {
                $this->dlog('restore_announcements: already mapped, skipping', [
                    'src_id' => (int)$oldId, 'dst_id' => $mapped
                ]);
                continue;
            }

            $title = trim((string)($a->title ?? ''));
            if ($title === '') { $title = 'Announcement'; }

            $contentHtml = (string)($a->content ?? '');
            $contentHtml = $rewriteContent($contentHtml);

            $endDate = null;
            try {
                $rawDate = (string)($a->date ?? '');
                if ($rawDate !== '') { $endDate = new \DateTime($rawDate); }
            } catch (\Throwable $e) { $endDate = null; }

            $emailSent = (bool)($a->email_sent ?? false);

            $existing = $findExistingByTitle($title);
            if ($existing) {
                switch ($this->file_option) {
                    case FILE_SKIP:
                        $destId = (int)$existing->getIid();
                        $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId] ??= new \stdClass();
                        $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId]->destination_id = $destId;
                        $this->dlog('restore_announcements: reuse (SKIP)', [
                            'src_id' => (int)$oldId, 'dst_id' => $destId, 'title' => $existing->getTitle()
                        ]);
                        break;

                    case FILE_OVERWRITE:
                        $existing
                            ->setTitle($title)
                            ->setContent($contentHtml)
                            ->setParent($course)
                            ->addCourseLink($course, $session, $group)
                            ->setEmailSent($emailSent);
                        if ($endDate instanceof \DateTimeInterface) { $existing->setEndDate($endDate); }
                        $em->persist($existing);
                        $em->flush();

                        $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId] ??= new \stdClass();
                        $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId]->destination_id = (int)$existing->getIid();

                        $this->dlog('restore_announcements: overwrite', [
                            'src_id' => (int)$oldId, 'dst_id' => (int)$existing->getIid(), 'title' => $title
                        ]);

                        $this->restoreAnnouncementAttachments($a, $existing, $originPath, $attachRepo, $em);
                        continue 2;

                    case FILE_RENAME:
                    default:
                        $base = $title; $i = 1; $candidate = $base;
                        while ($findExistingByTitle($candidate)) { $i++; $candidate = $base.' ('.$i.')'; }
                        $title = $candidate;
                        break;
                }
            }

            $entity = (new CAnnouncement())
                ->setTitle($title)
                ->setContent($contentHtml)
                ->setParent($course)
                ->addCourseLink($course, $session, $group)
                ->setEmailSent($emailSent);
            if ($endDate instanceof \DateTimeInterface) { $entity->setEndDate($endDate); }

            $em->persist($entity);
            $em->flush();

            $destId = (int)$entity->getIid();
            $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId] ??= new \stdClass();
            $this->course->resources[RESOURCE_ANNOUNCEMENT][$oldId]->destination_id = $destId;

            $this->dlog('restore_announcements: created', [
                'src_id' => (int)$oldId, 'dst_id' => $destId, 'title' => $title
            ]);

            $this->restoreAnnouncementAttachments($a, $entity, $originPath, $attachRepo, $em);
        }

        $this->dlog('restore_announcements: end');
    }

    private function restoreAnnouncementAttachments(
        object $a,
        CAnnouncement $entity,
        string $originPath,
        $attachRepo,
        EntityManagerInterface $em
    ): void {
        $copyMode = empty($this->course->backup_path);

        if ($copyMode) {
            $srcAttachmentIds = [];
            if (!empty($a->attachment_source_id)) { $srcAttachmentIds[] = (int)$a->attachment_source_id; }
            if (!empty($a->attachment_source_ids) && is_array($a->attachment_source_ids)) {
                foreach ($a->attachment_source_ids as $sid) { $sid = (int)$sid; if ($sid > 0) $srcAttachmentIds[] = $sid; }
            }
            if (empty($srcAttachmentIds) && !empty($a->source_id)) {
                $srcAnn = Container::getAnnouncementRepository()->find((int)$a->source_id);
                if ($srcAnn) {
                    $srcAtts = Container::getAnnouncementAttachmentRepository()->findBy(['announcement' => $srcAnn]);
                    foreach ($srcAtts as $sa) { $srcAttachmentIds[] = (int)$sa->getIid(); }
                }
            }

            if (!empty($srcAttachmentIds)) {
                $attRepo = Container::getAnnouncementAttachmentRepository();

                foreach (array_unique($srcAttachmentIds) as $sid) {
                    /** @var CAnnouncementAttachment|null $srcAtt */
                    $srcAtt = $attRepo->find($sid);
                    if (!$srcAtt) { continue; }

                    $abs = $this->resourceFileAbsPathFromAnnouncementAttachment($srcAtt);
                    if (!$abs) {
                        $this->dlog('restore_announcements: source attachment file not readable', ['src_att_id' => $sid]);
                        continue;
                    }

                    $filename = $srcAtt->getFilename() ?: basename($abs);
                    foreach ($entity->getAttachments() as $existingA) {
                        if ($existingA->getFilename() === $filename) {
                            if ($this->file_option === FILE_SKIP) { continue 2; }
                            if ($this->file_option === FILE_RENAME) {
                                $pi = pathinfo($filename);
                                $base = $pi['filename'] ?? $filename;
                                $ext  = isset($pi['extension']) && $pi['extension'] !== '' ? ('.'.$pi['extension']) : '';
                                $i = 1; $candidate = $filename;
                                $existingNames = array_map(fn($x) => $x->getFilename(), iterator_to_array($entity->getAttachments()));
                                while (in_array($candidate, $existingNames, true)) { $candidate = $base.'_'.$i.$ext; $i++; }
                                $filename = $candidate;
                            }
                        }
                    }

                    $newAtt = (new CAnnouncementAttachment())
                        ->setFilename($filename)
                        ->setComment((string)$srcAtt->getComment())
                        ->setSize((int)$srcAtt->getSize())
                        ->setPath(uniqid('announce_', true))
                        ->setAnnouncement($entity)
                        ->setParent($entity)
                        ->addCourseLink(
                            api_get_course_entity($this->destination_course_id),
                            api_get_session_entity(0),
                            api_get_group_entity()
                        );

                    $em->persist($newAtt);
                    $em->flush();

                    if (method_exists($attachRepo, 'addFileFromLocalPath')) {
                        $attachRepo->addFileFromLocalPath($newAtt, $abs);
                    } else {
                        $tmp = tempnam(sys_get_temp_dir(), 'ann_');
                        @copy($abs, $tmp);
                        $_FILES['user_upload'] = [
                            'name'     => $filename,
                            'type'     => function_exists('mime_content_type') ? (mime_content_type($tmp) ?: 'application/octet-stream') : 'application/octet-stream',
                            'tmp_name' => $tmp,
                            'error'    => 0,
                            'size'     => filesize($tmp) ?: (int)$srcAtt->getSize(),
                        ];
                        $attachRepo->addFileFromFileRequest($newAtt, 'user_upload');
                        @unlink($tmp);
                    }

                    $this->dlog('restore_announcements: attachment copied from ResourceFile', [
                        'dst_announcement_id' => (int)$entity->getIid(),
                        'filename'            => $newAtt->getFilename(),
                        'size'                => $newAtt->getSize(),
                    ]);
                }
            }
            return;
        }

        $meta = null;
        if (!empty($a->attachment_path)) {
            $src = rtrim($originPath, '/').'/'.$a->attachment_path;
            if (is_file($src) && is_readable($src)) {
                $meta = [
                    'src'      => $src,
                    'filename' => (string)($a->attachment_filename ?? basename($src)),
                    'comment'  => (string)($a->attachment_comment ?? ''),
                    'size'     => (int)($a->attachment_size ?? (filesize($src) ?: 0)),
                ];
            }
        }
        if (!$meta && !empty($this->course->orig)) {
            $table = \Database::get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
            $sql = 'SELECT path, comment, size, filename
            FROM '.$table.'
            WHERE c_id = '.$this->destination_course_id.'
              AND announcement_id = '.(int)($a->source_id ?? 0);
            $res = \Database::query($sql);
            if ($row = \Database::fetch_object($res)) {
                $src = rtrim($originPath, '/').'/'.$row->path;
                if (is_file($src) && is_readable($src)) {
                    $meta = [
                        'src'      => $src,
                        'filename' => (string)$row->filename,
                        'comment'  => (string)$row->comment,
                        'size'     => (int)$row->size,
                    ];
                }
            }
        }
        if (!$meta) { return; }

        $attachment = (new CAnnouncementAttachment())
            ->setFilename($meta['filename'])
            ->setPath(uniqid('announce_', true))
            ->setComment($meta['comment'])
            ->setSize($meta['size'])
            ->setAnnouncement($entity)
            ->setParent($entity)
            ->addCourseLink(
                api_get_course_entity($this->destination_course_id),
                api_get_session_entity(0),
                api_get_group_entity()
            );

        $em->persist($attachment);
        $em->flush();

        $tmp = tempnam(sys_get_temp_dir(), 'ann_');
        @copy($meta['src'], $tmp);
        $_FILES['user_upload'] = [
            'name'     => $meta['filename'],
            'type'     => function_exists('mime_content_type') ? (mime_content_type($tmp) ?: 'application/octet-stream') : 'application/octet-stream',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize($tmp) ?: $meta['size'],
        ];
        $attachRepo->addFileFromFileRequest($attachment, 'user_upload');
        @unlink($tmp);

        $this->dlog('restore_announcements: attachment stored (ZIP)', [
            'announcement_id' => (int)$entity->getIid(),
            'filename'        => $attachment->getFilename(),
            'size'            => $attachment->getSize(),
        ]);
    }

    public function restore_quizzes($session_id = 0, $respect_base_content = false)
    {
        if (!$this->course->has_resources(RESOURCE_QUIZ)) {
            error_log('RESTORE_QUIZ: No quiz resources in backup.');
            return;
        }

        $em            = Database::getManager();
        $resources     = $this->course->resources;
        $courseEntity  = api_get_course_entity($this->destination_course_id);
        $sessionEntity = !empty($session_id) ? api_get_session_entity((int)$session_id) : api_get_session_entity();

        $rewrite = function (?string $html) use ($courseEntity) {
            if ($html === null || $html === false) return '';
            if (class_exists(ChamiloHelper::class)
                && method_exists(ChamiloHelper::class, 'rewriteLegacyCourseUrlsToAssets')) {
                try {
                    $backupRoot = $this->course->backup_path ?? '';
                    return ChamiloHelper::rewriteLegacyCourseUrlsToAssets($html, $courseEntity, $backupRoot);
                } catch (\Throwable $e) {
                    error_log('RESTORE_QUIZ: rewriteLegacyCourseUrlsToAssets failed: '.$e->getMessage());
                    return $html;
                }
            }
            return $html;
        };

        if (empty($this->course->resources[RESOURCE_QUIZQUESTION])
            && !empty($this->course->resources['Exercise_Question'])) {
            $this->course->resources[RESOURCE_QUIZQUESTION] = $this->course->resources['Exercise_Question'];
            $resources = $this->course->resources;
            error_log('RESTORE_QUIZ: Aliased Exercise_Question -> RESOURCE_QUIZQUESTION for restore.');
        }

        foreach ($resources[RESOURCE_QUIZ] as $id => $quizWrap) {
            $quiz = isset($quizWrap->obj) ? $quizWrap->obj : $quizWrap;

            $description      = $rewrite($quiz->description ?? '');
            $quiz->start_time = ($quiz->start_time === '0000-00-00 00:00:00') ? null : ($quiz->start_time ?? null);
            $quiz->end_time   = ($quiz->end_time   === '0000-00-00 00:00:00') ? null : ($quiz->end_time   ?? null);

            global $_custom;
            if (!empty($_custom['exercises_clean_dates_when_restoring'])) {
                $quiz->start_time = null;
                $quiz->end_time   = null;
            }

            if ((int)$id === -1) {
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
                ->setTextWhenFinished((string) ($quiz->text_when_finished ?? ''))
                ->setTextWhenFinishedFailure((string) ($quiz->text_when_finished_failure ?? ''))
                ->setDisplayCategoryName((int) ($quiz->display_category_name ?? 0))
                ->setSaveCorrectAnswers(isset($quiz->save_correct_answers) ? (int) $quiz->save_correct_answers : 0)
                ->setPropagateNeg((int) $quiz->propagate_neg)
                ->setHideQuestionTitle((bool) ($quiz->hide_question_title ?? false))
                ->setHideQuestionNumber((int) ($quiz->hide_question_number ?? 0))
                ->setStartTime(!empty($quiz->start_time) ? new \DateTime($quiz->start_time) : null)
                ->setEndTime(!empty($quiz->end_time) ? new \DateTime($quiz->end_time) : null);

            if (isset($quiz->access_condition) && $quiz->access_condition !== '') {
                $entity->setAccessCondition((string)$quiz->access_condition);
            }
            if (isset($quiz->pass_percentage) && $quiz->pass_percentage !== '' && $quiz->pass_percentage !== null) {
                $entity->setPassPercentage((int)$quiz->pass_percentage);
            }
            if (isset($quiz->question_selection_type) && $quiz->question_selection_type !== '' && $quiz->question_selection_type !== null) {
                $entity->setQuestionSelectionType((int)$quiz->question_selection_type);
            }
            if ('true' === api_get_setting('exercise.allow_notification_setting_per_exercise')) {
                $entity->setNotifications((string)($quiz->notifications ?? ''));
            }

            $em->persist($entity);
            $em->flush();

            $newQuizId = (int)$entity->getIid();
            $this->course->resources[RESOURCE_QUIZ][$id]->destination_id = $newQuizId;

            $qCount = isset($quiz->question_ids) ? count((array)$quiz->question_ids) : 0;
            error_log('RESTORE_QUIZ: Created quiz iid='.$newQuizId.' title="'.(string)$quiz->title.'" with '.$qCount.' question ids.');

            $order = 0;
            if (!empty($quiz->question_ids)) {
                foreach ($quiz->question_ids as $index => $question_id) {
                    $qid = $this->restore_quiz_question($question_id);
                    if (!$qid) {
                        error_log('RESTORE_QUIZ: restore_quiz_question returned 0 for src_question_id='.$question_id);
                        continue;
                    }

                    $question_order = !empty($quiz->question_orders[$index])
                        ? (int)$quiz->question_orders[$index]
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
                        ->setQuestionOrder($question_order);

                    $em->persist($rel);
                    $em->flush();
                }
            } else {
                error_log('RESTORE_QUIZ: No questions bound to quiz src_id='.$id.' (title="'.(string)$quiz->title.'").');
            }
        }
    }


    /**
     * Restore quiz-questions. Returns new question IID.
     */
    public function restore_quiz_question($id)
    {
        $em        = Database::getManager();
        $resources = $this->course->resources;

        if (empty($resources[RESOURCE_QUIZQUESTION]) && !empty($resources['Exercise_Question'])) {
            $resources[RESOURCE_QUIZQUESTION] = $this->course->resources[RESOURCE_QUIZQUESTION]
                = $this->course->resources['Exercise_Question'];
            error_log('RESTORE_QUESTION: Aliased Exercise_Question -> RESOURCE_QUIZQUESTION for restore.');
        }

        /** @var object|null $question */
        $question = $resources[RESOURCE_QUIZQUESTION][$id] ?? null;
        if (!is_object($question)) {
            error_log('RESTORE_QUESTION: Question not found in resources. src_id='.$id);
            return 0;
        }
        if (method_exists($question, 'is_restored') && $question->is_restored()) {
            return (int)$question->destination_id;
        }

        $courseEntity = api_get_course_entity($this->destination_course_id);

        $rewrite = function (?string $html) use ($courseEntity) {
            if ($html === null || $html === false) return '';
            if (class_exists(ChamiloHelper::class)
                && method_exists(ChamiloHelper::class, 'rewriteLegacyCourseUrlsToAssets')) {
                try {
                    return ChamiloHelper::rewriteLegacyCourseUrlsToAssets((string)$html, $courseEntity, null);
                } catch (\ArgumentCountError $e) {
                    return ChamiloHelper::rewriteLegacyCourseUrlsToAssets((string)$html, $courseEntity);
                } catch (\Throwable $e) {
                    error_log('RESTORE_QUESTION: rewriteLegacyCourseUrlsToAssets failed: '.$e->getMessage());
                    return $html;
                }
            }
            return $html;
        };

        $question->description = $rewrite($question->description ?? '');
        $question->question    = $rewrite($question->question ?? '');

        $imageNewId = '';
        if (!empty($question->picture)) {
            if (isset($resources[RESOURCE_DOCUMENT]['image_quiz'][$question->picture])) {
                $imageNewId = (string) $resources[RESOURCE_DOCUMENT]['image_quiz'][$question->picture]['destination_id'];
            } elseif (isset($resources[RESOURCE_DOCUMENT][$question->picture])) {
                $imageNewId = (string) $resources[RESOURCE_DOCUMENT][$question->picture]->destination_id;
            }
        }

        $qType  = (int) ($question->quiz_type ?? $question->type);
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
            ->setExtra((string) ($question->extra ?? ''));

        $em->persist($entity);
        $em->flush();

        $new_id = (int)$entity->getIid();
        if (!$new_id) {
            error_log('RESTORE_QUESTION: Failed to obtain new question iid for src_id='.$id);
            return 0;
        }

        $answers = (array)($question->answers ?? []);
        error_log('RESTORE_QUESTION: Creating question src_id='.$id.' dst_iid='.$new_id.' answers_count='.count($answers));

        $isMatchingFamily  = in_array($qType, [DRAGGABLE, MATCHING, MATCHING_DRAGGABLE], true);
        $correctMapSrcToDst = []; // dstAnsId => srcCorrectRef
        $allSrcAnswersById  = []; // srcAnsId => text
        $dstAnswersByIdText = []; // dstAnsId => text

        if ($isMatchingFamily) {
            foreach ($answers as $a) {
                $allSrcAnswersById[$a['id']] = $rewrite($a['answer'] ?? '');
            }
        }

        foreach ($answers as $a) {
            $ansText = $rewrite($a['answer'] ?? '');
            $comment = $rewrite($a['comment'] ?? '');

            $ans = (new CQuizAnswer())
                ->setQuestion($entity)
                ->setAnswer((string)$ansText)
                ->setComment((string)$comment)
                ->setPonderation((float)($a['ponderation'] ?? 0))
                ->setPosition((int)($a['position'] ?? 0))
                ->setHotspotCoordinates(isset($a['hotspot_coordinates']) ? (string)$a['hotspot_coordinates'] : null)
                ->setHotspotType(isset($a['hotspot_type']) ? (string)$a['hotspot_type'] : null);

            if (isset($a['correct']) && $a['correct'] !== '' && $a['correct'] !== null) {
                $ans->setCorrect((int)$a['correct']);
            }

            $em->persist($ans);
            $em->flush();

            if ($isMatchingFamily) {
                $correctMapSrcToDst[(int)$ans->getIid()] = $a['correct'] ?? null;
                $dstAnswersByIdText[(int)$ans->getIid()] = $ansText;
            }
        }

        if ($isMatchingFamily && $correctMapSrcToDst) {
            foreach ($entity->getAnswers() as $dstAns) {
                $dstAid = (int)$dstAns->getIid();
                $srcRef = $correctMapSrcToDst[$dstAid] ?? null;
                if ($srcRef === null) continue;

                if (isset($allSrcAnswersById[$srcRef])) {
                    $needle = $allSrcAnswersById[$srcRef];
                    $newDst = null;
                    foreach ($dstAnswersByIdText as $candId => $txt) {
                        if ($txt === $needle) { $newDst = $candId; break; }
                    }
                    if ($newDst !== null) {
                        $dstAns->setCorrect((int)$newDst);
                        $em->persist($dstAns);
                    }
                }
            }
            $em->flush();
        }

        if (defined('MULTIPLE_ANSWER_TRUE_FALSE') && MULTIPLE_ANSWER_TRUE_FALSE === $qType) {
            $newOptByOld = [];
            if (isset($question->question_options) && is_iterable($question->question_options)) {
                foreach ($question->question_options as $optWrap) {
                    $opt = $optWrap->obj ?? $optWrap;
                    $optEntity = (new CQuizQuestionOption())
                        ->setQuestion($entity)
                        ->setTitle((string)$opt->name)
                        ->setPosition((int)$opt->position);
                    $em->persist($optEntity);
                    $em->flush();
                    $newOptByOld[$opt->id] = (int)$optEntity->getIid();
                }
                foreach ($entity->getAnswers() as $dstAns) {
                    $corr = $dstAns->getCorrect();
                    if ($corr !== null && isset($newOptByOld[$corr])) {
                        $dstAns->setCorrect((int)$newOptByOld[$corr]);
                        $em->persist($dstAns);
                    }
                }
                $em->flush();
            }
        }

        $this->course->resources[RESOURCE_QUIZQUESTION][$id]->destination_id = $new_id;

        return $new_id;
    }

    public function restore_surveys($sessionId = 0)
    {
        if (!$this->course->has_resources(RESOURCE_SURVEY)) {
            $this->debug && error_log('COURSE_DEBUG: restore_surveys: no survey resources in backup.');
            return;
        }

        $em            = Database::getManager();
        $surveyRepo    = Container::getSurveyRepository();
        $courseEntity  = api_get_course_entity($this->destination_course_id);
        $sessionEntity = $sessionId ? api_get_session_entity((int)$sessionId) : null;

        $backupRoot = is_string($this->course->backup_path ?? null) ? rtrim($this->course->backup_path, '/') : '';
        if ($backupRoot === '') {
            $this->debug && error_log('COURSE_DEBUG: restore_surveys: backupRoot empty; URL rewriting may be partial.');
        }

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_SURVEY] as $legacySurveyId => $surveyObj) {
            try {
                $code = (string)($surveyObj->code ?? '');
                $lang = (string)($surveyObj->lang ?? '');

                $title        = ChamiloHelper::rewriteLegacyCourseUrlsToAssets((string)($surveyObj->title ?? ''),        $courseEntity, $backupRoot) ?? (string)($surveyObj->title ?? '');
                $subtitle     = ChamiloHelper::rewriteLegacyCourseUrlsToAssets((string)($surveyObj->subtitle ?? ''),     $courseEntity, $backupRoot) ?? (string)($surveyObj->subtitle ?? '');
                $intro        = ChamiloHelper::rewriteLegacyCourseUrlsToAssets((string)($surveyObj->intro ?? ''),        $courseEntity, $backupRoot) ?? (string)($surveyObj->intro ?? '');
                $surveyThanks = ChamiloHelper::rewriteLegacyCourseUrlsToAssets((string)($surveyObj->surveythanks ?? ''), $courseEntity, $backupRoot) ?? (string)($surveyObj->surveythanks ?? '');

                $onePerPage = !empty($surveyObj->one_question_per_page);
                $shuffle    = isset($surveyObj->shuffle) ? (bool)$surveyObj->shuffle : (!empty($surveyObj->suffle));
                $anonymous  = (string)((int)($surveyObj->anonymous ?? 0));

                try { $creationDate = !empty($surveyObj->creation_date) ? new \DateTime((string)$surveyObj->creation_date) : new \DateTime(); } catch (\Throwable) { $creationDate = new \DateTime(); }
                try { $availFrom    = !empty($surveyObj->avail_from)    ? new \DateTime((string)$surveyObj->avail_from)    : null; } catch (\Throwable) { $availFrom = null; }
                try { $availTill    = !empty($surveyObj->avail_till)    ? new \DateTime((string)$surveyObj->avail_till)    : null; } catch (\Throwable) { $availTill = null; }

                $visibleResults        = isset($surveyObj->visible_results) ? (int)$surveyObj->visible_results : null;
                $displayQuestionNumber = isset($surveyObj->display_question_number) ? (bool)$surveyObj->display_question_number : true;

                $existing = null;
                try {
                    if (method_exists($surveyRepo, 'findOneByCodeAndLangInCourse')) {
                        $existing = $surveyRepo->findOneByCodeAndLangInCourse($courseEntity, $code, $lang);
                    } else {
                        $existing = $surveyRepo->findOneBy(['code' => $code, 'lang' => $lang]);
                    }
                } catch (\Throwable $e) {
                    $this->debug && error_log('COURSE_DEBUG: restore_surveys: duplicate check skipped: '.$e->getMessage());
                }

                if ($existing instanceof CSurvey) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            $this->course->resources[RESOURCE_SURVEY][$legacySurveyId]->destination_id = (int)$existing->getIid();
                            $this->debug && error_log("COURSE_DEBUG: restore_surveys: survey exists code='$code' (skip).");
                            continue 2;

                        case FILE_RENAME:
                            $base = $code.'_';
                            $i    = 1;
                            $try  = $base.$i;
                            while (!$this->is_survey_code_available($try)) {
                                $try = $base.(++$i);
                            }
                            $code = $try;
                            $this->debug && error_log("COURSE_DEBUG: restore_surveys: renaming to '$code'.");
                            break;

                        case FILE_OVERWRITE:
                            \SurveyManager::deleteSurvey($existing);
                            $em->flush();
                            $this->debug && error_log("COURSE_DEBUG: restore_surveys: existing survey deleted (overwrite).");
                            break;

                        default:
                            $this->course->resources[RESOURCE_SURVEY][$legacySurveyId]->destination_id = (int)$existing->getIid();
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
                    ->setIsShared((string)($surveyObj->is_shared ?? '0'))
                    ->setTemplate((string)($surveyObj->template ?? 'template'))
                    ->setIntro($intro)
                    ->setSurveythanks($surveyThanks)
                    ->setCreationDate($creationDate)
                    ->setInvited(0)
                    ->setAnswered(0)
                    ->setInviteMail((string)($surveyObj->invite_mail ?? ''))
                    ->setReminderMail((string)($surveyObj->reminder_mail ?? ''))
                    ->setOneQuestionPerPage($onePerPage)
                    ->setShuffle($shuffle)
                    ->setAnonymous($anonymous)
                    ->setDisplayQuestionNumber($displayQuestionNumber);

                if (method_exists($newSurvey, 'setParent')) {
                    $newSurvey->setParent($courseEntity);
                }
                $newSurvey->addCourseLink($courseEntity, $sessionEntity);

                if (method_exists($surveyRepo, 'create')) {
                    $surveyRepo->create($newSurvey);
                } else {
                    $em->persist($newSurvey);
                    $em->flush();
                }

                $newId = (int)$newSurvey->getIid();
                $this->course->resources[RESOURCE_SURVEY][$legacySurveyId]->destination_id = $newId;

                // --- Restore questions ---
                $questionIds = is_array($surveyObj->question_ids ?? null) ? $surveyObj->question_ids : [];
                if (empty($questionIds) && !empty($resources[RESOURCE_SURVEYQUESTION])) {
                    foreach ($resources[RESOURCE_SURVEYQUESTION] as $qid => $qWrap) {
                        $q = (isset($qWrap->obj) && is_object($qWrap->obj)) ? $qWrap->obj : $qWrap;
                        if ((int)($q->survey_id ?? 0) === (int)$legacySurveyId) {
                            $questionIds[] = (int)$qid;
                        }
                    }
                }

                foreach ($questionIds as $legacyQid) {
                    $this->restore_survey_question((int)$legacyQid, $newId);
                }

                $this->debug && error_log("COURSE_DEBUG: restore_surveys: created survey iid={$newId}, questions=".count($questionIds));
            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: restore_surveys: failed: '.$e->getMessage());
            }
        }
    }


    /**
     * Restore survey-questions (legacy signature). $survey_id is the NEW iid.
     */
    public function restore_survey_question($id, $survey_id)
    {
        $resources = $this->course->resources;
        $qWrap     = $resources[RESOURCE_SURVEYQUESTION][$id] ?? null;

        if (!$qWrap || !is_object($qWrap)) {
            $this->debug && error_log("COURSE_DEBUG: restore_survey_question: legacy question $id not found.");
            return 0;
        }
        if (method_exists($qWrap, 'is_restored') && $qWrap->is_restored()) {
            return $qWrap->destination_id;
        }

        $surveyRepo   = Container::getSurveyRepository();
        $em           = Database::getManager();
        $courseEntity = api_get_course_entity($this->destination_course_id);

        $backupRoot = is_string($this->course->backup_path ?? null) ? rtrim($this->course->backup_path, '/') : '';

        $survey = $surveyRepo->find((int)$survey_id);
        if (!$survey instanceof CSurvey) {
            $this->debug && error_log("COURSE_DEBUG: restore_survey_question: target survey $survey_id not found.");
            return 0;
        }

        $q = (isset($qWrap->obj) && is_object($qWrap->obj)) ? $qWrap->obj : $qWrap;

        // Rewrite HTML
        $questionText = ChamiloHelper::rewriteLegacyCourseUrlsToAssets((string)($q->survey_question ?? ''), $courseEntity, $backupRoot) ?? (string)($q->survey_question ?? '');
        $commentText  = ChamiloHelper::rewriteLegacyCourseUrlsToAssets((string)($q->survey_question_comment ?? ''), $courseEntity, $backupRoot) ?? (string)($q->survey_question_comment ?? '');

        try {
            $question = new CSurveyQuestion();
            $question
                ->setSurvey($survey)
                ->setSurveyQuestion($questionText)
                ->setSurveyQuestionComment($commentText)
                ->setType((string)($q->survey_question_type ?? $q->type ?? 'open'))
                ->setDisplay((string)($q->display ?? 'vertical'))
                ->setSort((int)($q->sort ?? 0));

            if (isset($q->shared_question_id) && method_exists($question, 'setSharedQuestionId')) {
                $question->setSharedQuestionId((int)$q->shared_question_id);
            }
            if (isset($q->max_value) && method_exists($question, 'setMaxValue')) {
                $question->setMaxValue((int)$q->max_value);
            }
            if (isset($q->is_required)) {
                if (method_exists($question, 'setIsMandatory')) {
                    $question->setIsMandatory((bool)$q->is_required);
                } elseif (method_exists($question, 'setIsRequired')) {
                    $question->setIsRequired((bool)$q->is_required);
                }
            }

            $em->persist($question);
            $em->flush();

            // Options (value NOT NULL: default to 0 if missing)
            $answers = is_array($q->answers ?? null) ? $q->answers : [];
            foreach ($answers as $idx => $answer) {
                $optText = ChamiloHelper::rewriteLegacyCourseUrlsToAssets((string)($answer['option_text'] ?? ''), $courseEntity, $backupRoot) ?? (string)($answer['option_text'] ?? '');
                $value   = isset($answer['value']) && $answer['value'] !== null ? (int)$answer['value'] : 0;
                $sort    = (int)($answer['sort'] ?? ($idx + 1));

                $opt = new CSurveyQuestionOption();
                $opt
                    ->setSurvey($survey)
                    ->setQuestion($question)
                    ->setOptionText($optText)
                    ->setSort($sort)
                    ->setValue($value);

                $em->persist($opt);
            }
            $em->flush();

            $this->course->resources[RESOURCE_SURVEYQUESTION][$id]->destination_id = (int)$question->getIid();

            return (int)$question->getIid();
        } catch (\Throwable $e) {
            error_log('COURSE_DEBUG: restore_survey_question: failed: '.$e->getMessage());
            return 0;
        }
    }


    public function is_survey_code_available($survey_code)
    {
        $survey_code = (string)$survey_code;
        $surveyRepo  = Container::getSurveyRepository();

        try {
            $hit = $surveyRepo->findOneBy(['code' => $survey_code]);
            return $hit ? false : true;
        } catch (\Throwable $e) {
            $this->debug && error_log('COURSE_DEBUG: is_survey_code_available: fallback failed: '.$e->getMessage());
            return true;
        }
    }

    /**
     * @param int  $sessionId
     * @param bool $baseContent
     */
    public function restore_learnpath_category(int $sessionId = 0, bool $baseContent = false): void
    {
        $reuseExisting = false;
        if (isset($this->tool_copy_settings['learnpath_category']['reuse_existing']) &&
            true === $this->tool_copy_settings['learnpath_category']['reuse_existing']) {
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

            $title = trim($lpCategory->getTitle());
            if ($title === '') {
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
        } catch (\Throwable $e) {
            error_log("SCORM ZIPPER: Failed to create temp zip: ".$e->getMessage());
            return null;
        }

        if (!is_file($tmpZip) || filesize($tmpZip) === 0) {
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
        if (!empty($this->course->resources[RESOURCE_SCORM]) && is_array($this->course->resources[RESOURCE_SCORM])) {
            foreach ($this->course->resources[RESOURCE_SCORM] as $sc) {
                $src = isset($sc->source_lp_id) ? (int) $sc->source_lp_id : 0;
                $dst = isset($sc->lp_id_dest)   ? (int) $sc->lp_id_dest   : 0;
                $match = ($src && $src === $srcLpId);

                if (
                    !$match &&
                    $dst &&
                    !empty($this->course->resources[RESOURCE_LEARNPATH][$srcLpId]->destination_id)
                ) {
                    $match = ($dst === (int) $this->course->resources[RESOURCE_LEARNPATH][$srcLpId]->destination_id);
                }
                if (!$match) { continue; }

                $cands = [];
                if (!empty($sc->zip))  { $cands[] = $base.'/'.ltrim((string) $sc->zip, '/'); }
                if (!empty($sc->path)) { $cands[] = $base.'/'.ltrim((string) $sc->path, '/'); }

                foreach ($cands as $abs) {
                    if (is_file($abs) && is_readable($abs)) {
                        $out['zip']  = $abs;
                        $out['temp'] = false;
                        return $out;
                    }
                    if (is_dir($abs) && is_readable($abs)) {
                        $tmp = $this->zipScormFolder($abs);
                        if ($tmp) {
                            $out['zip']  = $tmp;
                            $out['temp'] = true;
                            return $out;
                        }
                    }
                }
            }
        }

        // 2) Heuristic: typical folders with *.zip
        foreach (['/scorm','/document/scorm','/documents/scorm'] as $dir) {
            $full = $base.$dir;
            if (!is_dir($full)) { continue; }
            $glob = glob($full.'/*.zip') ?: [];
            if (!empty($glob)) {
                $out['zip']  = $glob[0];
                $out['temp'] = false;
                return $out;
            }
        }

        // 3) Heuristic: look for imsmanifest.xml anywhere, then zip that folder
        $riiFlags = \FilesystemIterator::SKIP_DOTS;
        try {
            $rii = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, $riiFlags),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($rii as $f) {
                if ($f->isFile() && strtolower($f->getFilename()) === 'imsmanifest.xml') {
                    $folder = $f->getPath();
                    $tmp = $this->zipScormFolder($folder);
                    if ($tmp) {
                        $out['zip']  = $tmp;
                        $out['temp'] = true;
                        return $out;
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("SCORM FINDER: Recursive scan failed: ".$e->getMessage());
        }

        return $out;
    }

    /**
     * Restore SCORM ZIPs under Documents (Learning paths) for traceability.
     * Accepts real zips and on-the-fly temporary ones (temp will be deleted after upload).
     */
    public function restore_scorm_documents(): void
    {
        $logp = 'RESTORE_SCORM_ZIP: ';

        $getBucket = function(string $type) {
            if (!empty($this->course->resources[$type]) && is_array($this->course->resources[$type])) {
                return $this->course->resources[$type];
            }
            foreach ($this->course->resources ?? [] as $k => $v) {
                if (is_string($k) && strtolower($k) === strtolower($type) && is_array($v)) {
                    return $v;
                }
            }
            return [];
        };

        /** @var \Chamilo\CourseBundle\Repository\CDocumentRepository $docRepo */
        $docRepo = Container::getDocumentRepository();
        $em      = Database::getManager();

        $courseInfo = $this->destination_course_info;
        if (empty($courseInfo) || empty($courseInfo['real_id'])) { error_log($logp.'missing courseInfo/real_id'); return; }

        $courseEntity = api_get_course_entity((int) $courseInfo['real_id']);
        if (!$courseEntity) { error_log($logp.'api_get_course_entity failed'); return; }

        $sid = property_exists($this, 'current_session_id') ? (int) $this->current_session_id : 0;
        $session = api_get_session_entity($sid);

        $entries = [];

        // A) direct SCORM bucket
        $scormBucket = $getBucket(RESOURCE_SCORM);
        foreach ($scormBucket as $sc) { $entries[] = $sc; }

        // B) also try LPs that are SCORM
        $lpBucket = $getBucket(RESOURCE_LEARNPATH);
        foreach ($lpBucket as $srcLpId => $lpObj) {
            $lpType = (int)($lpObj->lp_type ?? $lpObj->type ?? 1);
            if ($lpType === CLp::SCORM_TYPE) {
                $entries[] = (object)[
                    'source_lp_id' => (int)$srcLpId,
                    'lp_id_dest'   => (int)($lpObj->destination_id ?? 0),
                ];
            }
        }

        error_log($logp.'entries='.count($entries));
        if (empty($entries)) { return; }

        $lpTop = $docRepo->ensureLearningPathSystemFolder($courseEntity, $session);

        foreach ($entries as $sc) {
            // Locate package (zip or folder → temp zip)
            $srcLpId = (int)($sc->source_lp_id ?? 0);
            $pkg = $this->findScormPackageForLp($srcLpId);
            if (empty($pkg['zip'])) {
                error_log($logp.'No package (zip/folder) found for a SCORM entry');
                continue;
            }
            $zipAbs  = $pkg['zip'];
            $zipTemp = (bool)$pkg['temp'];

            // Map LP title/dest for folder name
            $lpId = 0; $lpTitle = 'Untitled';
            if (!empty($sc->lp_id_dest)) {
                $lpId = (int) $sc->lp_id_dest;
            } elseif ($srcLpId && !empty($lpBucket[$srcLpId]->destination_id)) {
                $lpId = (int) $lpBucket[$srcLpId]->destination_id;
            }
            $lpEntity = $lpId ? Container::getLpRepository()->find($lpId) : null;
            if ($lpEntity) { $lpTitle = $lpEntity->getTitle() ?: $lpTitle; }

            $cleanTitle = preg_replace('/\s+/', ' ', trim(str_replace(['/', '\\'], '-', (string)$lpTitle))) ?: 'Untitled';
            $folderTitleBase = sprintf('SCORM - %d - %s', $lpId ?: 0, $cleanTitle);
            $folderTitle     = $folderTitleBase;

            $exists = $docRepo->findChildNodeByTitle($lpTop, $folderTitle);
            if ($exists) {
                if ($this->file_option === FILE_SKIP) {
                    error_log($logp."Skip due to folder name collision: '$folderTitle'");
                    if ($zipTemp) { @unlink($zipAbs); }
                    continue;
                }
                if ($this->file_option === FILE_RENAME) {
                    $i = 1;
                    do {
                        $folderTitle = $folderTitleBase.' ('.$i.')';
                        $exists = $docRepo->findChildNodeByTitle($lpTop, $folderTitle);
                        $i++;
                    } while ($exists);
                }
                if ($this->file_option === FILE_OVERWRITE && $lpEntity) {
                    $docRepo->purgeScormZip($courseEntity, $lpEntity);
                    $em->flush();
                }
            }

            // Upload ZIP under Documents
            $uploaded = new UploadedFile(
                $zipAbs, basename($zipAbs), 'application/zip', null, true
            );
            $lpFolder = $docRepo->ensureFolder(
                $courseEntity, $lpTop, $folderTitle,
                ResourceLink::VISIBILITY_DRAFT, $session
            );
            $docRepo->createFileInFolder(
                $courseEntity, $lpFolder, $uploaded,
                sprintf('SCORM ZIP for LP #%d', $lpId),
                ResourceLink::VISIBILITY_DRAFT, $session
            );
            $em->flush();

            if ($zipTemp) { @unlink($zipAbs); }
            error_log($logp."ZIP stored under folder '$folderTitle'");
        }
    }

    /**
     * Restore learnpaths (SCORM-aware).
     * For SCORM LPs, it accepts a real zip or zips a folder-on-the-fly if needed.
     * This version adds strict checks, robust logging and a guaranteed fallback LP.
     */
    public function restore_learnpaths($session_id = 0, $respect_base_content = false, $destination_course_code = '')
    {
        $logp = 'RESTORE_LP: ';

        // --- REQUIRED INITIALIZATION (avoid "Undefined variable $courseEntity") ---
        $courseInfo = $this->destination_course_info ?? [];
        $courseId   = (int)($courseInfo['real_id'] ?? 0);
        if ($courseId <= 0) {
            error_log($logp.'Missing destination course id; aborting.');
            return;
        }

        $courseEntity = api_get_course_entity($courseId);
        if (!$courseEntity) {
            error_log($logp.'api_get_course_entity() returned null for id='.$courseId.'; aborting.');
            return;
        }

        // Session entity is optional
        $session = $session_id ? api_get_session_entity((int)$session_id) : null;

        $em     = Database::getManager();
        $lpRepo = Container::getLpRepository();

        /**
         * Resolve a resource "bucket" by type (constant or string) and return [key, data].
         * - Normalizes common aliases (case-insensitive).
         * - Keeps original bucket key so we can write back destination_id on the right slot.
         */
        $getBucketWithKey = function (int|string $type) use ($logp) {
            // Map constants to canonical strings
            if (is_int($type)) {
                $type = match ($type) {
                    defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : -1 => 'learnpath',
                    defined('RESOURCE_SCORM')     ? RESOURCE_SCORM     : -2 => 'scorm',
                    default => (string)$type,
                };
            }

            // Common legacy aliases
            $aliases = [
                'learnpath' => ['learnpath','coursecopylearnpath','CourseCopyLearnpath','learning_path'],
                'scorm'     => ['scorm','scormdocument','ScormDocument'],
            ];

            $want = strtolower((string)$type);
            $wantedKeys = array_unique(array_merge([$type], $aliases[$want] ?? []));

            $res = is_array($this->course->resources ?? null) ? $this->course->resources : [];
            if (empty($res)) {
                error_log($logp."resources array is empty or invalid");
                return [null, []];
            }

            // 1) Exact match
            foreach ($wantedKeys as $k) {
                if (isset($res[$k]) && is_array($res[$k])) {
                    error_log($logp."bucket '". $type ."' found as '$k' (".count($res[$k]).")");
                    return [$k, $res[$k]];
                }
            }
            // 2) Case-insensitive match
            $lowerWanted = array_map('strtolower', $wantedKeys);
            foreach ($res as $k => $v) {
                if (is_string($k) && in_array(strtolower($k), $lowerWanted, true) && is_array($v)) {
                    error_log($logp."bucket '". $type ."' found as '$k' (".count($v).")");
                    return [$k, $v];
                }
            }

            error_log($logp."bucket '".(string)$type."' not found");
            return [null, []];
        };

        // Resolve learnpath bucket (returning its actual key to write back destination_id)
        [$lpBucketKey, $lpBucket] = $getBucketWithKey(defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath');
        if (empty($lpBucket)) {
            error_log($logp."No LPs to process");
            return;
        }

        // Optional: resolve scorm bucket (may be used by other helpers)
        [$_scormKey, $scormBucket] = $getBucketWithKey(defined('RESOURCE_SCORM') ? RESOURCE_SCORM : 'scorm');
        error_log($logp."LPs=".count($lpBucket).", SCORM entries=".count($scormBucket));

        foreach ($lpBucket as $srcLpId => $lpObj) {
            $lpName   = $lpObj->name ?? ($lpObj->title ?? ('LP '.$srcLpId));
            $lpType   = (int)($lpObj->lp_type ?? $lpObj->type ?? 1); // 2 = SCORM
            $encoding = $lpObj->default_encoding ?? 'UTF-8';

            error_log($logp."LP src=$srcLpId, name='". $lpName ."', type=".$lpType);

            // ---- SCORM ----
            if ($lpType === CLp::SCORM_TYPE) {
                $createdLpId = 0;
                $zipAbs  = null;
                $zipTemp = false;

                try {
                    // Find a real SCORM ZIP (or zip a folder on-the-fly)
                    $pkg    = $this->findScormPackageForLp((int)$srcLpId);
                    $zipAbs = $pkg['zip'] ?? null;
                    $zipTemp = !empty($pkg['temp']);

                    if (!$zipAbs || !is_readable($zipAbs)) {
                        error_log($logp."SCORM LP src=$srcLpId: NO ZIP found/readable");
                    } else {
                        error_log($logp."SCORM LP src=$srcLpId ZIP=".$zipAbs);

                        // Try to resolve currentDir from the BACKUP (folder or ZIP)
                        $currentDir    = '';
                        $tmpExtractDir = '';
                        $bp = (string) ($this->course->backup_path ?? '');

                        // Case A: backup_path is an extracted directory
                        if ($bp && is_dir($bp)) {
                            try {
                                $rii = new \RecursiveIteratorIterator(
                                    new \RecursiveDirectoryIterator($bp, \FilesystemIterator::SKIP_DOTS),
                                    \RecursiveIteratorIterator::SELF_FIRST
                                );
                                foreach ($rii as $f) {
                                    if ($f->isFile() && strtolower($f->getFilename()) === 'imsmanifest.xml') {
                                        $currentDir = $f->getPath();
                                        break;
                                    }
                                }
                            } catch (\Throwable $e) {
                                error_log($logp.'Scan BACKUP dir failed: '.$e->getMessage());
                            }
                        }

                        // Case B: backup_path is a ZIP under var/cache/course_backups
                        if (!$currentDir && $bp && is_file($bp) && preg_match('/\.zip$/i', $bp)) {
                            $tmpExtractDir = rtrim(sys_get_temp_dir(), '/').'/scorm_restore_'.uniqid('', true);
                            @mkdir($tmpExtractDir, 0777, true);
                            try {
                                $zf = new ZipFile();
                                $zf->openFile($bp);
                                $zf->extractTo($tmpExtractDir);
                                $zf->close();

                                $rii = new \RecursiveIteratorIterator(
                                    new \RecursiveDirectoryIterator($tmpExtractDir, \FilesystemIterator::SKIP_DOTS),
                                    \RecursiveIteratorIterator::SELF_FIRST
                                );
                                foreach ($rii as $f) {
                                    if ($f->isFile() && strtolower($f->getFilename()) === 'imsmanifest.xml') {
                                        $currentDir = $f->getPath();
                                        break;
                                    }
                                }
                            } catch (\Throwable $e) {
                                error_log($logp.'TMP unzip failed: '.$e->getMessage());
                            }
                        }

                        if ($currentDir) {
                            error_log($logp.'Resolved currentDir from BACKUP: '.$currentDir);
                        } else {
                            error_log($logp.'Could not resolve currentDir from backup; import_package will derive it');
                        }

                        // Import in scorm class (import_manifest will create LP + items)
                        $sc = new \scorm();
                        $fileInfo = ['tmp_name' => $zipAbs, 'name' => basename($zipAbs)];

                        $ok = $sc->import_package($fileInfo, $currentDir);

                        // Cleanup tmp if we extracted the backup ZIP
                        if ($tmpExtractDir && is_dir($tmpExtractDir)) {
                            $it = new \RecursiveIteratorIterator(
                                new \RecursiveDirectoryIterator($tmpExtractDir, \FilesystemIterator::SKIP_DOTS),
                                \RecursiveIteratorIterator::CHILD_FIRST
                            );
                            foreach ($it as $p) {
                                $p->isDir() ? @rmdir($p->getPathname()) : @unlink($p->getPathname());
                            }
                            @rmdir($tmpExtractDir);
                        }

                        if ($ok !== true) {
                            error_log($logp."import_package() returned false");
                        } else {
                            if (empty($sc->manifestToString)) {
                                error_log($logp."manifestToString empty after import_package()");
                            } else {
                                // Parse & import manifest (creates LP + items)
                                $sc->parse_manifest();

                                /** @var CLp|null $lp */
                                $lp = $sc->import_manifest($courseId, 1, (int) $session_id);
                                if ($lp instanceof CLp) {
                                    if (property_exists($lpObj, 'content_local')) {
                                        $lp->setContentLocal((int) $lpObj->content_local);
                                    }
                                    if (property_exists($lpObj, 'content_maker')) {
                                        $lp->setContentMaker((string) $lpObj->content_maker);
                                    }
                                    $lp->setDefaultEncoding((string) $encoding);

                                    $em->persist($lp);
                                    $em->flush();

                                    $createdLpId = (int)$lp->getIid();
                                    if ($lpBucketKey !== null && isset($this->course->resources[$lpBucketKey][$srcLpId])) {
                                        $this->course->resources[$lpBucketKey][$srcLpId]->destination_id = $createdLpId;
                                    }
                                    error_log($logp."SCORM LP created id=".$createdLpId." (via manifest)");
                                } else {
                                    error_log($logp."import_manifest() returned NULL");
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    error_log($logp.'EXCEPTION: '.$e->getMessage());
                } finally {
                    if (empty($createdLpId)) {
                        $lp = (new CLp())
                            ->setLpType(CLp::SCORM_TYPE)
                            ->setTitle((string) $lpName)
                            ->setDefaultEncoding((string) $encoding)
                            ->setJsLib('scorm_api.php')
                            ->setUseMaxScore(1)
                            ->setParent($courseEntity);

                        if (method_exists($lp, 'addCourseLink')) {
                            // pass session only if available
                            $lp->addCourseLink($courseEntity, $session ?: null);
                        }

                        $lpRepo->createLp($lp);
                        $em->flush();

                        $createdLpId = (int) $lp->getIid();
                        if ($lpBucketKey !== null && isset($this->course->resources[$lpBucketKey][$srcLpId])) {
                            $this->course->resources[$lpBucketKey][$srcLpId]->destination_id = $createdLpId;
                        }
                        error_log($logp."SCORM LP created id=".$createdLpId." (FALLBACK)");
                    }

                    // Remove temp ZIP if we created it in findScormPackageForLp()
                    if (!empty($zipTemp) && !empty($zipAbs) && is_file($zipAbs)) {
                        @unlink($zipAbs);
                    }
                }

                continue; // next LP
            }

            // ---- Non-SCORM ----
            $lp = (new CLp())
                ->setLpType(CLp::LP_TYPE)
                ->setTitle((string) $lpName)
                ->setDefaultEncoding((string) $encoding)
                ->setJsLib('scorm_api.php')
                ->setUseMaxScore(1)
                ->setParent($courseEntity);

            if (method_exists($lp, 'addCourseLink')) {
                $lp->addCourseLink($courseEntity, $session ?: null);
            }

            $lpRepo->createLp($lp);
            $em->flush();
            error_log($logp."Standard LP created id=".$lp->getIid());

            if ($lpBucketKey !== null && isset($this->course->resources[$lpBucketKey][$srcLpId])) {
                $this->course->resources[$lpBucketKey][$srcLpId]->destination_id = (int) $lp->getIid();
            }

            // Manual items (only for non-SCORM if present in backup)
            if (!empty($lpObj->items) && is_array($lpObj->items)) {
                $lpItemRepo = Container::getLpItemRepository();
                $rootItem   = $lpItemRepo->getRootItem($lp->getIid());
                $parents    = [0 => $rootItem];

                foreach ($lpObj->items as $it) {
                    $level = (int) ($it['level'] ?? 0);
                    if (!isset($parents[$level])) { $parents[$level] = end($parents); }
                    $parentEntity = $parents[$level] ?? $rootItem;

                    $lpItem = (new CLpItem())
                        ->setTitle((string) ($it['title'] ?? ''))
                        ->setItemType((string) ($it['item_type'] ?? 'dir'))
                        ->setRef((string) ($it['identifier'] ?? ''))
                        ->setPath((string) ($it['path'] ?? ''))
                        ->setMinScore(0)
                        ->setMaxScore((int) ($it['max_score'] ?? 100))
                        ->setPrerequisite((string) ($it['prerequisites'] ?? ''))
                        ->setLaunchData((string) ($it['datafromlms'] ?? ''))
                        ->setParameters((string) ($it['parameters'] ?? ''))
                        ->setLp($lp)
                        ->setParent($parentEntity);

                    $lpItemRepo->create($lpItem);
                    $parents[$level+1] = $lpItem;
                }
                $em->flush();
                error_log($logp."Standard LP id=".$lp->getIid()." items=".count($lpObj->items));
            }
        }
    }

    /**
     * Restore glossary.
     */
    public function restore_glossary($sessionId = 0)
    {
        if (!$this->course->has_resources(RESOURCE_GLOSSARY)) {
            $this->debug && error_log('COURSE_DEBUG: restore_glossary: no glossary resources in backup.');
            return;
        }

        $em            = Database::getManager();
        /** @var CGlossaryRepository $repo */
        $repo          = $em->getRepository(CGlossary::class);
        /** @var CourseEntity $courseEntity */
        $courseEntity  = api_get_course_entity($this->destination_course_id);
        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int) $sessionId) : null;

        $backupRoot = is_string($this->course->backup_path ?? null) ? rtrim($this->course->backup_path, '/') : '';
        if ($backupRoot === '') {
            $this->debug && error_log('COURSE_DEBUG: restore_glossary: backupRoot empty; URL rewriting may be partial.');
        }

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_GLOSSARY] as $legacyId => $gls) {
            try {
                $title = (string) ($gls->name ?? $gls->title ?? '');
                $desc  = (string) ($gls->description ?? '');
                $order = (int)  ($gls->display_order ?? 0);

                $desc = ChamiloHelper::rewriteLegacyCourseUrlsToAssets($desc, $courseEntity, $backupRoot) ?? $desc;

                $existing = null;
                if (method_exists($repo, 'getResourcesByCourse')) {
                    $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity)
                        ->andWhere('resource.title = :title')
                        ->setParameter('title', $title)
                        ->setMaxResults(1);
                    $existing = $qb->getQuery()->getOneOrNullResult();
                } else {
                    $existing = $repo->findOneBy(['title' => $title]);
                }

                if ($existing instanceof CGlossary) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            $this->course->resources[RESOURCE_GLOSSARY][$legacyId]->destination_id = (int)$existing->getIid();
                            $this->debug && error_log("COURSE_DEBUG: restore_glossary: term exists title='{$title}' (skip).");
                            continue 2;

                        case FILE_RENAME:
                            $base = $title === '' ? 'Glossary term' : $title;
                            $try  = $base;
                            $i    = 1;
                            $isTaken = static function($repo, $courseEntity, $sessionEntity, $titleTry) {
                                if (method_exists($repo, 'getResourcesByCourse')) {
                                    $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity)
                                        ->andWhere('resource.title = :t')->setParameter('t', $titleTry)
                                        ->setMaxResults(1);
                                    return (bool)$qb->getQuery()->getOneOrNullResult();
                                }
                                return (bool)$repo->findOneBy(['title' => $titleTry]);
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
                            $this->debug && error_log("COURSE_DEBUG: restore_glossary: existing term deleted (overwrite).");
                            break;

                        default:
                            $this->course->resources[RESOURCE_GLOSSARY][$legacyId]->destination_id = (int)$existing->getIid();
                            continue 2;
                    }
                }

                $entity = new CGlossary();
                $entity
                    ->setTitle($title)
                    ->setDescription($desc);

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

                $newId = (int)$entity->getIid();
                if (!isset($this->course->resources[RESOURCE_GLOSSARY][$legacyId])) {
                    $this->course->resources[RESOURCE_GLOSSARY][$legacyId] = new \stdClass();
                }
                $this->course->resources[RESOURCE_GLOSSARY][$legacyId]->destination_id = $newId;

                $this->debug && error_log("COURSE_DEBUG: restore_glossary: created term iid={$newId}, title='{$title}'");
            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: restore_glossary: failed: '.$e->getMessage());
                continue;
            }
        }
    }

    /**
     * @param int $sessionId
     */
    public function restore_wiki($sessionId = 0)
    {
        if (!$this->course->has_resources(RESOURCE_WIKI)) {
            $this->debug && error_log('COURSE_DEBUG: restore_wiki: no wiki resources in backup.');
            return;
        }

        $em            = Database::getManager();
        /** @var CWikiRepository $repo */
        $repo          = $em->getRepository(CWiki::class);
        /** @var CourseEntity $courseEntity */
        $courseEntity  = api_get_course_entity($this->destination_course_id);
        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int)$sessionId) : null;

        $cid = (int)$this->destination_course_id;
        $sid = (int)($sessionEntity?->getId() ?? 0);

        $backupRoot = is_string($this->course->backup_path ?? null) ? rtrim($this->course->backup_path, '/') : '';
        if ($backupRoot === '') {
            $this->debug && error_log('COURSE_DEBUG: restore_wiki: backupRoot empty; URL rewriting may be partial.');
        }

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_WIKI] as $legacyId => $w) {
            try {
                $rawTitle  = (string)($w->title ?? $w->name ?? '');
                $reflink   = (string)($w->reflink ?? '');
                $content   = (string)($w->content ?? '');
                $comment   = (string)($w->comment ?? '');
                $progress  = (string)($w->progress ?? '');
                $version   = (int)  ($w->version ?? 1);
                $groupId   = (int)  ($w->group_id ?? 0);
                $userId    = (int)  ($w->user_id  ?? api_get_user_id());
                $dtimeStr  = (string)($w->dtime ?? '');
                $dtime     = null;
                try { $dtime = $dtimeStr !== '' ? new \DateTime($dtimeStr) : new \DateTime('now', new \DateTimeZone('UTC')); }
                catch (\Throwable) { $dtime = new \DateTime('now', new \DateTimeZone('UTC')); }

                $content = ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                    $content,
                    $courseEntity,
                    $backupRoot
                ) ?? $content;

                if ($rawTitle === '') {
                    $rawTitle = 'Wiki page';
                }
                if ($content === '') {
                    $content = '<p>&nbsp;</p>';
                }

                $makeSlug = static function (string $s): string {
                    $s = strtolower(trim($s));
                    $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s) ?: '';
                    $s = trim($s, '-');
                    return $s === '' ? 'page' : $s;
                };
                $reflink = $reflink !== '' ? $makeSlug($reflink) : $makeSlug($rawTitle);

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
                $exists = (bool)$qbExists->getQuery()->getOneOrNullResult();

                if ($exists) {
                    switch ($this->file_option) {
                        case FILE_SKIP:
                            $qbLast = $repo->createQueryBuilder('w')
                                ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                                ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId)
                                ->orderBy('w.version', 'DESC')->setMaxResults(1);
                            if ($sid > 0) { $qbLast->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid); }
                            else          { $qbLast->andWhere('COALESCE(w.sessionId,0) = 0'); }

                            /** @var CWiki|null $last */
                            $last = $qbLast->getQuery()->getOneOrNullResult();
                            $dest = $last ? (int)($last->getPageId() ?: $last->getIid()) : 0;
                            $this->course->resources[RESOURCE_WIKI][$legacyId]->destination_id = $dest;
                            $this->debug && error_log("COURSE_DEBUG: restore_wiki: reflink '{$reflink}' exists → skip (page_id={$dest}).");
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
                                    ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId);
                                if ($sid > 0) $qb->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                                else          $qb->andWhere('COALESCE(w.sessionId,0) = 0');
                                $qb->setMaxResults(1);
                                return (bool)$qb->getQuery()->getOneOrNullResult();
                            };
                            while ($isTaken($trySlug)) { $trySlug = $baseSlug.'-'.(++$i); }
                            $reflink  = $trySlug;
                            $rawTitle = $baseTitle.' ('.$i.')';
                            $this->debug && error_log("COURSE_DEBUG: restore_wiki: renamed reflink to '{$reflink}' / title='{$rawTitle}'.");
                            break;

                        case FILE_OVERWRITE:
                            $qbAll = $repo->createQueryBuilder('w')
                                ->andWhere('w.cId = :cid')->setParameter('cid', $cid)
                                ->andWhere('w.reflink = :r')->setParameter('r', $reflink)
                                ->andWhere('COALESCE(w.groupId,0) = :gid')->setParameter('gid', $groupId);
                            if ($sid > 0) $qbAll->andWhere('COALESCE(w.sessionId,0) = :sid')->setParameter('sid', $sid);
                            else          $qbAll->andWhere('COALESCE(w.sessionId,0) = 0');

                            foreach ($qbAll->getQuery()->getResult() as $old) {
                                $em->remove($old);
                            }
                            $em->flush();
                            $this->debug && error_log("COURSE_DEBUG: restore_wiki: removed previous pages for reflink '{$reflink}' (overwrite).");
                            break;

                        default:
                            $this->debug && error_log("COURSE_DEBUG: restore_wiki: unknown file_option → skip.");
                            continue 2;
                    }
                }

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
                $wiki->setDtime($dtime);
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

                if (empty($w->page_id)) {
                    $wiki->setPageId((int) $wiki->getIid());
                    $em->flush();
                } else {
                    $pid = (int) $w->page_id;
                    $wiki->setPageId($pid > 0 ? $pid : (int) $wiki->getIid());
                    $em->flush();
                }

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
                    $conf->setStartdateAssig(!empty($w->startdate_assig) ? new \DateTime((string) $w->startdate_assig) : null);
                } catch (\Throwable) { $conf->setStartdateAssig(null); }
                try {
                    $conf->setEnddateAssig(!empty($w->enddate_assig) ? new \DateTime((string) $w->enddate_assig) : null);
                } catch (\Throwable) { $conf->setEnddateAssig(null); }
                $conf->setDelayedsubmit(isset($w->delayedsubmit) ? (int) $w->delayedsubmit : 0);

                $em->persist($conf);
                $em->flush();

                $this->course->resources[RESOURCE_WIKI][$legacyId]->destination_id = (int) $wiki->getPageId();

                $this->debug && error_log("COURSE_DEBUG: restore_wiki: created page iid=".(int) $wiki->getIid()." page_id=".(int) $wiki->getPageId()." reflink='{$reflink}'");
            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: restore_wiki: failed: '.$e->getMessage());
                continue;
            }
        }
    }

    /**
     * Restore Thematics.
     *
     * @param int $sessionId
     */
    public function restore_thematic($sessionId = 0)
    {
        if (!$this->course->has_resources(RESOURCE_THEMATIC)) {
            $this->debug && error_log('COURSE_DEBUG: restore_thematic: no thematic resources.');
            return;
        }

        $em            = Database::getManager();
        /** @var CourseEntity $courseEntity */
        $courseEntity  = api_get_course_entity($this->destination_course_id);
        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int)$sessionId) : null;

        $cid = (int)$this->destination_course_id;
        $sid = (int)($sessionEntity?->getId() ?? 0);

        $backupRoot = is_string($this->course->backup_path ?? null) ? rtrim($this->course->backup_path, '/') : '';

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_THEMATIC] as $legacyId => $t) {
            try {
                $p = (array)($t->params ?? []);
                $title   = trim((string)($p['title']   ?? $p['name'] ?? ''));
                $content = (string)($p['content'] ?? '');
                $active  = (bool)  ($p['active']  ?? true);

                if ($content !== '') {
                    $content = ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                        $content,
                        $courseEntity,
                        $backupRoot
                    ) ?? $content;
                }

                if ($title === '') {
                    $title = 'Thematic';
                }

                $thematic = new CThematic();
                $thematic
                    ->setTitle($title)
                    ->setContent($content)
                    ->setActive($active);

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

                $this->course->resources[RESOURCE_THEMATIC][$legacyId]->destination_id = (int)$thematic->getIid();

                $advList = (array)($t->thematic_advance_list ?? []);
                foreach ($advList as $adv) {
                    if (!is_array($adv)) { $adv = (array)$adv; }

                    $advContent = (string)($adv['content'] ?? '');
                    if ($advContent !== '') {
                        $advContent = ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                            $advContent,
                            $courseEntity,
                            $backupRoot
                        ) ?? $advContent;
                    }

                    $startStr = (string)($adv['start_date'] ?? $adv['startDate'] ?? '');
                    try {
                        $startDate = $startStr !== '' ? new \DateTime($startStr) : new \DateTime('now', new \DateTimeZone('UTC'));
                    } catch (\Throwable) {
                        $startDate = new \DateTime('now', new \DateTimeZone('UTC'));
                    }

                    $duration    = (int)($adv['duration'] ?? 1);
                    $doneAdvance = (bool)($adv['done_advance'] ?? $adv['doneAdvance'] ?? false);

                    $advance = new CThematicAdvance();
                    $advance
                        ->setThematic($thematic)
                        ->setContent($advContent)
                        ->setStartDate($startDate)
                        ->setDuration($duration)
                        ->setDoneAdvance($doneAdvance);

                    $attId = (int)($adv['attendance_id'] ?? 0);
                    if ($attId > 0) {
                        $att = $em->getRepository(CAttendance::class)->find($attId);
                        if ($att) {
                            $advance->setAttendance($att);
                        }
                    }

                    $roomId = (int)($adv['room_id'] ?? 0);
                    if ($roomId > 0) {
                        $room = $em->getRepository(Room::class)->find($roomId);
                        if ($room) {
                            $advance->setRoom($room);
                        }
                    }

                    $em->persist($advance);
                }

                $planList = (array)($t->thematic_plan_list ?? []);
                foreach ($planList as $pl) {
                    if (!is_array($pl)) { $pl = (array)$pl; }

                    $plTitle = trim((string)($pl['title'] ?? ''));
                    if ($plTitle === '') { $plTitle = 'Plan'; }

                    $plDesc  = (string)($pl['description'] ?? '');
                    if ($plDesc !== '') {
                        $plDesc = ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                            $plDesc,
                            $courseEntity,
                            $backupRoot
                        ) ?? $plDesc;
                    }

                    $descType = (int)($pl['description_type'] ?? $pl['descriptionType'] ?? 0);

                    $plan = new CThematicPlan();
                    $plan
                        ->setThematic($thematic)
                        ->setTitle($plTitle)
                        ->setDescription($plDesc)
                        ->setDescriptionType($descType);

                    $em->persist($plan);
                }

                $em->flush();

                $this->debug && error_log("COURSE_DEBUG: restore_thematic: created thematic iid=".(int)$thematic->getIid()." (advances=".count($advList).", plans=".count($planList).")");
            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: restore_thematic: failed: '.$e->getMessage());
                continue;
            }
        }
    }

    /**
     * Restore Attendance.
     *
     * @param int $sessionId
     */
    public function restore_attendance($sessionId = 0)
    {
        if (!$this->course->has_resources(RESOURCE_ATTENDANCE)) {
            $this->debug && error_log('COURSE_DEBUG: restore_attendance: no attendance resources.');
            return;
        }

        $em            = Database::getManager();
        /** @var CourseEntity $courseEntity */
        $courseEntity  = api_get_course_entity($this->destination_course_id);
        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity((int)$sessionId) : null;

        $backupRoot = is_string($this->course->backup_path ?? null) ? rtrim($this->course->backup_path, '/') : '';

        $resources = $this->course->resources;

        foreach ($resources[RESOURCE_ATTENDANCE] as $legacyId => $att) {
            try {
                $p = (array)($att->params ?? []);

                $title  = trim((string)($p['title'] ?? 'Attendance'));
                $desc   = (string)($p['description'] ?? '');
                $active = (int)($p['active'] ?? 1);

                if ($desc !== '') {
                    $desc = ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                        $desc,
                        $courseEntity,
                        $backupRoot
                    ) ?? $desc;
                }

                $qualTitle  = isset($p['attendance_qualify_title']) ? (string)$p['attendance_qualify_title'] : null;
                $qualMax    = (int)($p['attendance_qualify_max'] ?? 0);
                $weight     = (float)($p['attendance_weight'] ?? 0.0);
                $locked     = (int)($p['locked'] ?? 0);

                $a = new CAttendance();
                $a->setTitle($title)
                    ->setDescription($desc)
                    ->setActive($active)
                    ->setAttendanceQualifyTitle($qualTitle ?? '')
                    ->setAttendanceQualifyMax($qualMax)
                    ->setAttendanceWeight($weight)
                    ->setLocked($locked);

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

                $this->course->resources[RESOURCE_ATTENDANCE][$legacyId]->destination_id = (int)$a->getIid();

                $calList = (array)($att->attendance_calendar ?? []);
                foreach ($calList as $c) {
                    if (!is_array($c)) { $c = (array)$c; }

                    $rawDt = (string)($c['date_time'] ?? $c['dateTime'] ?? $c['start_date'] ?? '');
                    try {
                        $dt = $rawDt !== '' ? new \DateTime($rawDt) : new \DateTime('now', new \DateTimeZone('UTC'));
                    } catch (\Throwable) {
                        $dt = new \DateTime('now', new \DateTimeZone('UTC'));
                    }

                    $done     = (bool)($c['done_attendance'] ?? $c['doneAttendance'] ?? false);
                    $blocked  = (bool)($c['blocked'] ?? false);
                    $duration = isset($c['duration']) ? (int)$c['duration'] : null;

                    $cal = new CAttendanceCalendar();
                    $cal->setAttendance($a)
                        ->setDateTime($dt)
                        ->setDoneAttendance($done)
                        ->setBlocked($blocked)
                        ->setDuration($duration);

                    $em->persist($cal);
                    $em->flush();

                    $groupId = (int)($c['group_id'] ?? 0);
                    if ($groupId > 0) {
                        try {
                            $repo = $em->getRepository(CAttendanceCalendarRelGroup::class);
                            if (method_exists($repo, 'addGroupToCalendar')) {
                                $repo->addGroupToCalendar((int)$cal->getIid(), $groupId);
                            }
                        } catch (\Throwable $e) {
                            $this->debug && error_log('COURSE_DEBUG: restore_attendance: calendar group link skipped: '.$e->getMessage());
                        }
                    }
                }

                $em->flush();
                $this->debug && error_log('COURSE_DEBUG: restore_attendance: created attendance iid='.(int)$a->getIid().' (cal='.count($calList).')');

            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: restore_attendance: failed: '.$e->getMessage());
                continue;
            }
        }
    }

    /**
     * Restore Works.
     *
     * @param int $sessionId
     */
    public function restore_works(int $sessionId = 0): void
    {
        if (!$this->course->has_resources(RESOURCE_WORK)) {
            return;
        }

        $em            = Database::getManager();
        /** @var CourseEntity $courseEntity */
        $courseEntity  = api_get_course_entity($this->destination_course_id);
        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity($sessionId) : null;

        $backupRoot = is_string($this->course->backup_path ?? null) ? rtrim($this->course->backup_path, '/') : '';

        /** @var CStudentPublicationRepository $pubRepo */
        $pubRepo = Container::getStudentPublicationRepository();

        foreach ($this->course->resources[RESOURCE_WORK] as $legacyId => $obj) {
            try {
                $p = (array)($obj->params ?? []);

                $title = trim((string)($p['title'] ?? 'Work'));
                if ($title === '') { $title = 'Work'; }

                $description = (string)($p['description'] ?? '');
                if ($description !== '') {
                    $description = ChamiloHelper::rewriteLegacyCourseUrlsToAssets(
                        $description,
                        $courseEntity,
                        $backupRoot
                    ) ?? $description;
                }

                $enableQualification = (bool)($p['enable_qualification'] ?? false);
                $addToCalendar       = (int)($p['add_to_calendar'] ?? 0) === 1;
                $expiresOn           = !empty($p['expires_on']) ? new \DateTime($p['expires_on']) : null;
                $endsOn              = !empty($p['ends_on'])    ? new \DateTime($p['ends_on'])    : null;

                $weight              = isset($p['weight']) ? (float)$p['weight'] : 0.0;
                $qualification       = isset($p['qualification']) ? (float)$p['qualification'] : 0.0;
                $allowText           = (int)($p['allow_text_assignment'] ?? 0);
                $defaultVisibility   = (bool)($p['default_visibility'] ?? 0);
                $studentMayDelete    = (bool)($p['student_delete_own_publication'] ?? 0);
                $extensions          = isset($p['extensions']) ? (string)$p['extensions'] : null;
                $groupCategoryWorkId = (int)($p['group_category_work_id'] ?? 0);
                $postGroupId         = (int)($p['post_group_id'] ?? 0);

                $existingQb = $pubRepo->findAllByCourse(
                    $courseEntity,
                    $sessionEntity,
                    $title,
                    null,
                    'folder'
                );
                $existing = $existingQb
                    ->andWhere('resource.publicationParent IS NULL')
                    ->andWhere('resource.active IN (0,1)')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if (!$existing) {
                    $pub = new CStudentPublication();
                    $pub->setTitle($title)
                        ->setDescription($description)
                        ->setFiletype('folder')
                        ->setContainsFile(0)
                        ->setWeight($weight)
                        ->setQualification($qualification)
                        ->setAllowTextAssignment($allowText)
                        ->setDefaultVisibility($defaultVisibility)
                        ->setStudentDeleteOwnPublication($studentMayDelete)
                        ->setExtensions($extensions)
                        ->setGroupCategoryWorkId($groupCategoryWorkId)
                        ->setPostGroupId($postGroupId);

                    if (method_exists($pub, 'setParent')) {
                        $pub->setParent($courseEntity);
                    }
                    if (method_exists($pub, 'setCreator')) {
                        $pub->setCreator(api_get_user_entity());
                    }
                    if (method_exists($pub, 'addCourseLink')) {
                        $pub->addCourseLink($courseEntity, $sessionEntity);
                    }

                    $em->persist($pub);
                    $em->flush();

                    // Assignment
                    $assignment = new CStudentPublicationAssignment();
                    $assignment->setPublication($pub)
                        ->setEnableQualification($enableQualification || $qualification > 0);

                    if ($expiresOn) { $assignment->setExpiresOn($expiresOn); }
                    if ($endsOn)    { $assignment->setEndsOn($endsOn); }

                    $em->persist($assignment);
                    $em->flush();

                    // Calendar (URL “Chamilo 2”: Router/UUID)
                    if ($addToCalendar) {
                        $eventTitle = sprintf(get_lang('Handing over of task %s'), $pub->getTitle());

                        // URL por UUID o Router
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
                                } catch (\Throwable) {
                                    $publicationUrl = '/r/student_publication/'. $uuid;
                                }
                            } else {
                                $publicationUrl = '/r/student_publication/'. $uuid;
                            }
                        }

                        $content = sprintf(
                            '<div>%s</div> %s',
                            $publicationUrl
                                ? sprintf('<a href="%s">%s</a>', $publicationUrl, $pub->getTitle())
                                : htmlspecialchars($pub->getTitle(), ENT_QUOTES),
                            $pub->getDescription()
                        );

                        $start = $expiresOn ? clone $expiresOn : new \DateTime('now', new \DateTimeZone('UTC'));
                        $end   = $expiresOn ? clone $expiresOn : new \DateTime('now', new \DateTimeZone('UTC'));

                        $color = CCalendarEvent::COLOR_STUDENT_PUBLICATION;
                        if ($colors = api_get_setting('agenda.agenda_colors')) {
                            if (!empty($colors['student_publication'])) {
                                $color = $colors['student_publication'];
                            }
                        }

                        $event = (new CCalendarEvent())
                            ->setTitle($eventTitle)
                            ->setContent($content)
                            ->setParent($courseEntity)
                            ->setCreator($pub->getCreator())
                            ->addLink(clone $pub->getFirstResourceLink())
                            ->setStartDate($start)
                            ->setEndDate($end)
                            ->setColor($color);

                        $em->persist($event);
                        $em->flush();

                        $assignment->setEventCalendarId((int)$event->getIid());
                        $em->flush();
                    }

                    $this->course->resources[RESOURCE_WORK][$legacyId]->destination_id = (int)$pub->getIid();
                } else {
                    $existing
                        ->setDescription($description)
                        ->setWeight($weight)
                        ->setQualification($qualification)
                        ->setAllowTextAssignment($allowText)
                        ->setDefaultVisibility($defaultVisibility)
                        ->setStudentDeleteOwnPublication($studentMayDelete)
                        ->setExtensions($extensions)
                        ->setGroupCategoryWorkId($groupCategoryWorkId)
                        ->setPostGroupId($postGroupId);

                    $em->persist($existing);
                    $em->flush();

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

                    $this->course->resources[RESOURCE_WORK][$legacyId]->destination_id = (int)$existing->getIid();
                }
            } catch (\Throwable $e) {
                error_log('COURSE_DEBUG: restore_works: '.$e->getMessage());
                continue;
            }
        }
    }


    public function restore_gradebook(int $sessionId = 0): void
    {
        if (\in_array($this->file_option, [FILE_SKIP, FILE_RENAME], true)) {
            return;
        }

        if (!$this->course->has_resources(RESOURCE_GRADEBOOK)) {
            $this->dlog('restore_gradebook: no gradebook resources');
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = \Database::getManager();

        /** @var Course $courseEntity */
        $courseEntity  = api_get_course_entity($this->destination_course_id);
        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $sessionId ? api_get_session_entity($sessionId) : null;
        /** @var User $currentUser */
        $currentUser   = api_get_user_entity();

        $catRepo  = $em->getRepository(GradebookCategory::class);

        // 1) Clean destination (overwrite semantics)
        try {
            $existingCats = $catRepo->findBy([
                'course'  => $courseEntity,
                'session' => $sessionEntity,
            ]);
            foreach ($existingCats as $cat) {
                $em->remove($cat); // cascades remove evaluations/links
            }
            $em->flush();
            $this->dlog('restore_gradebook: destination cleaned', ['removed' => count($existingCats)]);
        } catch (\Throwable $e) {
            $this->dlog('restore_gradebook: clean failed (continuing)', ['error' => $e->getMessage()]);
        }

        $oldIdToNewCat = [];

        // 2) First pass: create all categories (no parent yet)
        foreach ($this->course->resources[RESOURCE_GRADEBOOK] as $gbItem) {
            $categories = (array) ($gbItem->categories ?? []);
            foreach ($categories as $rawCat) {
                $c = is_array($rawCat) ? $rawCat : (array) $rawCat;

                $oldId   = (int)   ($c['id'] ?? $c['iid'] ?? 0);
                $title   = (string)($c['title'] ?? 'Category');
                $desc    = (string)($c['description'] ?? '');
                $weight  = (float)  ($c['weight'] ?? 0.0);
                $visible = (bool)   ($c['visible'] ?? true);
                $locked  = (int)    ($c['locked'] ?? 0);

                $new = new GradebookCategory();
                $new->setCourse($courseEntity);
                $new->setSession($sessionEntity);
                $new->setUser($currentUser);
                $new->setTitle($title);
                $new->setDescription($desc);
                $new->setWeight($weight);
                $new->setVisible($visible);
                $new->setLocked($locked);

                // Optional fields if present in backup
                if (isset($c['generate_certificates'])) {
                    $new->setGenerateCertificates((bool)$c['generate_certificates']);
                } elseif (isset($c['generateCertificates'])) {
                    $new->setGenerateCertificates((bool)$c['generateCertificates']);
                }
                if (isset($c['certificate_validity_period'])) {
                    $new->setCertificateValidityPeriod((int)$c['certificate_validity_period']);
                } elseif (isset($c['certificateValidityPeriod'])) {
                    $new->setCertificateValidityPeriod((int)$c['certificateValidityPeriod']);
                }
                if (isset($c['is_requirement'])) {
                    $new->setIsRequirement((bool)$c['is_requirement']);
                } elseif (isset($c['isRequirement'])) {
                    $new->setIsRequirement((bool)$c['isRequirement']);
                }
                if (isset($c['default_lowest_eval_exclude'])) {
                    $new->setDefaultLowestEvalExclude((bool)$c['default_lowest_eval_exclude']);
                } elseif (isset($c['defaultLowestEvalExclude'])) {
                    $new->setDefaultLowestEvalExclude((bool)$c['defaultLowestEvalExclude']);
                }
                if (array_key_exists('minimum_to_validate', $c)) {
                    $new->setMinimumToValidate((int)$c['minimum_to_validate']);
                } elseif (array_key_exists('minimumToValidate', $c)) {
                    $new->setMinimumToValidate((int)$c['minimumToValidate']);
                }
                if (array_key_exists('gradebooks_to_validate_in_dependence', $c)) {
                    $new->setGradeBooksToValidateInDependence((int)$c['gradebooks_to_validate_in_dependence']);
                } elseif (array_key_exists('gradeBooksToValidateInDependence', $c)) {
                    $new->setGradeBooksToValidateInDependence((int)$c['gradeBooksToValidateInDependence']);
                }
                if (array_key_exists('allow_skills_by_subcategory', $c)) {
                    $new->setAllowSkillsBySubcategory((int)$c['allow_skills_by_subcategory']);
                } elseif (array_key_exists('allowSkillsBySubcategory', $c)) {
                    $new->setAllowSkillsBySubcategory((int)$c['allowSkillsBySubcategory']);
                }
                if (!empty($c['grade_model_id'])) {
                    $gm = $em->find(GradeModel::class, (int)$c['grade_model_id']);
                    if ($gm) { $new->setGradeModel($gm); }
                }

                $em->persist($new);
                $em->flush();

                if ($oldId > 0) {
                    $oldIdToNewCat[$oldId] = $new;
                }
            }
        }

        // 3) Second pass: wire parents
        foreach ($this->course->resources[RESOURCE_GRADEBOOK] as $gbItem) {
            $categories = (array) ($gbItem->categories ?? []);
            foreach ($categories as $rawCat) {
                $c = is_array($rawCat) ? $rawCat : (array) $rawCat;
                $oldId     = (int)($c['id'] ?? $c['iid'] ?? 0);
                $parentOld = (int)($c['parent_id'] ?? $c['parentId'] ?? 0);
                if ($oldId > 0 && isset($oldIdToNewCat[$oldId]) && $parentOld > 0 && isset($oldIdToNewCat[$parentOld])) {
                    $cat = $oldIdToNewCat[$oldId];
                    $cat->setParent($oldIdToNewCat[$parentOld]);
                    $em->persist($cat);
                }
            }
        }
        $em->flush();

        // 4) Evaluations + Links
        foreach ($this->course->resources[RESOURCE_GRADEBOOK] as $gbItem) {
            $categories = (array) ($gbItem->categories ?? []);
            foreach ($categories as $rawCat) {
                $c = is_array($rawCat) ? $rawCat : (array) $rawCat;
                $oldId = (int)($c['id'] ?? $c['iid'] ?? 0);
                if ($oldId <= 0 || !isset($oldIdToNewCat[$oldId])) { continue; }

                $dstCat = $oldIdToNewCat[$oldId];

                // Evaluations
                foreach ((array)($c['evaluations'] ?? []) as $rawEval) {
                    $e = is_array($rawEval) ? $rawEval : (array) $rawEval;

                    $eval = new GradebookEvaluation();
                    $eval->setCourse($courseEntity);
                    $eval->setCategory($dstCat);
                    $eval->setTitle((string)($e['title'] ?? 'Evaluation'));
                    $eval->setDescription((string)($e['description'] ?? ''));
                    $eval->setWeight((float)($e['weight'] ?? 0.0));
                    $eval->setMax((float)($e['max'] ?? 100.0));
                    $eval->setType((string)($e['type'] ?? 'manual'));
                    $eval->setVisible((int)($e['visible'] ?? 1));
                    $eval->setLocked((int)($e['locked'] ?? 0));

                    if (isset($e['best_score']))    { $eval->setBestScore((float)$e['best_score']); }
                    if (isset($e['average_score'])) { $eval->setAverageScore((float)$e['average_score']); }
                    if (isset($e['score_weight']))  { $eval->setScoreWeight((float)$e['score_weight']); }
                    if (isset($e['min_score']))     { $eval->setMinScore((float)$e['min_score']); }

                    $em->persist($eval);
                }

                // Links
                foreach ((array)($c['links'] ?? []) as $rawLink) {
                    $l = is_array($rawLink) ? $rawLink : (array) $rawLink;

                    $linkType  = (int)($l['type']   ?? $l['link_type'] ?? 0);
                    $legacyRef = (int)($l['ref_id'] ?? $l['refId']     ?? 0);
                    if ($linkType <= 0 || $legacyRef <= 0) {
                        $this->dlog('restore_gradebook: skipping link (missing type/ref)', $l);
                        continue;
                    }

                    $resourceType = $this->gb_guessResourceTypeByLinkType($linkType);
                    $newRefId     = $this->gb_resolveDestinationId($resourceType, $legacyRef);
                    if ($newRefId <= 0) {
                        $this->dlog('restore_gradebook: skipping link (no destination id)', ['type' => $linkType, 'legacyRef' => $legacyRef]);
                        continue;
                    }

                    $link = new GradebookLink();
                    $link->setCourse($courseEntity);
                    $link->setCategory($dstCat);
                    $link->setType($linkType);
                    $link->setRefId($newRefId);
                    $link->setWeight((float)($l['weight'] ?? 0.0));
                    $link->setVisible((int)($l['visible'] ?? 1));
                    $link->setLocked((int)($l['locked'] ?? 0));

                    if (isset($l['best_score']))    { $link->setBestScore((float)$l['best_score']); }
                    if (isset($l['average_score'])) { $link->setAverageScore((float)$l['average_score']); }
                    if (isset($l['score_weight']))  { $link->setScoreWeight((float)$l['score_weight']); }
                    if (isset($l['min_score']))     { $link->setMinScore((float)$l['min_score']); }

                    $em->persist($link);
                }

                $em->flush();
            }
        }

        $this->dlog('restore_gradebook: done');
    }

    /** Map GradebookLink type → RESOURCE_* bucket used in $this->course->resources */
    private function gb_guessResourceTypeByLinkType(int $linkType): ?int
    {
        return match ($linkType) {
            LINK_EXERCISE            => RESOURCE_QUIZ,
            LINK_STUDENTPUBLICATION  => RESOURCE_WORK,
            LINK_LEARNPATH           => RESOURCE_LEARNPATH,
            LINK_FORUM_THREAD        => RESOURCE_FORUMTOPIC,
            LINK_ATTENDANCE          => RESOURCE_ATTENDANCE,
            LINK_SURVEY              => RESOURCE_SURVEY,
            LINK_HOTPOTATOES         => RESOURCE_QUIZ,
            default                  => null,
        };
    }

    /** Given a RESOURCE_* bucket and legacy id, return destination id (if that item was restored) */
    private function gb_resolveDestinationId(?int $type, int $legacyId): int
    {
        if (null === $type) { return 0; }
        if (!$this->course->has_resources($type)) { return 0; }
        $bucket = $this->course->resources[$type] ?? [];
        if (!isset($bucket[$legacyId])) { return 0; }
        $res = $bucket[$legacyId];
        $destId = (int)($res->destination_id ?? 0);
        return $destId > 0 ? $destId : 0;
    }


    /**
     * Restore course assets (not included in documents).
     */
    public function restore_assets()
    {
        if ($this->course->has_resources(RESOURCE_ASSET)) {
            $resources = $this->course->resources;
            $path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/';

            foreach ($resources[RESOURCE_ASSET] as $asset) {
                if (is_file($this->course->backup_path.'/'.$asset->path) &&
                    is_readable($this->course->backup_path.'/'.$asset->path) &&
                    is_dir(dirname($path.$asset->path)) &&
                    is_writeable(dirname($path.$asset->path))
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
}
