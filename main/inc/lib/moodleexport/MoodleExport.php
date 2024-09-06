<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Exception;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class MoodleExport.
 *
 * @package moodleexport
 */
class MoodleExport
{

    private $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Export a course in Moodle format (.mbz).
     *
     * @param int $courseId    The ID of the course to be exported.
     * @param string $exportDir   The directory where the export will be created.
     * @param string $version     The version of Moodle (3 or 4).
     *
     * @return bool|string        Returns the path of the exported file or false in case of error.
     * @throws Exception          If an error occurs during export.
     */
    public function export($courseId, $exportDir, $version)
    {
        // Temporary directory where the export will be saved
        $tempDir = api_get_path(SYS_ARCHIVE_PATH) . $exportDir;

        // Create the export directory if it doesn't exist
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, api_get_permissions_for_new_directories(), true)) {
                throw new Exception(get_lang('ErrorCreatingDirectory'));
            }
        }

        // Get course information
        $courseInfo = api_get_course_info($courseId);
        if (!$courseInfo) {
            throw new Exception(get_lang('CourseNotFound'));
        }

        $this->createMoodleBackupXml($tempDir, $version);

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

        // Clean up temporary directory if necessary
        $this->cleanupTempDir($tempDir);

        return $exportedFile;
    }


    /**
     * Export all root XML files for the course.
     *
     * This method generates XML files for various aspects of the course including badges, completion, gradebook,
     * grade history, groups, outcomes, and questions. It dynamically retrieves activities and quizzes, and exports
     * questions associated with each quiz.
     *
     * @param string $exportDir The directory where XML files will be saved.
     *
     * @return void
     */
    private function exportRootXmlFiles($exportDir)
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
     * Creates the moodle_backup.xml file with activities, sections, and settings.
     *
     * @param string $destinationDir  The directory where the XML will be saved.
     * @param string $version         The Moodle version (3 or 4).
     *
     * @return bool                   Returns true if the file was created successfully.
     */
    private function createMoodleBackupXml($destinationDir, $version)
    {
        $courseInfo = api_get_course_info($this->course->code);

        $backupId = md5(uniqid(mt_rand(), true));
        $siteHash = md5(uniqid(mt_rand(), true));

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
        return file_put_contents($xmlFile, $xmlContent) !== false;
    }

    /**
     * Retrieve all sections from the course.
     *
     * This method fetches and returns sections from the course resources where the learnpath type is '1'.
     * It uses the SectionExport class to get section data based on each learnpath.
     *
     * @return array An array of section data.
     */
    private function getSections()
    {
        $sectionExport = new SectionExport($this->course);
        $sections = [];
        foreach ($this->course->resources[RESOURCE_LEARNPATH] as $learnpath) {
            if ($learnpath->lp_type == '1') {
                $sections[] = $sectionExport->getSectionData($learnpath);
            }
        }
        return $sections;
    }

    /**
     * Retrieve all activities from the course.
     *
     * @return array An array of activities.
     */
    private function getActivities()
    {
        $activities = [];

        foreach ($this->course->resources as $resourceType => $resources) {
            foreach ($resources as $resource) {
                if ($resource->obj->iid > 0) {
                    if ($resourceType === RESOURCE_QUIZ) {
                        $quizExport = new QuizExport($this->course);
                        $activities[] = [
                            'id' => $resource->obj->iid,
                            'sectionid' => $quizExport->getSectionIdForQuiz($resource->obj->iid),
                            'modulename' => 'quiz',
                            'moduleid' => $resource->obj->iid,
                            'title' => $resource->obj->title,
                        ];
                    }

                    // Add more cases for other types of resources as needed
                }
            }
        }

        return $activities;
    }

    /**
     * Export badges data to XML file.
     *
     * @param string $exportDir Directory to save the XML file.
     */
    private function exportBadgesXml($exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<badges>' . PHP_EOL;
        // Populate badges
        $xmlContent .= '</badges>';
        $xmlFile = $exportDir . '/badges.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Export completion data to XML file.
     *
     * @param string $exportDir Directory to save the XML file.
     */
    private function exportCompletionXml($exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<completions>' . PHP_EOL;
        // Populate completion data
        $xmlContent .= '</completions>';
        $xmlFile = $exportDir . '/completion.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Export gradebook data to XML file.
     *
     * @param string $exportDir Directory to save the XML file.
     */
    private function exportGradebookXml($exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<gradebook>' . PHP_EOL;
        // Populate gradebook data
        $xmlContent .= '</gradebook>';
        $xmlFile = $exportDir . '/gradebook.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Export grade history data to XML file.
     *
     * @param string $exportDir Directory to save the XML file.
     */
    private function exportGradeHistoryXml($exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<grade_history>' . PHP_EOL;
        // Populate grade history data
        $xmlContent .= '</grade_history>';
        $xmlFile = $exportDir . '/grade_history.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Export groups data to XML file.
     *
     * @param string $exportDir Directory to save the XML file.
     */
    private function exportGroupsXml($exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<groups>' . PHP_EOL;
        // Populate groups data
        $xmlContent .= '</groups>';
        $xmlFile = $exportDir . '/groups.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Export outcomes data to XML file.
     *
     * @param string $exportDir Directory to save the XML file.
     */
    private function exportOutcomesXml($exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<outcomes>' . PHP_EOL;
        // Populate outcomes data
        $xmlContent .= '</outcomes>';
        $xmlFile = $exportDir . '/outcomes.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Export the questions to questions.xml.
     *
     * @param array $questionsData The data of the questions (from getQuizData).
     * @param string $exportDir The directory where the XML will be saved.
     */
    private function exportQuestionsXml($questionsData, $exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<question_categories>' . PHP_EOL;

        // Iterate through each quiz to extract questions
        foreach ($questionsData as $quiz) {
            // Assuming each entry in $questionsData represents a quiz
            $categoryId = $quiz['questions'][0]['questioncategoryid'] ?? 'default_category_id'; // Use a default value if category is not set

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

            // Extract each question from the quiz
            foreach ($quiz['questions'] as $question) {
                $xmlContent .= '      <question id="' . ($question['id'] ?? '0') . '">' . PHP_EOL;
                $xmlContent .= '        <parent>0</parent>' . PHP_EOL;
                $xmlContent .= '        <name>' . htmlspecialchars($question['questiontext'] ?? 'No question text') . '</name>' . PHP_EOL;
                $xmlContent .= '        <questiontext>' . htmlspecialchars($question['questiontext'] ?? 'No question text') . '</questiontext>' . PHP_EOL;
                $xmlContent .= '        <questiontextformat>1</questiontextformat>' . PHP_EOL;
                $xmlContent .= '        <generalfeedback></generalfeedback>' . PHP_EOL;
                $xmlContent .= '        <generalfeedbackformat>1</generalfeedbackformat>' . PHP_EOL;
                $xmlContent .= '        <defaultmark>' . ($question['maxmark'] ?? '0') . '</defaultmark>' . PHP_EOL;
                $xmlContent .= '        <penalty>0.3333333</penalty>' . PHP_EOL;
                $xmlContent .= '        <qtype>' . htmlspecialchars($question['qtype'] ?? 'unknown') . '</qtype>' . PHP_EOL;
                $xmlContent .= '        <length>1</length>' . PHP_EOL;
                $xmlContent .= '        <stamp>my.moodle3.com+' . time() . '+QUESTIONSTAMP</stamp>' . PHP_EOL;
                $xmlContent .= '        <version>my.moodle3.com+' . time() . '+VERSIONSTAMP</version>' . PHP_EOL;
                $xmlContent .= '        <hidden>0</hidden>' . PHP_EOL;
                $xmlContent .= '        <timecreated>' . time() . '</timecreated>' . PHP_EOL;
                $xmlContent .= '        <timemodified>' . time() . '</timemodified>' . PHP_EOL;
                $xmlContent .= '        <createdby>2</createdby>' . PHP_EOL;
                $xmlContent .= '        <modifiedby>2</modifiedby>' . PHP_EOL;

                // Check if the question type is multichoice and add answers
                if ($question['qtype'] === 'multichoice') {
                    $xmlContent .= '        <plugin_qtype_multichoice_question>' . PHP_EOL;
                    $xmlContent .= '          <answers>' . PHP_EOL;
                    foreach ($question['answers'] as $answer) {
                        $xmlContent .= '            <answer id="' . ($answer['id'] ?? '0') . '">' . PHP_EOL;
                        $xmlContent .= '              <answertext>' . htmlspecialchars($answer['text'] ?? 'No answer text') . '</answertext>' . PHP_EOL;
                        $xmlContent .= '              <answerformat>1</answerformat>' . PHP_EOL;
                        $xmlContent .= '              <fraction>' . ($answer['fraction'] ?? '0') . '</fraction>' . PHP_EOL;
                        $xmlContent .= '              <feedback>' . htmlspecialchars($answer['feedback'] ?? '') . '</feedback>' . PHP_EOL;
                        $xmlContent .= '              <feedbackformat>1</feedbackformat>' . PHP_EOL;
                        $xmlContent .= '            </answer>' . PHP_EOL;
                    }
                    $xmlContent .= '          </answers>' . PHP_EOL;
                    $xmlContent .= '          <multichoice id="' . ($question['id'] ?? '0') . '">' . PHP_EOL;
                    $xmlContent .= '            <layout>0</layout>' . PHP_EOL;
                    $xmlContent .= '            <single>1</single>' . PHP_EOL;
                    $xmlContent .= '            <shuffleanswers>1</shuffleanswers>' . PHP_EOL;
                    $xmlContent .= '            <correctfeedback>Your answer is correct.</correctfeedback>' . PHP_EOL;
                    $xmlContent .= '            <correctfeedbackformat>1</correctfeedbackformat>' . PHP_EOL;
                    $xmlContent .= '            <partiallycorrectfeedback>Your answer is partially correct.</partiallycorrectfeedback>' . PHP_EOL;
                    $xmlContent .= '            <partiallycorrectfeedbackformat>1</partiallycorrectfeedbackformat>' . PHP_EOL;
                    $xmlContent .= '            <incorrectfeedback>Your answer is incorrect.</incorrectfeedback>' . PHP_EOL;
                    $xmlContent .= '            <incorrectfeedbackformat>1</incorrectfeedbackformat>' . PHP_EOL;
                    $xmlContent .= '            <answernumbering>abc</answernumbering>' . PHP_EOL;
                    $xmlContent .= '            <shownumcorrect>1</shownumcorrect>' . PHP_EOL;
                    $xmlContent .= '            <showstandardinstruction>0</showstandardinstruction>' . PHP_EOL;
                    $xmlContent .= '          </multichoice>' . PHP_EOL;
                    $xmlContent .= '        </plugin_qtype_multichoice_question>' . PHP_EOL;
                }

                $xmlContent .= '      </question>' . PHP_EOL;
            }

            $xmlContent .= '    </questions>' . PHP_EOL;
            $xmlContent .= '  </question_category>' . PHP_EOL;
        }

        $xmlContent .= '</question_categories>';

        $xmlFile = $exportDir . '/questions.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Export roles data to XML file.
     *
     * @param string $exportDir Directory to save the XML file.
     */
    private function exportRolesXml($exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<roles>' . PHP_EOL;
        // Populate roles data
        $xmlContent .= '</roles>';
        $xmlFile = $exportDir . '/roles.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Export scales data to XML file.
     *
     * @param string $exportDir Directory to save the XML file.
     */
    private function exportScalesXml($exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<scales>' . PHP_EOL;
        // Populate scales data
        $xmlContent .= '</scales>';
        $xmlFile = $exportDir . '/scales.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Export users data to XML file.
     *
     * @param string $exportDir Directory to save the XML file.
     */
    private function exportUsersXml($exportDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<users>' . PHP_EOL;
        // Populate users data
        $xmlContent .= '</users>';
        $xmlFile = $exportDir . '/users.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Exports the course sections.
     *
     * @param int $courseId    The ID of the course.
     * @param string $exportDir The directory where the sections will be saved.
     *
     * @return void
     */
    private function exportSections($exportDir)
    {
        $sections = $this->getSections();
        foreach ($sections as $section) {
            $sectionExport = new SectionExport($this->course);
            $sectionExport->exportSection($section['id'], $exportDir);
        }
    }

    /**
     * Export backup settings dynamically for the course.
     *
     * @return array
     */
    private function exportBackupSettings()
    {
        // this should be pulled from a configuration
        return [
            ['level' => 'root', 'name' => 'users', 'value' => '1'],
            ['level' => 'root', 'name' => 'anonymize', 'value' => '0'],
            ['level' => 'root', 'name' => 'activities', 'value' => '1'],
            // Add more settings as needed
        ];
    }

    /**
     * Compresses the exported course into an .mbz (ZIP) file.
     *
     * @param string $sourceDir  Directory to compress.
     *
     * @return string            The path of the exported .mbz file.
     * @throws Exception         If an error occurs while creating the ZIP.
     */
    private function createMbzFile($sourceDir)
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

        foreach ($files as $name => $file) {
            // Skip directories
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($sourceDir) + 1);

                // Add the file to the ZIP
                if (!$zip->addFile($filePath, $relativePath)) {
                    throw new Exception(get_lang('ErrorAddingFileToZip') . ": $relativePath");
                }
            }
        }

        // Close the ZIP file
        if (!$zip->close()) {
            throw new Exception(get_lang('ErrorClosingZip'));
        }

        return $zipFile;
    }

    /**
     * Cleans up the temporary directory used for export.
     *
     * @param string $dir  Directory to delete.
     *
     * @return void
     */
    private function cleanupTempDir($dir)
    {
        $this->recursiveDelete($dir);
    }

    /**
     * Recursively deletes a directory and its contents.
     *
     * @param string $dir  Directory to delete.
     *
     * @return void
     */
    private function recursiveDelete($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            (is_dir($path)) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
}
