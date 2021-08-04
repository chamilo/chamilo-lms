<?php

/* For licensing terms, see /license.txt */

/**
 * This script fixes use of id instead of iid for the learning path
 */

exit;

require_once '../../main/inc/global.inc.php';

/** @var int $courseId */
$onlyCourseId = 0;
/** @var int $lpId lp id */
$lpId = 0;

$courses = Database::select('id, title, code', Database::get_main_table(TABLE_MAIN_COURSE));
$tblCLp = Database::get_course_table(TABLE_LP_MAIN);
$tblCLpItem = Database::get_course_table(TABLE_LP_ITEM);
$toolTable = Database::get_course_table(TABLE_TOOL_LIST);

// Start custom changes
// Delete inconsistencies from old base
$sql = 'DELETE FROM c_lp_item_view WHERE c_id = 0';
Database::query($sql);

var_dump($sql);
error_log($sql);

// This is a custom change, probably you don't needed it in your script (removing an empty attempt)
$sql = 'DELETE FROM c_lp_item_view WHERE lp_view_id = 18 and c_id = 4';
Database::query($sql);
var_dump($sql);
error_log($sql);

$sql = 'DELETE FROM c_lp_view where id = 18 and c_id = 4';
Database::query($sql);
var_dump($sql);
error_log($sql);

///update c_lp_item_view set status = 'not attempted', suspend_data = null where iid = 2148;

// end custom changes

$sessions = Database::select('id', Database::get_main_table(TABLE_MAIN_SESSION));
if (!empty($sessions)) {
    $sessions = array_column($sessions, 'id');
    // Add session_id = 0
    $sessions[] = 0;
} else {
    $sessions = [0];
}

