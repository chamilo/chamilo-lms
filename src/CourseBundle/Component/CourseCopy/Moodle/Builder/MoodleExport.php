<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

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
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * Class MoodleExport.
 * Handles the export of a Moodle course in .mbz format.
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
    private static $adminUserData = [];

    /**
     * @var bool selection flag (true when exporting only selected items)
     */
    private bool $selectionMode = false;

    protected static array $activityUserinfo = [];

    /** Synthetic module id for the News forum generated from announcements */
    private const ANNOUNCEMENTS_MODULE_ID = 48000001;

    /** Synthetic module id for Gradebook (Chamilo-only metadata) */
    private const GRADEBOOK_MODULE_ID = 48000002;

    private bool $wikiAdded = false;
    private const WIKI_MODULE_ID = 48000003;

    /**
     * Constructor to initialize the course object.
     *
     * @param object $course        Filtered legacy course (may be full or selected-only)
     * @param bool   $selectionMode When true, do NOT re-hydrate from complete snapshot
     */
    public function __construct(object $course, bool $selectionMode = false)
    {
        // Keep the provided (possibly filtered) course as-is.
        $this->course = $course;
        $this->selectionMode = $selectionMode;

        // Only auto-fill missing dependencies when doing a full export.
        // In selection mode we must not re-inject extra content ("full backup" effect).
        if (!$this->selectionMode) {
            $cb = new CourseBuilder('complete');
            $complete = $cb->build(0, (string) ($course->code ?? ''));

            // Fill missing resources from learnpath (full export only)
            $this->fillResourcesFromLearnpath($complete);

            // Fill missing quiz questions (full export only)
            $this->fillQuestionsFromQuiz($complete);
        }
    }

    /**
     * Export the Moodle course in .mbz format.
     *
     * @return string Path to the created .mbz file
     */
    public function export(string $courseId, string $exportDir, int $version)
    {
        @error_log('[MoodleExport::export] Start. courseId='.$courseId.' exportDir='.$exportDir.' version='.$version);

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

        // Create Moodle backup skeleton (backup.xml + dirs)
        $this->createMoodleBackupXml($tempDir, $version);
        @error_log('[MoodleExport::export] moodle_backup.xml generated');

        //    This must happen BEFORE calling getActivities() so they are included.
        if (method_exists($this, 'enqueueUrlActivities')) {
            @error_log('[MoodleExport::export] Enqueuing URL activities …');
            $this->enqueueUrlActivities();
            @error_log('[MoodleExport::export] URL activities enqueued');
        } else {
            @error_log('[MoodleExport::export][WARN] enqueueUrlActivities() not found; skipping URL activities');
        }

        // Gather activities (now includes URLs)
        $activities = $this->getActivities();
        @error_log('[MoodleExport::export] Activities count='.count($activities));

        // Export course structure (sections + activities metadata)
        $courseExport = new CourseExport($this->course, $activities);
        $courseExport->exportCourse($tempDir);
        @error_log('[MoodleExport::export] course/ exported');

        // Page export (collect extra files from HTML pages)
        $pageExport = new PageExport($this->course);
        $pageFiles = [];
        $pageData = $pageExport->getData(0, 1);
        if (!empty($pageData['files'])) {
            $pageFiles = $pageData['files'];
        }
        @error_log('[MoodleExport::export] pageFiles from PageExport='.count($pageFiles));

        // Files export (documents, attachments, + pages’ files)
        $fileExport = new FileExport($this->course);
        $filesData = $fileExport->getFilesData();
        @error_log('[MoodleExport::export] getFilesData='.count($filesData['files'] ?? []));
        $filesData['files'] = array_merge($filesData['files'] ?? [], $pageFiles);
        @error_log('[MoodleExport::export] merged files='.count($filesData['files'] ?? []));
        $fileExport->exportFiles($filesData, $tempDir);

        // Sections export (topics/weeks descriptors)
        $this->exportSections($tempDir);

        $this->exportCourseCalendar($tempDir);
        $this->exportAnnouncementsForum($activities, $tempDir);
        $this->exportLabelActivities($activities, $tempDir);
        $this->exportAttendanceActivities($activities, $tempDir);
        $this->exportThematicActivities($activities, $tempDir);
        $this->exportWikiActivities($activities, $tempDir);
        $this->exportGradebookActivities($activities, $tempDir);
        $this->exportQuizMetaActivities($activities, $tempDir);
        $this->exportLearnpathMeta($tempDir);

        // Root XMLs (course/activities indexes)
        $this->exportRootXmlFiles($tempDir);
        @error_log('[MoodleExport::export] root XMLs exported');

        // Create .mbz archive
        $exportedFile = $this->createMbzFile($tempDir);
        @error_log('[MoodleExport::export] mbz created at '.$exportedFile);

        // Cleanup temp dir
        $this->cleanupTempDir($tempDir);
        @error_log('[MoodleExport::export] tempDir removed '.$tempDir);

        @error_log('[MoodleExport::export] Done. file='.$exportedFile);
        return $exportedFile;
    }

    /**
     * Export questions data to XML file.
     */
    public function exportQuestionsXml(array $questionsData, string $exportDir): void
    {
        $quizExport = new QuizExport($this->course);
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<question_categories>'.PHP_EOL;

        $categoryHashes = [];
        foreach ($questionsData as $quiz) {
            // Skip empty sets defensively
            if (empty($quiz['questions']) || !\is_array($quiz['questions'])) {
                continue;
            }

            $first = $quiz['questions'][0] ?? [];
            $categoryId = $first['questioncategoryid'] ?? '1';

            $hash = md5($categoryId.($quiz['name'] ?? ''));
            if (isset($categoryHashes[$hash])) {
                continue;
            }
            $categoryHashes[$hash] = true;
            $xmlContent .= '  <question_category id="'.$categoryId.'">'.PHP_EOL;
            $xmlContent .= '    <name>Default for '.htmlspecialchars((string) ($quiz['name'] ?? 'Unknown')).'</name>'.PHP_EOL;
            $xmlContent .= '    <contextid>'.($quiz['contextid'] ?? '0').'</contextid>'.PHP_EOL;
            $xmlContent .= '    <contextlevel>70</contextlevel>'.PHP_EOL;
            $xmlContent .= '    <contextinstanceid>'.($quiz['moduleid'] ?? '0').'</contextinstanceid>'.PHP_EOL;
            $xmlContent .= '    <info>The default category for questions shared in context "'.htmlspecialchars((string) ($quiz['name'] ?? 'Unknown')).'".</info>'.PHP_EOL;
            $xmlContent .= '    <infoformat>0</infoformat>'.PHP_EOL;
            $xmlContent .= '    <stamp>moodle+'.time().'+CATEGORYSTAMP</stamp>'.PHP_EOL;
            $xmlContent .= '    <parent>0</parent>'.PHP_EOL;
            $xmlContent .= '    <sortorder>999</sortorder>'.PHP_EOL;
            $xmlContent .= '    <idnumber>$@NULL@$</idnumber>'.PHP_EOL;
            $xmlContent .= '    <questions>'.PHP_EOL;

            foreach ($quiz['questions'] as $question) {
                $xmlContent .= $quizExport->exportQuestion($question);
            }

            $xmlContent .= '    </questions>'.PHP_EOL;
            $xmlContent .= '  </question_category>'.PHP_EOL;
        }

        $xmlContent .= '</question_categories>';
        file_put_contents($exportDir.'/questions.xml', $xmlContent);
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
     * Robustly checks if a resource type matches a constant or any string aliases.
     * This prevents "undefined constant" notices and supports mixed key styles.
     *
     * @param mixed         $resourceType Numeric constant or string like 'quiz', 'document', etc.
     * @param string        $constName    Constant name, e.g. 'RESOURCE_QUIZ'
     * @param array<string> $aliases      String aliases accepted for this type
     */
    private function isType($resourceType, string $constName, array $aliases = []): bool
    {
        // Match numeric constant exactly when defined
        if (\defined($constName)) {
            $constVal = \constant($constName);
            if ($resourceType === $constVal) {
                return true;
            }
        }

        // Match by string aliases (case-insensitive) if resourceType is a string
        if (\is_string($resourceType)) {
            $rt = mb_strtolower($resourceType);
            foreach ($aliases as $a) {
                if ($rt === mb_strtolower($a)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Pulls dependent resources that LP items reference (only when LP bag exists).
     * Defensive: if no learnpath bag is present (e.g., exporting only documents),
     * this becomes a no-op. Keeps current behavior untouched when LP exist.
     */
    private function fillResourcesFromLearnpath(object $complete): void
    {
        // Accept both constant and plain-string keys defensively.
        $lpBag =
            $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
            ?? $this->course->resources['learnpath']
            ?? [];

        if (empty($lpBag) || !\is_array($lpBag)) {
            // No learnpaths selected/present → nothing to hydrate.
            return;
        }

        foreach ($lpBag as $learnpathId => $learnpath) {
            // $learnpath may be wrapped in ->obj
            $lp = (\is_object($learnpath) && isset($learnpath->obj) && \is_object($learnpath->obj))
                ? $learnpath->obj
                : $learnpath;

            if (!\is_object($lp) || empty($lp->items) || !\is_array($lp->items)) {
                continue;
            }

            foreach ($lp->items as $item) {
                // Legacy LP items expose "item_type" and "path" (resource id)
                $type = $item['item_type'] ?? null;
                $resourceId = $item['path'] ?? null;
                if (!$type || null === $resourceId) {
                    continue;
                }

                // Bring missing deps from the complete snapshot (keeps old behavior when LP exist)
                if (isset($complete->resources[$type][$resourceId])
                    && !isset($this->course->resources[$type][$resourceId])) {
                    $this->course->resources[$type][$resourceId] = $complete->resources[$type][$resourceId];
                }
            }
        }
    }

    private function fillQuestionsFromQuiz(object $complete): void
    {
        if (!isset($this->course->resources['quiz'])) {
            return;
        }
        foreach ($this->course->resources['quiz'] as $quizId => $quiz) {
            if (!isset($quiz->obj->question_ids)) {
                continue;
            }
            foreach ($quiz->obj->question_ids as $questionId) {
                if (isset($complete->resources['Exercise_Question'][$questionId]) && !isset($this->course->resources['Exercise_Question'][$questionId])) {
                    $this->course->resources['Exercise_Question'][$questionId] = $complete->resources['Exercise_Question'][$questionId];
                }
            }
        }
    }

    private function exportRootXmlFiles(string $exportDir): void
    {
        $this->exportBadgesXml($exportDir);
        $this->exportCompletionXml($exportDir);
        $this->exportGradebookXml($exportDir);
        $this->exportGradeHistoryXml($exportDir);
        $this->exportGroupsXml($exportDir);
        $this->exportOutcomesXml($exportDir);

        $activities = $this->getActivities();
        $questionsData = [];
        foreach ($activities as $activity) {
            if ('quiz' === ($activity['modulename'] ?? '')) {
                $quizExport = new QuizExport($this->course);
                $quizData = $quizExport->getData($activity['id'], $activity['sectionid']);
                $questionsData[] = $quizData;
            }
        }
        $this->exportQuestionsXml($questionsData, $exportDir);

        $this->exportRolesXml($exportDir);
        $this->exportScalesXml($exportDir);
        $this->exportUsersXml($exportDir);
    }

    private function createMoodleBackupXml(string $destinationDir, int $version): void
    {
        $courseInfo = api_get_course_info($this->course->code);
        $backupId = md5(bin2hex(random_bytes(16)));
        $siteHash = md5(bin2hex(random_bytes(16)));
        $wwwRoot = api_get_path(WEB_PATH);

        $courseStartDate = strtotime($courseInfo['creation_date']);
        $courseEndDate = $courseStartDate + (365 * 24 * 60 * 60);

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<moodle_backup>'.PHP_EOL;
        $xmlContent .= '  <information>'.PHP_EOL;

        $xmlContent .= '    <name>backup-'.htmlspecialchars((string) $courseInfo['code']).'.mbz</name>'.PHP_EOL;
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
        $xmlContent .= '    <original_course_id>'.htmlspecialchars((string) $courseInfo['real_id']).'</original_course_id>'.PHP_EOL;
        $xmlContent .= '    <original_course_format>'.get_lang('Topics').'</original_course_format>'.PHP_EOL;
        $xmlContent .= '    <original_course_fullname>'.htmlspecialchars((string) $courseInfo['title']).'</original_course_fullname>'.PHP_EOL;
        $xmlContent .= '    <original_course_shortname>'.htmlspecialchars((string) $courseInfo['code']).'</original_course_shortname>'.PHP_EOL;
        $xmlContent .= '    <original_course_startdate>'.$courseStartDate.'</original_course_startdate>'.PHP_EOL;
        $xmlContent .= '    <original_course_enddate>'.$courseEndDate.'</original_course_enddate>'.PHP_EOL;
        $xmlContent .= '    <original_course_contextid>'.$courseInfo['real_id'].'</original_course_contextid>'.PHP_EOL;
        $xmlContent .= '    <original_system_contextid>'.api_get_current_access_url_id().'</original_system_contextid>'.PHP_EOL;

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

        $sections = $this->getSections();
        if (!empty($sections)) {
            $xmlContent .= '      <sections>'.PHP_EOL;
            foreach ($sections as $section) {
                $xmlContent .= '        <section>'.PHP_EOL;
                $xmlContent .= '          <sectionid>'.$section['id'].'</sectionid>'.PHP_EOL;
                $xmlContent .= '          <title>'.htmlspecialchars((string) $section['name']).'</title>'.PHP_EOL;
                $xmlContent .= '          <directory>sections/section_'.$section['id'].'</directory>'.PHP_EOL;
                $xmlContent .= '        </section>'.PHP_EOL;
            }
            $xmlContent .= '      </sections>'.PHP_EOL;
        }

        $seenActs = [];
        $activitiesFlat = [];
        foreach ($sections as $section) {
            foreach ($section['activities'] as $a) {
                $modname = (string) ($a['modulename'] ?? '');
                $moduleid = isset($a['moduleid']) ? (int) $a['moduleid'] : null;
                if ('' === $modname || null === $moduleid || $moduleid < 0) {
                    continue;
                }
                $key = $modname.':'.$moduleid;
                if (isset($seenActs[$key])) {
                    continue;
                }
                $seenActs[$key] = true;

                $title = (string) ($a['title'] ?? $a['name'] ?? '');
                $activitiesFlat[] = [
                    'moduleid' => $moduleid,
                    'sectionid' => (int) $section['id'],
                    'modulename' => $modname,
                    'title' => $title,
                ];
            }
        }

        // Append label/forum/wiki from getActivities() that are not already listed by sections
        foreach ($this->getActivities() as $a) {
            $modname  = (string) ($a['modulename'] ?? '');
            if (!\in_array($modname, ['label','forum','wiki'], true)) {
                continue;
            }

            $moduleid = (int) ($a['moduleid'] ?? 0);
            if ($moduleid <= 0) {
                continue;
            }

            $key = $modname.':'.$moduleid;
            if (isset($seenActs[$key])) {
                continue; // already present via sections, skip to avoid duplicates
            }
            $seenActs[$key] = true;

            // Ensure we propagate title and section for the backup XML
            $activitiesFlat[] = [
                'moduleid'  => $moduleid,
                'sectionid' => (int) ($a['sectionid'] ?? 0),
                'modulename'=> $modname,
                'title'     => (string) ($a['title'] ?? ''),
            ];
        }

        if (!empty($activitiesFlat)) {
            $xmlContent .= '      <activities>'.PHP_EOL;
            foreach ($activitiesFlat as $activity) {
                $modname  = (string) $activity['modulename'];
                $moduleid = (int)    $activity['moduleid'];
                $sectionid= (int)    $activity['sectionid'];
                $title    = (string) $activity['title'];

                $hasUserinfo = self::$activityUserinfo[$modname][$moduleid] ?? false;

                $xmlContent .= '        <activity>'.PHP_EOL;
                $xmlContent .= '          <moduleid>'.$moduleid.'</moduleid>'.PHP_EOL;
                $xmlContent .= '          <sectionid>'.$sectionid.'</sectionid>'.PHP_EOL;
                $xmlContent .= '          <modulename>'.htmlspecialchars($modname).'</modulename>'.PHP_EOL;
                $xmlContent .= '          <title>'.htmlspecialchars($title).'</title>'.PHP_EOL;
                $xmlContent .= '          <directory>activities/'.$modname.'_'.$moduleid.'</directory>'.PHP_EOL;
                $xmlContent .= '          <userinfo>'.($hasUserinfo ? '1' : '0').'</userinfo>'.PHP_EOL;
                $xmlContent .= '        </activity>'.PHP_EOL;
            }
            $xmlContent .= '      </activities>'.PHP_EOL;
        }

        $xmlContent .= '      <course>'.PHP_EOL;
        $xmlContent .= '        <courseid>'.$courseInfo['real_id'].'</courseid>'.PHP_EOL;
        $xmlContent .= '        <title>'.htmlspecialchars((string) $courseInfo['title']).'</title>'.PHP_EOL;
        $xmlContent .= '        <directory>course</directory>'.PHP_EOL;
        $xmlContent .= '      </course>'.PHP_EOL;

        $xmlContent .= '    </contents>'.PHP_EOL;

        $xmlContent .= '    <settings>'.PHP_EOL;
        $activities = $activitiesFlat;
        $settings = $this->exportBackupSettings($sections, $activities);
        foreach ($settings as $setting) {
            $xmlContent .= '      <setting>'.PHP_EOL;
            $xmlContent .= '        <level>'.htmlspecialchars($setting['level']).'</level>'.PHP_EOL;
            $xmlContent .= '        <name>'.htmlspecialchars($setting['name']).'</name>'.PHP_EOL;
            $xmlContent .= '        <value>'.$setting['value'].'</value>'.PHP_EOL;
            if (isset($setting['section'])) {
                $xmlContent .= '        <section>'.htmlspecialchars($setting['section']).'</section>'.PHP_EOL;
            }
            if (isset($setting['activity'])) {
                $xmlContent .= '        <activity>'.htmlspecialchars($setting['activity']).'</activity>'.PHP_EOL;
            }
            $xmlContent .= '      </setting>'.PHP_EOL;
        }
        $xmlContent .= '    </settings>'.PHP_EOL;

        $xmlContent .= '  </information>'.PHP_EOL;
        $xmlContent .= '</moodle_backup>';

        $xmlFile = $destinationDir.'/moodle_backup.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Builds the sections array for moodle_backup.xml and for sections/* export.
     * Defensive: if no learnpaths are present/selected, only "General" (section 0) is emitted.
     * When LP exist, behavior remains unchanged.
     */
    private function getSections(): array
    {
        $sectionExport = new SectionExport($this->course);
        $sections = [];

        // Resolve LP bag defensively (constant or string key; or none)
        $lpBag =
            $this->course->resources[\defined('RESOURCE_LEARNPATH') ? RESOURCE_LEARNPATH : 'learnpath']
            ?? $this->course->resources['learnpath']
            ?? [];

        if (!empty($lpBag) && \is_array($lpBag)) {
            foreach ($lpBag as $learnpath) {
                // Unwrap if needed
                $lp = (\is_object($learnpath) && isset($learnpath->obj) && \is_object($learnpath->obj))
                    ? $learnpath->obj
                    : $learnpath;

                // Some exports use string '1' or int 1 for LP type = learnpath
                $lpType = \is_object($lp) && isset($lp->lp_type) ? (string) $lp->lp_type : '';
                if ('1' === $lpType) {
                    $sections[] = $sectionExport->getSectionData($learnpath);
                }
            }
        }

        // Always add "General" (section 0)
        $sections[] = [
            'id' => 0,
            'number' => 0,
            'name' => get_lang('General'),
            'summary' => get_lang('General course resources'),
            'sequence' => 0,
            'visible' => 1,
            'timemodified' => time(),
            'activities' => $sectionExport->getActivitiesForGeneral(),
        ];

        return $sections;
    }

    private function getActivities(): array
    {
        @error_log('[MoodleExport::getActivities] Start');

        $activities = [];
        $glossaryAdded = false;
        $wikiAdded = false;

        // Build a "documents" bucket (root-level files/folders)
        $docBucket = [];
        if (\defined('RESOURCE_DOCUMENT') && isset($this->course->resources[RESOURCE_DOCUMENT]) && \is_array($this->course->resources[RESOURCE_DOCUMENT])) {
            $docBucket = $this->course->resources[RESOURCE_DOCUMENT];
        } elseif (isset($this->course->resources['document']) && \is_array($this->course->resources['document'])) {
            $docBucket = $this->course->resources['document'];
        }
        @error_log('[MoodleExport::getActivities] docBucket='.count($docBucket));

        // Add a visible "Documents" folder activity if we actually have documents
        if (!empty($docBucket)) {
            $activities[] = [
                'id'        => ActivityExport::DOCS_MODULE_ID,
                'sectionid' => 0,
                'modulename'=> 'folder',
                'moduleid'  => ActivityExport::DOCS_MODULE_ID,
                'title'     => 'Documents',
            ];
            @error_log('[MoodleExport::getActivities] Added visible folder activity "Documents" (moduleid=' . ActivityExport::DOCS_MODULE_ID . ').');
        }

        $htmlPageIds = [];

        foreach ($this->course->resources as $resourceType => $resources) {
            if (!\is_array($resources) || empty($resources)) {
                continue;
            }

            foreach ($resources as $resource) {
                $exportClass = null;
                $moduleName = '';
                $title = '';
                $id = 0;

                // Quiz
                if ($this->isType($resourceType, 'RESOURCE_QUIZ', ['quiz'])) {
                    if (($resource->obj->iid ?? 0) > 0) {
                        $exportClass = QuizExport::class;
                        $moduleName = 'quiz';
                        $id = (int) $resource->obj->iid;
                        $title = (string) $resource->obj->title;
                    }
                }

                // URL
                if ($this->isType($resourceType, 'RESOURCE_LINK', ['link'])) {
                    if (($resource->source_id ?? 0) > 0) {
                        $exportClass = UrlExport::class;
                        $moduleName = 'url';
                        $id = (int) $resource->source_id;
                        $title = (string) ($resource->title ?? '');
                    }
                }
                // Glossary (only once)
                elseif ($this->isType($resourceType, 'RESOURCE_GLOSSARY', ['glossary'])) {
                    if (($resource->glossary_id ?? 0) > 0 && !$glossaryAdded) {
                        $exportClass = GlossaryExport::class;
                        $moduleName = 'glossary';
                        $id = 1;
                        $title = get_lang('Glossary');
                        $glossaryAdded = true;
                        self::flagActivityUserinfo('glossary', $id, true);
                    }
                }
                // Forum
                elseif ($this->isType($resourceType, 'RESOURCE_FORUM', ['forum'])) {
                    if (($resource->source_id ?? 0) > 0) {
                        $exportClass = ForumExport::class;
                        $moduleName = 'forum';
                        $id = (int) ($resource->obj->iid ?? 0);
                        $title = (string) ($resource->obj->forum_title ?? '');
                        self::flagActivityUserinfo('forum', $id, true);
                    }
                }
                // Documents (as Page or Resource)
                elseif ($this->isType($resourceType, 'RESOURCE_DOCUMENT', ['document'])) {
                    $resPath = (string) ($resource->path ?? '');
                    $resTitle = (string) ($resource->title ?? '');
                    $fileType = (string) ($resource->file_type ?? '');

                    $isRoot = ('' !== $resPath && 1 === substr_count($resPath, '/'));
                    $ext = '' !== $resPath ? pathinfo($resPath, PATHINFO_EXTENSION) : '';

                    // Root HTML -> export as "page"
                    if ('html' === $ext && $isRoot) {
                        $exportClass = PageExport::class;
                        $moduleName = 'page';
                        $id = (int) $resource->source_id;
                        $title = $resTitle;
                        $htmlPageIds[] = $id;
                    }

                    // Regular file -> export as "resource" (avoid colliding with pages)
                    if ('file' === $fileType && !\in_array($resource->source_id, $htmlPageIds, true)) {
                        $resourceExport = new ResourceExport($this->course);
                        if ($resourceExport->getSectionIdForActivity((int) $resource->source_id, $resourceType) > 0) {
                            if ($isRoot) {
                                $exportClass = ResourceExport::class;
                                $moduleName = 'resource';
                                $id = (int) $resource->source_id;
                                $title = '' !== $resTitle ? $resTitle : (basename($resPath) ?: ('File '.$id));
                            }
                        }
                    }
                }
                // Tool Intro -> treat "course_homepage" as a Page activity (id=0)
                elseif ($this->isType($resourceType, 'RESOURCE_TOOL_INTRO', ['tool_intro'])) {
                    // IMPORTANT: do not check source_id; the real key is obj->id
                    $objId = (string) ($resource->obj->id ?? '');
                    if ($objId === 'course_homepage') {
                        $exportClass = PageExport::class;
                        $moduleName = 'page';
                        // Keep activity id = 0 → PageExport::getData(0, ...) reads the intro HTML
                        $id = 0;
                        $title = get_lang('Introduction');
                    }
                }
                // Assignments
                elseif ($this->isType($resourceType, 'RESOURCE_WORK', ['work', 'assign'])) {
                    if (($resource->source_id ?? 0) > 0) {
                        $exportClass = AssignExport::class;
                        $moduleName = 'assign';
                        $id = (int) $resource->source_id;
                        $title = (string) ($resource->params['title'] ?? '');
                    }
                }
                // Surveys -> Feedback
                elseif ($this->isType($resourceType, 'RESOURCE_SURVEY', ['survey', 'feedback'])) {
                    if (($resource->source_id ?? 0) > 0) {
                        $exportClass = FeedbackExport::class;
                        $moduleName = 'feedback';
                        $id = (int) $resource->source_id;
                        $title = (string) ($resource->params['title'] ?? '');
                    }
                }
                // Course descriptions → Label
                elseif ($this->isType($resourceType, 'RESOURCE_COURSEDESCRIPTION', ['coursedescription', 'course_description'])) {
                    if (($resource->source_id ?? 0) > 0) {
                        $exportClass = LabelExport::class;
                        $moduleName  = 'label';
                        $id          = (int) $resource->source_id;
                        $title       = (string) ($resource->title ?? '');
                    }
                }
                // Attendance (store as Chamilo-only metadata; NOT a Moodle activity)
                elseif ($this->isType($resourceType, 'RESOURCE_ATTENDANCE', ['attendance'])) {
                    // Resolve legacy id (iid) from possible fields
                    $id = 0;
                    if (isset($resource->obj->iid) && \is_numeric($resource->obj->iid)) {
                        $id = (int) $resource->obj->iid;
                    } elseif (isset($resource->source_id) && \is_numeric($resource->source_id)) {
                        $id = (int) $resource->source_id;
                    } elseif (isset($resource->obj->id) && \is_numeric($resource->obj->id)) {
                        $id = (int) $resource->obj->id;
                    }

                    // Resolve title or fallback
                    $title = '';
                    foreach (['title','name'] as $k) {
                        if (!empty($resource->obj->{$k}) && \is_string($resource->obj->{$k})) { $title = trim((string)$resource->obj->{$k}); break; }
                        if (!empty($resource->{$k}) && \is_string($resource->{$k}))       { $title = trim((string)$resource->{$k}); break; }
                    }
                    if ($title === '') { $title = 'Attendance'; }

                    // Section: best-effort (0 when unknown). We avoid calling any Attendance exporter here.
                    $sectionId = 0;

                    // IMPORTANT:
                    // We register it with a pseudo module "attendance" for our own export step,
                    // but we do NOT emit a Moodle activity nor include it in moodle_backup.xml.
                    $activities[] = [
                        'id'         => $id,
                        'sectionid'  => $sectionId,
                        'modulename' => 'attendance',
                        'moduleid'   => $id,
                        'title'      => $title,
                        '__from'     => 'attendance',
                    ];

                    @error_log('[MoodleExport::getActivities] ADD (Chamilo-only) attendance moduleid='.$id.' sectionid='.$sectionId.' title="'.str_replace(["\n","\r"],' ',$title).'"');
                    // do NOT set $exportClass → keeps getActivities() side-effect free for Moodle
                }
                // Thematic (Chamilo-only metadata)
                elseif ($this->isType($resourceType, 'RESOURCE_THEMATIC', ['thematic'])) {
                    $id = (int) ($resource->obj->iid ?? $resource->source_id ?? $resource->obj->id ?? 0);
                    if ($id > 0) {
                        $title = '';
                        foreach (['title','name'] as $k) {
                            if (!empty($resource->obj->{$k})) { $title = trim((string) $resource->obj->{$k}); break; }
                            if (!empty($resource->{$k}))       { $title = trim((string) $resource->{$k}); break; }
                        }
                        if ($title === '') $title = 'Thematic';

                        $activities[] = [
                            'id'         => $id,
                            'sectionid'  => 0,            // or the real topic if you track it
                            'modulename' => 'thematic',   // Chamilo-only meta
                            'moduleid'   => $id,
                            'title'      => $title,
                            '__from'     => 'thematic',
                        ];
                        @error_log('[MoodleExport::getActivities] ADD (Chamilo-only) thematic id='.$id);
                    }
                }
                // Wiki (only once)
                elseif ($this->isType($resourceType, 'RESOURCE_WIKI', ['wiki'])) {
                    if (!$wikiAdded) {
                        $exportClass = WikiExport::class;
                        $moduleName  = 'wiki';
                        $id          = self::WIKI_MODULE_ID;
                        $title       = get_lang('Wiki') ?: 'Wiki';
                        $wikiAdded = true;

                        self::flagActivityUserinfo('wiki', $id, true);
                    } else {
                        continue;
                    }
                }
                // Gradebook (Chamilo-only; exports chamilo/gradebook/*.json; NOT a Moodle activity)
                elseif ($this->isType($resourceType, 'RESOURCE_GRADEBOOK', ['gradebook'])) {
                    // One snapshot per course/session; treat as a single meta activity.
                    $id = 1; // local activity id (opaque; not used by Moodle)
                    $title = 'Gradebook';

                    $activities[] = [
                        'id'         => $id,
                        'sectionid'  => 0, // place in "General" topic (informational only)
                        'modulename' => 'gradebook',
                        'moduleid'   => self::GRADEBOOK_MODULE_ID,
                        'title'      => $title,
                        '__from'     => 'gradebook',
                    ];
                    @error_log('[MoodleExport::getActivities] ADD (Chamilo-only) gradebook moduleid=' . self::GRADEBOOK_MODULE_ID);
                }

                // Emit activity if resolved
                if ($exportClass && $moduleName) {
                    /** @var object $exportInstance */
                    $exportInstance = new $exportClass($this->course);
                    $sectionId = $exportInstance->getSectionIdForActivity($id, $resourceType);
                    $activities[] = [
                        'id' => $id,
                        'sectionid' => $sectionId,
                        'modulename' => $moduleName,
                        'moduleid' => $id,
                        'title' => $title,
                    ];
                    @error_log('[MoodleExport::getActivities] ADD modulename='.$moduleName.' moduleid='.$id.' sectionid='.$sectionId.' title="'.str_replace(["\n","\r"],' ',$title).'"');
                }
            }
        }

        // ---- Append synthetic News forum from Chamilo announcements (if any) ----
        try {
            $res = \is_array($this->course->resources ?? null) ? $this->course->resources : [];
            $annBag =
                ($res[\defined('RESOURCE_ANNOUNCEMENT') ? RESOURCE_ANNOUNCEMENT : 'announcements'] ?? null)
                ?? ($res['announcements'] ?? null)
                ?? ($res['announcement'] ?? null)
                ?? [];

            if (!empty($annBag) && !$this->hasAnnouncementsLikeForum($activities)) {
                $activities[] = [
                    'id'         => 1, // local activity id for our synthetic forum
                    'sectionid'  => 0, // place in "General" topic
                    'modulename' => 'forum',
                    'moduleid'   => self::ANNOUNCEMENTS_MODULE_ID,
                    'title'      => get_lang('Announcements'),
                    '__from'     => 'announcements',
                ];
                // Forum contains posts, mark userinfo = true
                self::flagActivityUserinfo('forum', self::ANNOUNCEMENTS_MODULE_ID, true);
                @error_log('[MoodleExport::getActivities] Added synthetic News forum (announcements) moduleid='.self::ANNOUNCEMENTS_MODULE_ID);
            }
        } catch (\Throwable $e) {
            @error_log('[MoodleExport::getActivities][WARN] announcements detection: '.$e->getMessage());
        }

        @error_log('[MoodleExport::getActivities] Done. total='.count($activities));
        return $activities;
    }

    /**
     * Collect Moodle URL activities from legacy "link" bucket.
     *
     * It is defensive against different wrappers:
     * - Accepts link objects as $wrap->obj or directly as $wrap.
     * - Resolves title from title|name|url (last-resort).
     * - Maps category_id to a section name (category title) if available.
     *
     * @return UrlExport[]
     */
    private function buildUrlActivities(): array
    {
        $res = \is_array($this->course->resources ?? null) ? $this->course->resources : [];

        // Buckets (defensive: allow legacy casings)
        $links = $res['link'] ?? $res['Link'] ?? [];
        $cats  = $res['link_category'] ?? $res['Link_Category'] ?? [];

        // Map category_id → label for section naming
        $catLabel = [];
        foreach ($cats as $cid => $cwrap) {
            if (!\is_object($cwrap)) {
                continue;
            }
            $c = (isset($cwrap->obj) && \is_object($cwrap->obj)) ? $cwrap->obj : $cwrap;
            $label = '';
            foreach (['title', 'name'] as $k) {
                if (!empty($c->{$k}) && \is_string($c->{$k})) {
                    $label = trim((string) $c->{$k});
                    break;
                }
            }
            $catLabel[(int) $cid] = $label !== '' ? $label : ('Category #'.(int) $cid);
        }

        $out = [];
        foreach ($links as $id => $lwrap) {
            if (!\is_object($lwrap)) {
                continue;
            }
            $L = (isset($lwrap->obj) && \is_object($lwrap->obj)) ? $lwrap->obj : $lwrap;

            $url = (string) ($L->url ?? '');
            if ($url === '') {
                // Skip invalid URL records
                continue;
            }

            // Resolve a robust title
            $title = '';
            foreach (['title', 'name'] as $k) {
                if (!empty($L->{$k}) && \is_string($L->{$k})) {
                    $title = trim((string) $L->{$k});
                    break;
                }
            }
            if ($title === '') {
                $title = $url; // last resort: use the URL itself
            }

            $target = (string) ($L->target ?? '');
            $intro  = (string) ($L->description ?? '');
            $cid    = (int) ($L->category_id ?? 0);

            $sectionName = $catLabel[$cid] ?? null;

            // UrlExport ctor: (string $title, string $url, ?string $section = null, ?string $introHtml = null, ?string $target = null)
            $urlAct = new UrlExport($title, $url, $sectionName ?: null, $intro ?: null, $target ?: null);
            if (method_exists($urlAct, 'setLegacyId')) {
                $urlAct->setLegacyId((int) $id);
            }

            $out[] = $urlAct;
        }

        return $out;
    }

    /**
     * Enqueue all URL activities into the export pipeline.
     * Will try queueActivity(), then addActivity(), then $this->activities[].
     */
    private function enqueueUrlActivities(): void
    {
        $urls = $this->buildUrlActivities();

        if (empty($urls)) {
            @error_log('[MoodleExport] No URL activities to enqueue');
            return;
        }

        if (method_exists($this, 'queueActivity')) {
            foreach ($urls as $act) {
                $this->queueActivity($act);
            }
            @error_log('[MoodleExport] URL activities enqueued via queueActivity(): '.count($urls));
            return;
        }

        if (method_exists($this, 'addActivity')) {
            foreach ($urls as $act) {
                $this->addActivity($act);
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

    private function exportSections(string $exportDir): void
    {
        $sections = $this->getSections();
        foreach ($sections as $section) {
            $sectionExport = new SectionExport($this->course);
            $sectionExport->exportSection($section['id'], $exportDir);
        }
    }

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
                $relativePath = substr($filePath, \strlen($sourceDir) + 1);

                if (!$zip->addFile($filePath, $relativePath)) {
                    throw new Exception(get_lang('Error adding file to zip').": $relativePath");
                }
            }
        }

        if (!$zip->close()) {
            throw new Exception(get_lang('Error closing zip file'));
        }

        return $zipFile;
    }

    private function cleanupTempDir(string $dir): void
    {
        $this->recursiveDelete($dir);
    }

    private function recursiveDelete(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Export Gradebook metadata into chamilo/gradebook/*.json (no Moodle module).
     * Keeps getActivities() side-effect free and avoids adding to moodle_backup.xml.
     */
    private function exportGradebookActivities(array $activities, string $exportDir): void
    {
        $count = 0;
        foreach ($activities as $a) {
            if (($a['modulename'] ?? '') !== 'gradebook') {
                continue;
            }
            $activityId = (int) ($a['id'] ?? 0); // local/opaque; not strictly needed
            $moduleId   = (int) ($a['moduleid'] ?? self::GRADEBOOK_MODULE_ID);
            $sectionId  = (int) ($a['sectionid'] ?? 0);

            try {
                $meta = new GradebookMetaExport($this->course);
                $meta->export($activityId, $exportDir, $moduleId, $sectionId);

                // No userinfo here (change if you later add per-user grades)
                self::flagActivityUserinfo('gradebook', $moduleId, false);
                $count++;
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportGradebookActivities][ERROR] '.$e->getMessage());
            }
        }

        @error_log('[MoodleExport::exportGradebookActivities] exported=' . $count);
    }

    /**
     * Export raw learnpath metadata (categories + each LP with items) as JSON sidecars.
     * This does not affect Moodle XML; it complements the backup with Chamilo-native data.
     */
    private function exportLearnpathMeta(string $exportDir): void
    {
        try {
            $meta = new LearnpathMetaExport($this->course);
            $count = $meta->exportAll($exportDir);
            @error_log('[MoodleExport::exportLearnpathMeta] exported learnpaths='.$count);
        } catch (\Throwable $e) {
            @error_log('[MoodleExport::exportLearnpathMeta][ERROR] '.$e->getMessage());
        }
    }

    /**
     * Export quiz raw JSON sidecars (quiz.json, questions.json, answers.json)
     * for every selected quiz activity. This does not affect Moodle XML export.
     */
    private function exportQuizMetaActivities(array $activities, string $exportDir): void
    {
        $count = 0;
        foreach ($activities as $a) {
            if (($a['modulename'] ?? '') !== 'quiz') {
                continue;
            }
            $activityId = (int) ($a['id'] ?? 0);
            $moduleId   = (int) ($a['moduleid'] ?? 0);
            $sectionId  = (int) ($a['sectionid'] ?? 0);
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
        @error_log('[MoodleExport::exportQuizMetaActivities] exported='.$count);
    }

    /**
     * Export Attendance metadata into chamilo/attendance/*.json (no Moodle module).
     * Keeps getActivities() side-effect free and avoids adding to moodle_backup.xml.
     */
    private function exportAttendanceActivities(array $activities, string $exportDir): void
    {
        $count = 0;
        foreach ($activities as $a) {
            if (($a['modulename'] ?? '') !== 'attendance') {
                continue;
            }
            $activityId = (int) ($a['id'] ?? 0);
            $moduleId   = (int) ($a['moduleid'] ?? 0);
            $sectionId  = (int) ($a['sectionid'] ?? 0);
            if ($activityId <= 0 || $moduleId <= 0) {
                continue;
            }

            try {
                $meta = new AttendanceMetaExport($this->course);
                $meta->export($activityId, $exportDir, $moduleId, $sectionId);

                // No userinfo here (change to true if you later include per-user marks)
                self::flagActivityUserinfo('attendance', $moduleId, false);
                $count++;
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportAttendanceActivities][ERROR] '.$e->getMessage());
            }
        }

        @error_log('[MoodleExport::exportAttendanceActivities] exported='. $count);
    }

    /**
     * Export Label activities into activities/label_{id}/label.xml
     * Only for real "label" items (course descriptions).
     */
    private function exportLabelActivities(array $activities, string $exportDir): void
    {
        foreach ($activities as $a) {
            if (($a['modulename'] ?? '') !== 'label') {
                continue;
            }
            $activityId = (int) ($a['id'] ?? 0);
            $moduleId   = (int) ($a['moduleid'] ?? 0);
            $sectionId  = (int) ($a['sectionid'] ?? 0);

            try {
                $label = new LabelExport($this->course);
                $label->export($activityId, $exportDir, $moduleId, $sectionId);
                @error_log('[MoodleExport::exportLabelActivities] exported label moduleid='.$moduleId.' sectionid='.$sectionId);
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportLabelActivities][ERROR] '.$e->getMessage());
            }
        }
    }

    private function exportThematicActivities(array $activities, string $exportDir): void
    {
        $count = 0;
        foreach ($activities as $a) {
            if (($a['modulename'] ?? '') !== 'thematic') continue;

            $activityId = (int) ($a['id'] ?? 0);
            $moduleId   = (int) ($a['moduleid'] ?? 0);
            $sectionId  = (int) ($a['sectionid'] ?? 0);
            if ($activityId <= 0 || $moduleId <= 0) continue;

            try {
                $meta = new ThematicMetaExport($this->course);
                $meta->export($activityId, $exportDir, $moduleId, $sectionId);

                // no userinfo for meta-only artifacts
                self::flagActivityUserinfo('thematic', $moduleId, false);

                $count++;
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportThematicActivities][ERROR] '.$e->getMessage());
            }
        }
        @error_log('[MoodleExport::exportThematicActivities] exported='.$count);
    }

    private function exportWikiActivities(array $activities, string $exportDir): void
    {
        foreach ($activities as $a) {
            if (($a['modulename'] ?? '') !== 'wiki') {
                continue;
            }
            $activityId = (int)($a['id'] ?? 0);
            $moduleId   = (int)($a['moduleid'] ?? 0);
            $sectionId  = (int)($a['sectionid'] ?? 0);
            if ($activityId <= 0 || $moduleId <= 0) {
                continue;
            }
            try {
                $exp = new WikiExport($this->course);
                $exp->export($activityId, $exportDir, $moduleId, $sectionId);
                @error_log('[MoodleExport::exportWikiActivities] exported wiki moduleid='.$moduleId.' sectionid='.$sectionId);
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportWikiActivities][ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * Export synthetic News forum built from Chamilo announcements.
     */
    private function exportAnnouncementsForum(array $activities, string $exportDir): void
    {
        foreach ($activities as $a) {
            if (($a['modulename'] ?? '') !== 'forum') {
                continue;
            }
            if (($a['__from'] ?? '') !== 'announcements') {
                continue; // only our synthetic forum
            }

            $activityId = (int) ($a['id'] ?? 0);
            $moduleId   = (int) ($a['moduleid'] ?? 0);
            $sectionId  = (int) ($a['sectionid'] ?? 0);

            try {
                $exp = new AnnouncementsForumExport($this->course);
                $exp->export($activityId, $exportDir, $moduleId, $sectionId);
                @error_log('[MoodleExport::exportAnnouncementsForum] exported forum moduleid='.$moduleId.' sectionid='.$sectionId);
            } catch (\Throwable $e) {
                @error_log('[MoodleExport::exportAnnouncementsForum][ERROR] '.$e->getMessage());
            }
        }
    }

    /**
     * Export course-level calendar events to course/calendarevents.xml
     * (This is NOT an activity; it belongs to the course folder.)
     */
    private function exportCourseCalendar(string $exportDir): void
    {
        try {
            $cal = new CourseCalendarExport($this->course);
            $count = $cal->export($exportDir);

            // Root backup settings already include "calendarevents" = 1 in createMoodleBackupXml().
            @error_log('[MoodleExport::exportCourseCalendar] exported events='.$count);
        } catch (\Throwable $e) {
            @error_log('[MoodleExport::exportCourseCalendar][ERROR] '.$e->getMessage());
        }
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

    /** Returns true if an existing forum already looks like "Announcements/News". */
    private function hasAnnouncementsLikeForum(array $activities): bool
    {
        foreach ($activities as $a) {
            if (($a['modulename'] ?? '') !== 'forum') {
                continue;
            }
            $t = mb_strtolower((string) ($a['title'] ?? ''));
            foreach (['announcements','news'] as $kw) {
                if ($t === $kw || str_contains($t, $kw)) {
                    return true; // looks like an announcements/news forum already
                }
            }
        }
        return false;
    }
}
