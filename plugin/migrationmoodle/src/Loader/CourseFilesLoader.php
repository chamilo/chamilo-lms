<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;
use Chamilo\PluginBundle\MigrationMoodle\Traits\FileFinderTrait;

/**
 * Class CourseFilesLoader.
 *
 * Loader to create the files needed for Chamilo course documents coming from the files in Moodle course.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseFilesLoader implements LoaderInterface
{
    use FileFinderTrait;

    /**
     * Load the data and return the ID inserted.
     *
     * @throws \Exception
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $userId = 1;
        $courseInfo = api_get_course_info_by_id($incomingData['course']);

        $filePath = $this->findFilePath($incomingData['contenthash']);

        $file = [
            'file' => [
                'name' => $incomingData['filename'],
                'tmp_name' => $filePath,
                'type' => $incomingData['mimetype'],
                'size' => $incomingData['filesize'],
                'error' => 0,
                'from_file' => true,
                'move_file' => true,
            ],
        ];

        $_POST['language'] = $courseInfo['language'];

        $fileData = \DocumentManager::upload_document(
            $file,
            '/',
            $incomingData['filename'],
            '',
            null,
            null,
            true,
            false,
            'file',
            false,
            $userId,
            $courseInfo
        );

        return $fileData['iid'];
    }
}
