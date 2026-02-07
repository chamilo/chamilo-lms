<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Attendance;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\CalendarEvent;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseDescription;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Glossary;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\GradeBookBackup;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Thematic;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Work;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CAnnouncementAttachment;
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
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Closure;
use Countable;
use Database;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use DocumentManager;
use ReflectionProperty;
use stdClass;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

use const PHP_URL_PATH;

/**
 * CourseBuilder focused on Doctrine/ResourceNode export (keeps legacy orchestration).
 */
class CourseBuilder
{
    /**
     * @var Course Legacy course container used by the exporter
     */
    public $course;

    /**
     * @var array<string> Only the tools to build (defaults kept)
     */
    public array $tools_to_build = [
        'documents', 'forums', 'tool_intro', 'links', 'quizzes', 'quiz_questions',
        'assets', 'surveys', 'survey_questions', 'announcements', 'events',
        'course_descriptions', 'glossary', 'wiki', 'thematic', 'attendance', 'works',
        'gradebook', 'learnpath_category', 'learnpaths',
    ];

    /**
     * @var array<string, int|string> Legacy constant map (extend as you add tools)
     */
    public array $toolToName = [
        'documents' => RESOURCE_DOCUMENT,
        'forums' => RESOURCE_FORUM,
        'tool_intro' => RESOURCE_TOOL_INTRO,
        'links' => RESOURCE_LINK,
        'quizzes' => RESOURCE_QUIZ,
        'quiz_questions' => RESOURCE_QUIZQUESTION,
        'assets' => 'asset',
        'surveys' => RESOURCE_SURVEY,
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
    ];

    /**
     * @var array<string, array<int>> Optional whitelist of IDs per tool
     */
    public array $specific_id_list = [];

    /**
     * Documents referenced inside HTML.
     *
     * Stored as an associative array keyed by the URL to avoid duplicates:
     *  - key: url
     *  - value: [url, scope, type]
     *
     * @var array<string, array{0:string,1:string,2:string}>
     */
    public array $documentsAddedInText = [];

    /**
     * Doctrine services.
     */
    private EntityManagerInterface $em;
    private CDocumentRepository $docRepo;

    /**
     * When exporting with a session context:
     * - true  => include both base course content + session content
     * - false => include only session-specific content
     *
     * This must be used consistently by all resource builders.
     */
    private bool $withBaseContent = false;

    /**
     * Cached course info array used during build().
     *
     * @var array<string,mixed>
     */
    private array $courseInfo = [];

    /**
     * Internal trace toggle for this class.
     * Set to false to disable logs.
     */
    private const TRACE_ENABLED = false;

    /**
     * Constructor (keeps legacy init; wires Doctrine repositories).
     *
     * @param string     $type   'partial'|'complete'
     * @param array|null $course Optional course info array
     */
    public function __construct(string $type = '', ?array $course = null)
    {
        // Legacy behavior preserved
        $_course = api_get_course_info();
        if (!empty($course['official_code'])) {
            $_course = $course;
        }

        $this->course = new Course();
        $this->course->code = $_course['code'];
        $this->course->type = $type;
        $this->course->encoding = api_get_system_encoding();
        $this->course->info = $_course;

        /** @var EntityManagerInterface $em */
        $em = Database::getManager();
        $this->em = $em;

        /** @var CDocumentRepository $docRepo */
        $docRepo = Container::getDocumentRepository();
        $this->docRepo = $docRepo;

        $this->courseInfo = is_array($this->course->info ?? null) ? $this->course->info : [];
    }

    /**
     * Merge a parsed list of document refs into memory.
     *
     * @param array<int, array{0:string,1:string,2:string}> $list
     */
    public function addDocumentList(array $list): void
    {
        foreach ($list as $item) {
            $url = (string) ($item[0] ?? '');
            if ('' === $url) {
                continue;
            }

            // Keep the first occurrence for a given URL (dedupe by URL key).
            if (!isset($this->documentsAddedInText[$url])) {
                $this->documentsAddedInText[$url] = $item;
            }
        }
    }

    /**
     * Parse HTML and collect referenced course documents.
     */
    public function findAndSetDocumentsInText(string $html = ''): void
    {
        if ('' === $html) {
            return;
        }
        $documentList = DocumentManager::get_resources_from_source_html($html);
        $this->addDocumentList($documentList);
    }

    /**
     * Resolve collected HTML links to CDocument iids and build them.
     */
    public function restoreDocumentsFromList(?CourseEntity $course = null, ?SessionEntity $session = null): void
    {
        if (empty($this->documentsAddedInText)) {
            return;
        }

        // Resolve current course entity if not provided.
        if (!$course instanceof CourseEntity) {
            $courseInfo = api_get_course_info();
            $courseCode = (string) ($courseInfo['code'] ?? '');
            if ('' === $courseCode) {
                return;
            }

            /** @var CourseEntity|null $resolved */
            $resolved = $this->em->getRepository(CourseEntity::class)->findOneBy(['code' => $courseCode]);
            if (!$resolved instanceof CourseEntity) {
                return;
            }

            $course = $resolved;
        }

        $need = [];

        foreach ($this->documentsAddedInText as $item) {
            [$url, $scope, $type] = $item; // url, scope(local/remote), type(rel/abs/url)

            // Only process local document-style URLs.
            if ('local' !== $scope || !\in_array($type, ['rel', 'abs'], true)) {
                continue;
            }

            $rel = $this->extractRelativeDocumentPathFromUrl((string) $url);
            $rel = $this->normalizeDocumentRelPath($rel);

            if ('' === $rel) {
                continue;
            }

            // Include the file path itself.
            $need[$rel] = true;

            // Also include parent folders to preserve hierarchy on restore.
            $parts = array_values(array_filter(explode('/', $rel), static fn ($s) => '' !== $s));
            if (\count($parts) > 1) {
                $prefix = '';
                for ($i = 0; $i < \count($parts) - 1; $i++) {
                    $prefix = '' === $prefix ? $parts[$i] : $prefix.'/'.$parts[$i];
                    $need[$prefix] = true;
                }
            }
        }

        if (empty($need)) {
            return;
        }

        $paths = array_keys($need);

        $iids = $this->resolveDocumentIidsByRelativePaths($course, $session, $paths);
        if (empty($iids)) {
            $this->trace('COURSE_BUILD: no referenced documents matched in repository (paths_count='.\count($paths).')');
            return;
        }

        $sid = (int) ($session?->getId() ?? api_get_session_id());
        $cid = (int) $course->getId();

        $this->build_documents($sid, $cid, $this->withBaseContent, $iids);
    }

    /**
     * Extract path part after ".../document/" from a URL.
     * Returns an empty string when not a document-like URL.
     */
    private function extractRelativeDocumentPathFromUrl(string $url): string
    {
        if ('' === $url) {
            return '';
        }

        // Remove fragment/query early, keep only path.
        $decoded = urldecode($url);
        $path = (string) (parse_url($decoded, PHP_URL_PATH) ?? '');
        if ('' === $path) {
            $path = $decoded;
        }

        // Most common patterns:
        // - /courses/COURSECODE/document/Folder/file.png
        // - /document/Folder/file.png
        // - document/Folder/file.png
        $pos = stripos($path, '/document/');
        if (false !== $pos) {
            return substr($path, $pos + \strlen('/document/')) ?: '';
        }

        if (str_starts_with($path, 'document/')) {
            return substr($path, \strlen('document/')) ?: '';
        }
        if (str_starts_with($path, '/document/')) {
            return substr($path, \strlen('/document/')) ?: '';
        }

        // Fallback: "/document" without trailing slash.
        $pos2 = stripos($path, '/document');
        if (false !== $pos2) {
            $tail = substr($path, $pos2 + \strlen('/document')) ?: '';
            return ltrim($tail, '/');
        }

        return '';
    }

    /**
     * Normalize a relative document path for matching.
     */
    private function normalizeDocumentRelPath(string $path): string
    {
        if ('' === $path) {
            return '';
        }

        $path = urldecode($path);
        $path = str_replace('\\', '/', $path);

        // Remove "Documents" prefix if present (defensive).
        $path = preg_replace('~^/?Documents/?~i', '', $path) ?? $path;

        // Trim slashes and collapse duplicated slashes.
        $path = trim($path, '/');
        $path = preg_replace('~/{2,}~', '/', $path) ?? $path;

        return (string) $path;
    }

    /**
     * Resolve document IIDs by comparing computed "relative display paths" with the given list.
     *
     * @param array<int,string> $relativePaths
     *
     * @return array<int>
     */
    private function resolveDocumentIidsByRelativePaths(
        CourseEntity $course,
        ?SessionEntity $session,
        array $relativePaths
    ): array {
        if (empty($relativePaths)) {
            return [];
        }

        $need = [];
        foreach ($relativePaths as $p) {
            $p = $this->normalizeDocumentRelPath((string) $p);
            if ('' !== $p) {
                $need[$p] = true;
            }
        }
        if (empty($need)) {
            return [];
        }

        // IMPORTANT: always pass withBaseContent to the repository when supported.
        $qb = $this->getResourcesByCourseQbFromRepo($this->docRepo, $course, $session, $this->withBaseContent);

        /** @var CDocument[] $docs */
        $docs = $qb->getQuery()->getResult();

        $documentsRoot = $this->docRepo->getCourseDocumentsRootNode($course);

        $iids = [];

        foreach ($docs as $doc) {
            $node = $doc->getResourceNode();
            if (!$node instanceof ResourceNode) {
                continue;
            }

            $rel = '';
            if ($documentsRoot instanceof ResourceNode) {
                $rel = (string) $node->getPathForDisplayRemoveBase((string) $documentsRoot->getPath());
            } else {
                $rel = (string) $node->convertPathForDisplay((string) $node->getPath());
                $rel = preg_replace('~^/?Documents/?~i', '', (string) $rel) ?? $rel;
            }

            $rel = $this->normalizeDocumentRelPath($rel);

            if ('' === $rel) {
                continue;
            }

            if (isset($need[$rel])) {
                $iid = (int) $doc->getIid();
                if ($iid > 0) {
                    $iids[] = $iid;
                }
            }
        }

        return array_values(array_unique($iids));
    }

    /**
     * Set tools to build.
     *
     * @param array<string> $array
     */
    public function set_tools_to_build(array $array): void
    {
        $this->tools_to_build = $array;
    }

    /**
     * Set specific id list per tool.
     *
     * @param array<string, array<int>> $array
     */
    public function set_tools_specific_id_list(array $array): void
    {
        $this->specific_id_list = $array;
    }

