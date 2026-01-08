<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy;

use Chamilo\CourseBundle\Component\CourseCopy\Resources\Asset;
use Chamilo\CourseBundle\Component\CourseCopy\Resources\Document;
use Chamilo\CoreBundle\Helpers\DateTimeHelper;
use DateTime;
use PclZip;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use UnserializeApi;
use ZipArchive;

use const PATHINFO_EXTENSION;

/**
 * Some functions to write a course-object to a zip-file and to read a course-
 * object from such a zip-file.
 *
 * Hardened to support PHP 8+ typed properties and legacy backups.
 *
 * @author Bart Mollet
 */
class CourseArchiver
{
    /** @var bool Global debug flag (true by default) */
    private static bool $debug = true;

    /** Debug logger (safe JSON, truncated) */
    private static function dlog(string $stage, mixed $payload = null): void
    {
        if (!self::$debug) { return; }
        $prefix = 'COURSE_ARCHIVER';
        if ($payload === null) {
            error_log("$prefix: $stage");
            return;
        }
        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json !== null && strlen($json) > 8000) {
                $json = substr($json, 0, 8000) . '…(truncated)';
            }
        } catch (Throwable $e) {
            $json = '[payload_json_error: ' . $e->getMessage() . ']';
        }
        error_log("$prefix: $stage -> " . $json);
    }

    /** Allow toggling debug at runtime. */
    public static function setDebug(?bool $flag): void
    {
        if ($flag === null) { return; }
        self::$debug = (bool) $flag;
    }

    /** Expose aliases/typed-props helpers to other components. */
    public static function preprocessSerializedPayloadForTypedProps(string $serialized): string
    {
        return self::coerceNumericStringsInSerialized($serialized);
    }
    public static function ensureLegacyAliases(): void
    {
        self::registerLegacyAliases();
    }

    /** @return string */
    public static function getBackupDir()
    {
        return api_get_path(SYS_ARCHIVE_PATH) . 'course_backups/';
    }

    /** @return string */
    public static function createBackupDir()
    {
        $perms = api_get_permissions_for_new_directories();
        $dir = self::getBackupDir();
        $fs = new Filesystem();
        $fs->mkdir($dir, $perms);
        self::dlog('createBackupDir', ['dir' => $dir, 'perms' => $perms]);

        return $dir;
    }

    /** Delete old temp-dirs. */
    public static function cleanBackupDir(): void
    {
        $dir = self::getBackupDir();
        self::dlog('cleanBackupDir.begin', ['dir' => $dir]);

        if (is_dir($dir)) {
            if ($handle = @opendir($dir)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file !== '.' && $file !== '..'
                        && str_starts_with($file, 'CourseArchiver_')
                        && is_dir($dir . '/' . $file)
                    ) {
                        @rmdirr($dir . '/' . $file);
                        self::dlog('cleanBackupDir.removed', ['path' => $dir . '/' . $file]);
                    }
                }
                closedir($handle);
            }
        }

        self::dlog('cleanBackupDir.end');
    }

    /**
     * Write a course and all its resources to a zip-file.
     *
     * @param mixed $course
     * @return string A pointer to the zip-file
     */
    public static function createBackup($course)
    {
        self::cleanBackupDir();
        self::createBackupDir();

        $perm_dirs = api_get_permissions_for_new_directories();
        $backupDirectory = self::getBackupDir();

        // Create a temp directory
        $backup_dir = $backupDirectory . 'CourseArchiver_' . api_get_unique_id() . '/';

        // All course-information will be stored in course_info.dat
        $course_info_file = $backup_dir . 'course_info.dat';

        $user = api_get_user_info();
        $date = DateTimeHelper::nowLocalDateTime();
        $zipFileName = $user['user_id'] . '_' . $course->code . '_' . $date->format('Ymd-His') . '.zip';
        $zipFilePath = $backupDirectory . $zipFileName;

        self::dlog('createBackup.begin', [
            'zip' => $zipFileName,
            'backup_dir' => $backup_dir,
            'course_code' => $course->code ?? null,
        ]);

        $php_errormsg = '';
        $res = @mkdir($backup_dir, $perm_dirs);
        if ($res === false) {
            error_log(__FILE__ . ' line ' . __LINE__ . ': ' . ($php_errormsg ?: 'mkdir failed') . ' - Archive directory not writable; will prevent backups.', 0);
        }

        // Write the course-object to the file
        $fp = @fopen($course_info_file, 'w');
        if ($fp === false) {
            error_log(__FILE__ . ' line ' . __LINE__ . ': ' . ($php_errormsg ?: 'fopen failed for course_info.dat'), 0);
        }

        $serialized = @serialize($course);
        $b64 = base64_encode($serialized);
        $okWrite = $fp !== false ? @fwrite($fp, $b64) : false;
        if ($okWrite === false) {
            error_log(__FILE__ . ' line ' . __LINE__ . ': ' . ($php_errormsg ?: 'fwrite failed for course_info.dat'), 0);
        }
        if ($fp !== false) {
            @fclose($fp);
        }

        self::dlog('createBackup.course_info', [
            'size_bytes' => @filesize($course_info_file),
            'md5' => @md5_file($course_info_file),
        ]);

        // Copy all documents to the temp-dir
        if (isset($course->resources[RESOURCE_DOCUMENT]) && is_array($course->resources[RESOURCE_DOCUMENT])) {
            $webEditorCss = api_get_path(WEB_CSS_PATH) . 'editor.css';

            /** @var Document $document */
            foreach ($course->resources[RESOURCE_DOCUMENT] as $document) {
                if ('document' === $document->file_type) {
                    $doc_dir = $backup_dir . $document->path;
                    @mkdir(dirname($doc_dir), $perm_dirs, true);
                    if (file_exists($course->path . $document->path)) {
                        @copy($course->path . $document->path, $doc_dir);
                        // Check if is html or htm
                        $extension = pathinfo(basename($document->path), PATHINFO_EXTENSION);
                        switch ($extension) {
                            case 'html':
                            case 'htm':
                                $contents = @file_get_contents($doc_dir);
                                if ($contents !== false) {
                                    $contents = str_replace(
                                        $webEditorCss,
                                        '{{css_editor}}',
                                        $contents
                                    );
                                    @file_put_contents($doc_dir, $contents);
                                }
                                break;
                        }
                    }
                } else {
                    @mkdir($backup_dir . $document->path, $perm_dirs, true);
                }
            }
        }

        // Copy all scorm documents to the temp-dir
        if (isset($course->resources[RESOURCE_SCORM]) && is_array($course->resources[RESOURCE_SCORM])) {
            foreach ($course->resources[RESOURCE_SCORM] as $document) {
                @copyDirTo($course->path . $document->path, $backup_dir . $document->path, false);
            }
        }

        // Copy calendar attachments.
        if (isset($course->resources[RESOURCE_EVENT]) && is_array($course->resources[RESOURCE_EVENT])) {
            $doc_dir = dirname($backup_dir . '/upload/calendar/');
            @mkdir($doc_dir, $perm_dirs, true);
            @copyDirTo($course->path . 'upload/calendar/', $doc_dir, false);
        }

        // Copy Learning path author image.
        if (isset($course->resources[RESOURCE_LEARNPATH]) && is_array($course->resources[RESOURCE_LEARNPATH])) {
            $doc_dir = dirname($backup_dir . '/upload/learning_path/');
            @mkdir($doc_dir, $perm_dirs, true);
            @copyDirTo($course->path . 'upload/learning_path/', $doc_dir, false);
        }

        // Copy announcements attachments.
        if (isset($course->resources[RESOURCE_ANNOUNCEMENT]) && is_array($course->resources[RESOURCE_ANNOUNCEMENT])) {
            $doc_dir = dirname($backup_dir . '/upload/announcements/');
            @mkdir($doc_dir, $perm_dirs, true);
            @copyDirTo($course->path . 'upload/announcements/', $doc_dir, false);
        }

        // Copy work folders (only folders)
        if (isset($course->resources[RESOURCE_WORK]) && is_array($course->resources[RESOURCE_WORK])) {
            $doc_dir = $backup_dir . 'work';
            @mkdir($doc_dir, $perm_dirs, true);
            @copyDirWithoutFilesTo($course->path . 'work/', $doc_dir);
        }

        if (isset($course->resources[RESOURCE_ASSET]) && is_array($course->resources[RESOURCE_ASSET])) {
            /** @var Asset $asset */
            foreach ($course->resources[RESOURCE_ASSET] as $asset) {
                $doc_dir = $backup_dir . $asset->path;
                @mkdir(dirname($doc_dir), $perm_dirs, true);
                $assetPath = $course->path . $asset->path;

                if (!file_exists($assetPath)) {
                    continue;
                }

                if (is_dir($assetPath)) {
                    @copyDirTo($assetPath, $doc_dir, false);
                } else {
                    @copy($assetPath, $doc_dir);
                }
            }
        }

        // Zip the course-contents
        $zip = new PclZip($zipFilePath);
        $zip->create($backup_dir, PCLZIP_OPT_REMOVE_PATH, $backup_dir);

        // Remove the temp-dir.
        @rmdirr($backup_dir);

        self::dlog('createBackup.end', ['zip' => $zipFileName, 'path' => $zipFilePath]);

        return $zipFileName;
    }

    /**
     * @param int $user_id
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
                    $ext = $file_parts[1] ?? null;
                    if ('zip' == $ext && ((null != $user_id && $owner_id == $user_id) || (null == $user_id))) {
                        $date =
                            substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' .
                            substr($date, 6, 2) . ' ' . substr($date, 9, 2) . ':' .
                            substr($date, 11, 2) . ':' . substr($date, 13, 2);
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
     * @param array|string $file path or $_FILES['tmp_name']
     * @return bool|string
     */
    public static function importUploadedFile($file)
    {
        $new_filename = uniqid('import_file', true) . '.zip';
        $new_dir = self::getBackupDir();
        if (!is_dir($new_dir)) {
            $fs = new Filesystem();
            $fs->mkdir($new_dir);
        }
        if (is_dir($new_dir) && is_writable($new_dir)) {
            // move_uploaded_file() may fail in CLI/tests; try rename() as fallback
            $src = is_array($file) ? ($file['tmp_name'] ?? '') : (string) $file;
            $dst = $new_dir . $new_filename;

            $moved = @move_uploaded_file($src, $dst);
            if (!$moved) {
                $moved = @rename($src, $dst);
            }
            if ($moved) {
                self::dlog('importUploadedFile.ok', ['dst' => $dst, 'size' => @filesize($dst)]);
                return $new_filename;
            }

            self::dlog('importUploadedFile.fail', ['src' => $src, 'dst' => $dst]);
            return false;
        }

        self::dlog('importUploadedFile.dir_not_writable', ['dir' => $new_dir]);
        return false;
    }

    /**
     * Read a legacy course backup (.zip) and return a Course object.
     * - Extracts the zip into a temp dir.
     * - Finds and decodes course_info.dat:
     *     prefers base64(serialize), then raw serialize as fallback.
     * - Registers legacy aliases/stubs BEFORE unserialize (critical).
     * - Coerces typed-prop numeric strings BEFORE unserialize.
     * - Tries relaxed unserialize (no class instantiation) on failure and converts incomplete classes to stdClass.
     * - Normalizes common numeric identifiers AFTER unserialize (safe).
     *
     * @throws RuntimeException
     */
    public static function readCourse(string $filename, bool $delete = false): false|Course
    {
        self::cleanBackupDir();
        self::createBackupDir();

        $backupDir = rtrim(self::getBackupDir(), '/') . '/';
        $zipPath = $backupDir . $filename;

        if (!is_file($zipPath)) {
            throw new RuntimeException('Backup file not found: ' . $filename);
        }

        self::dlog('readCourse.begin', ['filename' => $filename, 'zipPath' => $zipPath]);

        // 1) Extract zip into a temp directory
        $tmp = $backupDir . 'CourseArchiver_' . uniqid('', true) . '/';
        (new Filesystem())->mkdir($tmp);

        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath)) {
            throw new RuntimeException('Cannot open zip: ' . $filename);
        }
        if (!$zip->extractTo($tmp)) {
            $zip->close();
            throw new RuntimeException('Cannot extract zip: ' . $filename);
        }
        $zip->close();

        // 2) Read course_info.dat (search nested if necessary)
        $courseInfoDat = $tmp . 'course_info.dat';
        if (!is_file($courseInfoDat)) {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp));
            foreach ($rii as $f) {
                if ($f->isFile() && $f->getFilename() === 'course_info.dat') {
                    $courseInfoDat = $f->getPathname();
                    break;
                }
            }
            if (!is_file($courseInfoDat)) {
                throw new RuntimeException('course_info.dat not found in backup');
            }
        }

        $raw = (string) @file_get_contents($courseInfoDat);
        if ($raw === '') {
            throw new RuntimeException('course_info.dat is empty');
        }

        self::dlog('readCourse.course_info', [
            'path' => $courseInfoDat,
            'size' => strlen($raw),
            'md5'  => md5($raw),
            'magic_ascii' => preg_replace('/[^\x20-\x7E]/', '.', substr($raw, 0, 16)),
            'magic_hex'   => bin2hex(substr($raw, 0, 8)),
        ]);

        // 3) Decode: prefer base64(serialize), else raw serialize
        $payload = base64_decode($raw, true);
        $encoding = 'base64(php-serialize)';

        if ($payload === false) {
            // Some very old backups stored raw serialize without base64
            $payload = $raw;
            $encoding = 'php-serialize';
        }

        // 4) Coerce numeric-string identifiers to int in serialized payload
        $payload = self::coerceNumericStringsInSerialized($payload);

        // 5) Register legacy aliases BEFORE unserialize
        self::registerLegacyAliases();

        // 6) Unserialize with robust fallbacks
        $course = null;
        $unserOk = false;
        $unserErr = null;
        $usedRelaxed = false;

        set_error_handler(static function () { /* suppress E_NOTICE/E_WARNING from unserialize */ });
        try {
            if (class_exists('UnserializeApi')) {
                /** @var Course $c */
                $c = UnserializeApi::unserialize('course', $payload);
                $course = $c;
                $unserOk = is_object($course);
            } else {
                /** @var Course|false $c */
                $c = @unserialize($payload, ['allowed_classes' => true]); // may throw TypeError with typed props
                if (is_object($c) || ($c === false && trim($payload) === 'b:0;')) {
                    $course = $c;
                    $unserOk = is_object($course);
                } else {
                    $unserOk = false;
                }

                // Relaxed fallback: do not instantiate classes; convert incomplete classes to stdClass
                if (!$unserOk) {
                    /** @var mixed $c2 */
                    $c2 = @unserialize($payload, ['allowed_classes' => false]);
                    if ($c2 !== false || trim($payload) === 'b:0;') {
                        $c2 = self::deincomplete($c2);
                        if (is_object($c2)) {
                            $course = $c2;
                            $unserOk = true;
                            $usedRelaxed = true;
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $unserErr = $e->getMessage();

            // Hard fallback inside catch as well
            try {
                $c2 = @unserialize($payload, ['allowed_classes' => false]);
                if ($c2 !== false || trim($payload) === 'b:0;') {
                    $c2 = self::deincomplete($c2);
                    if (is_object($c2)) {
                        $course = $c2;
                        $unserOk = true;
                        $usedRelaxed = true;
                        $unserErr = null;
                    }
                }
            } catch (Throwable $e2) {
                $unserErr = $unserErr . ' | relaxed: ' . $e2->getMessage();
            }
        } finally {
            restore_error_handler();
        }

        self::dlog('readCourse.unserialize', [
            'ok' => $unserOk,
            'encoding' => $encoding,
            'relaxed' => $usedRelaxed,
            'error' => $unserErr,
        ]);

        if (!$unserOk || !is_object($course)) {
            throw new RuntimeException('Could not unserialize legacy course');
        }

        // 7) Normalize numeric identifiers post-unserialize (safe)
        self::normalizeIds($course);

        // 8) Optionally delete uploaded file (compat with v1)
        if ($delete && is_file($zipPath)) {
            @unlink($zipPath);
            self::dlog('readCourse.deleted_zip', ['path' => $zipPath]);
        }

        self::dlog('readCourse.end', ['ok' => true]);

        // Keep temp dir; some restore flows need extracted files
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
        // If it looks like JSON, do nothing (defensive)
        $t = ltrim($ser);
        if ($t !== '' && ($t[0] === '{' || $t[0] === '[')) {
            self::dlog('coerceNumericStrings.skip_json');
            return $ser;
        }

        // Common identifier keys (conservative list)
        $keys = [
            'id','iid','c_id','parent_id','thematic_id','attendance_id',
            'room_id','display_order','session_id','category_id',
            // forum/link/doc/quiz/survey typical relations:
            'forum_id','thread_id','post_id','survey_id','question_id',
            'document_id','doc_id','link_id','quiz_id','work_id',
        ];

        $alternatives = [];
        foreach ($keys as $k) {
            $alternatives[] = preg_quote($k, '/');                      // public
            $alternatives[] = "\x00\\*\x00" . preg_quote($k, '/');      // protected
            $alternatives[] = "\x00[^\x00]+\x00" . preg_quote($k, '/'); // private (any class)
        }
        $nameAlt = '(?:' . implode('|', $alternatives) . ')';

        // Pattern: property name token, then value token -> coerce s:"123" to i:123
        $pattern = '/(s:\d+:"' . $nameAlt . '";)s:\d+:"(\d+)";/s';

        $fixed = preg_replace_callback(
            $pattern,
            static fn($m) => $m[1] . 'i:' . $m[2] . ';',
            $ser
        );

        if ($fixed !== null && $fixed !== $ser) {
            self::dlog('coerceNumericStrings.changed', ['delta_len' => strlen($fixed) - strlen($ser)]);
            return $fixed;
        }

        self::dlog('coerceNumericStrings.no_change');
        return $ser;
    }

    /**
     * Recursively cast common identifier fields to int after unserialize (safe).
     * - Never writes into private/protected properties (names starting with "\0").
     * - Most coercion should happen *before* unserialize; this is a safety net.
     */
    private static function normalizeIds(mixed &$node): void
    {
        $castKeys = [
            'id','iid','c_id','parent_id','thematic_id','attendance_id',
            'room_id','display_order','session_id','category_id',
            'forum_id','thread_id','post_id','survey_id','question_id',
            'document_id','doc_id','link_id','quiz_id','work_id',
        ];

        if (is_array($node)) {
            foreach ($node as &$v) {
                self::normalizeIds($v);
            }
            return;
        }

        if (is_object($node)) {
            foreach (get_object_vars($node) as $k => $v) {
                // Skip private/protected property names (start with NUL)
                if (is_string($k) && $k !== '' && $k[0] === "\0") {
                    self::normalizeIds($v);
                    continue;
                }

                if (in_array($k, $castKeys, true) && (is_string($v) || is_numeric($v))) {
                    try {
                        $node->{$k} = (int) $v;
                    } catch (Throwable) {
                        // Read-only or typed mismatch: ignore
                    }
                    continue;
                }

                self::normalizeIds($node->{$k});
            }
        }
    }

    /**
     * Replace any __PHP_Incomplete_Class instances with stdClass (deep).
     * Also traverses arrays and objects.
     */
    private static function deincomplete(mixed $v): mixed
    {
        // Handle leaf
        if ($v instanceof \__PHP_Incomplete_Class) {
            $o = new \stdClass();
            foreach (get_object_vars($v) as $k => $vv) {
                $o->{$k} = self::deincomplete($vv);
            }
            return $o;
        }
        // Recurse arrays
        if (is_array($v)) {
            foreach ($v as $k => $vv) {
                $v[$k] = self::deincomplete($vv);
            }
            return $v;
        }
        // Recurse stdClass or any object
        if (is_object($v)) {
            foreach (get_object_vars($v) as $k => $vv) {
                $v->{$k} = self::deincomplete($vv);
            }
            return $v;
        }
        return $v;
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

        self::dlog('registerLegacyAliases.done', ['count' => count($aliases)]);
    }
}
