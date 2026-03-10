<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ActivityExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\AnnouncementsForumExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\AssignExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\AttendanceMetaExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\CourseCalendarExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\FeedbackExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ForumExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\GlossaryExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\GradebookMetaExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\LabelExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\LearnpathMetaExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\PageExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\QuizExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\QuizMetaExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ResourceExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ThematicMetaExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\UrlExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\WikiExport;
use DocumentManager;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * Handles the export of a Moodle course in .mbz format.
 *
 * Important design:
 * - produce a valid Moodle backup structure
 * - keep Chamilo sidecar metadata under chamilo/* for richer restore/import in C2
 * - preserve selection-mode semantics
 */
class MoodleExport
{
    /**
     * @var object
     */
    private $course;

    /**
     * @var array<string,mixed>
     */
    private static array $adminUserData = [];

    /**
     * True when exporting only selected items.
     */
    private bool $selectionMode = false;

    /**
     * @var array<string,array<int,bool>>
     */
    protected static array $activityUserinfo = [];

    /**
     * Synthetic module id for the News forum generated from announcements.
     */
    private const ANNOUNCEMENTS_MODULE_ID = 48000001;

    /**
     * Synthetic module id for Gradebook Chamilo-only metadata.
     */
    private const GRADEBOOK_MODULE_ID = 48000002;

    /**
     * Synthetic module id for the single exported wiki activity.
     */
    private const WIKI_MODULE_ID = 48000003;

    private static int $backupCourseContextId = 0;
    private static int $backupCourseId = 0;

    /**
     * Constructor to initialize the course object.
     *
     * @param object $course filtered legacy course (may be full or selected-only)
     * @param bool   $selectionMode when true, do not re-hydrate from complete snapshot
     */
    public function __construct(object $course, bool $selectionMode = false)
    {
        $this->course = $course;
        $this->selectionMode = $selectionMode;

        // Only auto-fill dependencies on full export.
        // In selection mode we must not re-inject extra content.
        if (!$this->selectionMode) {
            $builder = new CourseBuilder('complete');
            $complete = $builder->build(0, (string) ($course->code ?? ''));

            $this->fillResourcesFromLearnpath($complete);
            $this->fillQuestionsFromQuiz($complete);
        }
    }

