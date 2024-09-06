<?php
/* For licensing terms, see /license.txt */

namespace moodleexport;

/**
 * Class FileExport.
 *
 * @package moodleexport
 */
class FileExport
{
    private $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Export the files and their metadata from files.xml.
     *
     * @param array $filesData The data from files.xml.
     * @param string $exportDir The directory where the files will be stored.
     */
    public function exportFiles($filesData, $exportDir)
    {
        // Directory for storing files
        $filesDir = $exportDir . '/files';

        // Check if the directory exists, if not, create it
        if (!is_dir($filesDir)) {
            mkdir($filesDir, api_get_permissions_for_new_directories(), true);
        }

        // Create a placeholder index.html file to prevent an empty directory
        $placeholderFile = $filesDir . '/index.html';
        file_put_contents($placeholderFile, "<!-- Placeholder file to ensure the directory is not empty -->");

        // Create files.xml in the root export directory
        $this->createFilesXml($filesData, $exportDir);
    }


    /**
     * Create the files.xml based on the provided data.
     *
     * @param array $filesData The data from files.xml.
     * @param string $destinationDir The directory where the files.xml will be stored (root export directory).
     */
    private function createFilesXml($filesData, $destinationDir)
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlContent .= '<files>' . PHP_EOL;

        foreach ($filesData['files'] as $file) {
            $xmlContent .= '  <file id="' . $file['id'] . '">' . PHP_EOL;
            $xmlContent .= '    <contenthash>' . htmlspecialchars($file['contenthash']) . '</contenthash>' . PHP_EOL;
            $xmlContent .= '    <contextid>' . $file['contextid'] . '</contextid>' . PHP_EOL;
            $xmlContent .= '    <component>' . htmlspecialchars($file['component']) . '</component>' . PHP_EOL;
            $xmlContent .= '    <filearea>' . htmlspecialchars($file['filearea']) . '</filearea>' . PHP_EOL;
            $xmlContent .= '    <itemid>' . $file['itemid'] . '</itemid>' . PHP_EOL;
            $xmlContent .= '    <filepath>' . htmlspecialchars($file['filepath']) . '</filepath>' . PHP_EOL;
            $xmlContent .= '    <filename>' . htmlspecialchars($file['filename']) . '</filename>' . PHP_EOL;
            $xmlContent .= '    <userid>' . $file['userid'] . '</userid>' . PHP_EOL;
            $xmlContent .= '    <filesize>' . $file['filesize'] . '</filesize>' . PHP_EOL;
            $xmlContent .= '    <mimetype>' . htmlspecialchars($file['mimetype']) . '</mimetype>' . PHP_EOL;
            $xmlContent .= '    <status>' . $file['status'] . '</status>' . PHP_EOL;
            $xmlContent .= '    <timecreated>' . $file['timecreated'] . '</timecreated>' . PHP_EOL;
            $xmlContent .= '    <timemodified>' . $file['timemodified'] . '</timemodified>' . PHP_EOL;
            $xmlContent .= '    <source>' . htmlspecialchars($file['source']) . '</source>' . PHP_EOL;
            $xmlContent .= '    <author>' . htmlspecialchars($file['author']) . '</author>' . PHP_EOL;
            $xmlContent .= '    <license>' . htmlspecialchars($file['license']) . '</license>' . PHP_EOL;
            $xmlContent .= '  </file>' . PHP_EOL;
        }

        $xmlContent .= '</files>' . PHP_EOL;

        $xmlFile = $destinationDir . '/files.xml';
        file_put_contents($xmlFile, $xmlContent);
    }

    /**
     * Get the file data for testing purposes. Later will be dynamic.
     *
     * @return array The file data related to activities.
     */
    public function getFilesData()
    {
        return [
            'files' => [
                [
                    'id' => 1,
                    'contenthash' => 'abcd1234efgh5678ijkl',
                    'contextid' => 26,
                    'component' => 'mod_assign',
                    'filearea' => 'submission_files',
                    'itemid' => 3,
                    'filepath' => '/',
                    'filename' => 'assignment_submission_001.pdf',
                    'userid' => 5,
                    'filesize' => 204800,
                    'mimetype' => 'application/pdf',
                    'status' => 0,
                    'timecreated' => time() - 3600,
                    'timemodified' => time(),
                    'source' => 'Original Submission',
                    'author' => 'Student Name',
                    'license' => 'allrightsreserved',
                ],
                [
                    'id' => 2,
                    'contenthash' => 'ijkl1234mnop5678qrst',
                    'contextid' => 26,
                    'component' => 'mod_quiz',
                    'filearea' => 'feedback_files',
                    'itemid' => 7,
                    'filepath' => '/',
                    'filename' => 'quiz_feedback_001.pdf',
                    'userid' => 6,
                    'filesize' => 102400,
                    'mimetype' => 'application/pdf',
                    'status' => 0,
                    'timecreated' => time() - 7200,
                    'timemodified' => time(),
                    'source' => 'Quiz Feedback',
                    'author' => 'Teacher Name',
                    'license' => 'allrightsreserved',
                ],
            ]
        ];
    }
}
