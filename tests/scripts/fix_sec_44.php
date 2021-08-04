<?php

/* For licensing terms, see /license.txt */

/**
 * This script fixes a file-upload vulnerability introduced in Chamilo 1.11.12
 * in a way that cannot be fixed through simple update.
 * It is safe to execute through the web, as it only checks very specific files
 * and directories that should be removed. If permissions do not allow the web
 * server to remove those files, a report will help the user identify which
 * files to remove manually from the server.
 * This refers to Issue 44 described at
 * https://support.chamilo.org/projects/chamilo-18/wiki/Security_issues
 * This script should be run on any Chamilo installation having been updated
 * through Git or through the 1.11.12 installer between early May 2020 and 
 * late October 2020.
 */

$deleteList = [
    'js/demo.js',
    'server/',
    '.github/',
    '.gitignore',
    'README.md',
    'SECURITY.md',
    'VULNERABILITIES.md',
    'cors/',
    'docker-composer.yml',
    'test/',
    'wdio/',
];

if (PHP_SAPI != 'cli') {
    if (is_file(__DIR__.'/../../app/config/configuration.php')) {
        require_once __DIR__.'/../../app/config/configuration.php';
        $chamiloFolder = $_configuration['root_sys'];
    } elseif (is_file(__DIR__.'/app/config/configuration.php')) {
        require_once __DIR__.'/app/config/configuration.php';
        $chamiloFolder = __DIR__;
    }
    deleteFilesWeb($chamiloFolder);
    exit;
}
// If CLI, do not depend on being inside a Chamilo folder and ask for cli args
if ($argc < 2) {
    echo "This script scans a main folder where different Chamilo installations might".PHP_EOL."be located and removes files which introduce a security vulnerability.".PHP_EOL;
    echo "It requires 1 or 2 arguments:".PHP_EOL;
    echo "  [main path]  The main directory where multiple Chamilo installations are located".PHP_EOL;
    echo "  [sub path]   If each Chamilo directory contains a sub-path ".PHP_EOL;
    echo "               (e.g. [main path]/chamilo1/htdocs), give this arguments as \"htdocs\"".PHP_EOL;
}
$mainFolder = '/var/www/';
$subFolder = '/';
if ($argc == 3) {
    $subFolder = trim($argv[2]);
    if (substr($subFolder, -1, 1) != '/') {
        $subFolder .= '/';
    }
    if (substr($subFolder, 0, 1) != '/') {
        $subFolder = '/'.$subFolder;
    }
}
$mainFolder = trim($argv[1]);
if ('.' == trim($argv[1])) {
    $mainFolder = getcwd().'/';
} else {
    if (substr($mainFolder, -1, 1) != '/') {
        $mainFolder .= '/';
    }
}

$foldersList = scandir($mainFolder);
foreach ($foldersList as $folder) {
    // Ignore folders starting with .
    if (substr($folder, 0, 1) == '.') {
        continue;
    }
    $chamiloFolder = $mainFolder.$folder.$subFolder;
    $configPath = $chamiloFolder.'app/config/configuration.php';
    if (file_exists($configPath)) {
        // This is likely Chamilo portal, scan further and delete files
        deleteFilesSystem($chamiloFolder);
    }
}

/**
 * Delete files using PHP
 * @param string $folder The Chamilo root folder
 */
function deleteFilesWeb($folder) {
    global $deleteList;
    $dangerFolder = $folder.'app/Resources/public/assets/jquery-file-upload/';
    $dangerFolder2 = $folder.'web/assets/jquery-file-upload/';
    echo "Analyzing Chamilo folder...<br />".PHP_EOL;
    $risk = 0;
    if (file_exists($dangerFolder)) {
        $risk = 1;
        echo "  Dangerous folder 1 exists in Resources, cleaning...<br/>".PHP_EOL;
        foreach ($deleteList as $deleteEntry) {
            if (substr($deleteEntry, -1, 1) == '/') {
                // this is a folder, recurse
                rmdirr($dangerFolder.$deleteEntry);
            } else {
                unlink($dangerFolder.$deleteEntry);
            }
        }
        if (is_file($dangerFolder.'README.md')) {
            echo "There was a problem removing files in 'app/Resources/public/assets/jquery-file-upload/'. Please remove the following files and folders manually:<br />".PHP_EOL;
            echo "<ul>".PHP_EOL;
            foreach ($deleteList as $deleteEntry) {
                echo "<li>[chamilo folder]/app/Resources/public/assets/jquery-file-upload/".$deleteEntry."</li>".PHP_EOL;
            }
            echo "</ul>".PHP_EOL;
        }
    }
    if (file_exists($dangerFolder2)) {
        $risk = 1;
        echo "  Dangerous folder 2 exists in web, cleaning...<br />".PHP_EOL;
        foreach ($deleteList as $deleteEntry) {
            if (substr($deleteEntry, -1, 1) == '/') {
                // this is a folder, recurse
                rmdirr($dangerFolder2.$deleteEntry);
            } else {
                unlink($dangerFolder2.$deleteEntry);
            }
        }
        if (is_file($dangerFolder2.'README.md')) {
            echo "There was a problem removing files in 'web/assets/jquery-file-upload/'. Please remove the following files and folders manually:<br />".PHP_EOL;
            echo "<ul>".PHP_EOL;
            foreach ($deleteList as $deleteEntry) {
                echo "<li>[chamilo folder]/web/assets/jquery-file-upload/".$deleteEntry."</li>".PHP_EOL;
            }
            echo "</ul>".PHP_EOL;
        }
    }
    if ($risk == 0) {
        echo "No dangerous file could be found. Your installation looks safe.<br />".PHP_EOL;
    }
}

/**
 * Delete files from the command line
 * @param string $folder The Chamilo root folder
 */
function deleteFilesSystem($folder) {
    global $deleteList;
    $dangerFolder = $folder.'app/Resources/public/assets/jquery-file-upload/';
    $dangerFolder2 = $folder.'web/assets/jquery-file-upload/';
    echo "Analyzing folder $folder...".PHP_EOL;
    if (is_dir($dangerFolder.'server/')) {
        echo "  Found $dangerFolder"."server/, cleaning...".PHP_EOL;
        foreach ($deleteList as $deleteEntry) {
            $recurse = '';
            if (substr($deleteEntry, -1, 1) == '/') {
                // this is a folder, recurse
                $recurse = '-r';
            }
            if (file_exists($dangerFolder.$deleteEntry)) {
                $return = system('rm '.$recurse.' '.$dangerFolder.$deleteEntry);
                if ($return === false) {
                    echo "  $dangerFolder$deleteEntry could not be deleted. Please delete manually.".PHP_EOL;
                }
            }
        }
    }
    if (is_dir($dangerFolder2.'server/')) {
        echo "  Found $dangerFolder2"."server/, deleting...".PHP_EOL;
        foreach ($deleteList as $deleteEntry) {
            $recurse = '';
            if (substr($deleteEntry, -1, 1) == '/') {
                // this is a folder, recurse
                $recurse = '-r';
            }
            if (file_exists($dangerFolder2.$deleteEntry)) {
                $return = system('rm '.$recurse.' '.$dangerFolder2.$deleteEntry);
                if ($return === false) {
                    echo "  $dangerFolder2$deleteEntry could not be deleted. Please delete manually.".PHP_EOL;
                }
            }
        }
    }
    echo "  Done with $folder".PHP_EOL;
}