    /**
     * Build the course (documents already repo-based; other tools preserved).
     *
     * @param array<int|string>   $parseOnlyToolList
     * @param array<string,mixed> $toolsFromPost
     */
    public function build(
        int $session_id = 0,
        string $courseCode = '',
        bool $withBaseContent = false,
        array $parseOnlyToolList = [],
        array $toolsFromPost = []
    ): Course {
        $this->withBaseContent = $withBaseContent;

        // Resolve effective course code:
        // - If caller did not pass a code, reuse the code already loaded in the constructor.
        $effectiveCourseCode = '' !== trim($courseCode)
            ? trim($courseCode)
            : (string) ($this->course->code ?? '');

        if ('' === $effectiveCourseCode) {
            throw new \RuntimeException('CourseBuilder cannot determine a course code (empty effective code).');
        }

        // Prefer constructor-provided course info to avoid api_get_course_info() side effects.
        if (empty($this->courseInfo) || !is_array($this->courseInfo)) {
            $this->courseInfo = is_array($this->course->info ?? null) ? $this->course->info : [];
        }

        // Only fetch via api_get_course_info() if we still don't have matching info.
        if (
            empty($this->courseInfo)
            || !is_array($this->courseInfo)
            || (string) ($this->courseInfo['code'] ?? '') !== $effectiveCourseCode
        ) {
            $this->courseInfo = api_get_course_info($effectiveCourseCode);
        }

        if (empty($this->courseInfo) || !is_array($this->courseInfo) || '' === (string) ($this->courseInfo['code'] ?? '')) {
            throw new \RuntimeException(sprintf(
                'CourseBuilder cannot load course info for course code "%s".',
                $effectiveCourseCode
            ));
        }

        /** @var CourseEntity|null $courseEntity */
        $courseEntity = $this->em->getRepository(CourseEntity::class)->findOneBy(['code' => $effectiveCourseCode]);
        if (!$courseEntity instanceof CourseEntity) {
            throw new \RuntimeException(sprintf(
                'CourseBuilder cannot resolve CourseEntity for course code "%s".',
                $effectiveCourseCode
            ));
        }

        /** @var SessionEntity|null $sessionEntity */
        $sessionEntity = $session_id
            ? $this->em->getRepository(SessionEntity::class)->find($session_id)
            : null;

        // Legacy DTO where resources[...] are built
        $legacyCourse = $this->course;
        foreach ($this->tools_to_build as $toolKey) {
            if (!empty($parseOnlyToolList)) {
                $const = $this->toolToName[$toolKey] ?? null;
                if (null !== $const && !\in_array($const, $parseOnlyToolList, true)) {
                    continue;
                }
            }

            if ('documents' === $toolKey) {
                $ids = $this->specific_id_list['documents'] ?? [];
                $this->build_documents_with_repo($courseEntity, $sessionEntity, $withBaseContent, $ids);
            }

            if ('forums' === $toolKey || 'forum' === $toolKey) {
                $ids = $this->specific_id_list['forums'] ?? $this->specific_id_list['forum'] ?? [];
                $this->build_forum_category($legacyCourse, $courseEntity, $sessionEntity, $ids);
                $this->build_forums($legacyCourse, $courseEntity, $sessionEntity, $ids);
                $this->build_forum_topics($legacyCourse, $courseEntity, $sessionEntity, $ids);
                $this->build_forum_posts($legacyCourse, $courseEntity, $sessionEntity, $ids);
            }

            if ('tool_intro' === $toolKey) {
                $this->build_tool_intro($legacyCourse, $courseEntity, $sessionEntity);
            }

            if ('links' === $toolKey) {
                $ids = $this->specific_id_list['links'] ?? [];
                $this->build_links($legacyCourse, $courseEntity, $sessionEntity, $ids);
            }

            if ('quizzes' === $toolKey || 'quiz' === $toolKey) {
                $ids = $this->specific_id_list['quizzes'] ?? $this->specific_id_list['quiz'] ?? [];
                $neededQuestionIds = $this->build_quizzes($legacyCourse, $courseEntity, $sessionEntity, $ids);
                // Always export question bucket required by the quizzes
                $this->build_quiz_questions($legacyCourse, $courseEntity, $sessionEntity, $neededQuestionIds);
            }

            if ('quiz_questions' === $toolKey) {
                $ids = $this->specific_id_list['quiz_questions'] ?? [];
                $this->build_quiz_questions($legacyCourse, $courseEntity, $sessionEntity, $ids);
            }

            if ('surveys' === $toolKey || 'survey' === $toolKey) {
                $ids = $this->specific_id_list['surveys'] ?? $this->specific_id_list['survey'] ?? [];
                $neededQ = $this->build_surveys($this->course, $courseEntity, $sessionEntity, $ids);
                $this->build_survey_questions($this->course, $courseEntity, $sessionEntity, $neededQ);
            }

            if ('survey_questions' === $toolKey) {
                $this->build_survey_questions($this->course, $courseEntity, $sessionEntity, []);
            }

            if ('announcements' === $toolKey) {
                $ids = $this->specific_id_list['announcements'] ?? [];
                $this->build_announcements($this->course, $courseEntity, $sessionEntity, $ids);
            }

            if ('events' === $toolKey) {
                $ids = $this->specific_id_list['events'] ?? [];
                $this->build_events($this->course, $courseEntity, $sessionEntity, $ids);
            }

            if ('course_descriptions' === $toolKey) {
                $ids = $this->specific_id_list['course_descriptions'] ?? [];
                $this->build_course_descriptions($this->course, $courseEntity, $sessionEntity, $ids);
            }

            if ('glossary' === $toolKey) {
                $ids = $this->specific_id_list['glossary'] ?? [];
                $this->build_glossary($this->course, $courseEntity, $sessionEntity, $ids);
            }

            if ('wiki' === $toolKey) {
                $ids = $this->specific_id_list['wiki'] ?? [];
                $this->build_wiki($this->course, $courseEntity, $sessionEntity, $ids);
            }

            if ('thematic' === $toolKey) {
                $ids = $this->specific_id_list['thematic'] ?? [];
                $this->build_thematic($this->course, $courseEntity, $sessionEntity, $ids);
            }

            if ('attendance' === $toolKey) {
                $ids = $this->specific_id_list['attendance'] ?? [];
                $this->build_attendance($this->course, $courseEntity, $sessionEntity, $ids);
            }

            if ('works' === $toolKey) {
                $ids = $this->specific_id_list['works'] ?? [];
                $this->build_works($this->course, $courseEntity, $sessionEntity, $ids);
            }

            if ('gradebook' === $toolKey) {
                $this->build_gradebook($this->course, $courseEntity, $sessionEntity);
            }

            if ('learnpath_category' === $toolKey) {
                $ids = $this->specific_id_list['learnpath_category'] ?? [];
                $this->build_learnpath_category($this->course, $courseEntity, $sessionEntity, $ids);
            }

            if ('learnpaths' === $toolKey) {
                $ids = $this->specific_id_list['learnpaths'] ?? [];
                $this->build_learnpaths($this->course, $courseEntity, $sessionEntity, $ids, true);
            }
        }

        // Always try to include documents referenced inside HTML (images, attachments, etc.).
        if ($courseEntity instanceof CourseEntity) {
            $this->restoreDocumentsFromList($courseEntity, $sessionEntity);
        }

        return $this->course;
    }

    /**
     * Export Learnpath categories (CLpCategory).
     *
     * @param array<int> $ids
     */
    public function build_learnpath_category(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getLpCategoryRepository();

        // propagate withBaseContent to repo when supported.
        $qb = $this->getResourcesByCourseQbFromRepo($repo, $courseEntity, $sessionEntity, $this->withBaseContent);

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
        }

        /** @var CLpCategory[] $rows */
        $rows = $qb->getQuery()->getResult();

