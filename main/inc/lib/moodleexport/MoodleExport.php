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

    /**
     * Constructor to initialize the course object.
     */
    public function __construct(object $course)
    {
        // Build the complete course object
        $cb = new CourseBuilder('complete');
        $complete = $cb->build();

        // Store the selected course
        $this->course = $course;

        // Fill missing resources from learnpath
        $this->fillResourcesFromLearnpath($complete);

        // Fill missing quiz questions
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
        $this->exportSections($tempDir, $activities);

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

        foreach ($questionsData as $quiz) {
            $categoryId = $quiz['questions'][0]['questioncategoryid'] ?? '1';
            $hash = md5($categoryId.$quiz['name']);
            if (isset($categoryHashes[$hash])) {
                continue;
            }
            $categoryHashes[$hash] = true;
            $xmlContent .= '  <question_category id="'.$categoryId.'">'.PHP_EOL;
            $xmlContent .= '    <name>Default for '.htmlspecialchars($quiz['name'] ?? 'Unknown').'</name>'.PHP_EOL;
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
     */
    public static function getAdminUserData(): array
    {
        return self::$adminUserData;
    }

    /**
     * Fills missing resources from the learnpath into the course structure.
     *
     * This method checks if the course has a learnpath and ensures that all
     * referenced resources (documents, quizzes, etc.) exist in the course's
     * resources array by pulling them from the complete course object.
     */
    private function fillResourcesFromLearnpath(object $complete): void
    {
        // Check if the course has learnpath
        if (!isset($this->course->resources['learnpath'])) {
            return;
        }

        foreach ($this->course->resources['learnpath'] as $learnpathId => $learnpath) {
            if (!isset($learnpath->items)) {
                continue;
            }

            foreach ($learnpath->items as $item) {
                $type = $item['item_type']; // Resource type (document, quiz, etc.)
                $resourceId = $item['path']; // Resource ID in resources

                // Check if the resource exists in the complete object and is not yet in the course resources
                if (isset($complete->resources[$type][$resourceId]) && !isset($this->course->resources[$type][$resourceId])) {
                    // Add the resource directly to the original course resources structure
                    $this->course->resources[$type][$resourceId] = $complete->resources[$type][$resourceId];
                }
            }
        }
    }

    /**
     * Fills missing exercise questions related to quizzes in the course.
     *
     * This method checks if the course has quizzes and ensures that all referenced
     * questions exist in the course's resources array by pulling them from the complete
     * course object.
     */
    private function fillQuestionsFromQuiz(object $complete): void
    {
        // Check if the course has quizzes
        if (!isset($this->course->resources['quiz'])) {
            return;
        }

        foreach ($this->course->resources['quiz'] as $quizId => $quiz) {
            if (!isset($quiz->obj->question_ids)) {
                continue;
            }

            foreach ($quiz->obj->question_ids as $questionId) {
                // Check if the question exists in the complete object and is not yet in the course resources
                if (isset($complete->resources['Exercise_Question'][$questionId]) && !isset($this->course->resources['Exercise_Question'][$questionId])) {
                    // Add the question directly to the original course resources structure
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
        $this->exportBadgesXml($exportDir);
        $this->exportCompletionXml($exportDir);
        $this->exportGradebookXml($exportDir);
        $this->exportGradeHistoryXml($exportDir);
        $this->exportGroupsXml($exportDir);
        $this->exportOutcomesXml($exportDir);

        // Export quizzes and their questions
        $activities = $this->getActivities();
        $questionsData = [];
        foreach ($activities as $activity) {
            if (($activity['modulename'] ?? '') === 'quiz') {
                $quizExport = new QuizExport($this->course);
                $quizData = $quizExport->getData((int) $activity['id'], (int) $activity['sectionid']);
                $quizData['moduleid'] = (int) $activity['moduleid'];
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
        // Generate course information and backup metadata
        $courseInfo = api_get_course_info($this->course->code);
        $backupId = md5(uniqid(mt_rand(), true));
        $siteHash = md5(uniqid(mt_rand(), true));
        $wwwRoot = api_get_path(WEB_PATH);

        $courseStartDate = strtotime($courseInfo['creation_date']);
        $courseEndDate = $courseStartDate + (365 * 24 * 60 * 60);

        // Build the XML content for the backup
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<moodle_backup>'.PHP_EOL;
        $xmlContent .= '  <information>'.PHP_EOL;

        $xmlContent .= '    <name>backup-'.htmlspecialchars($courseInfo['code']).'.mbz</name>'.PHP_EOL;
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
        $xmlContent .= '    <original_course_id>'.htmlspecialchars($courseInfo['real_id']).'</original_course_id>'.PHP_EOL;
        $xmlContent .= '    <original_course_format>'.get_lang('Topics').'</original_course_format>'.PHP_EOL;
        $xmlContent .= '    <original_course_fullname>'.htmlspecialchars($courseInfo['title']).'</original_course_fullname>'.PHP_EOL;
        $xmlContent .= '    <original_course_shortname>'.htmlspecialchars($courseInfo['code']).'</original_course_shortname>'.PHP_EOL;
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

        // Contents with activities and sections
        $xmlContent .= '    <contents>'.PHP_EOL;

        // Export sections dynamically and add them to the XML
        $sections = $this->getSections();
        if (!empty($sections)) {
            $xmlContent .= '      <sections>'.PHP_EOL;
            foreach ($sections as $section) {
                $xmlContent .= '        <section>'.PHP_EOL;
                $xmlContent .= '          <sectionid>'.$section['id'].'</sectionid>'.PHP_EOL;
                $xmlContent .= '          <title>'.htmlspecialchars($section['name']).'</title>'.PHP_EOL;
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
                $xmlContent .= '          <modulename>'.htmlspecialchars($activity['modulename']).'</modulename>'.PHP_EOL;
                $xmlContent .= '          <title>'.htmlspecialchars($activity['title']).'</title>'.PHP_EOL;
                $xmlContent .= '          <directory>activities/'.$activity['modulename'].'_'.$activity['moduleid'].'</directory>'.PHP_EOL;
                $xmlContent .= '        </activity>'.PHP_EOL;
            }
            $xmlContent .= '      </activities>'.PHP_EOL;
        }

        // Course directory
        $xmlContent .= '      <course>'.PHP_EOL;
        $xmlContent .= '        <courseid>'.$courseInfo['real_id'].'</courseid>'.PHP_EOL;
        $xmlContent .= '        <title>'.htmlspecialchars($courseInfo['title']).'</title>'.PHP_EOL;
        $xmlContent .= '        <directory>course</directory>'.PHP_EOL;
        $xmlContent .= '      </course>'.PHP_EOL;

        $xmlContent .= '    </contents>'.PHP_EOL;

        // Backup settings
        $xmlContent .= '    <settings>'.PHP_EOL;
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
     * Get all sections from the course ordered by LP display_order.
     * Uses the SAME activities list (and moduleid) as moodle_backup.xml.
     */
    private function getSections(?array $activities = null): array
    {
        $sections = [];

        // Compute activities once if not provided
        if ($activities === null) {
            $activities = $this->getActivities();
        }

        $activitiesBySection = $this->groupActivitiesBySection($activities);

        // We only need SectionExport for metadata (name/summary/visible/timemodified),
        // but it MUST reuse the precomputed activities to keep moduleid consistent.
        $sectionExport = new SectionExport($this->course, $activitiesBySection);

        // Safety: if there is no learnpath resource, return only the general section
        $learnpaths = $this->course->resources[RESOURCE_LEARNPATH] ?? [];

        // Sort LPs by display_order to respect the order defined in c_lp
        usort($learnpaths, static function ($a, $b): int {
            $aOrder = (int) ($a->display_order ?? 0);
            $bOrder = (int) ($b->display_order ?? 0);

            return $aOrder <=> $bOrder;
        });

        foreach ($learnpaths as $learnpath) {
            // We only export "real" LPs (type 1)
            if ((int) $learnpath->lp_type === 1) {
                $sections[] = $sectionExport->getSectionData($learnpath);
            }
        }

        // Add a general section for resources without a lesson
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
        // "Documents" folder pseudo-activity (always in section 0)
        $activities[] = [
            'id'         => ActivityExport::DOCS_MODULE_ID,
            'sectionid'  => 0,
            'modulename' => 'folder',
            'moduleid'   => ActivityExport::DOCS_MODULE_ID,
            'title'      => 'Documents',
            'order'      => 0,
        ];

        // Build activities from LP items (one course module per LP item)
        $learnpaths = $this->course->resources[RESOURCE_LEARNPATH] ?? [];

        // Sort by LP display_order to respect c_lp order
        usort($learnpaths, static function ($a, $b): int {
            return (int)($a->display_order ?? 0) <=> (int)($b->display_order ?? 0);
        });

        foreach ($learnpaths as $lp) {
            // Only "real" LPs
            if ((int)($lp->lp_type ?? 0) !== 1) {
                continue;
            }

            $sectionId = (int)($lp->source_id ?? 0);
            if ($sectionId <= 0 || empty($lp->items)) {
                continue;
            }

            foreach ($lp->items as $it) {
                $lpItemId = isset($it['id']) ? (int)$it['id'] : 0;
                $itemType = (string)($it['item_type'] ?? '');
                $path     = $it['path'] ?? null;
                $title    = (string)($it['title'] ?? '');
                $order    = isset($it['display_order']) ? (int)$it['display_order'] : 0;

                // Map LP item_type to Moodle modulename
                $moduleName = null;
                $instanceId = null;

                if ($itemType === 'quiz') {
                    $moduleName = 'quiz';
                    $instanceId = is_numeric($path) ? (int)$path : null;
                } elseif ($itemType === 'link') {
                    $moduleName = 'url';
                    $instanceId = is_numeric($path) ? (int)$path : null;
                } elseif ($itemType === 'student_publication') {
                    $moduleName = 'assign';
                    $instanceId = is_numeric($path) ? (int)$path : null;
                } elseif ($itemType === 'survey') {
                    $moduleName = 'feedback';
                    $instanceId = is_numeric($path) ? (int)$path : null;
                } elseif ($itemType === 'forum') {
                    $moduleName = 'forum';
                    $instanceId = is_numeric($path) ? (int)$path : null;
                } elseif ($itemType === 'document') {
                    $docId = is_numeric($path) ? (int)$path : 0;
                    if ($docId > 0) {
                        $doc = \DocumentManager::get_document_data_by_id($docId, $this->course->code);
                        if (!empty($doc)) {
                            $docPath = (string)($doc['path'] ?? '');
                            $ext = strtolower(pathinfo($docPath, PATHINFO_EXTENSION));

                            if ($ext === 'html' || $ext === 'htm') {
                                $moduleName = 'page';
                                $instanceId = $docId;
                                if ($title === '') {
                                    $title = (string)($doc['title'] ?? '');
                                }
                            } elseif (($doc['filetype'] ?? '') === 'file') {
                                $moduleName = 'resource';
                                $instanceId = $docId;
                                if ($title === '') {
                                    $title = (string)($doc['title'] ?? '');
                                }
                            }
                        }
                    }
                }

                // Skip unsupported / invalid
                if (empty($moduleName) || empty($instanceId)) {
                    continue;
                }

                // Generic unique course module id per LP occurrence
                $moduleId = $this->resolveLpModuleId($moduleName, $lpItemId, (int)$instanceId);

                $activities[] = [
                    'id'         => (int)$instanceId,
                    'sectionid'  => $sectionId,
                    'modulename' => $moduleName,
                    'moduleid'   => $moduleId,
                    'title'      => $title !== '' ? $title : $moduleName,
                    'order'      => $order,
                ];
            }
        }

        // Add general section activities (items not in any LP)
        $sectionExport = new SectionExport($this->course);
        $generalActivities = $sectionExport->getActivitiesForGeneral();

        foreach ($generalActivities as $ga) {
            // Avoid duplicating the Documents folder (we added it already)
            if (($ga['modulename'] ?? '') === 'folder') {
                continue;
            }

            $activities[] = [
                'id'         => (int)($ga['id'] ?? 0),
                'sectionid'  => 0,
                'modulename' => (string)($ga['modulename'] ?? ''),
                'moduleid'   => (int)($ga['moduleid'] ?? 0),
                'title'      => (string)($ga['name'] ?? ''),
                'order'      => 0,
            ];
        }

        // Sort activities per section by LP display_order
        $grouped  = [];
        $seqBySec = [];

        foreach ($activities as $a) {
            $sid = (int)($a['sectionid'] ?? 0);
            if (!isset($grouped[$sid])) {
                $grouped[$sid] = [];
                $seqBySec[$sid] = 0;
            }

            $ord = (int)($a['order'] ?? 0);
            if ($ord <= 0) {
                $seqBySec[$sid]++;
                $ord = 1000 + $seqBySec[$sid];
            }

            $a['_sort'] = $ord;
            $grouped[$sid][] = $a;
        }

        $sorted = [];
        foreach ($grouped as $sid => $list) {
            usort($list, static fn(array $x, array $y): int => $x['_sort'] <=> $y['_sort']);
            foreach ($list as $x) {
                unset($x['_sort'], $x['order']);
                $sorted[] = $x;
            }
        }

        return $sorted;
    }

    /**
     * Export the sections of the course.
     */
    private function exportSections(string $exportDir, array $activities): void
    {
        $sections = $this->getSections($activities);
        $activitiesBySection = $this->groupActivitiesBySection($activities);

        // Reuse ONE instance to keep any internal caches stable
        $sectionExport = new SectionExport($this->course, $activitiesBySection);

        foreach ($sections as $section) {
            $sectionExport->exportSection((int) $section['id'], $exportDir);
        }
    }

    /**
     * Convert MoodleExport::getActivities() output into the structure SectionExport expects.
     * Ensures section.xml sequence uses the same moduleid as moodle_backup.xml.
     */
    private function groupActivitiesBySection(array $activities): array
    {
        $bySection = [];

        foreach ($activities as $a) {
            $sid = (int) ($a['sectionid'] ?? 0);

            $bySection[$sid][] = [
                'id'        => (int) ($a['id'] ?? 0),
                'moduleid'  => (int) ($a['moduleid'] ?? 0),
                'modulename'=> (string) ($a['modulename'] ?? ''),
                'name'      => (string) ($a['title'] ?? ''),
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

        // Preferences
        if (isset($adminData['preferences']) && is_array($adminData['preferences'])) {
            $xmlContent .= '    <preferences>'.PHP_EOL;
            foreach ($adminData['preferences'] as $preference) {
                $xmlContent .= '      <preference>'.PHP_EOL;
                $xmlContent .= '        <name>'.htmlspecialchars($preference['name']).'</name>'.PHP_EOL;
                $xmlContent .= '        <value>'.htmlspecialchars($preference['value']).'</value>'.PHP_EOL;
                $xmlContent .= '      </preference>'.PHP_EOL;
            }
            $xmlContent .= '    </preferences>'.PHP_EOL;
        } else {
            $xmlContent .= '    <preferences></preferences>'.PHP_EOL;
        }

        // Roles (empty for now)
        $xmlContent .= '    <roles>'.PHP_EOL;
        $xmlContent .= '      <role_overrides></role_overrides>'.PHP_EOL;
        $xmlContent .= '      <role_assignments></role_assignments>'.PHP_EOL;
        $xmlContent .= '    </roles>'.PHP_EOL;

        $xmlContent .= '  </user>'.PHP_EOL;
        $xmlContent .= '</users>';

        // Save the content to the users.xml file
        file_put_contents($exportDir.'/users.xml', $xmlContent);
    }

    /**
     * Export the backup settings, including dynamic settings for sections and activities.
     */
    private function exportBackupSettings(array $sections, array $activities): array
    {
        // root-level settings
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

        // section-level settings
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

        // activity-level settings
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
     * Maps module name to item_type of c_lp_item.
     * (c_lp_item.item_type: document, quiz, link, forum, student_publication, survey)
     */
    private function mapToLpItemType(string $moduleOrItemType): string
    {
        switch ($moduleOrItemType) {
            case 'page':
            case 'resource':
                return 'document';
            case 'assign':
                return 'student_publication';
            case 'url':
                return 'link';
            case 'feedback':
                return 'survey';
            default:
                return $moduleOrItemType; // quiz, forum...
        }
    }

    /** Index titles by section + type + id from the LP items (c_lp_item.title). */
    private function buildLpTitleIndex(): array
    {
        $idx = [];
        if (empty($this->course->resources[RESOURCE_LEARNPATH])) {
            return $idx;
        }
        foreach ($this->course->resources[RESOURCE_LEARNPATH] as $lp) {
            $sid = (int) $lp->source_id;
            if (empty($lp->items)) {
                continue;
            }
            foreach ($lp->items as $it) {
                $type = $this->mapToLpItemType($it['item_type']);
                $rid  = (string) $it['path'];
                $idx[$sid][$type][$rid] = $it['title'] ?? '';
            }
        }
        return $idx;
    }

    /** Returns the LP title if it exists; otherwise, use the fallback. */
    private function titleFromLp(array $idx, int $sectionId, string $moduleName, int $resourceId, string $fallback): string
    {
        if ($sectionId <= 0) {
            return $fallback;
        }
        $type = $this->mapToLpItemType($moduleName);
        $rid  = (string) $resourceId;
        return $idx[$sectionId][$type][$rid] ?? $fallback;
    }

    /**
     * Generic resolver for Moodle course module id from an LP item occurrence.
     * Keep folder/glossary stable (if you treat glossary as singleton).
     */
    private function resolveLpModuleId(string $moduleName, int $lpItemId, int $fallback): int
    {
        if ($lpItemId <= 0) {
            return $fallback;
        }

        // Keep special/singleton modules stable if needed
        if (in_array($moduleName, ['folder', 'glossary'], true)) {
            return $fallback;
        }

        return 900000000 + $lpItemId;
    }
}
