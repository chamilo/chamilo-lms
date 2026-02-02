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
     * @param array|string $file Either $_FILES['...'] array or a tmp path string
     *
     * @return bool|string Returns the stored zip filename (not full path) or false on failure
     */
    public static function importUploadedFile($file)
    {
        $newFilename = uniqid('import_file', true).'.zip';
        $newDir = self::getBackupDir();

        // Ensure backup directory exists
        if (!is_dir($newDir)) {
            @mkdir($newDir, api_get_permissions_for_new_directories(), true);
        }

        if (!is_dir($newDir) || !is_writable($newDir)) {
            return false;
        }

        // Normalize input
        $tmpPath = '';
        $uploadErr = 0;

        if (is_array($file)) {
            $uploadErr = (int) ($file['error'] ?? 0);
            $tmpPath = (string) ($file['tmp_name'] ?? '');
        } else {
            $tmpPath = (string) $file;
        }

        if ($uploadErr !== 0 || $tmpPath === '') {
            return false;
        }

        $destPath = $newDir.$newFilename;

        // Prefer move_uploaded_file for real HTTP uploads, fallback to copy for non-upload contexts
        $ok = false;
        if (function_exists('is_uploaded_file') && is_uploaded_file($tmpPath)) {
            $ok = @move_uploaded_file($tmpPath, $destPath);
        } elseif (is_file($tmpPath) && is_readable($tmpPath)) {
            $ok = @copy($tmpPath, $destPath);
        } else {
            return false;
        }

        clearstatcache(true, $destPath);

        if (!$ok || !is_file($destPath) || (int) filesize($destPath) <= 0) {
            @unlink($destPath);
            return false;
        }

        return $newFilename;
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

        $perms = api_get_permissions_for_new_directories();

        if (!is_dir($unzip_dir) && !@mkdir($unzip_dir, $perms, true)) {
            error_log('[COURSE_ARCHIVER] readCourse: failed to create unzip_dir="'.$unzip_dir.'"');
            return new Course();
        }

        if (!is_file($filePath)) {
            error_log('[COURSE_ARCHIVER] readCourse: backup zip not found filePath="'.$filePath.'"');
            return new Course();
        }

        if (!@copy($filePath, $unzip_dir.'/backup.zip')) {
            error_log('[COURSE_ARCHIVER] readCourse: failed to copy zip filePath="'.$filePath.'" to "'.$unzip_dir.'/backup.zip"');
            return new Course();
        }

        // Unzip the archive
        $zip = new \PclZip($unzip_dir.'/backup.zip');

        if (!@chdir($unzip_dir)) {
            error_log('[COURSE_ARCHIVER] readCourse: chdir failed unzip_dir="'.$unzip_dir.'"');
            return new Course();
        }

        // For course backups we must preserve original filenames so that
        // paths in course_info.dat still match the files in backup_path.
        $extractResult = $zip->extract(PCLZIP_OPT_TEMP_FILE_ON);

        if ($extractResult === 0) {
            error_log('[COURSE_ARCHIVER] readCourse: extract failed error="'.$zip->errorInfo(true).'" unzip_dir="'.$unzip_dir.'"');
            return new Course();
        }

        // Remove the archive-file
        if ($delete) {
            @unlink($filePath);
        }

        // Read the course
        if (!is_file('course_info.dat')) {
            error_log('[COURSE_ARCHIVER] readCourse: missing course_info.dat cwd="'.getcwd().'" unzip_dir="'.$unzip_dir.'"');
            return new Course();
        }

        $size = (int) @filesize('course_info.dat');
        if ($size <= 0) {
            error_log('[COURSE_ARCHIVER] readCourse: empty course_info.dat size='.$size.' cwd="'.getcwd().'"');
            return new Course();
        }

        $fp = @fopen('course_info.dat', 'r');
        if (false === $fp) {
            error_log('[COURSE_ARCHIVER] readCourse: failed to open course_info.dat cwd="'.getcwd().'"');
            return new Course();
        }

        $contents = @fread($fp, $size);
        @fclose($fp);

        $readLen = is_string($contents) ? strlen($contents) : -1;
        if (!is_string($contents) || $readLen <= 0) {
            error_log('[COURSE_ARCHIVER] readCourse: failed to read course_info.dat');
            return new Course();
        }

        // Backward compatibility aliases used by serialized payloads
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

        $decoded = base64_decode($contents, true);
        if (false === $decoded) {
            error_log('[COURSE_ARCHIVER] readCourse: base64_decode strict failed, retry non-strict');
            $decoded = base64_decode($contents);
        }

        if (!is_string($decoded) || $decoded === '') {
            error_log('[COURSE_ARCHIVER] readCourse: base64_decode produced empty payload');
            return new Course();
        }

        /** @var mixed $course */
        $course = \UnserializeApi::unserialize('course', $decoded);
        if (!is_object($course) || !in_array(get_class($course), ['Course', 'Chamilo\CourseBundle\Component\CourseCopy\Course'], true)) {
            error_log('[COURSE_ARCHIVER] readCourse: invalid class after unserialize, returning empty Course');
            return new Course();
        }

        // Ensure backup_path is always set when unserialize is successful
        $course->backup_path = $unzip_dir;

        return $course;
    }
}
