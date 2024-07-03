<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Asset;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Some functions to write a course-object to a zip-file and to read a course-
 * object from such a zip-file.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 *
 * @todo Use archive-folder of Chamilo?
 */
class CourseArchiver
{
    /**
     * @return string
     */
    public static function getBackupDir()
    {
        return api_get_path(SYS_ARCHIVE_PATH).'course_backups/';
    }

    /**
     * @return string
     */
    public static function createBackupDir()
    {
        $perms = api_get_permissions_for_new_directories();
        $dir = self::getBackupDir();
        $fs = new Filesystem();
        $fs->mkdir($dir, $perms);

        return $dir;
    }

    /**
     * Delete old temp-dirs.
     */
    public static function cleanBackupDir()
    {
        $dir = self::getBackupDir();
        if (is_dir($dir)) {
            if ($handle = @opendir($dir)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file != "." && $file != ".." &&
                        strpos($file, 'CourseArchiver_') === 0 &&
                        is_dir($dir.'/'.$file)
                    ) {
                        rmdirr($dir.'/'.$file);
                    }
                }
                closedir($handle);
            }
        }
    }

    /**
     * Write a course and all its resources to a zip-file.
     *
     * @return string A pointer to the zip-file
     */
    public static function createBackup($course)
    {
        self::cleanBackupDir();
        self::createBackupDir();

        $perm_dirs = api_get_permissions_for_new_directories();
        $backupDirectory = self::getBackupDir();

        // Create a temp directory
        $backup_dir = $backupDirectory.'CourseArchiver_'.api_get_unique_id().'/';

        // All course-information will be stored in course_info.dat
        $course_info_file = $backup_dir.'course_info.dat';

        $user = api_get_user_info();
        $date = new \DateTime(api_get_local_time());
        $zipFileName = $user['user_id'].'_'.$course->code.'_'.$date->format('Ymd-His').'.zip';
        $zipFilePath = $backupDirectory.$zipFileName;

        $php_errormsg = '';
        $res = @mkdir($backup_dir, $perm_dirs);
        if ($res === false) {
            //TODO set and handle an error message telling the user to review the permissions on the archive directory
            error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors') != false ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini').' - This error, occuring because your archive directory will not let this script write data into it, will prevent courses backups to be created', 0);
        }
        // Write the course-object to the file
        $fp = @fopen($course_info_file, 'w');
        if ($fp === false) {
            error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors') != false ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }

        $res = @fwrite($fp, base64_encode(serialize($course)));
        if ($res === false) {
            error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors') != false ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }

        $res = @fclose($fp);
        if ($res === false) {
            error_log(__FILE__.' line '.__LINE__.': '.(ini_get('track_errors') != false ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }

        // Copy all documents to the temp-dir
        if (isset($course->resources[RESOURCE_DOCUMENT]) && is_array($course->resources[RESOURCE_DOCUMENT])) {
            $webEditorCss = api_get_path(WEB_CSS_PATH).'editor.css';
            /** @var Document $document */
            foreach ($course->resources[RESOURCE_DOCUMENT] as $document) {
                if ($document->file_type == DOCUMENT) {
                    $doc_dir = $backup_dir.$document->path;
                    @mkdir(dirname($doc_dir), $perm_dirs, true);
                    if (file_exists($course->path.$document->path)) {
                        copy($course->path.$document->path, $doc_dir);
                        // Check if is html or htm
                        $extension = pathinfo(basename($document->path), PATHINFO_EXTENSION);
                        switch ($extension) {
                            case 'html':
                            case 'htm':
                                $contents = file_get_contents($doc_dir);
                                $contents = str_replace(
                                    $webEditorCss,
                                    '{{css_editor}}',
                                    $contents
                                );
                                file_put_contents($doc_dir, $contents);
                                break;
                        }
                    }
                } else {
                    @mkdir($backup_dir.$document->path, $perm_dirs, true);
                }
            }
        }

        // Copy all xapi resources to the temp-dir
        if (isset($course->resources[RESOURCE_XAPI_TOOL]) && is_array($course->resources[RESOURCE_XAPI_TOOL])) {
            foreach ($course->resources[RESOURCE_XAPI_TOOL] as $xapi) {
                $launchPath = str_replace(
                    api_get_path(WEB_COURSE_PATH).$course->info['path'].'/',
                    '',
                    dirname($xapi->params['launch_url'])
                );
                $xapiDir = dirname($backup_dir.'/'.$launchPath.'/');
                @mkdir($xapiDir, $perm_dirs, true);
                copyDirTo($course->path.$launchPath.'/', $backup_dir.$launchPath, false);
            }
        }

        // Copy all scorm documents to the temp-dir
        if (isset($course->resources[RESOURCE_SCORM]) && is_array($course->resources[RESOURCE_SCORM])) {
            foreach ($course->resources[RESOURCE_SCORM] as $document) {
                copyDirTo($course->path.$document->path, $backup_dir.$document->path, false);
            }
        }

        // Copy calendar attachments.
        if (isset($course->resources[RESOURCE_EVENT]) && is_array($course->resources[RESOURCE_EVENT])) {
            $doc_dir = dirname($backup_dir.'/upload/calendar/');
            @mkdir($doc_dir, $perm_dirs, true);
            copyDirTo($course->path.'upload/calendar/', $doc_dir, false);
        }

        // Copy Learning path author image.
        if (isset($course->resources[RESOURCE_LEARNPATH]) && is_array($course->resources[RESOURCE_LEARNPATH])) {
            $doc_dir = dirname($backup_dir.'/upload/learning_path/');
            @mkdir($doc_dir, $perm_dirs, true);
            copyDirTo($course->path.'upload/learning_path/', $doc_dir, false);
        }

        // Copy announcements attachments.
        if (isset($course->resources[RESOURCE_ANNOUNCEMENT]) && is_array($course->resources[RESOURCE_ANNOUNCEMENT])) {
            $doc_dir = dirname($backup_dir.'/upload/announcements/');
            @mkdir($doc_dir, $perm_dirs, true);
            copyDirTo($course->path.'upload/announcements/', $doc_dir, false);
        }

        // Copy work folders (only folders)
        if (isset($course->resources[RESOURCE_WORK]) && is_array($course->resources[RESOURCE_WORK])) {
            $doc_dir = $backup_dir.'work';
            @mkdir($doc_dir, $perm_dirs, true);
            copyDirWithoutFilesTo($course->path.'work/', $doc_dir);
        }
        if (isset($course->resources[RESOURCE_ASSET]) && is_array($course->resources[RESOURCE_ASSET])) {
            /** @var Asset $asset */
            foreach ($course->resources[RESOURCE_ASSET] as $asset) {
                $doc_dir = $backup_dir.$asset->path;
                @mkdir(dirname($doc_dir), $perm_dirs, true);
                $assetPath = $course->path.$asset->path;

                if (!file_exists($assetPath)) {
                    continue;
                }

                if (is_dir($course->path.$asset->path)) {
                    copyDirTo($course->path.$asset->path, $doc_dir, false);
                    continue;
                }
                copy($course->path.$asset->path, $doc_dir);
            }
        }

        // Zip the course-contents
        $zip = new \PclZip($zipFilePath);
        $zip->create($backup_dir, PCLZIP_OPT_REMOVE_PATH, $backup_dir);

        // Remove the temp-dir.
        rmdirr($backup_dir);

        return $zipFileName;
    }

    /**
     * @param int $user_id
     *
     * @return array
     */
    public static function getAvailableBackups($user_id = null)
    {
        $backup_files = [];
        $dirname = self::getBackupDir();

        if (!file_exists($dirname)) {
            $dirname = self::createBackupDir();
        }

        if ($dir = opendir($dirname)) {
            while (($file = readdir($dir)) !== false) {
                $file_parts = explode('_', $file);
                if (count($file_parts) == 3) {
                    $owner_id = $file_parts[0];
                    $course_code = $file_parts[1];
                    $file_parts = explode('.', $file_parts[2]);
                    $date = $file_parts[0];
                    $ext = isset($file_parts[1]) ? $file_parts[1] : null;
                    if ($ext == 'zip' && ($user_id != null && $owner_id == $user_id || $user_id == null)) {
                        $date =
                            substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.
                            substr($date, 6, 2).' '.substr($date, 9, 2).':'.
                            substr($date, 11, 2).':'.substr($date, 13, 2);
                        $backup_files[] = [
                            'file' => $file,
                            'date' => $date,
                            'course_code' => $course_code,
                        ];
                    }
                }
            }
            closedir($dir);
        }

        return $backup_files;
    }

    /**
     * @param array $file
     *
     * @return bool|string
     */
    public static function importUploadedFile($file)
    {
        $new_filename = uniqid('import_file', true).'.zip';
        $new_dir = self::getBackupDir();
        if (!is_dir($new_dir)) {
            $fs = new Filesystem();
            $fs->mkdir($new_dir);
        }
        if (is_dir($new_dir) && is_writable($new_dir)) {
            move_uploaded_file($file, $new_dir.$new_filename);

            return $new_filename;
        }

        return false;
    }

    /**
     * Read a course-object from a zip-file.
     *
     * @param string $filename
     * @param bool   $delete   Delete the file after reading the course?
     *
     * @return course The course
     *
     * @todo Check if the archive is a correct Chamilo-export
     */
    public static function readCourse($filename, $delete = false)
    {
        self::cleanBackupDir();
        // Create a temp directory
        $tmp_dir_name = 'CourseArchiver_'.uniqid('');
        $unzip_dir = self::getBackupDir().$tmp_dir_name;
        $filePath = self::getBackupDir().$filename;

        @mkdir($unzip_dir, api_get_permissions_for_new_directories(), true);
        @copy(
            $filePath,
            $unzip_dir.'/backup.zip'
        );

        // unzip the archive
        $zip = new \PclZip($unzip_dir.'/backup.zip');
        @chdir($unzip_dir);

        $zip->extract(
            PCLZIP_OPT_TEMP_FILE_ON,
            PCLZIP_CB_PRE_EXTRACT,
            'clean_up_files_in_zip'
        );

        // remove the archive-file
        if ($delete) {
            @unlink($filePath);
        }

        // read the course
        if (!is_file('course_info.dat')) {
            return new Course();
        }

        $fp = @fopen('course_info.dat', "r");
        $contents = @fread($fp, filesize('course_info.dat'));
        @fclose($fp);

        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Course', 'Course');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Announcement', 'Announcement');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Attendance', 'Attendance');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\CalendarEvent', 'CalendarEvent');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyLearnpath', 'CourseCopyLearnpath');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyTestCategory', 'CourseCopyTestCategory');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseDescription', 'CourseDescription');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseSession', 'CourseSession');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Document', 'Document');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Forum', 'Forum');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumCategory', 'ForumCategory');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumPost', 'ForumPost');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumTopic', 'ForumTopic');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Glossary', 'Glossary');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\GradeBookBackup', 'GradeBookBackup');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Link', 'Link');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\LinkCategory', 'LinkCategory');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Quiz', 'Quiz');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestion', 'QuizQuestion');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestionOption', 'QuizQuestionOption');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\ScormDocument', 'ScormDocument');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Survey', 'Survey');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyInvitation', 'SurveyInvitation');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyQuestion', 'SurveyQuestion');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Thematic', 'Thematic');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\ToolIntro', 'ToolIntro');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Wiki', 'Wiki');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\Work', 'Work');
        class_alias('Chamilo\CourseBundle\Component\CourseCopy\Resources\XapiTool', 'XapiTool');

        /** @var Course $course */
        $course = \UnserializeApi::unserialize('course', base64_decode($contents));

        if (!in_array(
            get_class($course),
            ['Course', 'Chamilo\CourseBundle\Component\CourseCopy\Course']
        )
        ) {
            return new Course();
        }

        $course->backup_path = $unzip_dir;

        return $course;
    }
}
