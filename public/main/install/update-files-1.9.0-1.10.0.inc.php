<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Chamilo LMS.
 *
 * Updates the Chamilo files from version 1.9.0 to version 1.10.0
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 *
 * @package chamilo.install
 */
error_log("Starting ".basename(__FILE__));

global $debug;

if (defined('SYSTEM_INSTALLATION')) {
    // Changes for 1.10.x
    // Delete directories and files that are not necessary anymore
    // pChart (1) lib, etc

    // Delete the "chat" file in all language directories, as variables have been moved to the trad4all file
    $langPath = api_get_path(SYS_CODE_PATH).'lang/';
    // Only erase files from Chamilo languages (not sublanguages defined by the users)
    $officialLanguages = [
        'arabic',
        'asturian',
        'basque',
        'bengali',
        'bosnian',
        'brazilian',
        'bulgarian',
        'catalan',
        'croatian',
        'czech',
        'danish',
        'dari',
        'dutch',
        'english',
        'esperanto',
        'faroese',
        'finnish',
        'french',
        'friulian',
        'galician',
        'georgian',
        'german',
        'greek',
        'hebrew',
        'hindi',
        'hungarian',
        'indonesian',
        'italian',
        'japanese',
        'korean',
        'latvian',
        'lithuanian',
        'macedonian',
        'malay',
        'norwegian',
        'occitan',
        'pashto',
        'persian',
        'polish',
        'portuguese',
        'quechua_cusco',
        'romanian',
        'russian',
        'serbian',
        'simpl_chinese',
        'slovak',
        'slovenian',
        'somali',
        'spanish',
        'spanish_latin',
        'swahili',
        'swedish',
        'tagalog',
        'thai',
        'tibetan',
        'trad_chinese',
        'turkish',
        'ukrainian',
        'vietnamese',
        'xhosa',
        'yoruba',
    ];

    $filesToDelete = [
        'accessibility',
        'admin',
        'agenda',
        'announcements',
        'blog',
        'chat',
        'coursebackup',
        'course_description',
        'course_home',
        'course_info',
        'courses',
        'create_course',
        'document',
        'dropbox',
        'exercice',
        'external_module',
        'forum',
        'glossary',
        'gradebook',
        'group',
        'help',
        'import',
        'index',
        'install',
        'learnpath',
        'link',
        'md_document',
        'md_link',
        'md_mix',
        'md_scorm',
        'messages',
        'myagenda',
        'notebook',
        'notification',
        'registration',
        'reservation',
        'pedaSuggest',
        'resourcelinker',
        'scorm',
        'scormbuilder',
        'scormdocument',
        'slideshow',
        'survey',
        'tracking',
        'userInfo',
        'videoconf',
        'wiki',
        'work',
    ];

    $list = scandir($langPath);
    foreach ($list as $entry) {
        if (is_dir($langPath.$entry) &&
            in_array($entry, $officialLanguages)
        ) {
            foreach ($filesToDelete as $file) {
                if (is_file($langPath.$entry.'/'.$file.'.inc.php')) {
                    unlink($langPath.$entry.'/'.$file.'.inc.php');
                }
            }
        }
    }

    if ($debug) {
        error_log('Cleaning folders');
    }

    // Remove the "main/conference/" directory that wasn't used since years long
    // past - see rrmdir function declared below
    @rrmdir(api_get_path(SYS_CODE_PATH).'conference');
    // Other files that we renamed
    // events.lib.inc.php has been renamed to events.lib.php
    if (is_file(api_get_path(LIBRARY_PATH).'events.lib.inc.php')) {
        @unlink(api_get_path(LIBRARY_PATH).'events.lib.inc.php');
    }

    if (is_file(api_get_path(SYS_PATH).'courses/.htaccess')) {
        unlink(api_get_path(SYS_PATH).'courses/.htaccess');
    }

    // Move dirs into new structures.
    $movePathList = [
        api_get_path(SYS_CODE_PATH).'upload/users/groups' => api_get_path(SYS_UPLOAD_PATH).'groups',
        api_get_path(SYS_CODE_PATH).'upload/users' => api_get_path(SYS_UPLOAD_PATH).'users',
        api_get_path(SYS_CODE_PATH).'upload/badges' => api_get_path(SYS_UPLOAD_PATH).'badges',
        api_get_path(SYS_PATH).'courses' => api_get_path(SYS_APP_PATH).'courses',
        api_get_path(SYS_PATH).'searchdb' => api_get_path(SYS_UPLOAD_PATH).'plugins/xapian/',
        api_get_path(SYS_PATH).'home' => api_get_path(SYS_APP_PATH).'home',
    ];

    if ($debug) {
        error_log('Moving folders');
    }

    $fs = new Filesystem();

    foreach ($movePathList as $origin => $destination) {
        if (is_dir($origin)) {
            $fs->mirror($origin, $destination);

            if ($debug) {
                error_log("Renaming: '$origin' to '$destination'");
            }

            try {
                $fs->remove($origin);
            } catch (IOException $e) {
                // If removing the directory doesn't work, just log an error and continue
                error_log('Could not move '.$origin.' to '.$destination.'('.$e->getMessage().'). Please move it manually.');
            }
        }
    }

    // Delete all "courses/ABC/index.php" files.
    if ($debug) {
        error_log('Deleting old courses/ABC/index.php files');
    }
    $finder = new Finder();

    $courseDir = api_get_path(SYS_APP_PATH).'courses';
    if (is_dir($courseDir)) {
        $dirs = $finder->directories()->in($courseDir);
        /** @var Symfony\Component\Finder\SplFileInfo $dir */
        foreach ($dirs as $dir) {
            $indexFile = $dir->getPath().'/index.php';
            if ($debug) {
                error_log('Deleting: '.$indexFile);
            }
            if ($fs->exists($indexFile)) {
                $fs->remove($indexFile);
            }
        }
    }

    // Remove old "courses" folder if empty
    $originalCourseDir = api_get_path(SYS_PATH).'courses';

    if (is_dir($originalCourseDir)) {
        $dirs = $finder->directories()->in($originalCourseDir);
        $files = $finder->directories()->in($originalCourseDir);
        $dirCount = $dirs->count();
        $fileCount = $dirs->count();
        if ($fileCount == 0 && $dirCount == 0) {
            @rrmdir(api_get_path(SYS_PATH).'courses');
        }
    }

    if ($debug) {
        error_log('Remove archive folder');
    }

    // Remove archive
    @rrmdir(api_get_path(SYS_PATH).'archive');
} else {
    echo 'You are not allowed here !'.__FILE__;
}
