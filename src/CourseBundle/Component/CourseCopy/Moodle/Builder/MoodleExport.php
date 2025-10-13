<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\AssignExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\FeedbackExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ForumExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\GlossaryExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\PageExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\QuizExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ResourceExport;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\UrlExport;
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
        $tempDir = api_get_path(SYS_ARCHIVE_PATH).$exportDir;

        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, api_get_permissions_for_new_directories(), true)) {
                throw new Exception(get_lang('ErrorCreatingDirectory'));
            }
        }

        $courseInfo = api_get_course_info($courseId);
        if (!$courseInfo) {
            throw new Exception(get_lang('CourseNotFound'));
        }

        // Generate the moodle_backup.xml
        $this->createMoodleBackupXml($tempDir, $version);

        // Get the activities from the course
        $activities = $this->getActivities();

        // Export course-related files
        $courseExport = new CourseExport($this->course, $activities);
        $courseExport->exportCourse($tempDir);

        // Export files-related data and actual files
        $pageExport = new PageExport($this->course);
        $pageFiles = [];
        $pageData = $pageExport->getData(0, 1);
        if (!empty($pageData['files'])) {
            $pageFiles = $pageData['files'];
        }
        $fileExport = new FileExport($this->course);
        $filesData = $fileExport->getFilesData();
        $filesData['files'] = array_merge($filesData['files'], $pageFiles);
        $fileExport->exportFiles($filesData, $tempDir);

        // Export sections of the course
        $this->exportSections($tempDir);

        // Export all root XML files
        $this->exportRootXmlFiles($tempDir);

        // Compress everything into a .mbz (ZIP) file
        $exportedFile = $this->createMbzFile($tempDir);

        // Clean up temporary directory
        $this->cleanupTempDir($tempDir);

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
            $categoryId = $quiz['questions'][0]['questioncategoryid'] ?? '1';
            $hash = md5($categoryId.($quiz['name'] ?? ''));
            if (isset($categoryHashes[$hash])) {
                continue;
            }
            $categoryHashes[$hash] = true;
            $xmlContent .= '  <question_category id="'.$categoryId.'">'.PHP_EOL;
            $xmlContent .= '    <name>Default for '.htmlspecialchars((string) $quiz['name'] ?? 'Unknown').'</name>'.PHP_EOL;
            $xmlContent .= '    <contextid>'.($quiz['contextid'] ?? '0').'</contextid>'.PHP_EOL;
            $xmlContent .= '    <contextlevel>70</contextlevel>'.PHP_EOL;
            $xmlContent .= '    <contextinstanceid>'.($quiz['moduleid'] ?? '0').'</contextinstanceid>'.PHP_EOL;
            $xmlContent .= '    <info>The default category for questions shared in context "'.htmlspecialchars($quiz['name'] ?? 'Unknown').'".</info>'.PHP_EOL;
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
            if ('quiz' === $activity['modulename']) {
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

        if (!empty($activitiesFlat)) {
            $xmlContent .= '      <activities>'.PHP_EOL;
            foreach ($activitiesFlat as $activity) {
                $xmlContent .= '        <activity>'.PHP_EOL;
                $xmlContent .= '          <moduleid>'.$activity['moduleid'].'</moduleid>'.PHP_EOL;
                $xmlContent .= '          <sectionid>'.$activity['sectionid'].'</sectionid>'.PHP_EOL;
                $xmlContent .= '          <modulename>'.htmlspecialchars((string) $activity['modulename']).'</modulename>'.PHP_EOL;
                $xmlContent .= '          <title>'.htmlspecialchars((string) $activity['title']).'</title>'.PHP_EOL;
                $xmlContent .= '          <directory>activities/'.$activity['modulename'].'_'.$activity['moduleid'].'</directory>'.PHP_EOL;
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
            'summary' => get_lang('GeneralResourcesCourse'),
            'sequence' => 0,
            'visible' => 1,
            'timemodified' => time(),
            'activities' => $sectionExport->getActivitiesForGeneral(),
        ];

        return $sections;
    }

    private function getActivities(): array
    {
        $activities = [];
        $glossaryAdded = false;

        // Safely resolve the documents bucket (accept constant or plain 'document')
        $docBucket = [];
        if (\defined('RESOURCE_DOCUMENT') && isset($this->course->resources[RESOURCE_DOCUMENT]) && \is_array($this->course->resources[RESOURCE_DOCUMENT])) {
            $docBucket = $this->course->resources[RESOURCE_DOCUMENT];
        } elseif (isset($this->course->resources['document']) && \is_array($this->course->resources['document'])) {
            $docBucket = $this->course->resources['document'];
        }

        // If there are documents selected/present, add a visible "folder" activity like before
        if (!empty($docBucket)) {
            // NOTE: This creates the visible Folder activity in Moodle that groups all documents.
            // Files themselves will also be exported by FileExport as usual.
            $documentsFolder = [
                'id' => 0,
                'sectionid' => 0,
                'modulename' => 'folder',
                'moduleid' => 0,
                'title' => 'Documents',
            ];
            $activities[] = $documentsFolder;
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

                // QUIZ
                if (RESOURCE_QUIZ === $resourceType && ($resource->obj->iid ?? 0) > 0) {
                    $exportClass = QuizExport::class;
                    $moduleName = 'quiz';
                    $id = (int) $resource->obj->iid;
                    $title = (string) $resource->obj->title;
                }

                // URL (Link)
                if (RESOURCE_LINK === $resourceType && ($resource->source_id ?? 0) > 0) {
                    $exportClass = UrlExport::class;
                    $moduleName = 'url';
                    $id = (int) $resource->source_id;
                    $title = (string) ($resource->title ?? '');
                }
                // GLOSSARY (only once)
                elseif (RESOURCE_GLOSSARY === $resourceType && ($resource->glossary_id ?? 0) > 0 && !$glossaryAdded) {
                    $exportClass = GlossaryExport::class;
                    $moduleName = 'glossary';
                    $id = 1;
                    $title = get_lang('Glossary');
                    $glossaryAdded = true;
                }
                // FORUM
                elseif (RESOURCE_FORUM === $resourceType && ($resource->source_id ?? 0) > 0) {
                    $exportClass = ForumExport::class;
                    $moduleName = 'forum';
                    $id = (int) ($resource->obj->iid ?? 0);
                    $title = (string) ($resource->obj->forum_title ?? '');
                }
                // DOCUMENT → page/resource only when it really qualifies
                elseif (RESOURCE_DOCUMENT === $resourceType && ($resource->source_id ?? 0) > 0) {
                    $resPath = (string) ($resource->path ?? '');
                    $resTitle = (string) ($resource->title ?? '');
                    $fileType = (string) ($resource->file_type ?? '');

                    // Root = only one slash (e.g., "/foo.pdf")
                    $isRoot = ('' !== $resPath && 1 === substr_count($resPath, '/'));
                    $ext = '' !== $resPath ? pathinfo($resPath, PATHINFO_EXTENSION) : '';

                    // Root HTML becomes a Moodle 'page'
                    if ('html' === $ext && $isRoot) {
                        $exportClass = PageExport::class;
                        $moduleName = 'page';
                        $id = (int) $resource->source_id;
                        $title = $resTitle;
                        $htmlPageIds[] = $id;
                    }

                    // Root FILE (not already exported as 'page') becomes a Moodle 'resource'
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
                // COURSE INTRO
                elseif (RESOURCE_TOOL_INTRO === $resourceType && ($resource->source_id ?? '') === 'course_homepage') {
                    $exportClass = PageExport::class;
                    $moduleName = 'page';
                    $id = 0;
                    $title = get_lang('Introduction');
                }
                // ASSIGN
                elseif (RESOURCE_WORK === $resourceType && ($resource->source_id ?? 0) > 0) {
                    $exportClass = AssignExport::class;
                    $moduleName = 'assign';
                    $id = (int) $resource->source_id;
                    $title = (string) ($resource->params['title'] ?? '');
                }
                // FEEDBACK (Survey)
                elseif (RESOURCE_SURVEY === $resourceType && ($resource->source_id ?? 0) > 0) {
                    $exportClass = FeedbackExport::class;
                    $moduleName = 'feedback';
                    $id = (int) $resource->source_id;
                    $title = (string) ($resource->params['title'] ?? '');
                }

                if ($exportClass && $moduleName) {
                    /** @var object $exportInstance */
                    $exportInstance = new $exportClass($this->course);
                    $activities[] = [
                        'id' => $id,
                        'sectionid' => $exportInstance->getSectionIdForActivity($id, $resourceType),
                        'modulename' => $moduleName,
                        'moduleid' => $id,
                        'title' => $title,
                    ];
                }
            }
        }

        return $activities;
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
            throw new Exception(get_lang('ErrorCreatingZip'));
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
                    throw new Exception(get_lang('ErrorAddingFileToZip').": $relativePath");
                }
            }
        }

        if (!$zip->close()) {
            throw new Exception(get_lang('ErrorClosingZip'));
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
            $settings[] = [
                'level' => 'activity',
                'activity' => $activity['modulename'].'_'.$activity['moduleid'],
                'name' => $activity['modulename'].'_'.$activity['moduleid'].'_userinfo',
                'value' => '1',
            ];
        }

        return $settings;
    }
}
