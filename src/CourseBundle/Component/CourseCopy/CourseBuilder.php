<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\ResourceFile;
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
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Wiki;
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
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Entity\CThematicAdvance;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Closure;
use Countable;
use Database;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use DocumentManager;
use ReflectionProperty;
use stdClass;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

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
     * @var array<int, array{0:string,1:string,2:string}> Documents referenced inside HTML
     */
    public array $documentsAddedInText = [];

    /**
     * Doctrine services.
     */
    private $em;       // Doctrine EntityManager
    private $docRepo;  // CDocumentRepository

    /**
     * Constructor (keeps legacy init; wires Doctrine repositories).
     *
     * @param string     $type   'partial'|'complete'
     * @param array|null $course Optional course info array
     */
    public function __construct($type = '', $course = null)
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

        $this->em = Database::getManager();
        $this->docRepo = Container::getDocumentRepository();

        // Use $this->em / $this->docRepo in build_documents() when needed.
    }

    /**
     * Merge a parsed list of document refs into memory.
     *
     * @param array<int, array{0:string,1:string,2:string}> $list
     */
    public function addDocumentList(array $list): void
    {
        foreach ($list as $item) {
            if (!\in_array($item[0], $this->documentsAddedInText, true)) {
                $this->documentsAddedInText[$item[0]] = $item;
            }
        }
    }

    /**
     * Parse HTML and collect referenced course documents.
     *
     * @param string $html HTML content
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
     * Resolve collected HTML links to CDocument iids via the ResourceNode tree and build them.
     */
    public function restoreDocumentsFromList(): void
    {
        if (empty($this->documentsAddedInText)) {
            return;
        }

        $courseInfo = api_get_course_info();
        $courseCode = (string) ($courseInfo['code'] ?? '');
        if ('' === $courseCode) {
            return;
        }

        /** @var CourseEntity|null $course */
        $course = $this->em->getRepository(CourseEntity::class)->findOneBy(['code' => $courseCode]);
        if (!$course instanceof CourseEntity) {
            return;
        }

        // Documents root under the course
        $root = $this->docRepo->getCourseDocumentsRootNode($course);
        if (!$root instanceof ResourceNode) {
            return;
        }

        $iids = [];

        foreach ($this->documentsAddedInText as $item) {
            [$url, $scope, $type] = $item; // url, scope(local/remote), type(rel/abs/url)
            if ('local' !== $scope || !\in_array($type, ['rel', 'abs'], true)) {
                continue;
            }

            $segments = $this->extractDocumentSegmentsFromUrl((string) $url);
            if (!$segments) {
                continue;
            }

            // Walk the ResourceNode tree by matching child titles
            $node = $this->resolveNodeBySegments($root, $segments);
            if (!$node) {
                continue;
            }

            $resource = $this->docRepo->getResourceByResourceNode($node);
            if ($resource instanceof CDocument && \is_int($resource->getIid())) {
                $iids[] = $resource->getIid();
            }
        }

        $iids = array_values(array_unique($iids));
        if ($iids) {
            $this->build_documents(
                api_get_session_id(),
                (int) $course->getId(),
                true,
                $iids
            );
        }
    }

    /**
     * Extract path segments after "/document".
     *
     * @return array<string>
     */
    private function extractDocumentSegmentsFromUrl(string $url): array
    {
        $decoded = urldecode($url);
        if (!preg_match('#/document(/.*)$#', $decoded, $m)) {
            return [];
        }
        $tail = trim($m[1], '/'); // e.g. "Folder/Sub/file.pdf"
        if ('' === $tail) {
            return [];
        }

        $parts = array_values(array_filter(explode('/', $tail), static fn ($s) => '' !== $s));

        return array_map(static fn ($s) => trim($s), $parts);
    }

    /**
     * Walk children by title from a given parent node.
     *
     * @param array<int,string> $segments
     */
    private function resolveNodeBySegments(ResourceNode $parent, array $segments): ?ResourceNode
    {
        $node = $parent;
        foreach ($segments as $title) {
            $child = $this->docRepo->findChildNodeByTitle($node, $title);
            if (!$child instanceof ResourceNode) {
                return null;
            }
            $node = $child;
        }

        return $node;
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
     * Get legacy Course container.
     */
    public function get_course(): Course
    {
        return $this->course;
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
        /** @var CourseEntity|null $courseEntity */
        $courseEntity = '' !== $courseCode
            ? $this->em->getRepository(CourseEntity::class)->findOneBy(['code' => $courseCode])
            : $this->em->getRepository(CourseEntity::class)->find(api_get_course_int_id());

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
                error_log(
                    'COURSE_BUILD: quizzes='.\count($legacyCourse->resources[RESOURCE_QUIZ] ?? []).
                    ' quiz_questions='.\count($legacyCourse->resources[RESOURCE_QUIZQUESTION] ?? [])
                );
            }

            if ('quiz_questions' === $toolKey) {
                $ids = $this->specific_id_list['quiz_questions'] ?? [];
                $this->build_quiz_questions($legacyCourse, $courseEntity, $sessionEntity, $ids);
                error_log(
                    'COURSE_BUILD: explicit quiz_questions='.\count($legacyCourse->resources[RESOURCE_QUIZQUESTION] ?? [])
                );
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

        return $this->course;
    }

    /**
     * Export Learnpath categories (CLpCategory).
     *
     * @param array<int> $ids
     */
    private function build_learnpath_category(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getLpCategoryRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

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
    private function build_learnpaths(
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
        $qb = $lpRepo->getResourcesByCourse($courseEntity, $sessionEntity);

        if (!empty($idList)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $idList))))
            ;
        }

        /** @var CLp[] $lps */
        $lps = $qb->getQuery()->getResult();

        foreach ($lps as $lp) {
            $iid = (int) $lp->getIid();
            $lpType = (int) $lp->getLpType(); // 1=LP, 2=SCORM, 3=AICC

            $items = [];

            /** @var CLpItem $it */
            foreach ($lp->getItems() as $it) {
                $items[] = [
                    'id' => (int) $it->getIid(),
                    'item_type' => (string) $it->getItemType(),
                    'ref' => (string) $it->getRef(),
                    'title' => (string) $it->getTitle(),
                    'name' => (string) $lp->getTitle(),
                    'description' => (string) ($it->getDescription() ?? ''),
                    'path' => (string) $it->getPath(),
                    'min_score' => (float) $it->getMinScore(),
                    'max_score' => null !== $it->getMaxScore() ? (float) $it->getMaxScore() : null,
                    'mastery_score' => null !== $it->getMasteryScore() ? (float) $it->getMasteryScore() : null,
                    'parent_item_id' => (int) $it->getParentItemId(),
                    'previous_item_id' => null !== $it->getPreviousItemId() ? (int) $it->getPreviousItemId() : null,
                    'next_item_id' => null !== $it->getNextItemId() ? (int) $it->getNextItemId() : null,
                    'display_order' => (int) $it->getDisplayOrder(),
                    'prerequisite' => (string) ($it->getPrerequisite() ?? ''),
                    'parameters' => (string) ($it->getParameters() ?? ''),
                    'launch_data' => (string) $it->getLaunchData(),
                    'audio' => (string) ($it->getAudio() ?? ''),
                ];
            }

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
                'display_order' => (int) $lp->getDisplayNotAllowedLp(),
                'js_lib' => (string) $lp->getJsLib(),
                'content_license' => (string) $lp->getContentLicense(),
                'debug' => (bool) $lp->getDebug(),
                'visibility' => '1',
                'author' => (string) $lp->getAuthor(),
                'use_max_score' => (int) $lp->getUseMaxScore(),
                'autolaunch' => (int) $lp->getAutolaunch(),
                'created_on' => $this->fmtDate($lp->getCreatedOn()),
                'modified_on' => $this->fmtDate($lp->getModifiedOn()),
                'published_on' => $this->fmtDate($lp->getPublishedOn()),
                'expired_on' => $this->fmtDate($lp->getExpiredOn()),
                'session_id' => (int) ($sessionEntity?->getId() ?? 0),
                'category_id' => (int) ($lp->getCategory()?->getIid() ?? 0),
                'items' => $items,
            ];

            $legacyCourse->resources[RESOURCE_LEARNPATH][$iid] =
                $this->mkLegacyItem(RESOURCE_LEARNPATH, $iid, $payload, ['items']);
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
                        $payload = ['path' => '/'.$file, 'name' => (string) $file];
                        $legacyCourse->resources['scorm'][$i] =
                            $this->mkLegacyItem('scorm', $i, $payload);
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
    private function build_gradebook(
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

        $criteria = ['course' => $courseEntity];
        if ($sessionEntity) {
            $criteria['session'] = $sessionEntity;
        }

        /** @var GradebookCategory[] $cats */
        $cats = $catRepo->findBy($criteria);
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
    private function serializeGradebookCategory(GradebookCategory $c): array
    {
        $arr = [
            'id' => (int) $c->getId(),
            'title' => (string) $c->getTitle(),
            'description' => (string) ($c->getDescription() ?? ''),
            'weight' => (float) $c->getWeight(),
            'visible' => (bool) $c->getVisible(),
            'locked' => (int) $c->getLocked(),
            'parent_id' => $c->getParent() ? (int) $c->getParent()->getId() : 0,
            'generate_certificates' => (bool) $c->getGenerateCertificates(),
            'certificate_validity_period' => $c->getCertificateValidityPeriod(),
            'is_requirement' => (bool) $c->getIsRequirement(),
            'default_lowest_eval_exclude' => (bool) $c->getDefaultLowestEvalExclude(),
            'minimum_to_validate' => $c->getMinimumToValidate(),
            'gradebooks_to_validate_in_dependence' => $c->getGradeBooksToValidateInDependence(),
            'allow_skills_by_subcategory' => $c->getAllowSkillsBySubcategory(),
            // camelCase duplicates (future-proof)
            'generateCertificates' => (bool) $c->getGenerateCertificates(),
            'certificateValidityPeriod' => $c->getCertificateValidityPeriod(),
            'isRequirement' => (bool) $c->getIsRequirement(),
            'defaultLowestEvalExclude' => (bool) $c->getDefaultLowestEvalExclude(),
            'minimumToValidate' => $c->getMinimumToValidate(),
            'gradeBooksToValidateInDependence' => $c->getGradeBooksToValidateInDependence(),
            'allowSkillsBySubcategory' => $c->getAllowSkillsBySubcategory(),
        ];

        if ($c->getGradeModel()) {
            $arr['grade_model_id'] = (int) $c->getGradeModel()->getId();
        }

        // Evaluations
        $arr['evaluations'] = [];
        foreach ($c->getEvaluations() as $e) {
            /** @var GradebookEvaluation $e */
            $arr['evaluations'][] = [
                'title' => (string) $e->getTitle(),
                'description' => (string) ($e->getDescription() ?? ''),
                'weight' => (float) $e->getWeight(),
                'max' => (float) $e->getMax(),
                'type' => (string) $e->getType(),
                'visible' => (int) $e->getVisible(),
                'locked' => (int) $e->getLocked(),
                'best_score' => $e->getBestScore(),
                'average_score' => $e->getAverageScore(),
                'score_weight' => $e->getScoreWeight(),
                'min_score' => $e->getMinScore(),
            ];
        }

        // Links
        $arr['links'] = [];
        foreach ($c->getLinks() as $l) {
            /** @var GradebookLink $l */
            $arr['links'][] = [
                'type' => (int) $l->getType(),
                'ref_id' => (int) $l->getRefId(),
                'weight' => (float) $l->getWeight(),
                'visible' => (int) $l->getVisible(),
                'locked' => (int) $l->getLocked(),
                'best_score' => $l->getBestScore(),
                'average_score' => $l->getAverageScore(),
                'score_weight' => $l->getScoreWeight(),
                'min_score' => $l->getMinScore(),
            ];
        }

        return $arr;
    }

    /**
     * Export Works (root folders only; include assignment params).
     *
     * @param array<int> $ids
     */
    private function build_works(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getStudentPublicationRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

        $qb
            ->andWhere('resource.publicationParent IS NULL')
            ->andWhere('resource.filetype = :ft')->setParameter('ft', 'folder')
            ->andWhere('resource.active = 1')
        ;

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
        }

        /** @var CStudentPublication[] $rows */
        $rows = $qb->getQuery()->getResult();

        foreach ($rows as $row) {
            $iid = (int) $row->getIid();
            $title = (string) $row->getTitle();
            $desc = (string) ($row->getDescription() ?? '');

            // Detect documents linked in description
            $this->findAndSetDocumentsInText($desc);

            $asgmt = $row->getAssignment();
            $expiresOn = $asgmt?->getExpiresOn()?->format('Y-m-d H:i:s');
            $endsOn = $asgmt?->getEndsOn()?->format('Y-m-d H:i:s');
            $addToCal = $asgmt && $asgmt->getEventCalendarId() > 0 ? 1 : 0;
            $enableQ = (bool) ($asgmt?->getEnableQualification() ?? false);

            $params = [
                'id' => $iid,
                'title' => $title,
                'description' => $desc,
                'weight' => (float) $row->getWeight(),
                'qualification' => (float) $row->getQualification(),
                'allow_text_assignment' => (int) $row->getAllowTextAssignment(),
                'default_visibility' => (bool) ($row->getDefaultVisibility() ?? false),
                'student_delete_own_publication' => (bool) ($row->getStudentDeleteOwnPublication() ?? false),
                'extensions' => $row->getExtensions(),
                'group_category_work_id' => (int) $row->getGroupCategoryWorkId(),
                'post_group_id' => (int) $row->getPostGroupId(),
                'enable_qualification' => $enableQ,
                'add_to_calendar' => $addToCal ? 1 : 0,
                'expires_on' => $expiresOn ?: null,
                'ends_on' => $endsOn ?: null,
                'name' => $title,
                'url' => null,
            ];

            $legacy = new Work($params);
            $legacyCourse->add_resource($legacy);
        }
    }

    /**
     * Export Attendance + calendars.
     *
     * @param array<int> $ids
     */
    private function build_attendance(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getAttendanceRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

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
    private function build_thematic(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getThematicRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

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
    private function build_wiki(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getWikiRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
        }

        /** @var CWiki[] $pages */
        $pages = $qb->getQuery()->getResult();

        foreach ($pages as $page) {
            $iid = (int) $page->getIid();
            $pageId = (int) ($page->getPageId() ?? $iid);
            $reflink = (string) $page->getReflink();
            $title = (string) $page->getTitle();
            $content = (string) $page->getContent();
            $userId = (int) $page->getUserId();
            $groupId = (int) ($page->getGroupId() ?? 0);
            $progress = (string) ($page->getProgress() ?? '');
            $version = (int) ($page->getVersion() ?? 1);
            $dtime = $page->getDtime()?->format('Y-m-d H:i:s') ?? '';

            $this->findAndSetDocumentsInText($content);

            $legacy = new Wiki(
                $iid,
                $pageId,
                $reflink,
                $title,
                $content,
                $userId,
                $groupId,
                $dtime,
                $progress,
                $version
            );

            $this->course->add_resource($legacy);
        }
    }

    /**
     * Export Glossary terms (collect docs in descriptions).
     *
     * @param array<int> $ids
     */
    private function build_glossary(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getGlossaryRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

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

            $legacy = new Glossary(
                $iid,
                $title,
                $desc,
                0
            );

            $this->course->add_resource($legacy);
        }
    }

    /**
     * Export Course descriptions (collect docs in HTML).
     *
     * @param array<int> $ids
     */
    private function build_course_descriptions(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = Container::getCourseDescriptionRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);

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

            $export = new CourseDescription(
                $iid,
                $title,
                $html,
                $type
            );

            $this->course->add_resource($export);
        }
    }

    /**
     * Export Calendar events (first attachment as legacy, all as assets).
     *
     * @param array<int> $ids
     */
    private function build_events(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $eventRepo = Container::getCalendarEventRepository();
        $qb = $eventRepo->getResourcesByCourse($courseEntity, $sessionEntity);

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
                            $candidate = $resourceBase.$storedRel;
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
                    error_log('COURSE_BUILD: event attachment file not found (event_iid='
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
    private function build_announcements(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity) {
            return;
        }

        $annRepo = Container::getAnnouncementRepository();
        $qb = $annRepo->getResourcesByCourse($courseEntity, $sessionEntity);

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
                            $candidate = $resourceBase.$storedRel;
                            if (is_readable($candidate)) {
                                $abs = $candidate;
                            }
                        }
                    }
                }

                if ($abs) {
                    $this->tryAddAsset($assetRel, $abs, (int) $att->getSize());
                } else {
                    error_log('COURSE_BUILD: announcement attachment not found (iid='.(int) $att->getIid().')');
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
     *
     * @param string $relPath Relative path inside the ZIP
     * @param string $absPath Absolute filesystem path
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
            error_log('COURSE_BUILD: asset missing: '.$absPath);
        }
    }

    /**
     * Export Surveys; returns needed Question IDs for follow-up export.
     *
     * @param array<int> $surveyIds
     *
     * @return array<int>
     */
    private function build_surveys(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $surveyIds
    ): array {
        if (!$courseEntity) {
            return [];
        }

        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(CSurvey::class, 's')
            ->innerJoin('s.resourceNode', 'rn')
            ->leftJoin('rn.resourceLinks', 'links')
            ->andWhere('links.course = :course')->setParameter('course', $courseEntity)
            ->andWhere(
                $sessionEntity
                ? '(links.session IS NULL OR links.session = :session)'
                : 'links.session IS NULL'
            )
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
        ;

        if ($sessionEntity) {
            $qb->setParameter('session', $sessionEntity);
        }
        if (!empty($surveyIds)) {
            $qb->andWhere('s.iid IN (:ids)')->setParameter('ids', array_map('intval', $surveyIds));
        }

        /** @var CSurvey[] $surveys */
        $surveys = $qb->getQuery()->getResult();

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
                'shuffle' => (bool) $s->getShuffle(),
                'one_question_per_page' => (bool) $s->getOneQuestionPerPage(),
                'visible_results' => $s->getVisibleResults(),
                'display_question_number' => (bool) $s->isDisplayQuestionNumber(),
                'survey_type' => (int) $s->getSurveyType(),
                'show_form_profile' => (int) $s->getShowFormProfile(),
                'form_fields' => (string) $s->getFormFields(),
                'duration' => $s->getDuration(),
                'question_ids' => $qIds,
                'survey_id' => $iid,
            ];

            $legacyCourse->resources[RESOURCE_SURVEY][$iid] =
                $this->mkLegacyItem(RESOURCE_SURVEY, $iid, $payload);

            error_log('COURSE_BUILD: SURVEY iid='.$iid.' qids=['.implode(',', $qIds).']');
        }

        return array_keys($neededQuestionIds);
    }

    /**
     * Export Survey Questions (answers promoted at top level).
     *
     * @param array<int> $questionIds
     */
    private function build_survey_questions(
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
            ->innerJoin('q.survey', 's')
            ->innerJoin('s.resourceNode', 'rn')
            ->leftJoin('rn.resourceLinks', 'links')
            ->andWhere('links.course = :course')->setParameter('course', $courseEntity)
            ->andWhere(
                $sessionEntity
                ? '(links.session IS NULL OR links.session = :session)'
                : 'links.session IS NULL'
            )
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->orderBy('s.iid', 'ASC')
            ->addOrderBy('q.sort', 'ASC')
        ;

        if ($sessionEntity) {
            $qb->setParameter('session', $sessionEntity);
        }
        if (!empty($questionIds)) {
            $qb->andWhere('q.iid IN (:ids)')->setParameter('ids', array_map('intval', $questionIds));
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
            error_log('COURSE_BUILD: SURVEY_Q qid='.$qid.' survey='.$sid.' answers='.\count($answers));
        }

        error_log('COURSE_BUILD: survey questions exported='.$exported);
    }

    /**
     * Export Quizzes and return required Question IDs.
     *
     * @param array<int> $quizIds
     *
     * @return array<int>
     */
    private function build_quizzes(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $quizIds
    ): array {
        if (!$courseEntity) {
            return [];
        }

        $qb = $this->em->createQueryBuilder()
            ->select('q')
            ->from(CQuiz::class, 'q')
            ->innerJoin('q.resourceNode', 'rn')
            ->leftJoin('rn.resourceLinks', 'links')
            ->andWhere('links.course = :course')->setParameter('course', $courseEntity)
            ->andWhere(
                $sessionEntity
                ? '(links.session IS NULL OR links.session = :session)'
                : 'links.session IS NULL'
            )
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
        ;

        if ($sessionEntity) {
            $qb->setParameter('session', $sessionEntity);
        }
        if (!empty($quizIds)) {
            $qb->andWhere('q.iid IN (:ids)')->setParameter('ids', array_map('intval', $quizIds));
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
                'question_ids' => [],
                'question_orders' => [],
            ];

            $rels = $this->em->createQueryBuilder()
                ->select('rel', 'qq')
                ->from(CQuizRelQuestion::class, 'rel')
                ->innerJoin('rel.question', 'qq')
                ->andWhere('rel.quiz = :quiz')
                ->setParameter('quiz', $quiz)
                ->orderBy('rel.questionOrder', 'ASC')
                ->getQuery()->getResult()
            ;

            foreach ($rels as $rel) {
                $qid = (int) $rel->getQuestion()->getIid();
                $payload['question_ids'][] = $qid;
                $payload['question_orders'][] = (int) $rel->getQuestionOrder();
                $neededQuestionIds[$qid] = true;
            }

            $legacyCourse->resources[RESOURCE_QUIZ][$iid] =
                $this->mkLegacyItem(
                    RESOURCE_QUIZ,
                    $iid,
                    $payload,
                    ['question_ids', 'question_orders']
                );
        }

        error_log(
            'COURSE_BUILD: build_quizzes done; total='.\count($quizzes)
        );

        return array_keys($neededQuestionIds);
    }

    /**
     * Safe count helper for mixed values.
     */
    private function safeCount(mixed $v): int
    {
        return (\is_array($v) || $v instanceof Countable) ? \count($v) : 0;
    }

    /**
     * Export Quiz Questions (answers and options promoted).
     *
     * @param array<int> $questionIds
     */
    private function build_quiz_questions(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $questionIds
    ): void {
        if (!$courseEntity) {
            return;
        }

        error_log('COURSE_BUILD: build_quiz_questions start ids='.json_encode(array_values($questionIds)));
        error_log('COURSE_BUILD: build_quiz_questions exported='.$this->safeCount($legacyCourse->resources[RESOURCE_QUIZQUESTION] ?? 0));

        $qb = $this->em->createQueryBuilder()
            ->select('qq')
            ->from(CQuizQuestion::class, 'qq')
            ->innerJoin('qq.resourceNode', 'qrn')
            ->leftJoin('qrn.resourceLinks', 'qlinks')
            ->andWhere('qlinks.course = :course')->setParameter('course', $courseEntity)
            ->andWhere(
                $sessionEntity
                ? '(qlinks.session IS NULL OR qlinks.session = :session)'
                : 'qlinks.session IS NULL'
            )
            ->andWhere('qlinks.deletedAt IS NULL')
            ->andWhere('qlinks.endVisibilityAt IS NULL')
        ;

        if ($sessionEntity) {
            $qb->setParameter('session', $sessionEntity);
        }
        if (!empty($questionIds)) {
            $qb->andWhere('qq.iid IN (:ids)')->setParameter('ids', array_map('intval', $questionIds));
        }

        /** @var CQuizQuestion[] $questions */
        $questions = $qb->getQuery()->getResult();

        error_log('COURSE_BUILD: build_quiz_questions start ids='.json_encode(array_values($questionIds)));
        error_log('COURSE_BUILD: build_quiz_questions exported='.$this->safeCount($legacyCourse->resources[RESOURCE_QUIZQUESTION] ?? 0));

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
            ];

            $ans = $this->em->createQueryBuilder()
                ->select('a')
                ->from(CQuizAnswer::class, 'a')
                ->andWhere('a.question = :q')->setParameter('q', $q)
                ->orderBy('a.position', 'ASC')
                ->getQuery()->getResult()
            ;

            foreach ($ans as $a) {
                $payload['answers'][] = [
                    'id' => (int) $a->getIid(),
                    'answer' => (string) $a->getAnswer(),
                    'comment' => (string) ($a->getComment() ?? ''),
                    'ponderation' => (float) $a->getPonderation(),
                    'position' => (int) $a->getPosition(),
                    'hotspot_coordinates' => $a->getHotspotCoordinates(),
                    'hotspot_type' => $a->getHotspotType(),
                    'correct' => $a->getCorrect(),
                ];
            }

            if (\defined('MULTIPLE_ANSWER_TRUE_FALSE') && MULTIPLE_ANSWER_TRUE_FALSE === (int) $q->getType()) {
                $opts = $this->em->createQueryBuilder()
                    ->select('o')
                    ->from(CQuizQuestionOption::class, 'o')
                    ->andWhere('o.question = :q')->setParameter('q', $q)
                    ->orderBy('o.position', 'ASC')
                    ->getQuery()->getResult()
                ;

                $payload['question_options'] = array_map(static fn ($o) => [
                    'id' => (int) $o->getIid(),
                    'name' => (string) $o->getTitle(),
                    'position' => (int) $o->getPosition(),
                ], $opts);
            }

            $legacyCourse->resources[RESOURCE_QUIZQUESTION][$qid] =
                $this->mkLegacyItem(RESOURCE_QUIZQUESTION, $qid, $payload, ['answers', 'question_options']);

            error_log(
                'COURSE_BUILD: QQ qid='.$qid.
                ' quiz_type='.($legacyCourse->resources[RESOURCE_QUIZQUESTION][$qid]->quiz_type ?? 'missing').
                ' answers='.\count($legacyCourse->resources[RESOURCE_QUIZQUESTION][$qid]->answers ?? [])
            );
        }
    }

    /**
     * Export Link category as legacy item.
     */
    private function build_link_category(CLinkCategory $category): void
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
    private function build_links(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $linkRepo = Container::getLinkRepository();
        $catRepo = Container::getLinkCategoryRepository();

        $qb = $linkRepo->getResourcesByCourse($courseEntity, $sessionEntity);

        if (!empty($ids)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $ids))))
            ;
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
        foreach ($arrayKeysToPromote as $k) {
            if (isset($obj[$k]) && \is_array($obj[$k])) {
                $o->{$k} = $obj[$k];
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
            if (isset($obj['name']) && '' !== $obj['name']) {
                $o->name = (string) $obj['name'];
            } elseif (isset($obj['title']) && '' !== $obj['title']) {
                $o->name = (string) $obj['title'];
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
     * Export Tool intro (tool -> intro text) for visible tools.
     */
    private function build_tool_intro(
        object $legacyCourse,
        ?CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity
    ): void {
        if (!$courseEntity instanceof CourseEntity) {
            return;
        }

        $repo = $this->em->getRepository(CToolIntro::class);

        $qb = $repo->createQueryBuilder('ti')
            ->innerJoin('ti.courseTool', 'ct')
            ->andWhere('ct.course = :course')
            ->setParameter('course', $courseEntity)
        ;

        if ($sessionEntity) {
            $qb->andWhere('ct.session = :session')->setParameter('session', $sessionEntity);
        } else {
            $qb->andWhere('ct.session IS NULL');
        }

        $qb->andWhere('ct.visibility = :vis')->setParameter('vis', true);

        /** @var CToolIntro[] $intros */
        $intros = $qb->getQuery()->getResult();

        foreach ($intros as $intro) {
            $ctool = $intro->getCourseTool();       // CTool
            $titleKey = (string) $ctool->getTitle();   // e.g. 'documents', 'forum'
            if ('' === $titleKey) {
                continue;
            }

            $payload = [
                'id' => $titleKey,
                'intro_text' => (string) $intro->getIntroText(),
            ];

            // Use 0 as source_id (unused by restore)
            $legacyCourse->resources[RESOURCE_TOOL_INTRO][$titleKey] =
                $this->mkLegacyItem(RESOURCE_TOOL_INTRO, 0, $payload);
        }
    }

    /**
     * Export Forum categories.
     *
     * @param array<int> $ids
     */
    private function build_forum_category(
        object $legacyCourse,
        CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        $repo = Container::getForumCategoryRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);
        $categories = $qb->getQuery()->getResult();

        $keep = $this->makeIdFilter($ids);

        foreach ($categories as $cat) {
            /** @var CForumCategory $cat */
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
    private function build_forums(
        object $legacyCourse,
        CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        $repo = Container::getForumRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);
        $forums = $qb->getQuery()->getResult();

        $keep = $this->makeIdFilter($ids);

        foreach ($forums as $f) {
            /** @var CForum $f */
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
    private function build_forum_topics(
        object $legacyCourse,
        CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        $repo = Container::getForumThreadRepository();
        $qb = $repo->getResourcesByCourse($courseEntity, $sessionEntity);
        $threads = $qb->getQuery()->getResult();

        $keep = $this->makeIdFilter($ids);

        foreach ($threads as $t) {
            /** @var CForumThread $t */
            $id = (int) $t->getIid();
            if (!$keep($id)) {
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
    }

    /**
     * Export first post for each thread as topic root post.
     *
     * @param array<int> $ids
     */
    private function build_forum_posts(
        object $legacyCourse,
        CourseEntity $courseEntity,
        ?SessionEntity $sessionEntity,
        array $ids
    ): void {
        $repoThread = Container::getForumThreadRepository();
        $repoPost = Container::getForumPostRepository();

        $qb = $repoThread->getResourcesByCourse($courseEntity, $sessionEntity);
        $threads = $qb->getQuery()->getResult();

        $keep = $this->makeIdFilter($ids);

        foreach ($threads as $t) {
            /** @var CForumThread $t */
            $threadId = (int) $t->getIid();
            if (!$keep($threadId)) {
                continue;
            }

            $first = $repoPost->findOneBy(['thread' => $t], ['postDate' => 'ASC', 'iid' => 'ASC']);
            if (!$first) {
                continue;
            }

            $postId = (int) $first->getIid();
            $titleFromPost = trim((string) $first->getTitle());
            if ('' === $titleFromPost) {
                $plain = trim(strip_tags((string) ($first->getPostText() ?? '')));
                $titleFromPost = mb_substr('' !== $plain ? $plain : 'Post', 0, 60);
            }

            $payload = [
                'title' => $titleFromPost,
                'post_title' => $titleFromPost,
                'post_text' => (string) ($first->getPostText() ?? ''),
                'thread_id' => $threadId,
                'forum_id' => (int) ($t->getForum()?->getIid() ?? 0),
                'post_notification' => (int) ($first->getPostNotification() ? 1 : 0),
                'visible' => (int) ($first->getVisible() ? 1 : 0),
                'status' => (int) ($first->getStatus() ?? CForumPost::STATUS_VALIDATED),
                'post_parent_id' => (int) ($first->getPostParent()?->getIid() ?? 0),
                'poster_id' => (int) ($first->getUser()?->getId() ?? 0),
                'text' => (string) ($first->getPostText() ?? ''),
                'poster_name' => (string) ($first->getUser()?->getUsername() ?? ''),
                'post_date' => $this->fmtDate($first->getPostDate()),
            ];

            $legacyCourse->resources[RESOURCE_FORUMPOST][$postId] =
                $this->mkLegacyItem(RESOURCE_FORUMPOST, $postId, $payload);
        }
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

        $qb = $this->docRepo->getResourcesByCourse(
            $course,
            $session,
            null,
            null,
            true,
            false
        );

        if (!empty($idList)) {
            $qb->andWhere('resource.iid IN (:ids)')
                ->setParameter('ids', array_values(array_unique(array_map('intval', $idList))))
            ;
        }

        /** @var CDocument[] $docs */
        $docs = $qb->getQuery()->getResult();

        foreach ($docs as $doc) {
            $node = $doc->getResourceNode();
            $filetype = $doc->getFiletype(); // 'file'|'folder'|...
            $title = $doc->getTitle();
            $comment = $doc->getComment() ?? '';
            $iid = (int) $doc->getIid();
            $fullPath = $doc->getFullPath();

            // Determine size
            $size = 0;
            if ('folder' === $filetype) {
                $size = $this->docRepo->getFolderSize($node, $course, $session);
            } else {
                /** @var Collection<int,ResourceFile>|null $files */
                $files = $node?->getResourceFiles();
                if ($files && $files->count() > 0) {
                    /** @var ResourceFile $first */
                    $first = $files->first();
                    $size = (int) $first->getSize();
                }
            }

            $exportDoc = new Document(
                $iid,
                '/'.$fullPath,
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

        $this->build_documents_with_repo($course, $session, $withBaseContent, $idList);
    }
}
