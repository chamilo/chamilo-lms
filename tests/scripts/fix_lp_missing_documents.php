<?php

/* For licensing terms, see /license.txt */

/**
 * Set id = iid in c_document for a c_lp
 */

exit;
require_once '../../main/inc/global.inc.php';

/** @var The course id $courseId */
$courseId = 2;
/** @var The LP id $lpId */
$lpId = 3;

$tblCLp = Database::get_course_table(TABLE_LP_MAIN);
$tblCLpItem = Database::get_course_table(TABLE_LP_ITEM);

$course = api_get_course_info_by_id($courseId);
if (empty($course)) {
    exit;
}
$items = Database::store_result(
    Database::query("SELECT iid, id, c_id, lp_id, path FROM $tblCLpItem WHERE item_type = 'document' AND c_id = $courseId AND lp_id = $lpId"),
    'ASSOC'
);

if (empty($items)) {
    exit;
}

$lp = Database::fetch_assoc(
    Database::query("SELECT path FROM $tblCLp WHERE c_id = $courseId AND iid = $lpId")
);
/** @var array $item */
foreach ($items as $item) {
    $newPath = $item['path'];
    $TABLE_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
    $sql = "SELECT * FROM $TABLE_DOCUMENT
            WHERE iid = $newPath AND c_id = $courseId";
     $result = Database::query($sql);

    if (Database::num_rows($result)) {
        $row = Database::fetch_array($result, 'ASSOC');

        $sql = "
            UPDATE $TABLE_DOCUMENT
            SET id = iid
            WHERE c_id = $courseId and iid = $newPath
         ";
        //Database::query($sql);
        echo "Check: $sql" . PHP_EOL.'<br /><br/>';
    }

}
