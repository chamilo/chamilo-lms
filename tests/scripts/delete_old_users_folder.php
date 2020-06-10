<?php
/* For licensing terms, see /license.txt */
/**
 * Delete folders from users that have been deleted from the platform
 * and where the personal folder in app/upload/users/[num]/[user_id]/
 * was left behind.
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */

// Remove the following line to enable
exit;

if (PHP_SAPI != 'cli') {
    die('This script can only be executed from the command line');
}
require_once __DIR__.'/../../main/inc/global.inc.php';
$userFolder = $_configuration['root_sys'].'app/upload/users/';
$usersIds = [];
$sql1 = "SELECT id FROM user";
$res1 = Database::query($sql1);
while ($row1 = Database::fetch_array($res1)) {
    $usersIds[$row1['id']] = true;
}

$usersFolder = new DirectoryIterator($userFolder);

/** @var SplFileInfo $file */
foreach ($usersFolder as $file) {
    if (substr($file->getFilename(), 0, 1) == '.' || !$file->isDir()) {
        continue;
    }

    echo $file->getPathname().PHP_EOL;

    $numberFolder = new DirectoryIterator($file->getPathname());

    /** @var SplFileInfo $userFolder */
    foreach ($numberFolder as $userFolder) {
        if (substr($userFolder->getFilename(), 0, 1) == '.') {
            continue;
        }

        if (!empty($usersIds[$userFolder->getFilename()])) {
            echo "\tUser {$userFolder->getFilename()} exists".PHP_EOL;
        } else {
            echo "\tUser {$userFolder->getFilename()} does not exists."
                ." Folder exists but user has been deleted: {$userFolder->getPathname()}".PHP_EOL;
            UserManager::deleteUserFiles($userFolder->getFilename());
        }
    }
}
