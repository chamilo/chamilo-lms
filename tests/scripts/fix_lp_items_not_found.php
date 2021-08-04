<?php

/* For licensing terms, see /license.txt */

/**
 * This script try to fix the lp items path for missing files
 * It's useful when the path field in c_lp_item table has a value like 'document/item_file.html'
 * Then this values is updated to 'document/learning_path/LP_DIRECTORY/item_file.html
 */

exit;

require_once '../../main/inc/global.inc.php';

/** @var The course id $courseId */
$courseId = 0;
/** @var The LP id $lpId */
$lpId = 0;

$tblCLp = Database::get_course_table(TABLE_LP_MAIN);
$tblCLpItem = Database::get_course_table(TABLE_LP_ITEM);

$course = api_get_course_info_by_id($courseId);
$lp = Database::fetch_assoc(
    Database::query("SELECT path FROM $tblCLp WHERE c_id = $courseId AND id = $lpId")
);
$items = Database::store_result(
    Database::query("SELECT id, c_id, lp_id, path FROM $tblCLpItem WHERE c_id = $courseId AND lp_id = $lpId"),
    'ASSOC'
);

$scormDir = api_get_path(SYS_COURSE_PATH) . $course['path'] . '/scorm/' . $lp['path'] . '/';

/** @var array $item */
foreach ($items as $item) {
    $fixedDirectory = "document/learning_path/{$lp['path']}/";

    $oldPath = $scormDir . $item['path'];
    $newPath = $scormDir . str_replace('document/', $fixedDirectory, $item['path']);

    if (!file_exists($oldPath) && file_exists($newPath)) {
        $sql = "
            UPDATE $tblCLpItem
            SET path = REPLACE(path, 'document/', '$fixedDirectory')
            WHERE c_id = {$item['c_id']} AND lp_id = {$item['lp_id']} AND id = {$item['id']}
        ";

        Database::query($sql);
        echo "Executing: $sql" . PHP_EOL;
    }
}
