<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export;

use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Exception;
use Chamilo\CoreBundle\Helpers\DateTimeHelper;

class Cc13ExportConvert
{
    /**
     * Export the CommonCartridge object to the app/cache/course_backups/CourseCC13Archiver_[hash] directory.
     *
     * @param \Chamilo\CourseBundle\Component\CourseCopy\Course $objCourse
     *
     * @return false|string
     */
    public static function export($objCourse)
    {
        $permDirs = api_get_permissions_for_new_directories();
        $backupDirectory = CourseArchiver::getBackupDir();

        // Create a temp directory
        $backupDir = $backupDirectory.'CourseCC13Archiver_'.api_get_unique_id();

        if (mkdir($backupDir, $permDirs, true)) {
            $converted = Cc13Convert::convert($backupDirectory, $backupDir, $objCourse);
            if ($converted) {
                $imsccFileName = self::createImscc($backupDir, $objCourse);

                return $imsccFileName;
            }
        }

        return false;
    }

    /**
     * @param $backupDir
     * @param $objCourse
     *
     * @throws Exception
     *
     * @return string Filename of the created .imscc (zip) file
     */
    public static function createImscc($backupDir, $objCourse)
    {
        $backupDirectory = CourseArchiver::getBackupDir();

        $date = DateTimeHelper::nowLocalDateTime();

        $imsccFileName = $objCourse->info['code'].'_'.$date->format('Ymd-His').'.imscc';
        $imsccFilePath = $backupDirectory.$imsccFileName;

        // Zip the course-contents
        $zip = new \PclZip($imsccFilePath);
        $zip->create($backupDir, PCLZIP_OPT_REMOVE_PATH, $backupDir);

        // Remove the temp-dir.
        rmdirr($backupDir);

        return $imsccFileName;
    }
}
