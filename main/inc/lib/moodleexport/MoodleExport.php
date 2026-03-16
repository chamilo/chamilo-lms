<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Class MoodleExport.
 * Handles the export of a Moodle course in .mbz format.
 *
 * @package moodleexport
 */
class MoodleExport
{
    private $course;
    private static $adminUserData = [];
    private static int $backupCourseContextId = 0;
    private static int $backupCourseId = 0;

    /**
     * Constructor to initialize the course object.
     */
    public function __construct(object $course)
    {
        $cb = new CourseBuilder('complete');
        $complete = $cb->build();

        $this->course = $course;
        $this->fillResourcesFromLearnpath($complete);
        $this->fillQuestionsFromQuiz($complete);
    }

    /**
     * Export the Moodle course in .mbz format.
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

        $backupCourseId = (int) ($courseInfo['real_id'] ?? 0);
        $backupCourseContextId = $this->buildBackupCourseContextId($backupCourseId);
        self::setBackupCourseContext($backupCourseId, $backupCourseContextId);

        $this->createMoodleBackupXml($tempDir, $version);

        $activities = $this->getActivities();

        $courseExport = new CourseExport($this->course, $activities);
        $courseExport->exportCourse($tempDir);

        $pageExport = new PageExport($this->course);
        $pageFiles = [];

        // Force export of the synthetic introduction activity when it exists.
        // This guarantees that activities/page_910000000/module.xml is created
        // when the backup declares the introduction page in moodle_backup.xml.
        if ($this->hasCourseIntroduction()) {
            $pageExport->export(0, $tempDir, PageExport::INTRO_PAGE_MODULE_ID, 0);

            $introPageData = $pageExport->getData(0, 0, PageExport::INTRO_PAGE_MODULE_ID);
            if (!empty($introPageData['files'])) {
                $pageFiles = array_merge($pageFiles, $introPageData['files']);
            }
        }

        // Collect embedded files for real page activities.
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'page') {
                continue;
            }

            // The synthetic introduction page was already exported explicitly above.
            if ((int) ($activity['id'] ?? 0) === 0) {
                continue;
            }

            $pageData = $pageExport->getData(
                (int) $activity['id'],
                (int) $activity['sectionid'],
                (int) $activity['moduleid']
            );

            if (!empty($pageData['files'])) {
                $pageFiles = array_merge($pageFiles, $pageData['files']);
            }
        }

        $resourceFiles = [];
        $resourceExport = new ResourceExport($this->course);

        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'resource') {
                continue;
            }

            $resourceData = $resourceExport->getData(
                (int) $activity['id'],
                (int) $activity['sectionid'],
                (int) $activity['moduleid']
            );

            if (!empty($resourceData['files'])) {
                $resourceFiles = array_merge($resourceFiles, $resourceData['files']);
            }
        }

        $quizFiles = [];
        $quizExport = new QuizExport($this->course);

        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'quiz') {
                continue;
            }

            $quizData = $quizExport->getData(
                (int) $activity['id'],
                (int) $activity['sectionid'],
                (int) $activity['moduleid']
            );

            if (!empty($quizData['files'])) {
                $quizFiles = array_merge($quizFiles, $quizData['files']);
            }
        }

        $urlFiles = [];
        $urlExport = new UrlExport($this->course);

        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') !== 'url') {
                continue;
            }

            $urlData = $urlExport->getData(
                (int) $activity['id'],
                (int) $activity['sectionid'],
                (int) $activity['moduleid']
            );

            if (!empty($urlData['files'])) {
                $urlFiles = array_merge($urlFiles, $urlData['files']);
            }
        }

        $fileExport = new FileExport($this->course);
        $filesData = $fileExport->getFilesData();
        $filesData['files'] = $this->mergeUniqueFiles(array_merge(
            $filesData['files'],
            $pageFiles,
            $resourceFiles,
            $quizFiles,
            $urlFiles
        ));
        $fileExport->exportFiles($filesData, $tempDir);

        $this->exportSections($tempDir, $activities);
        $this->exportRootXmlFiles($tempDir);

        $exportedFile = $this->createMbzFile($tempDir);
        $this->cleanupTempDir($tempDir);

        return $exportedFile;
    }

    /**
     * Merge file definitions avoiding duplicate files.xml ids.
     */
    private function mergeUniqueFiles(array $files): array
    {
        $unique = [];

        foreach ($files as $file) {
            $id = (string) ($file['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $unique[$id] = $file;
        }

        return array_values($unique);
    }

    /**
     * Check whether the course introduction contains HTML content.
     */
    private function hasCourseIntroduction(): bool
    {
        $introText = trim((string) ($this->course->resources[RESOURCE_TOOL_INTRO]['course_homepage']->intro_text ?? ''));

        return $introText !== '';
    }

    /**
     * Export questions data to XML file.
     */
    public function exportQuestionsXml(array $questionsData, string $exportDir): void
    {
        $quizExport = new QuizExport($this->course);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<question_categories>'.PHP_EOL;

        $rootByContext = [];
        $writtenCats = [];

        foreach ($questionsData as $quiz) {
            $ctx = (int) ($quiz['contextid'] ?? 0);
            $courseId = (int) ($quiz['courseid'] ?? 0);

            if ($ctx <= 0 || $courseId <= 0) {
                continue;
            }

            if (!isset($rootByContext[$ctx])) {
                $rootId = $this->buildRootQuestionCategoryId($ctx);
                $rootByContext[$ctx] = $rootId;

                $xml .= '  <question_category id="'.$rootId.'">'.PHP_EOL;
                $xml .= '    <name>Top</name>'.PHP_EOL;
                $xml .= '    <contextid>'.$ctx.'</contextid>'.PHP_EOL;
                $xml .= '    <contextlevel>50</contextlevel>'.PHP_EOL;
                $xml .= '    <contextinstanceid>'.$courseId.'</contextinstanceid>'.PHP_EOL;
                $xml .= '    <info>Top category</info>'.PHP_EOL;
                $xml .= '    <infoformat>0</infoformat>'.PHP_EOL;
                $xml .= '    <stamp>moodle+'.time().'+CATEGORYSTAMP</stamp>'.PHP_EOL;
                $xml .= '    <parent>0</parent>'.PHP_EOL;
                $xml .= '    <sortorder>999</sortorder>'.PHP_EOL;
                $xml .= '    <idnumber>$@NULL@$</idnumber>'.PHP_EOL;
                $xml .= '    <questions></questions>'.PHP_EOL;
                $xml .= '  </question_category>'.PHP_EOL;
            }
        }

        foreach ($questionsData as $quiz) {
            if (empty($quiz['questions'])) {
                continue;
            }

            $ctx = (int) ($quiz['contextid'] ?? 0);
            $courseId = (int) ($quiz['courseid'] ?? 0);

            if ($ctx <= 0 || $courseId <= 0) {
                continue;
            }

            $rootId = (int) ($rootByContext[$ctx] ?? 0);
            if ($rootId <= 0) {
                $rootId = $this->buildRootQuestionCategoryId($ctx);
                $rootByContext[$ctx] = $rootId;
            }

            $catId = (int) ($quiz['question_category_id'] ?? 0);
            if ($catId <= 0) {
                $moduleId = (int) ($quiz['moduleid'] ?? 0);
                $catId = 1000000000 + max(1, $moduleId);
            }

            $catKey = $ctx.':'.$catId;
            if (isset($writtenCats[$catKey])) {
                continue;
            }
            $writtenCats[$catKey] = true;

            $xml .= '  <question_category id="'.$catId.'">'.PHP_EOL;
            $xml .= '    <name>Default for '.htmlspecialchars((string) ($quiz['name'] ?? 'Quiz')).'</name>'.PHP_EOL;
            $xml .= '    <contextid>'.$ctx.'</contextid>'.PHP_EOL;
            $xml .= '    <contextlevel>50</contextlevel>'.PHP_EOL;
            $xml .= '    <contextinstanceid>'.$courseId.'</contextinstanceid>'.PHP_EOL;
            $xml .= '    <info>Default questions category</info>'.PHP_EOL;
            $xml .= '    <infoformat>0</infoformat>'.PHP_EOL;
            $xml .= '    <stamp>moodle+'.time().'+CATEGORYSTAMP</stamp>'.PHP_EOL;
            $xml .= '    <parent>'.$rootId.'</parent>'.PHP_EOL;
            $xml .= '    <sortorder>999</sortorder>'.PHP_EOL;
            $xml .= '    <idnumber>$@NULL@$</idnumber>'.PHP_EOL;
            $xml .= '    <questions>'.PHP_EOL;

            foreach ($quiz['questions'] as $question) {
                $xml .= $quizExport->exportQuestion($question);
            }

            $xml .= '    </questions>'.PHP_EOL;
            $xml .= '  </question_category>'.PHP_EOL;
        }

        $xml .= '</question_categories>'.PHP_EOL;

        file_put_contents($exportDir.'/questions.xml', $xml);
    }

    /**
     * Build a stable root question category id per contextid.
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
     */
    public static function getAdminUserData(): array
    {
        return self::$adminUserData;
    }

    /**
     * Fills missing resources from the learnpath into the course structure.
     */
    private function fillResourcesFromLearnpath(object $complete): void
    {
        if (!isset($this->course->resources['learnpath'])) {
            return;
        }

        foreach ($this->course->resources['learnpath'] as $learnpath) {
            if (!isset($learnpath->items)) {
                continue;
            }

            foreach ($learnpath->items as $item) {
                $type = $item['item_type'];
                $resourceId = $item['path'];

                if (isset($complete->resources[$type][$resourceId]) && !isset($this->course->resources[$type][$resourceId])) {
                    $this->course->resources[$type][$resourceId] = $complete->resources[$type][$resourceId];
                }
            }
        }
    }

    /**
     * Fills missing exercise questions related to quizzes in the course.
     */
    private function fillQuestionsFromQuiz(object $complete): void
    {
        if (!isset($this->course->resources['quiz'])) {
            return;
        }

        foreach ($this->course->resources['quiz'] as $quiz) {
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

    /**
     * Export root XML files such as badges, completion, gradebook, etc.
     */
    private function exportRootXmlFiles(string $exportDir): void
    {
        $this->exportContextsXml($exportDir);
        $this->exportBadgesXml($exportDir);
        $this->exportCompletionXml($exportDir);
        $this->exportGradebookXml($exportDir);
        $this->exportGradeHistoryXml($exportDir);
        $this->exportGroupsXml($exportDir);
        $this->exportOutcomesXml($exportDir);

        $activities = $this->getActivities();
        $questionsData = [];
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') === 'quiz') {
                $quizExport = new QuizExport($this->course);
                $quizData = $quizExport->getData(
                    (int) $activity['id'],
                    (int) $activity['sectionid'],
                    (int) $activity['moduleid']
                );

                $questionsData[] = $quizData;
            }
        }
        $this->exportQuestionsXml($questionsData, $exportDir);

        $this->exportRolesXml($exportDir);
        $this->exportScalesXml($exportDir);
        $this->exportUsersXml($exportDir);
    }

    /**
     * Create the moodle_backup.xml file with the required course details.
     */
    private function createMoodleBackupXml(string $destinationDir, int $version): void
    {
        $courseInfo = api_get_course_info($this->course->code);
        $backupId = md5(uniqid(mt_rand(), true));
        $siteHash = md5(uniqid(mt_rand(), true));
        $wwwRoot = api_get_path(WEB_PATH);

        $courseStartDate = strtotime($courseInfo['creation_date']);
        $courseEndDate = $courseStartDate + (365 * 24 * 60 * 60);

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<moodle_backup>'.PHP_EOL;
        $xmlContent .= '  <information>'.PHP_EOL;

        $xmlContent .= '    <name>backup-'.htmlspecialchars((string) $courseInfo['code']).'.mbz</name>'.PHP_EOL;
        $xmlContent .= '    <moodle_version>'.($version === 3 ? '2021051718' : '2022041900').'</moodle_version>'.PHP_EOL;
        $xmlContent .= '    <moodle_release>'.($version === 3 ? '3.11.18 (Build: 20231211)' : '4.x version here').'</moodle_release>'.PHP_EOL;
        $xmlContent .= '    <backup_version>'.($version === 3 ? '2021051700' : '2022041900').'</backup_version>'.PHP_EOL;
        $xmlContent .= '    <backup_release>'.($version === 3 ? '3.11' : '4.x').'</backup_release>'.PHP_EOL;
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

        $sections = $this->getSections();
        if (!empty($sections)) {
            $xmlContent .= '      <sections>'.PHP_EOL;
            foreach ($sections as $section) {
                $xmlContent .= '        <section>'.PHP_EOL;
                $xmlContent .= '          <sectionid>'.$section['id'].'</sectionid>'.PHP_EOL;
                $xmlContent .= '          <title>'.htmlspecialchars($this->sanitizeBackupTitle((string) $section['name'])).'</title>'.PHP_EOL;
                $xmlContent .= '          <directory>sections/section_'.$section['id'].'</directory>'.PHP_EOL;
                $xmlContent .= '        </section>'.PHP_EOL;
            }
            $xmlContent .= '      </sections>'.PHP_EOL;
        }

        $activities = $this->getActivities();
        if (!empty($activities)) {
            $xmlContent .= '      <activities>'.PHP_EOL;
            foreach ($activities as $activity) {
                $xmlContent .= '        <activity>'.PHP_EOL;
                $xmlContent .= '          <moduleid>'.$activity['moduleid'].'</moduleid>'.PHP_EOL;
                $xmlContent .= '          <sectionid>'.$activity['sectionid'].'</sectionid>'.PHP_EOL;
                $xmlContent .= '          <modulename>'.htmlspecialchars((string) $activity['modulename']).'</modulename>'.PHP_EOL;
                $xmlContent .= '          <title>'.htmlspecialchars($this->sanitizeBackupTitle((string) $activity['title'])).'</title>'.PHP_EOL;
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
        $settings = $this->exportBackupSettings($sections, $activities);
        foreach ($settings as $setting) {
            $xmlContent .= '      <setting>'.PHP_EOL;
            $xmlContent .= '        <level>'.htmlspecialchars((string) $setting['level']).'</level>'.PHP_EOL;
            $xmlContent .= '        <name>'.htmlspecialchars((string) $setting['name']).'</name>'.PHP_EOL;
            $xmlContent .= '        <value>'.$setting['value'].'</value>'.PHP_EOL;
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

        $xmlFile = $destinationDir.'/moodle_backup.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Get all sections from the course ordered by LP display_order.
     * Uses the same activities list and module ids as moodle_backup.xml.
     */
    private function getSections(?array $activities = null): array
    {
        $sections = [];

        if ($activities === null) {
            $activities = $this->getActivities();
        }

        $activitiesBySection = $this->groupActivitiesBySection($activities);
        $sectionExport = new SectionExport($this->course, $activitiesBySection);

        $learnpaths = $this->course->resources[RESOURCE_LEARNPATH] ?? [];

        usort($learnpaths, static function ($a, $b): int {
            $aOrder = (int) ($a->display_order ?? 0);
            $bOrder = (int) ($b->display_order ?? 0);

            return $aOrder <=> $bOrder;
        });

        foreach ($learnpaths as $learnpath) {
            if ((int) $learnpath->lp_type === 1) {
                $sections[] = $sectionExport->getSectionData($learnpath);
            }
        }

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

    /**
     * Get all activities from the course.
     * Activities are ordered by learnpath display_order when available.
     */
    private function getActivities(): array
    {
        $activities = [];
        $activities[] = [
            'id' => ActivityExport::DOCS_MODULE_ID,
            'sectionid' => 0,
            'modulename' => 'folder',
            'moduleid' => ActivityExport::DOCS_MODULE_ID,
            'title' => 'Documents',
            'order' => 0,
        ];

        $learnpaths = $this->course->resources[RESOURCE_LEARNPATH] ?? [];

        usort($learnpaths, static function ($a, $b): int {
            return (int) ($a->display_order ?? 0) <=> (int) ($b->display_order ?? 0);
        });

        foreach ($learnpaths as $lp) {
            if ((int) ($lp->lp_type ?? 0) !== 1) {
                continue;
            }

            $sectionId = (int) ($lp->source_id ?? 0);
            if ($sectionId <= 0 || empty($lp->items)) {
                continue;
            }

            foreach ($lp->items as $it) {
                $lpItemId = isset($it['id']) ? (int) $it['id'] : 0;
                $itemType = (string) ($it['item_type'] ?? '');
                $path = $it['path'] ?? null;
                $title = (string) ($it['title'] ?? '');
                $order = isset($it['display_order']) ? (int) $it['display_order'] : 0;

                $moduleName = null;
                $instanceId = null;

                if ($itemType === 'quiz') {
                    $moduleName = 'quiz';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ($itemType === 'link') {
                    $moduleName = 'url';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ($itemType === 'student_publication') {
                    $moduleName = 'assign';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ($itemType === 'survey') {
                    $moduleName = 'feedback';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ($itemType === 'forum') {
                    $moduleName = 'forum';
                    $instanceId = is_numeric($path) ? (int) $path : null;
                } elseif ($itemType === 'document') {
                    $docId = is_numeric($path) ? (int) $path : 0;
                    if ($docId > 0) {
                        $doc = \DocumentManager::get_document_data_by_id($docId, $this->course->code);
                        if (!empty($doc)) {
                            $docPath = (string) ($doc['path'] ?? '');
                            $ext = strtolower(pathinfo($docPath, PATHINFO_EXTENSION));

                            if ($ext === 'html' || $ext === 'htm') {
                                $moduleName = 'page';
                                $instanceId = $docId;
                                if ($title === '') {
                                    $title = (string) ($doc['title'] ?? '');
                                }
                            } elseif (($doc['filetype'] ?? '') === 'file') {
                                $moduleName = 'resource';
                                $instanceId = $docId;
                                if ($title === '') {
                                    $title = (string) ($doc['title'] ?? '');
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
                    'title' => $this->sanitizeBackupTitle($title !== '' ? $title : $moduleName),
                    'order' => $order,
                ];
            }
        }

        $sectionExport = new SectionExport($this->course);
        $generalActivities = $sectionExport->getActivitiesForGeneral();

        foreach ($generalActivities as $ga) {
            if (($ga['modulename'] ?? '') === 'folder') {
                continue;
            }

            $activityId = (int) ($ga['id'] ?? 0);
            $moduleName = (string) ($ga['modulename'] ?? '');
            $moduleId = (int) ($ga['moduleid'] ?? 0);

            if ($moduleName === 'page' && $activityId === 0) {
                if (!$this->hasCourseIntroduction()) {
                    continue;
                }

                $moduleId = PageExport::INTRO_PAGE_MODULE_ID;
            }

            $activities[] = [
                'id' => $activityId,
                'sectionid' => 0,
                'modulename' => $moduleName,
                'moduleid' => $moduleId,
                'title' => $this->sanitizeBackupTitle((string) ($ga['name'] ?? '')),
                'order' => 0,
            ];
        }

        $grouped = [];
        $seqBySec = [];

        foreach ($activities as $a) {
            $sid = (int) ($a['sectionid'] ?? 0);
            if (!isset($grouped[$sid])) {
                $grouped[$sid] = [];
                $seqBySec[$sid] = 0;
            }

            $ord = (int) ($a['order'] ?? 0);
            if ($ord <= 0) {
                $seqBySec[$sid]++;
                $ord = 1000 + $seqBySec[$sid];
            }

            $a['_sort'] = $ord;
            $grouped[$sid][] = $a;
        }

        $sorted = [];
        foreach ($grouped as $sid => $list) {
            usort($list, static fn (array $x, array $y): int => $x['_sort'] <=> $y['_sort']);
            foreach ($list as $x) {
                unset($x['_sort'], $x['order']);
                $sorted[] = $x;
            }
        }

        return $sorted;
    }

    /**
     * Sanitize titles used in moodle_backup.xml contents.
     */
    private function sanitizeBackupTitle(string $title): string
    {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = strip_tags($title);
        $title = str_replace("\xc2\xa0", ' ', $title);
        $title = preg_replace('/\s+/u', ' ', trim($title));

        return $title;
    }

    /**
     * Export the sections of the course.
     */
    private function exportSections(string $exportDir, array $activities): void
    {
        $sections = $this->getSections($activities);
        $activitiesBySection = $this->groupActivitiesBySection($activities);

        $sectionExport = new SectionExport($this->course, $activitiesBySection);

        foreach ($sections as $section) {
            $sectionExport->exportSection((int) $section['id'], $exportDir);
        }
    }

    /**
     * Convert MoodleExport::getActivities() output into the structure SectionExport expects.
     */
    private function groupActivitiesBySection(array $activities): array
    {
        $bySection = [];

        foreach ($activities as $a) {
            $sid = (int) ($a['sectionid'] ?? 0);

            $bySection[$sid][] = [
                'id' => (int) ($a['id'] ?? 0),
                'moduleid' => (int) ($a['moduleid'] ?? 0),
                'modulename' => (string) ($a['modulename'] ?? ''),
                'name' => (string) ($a['title'] ?? ''),
                'sectionid' => $sid,
            ];
        }

        return $bySection;
    }

    /**
     * Create a .mbz (ZIP) file from the exported data.
     */
    private function createMbzFile(string $sourceDir): string
    {
        $zip = new ZipArchive();
        $zipFile = $sourceDir.'.mbz';

        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception(get_lang('ErrorCreatingZip'));
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);

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

    /**
     * Clean up the temporary directory used for export.
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
            $path = "$dir/$file";
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Export badges data to XML file.
     */
    private function exportBadgesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<badges>'.PHP_EOL;
        $xmlContent .= '</badges>';
        file_put_contents($exportDir.'/badges.xml', $xmlContent);
    }

    /**
     * Export course completion data to XML file.
     */
    private function exportCompletionXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<completions>'.PHP_EOL;
        $xmlContent .= '</completions>';
        file_put_contents($exportDir.'/completion.xml', $xmlContent);
    }

    /**
     * Export gradebook data to XML file.
     */
    private function exportGradebookXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<gradebook>'.PHP_EOL;
        $xmlContent .= '</gradebook>';
        file_put_contents($exportDir.'/gradebook.xml', $xmlContent);
    }

    /**
     * Export grade history data to XML file.
     */
    private function exportGradeHistoryXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<grade_history>'.PHP_EOL;
        $xmlContent .= '</grade_history>';
        file_put_contents($exportDir.'/grade_history.xml', $xmlContent);
    }

    /**
     * Export groups data to XML file.
     */
    private function exportGroupsXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<groups>'.PHP_EOL;
        $xmlContent .= '</groups>';
        file_put_contents($exportDir.'/groups.xml', $xmlContent);
    }

    /**
     * Export outcomes data to XML file.
     */
    private function exportOutcomesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<outcomes>'.PHP_EOL;
        $xmlContent .= '</outcomes>';
        file_put_contents($exportDir.'/outcomes.xml', $xmlContent);
    }

    /**
     * Export roles data to XML file.
     */
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

    /**
     * Export scales data to XML file.
     */
    private function exportScalesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<scales>'.PHP_EOL;
        $xmlContent .= '</scales>';
        file_put_contents($exportDir.'/scales.xml', $xmlContent);
    }

    /**
     * Export the user XML with admin user data.
     */
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

        if (isset($adminData['preferences']) && is_array($adminData['preferences'])) {
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
     * Export the backup settings, including dynamic settings for sections and activities.
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
            $settings[] = [
                'level' => 'activity',
                'activity' => $activity['modulename'].'_'.$activity['moduleid'],
                'name' => $activity['modulename'].'_'.$activity['moduleid'].'_userinfo',
                'value' => '1',
            ];
        }

        return $settings;
    }

    /**
     * Generic resolver for Moodle course module id from an LP item occurrence.
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
     * Build a stable backup course context id.
     */
    private function buildBackupCourseContextId(int $courseId): int
    {
        return 700000000 + max(1, $courseId);
    }

    /**
     * Store backup course mapping used by question bank and question files.
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
     * Export minimal contexts required by Moodle restore.
     */
    private function exportContextsXml(string $exportDir): void
    {
        $courseId = self::getBackupCourseId();
        $courseContextId = self::getBackupCourseContextId();
        $activities = $this->getActivities();

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<contexts>'.PHP_EOL;

        $xmlContent .= '  <context id="1">'.PHP_EOL;
        $xmlContent .= '    <contextlevel>10</contextlevel>'.PHP_EOL;
        $xmlContent .= '    <instanceid>0</instanceid>'.PHP_EOL;
        $xmlContent .= '    <path>/1</path>'.PHP_EOL;
        $xmlContent .= '    <depth>1</depth>'.PHP_EOL;
        $xmlContent .= '    <parentcontextid>0</parentcontextid>'.PHP_EOL;
        $xmlContent .= '  </context>'.PHP_EOL;

        $xmlContent .= '  <context id="'.$courseContextId.'">'.PHP_EOL;
        $xmlContent .= '    <contextlevel>50</contextlevel>'.PHP_EOL;
        $xmlContent .= '    <instanceid>'.$courseId.'</instanceid>'.PHP_EOL;
        $xmlContent .= '    <path>/1/'.$courseContextId.'</path>'.PHP_EOL;
        $xmlContent .= '    <depth>2</depth>'.PHP_EOL;
        $xmlContent .= '    <parentcontextid>1</parentcontextid>'.PHP_EOL;
        $xmlContent .= '  </context>'.PHP_EOL;

        $seen = [
            1 => true,
            $courseContextId => true,
        ];

        foreach ($activities as $activity) {
            $moduleContextId = (int) ($activity['moduleid'] ?? 0);
            if ($moduleContextId <= 0 || isset($seen[$moduleContextId])) {
                continue;
            }

            $seen[$moduleContextId] = true;

            $xmlContent .= '  <context id="'.$moduleContextId.'">'.PHP_EOL;
            $xmlContent .= '    <contextlevel>70</contextlevel>'.PHP_EOL;
            $xmlContent .= '    <instanceid>'.$moduleContextId.'</instanceid>'.PHP_EOL;
            $xmlContent .= '    <path>/1/'.$courseContextId.'/'.$moduleContextId.'</path>'.PHP_EOL;
            $xmlContent .= '    <depth>3</depth>'.PHP_EOL;
            $xmlContent .= '    <parentcontextid>'.$courseContextId.'</parentcontextid>'.PHP_EOL;
            $xmlContent .= '  </context>'.PHP_EOL;
        }

        $xmlContent .= '</contexts>'.PHP_EOL;

        file_put_contents($exportDir.'/contexts.xml', $xmlContent);
    }
}
