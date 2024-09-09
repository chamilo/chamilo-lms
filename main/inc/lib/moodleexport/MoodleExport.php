<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Exception;
use FillBlanks;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class MoodleExport.
 * Handles the export of a Moodle course in .mbz format.
 *
 * @package moodleexport
 */
class MoodleExport
{
    private $course;

    /**
     * Constructor to initialize the course object.
     */
    public function __construct(object $course)
    {
        $this->course = $course;
    }

    /**
     * Export the Moodle course in .mbz format.
     */
    public function export(string $courseId, string $exportDir, string $version)
    {
        $tempDir = api_get_path(SYS_ARCHIVE_PATH) . $exportDir;

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
        $fileExport = new FileExport($this->course);
        $filesData = $fileExport->getFilesData();
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
            if ($activity['modulename'] === 'quiz') {
                $quizExport = new QuizExport($this->course);
                $quizData = $quizExport->getQuizData($activity['id'], $activity['sectionid']);
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
    private function createMoodleBackupXml(string $destinationDir, string $version): void
    {
        // Generate course information and backup metadata
        $courseInfo = api_get_course_info($this->course->code);
        $backupId = md5(uniqid(mt_rand(), true));
        $siteHash = md5(uniqid(mt_rand(), true));
        $wwwRoot = api_get_path(WEB_PATH);

        // Build the XML content for the backup
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<moodle_backup>' . PHP_EOL;
        $xmlContent .= '  <information>' . PHP_EOL;

        $wwwRoot = api_get_path(WEB_PATH);

        $xmlContent .= '    <name>backup-' . htmlspecialchars($courseInfo['code']) . '.mbz</name>' . PHP_EOL;
        $xmlContent .= '    <moodle_version>' . ($version === '3' ? '2021051718' : '2022041900') . '</moodle_version>' . PHP_EOL;
        $xmlContent .= '    <moodle_release>' . ($version === '3' ? '3.11.18 (Build: 20231211)' : '4.x version here') . '</moodle_release>' . PHP_EOL;
        $xmlContent .= '    <backup_version>' . ($version === '3' ? '2021051700' : '2022041900') . '</backup_version>' . PHP_EOL;
        $xmlContent .= '    <backup_release>' . ($version === '3' ? '3.11' : '4.x') . '</backup_release>' . PHP_EOL;
        $xmlContent .= '    <backup_date>' . time() . '</backup_date>' . PHP_EOL;
        $xmlContent .= '    <mnet_remoteusers>0</mnet_remoteusers>' . PHP_EOL;
        $xmlContent .= '    <include_files>1</include_files>' . PHP_EOL;
        $xmlContent .= '    <include_file_references_to_external_content>0</include_file_references_to_external_content>' . PHP_EOL;
        $xmlContent .= '    <original_wwwroot>'.$wwwRoot.'</original_wwwroot>' . PHP_EOL;
        $xmlContent .= '    <original_site_identifier_hash>' . $siteHash . '</original_site_identifier_hash>' . PHP_EOL;
        $xmlContent .= '    <original_course_id>' . htmlspecialchars($courseInfo['real_id']) . '</original_course_id>' . PHP_EOL;
        $xmlContent .= '    <original_course_format>'.get_lang('Topics').'</original_course_format>' . PHP_EOL;
        $xmlContent .= '    <original_course_fullname>' . htmlspecialchars($courseInfo['title']) . '</original_course_fullname>' . PHP_EOL;
        $xmlContent .= '    <original_course_shortname>' . htmlspecialchars($courseInfo['code']) . '</original_course_shortname>' . PHP_EOL;
        $xmlContent .= '    <original_course_startdate>' . $courseInfo['startdate'] . '</original_course_startdate>' . PHP_EOL;
        $xmlContent .= '    <original_course_enddate>' . $courseInfo['enddate'] . '</original_course_enddate>' . PHP_EOL;
        $xmlContent .= '    <original_course_contextid>'.$courseInfo['real_id'].'</original_course_contextid>' . PHP_EOL;
        $xmlContent .= '    <original_system_contextid>'.api_get_current_access_url_id().'</original_system_contextid>' . PHP_EOL;

        $xmlContent .= '    <details>' . PHP_EOL;
        $xmlContent .= '      <detail backup_id="' . $backupId . '">' . PHP_EOL;
        $xmlContent .= '        <type>course</type>' . PHP_EOL;
        $xmlContent .= '        <format>moodle2</format>' . PHP_EOL;
        $xmlContent .= '        <interactive>1</interactive>' . PHP_EOL;
        $xmlContent .= '        <mode>10</mode>' . PHP_EOL;
        $xmlContent .= '        <execution>1</execution>' . PHP_EOL;
        $xmlContent .= '        <executiontime>0</executiontime>' . PHP_EOL;
        $xmlContent .= '      </detail>' . PHP_EOL;
        $xmlContent .= '    </details>' . PHP_EOL;

        // Contents with activities and sections
        $xmlContent .= '    <contents>' . PHP_EOL;

        // Export sections dynamically and add them to the XML
        $sections = $this->getSections();
        if (!empty($sections)) {
            $xmlContent .= '      <sections>' . PHP_EOL;
            foreach ($sections as $section) {
                $xmlContent .= '        <section>' . PHP_EOL;
                $xmlContent .= '          <sectionid>' . $section['id'] . '</sectionid>' . PHP_EOL;
                $xmlContent .= '          <title>' . htmlspecialchars($section['name']) . '</title>' . PHP_EOL;
                $xmlContent .= '          <directory>sections/section_' . $section['id'] . '</directory>' . PHP_EOL;
                $xmlContent .= '        </section>' . PHP_EOL;
            }
            $xmlContent .= '      </sections>' . PHP_EOL;
        }

        $activities = $this->getActivities();
        if (!empty($activities)) {
            $xmlContent .= '      <activities>' . PHP_EOL;
            foreach ($activities as $activity) {
                $xmlContent .= '        <activity>' . PHP_EOL;
                $xmlContent .= '          <moduleid>' . $activity['moduleid'] . '</moduleid>' . PHP_EOL;
                $xmlContent .= '          <sectionid>' . $activity['sectionid'] . '</sectionid>' . PHP_EOL;
                $xmlContent .= '          <modulename>' . htmlspecialchars($activity['modulename']) . '</modulename>' . PHP_EOL;
                $xmlContent .= '          <title>' . htmlspecialchars($activity['title']) . '</title>' . PHP_EOL;
                $xmlContent .= '          <directory>activities/' . $activity['modulename'] . '_' . $activity['moduleid'] . '</directory>' . PHP_EOL;
                $xmlContent .= '        </activity>' . PHP_EOL;
            }
            $xmlContent .= '      </activities>' . PHP_EOL;
        }

        // Course directory
        $xmlContent .= '      <course>' . PHP_EOL;
        $xmlContent .= '        <courseid>' . $courseInfo['real_id'] . '</courseid>' . PHP_EOL;
        $xmlContent .= '        <title>' . htmlspecialchars($courseInfo['title']) . '</title>' . PHP_EOL;
        $xmlContent .= '        <directory>course</directory>' . PHP_EOL;
        $xmlContent .= '      </course>' . PHP_EOL;

        $xmlContent .= '    </contents>' . PHP_EOL;

        // Backup settings
        $xmlContent .= '    <settings>' . PHP_EOL;
        $settings = $this->exportBackupSettings(); // Export backup settings dynamically
        foreach ($settings as $setting) {
            $xmlContent .= '      <setting>' . PHP_EOL;
            $xmlContent .= '        <level>' . htmlspecialchars($setting['level']) . '</level>' . PHP_EOL;
            $xmlContent .= '        <name>' . htmlspecialchars($setting['name']) . '</name>' . PHP_EOL;
            $xmlContent .= '        <value>' . $setting['value'] . '</value>' . PHP_EOL;
            $xmlContent .= '      </setting>' . PHP_EOL;
        }
        $xmlContent .= '    </settings>' . PHP_EOL;

        $xmlContent .= '  </information>' . PHP_EOL;
        $xmlContent .= '</moodle_backup>';

        $xmlFile = $destinationDir . '/moodle_backup.xml';
        file_put_contents($xmlFile, $xmlContent) !== false;
    }

    /**
     * Get all sections from the course.
     */
    private function getSections(): array
    {
        $sectionExport = new SectionExport($this->course);
        $sections = [];

        foreach ($this->course->resources[RESOURCE_LEARNPATH] as $learnpath) {
            if ($learnpath->lp_type == '1') {
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
     */
    private function getActivities(): array
    {
        $activities = [];

        foreach ($this->course->resources as $resourceType => $resources) {
            foreach ($resources as $resource) {
                // Handle quizzes
                if ($resourceType === RESOURCE_QUIZ && $resource->obj->iid > 0) {
                    $quizExport = new QuizExport($this->course);
                    $activities[] = [
                        'id' => $resource->obj->iid,
                        'sectionid' => $quizExport->getSectionIdForActivity($resource->obj->iid, RESOURCE_QUIZ),
                        'modulename' => 'quiz',
                        'moduleid' => $resource->obj->iid,
                        'title' => $resource->obj->title,
                    ];
                }

                if ($resourceType === RESOURCE_DOCUMENT && $resource->source_id > 0) {
                    $document = \DocumentManager::get_document_data_by_id($resource->source_id, $this->course->code);
                    // Handle documents (HTML pages)
                    if ('html' === pathinfo($document['path'], PATHINFO_EXTENSION)) {
                        $pageExport = new PageExport($this->course);
                        $activities[] = [
                            'id' => $resource->source_id,
                            'sectionid' => $pageExport->getSectionIdForActivity($resource->source_id, RESOURCE_DOCUMENT),
                            'modulename' => 'page',
                            'moduleid' => $resource->source_id,
                            'title' => $document['title'],
                        ];
                    } else {
                        // Handle files (resources with file_type 'file')
                        if ($resourceType === RESOURCE_DOCUMENT && $resource->file_type === 'file') {
                            $resourceExport = new ResourceExport($this->course);
                            $activities[] = [
                                'id' => $resource->source_id,
                                'sectionid' => $resourceExport->getSectionIdForActivity($resource->source_id, RESOURCE_DOCUMENT),
                                'modulename' => 'resource',
                                'moduleid' => $resource->source_id,
                                'title' => $resource->title,
                            ];
                        }

                        // Handle folders
                        if ($resourceType === RESOURCE_DOCUMENT && $resource->file_type === 'folder') {
                            $folderExport = new FolderExport($this->course);
                            $activities[] = [
                                'id' => $resource->source_id,
                                'sectionid' => $folderExport->getSectionIdForActivity($resource->source_id, RESOURCE_DOCUMENT),
                                'modulename' => 'folder',
                                'moduleid' => $resource->source_id,
                                'title' => $resource->title,
                            ];
                        }
                    }
                }
            }
        }

        return $activities;
    }

    /**
     * Export the sections of the course.
     */
    private function exportSections(string $exportDir): void
    {
        $sections = $this->getSections();

        foreach ($sections as $section) {
            $sectionExport = new SectionExport($this->course);
            $sectionExport->exportSection($section['id'], $exportDir);
        }
    }

    /**
     * Create a .mbz (ZIP) file from the exported data.
     */
    private function createMbzFile(string $sourceDir): string
    {
        $zip = new ZipArchive();
        $zipFile = $sourceDir . '.mbz';

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
                    throw new Exception(get_lang('ErrorAddingFileToZip') . ": $relativePath");
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
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<badges>' . PHP_EOL;
        $xmlContent .= '</badges>';
        file_put_contents($exportDir . '/badges.xml', $xmlContent);
    }

    /**
     * Export course completion data to XML file.
     */
    private function exportCompletionXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<completions>' . PHP_EOL;
        $xmlContent .= '</completions>';
        file_put_contents($exportDir . '/completion.xml', $xmlContent);
    }

    /**
     * Export gradebook data to XML file.
     */
    private function exportGradebookXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<gradebook>' . PHP_EOL;
        $xmlContent .= '</gradebook>';
        file_put_contents($exportDir . '/gradebook.xml', $xmlContent);
    }

    /**
     * Export grade history data to XML file.
     */
    private function exportGradeHistoryXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<grade_history>' . PHP_EOL;
        $xmlContent .= '</grade_history>';
        file_put_contents($exportDir . '/grade_history.xml', $xmlContent);
    }

    /**
     * Export groups data to XML file.
     */
    private function exportGroupsXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<groups>' . PHP_EOL;
        $xmlContent .= '</groups>';
        file_put_contents($exportDir . '/groups.xml', $xmlContent);
    }

    /**
     * Export outcomes data to XML file.
     */
    private function exportOutcomesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<outcomes>' . PHP_EOL;
        $xmlContent .= '</outcomes>';
        file_put_contents($exportDir . '/outcomes.xml', $xmlContent);
    }

