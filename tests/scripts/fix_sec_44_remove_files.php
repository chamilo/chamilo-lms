<?php

/* For licensing terms, see /license.txt */

/**
 * This script fixes a file-upload vulnerability introduced in Chamilo 1.11.12
 * in a way that cannot be fixed through simple update.
 * This refers to Issue 44 described at
 * https://support.chamilo.org/projects/chamilo-18/wiki/Security_issues
 * This script should be run on any Chamilo installation having been updated
 * through Git or through the 1.11.12 installer between early May 2020 and
 * late October 2020.
 */

use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__.'/../../vendor/autoload.php';

exit;

if (PHP_SAPI != 'cli') {
    exit;
}

$dangerousItems = [
    'jquery-file-upload/server',
    'jquery-file-upload/.github',
    'jquery-file-upload/cors',
    'jquery-file-upload/test',
    'jquery-file-upload/wdio',
    'jquery-file-upload/js/demo.js',
    'jquery-file-upload/.gitignore',
    'jquery-file-upload/README.md',
    'jquery-file-upload/SECURITY.md',
    'jquery-file-upload/VULNERABILITIES.md',
    'jquery-file-upload/docker-composer.yml',
    'ckeditor/samples',
    'select2/docs',
];

$acceptableItemNames = [
    'LICENSE.txt',
    'LICENSE.md',
    'LICENSE',
];

require_once __DIR__.'/../../app/config/configuration.php';

$chamiloFolder = $_configuration['root_sys'];

$sysAssetsDir = $chamiloFolder.'app/Resources/public/assets';
$webAssetsDir = $chamiloFolder.'web/assets';

$emptyIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sysAssetsDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

$filesToRemove = [];

/** @var \SplFileObject $item */
foreach ($emptyIterator as $item) {
    if (!$item->isFile()) {
        continue;
    }

    foreach ($acceptableItemNames as $acceptableItemName) {
        if (strtolower($item->getFilename()) === strtolower($acceptableItemName)) {
            continue 2;
        }
    }

    foreach ($dangerousItems as $dangerousItem) {
        if (strpos($item->getPathname(), "$sysAssetsDir/$dangerousItem") !== false) {
            $filesToRemove[] = $item->getPathname();
        }
    }
}

$fs = new Filesystem();

foreach ($filesToRemove as $fileToRemove) {
    echo "Removing: $fileToRemove".PHP_EOL;

    $fs->remove($fileToRemove);
}

echo PHP_EOL;
echo "Mirroring web directory: ScriptHandler::dumpCssFiles".PHP_EOL;

$fs->mirror($sysAssetsDir, $webAssetsDir, null, ['override' => true, 'delete' => true]);

echo PHP_EOL;
echo 'Done.'.PHP_EOL;