    /**
     * Export the Moodle course in .mbz format.
     *
     * @return string path to the created .mbz file
     */
    public function export(string $courseId, string $exportDir, int $version): string
    {
        FileIndex::reset();
        $tempDir = api_get_path(SYS_ARCHIVE_PATH).$exportDir;

        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, api_get_permissions_for_new_directories(), true)) {
                @error_log('[MoodleExport::export] ERROR cannot create tempDir='.$tempDir);
                throw new Exception(get_lang('Unable to create the folder.'));
            }
            @error_log('[MoodleExport::export] Created tempDir='.$tempDir);
        }

        $courseInfo = api_get_course_info($courseId);
        if (!$courseInfo) {
            @error_log('[MoodleExport::export] ERROR CourseNotFound id='.$courseId);
            throw new Exception(get_lang('Course not found'));
        }

        $backupCourseId = (int) ($courseInfo['real_id'] ?? 0);
        $backupCourseContextId = $this->buildBackupCourseContextId($backupCourseId);
        self::setBackupCourseContext($backupCourseId, $backupCourseContextId);

        // Keep this call for compatibility with legacy experiments,
        // even if current activity collection already resolves URLs directly.
        $this->enqueueUrlActivities();

        $activities = $this->getActivities();
        $sections = $this->getSections($activities);

        // Create Moodle backup skeleton.
        $this->createMoodleBackupXml($tempDir, $version, $activities, $sections);

        // Export course structure.
        $courseExport = new CourseExport($this->course, $activities);
        $courseExport->exportCourse($tempDir);

        // Collect extra files from intro/page HTML.
        $pageExport = new PageExport($this->course);
        $pageFiles = [];
        $pageData = $pageExport->getData(0, 1);
        if (!empty($pageData['files'])) {
            $pageFiles = $pageData['files'];
        }

        // Collect files from resource activities.
        $resourceFiles = [];
        $resourceExport = new ResourceExport($this->course);
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'resource') {
                continue;
            }

            $resourceData = $resourceExport->getData(
                (int) ($activity['id'] ?? 0),
                (int) ($activity['sectionid'] ?? 0),
                (int) ($activity['moduleid'] ?? 0)
            );

            if (!empty($resourceData['files'])) {
                $resourceFiles = array_merge($resourceFiles, $resourceData['files']);
            }
        }

        // Collect embedded files from quiz questions/answers.
        $quizFiles = [];
        $quizExport = new QuizExport($this->course);
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'quiz') {
                continue;
            }

            $quizData = $quizExport->getData(
                (int) ($activity['id'] ?? 0),
                (int) ($activity['sectionid'] ?? 0),
                (int) ($activity['moduleid'] ?? 0)
            );

            if (!empty($quizData['files'])) {
                $quizFiles = array_merge($quizFiles, $quizData['files']);
            }
        }

        // Files export.
        $fileExport = new FileExport($this->course);
        $filesData = $fileExport->getFilesData();
        $filesData['files'] = array_merge(
            $filesData['files'] ?? [],
            $pageFiles,
            $resourceFiles,
            $quizFiles
        );

        $fileExport->exportFiles($filesData, $tempDir);

        // Export sections.
        $this->exportSections($tempDir, $activities, $sections);

        // Export complementary artifacts.
        $this->exportCourseCalendar($tempDir);
        $this->exportAnnouncementsForum($activities, $tempDir);
        $this->exportLabelActivities($activities, $tempDir);
        $this->exportAttendanceActivities($activities, $tempDir);
        $this->exportThematicActivities($activities, $tempDir);
        $this->exportWikiActivities($activities, $tempDir);
        $this->exportGradebookActivities($activities, $tempDir);
        $this->exportQuizMetaActivities($activities, $tempDir);
        $this->exportLearnpathMeta($tempDir);
        $this->exportDocumentIndex($tempDir);

        // Root XMLs.
        $this->exportRootXmlFiles($tempDir, $activities);

        // Build .mbz.
        $exportedFile = $this->createMbzFile($tempDir);

        // Cleanup.
        $this->cleanupTempDir($tempDir);

        return $exportedFile;
    }

    /**
     * Export questions data to XML file.
     *
     * @param array<int,array<string,mixed>> $questionsData
     */
    public function exportQuestionsXml(array $questionsData, string $exportDir): void
    {
        $quizExport = new QuizExport($this->course);
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<question_categories>'.PHP_EOL;

        $rootByContext = [];
        $writtenCategories = [];

        foreach ($questionsData as $quiz) {
            $contextId = (int) ($quiz['contextid'] ?? 0);
            $courseId = (int) ($quiz['courseid'] ?? 0);

            if ($contextId <= 0 || $courseId <= 0) {
                continue;
            }

            if (!isset($rootByContext[$contextId])) {
                $rootId = $this->buildRootQuestionCategoryId($contextId);
                $rootByContext[$contextId] = $rootId;

                $xmlContent .= '  <question_category id="'.$rootId.'">'.PHP_EOL;
                $xmlContent .= '    <name>Top</name>'.PHP_EOL;
                $xmlContent .= '    <contextid>'.$contextId.'</contextid>'.PHP_EOL;
                $xmlContent .= '    <contextlevel>50</contextlevel>'.PHP_EOL;
                $xmlContent .= '    <contextinstanceid>'.$courseId.'</contextinstanceid>'.PHP_EOL;
                $xmlContent .= '    <info>Top category</info>'.PHP_EOL;
                $xmlContent .= '    <infoformat>0</infoformat>'.PHP_EOL;
                $xmlContent .= '    <stamp>moodle+'.time().'+CATEGORYSTAMP</stamp>'.PHP_EOL;
                $xmlContent .= '    <parent>0</parent>'.PHP_EOL;
                $xmlContent .= '    <sortorder>999</sortorder>'.PHP_EOL;
                $xmlContent .= '    <idnumber>$@NULL@$</idnumber>'.PHP_EOL;
                $xmlContent .= '    <questions></questions>'.PHP_EOL;
                $xmlContent .= '  </question_category>'.PHP_EOL;
            }
        }

        foreach ($questionsData as $quiz) {
            if (empty($quiz['questions']) || !is_array($quiz['questions'])) {
                continue;
            }

            $contextId = (int) ($quiz['contextid'] ?? 0);
            $courseId = (int) ($quiz['courseid'] ?? 0);

            if ($contextId <= 0 || $courseId <= 0) {
                continue;
            }

            $rootId = (int) ($rootByContext[$contextId] ?? 0);
            if ($rootId <= 0) {
                $rootId = $this->buildRootQuestionCategoryId($contextId);
                $rootByContext[$contextId] = $rootId;
            }

            $categoryId = (int) ($quiz['question_category_id'] ?? 0);
            if ($categoryId <= 0) {
                $moduleId = (int) ($quiz['moduleid'] ?? 0);
                $categoryId = 1000000000 + max(1, $moduleId);
            }

            $categoryKey = $contextId.':'.$categoryId;
            if (isset($writtenCategories[$categoryKey])) {
                continue;
            }
            $writtenCategories[$categoryKey] = true;

            $xmlContent .= '  <question_category id="'.$categoryId.'">'.PHP_EOL;
            $xmlContent .= '    <name>Default for '.htmlspecialchars((string) ($quiz['name'] ?? 'Quiz')).'</name>'.PHP_EOL;
            $xmlContent .= '    <contextid>'.$contextId.'</contextid>'.PHP_EOL;
            $xmlContent .= '    <contextlevel>50</contextlevel>'.PHP_EOL;
            $xmlContent .= '    <contextinstanceid>'.$courseId.'</contextinstanceid>'.PHP_EOL;
            $xmlContent .= '    <info>Default questions category</info>'.PHP_EOL;
            $xmlContent .= '    <infoformat>0</infoformat>'.PHP_EOL;
            $xmlContent .= '    <stamp>moodle+'.time().'+CATEGORYSTAMP</stamp>'.PHP_EOL;
            $xmlContent .= '    <parent>'.$rootId.'</parent>'.PHP_EOL;
            $xmlContent .= '    <sortorder>999</sortorder>'.PHP_EOL;
            $xmlContent .= '    <idnumber>$@NULL@$</idnumber>'.PHP_EOL;
            $xmlContent .= '    <questions>'.PHP_EOL;

            foreach ($quiz['questions'] as $question) {
                $xmlContent .= $quizExport->exportQuestion($question);
            }

            $xmlContent .= '    </questions>'.PHP_EOL;
            $xmlContent .= '  </question_category>'.PHP_EOL;
        }

        $xmlContent .= '</question_categories>'.PHP_EOL;

        file_put_contents($exportDir.'/questions.xml', $xmlContent);
    }

    /**
     * Build a stable root question category id per context id.
     */
    private function buildRootQuestionCategoryId(int $contextId): int
    {
        return 800000000 + max(1, $contextId);
    }

    /**
     * Sets the admin user data.
     */
    public function setAdminUserData(int $id, string $username, string $email): void
    {
        self::$adminUserData = [
            'id' => $id,
            'contextid' => $id,
            'username' => $username,
            'idnumber' => '',
            'email' => $email,
            'phone1' => '',
            'phone2' => '',
            'institution' => '',
            'department' => '',
            'address' => '',
            'city' => 'London',
            'country' => 'GB',
            'lastip' => '127.0.0.1',
            'picture' => '0',
            'description' => '',
            'descriptionformat' => 1,
            'imagealt' => '$@NULL@$',
            'auth' => 'manual',
            'firstname' => 'Admin',
            'lastname' => 'User',
            'confirmed' => 1,
            'policyagreed' => 0,
            'deleted' => 0,
            'lang' => 'en',
            'theme' => '',
            'timezone' => 99,
            'firstaccess' => time(),
            'lastaccess' => time() - (60 * 60 * 24 * 7),
            'lastlogin' => time() - (60 * 60 * 24 * 2),
            'currentlogin' => time(),
            'mailformat' => 1,
            'maildigest' => 0,
            'maildisplay' => 1,
            'autosubscribe' => 1,
            'trackforums' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
            'trustbitmask' => 0,
            'preferences' => [
                ['name' => 'core_message_migrate_data', 'value' => 1],
                ['name' => 'auth_manual_passwordupdatetime', 'value' => time()],
                ['name' => 'email_bounce_count', 'value' => 1],
                ['name' => 'email_send_count', 'value' => 1],
                ['name' => 'login_failed_count_since_success', 'value' => 0],
                ['name' => 'filepicker_recentrepository', 'value' => 5],
                ['name' => 'filepicker_recentlicense', 'value' => 'unknown'],
            ],
        ];
    }

    /**
     * Returns hardcoded data for the admin user.
     *
     * @return array<string,mixed>
     */
    public static function getAdminUserData(): array
    {
        return self::$adminUserData;
    }

    public static function flagActivityUserinfo(string $modname, int $moduleId, bool $hasUserinfo): void
    {
        self::$activityUserinfo[$modname][$moduleId] = $hasUserinfo;
    }

    /**
     * Store backup course mapping used by question bank and related files.
     */
    public static function setBackupCourseContext(int $courseId, int $contextId): void
    {
        self::$backupCourseId = $courseId;
        self::$backupCourseContextId = $contextId;
    }

    /**
     * Get the exported backup course id.
     */
    public static function getBackupCourseId(): int
    {
        return self::$backupCourseId;
    }

    /**
     * Get the exported backup course context id.
     */
    public static function getBackupCourseContextId(): int
    {
        return self::$backupCourseContextId;
    }

    /**
     * Build a stable backup course context id.
     *
     * This is intentionally aligned with the quiz question-bank export logic.
     */
    private function buildBackupCourseContextId(int $courseId): int
    {
        return 600000000 + max(1, $courseId);
    }

    /**
     * Robustly checks if a resource type matches a constant or any string aliases.
     *
     * @param mixed         $resourceType numeric constant or string like "quiz", "document", etc.
     * @param string        $constName    constant name, e.g. "RESOURCE_QUIZ"
     * @param array<string> $aliases      accepted string aliases
     */
    private function isType($resourceType, string $constName, array $aliases = []): bool
    {
        if (\defined($constName)) {
            $constVal = \constant($constName);
            if ($resourceType === $constVal) {
                return true;
            }
        }

        if (\is_string($resourceType)) {
            $resourceType = mb_strtolower($resourceType);
            foreach ($aliases as $alias) {
                if ($resourceType === mb_strtolower($alias)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Pull dependent resources referenced by LP items.
     */
    private function fillResourcesFromLearnpath(object $complete): void
    {
        $lpBag =
            $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
            ?? $this->course->resources['learnpath']
            ?? [];

        if (empty($lpBag) || !\is_array($lpBag)) {
            return;
        }

        foreach ($lpBag as $learnpath) {
            $lp = (\is_object($learnpath) && isset($learnpath->obj) && \is_object($learnpath->obj))
                ? $learnpath->obj
                : $learnpath;

            if (!\is_object($lp) || empty($lp->items) || !\is_array($lp->items)) {
                continue;
            }

            foreach ($lp->items as $item) {
                $type = $item['item_type'] ?? null;
                $resourceId = $item['path'] ?? null;
                if (!$type || null === $resourceId) {
                    continue;
                }

                if (isset($complete->resources[$type][$resourceId]) && !isset($this->course->resources[$type][$resourceId])) {
                    $this->course->resources[$type][$resourceId] = $complete->resources[$type][$resourceId];
                }
            }
        }
    }

    /**
     * Fill missing quiz questions when exporting a complete course.
     */
    private function fillQuestionsFromQuiz(object $complete): void
    {
        $quizResources =
            $this->course->resources[\defined('RESOURCE_QUIZ') ? RESOURCE_QUIZ : 'quiz']
            ?? $this->course->resources['quiz']
            ?? [];

        if (!\is_array($quizResources) || empty($quizResources)) {
            return;
        }

        foreach ($quizResources as $quiz) {
            if (!isset($quiz->obj->question_ids) || !\is_array($quiz->obj->question_ids)) {
                continue;
            }
            foreach ($quiz->obj->question_ids as $questionId) {
                if (isset($complete->resources['Exercise_Question'][$questionId]) && !isset($this->course->resources['Exercise_Question'][$questionId])) {
                    $this->course->resources['Exercise_Question'][$questionId] = $complete->resources['Exercise_Question'][$questionId];
                }
            }
        }
    }

    /**
     * Export all root XML files.
     *
     * @param array<int,array<string,mixed>> $activities
     */
    private function exportRootXmlFiles(string $exportDir, array $activities): void
    {
        $this->exportContextsXml($exportDir);
        $this->exportBadgesXml($exportDir);
        $this->exportCompletionXml($exportDir);
        $this->exportGradebookXml($exportDir);
        $this->exportGradeHistoryXml($exportDir);
        $this->exportGroupsXml($exportDir);
        $this->exportOutcomesXml($exportDir);

        $questionsData = [];
        foreach ($activities as $activity) {
            if ('quiz' !== ($activity['modulename'] ?? '')) {
                continue;
            }

            $quizExport = new QuizExport($this->course);
            $quizData = $quizExport->getData(
                (int) ($activity['id'] ?? 0),
                (int) ($activity['sectionid'] ?? 0),
                (int) ($activity['moduleid'] ?? 0)
            );
            $questionsData[] = $quizData;
        }
        $this->exportQuestionsXml($questionsData, $exportDir);

        $this->exportRolesXml($exportDir);
        $this->exportScalesXml($exportDir);
        $this->exportUsersXml($exportDir);
    }

    /**
     * Create the moodle_backup.xml file.
     *
     * @param array<int,array<string,mixed>> $activities
     * @param array<int,array<string,mixed>> $sections
     */
    private function createMoodleBackupXml(string $destinationDir, int $version, array $activities, array $sections): void
    {
        $courseInfo = api_get_course_info((string) ($this->course->code ?? ''));
        $backupId = md5(bin2hex(random_bytes(16)));
        $siteHash = md5(bin2hex(random_bytes(16)));
        $wwwRoot = api_get_path(WEB_PATH);

        $courseStartDate = !empty($courseInfo['creation_date'])
            ? strtotime((string) $courseInfo['creation_date'])
            : time();

        $courseEndDate = $courseStartDate + (365 * 24 * 60 * 60);

        $activitiesFlat = [];
        $seenActivities = [];

        foreach ($sections as $section) {
            foreach ((array) ($section['activities'] ?? []) as $activity) {
                $modname = (string) ($activity['modulename'] ?? '');
                $moduleId = isset($activity['moduleid']) ? (int) $activity['moduleid'] : null;

                if ('' === $modname || null === $moduleId || $moduleId < 0) {
                    continue;
                }

                $key = $modname.':'.$moduleId;
                if (isset($seenActivities[$key])) {
                    continue;
                }
                $seenActivities[$key] = true;

                $title = (string) ($activity['title'] ?? $activity['name'] ?? '');

                $activitiesFlat[] = [
                    'moduleid' => $moduleId,
                    'sectionid' => (int) ($section['id'] ?? 0),
                    'modulename' => $modname,
                    'title' => $title,
                ];
            }
        }

        // Safety fallback for real Moodle activities that were not present in sections.
        foreach ($activities as $activity) {
            $modname = (string) ($activity['modulename'] ?? '');
            if (!$this->isRealMoodleActivity($modname)) {
                continue;
            }

            $moduleId = (int) ($activity['moduleid'] ?? 0);
            if ($moduleId <= 0) {
                continue;
            }

            $key = $modname.':'.$moduleId;
            if (isset($seenActivities[$key])) {
                continue;
            }
            $seenActivities[$key] = true;

            $activitiesFlat[] = [
                'moduleid' => $moduleId,
                'sectionid' => (int) ($activity['sectionid'] ?? 0),
                'modulename' => $modname,
                'title' => (string) ($activity['title'] ?? ''),
            ];
        }

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<moodle_backup>'.PHP_EOL;
        $xmlContent .= '  <information>'.PHP_EOL;

        $xmlContent .= '    <name>backup-'.htmlspecialchars((string) ($courseInfo['code'] ?? 'course')).'.mbz</name>'.PHP_EOL;
        $xmlContent .= '    <moodle_version>'.(3 === $version ? '2021051718' : '2022041900').'</moodle_version>'.PHP_EOL;
        $xmlContent .= '    <moodle_release>'.(3 === $version ? '3.11.18 (Build: 20231211)' : '4.x version here').'</moodle_release>'.PHP_EOL;
        $xmlContent .= '    <backup_version>'.(3 === $version ? '2021051700' : '2022041900').'</backup_version>'.PHP_EOL;
        $xmlContent .= '    <backup_release>'.(3 === $version ? '3.11' : '4.x').'</backup_release>'.PHP_EOL;
        $xmlContent .= '    <backup_date>'.time().'</backup_date>'.PHP_EOL;
        $xmlContent .= '    <mnet_remoteusers>0</mnet_remoteusers>'.PHP_EOL;
        $xmlContent .= '    <include_files>1</include_files>'.PHP_EOL;
        $xmlContent .= '    <include_file_references_to_external_content>0</include_file_references_to_external_content>'.PHP_EOL;
        $xmlContent .= '    <original_wwwroot>'.$wwwRoot.'</original_wwwroot>'.PHP_EOL;
        $xmlContent .= '    <original_site_identifier_hash>'.$siteHash.'</original_site_identifier_hash>'.PHP_EOL;
        $xmlContent .= '    <original_course_id>'.htmlspecialchars((string) ($courseInfo['real_id'] ?? 0)).'</original_course_id>'.PHP_EOL;
        $xmlContent .= '    <original_course_format>'.get_lang('Topics').'</original_course_format>'.PHP_EOL;
        $xmlContent .= '    <original_course_fullname>'.htmlspecialchars((string) ($courseInfo['title'] ?? '')).'</original_course_fullname>'.PHP_EOL;
        $xmlContent .= '    <original_course_shortname>'.htmlspecialchars((string) ($courseInfo['code'] ?? '')).'</original_course_shortname>'.PHP_EOL;
        $xmlContent .= '    <original_course_startdate>'.$courseStartDate.'</original_course_startdate>'.PHP_EOL;
        $xmlContent .= '    <original_course_enddate>'.$courseEndDate.'</original_course_enddate>'.PHP_EOL;
        $xmlContent .= '    <original_course_contextid>'.self::getBackupCourseContextId().'</original_course_contextid>'.PHP_EOL;
        $xmlContent .= '    <original_system_contextid>1</original_system_contextid>'.PHP_EOL;

        $xmlContent .= '    <details>'.PHP_EOL;
        $xmlContent .= '      <detail backup_id="'.$backupId.'">'.PHP_EOL;
        $xmlContent .= '        <type>course</type>'.PHP_EOL;
        $xmlContent .= '        <format>moodle2</format>'.PHP_EOL;
        $xmlContent .= '        <interactive>1</interactive>'.PHP_EOL;
        $xmlContent .= '        <mode>10</mode>'.PHP_EOL;
        $xmlContent .= '        <execution>1</execution>'.PHP_EOL;
        $xmlContent .= '        <executiontime>0</executiontime>'.PHP_EOL;
        $xmlContent .= '      </detail>'.PHP_EOL;
        $xmlContent .= '    </details>'.PHP_EOL;

        $xmlContent .= '    <contents>'.PHP_EOL;

        if (!empty($sections)) {
            $xmlContent .= '      <sections>'.PHP_EOL;
            foreach ($sections as $section) {
                $xmlContent .= '        <section>'.PHP_EOL;
                $xmlContent .= '          <sectionid>'.(int) ($section['id'] ?? 0).'</sectionid>'.PHP_EOL;
                $xmlContent .= '          <title>'.htmlspecialchars((string) ($section['name'] ?? '')).'</title>'.PHP_EOL;
                $xmlContent .= '          <directory>sections/section_'.(int) ($section['id'] ?? 0).'</directory>'.PHP_EOL;
                $xmlContent .= '        </section>'.PHP_EOL;
            }
            $xmlContent .= '      </sections>'.PHP_EOL;
        }

        if (!empty($activitiesFlat)) {
            $xmlContent .= '      <activities>'.PHP_EOL;
            foreach ($activitiesFlat as $activity) {
                $modname = (string) $activity['modulename'];
                $moduleId = (int) $activity['moduleid'];
                $sectionId = (int) $activity['sectionid'];
                $title = (string) $activity['title'];

                $hasUserinfo = self::$activityUserinfo[$modname][$moduleId] ?? false;

                $xmlContent .= '        <activity>'.PHP_EOL;
                $xmlContent .= '          <moduleid>'.$moduleId.'</moduleid>'.PHP_EOL;
                $xmlContent .= '          <sectionid>'.$sectionId.'</sectionid>'.PHP_EOL;
                $xmlContent .= '          <modulename>'.htmlspecialchars($modname).'</modulename>'.PHP_EOL;
                $xmlContent .= '          <title>'.htmlspecialchars($title).'</title>'.PHP_EOL;
                $xmlContent .= '          <directory>activities/'.$modname.'_'.$moduleId.'</directory>'.PHP_EOL;
                $xmlContent .= '          <userinfo>'.($hasUserinfo ? '1' : '0').'</userinfo>'.PHP_EOL;
                $xmlContent .= '        </activity>'.PHP_EOL;
            }
            $xmlContent .= '      </activities>'.PHP_EOL;
        }

        $xmlContent .= '      <course>'.PHP_EOL;
        $xmlContent .= '        <courseid>'.($courseInfo['real_id'] ?? 0).'</courseid>'.PHP_EOL;
        $xmlContent .= '        <title>'.htmlspecialchars((string) ($courseInfo['title'] ?? '')).'</title>'.PHP_EOL;
        $xmlContent .= '        <directory>course</directory>'.PHP_EOL;
        $xmlContent .= '      </course>'.PHP_EOL;

        $xmlContent .= '    </contents>'.PHP_EOL;

        $xmlContent .= '    <settings>'.PHP_EOL;
        $settings = $this->exportBackupSettings($sections, $activitiesFlat);
        foreach ($settings as $setting) {
            $xmlContent .= '      <setting>'.PHP_EOL;
            $xmlContent .= '        <level>'.htmlspecialchars((string) $setting['level']).'</level>'.PHP_EOL;
            $xmlContent .= '        <name>'.htmlspecialchars((string) $setting['name']).'</name>'.PHP_EOL;
            $xmlContent .= '        <value>'.htmlspecialchars((string) $setting['value']).'</value>'.PHP_EOL;
            if (isset($setting['section'])) {
                $xmlContent .= '        <section>'.htmlspecialchars((string) $setting['section']).'</section>'.PHP_EOL;
            }
            if (isset($setting['activity'])) {
                $xmlContent .= '        <activity>'.htmlspecialchars((string) $setting['activity']).'</activity>'.PHP_EOL;
            }
            $xmlContent .= '      </setting>'.PHP_EOL;
        }
        $xmlContent .= '    </settings>'.PHP_EOL;

        $xmlContent .= '  </information>'.PHP_EOL;
        $xmlContent .= '</moodle_backup>';

        file_put_contents($destinationDir.'/moodle_backup.xml', $xmlContent);
    }

    /**
     * Builds the sections array for moodle_backup.xml and sections/* export.
     *
     * @param array<int,array<string,mixed>>|null $activities
     * @return array<int,array<string,mixed>>
     */
    private function getSections(?array $activities = null): array
    {
        if (null === $activities) {
            $activities = $this->getActivities();
        }

        $activitiesBySection = $this->groupActivitiesBySection($activities);
        $sectionExport = new SectionExport($this->course, $activitiesBySection);
        $sections = [];
        $seenSectionIds = [];

        $learnpaths = $this->getLearnpaths();
        usort(
            $learnpaths,
            fn ($a, $b): int => $this->getLearnpathSortOrder($a) <=> $this->getLearnpathSortOrder($b)
        );

        foreach ($learnpaths as $learnpath) {
            $lp = $this->unwrapLearnpath($learnpath);

            if ((int) ($lp->lp_type ?? 0) !== 1) {
                continue;
            }

            $sectionId = (int) ($lp->source_id ?? $lp->id ?? 0);
            if ($sectionId <= 0 || isset($seenSectionIds[$sectionId])) {
                continue;
            }

            $sectionData = $sectionExport->getSectionData($lp);
            $resolvedSectionId = (int) ($sectionData['id'] ?? 0);

            if ($resolvedSectionId <= 0 || isset($seenSectionIds[$resolvedSectionId])) {
                continue;
            }

            $sections[] = $sectionData;
            $seenSectionIds[$resolvedSectionId] = true;
        }

        if (!isset($seenSectionIds[0])) {
            $sections[] = [
                'id' => 0,
                'number' => 0,
                'name' => get_lang('General'),
                'summary' => get_lang('GeneralResourcesCourse'),
                'sequence' => 0,
                'visible' => 1,
                'timemodified' => time(),
                'activities' => $sectionExport->getActivitiesForGeneral(),
            ];
        }

        return $sections;
    }

    /**
     * Get all activities from the course.
     *
     * This list includes:
     * - real Moodle activities
     * - Chamilo-only metadata pseudo-activities (attendance/thematic/gradebook)
     *
     * @return array<int,array<string,mixed>>
     */
    private function getActivities(): array
    {
        @error_log('[MoodleExport::getActivities] Start');

        $activities = [];
        $orderBySection = [];

        $documents = $this->getDocumentBucket();
        if (!empty($documents)) {
            $activities[] = [
                'id' => ActivityExport::DOCS_MODULE_ID,
                'sectionid' => 0,
                'modulename' => 'folder',
                'moduleid' => ActivityExport::DOCS_MODULE_ID,
                'title' => 'Documents',
                'order' => 0,
            ];
        }

        // Build activities from LP items first to preserve LP order and unique LP-based module ids.
        $learnpaths = $this->getLearnpaths();
        usort(
            $learnpaths,
            fn ($a, $b): int => $this->getLearnpathSortOrder($a) <=> $this->getLearnpathSortOrder($b)
        );

        foreach ($learnpaths as $learnpath) {
            $lp = $this->unwrapLearnpath($learnpath);

            if ((int) ($lp->lp_type ?? 0) !== 1) {
                continue;
            }

            $sectionId = (int) ($lp->source_id ?? $lp->id ?? 0);
            if ($sectionId <= 0) {
                continue;
            }

            $items = (array) ($lp->items ?? []);
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $lpItemId = isset($item['id']) ? (int) $item['id'] : 0;
                $itemType = (string) ($item['item_type'] ?? '');
                $path = $item['path'] ?? null;
                $title = (string) ($item['title'] ?? '');
                $order = isset($item['display_order']) ? (int) $item['display_order'] : 0;

                $moduleName = null;
                $instanceId = null;

                if ('quiz' === $itemType) {
                    $moduleName = 'quiz';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ('link' === $itemType) {
                    $moduleName = 'url';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ('student_publication' === $itemType || 'work' === $itemType) {
                    $moduleName = 'assign';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ('survey' === $itemType) {
                    $moduleName = 'feedback';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ('forum' === $itemType) {
                    $moduleName = 'forum';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ('glossary' === $itemType) {
                    $moduleName = 'glossary';
                    $instanceId = 1;
                    if ('' === $title) {
                        $title = get_lang('Glossary');
                    }
                    self::flagActivityUserinfo('glossary', 1, true);
                } elseif ('document' === $itemType) {
                    $documentId = is_numeric($path) ? (int) $path : 0;
                    if ($documentId > 0) {
                        $document = DocumentManager::get_document_data_by_id(
                            $documentId,
                            (string) ($this->course->code ?? '')
                        );

                        if (!empty($document)) {
                            $documentPath = (string) ($document['path'] ?? '');
                            $extension = strtolower((string) pathinfo($documentPath, PATHINFO_EXTENSION));

                            if (in_array($extension, ['html', 'htm'], true)) {
                                $moduleName = 'page';
                                $instanceId = $documentId;
                                if ('' === $title) {
                                    $title = (string) ($document['title'] ?? '');
                                }
                            } elseif (($document['filetype'] ?? '') === 'file') {
                                $moduleName = 'resource';
                                $instanceId = $documentId;
                                if ('' === $title) {
                                    $title = (string) ($document['title'] ?? '');
                                }
                            }
                        }
                    }
                }

                if (empty($moduleName) || empty($instanceId)) {
                    continue;
                }

                $moduleId = $this->resolveLpModuleId($moduleName, $lpItemId, (int) $instanceId);

                $activities[] = [
                    'id' => (int) $instanceId,
                    'sectionid' => $sectionId,
                    'modulename' => $moduleName,
                    'moduleid' => $moduleId,
                    'title' => '' !== $title ? $title : $moduleName,
                    'order' => $order,
                ];

                if ('forum' === $moduleName) {
                    self::flagActivityUserinfo('forum', $moduleId, true);
                }
            }
        }

        // General intro page.
        $toolIntroBucket =
            $this->course->resources[\defined('RESOURCE_TOOL_INTRO') ? RESOURCE_TOOL_INTRO : 'tool_intro']
            ?? $this->course->resources['tool_intro']
            ?? [];

        if (is_array($toolIntroBucket) && isset($toolIntroBucket['course_homepage'])) {
            $activities[] = [
                'id' => 0,
                'sectionid' => 0,
                'modulename' => 'page',
                'moduleid' => ActivityExport::INTRO_PAGE_MODULE_ID,
                'title' => get_lang('Introduction'),
                'order' => 0,
            ];
        }

        // General standalone activities not linked to LP.
        $glossaryAdded = false;
        $wikiAdded = false;

        foreach ($this->course->resources as $resourceType => $resources) {
            if (!\is_array($resources) || empty($resources)) {
                continue;
            }

            foreach ($resources as $resourceId => $resource) {
                if (!\is_object($resource)) {
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_QUIZ', ['quiz'])) {
                    $quizId = (int) ($resource->obj->iid ?? 0);
                    if ($quizId > 0 && !$this->isActivityInLearnpath('quiz', $quizId)) {
                        $activities[] = [
                            'id' => $quizId,
                            'sectionid' => 0,
                            'modulename' => 'quiz',
                            'moduleid' => $quizId,
                            'title' => (string) ($resource->obj->title ?? 'Quiz'),
                            'order' => 0,
                        ];
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_LINK', ['link'])) {
                    $linkId = (int) ($resource->source_id ?? 0);
                    if ($linkId > 0 && !$this->isActivityInLearnpath('link', $linkId)) {
                        $activities[] = [
                            'id' => $linkId,
                            'sectionid' => 0,
                            'modulename' => 'url',
                            'moduleid' => $linkId,
                            'title' => (string) ($resource->title ?? ''),
                            'order' => 0,
                        ];
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_WORK', ['work', 'assign'])) {
                    $workId = (int) ($resource->source_id ?? 0);
                    if ($workId > 0 && !$this->isActivityInLearnpath('work', $workId)) {
                        $activities[] = [
                            'id' => $workId,
                            'sectionid' => 0,
                            'modulename' => 'assign',
                            'moduleid' => $workId,
                            'title' => (string) ($resource->params['title'] ?? ''),
                            'order' => 0,
                        ];
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_SURVEY', ['survey', 'feedback'])) {
                    $surveyId = (int) ($resource->source_id ?? 0);
                    if ($surveyId > 0 && !$this->isActivityInLearnpath('survey', $surveyId)) {
                        $activities[] = [
                            'id' => $surveyId,
                            'sectionid' => 0,
                            'modulename' => 'feedback',
                            'moduleid' => $surveyId,
                            'title' => (string) ($resource->params['title'] ?? ''),
                            'order' => 0,
                        ];
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_FORUM', ['forum'])) {
                    $forumId = (int) ($resource->obj->iid ?? $resource->source_id ?? 0);
                    if ($forumId > 0 && !$this->isActivityInLearnpath('forum', $forumId)) {
                        $activities[] = [
                            'id' => $forumId,
                            'sectionid' => 0,
                            'modulename' => 'forum',
                            'moduleid' => $forumId,
                            'title' => (string) ($resource->obj->forum_title ?? 'Forum'),
                            'order' => 0,
                        ];
                        self::flagActivityUserinfo('forum', $forumId, true);
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_GLOSSARY', ['glossary'])) {
                    $glossaryId = (int) ($resource->glossary_id ?? 0);
                    if ($glossaryId > 0 && !$glossaryAdded) {
                        $activities[] = [
                            'id' => 1,
                            'sectionid' => 0,
                            'modulename' => 'glossary',
                            'moduleid' => 1,
                            'title' => get_lang('Glossary'),
                            'order' => 0,
                        ];
                        self::flagActivityUserinfo('glossary', 1, true);
                        $glossaryAdded = true;
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_DOCUMENT', ['document'])) {
                    $documentId = (int) ($resource->source_id ?? 0);
                    if ($documentId <= 0) {
                        continue;
                    }

                    $documentPath = (string) ($resource->path ?? '');
                    $extension = strtolower((string) pathinfo($documentPath, PATHINFO_EXTENSION));

                    // Standalone HTML documents become general pages.
                    if (
                        !$this->isActivityInLearnpath('document', $documentId, $documentPath)
                        && in_array($extension, ['html', 'htm'], true)
                    ) {
                        $activities[] = [
                            'id' => $documentId,
                            'sectionid' => 0,
                            'modulename' => 'page',
                            'moduleid' => $documentId,
                            'title' => (string) ($resource->title ?? ''),
                            'order' => 0,
                        ];
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_COURSEDESCRIPTION', ['coursedescription', 'course_description'])) {
                    $descriptionId = (int) ($resource->source_id ?? 0);
                    if ($descriptionId > 0) {
                        $activities[] = [
                            'id' => $descriptionId,
                            'sectionid' => 0,
                            'modulename' => 'label',
                            'moduleid' => $descriptionId,
                            'title' => (string) ($resource->title ?? ''),
                            'order' => 0,
                        ];
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_WIKI', ['wiki'])) {
                    if (!$wikiAdded) {
                        $activities[] = [
                            'id' => self::WIKI_MODULE_ID,
                            'sectionid' => 0,
                            'modulename' => 'wiki',
                            'moduleid' => self::WIKI_MODULE_ID,
                            'title' => get_lang('Wiki') ?: 'Wiki',
                            'order' => 0,
                        ];
                        self::flagActivityUserinfo('wiki', self::WIKI_MODULE_ID, true);
                        $wikiAdded = true;
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_ATTENDANCE', ['attendance'])) {
                    $id = 0;
                    if (isset($resource->obj->iid) && \is_numeric($resource->obj->iid)) {
                        $id = (int) $resource->obj->iid;
                    } elseif (isset($resource->source_id) && \is_numeric($resource->source_id)) {
                        $id = (int) $resource->source_id;
                    } elseif (isset($resource->obj->id) && \is_numeric($resource->obj->id)) {
                        $id = (int) $resource->obj->id;
                    }

                    $title = '';
                    foreach (['title', 'name'] as $key) {
                        if (!empty($resource->obj->{$key}) && \is_string($resource->obj->{$key})) {
                            $title = trim((string) $resource->obj->{$key});
                            break;
                        }
                        if (!empty($resource->{$key}) && \is_string($resource->{$key})) {
                            $title = trim((string) $resource->{$key});
                            break;
                        }
                    }
                    if ('' === $title) {
                        $title = 'Attendance';
                    }

                    $activities[] = [
                        'id' => $id,
                        'sectionid' => 0,
                        'modulename' => 'attendance',
                        'moduleid' => $id,
                        'title' => $title,
                        '__from' => 'attendance',
                        'order' => 0,
                    ];
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_THEMATIC', ['thematic'])) {
                    $id = (int) ($resource->obj->iid ?? $resource->source_id ?? $resource->obj->id ?? 0);
                    if ($id > 0) {
                        $title = '';
                        foreach (['title', 'name'] as $key) {
                            if (!empty($resource->obj->{$key})) {
                                $title = trim((string) $resource->obj->{$key});
                                break;
                            }
                            if (!empty($resource->{$key})) {
                                $title = trim((string) $resource->{$key});
                                break;
                            }
                        }
                        if ('' === $title) {
                            $title = 'Thematic';
                        }

                        $activities[] = [
                            'id' => $id,
                            'sectionid' => 0,
                            'modulename' => 'thematic',
                            'moduleid' => $id,
                            'title' => $title,
                            '__from' => 'thematic',
                            'order' => 0,
                        ];
                    }
                    continue;
                }

                if ($this->isType($resourceType, 'RESOURCE_GRADEBOOK', ['gradebook'])) {
                    $activities[] = [
                        'id' => 1,
                        'sectionid' => 0,
                        'modulename' => 'gradebook',
                        'moduleid' => self::GRADEBOOK_MODULE_ID,
                        'title' => 'Gradebook',
                        '__from' => 'gradebook',
                        'order' => 0,
                    ];
                    continue;
                }
            }
        }

        // Synthetic announcements forum.
        try {
            $resources = \is_array($this->course->resources ?? null) ? $this->course->resources : [];
            $annBag =
                ($resources[\defined('RESOURCE_ANNOUNCEMENT') ? RESOURCE_ANNOUNCEMENT : 'announcements'] ?? null)
                ?? ($resources['announcements'] ?? null)
                ?? ($resources['announcement'] ?? null)
                ?? [];

            if (!empty($annBag) && !$this->hasAnnouncementsLikeForum($activities)) {
                $activities[] = [
                    'id' => 1,
                    'sectionid' => 0,
                    'modulename' => 'forum',
                    'moduleid' => self::ANNOUNCEMENTS_MODULE_ID,
                    'title' => get_lang('Announcements'),
                    '__from' => 'announcements',
                    'order' => 0,
                ];

                self::flagActivityUserinfo('forum', self::ANNOUNCEMENTS_MODULE_ID, true);
            }
        } catch (\Throwable $e) {
            @error_log('[MoodleExport::getActivities][WARN] announcements detection: '.$e->getMessage());
        }

        // Stable ordering per section.
        $grouped = [];
        foreach ($activities as $activity) {
            $sectionId = (int) ($activity['sectionid'] ?? 0);

            if (!isset($grouped[$sectionId])) {
                $grouped[$sectionId] = [];
                $orderBySection[$sectionId] = 0;
            }

            $order = (int) ($activity['order'] ?? 0);
            if ($order <= 0) {
                $orderBySection[$sectionId]++;
                $order = 1000 + $orderBySection[$sectionId];
            }

            $activity['_sort'] = $order;
            $grouped[$sectionId][] = $activity;
        }

        $sorted = [];
        foreach ($grouped as $sectionId => $list) {
            usort(
                $list,
                static fn (array $a, array $b): int => (int) $a['_sort'] <=> (int) $b['_sort']
            );

            foreach ($list as $row) {
                unset($row['_sort']);
                $sorted[] = $row;
            }
        }

        return $sorted;
    }

    /**
     * Collect Moodle URL activities from legacy link bucket.
     *
     * @return UrlExport[]
     */
    private function buildUrlActivities(): array
    {
        $resources = \is_array($this->course->resources ?? null) ? $this->course->resources : [];

        $links = $resources['link'] ?? $resources['Link'] ?? [];
        $categories = $resources['link_category'] ?? $resources['Link_Category'] ?? [];

        $categoryLabels = [];
        foreach ($categories as $categoryId => $categoryWrap) {
            if (!\is_object($categoryWrap)) {
                continue;
            }

            $category = (isset($categoryWrap->obj) && \is_object($categoryWrap->obj)) ? $categoryWrap->obj : $categoryWrap;

            $label = '';
            foreach (['title', 'name'] as $key) {
                if (!empty($category->{$key}) && \is_string($category->{$key})) {
                    $label = trim((string) $category->{$key});
                    break;
                }
            }

            $categoryLabels[(int) $categoryId] = '' !== $label ? $label : ('Category #'.(int) $categoryId);
        }

        $out = [];
        foreach ($links as $id => $linkWrap) {
            if (!\is_object($linkWrap)) {
                continue;
            }

            $link = (isset($linkWrap->obj) && \is_object($linkWrap->obj)) ? $linkWrap->obj : $linkWrap;

            $url = (string) ($link->url ?? '');
            if ('' === $url) {
                continue;
            }

            $title = '';
            foreach (['title', 'name'] as $key) {
                if (!empty($link->{$key}) && \is_string($link->{$key})) {
                    $title = trim((string) $link->{$key});
                    break;
                }
            }
            if ('' === $title) {
                $title = $url;
            }

            $target = (string) ($link->target ?? '');
            $intro = (string) ($link->description ?? '');
            $categoryId = (int) ($link->category_id ?? 0);
            $sectionName = $categoryLabels[$categoryId] ?? null;

            $urlActivity = new UrlExport($title, $url, $sectionName ?: null, $intro ?: null, $target ?: null);
            if (method_exists($urlActivity, 'setLegacyId')) {
                $urlActivity->setLegacyId((int) $id);
            }

            $out[] = $urlActivity;
        }

        return $out;
    }

    /**
     * Enqueue all URL activities into the export pipeline.
     */
    private function enqueueUrlActivities(): void
    {
        $urls = $this->buildUrlActivities();

        if (empty($urls)) {
            @error_log('[MoodleExport] No URL activities to enqueue');
            return;
        }

        if (method_exists($this, 'queueActivity')) {
            foreach ($urls as $activity) {
                $this->queueActivity($activity);
            }
            @error_log('[MoodleExport] URL activities enqueued via queueActivity(): '.count($urls));
            return;
        }

        if (method_exists($this, 'addActivity')) {
            foreach ($urls as $activity) {
                $this->addActivity($activity);
            }
            @error_log('[MoodleExport] URL activities appended via addActivity(): '.count($urls));
            return;
        }

        if (property_exists($this, 'activities') && \is_array($this->activities)) {
            array_push($this->activities, ...$urls);
            @error_log('[MoodleExport] URL activities appended to $this->activities: '.count($urls));
            return;
        }

        @error_log('[MoodleExport][WARN] Could not enqueue URL activities (no compatible method found)');
    }

    /**
     * Export the course sections.
     *
     * @param array<int,array<string,mixed>> $activities
     * @param array<int,array<string,mixed>> $sections
     */
    private function exportSections(string $exportDir, array $activities, array $sections): void
    {
        $activitiesBySection = $this->groupActivitiesBySection($activities);
        $sectionExport = new SectionExport($this->course, $activitiesBySection);

        foreach ($sections as $section) {
            $sectionExport->exportSection((int) ($section['id'] ?? 0), $exportDir);
        }
    }

    /**
     * Group only real Moodle activities by section.
     *
     * @param array<int,array<string,mixed>> $activities
     * @return array<int,array<int,array<string,mixed>>>
     */
    private function groupActivitiesBySection(array $activities): array
    {
        $bySection = [];

        foreach ($activities as $activity) {
            $moduleName = (string) ($activity['modulename'] ?? '');
            if (!$this->isRealMoodleActivity($moduleName)) {
                continue;
            }

            $sectionId = (int) ($activity['sectionid'] ?? 0);

            $bySection[$sectionId][] = [
                'id' => (int) ($activity['id'] ?? 0),
                'moduleid' => (int) ($activity['moduleid'] ?? 0),
                'modulename' => $moduleName,
                'name' => (string) ($activity['title'] ?? ''),
                'title' => (string) ($activity['title'] ?? ''),
                'sectionid' => $sectionId,
            ];
        }

        return $bySection;
    }

    /**
     * Returns true for real Moodle activities that must be part of sections and moodle_backup.xml.
     */
    private function isRealMoodleActivity(string $moduleName): bool
    {
        return in_array(
            $moduleName,
            ['folder', 'quiz', 'glossary', 'url', 'assign', 'forum', 'page', 'resource', 'feedback', 'label', 'wiki'],
            true
        );
    }

    /**
     * Determine whether a legacy activity is already linked inside a learnpath.
     */
    private function isActivityInLearnpath(string $itemType, int $resourceId, ?string $documentPath = null): bool
    {
        $learnpaths = $this->getLearnpaths();
        if (empty($learnpaths)) {
            return false;
        }

        $needleType = $this->normalizeItemTypeForLpComparison($itemType);

        foreach ($learnpaths as $learnpath) {
            $lp = $this->unwrapLearnpath($learnpath);

            if (empty($lp->items) || !is_array($lp->items)) {
                continue;
            }

            foreach ($lp->items as $item) {
                $lpType = $this->normalizeItemTypeForLpComparison((string) ($item['item_type'] ?? ''));
                if ($lpType !== $needleType) {
                    continue;
                }

                $lpPath = (string) ($item['path'] ?? '');
                if ('' !== $lpPath && ctype_digit($lpPath) && (int) $lpPath === $resourceId) {
                    return true;
                }

                if ('document' === $needleType && null !== $documentPath) {
                    foreach ($this->buildDocumentLpCandidates($documentPath) as $candidate) {
                        if ($lpPath === $candidate) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Normalize LP item types for comparisons.
     */
    private function normalizeItemTypeForLpComparison(string $type): string
    {
        return match ($type) {
            'student_publication', 'work', 'assign' => 'work',
            'link', 'url' => 'link',
            'survey', 'feedback' => 'survey',
            'page', 'resource' => 'document',
            default => $type,
        };
    }

    /**
     * Resolve the exported module id for an LP occurrence.
     */
    private function resolveLpModuleId(string $moduleName, int $lpItemId, int $fallback): int
    {
        if ($lpItemId <= 0) {
            return $fallback;
        }

        if (in_array($moduleName, ['folder', 'glossary'], true)) {
            return $fallback;
        }

        return 900000000 + $lpItemId;
    }

    /**
     * Get learnpaths defensively.
     *
     * @return array<int,mixed>
     */
    private function getLearnpaths(): array
    {
        $learnpaths =
            $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
            ?? $this->course->resources['learnpath']
            ?? [];

        return is_array($learnpaths) ? $learnpaths : [];
    }

    /**
     * Get the document bucket defensively.
     *
     * @return array<int,mixed>
     */
    private function getDocumentBucket(): array
    {
        $documents =
            $this->course->resources[\defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : 'document']
            ?? $this->course->resources['document']
            ?? [];

        return is_array($documents) ? $documents : [];
    }


    private function exportDocumentIndex(string $exportDir): void
    {
        $documents = $this->getDocumentBucket();
        if (empty($documents)) {
            return;
        }

        $hashById = $this->buildExportedDocumentHashMap($exportDir);

        $indexDir = rtrim($exportDir, '/').'/chamilo/document';
        if (!is_dir($indexDir) && !@mkdir($indexDir, api_get_permissions_for_new_directories(), true) && !is_dir($indexDir)) {
            @error_log('[MoodleExport::exportDocumentIndex] ERROR cannot create '.$indexDir);

            return;
        }

        $entries = [];
        $seen = [];

        foreach ($documents as $id => $wrap) {
            if (!\is_object($wrap)) {
                continue;
            }

            $doc = (isset($wrap->obj) && \is_object($wrap->obj)) ? $wrap->obj : $wrap;

            $rawPath = (string) ($doc->path ?? $wrap->path ?? '');
            if ('' === $rawPath) {
                continue;
            }

            $fileType = strtolower((string) ($doc->file_type ?? $doc->filetype ?? $wrap->file_type ?? $wrap->filetype ?? ''));
            $isFolder = 'folder' === $fileType || '/' === substr($rawPath, -1);

            $relativePath = $this->normalizeDocumentIndexRelativePath($rawPath);
            if ('' === $relativePath) {
                continue;
            }

            $title = trim((string) ($doc->title ?? $wrap->title ?? ''));
            $cleanPath = $this->buildCleanDocumentExportPath($relativePath, $title, $isFolder);
            if ('' === $cleanPath) {
                continue;
            }

            $key = ($isFolder ? 'folder:' : 'file:').$cleanPath;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            if ('' === $title) {
                $title = basename($cleanPath);
            }

            $parentPath = trim((string) dirname($cleanPath), '.');
            if ('.' === $parentPath) {
                $parentPath = '';
            }

            $entry = [
                'id' => (int) ($doc->iid ?? $wrap->source_id ?? $id),
                'source_id' => (int) ($wrap->source_id ?? $doc->iid ?? $id),
                'file_type' => $isFolder ? 'folder' : 'file',
                'path' => $cleanPath,
                'title' => $title,
                'comment' => (string) ($doc->comment ?? $wrap->comment ?? ''),
                'size' => (int) ($doc->size ?? $wrap->size ?? 0),
                'parent_path' => $parentPath,
            ];

            if (!$isFolder) {
                $lookupId = (int) ($wrap->source_id ?? $doc->iid ?? $id);
                $contentHash = (string) ($hashById[$lookupId] ?? '');

                // Only keep file entries that actually have a blob inside files/.
                if ('' === $contentHash) {
                    continue;
                }

                $entry['contenthash'] = $contentHash;
            }

            $entries[] = $entry;
        }

        usort(
            $entries,
            static function (array $a, array $b): int {
                if (($a['file_type'] ?? '') !== ($b['file_type'] ?? '')) {
                    return 'folder' === ($a['file_type'] ?? '') ? -1 : 1;
                }

                return strcmp((string) ($a['path'] ?? ''), (string) ($b['path'] ?? ''));
            }
        );

        file_put_contents(
            $indexDir.'/index.json',
            json_encode(
                [
                    'generated_at' => date('c'),
                    'course_code' => (string) ($this->course->code ?? ''),
                    'documents' => $entries,
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }

    private function buildExportedDocumentHashMap(string $exportDir): array
    {
        $filesXml = rtrim($exportDir, '/').'/files.xml';
        if (!is_file($filesXml)) {
            return [];
        }

        $xml = @simplexml_load_file($filesXml);
        if (false === $xml) {
            @error_log('[MoodleExport::buildExportedDocumentHashMap] ERROR cannot read files.xml');

            return [];
        }

        $map = [];

        foreach ($xml->file as $file) {
            $id = (int) ($file['id'] ?? 0);
            $hash = trim((string) ($file->contenthash ?? ''));

            if ($id > 0 && '' !== $hash) {
                $map[$id] = $hash;
            }
        }

        return $map;
    }

    private function normalizeDocumentIndexRelativePath(string $rawPath): string
    {
        $path = trim(str_replace('\\', '/', $rawPath));
        $path = preg_replace('#/+#', '/', $path);
        $path = trim((string) $path, '/');

        if ('' === $path || '.' === $path) {
            return '';
        }

        $segments = array_values(array_filter(
            explode('/', $path),
            static fn ($part) => '' !== $part
        ));

        if (empty($segments)) {
            return '';
        }

        while (!empty($segments) && 0 === strcasecmp((string) $segments[0], 'document')) {
            array_shift($segments);
        }

        if (
            !empty($segments) &&
            preg_match('~^(localhost(?:-\d+)?|127(?:\.\d+){3}|[a-z0-9.-]+\.[a-z]{2,})$~i', (string) $segments[0])
        ) {
            array_shift($segments);
        }

        $courseCode = (string) ($this->course->code ?? '');
        if ('' !== $courseCode && !empty($segments)) {
            $first = (string) $segments[0];
            if (
                0 === strcasecmp($first, $courseCode) ||
                str_starts_with(strtolower($first), strtolower($courseCode).'-')
            ) {
                array_shift($segments);
            }
        }

        foreach ($segments as $index => $segment) {
            $hasExtension = '' !== (string) pathinfo($segment, PATHINFO_EXTENSION);
            if (!$hasExtension && preg_match('~^(.+)-\d{3,}$~', $segment, $matches)) {
                $segments[$index] = (string) $matches[1];
            }
        }

        $segments = array_values(array_filter(
            $segments,
            static fn ($part) => '' !== trim((string) $part)
        ));

        if (empty($segments)) {
            return '';
        }

        return implode('/', $segments);
    }

    private function buildCleanDocumentExportPath(string $relativePath, string $title, bool $isFolder): string
    {
        $segments = array_values(array_filter(
            explode('/', trim($relativePath, '/')),
            static fn ($part) => '' !== trim((string) $part)
        ));

        if (empty($segments)) {
            return '';
        }

        $lastIndex = \count($segments) - 1;

        foreach ($segments as $index => $segment) {
            $segment = trim(str_replace('\\', '/', (string) $segment), '/');
            $segment = preg_replace('#\s+#', ' ', $segment) ?? $segment;
            $segments[$index] = basename($segment);
        }

        $cleanTitle = basename(trim(str_replace('\\', '/', $title), '/'));

        if ($isFolder) {
            if ('' !== $cleanTitle) {
                $segments[$lastIndex] = $cleanTitle;
            }
        } else {
            $originalFileName = $segments[$lastIndex];
            $titleExt = (string) pathinfo($cleanTitle, PATHINFO_EXTENSION);

            // For files, prefer the document title when it already looks like a real filename.
            // This removes technical suffixes such as "-23450" from exported paths.
            if ('' !== $cleanTitle && '' !== $titleExt) {
                $segments[$lastIndex] = $cleanTitle;
            } else {
                $segments[$lastIndex] = $originalFileName;
            }
        }

        $segments = array_values(array_filter(
            $segments,
            static fn ($part) => '' !== trim((string) $part)
        ));

        if (empty($segments)) {
            return '';
        }

        return trim(implode('/', $segments), '/');
    }

    private function resolveSourceDocumentAbsolutePath(object $doc): ?string
    {
        if (!method_exists($doc, 'getResourceNode')) {
            return null;
        }

        $node = $doc->getResourceNode();
        if (!$node || !method_exists($node, 'getResourceFiles')) {
            return null;
        }

        $files = $node->getResourceFiles();
        if (!$files || 0 === $files->count()) {
            return null;
        }

        $first = $files->first();
        if (!$first instanceof ResourceFile) {
            return null;
        }

        $file = $first->getFile();
        if (!$file) {
            return null;
        }

        $pathname = $file->getPathname();
        if ('' === $pathname || !is_file($pathname)) {
            return null;
        }

        return $pathname;
    }

    /**
     * Unwrap a learnpath wrapper into the effective learnpath object.
     */
    private function unwrapLearnpath($learnpath): object
    {
        if (\is_object($learnpath) && isset($learnpath->obj) && \is_object($learnpath->obj)) {
            return $learnpath->obj;
        }

        return \is_object($learnpath) ? $learnpath : (object) [];
    }

    /**
     * Resolve the display order of a learnpath, wrapper-safe.
     */
    private function getLearnpathSortOrder($learnpath): int
    {
        $lp = $this->unwrapLearnpath($learnpath);

        return (int) ($lp->display_order ?? 0);
    }

    /**
     * Build normalized document path candidates for LP comparisons.
     *
     * @return array<int,string>
     */
    private function buildDocumentLpCandidates(string $documentPath): array
    {
        $normalized = ltrim(str_replace('\\', '/', $documentPath), '/');
        $normalized = (string) preg_replace('#^document/#', '', $normalized);

        return array_values(array_unique([
            $normalized,
            '/'.$normalized,
            'document/'.$normalized,
            '/document/'.$normalized,
        ]));
    }

    /**
     * Create a .mbz file from the exported directory.
     */
    private function createMbzFile(string $sourceDir): string
    {
        $zip = new ZipArchive();
        $zipFile = $sourceDir.'.mbz';

        if (true !== $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new Exception(get_lang('Error creating zip file'));
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr((string) $filePath, \strlen($sourceDir) + 1);

                if (!$zip->addFile((string) $filePath, $relativePath)) {
                    throw new Exception(get_lang('Error adding file to zip').": $relativePath");
                }
            }
        }

        if (!$zip->close()) {
            throw new Exception(get_lang('Error closing zip file'));
        }

        return $zipFile;
    }

    /**
     * Clean up the temporary export directory.
     */
    private function cleanupTempDir(string $dir): void
    {
        $this->recursiveDelete($dir);
    }

    /**
     * Recursively delete a directory and its contents.
     */
    private function recursiveDelete(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Export Gradebook metadata into chamilo/gradebook/*.json.
     */
    private function exportGradebookActivities(array $activities, string $exportDir): void
    {
        $count = 0;
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'gradebook') {
                continue;
            }
            $activityId = (int) ($activity['id'] ?? 0);
            $moduleId = (int) ($activity['moduleid'] ?? self::GRADEBOOK_MODULE_ID);
            $sectionId = (int) ($activity['sectionid'] ?? 0);

            try {
                $meta = new GradebookMetaExport($this->course);
                $meta->export($activityId, $exportDir, $moduleId, $sectionId);

                self::flagActivityUserinfo('gradebook', $moduleId, false);
                $count++;
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportGradebookActivities][ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * Export raw learnpath metadata as JSON sidecars.
     */
    private function exportLearnpathMeta(string $exportDir): void
    {
        try {
            $meta = new LearnpathMetaExport($this->course);
            $count = $meta->exportAll($exportDir);
        } catch (\Throwable $e) {
            @error_log('[MoodleExport::exportLearnpathMeta][ERROR] '.$e->getMessage());
        }
    }

    /**
     * Export quiz raw JSON sidecars.
     */
    private function exportQuizMetaActivities(array $activities, string $exportDir): void
    {
        $count = 0;

        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'quiz') {
                continue;
            }

            $activityId = (int) ($activity['id'] ?? 0);
            $moduleId = (int) ($activity['moduleid'] ?? 0);
            $sectionId = (int) ($activity['sectionid'] ?? 0);

            if ($activityId <= 0 || $moduleId <= 0) {
                continue;
            }

            try {
                $meta = new QuizMetaExport($this->course);
                $meta->export($activityId, $exportDir, $moduleId, $sectionId);
                $count++;
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportQuizMetaActivities][ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * Export Attendance metadata into chamilo/attendance/*.json.
     */
    private function exportAttendanceActivities(array $activities, string $exportDir): void
    {
        $count = 0;

        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'attendance') {
                continue;
            }

            $activityId = (int) ($activity['id'] ?? 0);
            $moduleId = (int) ($activity['moduleid'] ?? 0);
            $sectionId = (int) ($activity['sectionid'] ?? 0);

            if ($activityId <= 0 || $moduleId <= 0) {
                continue;
            }

            try {
                $meta = new AttendanceMetaExport($this->course);
                $meta->export($activityId, $exportDir, $moduleId, $sectionId);

                self::flagActivityUserinfo('attendance', $moduleId, false);
                $count++;
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportAttendanceActivities][ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * Export Label activities.
     */
    private function exportLabelActivities(array $activities, string $exportDir): void
    {
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'label') {
                continue;
            }

            $activityId = (int) ($activity['id'] ?? 0);
            $moduleId = (int) ($activity['moduleid'] ?? 0);
            $sectionId = (int) ($activity['sectionid'] ?? 0);

            try {
                $label = new LabelExport($this->course);
                $label->export($activityId, $exportDir, $moduleId, $sectionId);
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportLabelActivities][ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * Export thematic metadata.
     */
    private function exportThematicActivities(array $activities, string $exportDir): void
    {
        $count = 0;

        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'thematic') {
                continue;
            }

            $activityId = (int) ($activity['id'] ?? 0);
            $moduleId = (int) ($activity['moduleid'] ?? 0);
            $sectionId = (int) ($activity['sectionid'] ?? 0);

            if ($activityId <= 0 || $moduleId <= 0) {
                continue;
            }

            try {
                $meta = new ThematicMetaExport($this->course);
                $meta->export($activityId, $exportDir, $moduleId, $sectionId);

                self::flagActivityUserinfo('thematic', $moduleId, false);
                $count++;
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportThematicActivities][ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * Export wiki activities.
     */
    private function exportWikiActivities(array $activities, string $exportDir): void
    {
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'wiki') {
                continue;
            }

            $activityId = (int) ($activity['id'] ?? 0);
            $moduleId = (int) ($activity['moduleid'] ?? 0);
            $sectionId = (int) ($activity['sectionid'] ?? 0);

            if ($activityId <= 0 || $moduleId <= 0) {
                continue;
            }

            try {
                $wiki = new WikiExport($this->course);
                $wiki->export($activityId, $exportDir, $moduleId, $sectionId);
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportWikiActivities][ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * Export synthetic News forum built from announcements.
     */
    private function exportAnnouncementsForum(array $activities, string $exportDir): void
    {
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'forum') {
                continue;
            }
            if (($activity['__from'] ?? '') !== 'announcements') {
                continue;
            }

            $activityId = (int) ($activity['id'] ?? 0);
            $moduleId = (int) ($activity['moduleid'] ?? 0);
            $sectionId = (int) ($activity['sectionid'] ?? 0);

            try {
                $forum = new AnnouncementsForumExport($this->course);
                $forum->export($activityId, $exportDir, $moduleId, $sectionId);
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportAnnouncementsForum][ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * Export course-level calendar events to course/calendarevents.xml.
     */
    private function exportCourseCalendar(string $exportDir): void
    {
        try {
            $calendar = new CourseCalendarExport($this->course);
            $count = $calendar->export($exportDir);
        } catch (\Throwable $e) {
            @error_log('[MoodleExport::exportCourseCalendar][ERROR] '.$e->getMessage());
        }
    }

    /**
     * Export minimal contexts required by Moodle restore.
     */
    private function exportContextsXml(string $exportDir): void
    {
        $courseId = self::getBackupCourseId();
        $courseContextId = self::getBackupCourseContextId();
        $activities = $this->getActivities();

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<contexts>'.PHP_EOL;

        // System context
        $xmlContent .= '  <context id="1">'.PHP_EOL;
        $xmlContent .= '    <contextlevel>10</contextlevel>'.PHP_EOL;
        $xmlContent .= '    <instanceid>0</instanceid>'.PHP_EOL;
        $xmlContent .= '    <path>/1</path>'.PHP_EOL;
        $xmlContent .= '    <depth>1</depth>'.PHP_EOL;
        $xmlContent .= '    <parentcontextid>0</parentcontextid>'.PHP_EOL;
        $xmlContent .= '  </context>'.PHP_EOL;

        // Course context
        $xmlContent .= '  <context id="'.$courseContextId.'">'.PHP_EOL;
        $xmlContent .= '    <contextlevel>50</contextlevel>'.PHP_EOL;
        $xmlContent .= '    <instanceid>'.$courseId.'</instanceid>'.PHP_EOL;
        $xmlContent .= '    <path>/1/'.$courseContextId.'</path>'.PHP_EOL;
        $xmlContent .= '    <depth>2</depth>'.PHP_EOL;
        $xmlContent .= '    <parentcontextid>1</parentcontextid>'.PHP_EOL;
        $xmlContent .= '  </context>'.PHP_EOL;

        // Module contexts
        $seen = [];
        foreach ($activities as $activity) {
            $moduleId = (int) ($activity['moduleid'] ?? 0);
            if ($moduleId <= 0) {
                continue;
            }
            if (isset($seen[$moduleId])) {
                continue;
            }
            $seen[$moduleId] = true;

            $contextId = $moduleId;

            $xmlContent .= '  <context id="'.$contextId.'">'.PHP_EOL;
            $xmlContent .= '    <contextlevel>70</contextlevel>'.PHP_EOL;
            $xmlContent .= '    <instanceid>'.$moduleId.'</instanceid>'.PHP_EOL;
            $xmlContent .= '    <path>/1/'.$courseContextId.'/'.$contextId.'</path>'.PHP_EOL;
            $xmlContent .= '    <depth>3</depth>'.PHP_EOL;
            $xmlContent .= '    <parentcontextid>'.$courseContextId.'</parentcontextid>'.PHP_EOL;
            $xmlContent .= '  </context>'.PHP_EOL;
        }

        $xmlContent .= '</contexts>'.PHP_EOL;

        file_put_contents($exportDir.'/contexts.xml', $xmlContent);
    }

    private function exportBadgesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<badges>'.PHP_EOL;
        $xmlContent .= '</badges>';
        file_put_contents($exportDir.'/badges.xml', $xmlContent);
    }

    private function exportCompletionXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<completions>'.PHP_EOL;
        $xmlContent .= '</completions>';
        file_put_contents($exportDir.'/completion.xml', $xmlContent);
    }

    private function exportGradebookXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<gradebook>'.PHP_EOL;
        $xmlContent .= '</gradebook>';
        file_put_contents($exportDir.'/gradebook.xml', $xmlContent);
    }

    private function exportGradeHistoryXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<grade_history>'.PHP_EOL;
        $xmlContent .= '</grade_history>';
        file_put_contents($exportDir.'/grade_history.xml', $xmlContent);
    }

    private function exportGroupsXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<groups>'.PHP_EOL;
        $xmlContent .= '</groups>';
        file_put_contents($exportDir.'/groups.xml', $xmlContent);
    }

    private function exportOutcomesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<outcomes>'.PHP_EOL;
        $xmlContent .= '</outcomes>';
        file_put_contents($exportDir.'/outcomes.xml', $xmlContent);
    }

    private function exportRolesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<roles_definition>'.PHP_EOL;
        $xmlContent .= '  <role id="5">'.PHP_EOL;
        $xmlContent .= '    <name></name>'.PHP_EOL;
        $xmlContent .= '    <shortname>student</shortname>'.PHP_EOL;
        $xmlContent .= '    <nameincourse>$@NULL@$</nameincourse>'.PHP_EOL;
        $xmlContent .= '    <description></description>'.PHP_EOL;
        $xmlContent .= '    <sortorder>5</sortorder>'.PHP_EOL;
        $xmlContent .= '    <archetype>student</archetype>'.PHP_EOL;
        $xmlContent .= '  </role>'.PHP_EOL;
        $xmlContent .= '</roles_definition>'.PHP_EOL;

        file_put_contents($exportDir.'/roles.xml', $xmlContent);
    }

    private function exportScalesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<scales>'.PHP_EOL;
        $xmlContent .= '</scales>';
        file_put_contents($exportDir.'/scales.xml', $xmlContent);
    }

    private function exportUsersXml(string $exportDir): void
    {
        $adminData = self::getAdminUserData();

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<users>'.PHP_EOL;
        $xmlContent .= '  <user id="'.$adminData['id'].'" contextid="'.$adminData['contextid'].'">'.PHP_EOL;
        $xmlContent .= '    <username>'.$adminData['username'].'</username>'.PHP_EOL;
        $xmlContent .= '    <idnumber>'.$adminData['idnumber'].'</idnumber>'.PHP_EOL;
        $xmlContent .= '    <email>'.$adminData['email'].'</email>'.PHP_EOL;
        $xmlContent .= '    <phone1>'.$adminData['phone1'].'</phone1>'.PHP_EOL;
        $xmlContent .= '    <phone2>'.$adminData['phone2'].'</phone2>'.PHP_EOL;
        $xmlContent .= '    <institution>'.$adminData['institution'].'</institution>'.PHP_EOL;
        $xmlContent .= '    <department>'.$adminData['department'].'</department>'.PHP_EOL;
        $xmlContent .= '    <address>'.$adminData['address'].'</address>'.PHP_EOL;
        $xmlContent .= '    <city>'.$adminData['city'].'</city>'.PHP_EOL;
        $xmlContent .= '    <country>'.$adminData['country'].'</country>'.PHP_EOL;
        $xmlContent .= '    <lastip>'.$adminData['lastip'].'</lastip>'.PHP_EOL;
        $xmlContent .= '    <picture>'.$adminData['picture'].'</picture>'.PHP_EOL;
        $xmlContent .= '    <description>'.$adminData['description'].'</description>'.PHP_EOL;
        $xmlContent .= '    <descriptionformat>'.$adminData['descriptionformat'].'</descriptionformat>'.PHP_EOL;
        $xmlContent .= '    <imagealt>'.$adminData['imagealt'].'</imagealt>'.PHP_EOL;
        $xmlContent .= '    <auth>'.$adminData['auth'].'</auth>'.PHP_EOL;
        $xmlContent .= '    <firstname>'.$adminData['firstname'].'</firstname>'.PHP_EOL;
        $xmlContent .= '    <lastname>'.$adminData['lastname'].'</lastname>'.PHP_EOL;
        $xmlContent .= '    <confirmed>'.$adminData['confirmed'].'</confirmed>'.PHP_EOL;
        $xmlContent .= '    <policyagreed>'.$adminData['policyagreed'].'</policyagreed>'.PHP_EOL;
        $xmlContent .= '    <deleted>'.$adminData['deleted'].'</deleted>'.PHP_EOL;
        $xmlContent .= '    <lang>'.$adminData['lang'].'</lang>'.PHP_EOL;
        $xmlContent .= '    <theme>'.$adminData['theme'].'</theme>'.PHP_EOL;
        $xmlContent .= '    <timezone>'.$adminData['timezone'].'</timezone>'.PHP_EOL;
        $xmlContent .= '    <firstaccess>'.$adminData['firstaccess'].'</firstaccess>'.PHP_EOL;
        $xmlContent .= '    <lastaccess>'.$adminData['lastaccess'].'</lastaccess>'.PHP_EOL;
        $xmlContent .= '    <lastlogin>'.$adminData['lastlogin'].'</lastlogin>'.PHP_EOL;
        $xmlContent .= '    <currentlogin>'.$adminData['currentlogin'].'</currentlogin>'.PHP_EOL;
        $xmlContent .= '    <mailformat>'.$adminData['mailformat'].'</mailformat>'.PHP_EOL;
        $xmlContent .= '    <maildigest>'.$adminData['maildigest'].'</maildigest>'.PHP_EOL;
        $xmlContent .= '    <maildisplay>'.$adminData['maildisplay'].'</maildisplay>'.PHP_EOL;
        $xmlContent .= '    <autosubscribe>'.$adminData['autosubscribe'].'</autosubscribe>'.PHP_EOL;
        $xmlContent .= '    <trackforums>'.$adminData['trackforums'].'</trackforums>'.PHP_EOL;
        $xmlContent .= '    <timecreated>'.$adminData['timecreated'].'</timecreated>'.PHP_EOL;
        $xmlContent .= '    <timemodified>'.$adminData['timemodified'].'</timemodified>'.PHP_EOL;
        $xmlContent .= '    <trustbitmask>'.$adminData['trustbitmask'].'</trustbitmask>'.PHP_EOL;

        if (isset($adminData['preferences']) && \is_array($adminData['preferences'])) {
            $xmlContent .= '    <preferences>'.PHP_EOL;
            foreach ($adminData['preferences'] as $preference) {
                $xmlContent .= '      <preference>'.PHP_EOL;
                $xmlContent .= '        <name>'.htmlspecialchars((string) $preference['name']).'</name>'.PHP_EOL;
                $xmlContent .= '        <value>'.htmlspecialchars((string) $preference['value']).'</value>'.PHP_EOL;
                $xmlContent .= '      </preference>'.PHP_EOL;
            }
            $xmlContent .= '    </preferences>'.PHP_EOL;
        } else {
            $xmlContent .= '    <preferences></preferences>'.PHP_EOL;
        }

        $xmlContent .= '    <roles>'.PHP_EOL;
        $xmlContent .= '      <role_overrides></role_overrides>'.PHP_EOL;
        $xmlContent .= '      <role_assignments></role_assignments>'.PHP_EOL;
        $xmlContent .= '    </roles>'.PHP_EOL;
        $xmlContent .= '  </user>'.PHP_EOL;
        $xmlContent .= '</users>';

        file_put_contents($exportDir.'/users.xml', $xmlContent);
    }

    /**
     * Export backup settings.
     *
     * @param array<int,array<string,mixed>> $sections
     * @param array<int,array<string,mixed>> $activities
     * @return array<int,array<string,string|int>>
     */
    private function exportBackupSettings(array $sections, array $activities): array
    {
        $settings = [
            ['level' => 'root', 'name' => 'filename', 'value' => 'backup-moodle-course-'.time().'.mbz'],
            ['level' => 'root', 'name' => 'imscc11', 'value' => '0'],
            ['level' => 'root', 'name' => 'users', 'value' => '1'],
            ['level' => 'root', 'name' => 'anonymize', 'value' => '0'],
            ['level' => 'root', 'name' => 'role_assignments', 'value' => '1'],
            ['level' => 'root', 'name' => 'activities', 'value' => '1'],
            ['level' => 'root', 'name' => 'blocks', 'value' => '1'],
            ['level' => 'root', 'name' => 'files', 'value' => '1'],
            ['level' => 'root', 'name' => 'filters', 'value' => '1'],
            ['level' => 'root', 'name' => 'comments', 'value' => '1'],
            ['level' => 'root', 'name' => 'badges', 'value' => '1'],
            ['level' => 'root', 'name' => 'calendarevents', 'value' => '1'],
            ['level' => 'root', 'name' => 'userscompletion', 'value' => '1'],
            ['level' => 'root', 'name' => 'logs', 'value' => '0'],
            ['level' => 'root', 'name' => 'grade_histories', 'value' => '0'],
            ['level' => 'root', 'name' => 'questionbank', 'value' => '1'],
            ['level' => 'root', 'name' => 'groups', 'value' => '1'],
            ['level' => 'root', 'name' => 'competencies', 'value' => '0'],
            ['level' => 'root', 'name' => 'customfield', 'value' => '1'],
            ['level' => 'root', 'name' => 'contentbankcontent', 'value' => '1'],
            ['level' => 'root', 'name' => 'legacyfiles', 'value' => '1'],
        ];

        foreach ($sections as $section) {
            $settings[] = [
                'level' => 'section',
                'section' => 'section_'.$section['id'],
                'name' => 'section_'.$section['id'].'_included',
                'value' => '1',
            ];
            $settings[] = [
                'level' => 'section',
                'section' => 'section_'.$section['id'],
                'name' => 'section_'.$section['id'].'_userinfo',
                'value' => '1',
            ];
        }

        foreach ($activities as $activity) {
            $settings[] = [
                'level' => 'activity',
                'activity' => $activity['modulename'].'_'.$activity['moduleid'],
                'name' => $activity['modulename'].'_'.$activity['moduleid'].'_included',
                'value' => '1',
            ];

            $value = (self::$activityUserinfo[$activity['modulename']][$activity['moduleid']] ?? false) ? '1' : '0';
            $settings[] = [
                'level' => 'activity',
                'activity' => $activity['modulename'].'_'.$activity['moduleid'],
                'name' => $activity['modulename'].'_'.$activity['moduleid'].'_userinfo',
                'value' => $value,
            ];
        }

        return $settings;
    }

    /**
     * Returns true if an existing forum already looks like announcements/news.
     *
     * @param array<int,array<string,mixed>> $activities
     */
    private function hasAnnouncementsLikeForum(array $activities): bool
    {
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'forum') {
                continue;
            }

            $title = mb_strtolower((string) ($activity['title'] ?? ''));
            foreach (['announcements', 'news'] as $keyword) {
                if ($title === $keyword || str_contains($title, $keyword)) {
                    return true;
                }
            }
        }

        return false;
    }
}
