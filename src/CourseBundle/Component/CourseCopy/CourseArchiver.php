<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Asset;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use DateTime;
use PclZip;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Some functions to write a course-object to a zip-file and to read a course-
 * object from such a zip-file.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
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
                while (false !== ($file = readdir($handle))) {
                    if ('.' != $file && '..' != $file &&
                        0 === strpos($file, 'CourseArchiver_') &&
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
        $date = new DateTime(api_get_local_time());
        $zipFileName = $user['user_id'].'_'.$course->code.'_'.$date->format('Ymd-His').'.zip';
        $zipFilePath = $backupDirectory.$zipFileName;

        $php_errormsg = '';
        $res = @mkdir($backup_dir, $perm_dirs);
        if (false === $res) {
            //TODO set and handle an error message telling the user to review the permissions on the archive directory
            error_log(__FILE__.' line '.__LINE__.': '.(false != ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini').' - This error, occuring because your archive directory will not let this script write data into it, will prevent courses backups to be created', 0);
        }
        // Write the course-object to the file
        $fp = @fopen($course_info_file, 'w');
        if (false === $fp) {
            error_log(__FILE__.' line '.__LINE__.': '.(false != ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }

        $res = @fwrite($fp, base64_encode(serialize($course)));
        if (false === $res) {
            error_log(__FILE__.' line '.__LINE__.': '.(false != ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }

        $res = @fclose($fp);
        if (false === $res) {
            error_log(__FILE__.' line '.__LINE__.': '.(false != ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }

        // Copy all documents to the temp-dir
        if (isset($course->resources[RESOURCE_DOCUMENT]) && is_array($course->resources[RESOURCE_DOCUMENT])) {
            $webEditorCss = api_get_path(WEB_CSS_PATH).'editor.css';
            /** @var Document $document */
            foreach ($course->resources[RESOURCE_DOCUMENT] as $document) {
                if ('document' === $document->file_type) {
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
        $zip = new PclZip($zipFilePath);
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
            while (false !== ($file = readdir($dir))) {
                $file_parts = explode('_', $file);
                if (3 == count($file_parts)) {
                    $owner_id = $file_parts[0];
                    $course_code = $file_parts[1];
                    $file_parts = explode('.', $file_parts[2]);
                    $date = $file_parts[0];
                    $ext = isset($file_parts[1]) ? $file_parts[1] : null;
                    if ('zip' == $ext && (null != $user_id && $owner_id == $user_id || null == $user_id)) {
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
     * @return Course The course
     *
     * @todo Check if the archive is a correct Chamilo-export
     */
    public static function readCourse($filename, $delete = false)
    {
    }
}
