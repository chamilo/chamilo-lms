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
    $sql = "SELECT * FROM $tblCLp WHERE c_id = $courseId AND iid <> id ORDER by iid";
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
                        WHERE c_id = $courseId AND (link = '$link' OR link ='$secondLink')";
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
                        //var_dump($sql);
                    }
                }

                if ($item['item_type'] == 'document' && !empty($item['path'])) {
                    $oldDocumentId = $item['path'];
                    $sql = "SELECT * FROM c_document WHERE c_id = $courseId AND id = $oldDocumentId";
                    $resultDocument = Database::query($sql);
                    if (Database::num_rows($resultDocument)) {
                        $document = Database::fetch_array($resultDocument, 'ASSOC');
                        $newDocumentId = $document['iid'];
                        if (!empty($newDocumentId)) {
                            $sql = "UPDATE $tblCLpItem SET path = $newDocumentId 
                                    WHERE iid = $itemIid AND c_id = $courseId";
                            Database::query($sql);
                            //var_dump($sql);
                        }
                    }
                }

                // c_lp_view
                $sql = "UPDATE c_lp_view SET last_item = $itemIid 
                        WHERE c_id = $courseId AND last_item = $itemId AND lp_id = $oldId";
                Database::query($sql);
                //var_dump($sql);

                // c_lp_item_view
                $sql = "UPDATE c_lp_item_view SET lp_item_id = $itemIid 
                        WHERE c_id = $courseId AND lp_item_id = $itemId ";
                Database::query($sql);
                //var_dump($sql);

                // Update track_exercises
                $sql = "UPDATE track_e_exercises SET orig_lp_item_id = $itemIid 
                        WHERE c_id = $courseId AND orig_lp_id = $oldId AND orig_lp_item_id = $itemId";
                Database::query($sql);
                //var_dump($sql);

                // c_forum_thread
                $sql = "UPDATE c_forum_thread SET lp_item_id = $itemIid 
                        WHERE c_id = $courseId AND lp_item_id = $itemId";
                Database::query($sql);
                //var_dump($sql);

                // orig_lp_item_view_id
                $sql = "SELECT * FROM c_lp_view
                        WHERE c_id = $courseId AND lp_id = $oldId";
                $itemViewList = Database::store_result(Database::query($sql),'ASSOC');
                if ($itemViewList) {
                    foreach ($itemViewList as $itemView) {
                        $userId = $itemView['user_id'];
                        $oldItemViewId = $itemView['id'];
                        $newItemView = $itemView['iid'];
                        if (empty($oldItemViewId)) {
                            continue;
                        }

                        $sql = "UPDATE track_e_exercises 
                                SET orig_lp_item_view_id = $newItemView 
                                WHERE 
                                  c_id = $courseId AND 
                                  orig_lp_id = $oldId AND 
                                  orig_lp_item_id = $itemIid AND 
                                  orig_lp_item_view_id = $oldItemViewId AND 
                                  exe_user_id = $userId                                       
                                  ";
                        Database::query($sql);
                        //var_dump($sql);
                    }
                }

                $sql = "UPDATE $tblCLpItem SET lp_id = $lpIid 
                        WHERE c_id = $courseId AND lp_id = $oldId AND id = $itemId";
                Database::query($sql);
                //var_dump($sql);

                $sql = "UPDATE $tblCLpItem SET id = iid 
                    WHERE c_id = $courseId AND lp_id = $oldId AND id = $itemId";
                Database::query($sql);
                //var_dump($sql);
            }

            $sql = "UPDATE c_lp_view SET lp_id = $lpIid WHERE c_id = $courseId AND lp_id = $oldId";
            Database::query($sql);
            //var_dump($sql);

            $sql = "UPDATE c_forum_forum SET lp_id = $lpIid WHERE c_id = $courseId AND lp_id = $oldId";
            Database::query($sql);
            //var_dump($sql);

            // Update track_exercises
            $sql = "UPDATE track_e_exercises SET orig_lp_id = $lpIid 
                    WHERE c_id = $courseId AND orig_lp_id = $oldId";
            Database::query($sql);
            //var_dump($sql);

            $sql = "UPDATE $tblCLp SET id = iid WHERE c_id = $courseId AND id = $oldId ";
            Database::query($sql);
            //var_dump($sql);
        }
    }
}

echo 'finished';
error_log('finished');
