<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Traits\FileFinderTrait;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class FilesForScormScoLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class FilesForScormScoLoader extends CourseFilesLoader
{
    use FileFinderTrait;

    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $course = api_get_course_entity($incomingData['c_id']);

        $moodleFilePath = $this->findFilePath($incomingData['contenthash']);

        $sysCourseScormPath = api_get_path(SYS_COURSE_PATH).$course->getDirectory().'/scorm';
        $lpDirectory = CourseModulesScormLoader::generateDirectoryName($incomingData['lp_name']);
        $lpDirectoryPath = "$sysCourseScormPath/$lpDirectory";
        $fileDirectoryPath = $lpDirectoryPath.$incomingData['filepath'];
        $filePath = $fileDirectoryPath.$incomingData['filename'];

        $fileSystem = new Filesystem();

        if ($incomingData['filepath'] != '/') {
            $fileSystem->mkdir(
                $fileDirectoryPath,
                api_get_permissions_for_new_directories()
            );
        }

        $fileSystem->copy($moodleFilePath, $filePath);

        return 0;
    }
}