foreach ($courses as $course) {
    if (!empty($onlyCourseId)) {
        if ($onlyCourseId != $course['id']) {
            continue;
        }
    }
    $courseId = $course['id'];
    $sql = "SELECT * FROM $tblCLp WHERE c_id = $courseId ORDER by iid";
    echo 'Select all lps';
    var_dump($sql);
    error_log($sql);
    $result = Database::query($sql);

    $myOnlyLpList = [];
    if (Database::num_rows($result)) {
        while ($lpInfo = Database::fetch_array($result, 'ASSOC')) {
            $lpIid = $lpInfo['iid'];
            $oldId = $lpInfo['id'];
            $sql = "SELECT * FROM $tblCLpItem 
                    WHERE c_id = $courseId AND lp_id = $oldId ORDER by iid";
            //echo "<h3>$sql</h3>";
            //echo "New lp.iid $lpIid / old lp.id $oldId";
            $items = Database::store_result(Database::query($sql),'ASSOC');
            $lpInfo['lp_list'] = $items;
            $myOnlyLpList[] = $lpInfo;
        }
    }

    if (!empty($myOnlyLpList)) {
        foreach ($myOnlyLpList as $lpInfo) {
            $lpIid = $lpInfo['iid'];
            $oldId = $lpInfo['id'];

            if (!empty($lpId)) {
                if ($lpId != $oldId) {
                    continue;
                }
            }

            if (empty($lpInfo['lp_list'])) {
                continue;
            }

            $items = $lpInfo['lp_list'];

            $itemList = [];
            foreach ($items as $subItem) {
                $itemList[$subItem['id']] = $subItem['iid'];
            }

            $variablesToFix = [
                'parent_item_id',
                'next_item_id',
                'prerequisite',
                'previous_item_id'
            ];

            foreach ($sessions as $sessionId) {
                $correctLink = "lp/lp_controller.php?action=view&lp_id=$lpIid&id_session=$sessionId";
                $link = "newscorm/lp_controller.php?action=view&lp_id=$oldId&id_session=$sessionId";
                $secondLink = "lp/lp_controller.php?action=view&lp_id=$oldId&id_session=$sessionId";

                $sql = "UPDATE $toolTable 
                        SET link = '$correctLink'
                        WHERE 
                              c_id = $courseId AND 
                              (link = '$link' OR link ='$secondLink' )";
                Database::query($sql);
            }

            foreach ($items as $item) {
                $itemIid = $item['iid'];
                $itemId = $item['id'];

                foreach ($variablesToFix as $variable) {
                    if (!empty($item[$variable]) && isset($itemList[$item[$variable]])) {
                        $newId = $itemList[$item[$variable]];
                        $sql = "UPDATE $tblCLpItem SET $variable = $newId 
                                WHERE iid = $itemIid AND c_id = $courseId AND lp_id = $oldId";
                        Database::query($sql);
                        var_dump($sql);
                    }
                }

                // c_lp_view
                $sql = "UPDATE c_lp_view SET last_item = $itemIid 
                        WHERE c_id = $courseId AND last_item = $itemId AND lp_id = $oldId";
                Database::query($sql);
                var_dump($sql);

                // c_lp_item_view
                $sql = "UPDATE c_lp_item_view SET lp_item_id = $itemIid 
                        WHERE c_id = $courseId AND lp_item_id = $itemId ";
                Database::query($sql);
                var_dump($sql);

                // Update track_exercises
                $sql = "UPDATE track_e_exercises SET orig_lp_item_id = $itemIid 
                        WHERE c_id = $courseId AND orig_lp_id = $oldId AND orig_lp_item_id = $itemId";
                Database::query($sql);
                var_dump($sql);

                // c_forum_thread
                $sql = "UPDATE c_forum_thread SET lp_item_id = $itemIid 
                        WHERE c_id = $courseId AND lp_item_id = $itemId";
                Database::query($sql);
                var_dump($sql);

                // orig_lp_item_view_id
                $sql = "SELECT * FROM c_lp_view
                        WHERE c_id = $courseId AND lp_id = $oldId AND id <> iid";
                error_log($sql);

                $viewList = Database::store_result(Database::query($sql),'ASSOC');
                if ($viewList) {
                    error_log("c_lp_view list: ".count(    $viewList));
                    foreach ($viewList as $view) {
                        $oldViewId = $view['id'];
                        $newViewId = $prefixViewId = $view['iid'];
                        $userId = $view['user_id'];

                        if (empty($oldViewId)) {
                            continue;
                        }

                        $view['iid'] = null;

                        // Create new c_lp_view to avoid conflicts
                        $newViewId = Database::insert('c_lp_view', $view);
                        var_dump($newViewId);
                        if (empty($newViewId)) {
                            continue;
                        }
                        $sql = "UPDATE c_lp_view SET id = iid WHERE iid = $newViewId";
                        Database::query($sql);

                        // Delete old c_lp_view
                        $sql = "DELETE FROM c_lp_view WHERE id = $oldViewId AND iid = $prefixViewId ";
                        Database::query($sql);

                        $sql = "UPDATE track_e_exercises 
                                SET orig_lp_item_view_id = $newViewId 
                                WHERE 
                                  c_id = $courseId AND 
                                  orig_lp_id = $oldId AND 
                                  orig_lp_item_id = $itemIid AND 
                                  orig_lp_item_view_id = $oldViewId AND 
                                  exe_user_id = $userId                                       
                                  ";
                        Database::query($sql);
                        var_dump($sql);

                        $sql = "SELECT * FROM c_lp_item_view WHERE lp_view_id = $oldViewId AND c_id = $courseId ";
                        error_log($sql);
                        $list = Database::store_result(Database::query($sql),'ASSOC');

                        if (!empty($list)) {
                            foreach ($list as $itemView) {
                                $itemView['lp_view_id'] = $newViewId;
                                $itemView['iid'] = null;
                                //var_dump($itemView);
                                $itemViewId = Database::insert('c_lp_item_view', $itemView);
                                if ($itemViewId) {
                                    $sql = "UPDATE c_lp_item_view SET id = iid WHERE iid = $itemViewId";
                                    var_dump($sql);
                                    Database::query($sql);
                                }
                            }

                            $sql = "DELETE FROM c_lp_item_view WHERE lp_view_id = $oldViewId AND c_id = $courseId";
                            Database::query($sql);
                            var_dump($sql);
                        }

                        /*$sql = "UPDATE c_lp_item_view
                                SET lp_view_id = $newViewId
                                WHERE
                                    lp_view_id = $oldViewId AND
                                    lp_item_id = $itemIid AND
                                    c_id = $courseId
                                  ";
                        Database::query($sql);*/

                        /*$sql = "UPDATE c_lp_view SET id = iid
                                WHERE id = $oldViewId ";
                        Database::query($sql);*/
                    }
                }

                $sql = "UPDATE $tblCLpItem SET lp_id = $lpIid 
                        WHERE c_id = $courseId AND lp_id = $oldId AND id = $itemId";
                Database::query($sql);
                var_dump($sql);

                $sql = "UPDATE $tblCLpItem SET id = iid 
                    WHERE c_id = $courseId AND lp_id = $oldId AND id = $itemId";
                Database::query($sql);
                var_dump($sql);
            }

            $sql = "UPDATE c_lp_view SET lp_id = $lpIid WHERE c_id = $courseId AND lp_id = $oldId";
            Database::query($sql);
            var_dump($sql);

            $sql = "UPDATE c_forum_forum SET lp_id = $lpIid WHERE c_id = $courseId AND lp_id = $oldId";
            Database::query($sql);
            var_dump($sql);

            // Update track_exercises
            $sql = "UPDATE track_e_exercises SET orig_lp_id = $lpIid 
                    WHERE c_id = $courseId AND orig_lp_id = $oldId";
            Database::query($sql);
            var_dump($sql);

            $sql = "UPDATE $tblCLp SET id = iid WHERE c_id = $courseId AND id = $oldId ";
            Database::query($sql);
            var_dump($sql);
        }
    }
}

echo 'finished';
error_log('finished');
