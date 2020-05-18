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
$list = scandir($userFolder);
foreach ($list as $directory) {
    $directory = trim($directory);
    if (substr($directory, 0, 1) == '.') {
        continue;
    }
    if (intval($directory) != $directory) {
        continue;
    }
    echo $userFolder.$directory."\n";
    $subList = scandir($userFolder.'/'.$directory);
    foreach ($subList as $subDirectory) {
        $subDirectory = trim($subDirectory);
        if (substr($subDirectory, 0, 1) == '.') {
            continue;
        }
        if ($subDirectory == 'my_files') {
            continue;
        }
        $fullDirectory = $directory.'/'.$subDirectory;
        if (!empty($usersIds[$subDirectory])) {
            echo "User ".$subDirectory." exists\n";
        } else {
            //echo "User ".$directory." does not exists\n";
            $thisUserFolder = $userFolder.$fullDirectory;
            //echo "Folder exists but user has been deleted: ".$thisUserFolder."\n";
            echo "rm -rf $thisUserFolder\n";
            exec("rm -rf ".$thisUserFolder);
        }
    }
}