        foreach ($rows as $cat) {
            $iid = (int) $cat->getIid();
            $title = (string) $cat->getTitle();

            $payload = [
                'id' => $iid,
                'title' => $title,
            ];

            $legacyCourse->resources[RESOURCE_LEARNPATH_CATEGORY][$iid] =
                $this->mkLegacyItem(RESOURCE_LEARNPATH_CATEGORY, $iid, $payload);
        }
    }

    /**
     * Export Learnpaths (CLp) + items, with optional SCORM folder packing.
     *
     * @param array<int> $idList
     */
    public function build_learnpaths(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $idList = [],
        bool $addScormFolder = true
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $lpRepo = Container::getLpRepository();

        // propagate withBaseContent to repo when supported.
        $qb = $this->getResourcesByCourseQbFromRepo($lpRepo, $courseEntity, $sessionEntity, $this->withBaseContent);

        if (!empty($idList)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $idList))))
            ;
        }

        /** @var CLp[] $lps */
        $lps = $qb->getQuery()->getResult();

        // Map SCORM folder name -> LP iid when possible (best-effort, no guesses beyond CLp getters).
        $scormLpByDir = [];
        foreach ($lps as $lpTmp) {
            $lpTypeTmp = (int) $lpTmp->getLpType();
            if (CLp::SCORM_TYPE !== $lpTypeTmp) {
                continue;
            }

            $p = trim((string) $lpTmp->getPath());
            if ('' === $p) {
                continue;
            }

            // Try direct folder name and basename variants.
            $pNorm = trim(str_replace('\\', '/', $p), '/');
            if ('' !== $pNorm) {
                $scormLpByDir[$pNorm] = (int) ($lpTmp->getIid() ?? 0);
                $base = basename($pNorm);
                if ('' !== $base) {
                    $scormLpByDir[$base] = (int) ($lpTmp->getIid() ?? 0);
                }
            }
        }

        foreach ($lps as $lp) {
            $iid = (int) ($lp->getIid() ?? 0);
            if ($iid <= 0) {
                continue;
            }

            $lpType = (int) $lp->getLpType(); // 1=LP, 2=SCORM, 3=AICC

            // Build raw items keyed by legacy item iid so we can compute levels safely.
            $rawItemsById = [];
            $parentById = [];

            /** @var CLpItem $it */
            foreach ($lp->getItems() as $it) {
                $itemId = (int) ($it->getIid() ?? 0);
                if ($itemId <= 0) {
                    continue;
                }

                $itemType = (string) $it->getItemType();
                $itemTypeLower = strtolower($itemType);

                // Avoid exporting an eventual root item (restore will use lpItemRepo->getRootItem()).
                if ('root' === $itemTypeLower) {
                    continue;
                }

                $parentId = (int) $it->getParentItemId();
                $parentById[$itemId] = $parentId;

                $ref = (string) $it->getRef();
                $path = (string) $it->getPath();

                $rawItemsById[$itemId] = [
                    'id' => $itemId,
                    'item_type' => $itemType,
                    'ref' => $ref,
                    'identifier' => $ref,          // legacy compatibility
                    'path' => $path,
                    'identifierref' => $path,      // legacy compatibility
                    'title' => (string) $it->getTitle(),
                    'description' => (string) ($it->getDescription() ?? ''),
                    'min_score' => (float) $it->getMinScore(),
                    'max_score' => null !== $it->getMaxScore() ? (float) $it->getMaxScore() : null,
                    'mastery_score' => null !== $it->getMasteryScore() ? (float) $it->getMasteryScore() : null,
                    'parent_item_id' => $parentId,
                    'previous_item_id' => null !== $it->getPreviousItemId() ? (int) $it->getPreviousItemId() : null,
                    'next_item_id' => null !== $it->getNextItemId() ? (int) $it->getNextItemId() : null,
                    'display_order' => (int) $it->getDisplayOrder(),
                    'prerequisite' => (string) ($it->getPrerequisite() ?? ''),
                    'parameters' => (string) ($it->getParameters() ?? ''),
                    'launch_data' => (string) $it->getLaunchData(),
                    'audio' => (string) ($it->getAudio() ?? ''),
                    'duration' => method_exists($it, 'getDuration') ? $it->getDuration() : null,
                    'export_allowed' => method_exists($it, 'isExportAllowed') ? (bool) $it->isExportAllowed() : null,
                ];
            }

            // Compute "level" purely from parent_item_id relationships (no reliance on getLvl()).
            $visiting = [];
            $levelOf = null;
            $levelOf = static function (int $id) use (&$levelOf, &$parentById, &$visiting): int {
                if ($id <= 0) {
                    return 0;
                }
                if (isset($visiting[$id])) {
                    // Cycle protection: treat as root-level.
                    return 0;
                }
                $pid = $parentById[$id] ?? 0;
                if ($pid <= 0) {
                    return 0;
                }
                $visiting[$id] = true;
                $lvl = 1 + $levelOf($pid);
                unset($visiting[$id]);

                return $lvl;
            };

            foreach ($rawItemsById as $itemId => $row) {
                $rawItemsById[$itemId]['level'] = $levelOf((int) $itemId);
            }

            // linked_resources: helps restore minimal deps without relying on selection.
            $linked = [
                'document' => [],
                'quiz' => [],
                'link' => [],
                'student_publication' => [],
                'survey' => [],
                'forum' => [],
            ];

            $addLinked = static function (array &$bucket, string $key, $raw): void {
                if (!isset($bucket[$key])) {
                    return;
                }
                if (null === $raw || '' === $raw) {
                    return;
                }
                $s = (string) $raw;
                if (!ctype_digit($s)) {
                    return;
                }
                $bucket[$key][(int) $s] = true;
            };

            foreach ($rawItemsById as $row) {
                $t = strtolower((string) ($row['item_type'] ?? ''));
                $raw = $row['path'] ?? ($row['ref'] ?? ($row['identifierref'] ?? ''));
                switch ($t) {
                    case 'document':
                        $addLinked($linked, 'document', $raw);
                        break;
                    case 'quiz':
                    case 'exercise':
                        $addLinked($linked, 'quiz', $raw);
                        break;
                    case 'link':
                    case 'weblink':
                    case 'url':
                        $addLinked($linked, 'link', $raw);
                        break;
                    case 'work':
                    case 'student_publication':
                        $addLinked($linked, 'student_publication', $raw);
                        break;
                    case 'survey':
                        $addLinked($linked, 'survey', $raw);
                        break;
                    case 'forum':
                        $addLinked($linked, 'forum', $raw);
                        break;
                }
            }

            // Convert linked sets to lists.
            foreach ($linked as $k => $set) {
                $linked[$k] = array_values(array_map('intval', array_keys($set)));
            }

            // Stable items ordering for export (level, then display_order, then id).
            $items = array_values($rawItemsById);
            usort($items, static function (array $a, array $b): int {
                $la = (int) ($a['level'] ?? 0);
                $lb = (int) ($b['level'] ?? 0);
                if ($la !== $lb) {
                    return $la <=> $lb;
                }
                $oa = (int) ($a['display_order'] ?? 0);
                $ob = (int) ($b['display_order'] ?? 0);
                if ($oa !== $ob) {
                    return $oa <=> $ob;
                }
                return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
            });

            $payload = [
                'id' => $iid,
                'lp_type' => $lpType,
                'title' => (string) $lp->getTitle(),
                'path' => (string) $lp->getPath(),
                'ref' => (string) ($lp->getRef() ?? ''),
                'description' => (string) ($lp->getDescription() ?? ''),
                'content_local' => (string) $lp->getContentLocal(),
                'default_encoding' => (string) $lp->getDefaultEncoding(),
                'default_view_mod' => (string) $lp->getDefaultViewMod(),
                'prevent_reinit' => (bool) $lp->getPreventReinit(),
                'force_commit' => (bool) $lp->getForceCommit(),
                'content_maker' => (string) $lp->getContentMaker(),
                'js_lib' => (string) $lp->getJsLib(),
                'content_license' => (string) $lp->getContentLicense(),
                'debug' => (bool) $lp->getDebug(),
                'theme' => (string) $lp->getTheme(),
                'author' => (string) $lp->getAuthor(),
                'prerequisite' => (int) $lp->getPrerequisite(),
                'hide_toc_frame' => method_exists($lp, 'getHideTocFrame') ? (bool) $lp->getHideTocFrame() : null,
                'seriousgame_mode' => method_exists($lp, 'getSeriousgameMode') ? (bool) $lp->getSeriousgameMode() : null,
                'use_max_score' => (int) $lp->getUseMaxScore(),
                'autolaunch' => (int) $lp->getAutolaunch(),
                'max_attempts' => method_exists($lp, 'getMaxAttempts') ? (int) $lp->getMaxAttempts() : null,
                'subscribe_users' => (int) $lp->getSubscribeUsers(),
                'accumulate_scorm_time' => (int) $lp->getAccumulateScormTime(),
                'accumulate_work_time' => (int) $lp->getAccumulateWorkTime(),
                'next_lp_id' => (int) $lp->getNextLpId(),
                'subscribe_user_by_date' => (bool) $lp->getSubscribeUserByDate(),
                'display_not_allowed_lp' => (bool) $lp->getDisplayNotAllowedLp(),
                'duration' => method_exists($lp, 'getDuration') ? $lp->getDuration() : null,
                'auto_forward_video' => method_exists($lp, 'getAutoForwardVideo') ? (bool) $lp->getAutoForwardVideo() : null,
                'created_on' => $this->fmtDate($lp->getCreatedOn()),
                'modified_on' => $this->fmtDate($lp->getModifiedOn()),
                'published_on' => $this->fmtDate($lp->getPublishedOn()),
                'expired_on' => $this->fmtDate($lp->getExpiredOn()),
                'session_id' => (int) ($sessionEntity?->getId() ?? 0),
                'category_id' => (int) ($lp->getCategory()?->getIid() ?? 0),
                'linked_resources' => $linked,
                'items' => $items,
            ];

            $legacyCourse->resources[RESOURCE_LEARNPATH][$iid] =
                $this->mkLegacyItem(RESOURCE_LEARNPATH, $iid, $payload, ['items', 'linked_resources']);
        }

        // Optional: pack “scorm” folder (legacy parity)
        if ($addScormFolder && isset($this->course->backup_path)) {
            $scormDir = rtrim((string) $this->course->backup_path, '/').'/scorm';
            if (is_dir($scormDir) && ($dh = @opendir($scormDir))) {
                $i = 1;
                while (false !== ($file = readdir($dh))) {
                    if ('.' === $file || '..' === $file) {
                        continue;
                    }
                    if (is_dir($scormDir.'/'.$file)) {
                        $payload = [
                            'path' => '/'.$file,
                            'name' => (string) $file,
                            'source_lp_id' => (int) ($scormLpByDir[$file] ?? 0),
                        ];

                        $legacyCourse->resources[RESOURCE_SCORM][$i] =
                            $this->mkLegacyItem(RESOURCE_SCORM, $i, $payload);

                        $i++;
                    }
                }
                closedir($dh);
            }
        }
    }

    /**
     * Export Gradebook (categories + evaluations + links).
     */
    public function build_gradebook(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = Database::getManager();
        $catRepo = $em->getRepository(GradebookCategory::class);

        $qb = $catRepo->createQueryBuilder('cat')
            ->andWhere('cat.course = :course')
            ->setParameter('course', $courseEntity);

        if ($sessionEntity instanceof SessionEntity) {
            if ($this->withBaseContent) {
                // Include base categories (session IS NULL) + session categories
                $qb->andWhere(
                    $qb->expr()->orX(
                        'cat.session = :session',
                        'cat.session IS NULL'
                    )
                )->setParameter('session', $sessionEntity);
            } else {
                // Only session-specific categories
                $qb->andWhere('cat.session = :session')
                    ->setParameter('session', $sessionEntity);
            }
        } else {
            // No session context => base only
            $qb->andWhere('cat.session IS NULL');
        }

        $qb->addOrderBy('cat.id', 'ASC');

        /** @var GradebookCategory[] $cats */
        $cats = $qb->getQuery()->getResult();

        if (!$cats) {
            return;
        }

        $payloadCategories = [];
        foreach ($cats as $cat) {
            $payloadCategories[] = $this->serializeGradebookCategory($cat);
        }

        $backup = new GradeBookBackup($payloadCategories);
        $legacyCourse->add_resource($backup);
    }

    /**
     * Serialize GradebookCategory (and nested parts) to array for restore.
     *
     * @return array<string,mixed>
     */
    private function serializeGradebookCategory(GradebookCategory $cat): array
    {
        $em = Database::getManager();

        $evalRepo = $em->getRepository(GradebookEvaluation::class);
        $linkRepo = $em->getRepository(GradebookLink::class);

        $id = method_exists($cat, 'getId') ? (int) $cat->getId() : 0;

        $parentId = 0;
        if (method_exists($cat, 'getParent') && $cat->getParent()) {
            $parentId = method_exists($cat->getParent(), 'getId') ? (int) $cat->getParent()->getId() : 0;
        }

        $evaluations = [];
        foreach ($evalRepo->findBy(['category' => $cat]) as $e) {
            $evaluations[] = [
                'title' => method_exists($e, 'getTitle') ? (string) $e->getTitle() : 'Evaluation',
                'description' => method_exists($e, 'getDescription') ? (string) $e->getDescription() : '',
                'weight' => method_exists($e, 'getWeight') ? (float) $e->getWeight() : 0.0,
                'max' => method_exists($e, 'getMax') ? (float) $e->getMax() : 100.0,
                'type' => method_exists($e, 'getType') ? (string) $e->getType() : 'manual',
                'visible' => method_exists($e, 'getVisible') ? (int) $e->getVisible() : 1,
                'locked' => method_exists($e, 'getLocked') ? (int) $e->getLocked() : 0,
            ];
        }

        $links = [];
        foreach ($linkRepo->findBy(['category' => $cat]) as $l) {
            $links[] = [
                'type' => method_exists($l, 'getType') ? (int) $l->getType() : 0,
                'ref_id' => method_exists($l, 'getRefId') ? (int) $l->getRefId() : 0,
                'weight' => method_exists($l, 'getWeight') ? (float) $l->getWeight() : 0.0,
                'visible' => method_exists($l, 'getVisible') ? (int) $l->getVisible() : 1,
                'locked' => method_exists($l, 'getLocked') ? (int) $l->getLocked() : 0,
            ];
        }

        return [
            'id' => $id,
            'parent_id' => $parentId,
            'title' => method_exists($cat, 'getTitle') ? (string) $cat->getTitle() : 'Category',
            'description' => method_exists($cat, 'getDescription') ? (string) $cat->getDescription() : '',
            'weight' => method_exists($cat, 'getWeight') ? (float) $cat->getWeight() : 0.0,
            'visible' => method_exists($cat, 'getVisible') ? (bool) $cat->getVisible() : true,
            'locked' => method_exists($cat, 'getLocked') ? (int) $cat->getLocked() : 0,
            'evaluations' => $evaluations,
            'links' => $links,
        ];
    }

    /**
     * Export Works (root folders only; include assignment params).
     *
     * @param array<int> $ids
     */
    public function build_works(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $this->course->resources[RESOURCE_WORK] ??= [];

        // Try to read the flag from the backup/copy context
        $copyOnlySessionItems = (bool) (
            $this->course->copy_only_session_items
            ?? $this->course->copyOnlySessionItems
            ?? false
        );

        // If we only want session items, don't export base-course works (sid=0).
        if ($copyOnlySessionItems && null === $sessionEntity) {
            if (method_exists($this, 'dlog')) {
                $this->dlog('build_works: skipped for base course because copy_only_session_items is enabled', [
                    'course_id' => (int) $courseEntity->getId(),
                ]);
            }
            return;
        }

        $qb = $this->createContextQb(
            CStudentPublication::class,
            $courseEntity,
            $sessionEntity,
            'w'
        );

        $linksAlias = $this->getContextLinksAlias('w');

        $qb
            ->andWhere('w.filetype = :ft')
            ->setParameter('ft', 'folder')
            ->andWhere('w.publicationParent IS NULL')
            ->andWhere('w.active IN (0,1)')
            ->distinct()
            ->groupBy('w.iid')
            ->orderBy('w.iid', 'ASC')
        ;

        // Force strict session links when copying only session items.
        if ($copyOnlySessionItems && $sessionEntity) {
            $qb
                ->andWhere($linksAlias.'.session = :session')
                ->setParameter('session', $sessionEntity)
            ;
        }

        $results = $qb->getQuery()->getResult();

        $seen = [];
        foreach ($results as $pub) {
            if (!$pub instanceof CStudentPublication) {
                continue;
            }

            $iid = (int) $pub->getIid();
            if ($iid <= 0 || isset($seen[$iid])) {
                continue;
            }
            $seen[$iid] = true;

            $assignment = $pub->getAssignment();

            $params = [
                'iid' => $iid,
                'title' => (string) $pub->getTitle(),
                'description' => (string) $pub->getDescription(),

                'weight' => (float) ($pub->getWeight() ?? 0.0),
                'qualification' => (float) ($pub->getQualification() ?? 0.0),
                'allow_text_assignment' => (int) ($pub->getAllowTextAssignment() ?? 0),

                'default_visibility' => (bool) ($pub->getDefaultVisibility() ?? false),
                'student_delete_own_publication' => (bool) ($pub->getStudentDeleteOwnPublication() ?? false),

                'extensions' => (string) ($pub->getExtensions() ?? ''),
                'group_category_work_id' => (int) ($pub->getGroupCategoryWorkId() ?? 0),
                'post_group_id' => (int) ($pub->getPostGroupId() ?? 0),
            ];

            try {
                $u = $pub->getUser();
                $params['user_id'] = $u ? (int) $u->getId() : 0;
            } catch (\Throwable) {
                $params['user_id'] = 0;
            }

            if ($assignment instanceof CStudentPublicationAssignment) {
                $params['enable_qualification'] = (bool) ($assignment->getEnableQualification() ?? false);

                $expiresOn = $assignment->getExpiresOn();
                $endsOn = $assignment->getEndsOn();

                $params['expires_on'] = $expiresOn instanceof \DateTimeInterface ? $expiresOn->format('Y-m-d H:i:s') : null;
                $params['ends_on'] = $endsOn instanceof \DateTimeInterface ? $endsOn->format('Y-m-d H:i:s') : null;

                $params['add_to_calendar'] = ((int) ($assignment->getEventCalendarId() ?? 0)) > 0;
            } else {
                $params['enable_qualification'] = false;
                $params['expires_on'] = null;
                $params['ends_on'] = null;
                $params['add_to_calendar'] = false;
            }

            $work = new \stdClass();
            $work->params = $params;
            $work->destination_id = -1;

            // Key by iid = stable and unique.
            $this->course->resources[RESOURCE_WORK][$iid] = $work;
        }

        if (method_exists($this, 'dlog')) {
            $this->dlog('build_works: done', [
                'count' => count($this->course->resources[RESOURCE_WORK]),
                'copy_only_session_items' => (bool) $copyOnlySessionItems,
                'session_id' => (int) ($sessionEntity?->getId() ?? 0),
            ]);
        }
    }

    /**
     * Export Attendance + calendars.
     *
     * @param array<int> $ids
     */
    public function build_attendance(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getAttendanceRepository();
        $qb = $this->getResourcesByCourseQbFromRepo($repo, $courseEntity, $sessionEntity, $this->withBaseContent);

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
        }

        /** @var CAttendance[] $rows */
        $rows = $qb->getQuery()->getResult();

        foreach ($rows as $row) {
            $iid = (int) $row->getIid();
            $title = (string) $row->getTitle();
            $desc = (string) ($row->getDescription() ?? '');
            $active = (int) $row->getActive();

            $this->findAndSetDocumentsInText($desc);

            $params = [
                'id' => $iid,
                'title' => $title,
                'description' => $desc,
                'active' => $active,
                'attendance_qualify_title' => (string) ($row->getAttendanceQualifyTitle() ?? ''),
                'attendance_qualify_max' => (int) $row->getAttendanceQualifyMax(),
                'attendance_weight' => (float) $row->getAttendanceWeight(),
                'locked' => (int) $row->getLocked(),
                'name' => $title,
            ];

            $legacy = new Attendance($params);

            /** @var CAttendanceCalendar $cal */
            foreach ($row->getCalendars() as $cal) {
                $calArr = [
                    'id' => (int) $cal->getIid(),
                    'attendance_id' => $iid,
                    'date_time' => $cal->getDateTime()?->format('Y-m-d H:i:s') ?? '',
                    'done_attendance' => (bool) $cal->getDoneAttendance(),
                    'blocked' => (bool) $cal->getBlocked(),
                    'duration' => null !== $cal->getDuration() ? (int) $cal->getDuration() : null,
                ];
                $legacy->add_attendance_calendar($calArr);
            }

            $legacyCourse->add_resource($legacy);
        }
    }

    /**
     * Export Thematic + advances + plans (and collect linked docs).
     *
     * @param array<int> $ids
     */
    public function build_thematic(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getThematicRepository();
        $qb = $this->getResourcesByCourseQbFromRepo($repo, $courseEntity, $sessionEntity, $this->withBaseContent);

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
        }

        /** @var CThematic[] $rows */
        $rows = $qb->getQuery()->getResult();

        foreach ($rows as $row) {
            $iid = (int) $row->getIid();
            $title = (string) $row->getTitle();
            $content = (string) ($row->getContent() ?? '');
            $active = (bool) $row->getActive();

            $this->findAndSetDocumentsInText($content);

            $params = [
                'id' => $iid,
                'title' => $title,
                'content' => $content,
                'active' => $active,
            ];

            $legacy = new Thematic($params);

            /** @var CThematicAdvance $adv */
            foreach ($row->getAdvances() as $adv) {
                $attendanceId = 0;

                try {
                    $refAtt = new ReflectionProperty(CThematicAdvance::class, 'attendance');
                    if ($refAtt->isInitialized($adv)) {
                        $att = $adv->getAttendance();
                        if ($att) {
                            $attendanceId = (int) $att->getIid();
                        }
                    }
                } catch (Throwable) {
                    // keep $attendanceId = 0
                }

                $advArr = [
                    'id' => (int) $adv->getIid(),
                    'thematic_id' => (int) $row->getIid(),
                    'content' => (string) ($adv->getContent() ?? ''),
                    'start_date' => $adv->getStartDate()?->format('Y-m-d H:i:s') ?? '',
                    'duration' => (int) $adv->getDuration(),
                    'done_advance' => (bool) $adv->getDoneAdvance(),
                    'attendance_id' => $attendanceId,
                    'room_id' => (int) ($adv->getRoom()?->getId() ?? 0),
                ];

                $this->findAndSetDocumentsInText((string) $advArr['content']);
                $legacy->addThematicAdvance($advArr);
            }

            /** @var CThematicPlan $pl */
            foreach ($row->getPlans() as $pl) {
                $plArr = [
                    'id' => (int) $pl->getIid(),
                    'thematic_id' => $iid,
                    'title' => (string) $pl->getTitle(),
                    'description' => (string) ($pl->getDescription() ?? ''),
                    'description_type' => (int) $pl->getDescriptionType(),
                ];
                $this->findAndSetDocumentsInText((string) $plArr['description']);
                $legacy->addThematicPlan($plArr);
            }

            $legacyCourse->add_resource($legacy);
        }
    }

    /**
     * Export Wiki pages (content + metadata; collect docs in content).
     *
     * @param array<int> $ids
     */
    public function build_wiki(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getWikiRepository();

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $keep = $this->makeIdFilter($ids);

        // Try the generic QB (ResourceLinks-based) first
        $qb = $this->getResourcesByCourseQbFromRepo(
            $repo,
            $courseEntity,
            $sessionEntity,
            $this->withBaseContent
        );

        // Optional filter: accept ids as wiki iid OR page_id
        if (!empty($ids)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('resource.iid', ':ids'),
                    $qb->expr()->in('resource.pageId', ':ids')
                )
            )->setParameter('ids', $ids);
        }

        // Stable order: latest version first for each page_id
        $qb->addOrderBy('resource.pageId', 'ASC')
            ->addOrderBy('resource.version', 'DESC')
            ->addOrderBy('resource.iid', 'DESC');

        /** @var CWiki[] $pages */
        $pages = $qb->getQuery()->getResult();

        // if generic QB returns nothing, query by legacy columns (cId/sessionId)
        if (!$pages) {
            $cid = method_exists($courseEntity, 'getId') ? (int) $courseEntity->getId() : 0;
            $sid = $sessionEntity && method_exists($sessionEntity, 'getId') ? (int) $sessionEntity->getId() : 0;

            if ($cid > 0) {
                $qb2 = $repo->createQueryBuilder('resource')
                    ->andWhere('resource.cId = :cid')
                    ->setParameter('cid', $cid);

                if ($sid > 0) {
                    if ($this->withBaseContent) {
                        // Include session-specific + base (NULL/0)
                        $qb2->andWhere(
                            $qb2->expr()->orX(
                                'resource.sessionId = :sid',
                                'resource.sessionId IS NULL',
                                'resource.sessionId = 0'
                            )
                        )->setParameter('sid', $sid);
                    } else {
                        // Only session-specific
                        $qb2->andWhere('resource.sessionId = :sid')
                            ->setParameter('sid', $sid);
                    }
                } else {
                    // No session context => base only
                    $qb2->andWhere(
                        $qb2->expr()->orX(
                            'resource.sessionId IS NULL',
                            'resource.sessionId = 0'
                        )
                    );
                }

                if (!empty($ids)) {
                    $qb2->andWhere(
                        $qb2->expr()->orX(
                            $qb2->expr()->in('resource.iid', ':ids'),
                            $qb2->expr()->in('resource.pageId', ':ids')
                        )
                    )->setParameter('ids', $ids);
                }

                $qb2->addOrderBy('resource.pageId', 'ASC')
                    ->addOrderBy('resource.version', 'DESC')
                    ->addOrderBy('resource.iid', 'DESC');

                $pages = $qb2->getQuery()->getResult();
            }
        }

        if (!$pages) {
            return;
        }

        $selected = [];
        foreach ($pages as $page) {
            $iid = (int) $page->getIid();
            if ($iid <= 0) {
                continue;
            }

            $pageId = (int) ($page->getPageId() ?? $iid);

            // If ids provided, allow matching by iid or by page_id
            if (!empty($ids) && !$keep($iid) && !$keep($pageId)) {
                continue;
            }

            $groupId = (int) ($page->getGroupId() ?? 0);
            $reflink = (string) $page->getReflink();

            $key = $pageId.'|'.$groupId.'|'.$reflink;
            if (!isset($selected[$key])) {
                $selected[$key] = $page;
            }
        }

        foreach ($selected as $page) {
            $iid = (int) $page->getIid();
            if ($iid <= 0) {
                continue;
            }

            $pageId = (int) ($page->getPageId() ?? $iid);
            $reflink = (string) $page->getReflink();
            $title = (string) $page->getTitle();
            $content = (string) $page->getContent();
            $userId = (int) $page->getUserId();
            $groupId = (int) ($page->getGroupId() ?? 0);
            $progress = (string) ($page->getProgress() ?? '');
            $version = (int) ($page->getVersion() ?? 1);
            $dtime = $page->getDtime()?->format('Y-m-d H:i:s') ?? '';

            if ('' !== $content) {
                $this->findAndSetDocumentsInText($content);
            }

            $payload = [
                'title' => $title,
                'reflink' => $reflink,
                'content' => $content,
                'user_id' => $userId,
                'group_id' => $groupId,
                'dtime' => $dtime,
                'progress' => $progress,
                'version' => $version,
                'page_id' => $pageId,
                'name' => $title,
            ];

            $legacyCourse->resources[RESOURCE_WIKI][$iid] =
                $this->mkLegacyItem(RESOURCE_WIKI, $iid, $payload);
        }
    }

    /**
     * Export Glossary terms (collect docs in descriptions).
     *
     * @param array<int> $ids
     */
    public function build_glossary(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getGlossaryRepository();
        $qb = $this->getResourcesByCourseQbFromRepo($repo, $courseEntity, $sessionEntity, $this->withBaseContent);

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
        }

        /** @var CGlossary[] $terms */
        $terms = $qb->getQuery()->getResult();

        foreach ($terms as $term) {
            $iid = (int) $term->getIid();
            $title = (string) $term->getTitle();
            $desc = (string) ($term->getDescription() ?? '');

            $this->findAndSetDocumentsInText($desc);

            $legacy = new Glossary($iid, $title, $desc, 0);
            $this->course->add_resource($legacy);
        }
    }

    /**
     * Export Course descriptions (collect docs in HTML).
     *
     * @param array<int> $ids
     */
    public function build_course_descriptions(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getCourseDescriptionRepository();
        $qb = $this->getResourcesByCourseQbFromRepo($repo, $courseEntity, $sessionEntity, true);

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
        }

        /** @var CCourseDescription[] $rows */
        $rows = $qb->getQuery()->getResult();

        foreach ($rows as $row) {
            $iid = (int) $row->getIid();
            $title = (string) ($row->getTitle() ?? '');
            $html = (string) ($row->getContent() ?? '');
            $type = (int) $row->getDescriptionType();

            $this->findAndSetDocumentsInText($html);

            $export = new CourseDescription($iid, $title, $html, $type);
            $this->course->add_resource($export);
        }
    }

    /**
     * Export Calendar events (first attachment as legacy, all as assets).
     *
     * @param array<int> $ids
     */
    public function build_events(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $eventRepo = Container::getCalendarEventRepository();
        $qb = $this->getResourcesByCourseQbFromRepo($eventRepo, $courseEntity, $sessionEntity, $this->withBaseContent);

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
        }

        /** @var CCalendarEvent[] $events */
        $events = $qb->getQuery()->getResult();

        /** @var KernelInterface $kernel */
        $kernel = Container::$container->get('kernel');
        $projectDir = rtrim($kernel->getProjectDir(), '/');
        $resourceBase = $projectDir.'/var/upload/resource';

        /** @var ResourceNodeRepository $rnRepo */
        $rnRepo = Container::$container->get(ResourceNodeRepository::class);

        foreach ($events as $ev) {
            $iid = (int) $ev->getIid();
            $title = (string) $ev->getTitle();
            $content = (string) ($ev->getContent() ?? '');
            $startDate = $ev->getStartDate()?->format('Y-m-d H:i:s') ?? '';
            $endDate = $ev->getEndDate()?->format('Y-m-d H:i:s') ?? '';
            $allDay = (int) $ev->isAllDay();

            $firstPath = $firstName = $firstComment = '';
            $firstSize = 0;

            /** @var CCalendarEventAttachment $att */
            foreach ($ev->getAttachments() as $att) {
                $node = $att->getResourceNode();
                $abs = null;
                $size = 0;
                $relForZip = null;

                if ($node) {
                    $file = $node->getFirstResourceFile();
                    if ($file) {
                        $storedRel = (string) $rnRepo->getFilename($file);
                        if ('' !== $storedRel) {
                            $candidate = rtrim($resourceBase, '/').'/'.ltrim($storedRel, '/');
                            if (is_readable($candidate)) {
                                $abs = $candidate;
                                $size = (int) $file->getSize();
                                if ($size <= 0 && is_file($candidate)) {
                                    $st = @stat($candidate);
                                    $size = $st ? (int) $st['size'] : 0;
                                }
                                $base = basename($storedRel) ?: (string) $att->getIid();
                                $relForZip = 'upload/calendar/'.$base;
                            }
                        }
                    }
                }

                if ($abs && $relForZip) {
                    $this->tryAddAsset($relForZip, $abs, $size);
                } else {
                    $this->trace('COURSE_BUILD: event attachment file not found (event_iid='
                        .$iid.'; att_iid='.(int) $att->getIid().')');
                }

                if ('' === $firstName && $relForZip) {
                    $firstPath = substr($relForZip, \strlen('upload/calendar/'));
                    $firstName = (string) $att->getFilename();
                    $firstComment = (string) ($att->getComment() ?? '');
                    $firstSize = (int) $size;
                }
            }

            $export = new CalendarEvent(
                $iid,
                $title,
                $content,
                $startDate,
                $endDate,
                $firstPath,
                $firstName,
                $firstSize,
                $firstComment,
                $allDay
            );

            $this->course->add_resource($export);
        }
    }

    /**
     * Export Announcements (first attachment legacy, all as assets).
     *
     * @param array<int> $ids
     */
    public function build_announcements(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $annRepo = Container::getAnnouncementRepository();
        $qb = $this->getResourcesByCourseQbFromRepo($annRepo, $courseEntity, $sessionEntity, $this->withBaseContent);

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
        }

        /** @var CAnnouncement[] $anns */
        $anns = $qb->getQuery()->getResult();

        /** @var KernelInterface $kernel */
        $kernel = Container::$container->get('kernel');
        $projectDir = rtrim($kernel->getProjectDir(), '/');
        $resourceBase = $projectDir.'/var/upload/resource';

        /** @var ResourceNodeRepository $rnRepo */
        $rnRepo = Container::$container->get(ResourceNodeRepository::class);

        foreach ($anns as $a) {
            $iid = (int) $a->getIid();
            $title = (string) $a->getTitle();
            $html = (string) ($a->getContent() ?? '');
            $date = $a->getEndDate()?->format('Y-m-d H:i:s') ?? '';
            $email = (bool) $a->getEmailSent();

            $firstPath = $firstName = $firstComment = '';
            $firstSize = 0;

            $attachmentsArr = [];

            /** @var CAnnouncementAttachment $att */
            foreach ($a->getAttachments() as $att) {
                $relPath = ltrim((string) $att->getPath(), '/');
                $assetRel = 'upload/announcements/'.$relPath;

                $abs = null;
                $node = $att->getResourceNode();
                if ($node) {
                    $file = $node->getFirstResourceFile();
                    if ($file) {
                        $storedRel = (string) $rnRepo->getFilename($file);
                        if ('' !== $storedRel) {
                            $candidate = rtrim($resourceBase, '/').'/'.ltrim($storedRel, '/');
                            if (is_readable($candidate)) {
                                $abs = $candidate;
                            }
                        }
                    }
                }

                if ($abs) {
                    $this->tryAddAsset($assetRel, $abs, (int) $att->getSize());
                } else {
                    $this->trace('COURSE_BUILD: announcement attachment not found (iid='.(int) $att->getIid().')');
                }

                $attachmentsArr[] = [
                    'path' => $relPath,
                    'filename' => (string) $att->getFilename(),
                    'size' => (int) $att->getSize(),
                    'comment' => (string) ($att->getComment() ?? ''),
                    'asset_relpath' => $assetRel,
                ];

                if ('' === $firstName) {
                    $firstPath = $relPath;
                    $firstName = (string) $att->getFilename();
                    $firstSize = (int) $att->getSize();
                    $firstComment = (string) ($att->getComment() ?? '');
                }
            }

            $payload = [
                'title' => $title,
                'content' => $html,
                'date' => $date,
                'display_order' => 0,
                'email_sent' => $email ? 1 : 0,
                'attachment_path' => $firstPath,
                'attachment_filename' => $firstName,
                'attachment_size' => $firstSize,
                'attachment_comment' => $firstComment,
                'attachments' => $attachmentsArr,
            ];

            $legacyCourse->resources[RESOURCE_ANNOUNCEMENT][$iid] =
                $this->mkLegacyItem(RESOURCE_ANNOUNCEMENT, $iid, $payload, ['attachments']);
        }
    }

    /**
     * Register an asset to be packed into the export ZIP.
     */
    private function addAsset(string $relPath, string $absPath, int $size = 0): void
    {
        if (!isset($this->course->resources['asset']) || !\is_array($this->course->resources['asset'])) {
            $this->course->resources['asset'] = [];
        }
        $this->course->resources['asset'][$relPath] = [
            'abs' => $absPath,
            'size' => $size,
        ];
    }

    /**
     * Try to add an asset only if file exists.
     */
    private function tryAddAsset(string $relPath, string $absPath, int $size = 0): void
    {
        if (is_file($absPath) && is_readable($absPath)) {
            $this->addAsset($relPath, $absPath, $size);
        } else {
            $this->trace('COURSE_BUILD: asset missing: '.$absPath);
        }
    }

    /**
     * Export Surveys; returns needed Question IDs for follow-up export.
     *
     * @param array<int> $surveyIds
     *
     * @return array<int>
     */
    public function build_surveys(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $surveyIds
    ): array {
        if (!$courseEntity) {
            return [];
        }

        // Context-aware query once (course/session + visibility rules).
        $qb = $this->createContextQb(
            CSurvey::class,
            $courseEntity,
            $sessionEntity,
            's'
        );

        if (!empty($surveyIds)) {
            $ids = array_values(array_unique(array_map('intval', $surveyIds)));
            $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
            if (!empty($ids)) {
                $qb->andWhere('s.iid IN (:ids)')
                    ->setParameter('ids', $ids);
            }
        }

        /** @var CSurvey[] $surveys */
        $surveys = $qb->getQuery()->getResult();

        $this->trace(
            'COURSE_BUILD: surveys found='.\count($surveys)
            .' course_id='.(int) $courseEntity->getId()
            .' session_id='.(int) ($sessionEntity?->getId() ?? 0)
        );

        $neededQuestionIds = [];

        foreach ($surveys as $s) {
            $iid = (int) $s->getIid();
            $qIds = [];

            foreach ($s->getQuestions() as $q) {
                /** @var CSurveyQuestion $q */
                $qid = (int) $q->getIid();
                $qIds[] = $qid;
                $neededQuestionIds[$qid] = true;
            }

            $payload = [
                'code' => (string) ($s->getCode() ?? ''),
                'title' => (string) $s->getTitle(),
                'subtitle' => (string) ($s->getSubtitle() ?? ''),
                'author' => '',
                'lang' => (string) ($s->getLang() ?? ''),
                'avail_from' => $s->getAvailFrom()?->format('Y-m-d H:i:s'),
                'avail_till' => $s->getAvailTill()?->format('Y-m-d H:i:s'),
                'is_shared' => (string) ($s->getIsShared() ?? '0'),
                'template' => (string) ($s->getTemplate() ?? 'template'),
                'intro' => (string) ($s->getIntro() ?? ''),
                'surveythanks' => (string) ($s->getSurveythanks() ?? ''),
                'creation_date' => $s->getCreationDate()?->format('Y-m-d H:i:s') ?: date('Y-m-d H:i:s'),
                'invited' => (int) $s->getInvited(),
                'answered' => (int) $s->getAnswered(),
                'invite_mail' => (string) $s->getInviteMail(),
                'reminder_mail' => (string) $s->getReminderMail(),
                'mail_subject' => (string) $s->getMailSubject(),
                'anonymous' => (string) $s->getAnonymous(),
                'access_condition' => (string) ($s->getAccessCondition() ?? ''),
                'shuffle' => (bool) $s->getShuffle(),
                'one_question_per_page' => (bool) $s->getOneQuestionPerPage(),
                'survey_version' => (string) ($s->getSurveyVersion() ?? ''),
                'visible_results' => $s->getVisibleResults(),
                'is_mandatory' => (bool) $s->isMandatory(),
                'display_question_number' => (bool) $s->isDisplayQuestionNumber(),
                'survey_type' => (int) $s->getSurveyType(),
                'show_form_profile' => (int) $s->getShowFormProfile(),
                'form_fields' => (string) $s->getFormFields(),
                'duration' => $s->getDuration(),
                'question_ids' => $qIds,
                'survey_id' => $iid,
            ];

            // Collect referenced documents in survey HTML/text fields (images, attachments, etc.).
            // This prevents missing assets when restoring the survey content.
            $this->findAndSetDocumentsInText((string) ($payload['subtitle'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['intro'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['surveythanks'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['invite_mail'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['reminder_mail'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['mail_subject'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['access_condition'] ?? ''));

            $legacyCourse->resources[RESOURCE_SURVEY][$iid] =
                $this->mkLegacyItem(RESOURCE_SURVEY, $iid, $payload);

            $this->trace('COURSE_BUILD: SURVEY iid='.$iid.' qids=['.implode(',', $qIds).']');
        }

        return array_keys($neededQuestionIds);
    }

    /**
     * Export Survey Questions (answers promoted at top level).
     *
     * @param array<int> $questionIds
     */
    public function build_survey_questions(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $questionIds
    ): void {
        if (!$courseEntity) {
            return;
        }

        $qb = $this->em->createQueryBuilder()
            ->select('q', 's')
            ->from(CSurveyQuestion::class, 'q')
            ->innerJoin('q.survey', 's');

        // Apply the same context rules on the survey alias "s".
        $this->applyContextToQb(
            $qb,
            's',
            $courseEntity,
            $sessionEntity
        );

        $qb->distinct()
            ->orderBy('s.iid', 'ASC')
            ->addOrderBy('q.sort', 'ASC');

        if (!empty($questionIds)) {
            $qb->andWhere('q.iid IN (:ids)')
                ->setParameter('ids', array_map('intval', $questionIds));
        }

        /** @var CSurveyQuestion[] $questions */
        $questions = $qb->getQuery()->getResult();

        $exported = 0;

        foreach ($questions as $q) {
            $qid = (int) $q->getIid();
            $sid = (int) $q->getSurvey()->getIid();

            $answers = [];
            foreach ($q->getOptions() as $opt) {
                /** @var CSurveyQuestionOption $opt */
                $answers[] = [
                    'option_text' => (string) $opt->getOptionText(),
                    'sort' => (int) $opt->getSort(),
                    'value' => (int) $opt->getValue(),
                ];
            }

            $payload = [
                'survey_id' => $sid,
                'survey_question' => (string) $q->getSurveyQuestion(),
                'survey_question_comment' => (string) ($q->getSurveyQuestionComment() ?? ''),
                'type' => (string) $q->getType(),
                'display' => (string) $q->getDisplay(),
                'sort' => (int) $q->getSort(),
                'shared_question_id' => $q->getSharedQuestionId(),
                'max_value' => $q->getMaxValue(),
                'is_required' => (bool) $q->isMandatory(),
                'answers' => $answers,
            ];

            $legacyCourse->resources[RESOURCE_SURVEYQUESTION][$qid] =
                $this->mkLegacyItem(RESOURCE_SURVEYQUESTION, $qid, $payload, ['answers']);

            $exported++;
            $this->trace('COURSE_BUILD: SURVEY_Q qid='.$qid.' survey='.$sid.' answers='.\count($answers));
        }

        $this->trace('COURSE_BUILD: survey questions exported='.$exported);
    }

    /**
     * Export Quizzes and return required Question IDs.
     *
     * @param array<int> $quizIds
     *
     * @return array<int>
     */
    public function build_quizzes(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $quizIds
    ): array {
        if (!$courseEntity) {
            return [];
        }

        $qb = $this->createContextQb(
            CQuiz::class,
            $courseEntity,
            $sessionEntity,
            'q'
        );

        if (!empty($quizIds)) {
            $ids = array_values(array_unique(array_map('intval', $quizIds)));
            $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
            if (!empty($ids)) {
                $qb->andWhere('q.iid IN (:ids)')
                    ->setParameter('ids', $ids);
            }
        }

        /** @var CQuiz[] $quizzes */
        $quizzes = $qb->getQuery()->getResult();
        $neededQuestionIds = [];

        foreach ($quizzes as $quiz) {
            $iid = (int) $quiz->getIid();

            $payload = [
                'title' => (string) $quiz->getTitle(),
                'description' => (string) ($quiz->getDescription() ?? ''),
                'type' => (int) $quiz->getType(),
                'random' => (int) $quiz->getRandom(),
                'random_answers' => (bool) $quiz->getRandomAnswers(),
                'results_disabled' => (int) $quiz->getResultsDisabled(),
                'max_attempt' => (int) $quiz->getMaxAttempt(),
                'feedback_type' => (int) $quiz->getFeedbackType(),
                'expired_time' => (int) $quiz->getExpiredTime(),
                'review_answers' => (int) $quiz->getReviewAnswers(),
                'random_by_category' => (int) $quiz->getRandomByCategory(),
                'text_when_finished' => (string) ($quiz->getTextWhenFinished() ?? ''),
                'text_when_finished_failure' => (string) ($quiz->getTextWhenFinishedFailure() ?? ''),
                'display_category_name' => (int) $quiz->getDisplayCategoryName(),
                'save_correct_answers' => (int) ($quiz->getSaveCorrectAnswers() ?? 0),
                'propagate_neg' => (int) $quiz->getPropagateNeg(),
                'hide_question_title' => (bool) $quiz->isHideQuestionTitle(),
                'hide_question_number' => (int) $quiz->getHideQuestionNumber(),
                'question_selection_type' => (int) ($quiz->getQuestionSelectionType() ?? 0),
                'access_condition' => (string) ($quiz->getAccessCondition() ?? ''),
                'pass_percentage' => $quiz->getPassPercentage(),
                'start_time' => $quiz->getStartTime()?->format('Y-m-d H:i:s'),
                'end_time' => $quiz->getEndTime()?->format('Y-m-d H:i:s'),
                'prevent_backwards' => (int) $quiz->getPreventBackwards(),
                'show_previous_button' => (bool) $quiz->isShowPreviousButton(),
                'notifications' => (string) $quiz->getNotifications(),
                'autolaunch' => (bool) ($quiz->getAutoLaunch() ?? false),
                'hide_attempts_table' => (bool) $quiz->isHideAttemptsTable(),
                'page_result_configuration' => (array) $quiz->getPageResultConfiguration(),
                'display_chart_degree_certainty' => (int) $quiz->getDisplayChartDegreeCertainty(),
                'send_email_chart_degree_certainty' => (int) $quiz->getSendEmailChartDegreeCertainty(),
                'not_display_balance_percentage_categorie_question' => (int) $quiz->getNotDisplayBalancePercentageCategorieQuestion(),
                'display_chart_degree_certainty_category' => (int) $quiz->getDisplayChartDegreeCertaintyCategory(),
                'gather_questions_categories' => (int) $quiz->getGatherQuestionsCategories(),
                'duration' => $quiz->getDuration(),

                'question_ids' => [],
                'question_orders' => [],
            ];

            // Collect referenced documents in quiz HTML fields.
            $this->findAndSetDocumentsInText((string) ($payload['description'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['text_when_finished'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['text_when_finished_failure'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['access_condition'] ?? ''));

            $rels = $this->em->createQueryBuilder()
                ->select('rel', 'qq')
                ->from(CQuizRelQuestion::class, 'rel')
                ->innerJoin('rel.question', 'qq')
                ->andWhere('rel.quiz = :quiz')
                ->setParameter('quiz', $quiz)
                ->orderBy('rel.questionOrder', 'ASC')
                ->getQuery()
                ->getResult()
            ;

            foreach ($rels as $rel) {
                /** @var CQuizRelQuestion $rel */
                $qid = (int) $rel->getQuestion()->getIid();
                $payload['question_ids'][] = $qid;
                $payload['question_orders'][] = (int) $rel->getQuestionOrder();
                $neededQuestionIds[$qid] = true;
            }

            $legacyCourse->resources[RESOURCE_QUIZ][$iid] = $this->mkLegacyItem(
                RESOURCE_QUIZ,
                $iid,
                $payload,
                ['question_ids', 'question_orders']
            );
        }

        $this->trace('COURSE_BUILD: build_quizzes done; total='.\count($quizzes));

        return array_keys($neededQuestionIds);
    }

    /**
     * Export Quiz Questions (answers and options promoted).
     *
     * @param array<int> $questionIds
     */
    public function build_quiz_questions(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $questionIds
    ): void {
        if (!$courseEntity) {
            return;
        }

        $questionIds = array_values(array_unique(array_map('intval', $questionIds)));
        $questionIds = array_values(array_filter($questionIds, static fn (int $id): bool => $id > 0));

        if (empty($questionIds)) {
            $this->trace('COURSE_BUILD: build_quiz_questions called with empty questionIds.');
            return;
        }

        $qb = $this->createContextQb(
            CQuizQuestion::class,
            $courseEntity,
            $sessionEntity,
            'qq'
        );

        $qb->andWhere('qq.iid IN (:ids)')
            ->setParameter('ids', $questionIds);

        /** @var CQuizQuestion[] $questions */
        $questions = $qb->getQuery()->getResult();

        // if some IDs were filtered out by context, fetch them by PK.
        $found = [];
        foreach ($questions as $q) {
            $found[(int) $q->getIid()] = true;
        }

        $missing = [];
        foreach ($questionIds as $qid) {
            if (!isset($found[$qid])) {
                $missing[] = $qid;
            }
        }

        if (!empty($missing)) {
            $this->trace('COURSE_BUILD: build_quiz_questions missing_ids='.implode(',', $missing).' (fallback fetch).');

            $more = $this->em->createQueryBuilder()
                ->select('qq')
                ->from(CQuizQuestion::class, 'qq')
                ->andWhere('qq.iid IN (:ids)')
                ->setParameter('ids', $missing)
                ->getQuery()
                ->getResult()
            ;

            foreach ($more as $q) {
                $questions[] = $q;
            }
        }

        $this->exportQuestionsWithAnswers($legacyCourse, $questions);
    }

    /**
     * Internal exporter for quiz questions + answers (+ options for MATF type).
     *
     * @param array<int,CQuizQuestion> $questions
     */
    private function exportQuestionsWithAnswers(object $legacyCourse, array $questions): void
    {
        foreach ($questions as $q) {
            $qid = (int) $q->getIid();

            $payload = [
                'question' => (string) $q->getQuestion(),
                'description' => (string) ($q->getDescription() ?? ''),
                'ponderation' => (float) $q->getPonderation(),
                'position' => (int) $q->getPosition(),
                'type' => (int) $q->getType(),
                'quiz_type' => (int) $q->getType(),
                'picture' => (string) ($q->getPicture() ?? ''),
                'level' => (int) $q->getLevel(),
                'extra' => (string) ($q->getExtra() ?? ''),
                'feedback' => (string) ($q->getFeedback() ?? ''),
                'question_code' => (string) ($q->getQuestionCode() ?? ''),
                'mandatory' => (int) $q->getMandatory(),
                'duration' => $q->getDuration(),
                'parent_media_id' => $q->getParentMediaId(),
                'answers' => [],
                'question_options' => [],
            ];

            // Collect referenced documents in question HTML fields.
            $this->findAndSetDocumentsInText((string) ($payload['question'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['description'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['feedback'] ?? ''));
            $this->findAndSetDocumentsInText((string) ($payload['extra'] ?? ''));

            $ans = $this->em->createQueryBuilder()
                ->select('a')
                ->from(CQuizAnswer::class, 'a')
                ->andWhere('a.question = :q')->setParameter('q', $q)
                ->orderBy('a.position', 'ASC')
                ->getQuery()->getResult()
            ;

            foreach ($ans as $a) {
                /** @var CQuizAnswer $a */
                $row = [
                    'id' => (int) $a->getIid(),
                    'answer' => (string) $a->getAnswer(),
                    'comment' => (string) ($a->getComment() ?? ''),
                    'ponderation' => (float) $a->getPonderation(),
                    'position' => (int) $a->getPosition(),
                    'hotspot_coordinates' => $a->getHotspotCoordinates(),
                    'hotspot_type' => $a->getHotspotType(),
                    'correct' => $a->getCorrect(),
                ];

                // Collect referenced documents in answers too.
                $this->findAndSetDocumentsInText((string) ($row['answer'] ?? ''));
                $this->findAndSetDocumentsInText((string) ($row['comment'] ?? ''));

                $payload['answers'][] = $row;
            }

            if (\defined('MULTIPLE_ANSWER_TRUE_FALSE') && MULTIPLE_ANSWER_TRUE_FALSE === (int) $q->getType()) {
                $opts = $this->em->createQueryBuilder()
                    ->select('o')
                    ->from(CQuizQuestionOption::class, 'o')
                    ->andWhere('o.question = :q')->setParameter('q', $q)
                    ->orderBy('o.position', 'ASC')
                    ->getQuery()->getResult()
                ;

                $payload['question_options'] = array_map(static function ($o): array {
                    /** @var CQuizQuestionOption $o */
                    return [
                        'id' => (int) $o->getIid(),
                        'name' => (string) $o->getTitle(),
                        'position' => (int) $o->getPosition(),
                    ];
                }, $opts);
            }

            $legacyCourse->resources[RESOURCE_QUIZQUESTION][$qid] = $this->mkLegacyItem(
                RESOURCE_QUIZQUESTION,
                $qid,
                $payload,
                ['answers', 'question_options']
            );

            $this->trace('COURSE_BUILD: QQ exported qid='.$qid.' answers='.\count($payload['answers']));
        }
    }

    /**
     * Safe count helper for mixed values.
     */
    private function safeCount(mixed $v): int
    {
        return (\is_array($v) || $v instanceof Countable) ? \count($v) : 0;
    }

    /**
     * Export Link category as legacy item.
     */
    public function build_link_category(CLinkCategory $category): void
    {
        $id = (int) $category->getIid();
        if ($id <= 0) {
            return;
        }

        $payload = [
            'title' => (string) $category->getTitle(),
            'description' => (string) ($category->getDescription() ?? ''),
            'category_title' => (string) $category->getTitle(),
        ];

        $this->course->resources[RESOURCE_LINKCATEGORY][$id] =
            $this->mkLegacyItem(RESOURCE_LINKCATEGORY, $id, $payload);
    }

    /**
     * Export Links (and their categories once).
     *
     * @param array<int> $ids
     */
    public function build_links(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $qb = $this->createContextQb(
            CLink::class,
            $courseEntity,
            $sessionEntity,
            'l'
        );

        if (!empty($ids)) {
            $qb->andWhere('l.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))));
        }

        /** @var CLink[] $links */
        $links = $qb->getQuery()->getResult();

        $exportedCats = [];

        foreach ($links as $link) {
            $iid = (int) $link->getIid();
            $title = (string) $link->getTitle();
            $url = (string) $link->getUrl();
            $desc = (string) ($link->getDescription() ?? '');
            $tgt = (string) ($link->getTarget() ?? '');

            $cat = $link->getCategory();
            $catId = (int) ($cat?->getIid() ?? 0);

            if ($catId > 0 && !isset($exportedCats[$catId])) {
                $this->build_link_category($cat);
                $exportedCats[$catId] = true;
            }

            $payload = [
                'title' => '' !== $title ? $title : $url,
                'url' => $url,
                'description' => $desc,
                'target' => $tgt,
                'category_id' => $catId,
                'on_homepage' => false,
            ];

            $legacyCourse->resources[RESOURCE_LINK][$iid] =
                $this->mkLegacyItem(RESOURCE_LINK, $iid, $payload);
        }
    }

    /**
     * Format DateTime as string "Y-m-d H:i:s".
     */
    private function fmtDate(?DateTimeInterface $dt): string
    {
        return $dt ? $dt->format('Y-m-d H:i:s') : '';
    }

    /**
     * Create a legacy item object, promoting selected array keys to top-level.
     *
     * @param array<int,string> $arrayKeysToPromote
     */
    private function mkLegacyItem(string $type, int $sourceId, array|object $obj, array $arrayKeysToPromote = []): stdClass
    {
        $o = new stdClass();
        $o->type = $type;
        $o->source_id = $sourceId;
        $o->destination_id = -1;
        $o->has_obj = true;
        $o->obj = (object) $obj;

        if (!isset($o->obj->iid)) {
            $o->obj->iid = $sourceId;
        }
        if (!isset($o->id)) {
            $o->id = $sourceId;
        }
        if (!isset($o->obj->id)) {
            $o->obj->id = $sourceId;
        }

        foreach ((array) $obj as $k => $v) {
            if (\is_scalar($v) || null === $v) {
                if (!property_exists($o, $k)) {
                    $o->{$k} = $v;
                }
            }
        }

        $objArr = (array) $obj;
        foreach ($arrayKeysToPromote as $k) {
            if (isset($objArr[$k]) && \is_array($objArr[$k])) {
                $o->{$k} = $objArr[$k];
            }
        }

        if (RESOURCE_DOCUMENT === $type) {
            $o->path = (string) ($o->path ?? $o->full_path ?? $o->obj->path ?? $o->obj->full_path ?? '');
            $o->full_path = (string) ($o->full_path ?? $o->path ?? $o->obj->full_path ?? $o->obj->path ?? '');
            $o->file_type = (string) ($o->file_type ?? $o->filetype ?? $o->obj->file_type ?? $o->obj->filetype ?? '');
            $o->filetype = (string) ($o->filetype ?? $o->file_type ?? $o->obj->filetype ?? $o->obj->file_type ?? '');
            $o->title = (string) ($o->title ?? $o->obj->title ?? '');
            if (!isset($o->name) || '' === $o->name || null === $o->name) {
                $o->name = '' !== $o->title ? $o->title : ('document '.$sourceId);
            }
        }

        if (RESOURCE_SURVEYQUESTION === $type) {
            if (!isset($o->survey_question_type) && isset($o->type)) {
                $o->survey_question_type = $o->type;
            }
            if (!isset($o->type) && isset($o->survey_question_type)) {
                $o->type = $o->survey_question_type;
            }

            if (isset($o->obj) && \is_object($o->obj)) {
                if (!isset($o->obj->survey_question_type) && isset($o->obj->type)) {
                    $o->obj->survey_question_type = $o->obj->type;
                }
                if (!isset($o->obj->type) && isset($o->obj->survey_question_type)) {
                    $o->obj->type = $o->obj->survey_question_type;
                }
            }
        }

        if (!isset($o->name) || '' === $o->name || null === $o->name) {
            if (isset($objArr['name']) && '' !== (string) $objArr['name']) {
                $o->name = (string) $objArr['name'];
            } elseif (isset($objArr['title']) && '' !== (string) $objArr['title']) {
                $o->name = (string) $objArr['title'];
            } else {
                $o->name = $type.' '.$sourceId;
            }
        }

        return $o;
    }

    /**
     * Build an id filter closure.
     *
     * @param array<int> $idsFilter
     *
     * @return Closure(int):bool
     */
    private function makeIdFilter(array $idsFilter): Closure
    {
        if (empty($idsFilter)) {
            return static fn (int $id): bool => true;
        }
        $set = array_fill_keys(array_map('intval', $idsFilter), true);

        return static fn (int $id): bool => isset($set[$id]);
    }

    /**
     * Export Tool intro only for the course_homepage tool.
     * Prefers the session-specific intro when both (session and base) exist.
     */
    public function build_tool_intro(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $toolKey = 'course_homepage';

        // Bucket key: be defensive
        $bagKey = \defined('RESOURCE_TOOL_INTRO') ? RESOURCE_TOOL_INTRO : 'tool_intro';
        $qb = $this->createContextQb(
            CToolIntro::class,
            $courseEntity,
            $sessionEntity,
            'ti'
        );

        $qb->innerJoin('ti.courseTool', 'ct')
            ->andWhere('ct.course = :course')
            ->andWhere('ct.title = :title')
            ->setParameter('course', $courseEntity)
            ->setParameter('title', $toolKey);

        if ($sessionEntity) {
            $qb->andWhere('(ct.session = :session OR ct.session IS NULL)')
                ->setParameter('session', $sessionEntity);

            // prefer session-specific tool intros first
            $qb->addOrderBy('CASE WHEN ct.session IS NULL THEN 0 ELSE 1 END', 'DESC');
        } else {
            $qb->andWhere('ct.session IS NULL');
        }

        // Prefer newest intro (avoid picking an old/base one by accident)
        $qb->addOrderBy('ti.iid', 'DESC');

        /** @var CToolIntro[] $rows */
        $rows = $qb->getQuery()->getResult();

        // if context QB returns nothing (e.g. missing ResourceLinks),
        // query only by course+tool+session without relying on ResourceLinks.
        if (!$rows) {
            $qb2 = $this->em->createQueryBuilder();
            $qb2->select('ti')
                ->from(CToolIntro::class, 'ti')
                ->innerJoin('ti.courseTool', 'ct')
                ->andWhere('ct.course = :course')
                ->andWhere('ct.title = :title')
                ->setParameter('course', $courseEntity)
                ->setParameter('title', $toolKey);

            if ($sessionEntity) {
                $qb2->andWhere('(ct.session = :session OR ct.session IS NULL)')
                    ->setParameter('session', $sessionEntity);

                $qb2->addOrderBy('CASE WHEN ct.session IS NULL THEN 0 ELSE 1 END', 'DESC');
            } else {
                $qb2->andWhere('ct.session IS NULL');
            }

            $qb2->addOrderBy('ti.iid', 'DESC');

            /** @var CToolIntro[] $rows2 */
            $rows2 = $qb2->getQuery()->getResult();
            $rows = $rows2 ?: [];
        }

        if (!$rows) {
            return;
        }

        $selected = $rows[0];

        $payload = [
            'id' => $toolKey, // keep stable key
            'intro_text' => (string) $selected->getIntroText(),
        ];

        $legacyCourse->resources[$bagKey][$toolKey] =
            $this->mkLegacyItem($bagKey, 0, $payload);
    }

    /**
     * Export Forum categories.
     *
     * @param array<int> $ids
     */
    public function build_forum_category(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getForumCategoryRepository();
        $qb = $this->getResourcesByCourseQbFromRepo($repo, $courseEntity, $sessionEntity, $this->withBaseContent);

        /** @var CForumCategory[] $categories */
        $categories = $qb->getQuery()->getResult();

        $keep = $this->makeIdFilter($ids);

        foreach ($categories as $cat) {
            $id = (int) $cat->getIid();
            if (!$keep($id)) {
                continue;
            }

            $payload = [
                'title' => (string) $cat->getTitle(),
                'description' => (string) ($cat->getCatComment() ?? ''),
                'cat_title' => (string) $cat->getTitle(),
                'cat_comment' => (string) ($cat->getCatComment() ?? ''),
            ];

            $legacyCourse->resources[RESOURCE_FORUMCATEGORY][$id] =
                $this->mkLegacyItem(RESOURCE_FORUMCATEGORY, $id, $payload);
        }
    }

    /**
     * Export Forums.
     *
     * @param array<int> $ids
     */
    public function build_forums(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $qb = $this->createContextQb(
            CForum::class,
            $courseEntity,
            $sessionEntity,
            'f'
        );

        if (!empty($ids)) {
            $qb->andWhere('f.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))));
        }

        /** @var CForum[] $forums */
        $forums = $qb->getQuery()->getResult();

        $keep = $this->makeIdFilter($ids);

        foreach ($forums as $f) {
            $id = (int) $f->getIid();
            if (!$keep($id)) {
                continue;
            }

            $payload = [
                'title' => (string) $f->getTitle(),
                'description' => (string) ($f->getForumComment() ?? ''),
                'forum_title' => (string) $f->getTitle(),
                'forum_comment' => (string) ($f->getForumComment() ?? ''),
                'forum_category' => (int) ($f->getForumCategory()?->getIid() ?? 0),
                'allow_anonymous' => (int) ($f->getAllowAnonymous() ?? 0),
                'allow_edit' => (int) ($f->getAllowEdit() ?? 0),
                'approval_direct_post' => (string) ($f->getApprovalDirectPost() ?? '0'),
                'allow_attachments' => (int) ($f->getAllowAttachments() ?? 1),
                'allow_new_threads' => (int) ($f->getAllowNewThreads() ?? 1),
                'default_view' => (string) ($f->getDefaultView() ?? 'flat'),
                'forum_of_group' => (string) ($f->getForumOfGroup() ?? '0'),
                'forum_group_public_private' => (string) ($f->getForumGroupPublicPrivate() ?? 'public'),
                'moderated' => (int) ($f->isModerated() ? 1 : 0),
                'start_time' => $this->fmtDate($f->getStartTime()),
                'end_time' => $this->fmtDate($f->getEndTime()),
            ];

            $legacyCourse->resources[RESOURCE_FORUM][$id] =
                $this->mkLegacyItem(RESOURCE_FORUM, $id, $payload);
        }
    }

    /**
     * Export Forum threads.
     *
     * @param array<int> $ids
     */
    public function build_forum_topics(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getForumThreadRepository();
        $qb = $this->getResourcesByCourseQbFromRepo($repo, $courseEntity, $sessionEntity, $this->withBaseContent);

        // Determine root alias used by repository QB.
        $rootAliases = $qb->getRootAliases();
        $rootAlias = $rootAliases[0] ?? 'resource';

        // Optional filter by thread IDs.
        if (!empty($ids)) {
            $ids = array_values(array_unique(array_map('intval', $ids)));
            $qb->andWhere($qb->expr()->in($rootAlias.'.iid', ':ids'))
                ->setParameter('ids', $ids);
        }

        // Stable order for restore/debug.
        $qb->addOrderBy($rootAlias.'.iid', 'ASC');

        /** @var CForumThread[] $threads */
        $threads = $qb->getQuery()->getResult();
        if (!$threads) {
            return;
        }

        foreach ($threads as $t) {
            $id = (int) $t->getIid();
            if ($id <= 0) {
                continue;
            }

            $payload = [
                'title' => (string) $t->getTitle(),
                'thread_title' => (string) $t->getTitle(),
                'title_qualify' => (string) ($t->getThreadTitleQualify() ?? ''),
                'topic_poster_name' => (string) ($t->getUser()?->getUsername() ?? ''),
                'forum_id' => (int) ($t->getForum()?->getIid() ?? 0),
                'thread_date' => $this->fmtDate($t->getThreadDate()),
                'thread_sticky' => (int) ($t->getThreadSticky() ? 1 : 0),
                'thread_title_qualify' => (string) ($t->getThreadTitleQualify() ?? ''),
                'thread_qualify_max' => (float) $t->getThreadQualifyMax(),
                'thread_weight' => (float) $t->getThreadWeight(),
                'thread_peer_qualify' => (int) ($t->isThreadPeerQualify() ? 1 : 0),
            ];

            $legacyCourse->resources[RESOURCE_FORUMTOPIC][$id] =
                $this->mkLegacyItem(RESOURCE_FORUMTOPIC, $id, $payload);
        }

        $this->trace('COURSE_BUILD: forum threads exported='.count($threads));
    }

    /**
     * Export first post for each thread as topic root post.
     *
     * @param array<int> $ids
     */
    public function build_forum_posts(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repoPost = Container::getForumPostRepository();
        $qb = $this->getResourcesByCourseQbFromRepo($repoPost, $courseEntity, $sessionEntity, $this->withBaseContent);

        // Determine the root alias used by the repository QB (usually "resource").
        $rootAliases = $qb->getRootAliases();
        $rootAlias = $rootAliases[0] ?? 'resource';

        $threadAlias = $rootAlias.'Thread';
        $forumAlias = $rootAlias.'Forum';

        // Ensure joins for thread/forum exist so we can filter and export IDs safely.
        if (!$this->hasJoinAlias($qb, $threadAlias)) {
            $qb->innerJoin($rootAlias.'.thread', $threadAlias);
            $qb->addSelect($threadAlias);
        }
        if (!$this->hasJoinAlias($qb, $forumAlias)) {
            $qb->leftJoin($threadAlias.'.forum', $forumAlias);
            $qb->addSelect($forumAlias);
        }

        // Optional filter: accept ids as post ids OR thread ids OR forum ids.
        if (!empty($ids)) {
            $ids = array_values(array_unique(array_map('intval', $ids)));

            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in($rootAlias.'.iid', ':ids'),
                    $qb->expr()->in($threadAlias.'.iid', ':ids'),
                    $qb->expr()->in($forumAlias.'.iid', ':ids')
                )
            )->setParameter('ids', $ids);
        }

        // Stable order for restore/debug.
        $qb->addOrderBy($threadAlias.'.iid', 'ASC')
            ->addOrderBy($rootAlias.'.postDate', 'ASC')
            ->addOrderBy($rootAlias.'.iid', 'ASC');

        /** @var CForumPost[] $posts */
        $posts = $qb->getQuery()->getResult();
        if (!$posts) {
            return;
        }

        $exported = 0;

        foreach ($posts as $p) {
            if (!$p instanceof CForumPost) {
                continue;
            }

            $postId = (int) $p->getIid();
            if ($postId <= 0) {
                continue;
            }

            $thread = $p->getThread();
            $threadId = (int) ($thread?->getIid() ?? 0);
            $forumId = (int) ($thread?->getForum()?->getIid() ?? 0);

            $postText = (string) ($p->getPostText() ?? '');
            $titleFromPost = $this->resolveForumPostTitle($p, $postText);

            // Detect documents linked in post text (images, attachments, etc.).
            if ('' !== $postText) {
                $this->findAndSetDocumentsInText($postText);
            }

            $user = $p->getUser();
            $posterId = (int) ($user?->getId() ?? 0);
            $posterName = (string) ($user?->getUsername() ?? '');

            $parentId = 0;
            $parent = $p->getPostParent();
            if ($parent) {
                $parentId = (int) ($parent->getIid() ?? 0);
            }

            $status = $p->getStatus();
            if (null === $status || '' === (string) $status) {
                $status = CForumPost::STATUS_VALIDATED;
            }

            $payload = [
                'title' => $titleFromPost,
                'post_title' => $titleFromPost,
                'post_text' => $postText,
                'thread_id' => $threadId,
                'forum_id' => $forumId,
                'post_notification' => (int) ($p->getPostNotification() ? 1 : 0),
                'visible' => (int) ($p->getVisible() ? 1 : 0),
                'status' => (int) $status,
                'post_parent_id' => $parentId,
                'poster_id' => $posterId,
                'text' => $postText,
                'poster_name' => $posterName,
                'post_date' => $this->fmtDate($p->getPostDate()),
            ];

            $legacyCourse->resources[RESOURCE_FORUMPOST][$postId] =
                $this->mkLegacyItem(RESOURCE_FORUMPOST, $postId, $payload);

            $exported++;
        }

        $this->trace('COURSE_BUILD: forum posts exported='.$exported);
    }

    /**
     * Resolve a safe forum post title.
     */
    private function resolveForumPostTitle(CForumPost $post, string $postText = ''): string
    {
        $title = trim((string) ($post->getTitle() ?? ''));
        if ('' !== $title) {
            return $title;
        }

        $plain = trim(strip_tags($postText));
        if ('' === $plain) {
            return 'Post';
        }

        return (string) mb_substr($plain, 0, 60);
    }

    /**
     * New Chamilo 2 build: CDocumentRepository-based (instead of legacy tables).
     *
     * @param array<int> $idList
     */
    private function build_documents_with_repo(
        ?CourseEntity $course,
        ?SessionEntity $session,
        bool $withBaseContent,
        array $idList = []
    ): void {
        if (!$course instanceof CourseEntity) {
            return;
        }

        $qb = $this->getResourcesByCourseQbFromRepo($this->docRepo, $course, $session, $withBaseContent);

        if (!empty($idList)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $idList))));
        }

        /** @var CDocument[] $docs */
        $docs = $qb->getQuery()->getResult();

        $documentsRoot = $this->docRepo->getCourseDocumentsRootNode($course);

        foreach ($docs as $doc) {
            $node     = $doc->getResourceNode();
            $filetype = $doc->getFiletype(); // 'file' | 'folder' | ...
            $title    = $doc->getTitle();
            $comment  = $doc->getComment() ?? '';
            $iid      = (int) $doc->getIid();

            $size = 0;
            if ('folder' === $filetype) {
                $size = $this->docRepo->getFolderSize($node, $course, $session);
            } else {
                $files = $node?->getResourceFiles();
                if ($files && $files->count() > 0) {
                    /** @var ResourceFile $first */
                    $first = $files->first();
                    $size  = (int) $first->getSize();
                }
            }

            $rel = '';
            if ($node instanceof ResourceNode) {
                if ($documentsRoot instanceof ResourceNode) {
                    $rel = (string) $node->getPathForDisplayRemoveBase((string) $documentsRoot->getPath());
                } else {
                    $rel = (string) $node->convertPathForDisplay((string) $node->getPath());
                    $rel = preg_replace('~^/?Documents/?~i', '', (string) $rel) ?? $rel;
                }
            }

            $rel = trim((string) $rel, '/');

            $pathForSelector = 'document'.($rel !== '' ? '/'.$rel : '');
            if ('folder' === $filetype) {
                $pathForSelector = rtrim($pathForSelector, '/').'/';
            }

            $exportDoc = new Document(
                $iid,
                $pathForSelector,
                $comment,
                $title,
                $filetype,
                (string) $size
            );

            $this->course->add_resource($exportDoc);
        }
    }

    /**
     * Backward-compatible wrapper for build_documents_with_repo().
     *
     * @param array<int> $idList
     */
    public function build_documents(
        int $session_id = 0,
        int $courseId = 0,
        bool $withBaseContent = false,
        array $idList = []
    ): void {
        /** @var CourseEntity|null $course */
        $course = $this->em->getRepository(CourseEntity::class)->find($courseId);

        /** @var SessionEntity|null $session */
        $session = $session_id ? $this->em->getRepository(SessionEntity::class)->find($session_id) : null;

        // If we are exporting for a session and the caller did NOT request base content,
        // enable it to prevent empty "document" bucket in typical course-copy flows.
        $effectiveWithBaseContent = $withBaseContent;
        if ($session_id > 0 && !$withBaseContent && empty($idList)) {
            $effectiveWithBaseContent = true;
        }

        $this->trace('COURSE_BUILD: build_documents params '.json_encode([
                'course_id' => (int) $courseId,
                'session_id' => (int) $session_id,
                'withBaseContent' => (int) $withBaseContent,
                'effectiveWithBaseContent' => (int) $effectiveWithBaseContent,
                'idListCount' => count($idList),
                'courseFound' => (int) ($course instanceof CourseEntity),
                'sessionFound' => (int) ($session instanceof SessionEntity),
            ]));

        $this->build_documents_with_repo($course, $session, $effectiveWithBaseContent, $idList);
    }

    /**
     * Create a QueryBuilder pre-configured with course/session visibility constraints
     * based on ResourceNode/ResourceLink associations.
     *
     * @param class-string $fromClass
     */
    private function createContextQb(
        string $fromClass,
        CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        string $alias = 'resource'
    ): QueryBuilder {
        $qb = $this->em->createQueryBuilder()
            ->select($alias)
            ->from($fromClass, $alias);

        $this->applyContextToQb($qb, $alias, $courseEntity, $sessionEntity);

        $qb->distinct();

        return $qb;
    }

    /**
     * Apply course/session constraints on an existing QueryBuilder.
     *
     * The method ensures the required joins exist with predictable aliases:
     *  - "{$rootAlias}Node"  for ResourceNode
     *  - "{$rootAlias}Links" for ResourceLink
     *
     * It returns the ResourceLink alias so callers can reference it (ORDER BY, etc).
     */
    private function applyContextToQb(
        QueryBuilder $qb,
        string $rootAlias,
        CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity
    ): string {
        $nodeAlias = $this->getContextNodeAlias($rootAlias);
        $linksAlias = $this->getContextLinksAlias($rootAlias);

        // Ensure ResourceNode join exists.
        if (!$this->hasJoinAlias($qb, $nodeAlias)) {
            $qb->innerJoin($rootAlias.'.resourceNode', $nodeAlias);
        }

        // Ensure ResourceLinks join exists.
        if (!$this->hasJoinAlias($qb, $linksAlias)) {
            $qb->innerJoin($nodeAlias.'.resourceLinks', $linksAlias);
        }

        // Base constraints.
        $qb
            ->andWhere($linksAlias.'.course = :course')
            ->setParameter('course', $courseEntity)
            ->andWhere($linksAlias.'.deletedAt IS NULL')
            ->andWhere($linksAlias.'.endVisibilityAt IS NULL')
        ;

        // Group constraint (safe for association or scalar).
        $this->addNoGroupConstraint($qb, $linksAlias);

        // Session constraint (safe for association or scalar, and still includes "0" rows via LEFT JOIN behavior).
        $this->addSessionConstraint($qb, $linksAlias, $sessionEntity, $this->withBaseContent);

        return $linksAlias;
    }

    /**
     * Add session constraint without breaking when ResourceLink::session is an association.
     *
     * Why:
     * - DQL like "links.session = 0" can break if "session" is mapped as an association.
     * - Some DB rows may still contain FK=0 after old migrations; using LEFT JOIN + IS NULL on joined id
     *   will also include those "0" rows (because the join won't match any Session row).
     */
    private function addSessionConstraint(
        QueryBuilder $qb,
        string $linksAlias,
        ?SessionEntity $sessionEntity,
        bool $withBaseContent
    ): void {
        try {
            $meta = $this->em->getClassMetadata(ResourceLink::class);

            if ($meta->hasAssociation('session')) {
                $sessionAlias = $linksAlias.'Session';

                if (!$this->hasJoinAlias($qb, $sessionAlias)) {
                    $qb->leftJoin($linksAlias.'.session', $sessionAlias);
                }

                $targetClass = $meta->getAssociationTargetClass('session');
                $targetMeta = $this->em->getClassMetadata($targetClass);
                $idField = $targetMeta->getSingleIdentifierFieldName();

                if (null !== $sessionEntity) {
                    $qb->setParameter('sessionId', (int) $sessionEntity->getId());

                    if ($withBaseContent) {
                        // Base + session: include NULL/0 rows via LEFT JOIN producing NULL joined id
                        $qb->andWhere(
                            $qb->expr()->orX(
                                $qb->expr()->isNull($sessionAlias.'.'.$idField),
                                $qb->expr()->eq($sessionAlias.'.'.$idField, ':sessionId')
                            )
                        );
                    } else {
                        // Session-only
                        $qb->andWhere($qb->expr()->eq($sessionAlias.'.'.$idField, ':sessionId'));
                    }
                } else {
                    // Base-only (includes legacy FK=0 rows because LEFT JOIN won't match => NULL joined id)
                    $qb->andWhere($qb->expr()->isNull($sessionAlias.'.'.$idField));
                }

                return;
            }
        } catch (Throwable) {
            // Fall back to scalar legacy behavior below.
        }

        // Scalar mapping fallback (legacy):
        if (null !== $sessionEntity) {
            $qb->setParameter('session', $sessionEntity);

            if ($withBaseContent) {
                $qb->andWhere('('
                    .$linksAlias.'.session IS NULL OR '
                    .$linksAlias.'.session = 0 OR '
                    .$linksAlias.'.session = :session'
                    .')');
            } else {
                $qb->andWhere($linksAlias.'.session = :session');
            }
        } else {
            $qb->andWhere('('
                .$linksAlias.'.session IS NULL OR '
                .$linksAlias.'.session = 0'
                .')');
        }
    }

    private function getContextNodeAlias(string $rootAlias): string
    {
        return $rootAlias.'Node';
    }

    private function getContextLinksAlias(string $rootAlias): string
    {
        return $rootAlias.'Links';
    }

    /**
     * Detect whether a QB already contains a join alias.
     */
    private function hasJoinAlias(QueryBuilder $qb, string $alias): bool
    {
        $joins = $qb->getDQLPart('join');
        if (!\is_array($joins)) {
            return false;
        }

        foreach ($joins as $group) {
            if (!\is_array($group)) {
                continue;
            }
            foreach ($group as $j) {
                if (method_exists($j, 'getAlias') && $j->getAlias() === $alias) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add "no group" constraint without breaking when ResourceLink::group is an inverse-side association.
     */
    private function addNoGroupConstraint(QueryBuilder $qb, string $linksAlias): void
    {
        try {
            $meta = $this->em->getClassMetadata(ResourceLink::class);
            if ($meta->hasAssociation('group')) {
                // Always LEFT JOIN and check the joined identifier instead of "links.group IS NULL"
                // because inverse-side associations cannot be used in NULL comparisons.
                $groupAlias = $linksAlias.'Group';

                if (!$this->hasJoinAlias($qb, $groupAlias)) {
                    $qb->leftJoin($linksAlias.'.group', $groupAlias);
                }

                $targetClass = $meta->getAssociationTargetClass('group');
                $targetMeta = $this->em->getClassMetadata($targetClass);

                $idField = $targetMeta->getSingleIdentifierFieldName(); // e.g. "id" / "iid"
                $qb->andWhere($qb->expr()->isNull($groupAlias.'.'.$idField));

                return;
            }
        } catch (Throwable) {
            // Fall back to scalar legacy behavior below.
        }

        // Scalar mapping (or unknown): keep legacy behavior.
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull($linksAlias.'.group'),
                $qb->expr()->eq($linksAlias.'.group', 0)
            )
        );
    }

    /**
     * Build a QueryBuilder from a repository implementing getResourcesByCourse(),
     * passing $withBaseContent
     */
    private function getResourcesByCourseQbFromRepo(
        object $repo,
        CourseEntity $course,
        ?SessionEntity $session,
        bool $withBaseContent
    ): QueryBuilder {
        if (method_exists($repo, 'getResourcesByCourse')) {
            try {
                $rm = new \ReflectionMethod($repo, 'getResourcesByCourse');
                $argc = $rm->getNumberOfParameters();

                // If repo supports the override parameter, use it (no repo changes needed).
                if ($argc >= 7) {
                    $qb = $rm->invoke(
                        $repo,
                        $course,
                        $session,
                        null,   // group
                        null,   // parentNode
                        false,  // displayOnlyPublished (force false for course copy)
                        false,  // displayOrder
                        $withBaseContent // withBaseContentOverride
                    );

                    if ($qb instanceof QueryBuilder) {
                        return $qb;
                    }
                }

                // If repo does not support the override, we fallback below.
            } catch (\Throwable) {
                // Fallback below.
            }
        }

        // build a context QB locally (builder-only behavior).
        // This avoids touching repositories used across the platform.
        if (method_exists($repo, 'getClassName')) {
            $class = (string) $repo->getClassName();
            if ('' !== $class) {
                // Use a stable alias "resource" to preserve your existing filters like:
                // ->andWhere('resource.iid IN (:ids)')
                return $this->createContextQb($class, $course, $session, 'resource');
            }
        }

        throw new \RuntimeException(
            'Cannot build resources QB: repository lacks getResourcesByCourse() and getClassName().'
        );
    }

    private function trace(string $message, array $context = []): void
    {
        if (!self::TRACE_ENABLED) {
            return;
        }

        if (!empty($context)) {
            $message .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        error_log($message);
    }
}
