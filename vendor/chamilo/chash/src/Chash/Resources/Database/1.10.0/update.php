<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @param $_configuration
 * @param $mainConnection
 * @param $courseList
 * @param $dryRun
 * @param \Symfony\Component\Console\Output\OutputInterface $output
 * @param $upgrade
 */
$updateFiles = function($_configuration, $mainConnection, $courseList, $dryRun, $output, $upgrade)
{
    $sysPath = $upgrade->getRootSys();
    $sysCodePath = $upgrade->getRootSys().'main/';
    $output->writeln(__DIR__.'update.php');
    try {
        $langPath = $sysCodePath.'lang/';
        // Only erase files from Chamilo languages (not sublanguages defined by the users)
        $officialLanguages = array(
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
        );

        $filesToDelete = array(
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
        );

        $output->writeln('Cleaning lang vars');

        $list = scandir($langPath);
        foreach ($list as $entry) {
            if (is_dir($langPath . $entry) &&
                in_array($entry, $officialLanguages)
            ) {
                foreach ($filesToDelete as $file) {
                    if (is_file($langPath . $entry . '/' . $file . '.inc.php')) {
                        unlink($langPath . $entry . '/' . $file . '.inc.php');
                    }
                }
            }
        }

        $fs = new Filesystem();

        $output->writeln('Cleaning folders');

        // Remove the "main/conference/" directory that wasn't used since years long
        // past - see rrmdir function declared below
        if ($fs->exists($sysCodePath.'conference')) {
           $fs->remove($sysCodePath.'conference');
        }

        // Other files that we renamed
        // events.lib.inc.php has been renamed to events.lib.php
        if (is_file($sysCodePath.'inc/lib/events.lib.inc.php')) {
            @unlink($sysCodePath.'inc/lib/events.lib.inc.php');
        }

        if (is_file($sysPath.'courses/.htaccess')) {
            unlink($sysPath.'courses/.htaccess');
        }

        // Move dirs into new structures.
        $movePathList = [
            $sysCodePath.'upload/users/groups' => $sysPath . 'app/upload/groups',
            $sysCodePath.'upload/users' => $sysPath . 'app/upload/users',
            $sysCodePath.'upload/badges' => $sysPath . 'app/upload/badges',
            $sysPath.'courses' => $sysPath . 'app/courses',
            $sysPath.'searchdb' => $sysPath . 'app/upload/plugins/xapian/',
            $sysPath.'home' => $sysPath . 'app/home'
        ];

        $output->writeln('Moving folders');

        foreach ($movePathList as $origin => $destination) {
            $output->writeln("Renaming: '$origin' to '$destination'");
            if (is_dir($origin)) {
                $fs->mirror($origin, $destination, null, ['override']);
                $fs->remove($origin);
            }
        }

        // Delete all "courses/ABC/index.php" files.
        $output->writeln('Deleting index.php inside course folders example: courses/XXX/index.php ');

        $finder = new Finder();
        $courseDir = $sysPath.'app/courses';
        if (is_dir($courseDir)) {
            $dirs = $finder->directories()->in($courseDir);
            /** @var Symfony\Component\Finder\SplFileInfo $dir */
            foreach ($dirs as $dir) {
                $indexFile = $dir->getPath().'/index.php';
                if ($fs->exists($indexFile)) {
                    $output->writeln('Deleting: '.$indexFile);
                    $fs->remove($indexFile);
                }
            }
        }

        // Remove old "courses" folder if empty
        $originalCourseDir = $sysPath.'courses';

        if (is_dir($originalCourseDir)) {
            $dirs = $finder->directories()->in($originalCourseDir);
            $files = $finder->directories()->in($originalCourseDir);
            $dirCount = $dirs->count();
            $fileCount = $dirs->count();
            if ($fileCount == 0 && $dirCount == 0) {
                if ($fs->exists($sysPath.'courses')) {
                    $fs->remove($sysPath.'courses');
                }
            }
        }

        // Remove archive
        if ($fs->exists($sysPath.'archive')) {
            $output->writeln('Remove archive folder');
            $fs->remove($sysPath.'archive');
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }
};
