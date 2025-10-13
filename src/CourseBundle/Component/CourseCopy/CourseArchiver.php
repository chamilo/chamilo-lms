<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Asset;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use DateTime;
use PclZip;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use UnserializeApi;
use ZipArchive;

use const PATHINFO_EXTENSION;

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
    public static function cleanBackupDir(): void
    {
        $dir = self::getBackupDir();
        if (is_dir($dir)) {
            if ($handle = @opendir($dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ('.' != $file && '..' != $file
                          && str_starts_with($file, 'CourseArchiver_')
                        && is_dir($dir.'/'.$file)
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
     * @param mixed $course
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
            // TODO set and handle an error message telling the user to review the permissions on the archive directory
            error_log(__FILE__.' line '.__LINE__.': '.(false != \ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini').' - This error, occuring because your archive directory will not let this script write data into it, will prevent courses backups to be created', 0);
        }
        // Write the course-object to the file
        $fp = @fopen($course_info_file, 'w');
        if (false === $fp) {
            error_log(__FILE__.' line '.__LINE__.': '.(false != \ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }

        $res = @fwrite($fp, base64_encode(serialize($course)));
        if (false === $res) {
            error_log(__FILE__.' line '.__LINE__.': '.(false != \ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }

        $res = @fclose($fp);
        if (false === $res) {
            error_log(__FILE__.' line '.__LINE__.': '.(false != \ini_get('track_errors') ? $php_errormsg : 'error not recorded because track_errors is off in your php.ini'), 0);
        }

        // Copy all documents to the temp-dir
        if (isset($course->resources[RESOURCE_DOCUMENT]) && \is_array($course->resources[RESOURCE_DOCUMENT])) {
            $webEditorCss = api_get_path(WEB_CSS_PATH).'editor.css';

            /** @var Document $document */
            foreach ($course->resources[RESOURCE_DOCUMENT] as $document) {
                if ('document' === $document->file_type) {
                    $doc_dir = $backup_dir.$document->path;
                    @mkdir(\dirname($doc_dir), $perm_dirs, true);
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
        if (isset($course->resources[RESOURCE_SCORM]) && \is_array($course->resources[RESOURCE_SCORM])) {
            foreach ($course->resources[RESOURCE_SCORM] as $document) {
                copyDirTo($course->path.$document->path, $backup_dir.$document->path, false);
            }
        }

        // Copy calendar attachments.
        if (isset($course->resources[RESOURCE_EVENT]) && \is_array($course->resources[RESOURCE_EVENT])) {
            $doc_dir = \dirname($backup_dir.'/upload/calendar/');
            @mkdir($doc_dir, $perm_dirs, true);
            copyDirTo($course->path.'upload/calendar/', $doc_dir, false);
        }

        // Copy Learning path author image.
        if (isset($course->resources[RESOURCE_LEARNPATH]) && \is_array($course->resources[RESOURCE_LEARNPATH])) {
            $doc_dir = \dirname($backup_dir.'/upload/learning_path/');
            @mkdir($doc_dir, $perm_dirs, true);
            copyDirTo($course->path.'upload/learning_path/', $doc_dir, false);
        }

        // Copy announcements attachments.
        if (isset($course->resources[RESOURCE_ANNOUNCEMENT]) && \is_array($course->resources[RESOURCE_ANNOUNCEMENT])) {
            $doc_dir = \dirname($backup_dir.'/upload/announcements/');
            @mkdir($doc_dir, $perm_dirs, true);
            copyDirTo($course->path.'upload/announcements/', $doc_dir, false);
        }

        // Copy work folders (only folders)
        if (isset($course->resources[RESOURCE_WORK]) && \is_array($course->resources[RESOURCE_WORK])) {
            $doc_dir = $backup_dir.'work';
            @mkdir($doc_dir, $perm_dirs, true);
            copyDirWithoutFilesTo($course->path.'work/', $doc_dir);
        }

        if (isset($course->resources[RESOURCE_ASSET]) && \is_array($course->resources[RESOURCE_ASSET])) {
            /** @var Asset $asset */
            foreach ($course->resources[RESOURCE_ASSET] as $asset) {
                $doc_dir = $backup_dir.$asset->path;
                @mkdir(\dirname($doc_dir), $perm_dirs, true);
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
                if (3 == \count($file_parts)) {
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
     * Read a legacy course backup (.zip) and return a Course object.
     * - Extracts the zip into a temp dir.
     * - Finds and decodes course_info.dat (base64 + serialize).
     * - Registers legacy aliases/stubs BEFORE unserialize (critical).
     * - Normalizes common identifier fields to int after unserialize.
     */
    public static function readCourse(string $filename, bool $delete = false): false|Course
    {
        // Clean temp backup dirs and ensure backup dir exists
        self::cleanBackupDir();
        self::createBackupDir();

        $backupDir = rtrim(self::getBackupDir(), '/').'/';
        $zipPath = $backupDir.$filename;

        if (!is_file($zipPath)) {
            throw new RuntimeException('Backup file not found: '.$filename);
        }

        // 1) Extract zip into a temp directory
        $tmp = $backupDir.'CourseArchiver_'.uniqid('', true).'/';
        (new Filesystem())->mkdir($tmp);

        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath)) {
            throw new RuntimeException('Cannot open zip: '.$filename);
        }
        if (!$zip->extractTo($tmp)) {
            $zip->close();

            throw new RuntimeException('Cannot extract zip: '.$filename);
        }
        $zip->close();

        // 2) Read and decode course_info.dat (base64 + serialize)
        $courseInfoDat = $tmp.'course_info.dat';
        if (!is_file($courseInfoDat)) {
            // Fallback: search nested locations
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp));
            foreach ($rii as $f) {
                if ($f->isFile() && 'course_info.dat' === $f->getFilename()) {
                    $courseInfoDat = $f->getPathname();

                    break;
                }
            }
            if (!is_file($courseInfoDat)) {
                throw new RuntimeException('course_info.dat not found in backup');
            }
        }

        $raw = file_get_contents($courseInfoDat);
        $payload = base64_decode($raw, true);
        if (false === $payload) {
            throw new RuntimeException('course_info.dat is not valid base64');
        }

        // 3) Coerce numeric-string identifiers to integers *before* unserialize
        //    This prevents "Cannot assign string to property ... of type int"
        //    on typed properties (handles public/protected/private names).
        $payload = self::coerceNumericStringsInSerialized($payload);

        // 4) Register legacy aliases BEFORE unserialize (critical for v1 backups)
        self::registerLegacyAliases();

        // 5) Unserialize using UnserializeApi if present (v1-compatible)
        if (class_exists('UnserializeApi')) {
            /** @var Course $course */
            $course = UnserializeApi::unserialize('course', $payload);
        } else {
            /** @var Course|false $course */
            $course = @unserialize($payload, ['allowed_classes' => true]);
        }

        if (!\is_object($course)) {
            throw new RuntimeException('Could not unserialize legacy course');
        }

        // 6) Normalize common numeric identifiers after unserialize (extra safety)
        self::normalizeIds($course);

        // 7) Optionally delete uploaded file (compat with v1)
        if ($delete && is_file($zipPath)) {
            @unlink($zipPath);
        }

        // Keep temp dir until restore phase if files are needed later (compat with v1)
        return $course;
    }

    /**
     * Convert selected numeric-string fields to integers inside the serialized payload
     * to avoid "Cannot assign string to property ... of type int" on typed properties.
     *
     * It handles public, protected ("\0*\0key") and private ("\0Class\0key") property names.
     * We only coerce known identifier keys to keep it safe.
     */
    private static function coerceNumericStringsInSerialized(string $ser): string
    {
        // Identifier keys that must be integers
        $keys = [
            'id', 'iid', 'c_id', 'parent_id', 'thematic_id', 'attendance_id',
            'room_id', 'display_order', 'session_id', 'category_id',
        ];

        /**
         * Build a pattern that matches any of these name encodings:
         *  - public:   "id"
         *  - protected:"\0*\0id"
         *  - private:  "\0SomeClass\0id"
         *
         * We don't touch the key itself (so its s:N length stays valid).
         * We only replace the *value* part: s:M:"123"  =>  i:123
         */
        $alternatives = [];
        foreach ($keys as $k) {
            // public
            $alternatives[] = preg_quote($k, '/');
            // protected
            $alternatives[] = "\x00\\*\x00".preg_quote($k, '/');
            // private (class-specific; we accept any class name between NULs)
            $alternatives[] = "\x00[^\x00]+\x00".preg_quote($k, '/');
        }
        $nameAlt = '(?:'.implode('|', $alternatives).')';

        // Full pattern:
        // (s:\d+:"<any of the above forms>";) s:\d+:"(<digits>)";
        // Note: we *must not* use /u because of NUL bytes; keep binary-safe regex.
        $pattern = '/(s:\d+:"'.$nameAlt.'";)s:\d+:"(\d+)";/s';

        return preg_replace_callback(
            $pattern,
            static fn ($m) => $m[1].'i:'.$m[2].';',
            $ser
        );
    }

    /**
     * Recursively cast common identifier fields to int after unserialize.
     * Safe to call on arrays/objects/stdClass/legacy resource objects.
     */
    private static function normalizeIds(mixed &$node): void
    {
        $castKeys = [
            'id', 'iid', 'c_id', 'parent_id', 'thematic_id', 'attendance_id',
            'room_id', 'display_order', 'session_id', 'category_id',
        ];

        if (\is_array($node)) {
            foreach ($node as $k => &$v) {
                if (\is_string($k) && \in_array($k, $castKeys, true) && (\is_string($v) || is_numeric($v))) {
                    $v = (int) $v;
                } else {
                    self::normalizeIds($v);
                }
            }

            return;
        }

        if (\is_object($node)) {
            foreach (get_object_vars($node) as $k => $v) {
                if (\in_array($k, $castKeys, true) && (\is_string($v) || is_numeric($v))) {
                    $node->{$k} = (int) $v;

                    continue;
                }
                self::normalizeIds($node->{$k});
            }
        }
    }

    /**
     * Keep the old alias map so unserialize works exactly like v1.
     */
    private static function registerLegacyAliases(): void
    {
        $aliases = [
            'Chamilo\CourseBundle\Component\CourseCopy\Course' => 'Course',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Announcement' => 'Announcement',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Attendance' => 'Attendance',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\CalendarEvent' => 'CalendarEvent',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyLearnpath' => 'CourseCopyLearnpath',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseCopyTestCategory' => 'CourseCopyTestCategory',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseDescription' => 'CourseDescription',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\CourseSession' => 'CourseSession',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Document' => 'Document',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Forum' => 'Forum',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumCategory' => 'ForumCategory',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumPost' => 'ForumPost',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\ForumTopic' => 'ForumTopic',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Glossary' => 'Glossary',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\GradeBookBackup' => 'GradeBookBackup',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Link' => 'Link',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\LinkCategory' => 'LinkCategory',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Quiz' => 'Quiz',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestion' => 'QuizQuestion',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\QuizQuestionOption' => 'QuizQuestionOption',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\ScormDocument' => 'ScormDocument',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Survey' => 'Survey',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyInvitation' => 'SurveyInvitation',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\SurveyQuestion' => 'SurveyQuestion',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Thematic' => 'Thematic',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\ToolIntro' => 'ToolIntro',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Wiki' => 'Wiki',
            'Chamilo\CourseBundle\Component\CourseCopy\Resources\Work' => 'Work',
        ];

        foreach ($aliases as $fqcn => $alias) {
            if (!class_exists($alias)) {
                class_alias($fqcn, $alias);
            }
        }
    }
}