    /**
     * Export questions data to XML file.
     */
    public function exportQuestionsXml(array $questionsData, string $exportDir): void
    {
        $quizExport = new QuizExport($this->course);
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<question_categories>' . PHP_EOL;

        foreach ($questionsData as $quiz) {
            $categoryId = $quiz['questions'][0]['questioncategoryid'] ?? '0';

            $xmlContent .= '  <question_category id="' . $categoryId . '">' . PHP_EOL;
            $xmlContent .= '    <name>Default for ' . htmlspecialchars($quiz['name'] ?? 'Unknown') . '</name>' . PHP_EOL;
            $xmlContent .= '    <contextid>' . ($quiz['contextid'] ?? '0') . '</contextid>' . PHP_EOL;
            $xmlContent .= '    <contextlevel>70</contextlevel>' . PHP_EOL;
            $xmlContent .= '    <contextinstanceid>' . ($quiz['moduleid'] ?? '0') . '</contextinstanceid>' . PHP_EOL;
            $xmlContent .= '    <info>The default category for questions shared in context "' . htmlspecialchars($quiz['name'] ?? 'Unknown') . '".</info>' . PHP_EOL;
            $xmlContent .= '    <infoformat>0</infoformat>' . PHP_EOL;
            $xmlContent .= '    <stamp>my.moodle3.com+' . time() . '+CATEGORYSTAMP</stamp>' . PHP_EOL;
            $xmlContent .= '    <parent>0</parent>' . PHP_EOL;
            $xmlContent .= '    <sortorder>999</sortorder>' . PHP_EOL;
            $xmlContent .= '    <idnumber>$@NULL@$</idnumber>' . PHP_EOL;
            $xmlContent .= '    <questions>' . PHP_EOL;

            foreach ($quiz['questions'] as $question) {
                $xmlContent .= $quizExport->exportQuestion($question);
            }

            $xmlContent .= '    </questions>' . PHP_EOL;
            $xmlContent .= '  </question_category>' . PHP_EOL;
        }

        $xmlContent .= '</question_categories>';
        file_put_contents($exportDir . '/questions.xml', $xmlContent);
    }

    /**
     * Export roles data to XML file.
     */
    private function exportRolesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<roles>' . PHP_EOL;
        $xmlContent .= '</roles>';
        file_put_contents($exportDir . '/roles.xml', $xmlContent);
    }

    /**
     * Export scales data to XML file.
     */
    private function exportScalesXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<scales>' . PHP_EOL;
        $xmlContent .= '</scales>';
        file_put_contents($exportDir . '/scales.xml', $xmlContent);
    }

    /**
     * Export users data to XML file.
     */
    private function exportUsersXml(string $exportDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<users>' . PHP_EOL;
        $xmlContent .= '</users>';
        file_put_contents($exportDir . '/users.xml', $xmlContent);
    }

    /**
     * Export the backup settings.
     */
    private function exportBackupSettings(): array
    {
        return [
            ['level' => 'root', 'name' => 'users', 'value' => '1'],
            ['level' => 'root', 'name' => 'anonymize', 'value' => '0'],
            ['level' => 'root', 'name' => 'activities', 'value' => '1'],
            // Add more settings as needed
        ];
    }
}
