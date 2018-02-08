<?php
/**
 * This script fixes use of id instead of iid for the learning path
 */

exit;
require_once '../../main/inc/global.inc.php';

/** @var The course id $courseId */
$courseId = 109;
/** @var The LP id $lpId */
$lpId = 2;
$lpId = 0;
$res = Database::select('id, title, code', Database::get_main_table(TABLE_MAIN_COURSE));
$tblCLp = Database::get_course_table(TABLE_LP_MAIN);
$tblCLpItem = Database::get_course_table(TABLE_LP_ITEM);

foreach ($res as $course) {
    if (!empty($courseId)) {
        if ($courseId != $course['id']) {
            continue;
        }
        $courseId = $course['id'];
        $sql = "SELECT * FROM $tblCLp WHERE c_id = $courseId";
        echo 'Select all lps';
        var_dump($sql);
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            while ($lpInfo = Database::fetch_array($result, 'ASSOC')) {
                $lpIid = $lpInfo['iid'];
                $oldId = $lpInfo['id'];

                if ($lpIid == $oldId) {
                    // Do nothing
                    continue;
                }

                if (!empty($lpId)) {
                    if ($lpId != $oldId) {
                        continue;
                    }
                }

                $sql = "SELECT * FROM $tblCLpItem 
                        WHERE c_id = $courseId AND lp_id = $oldId";
                var_dump($sql);
                $items = Database::store_result(Database::query($sql),'ASSOC');
                $itemList = [];
                foreach ($items as $item) {
                    $itemList[$item['id']] = $item['iid'];
                }
                $variablesToFix = [
                    'parent_item_id',
                    'next_item_id',
                    'prerequisite',
                    'previous_item_id'
                ];
                foreach ($items as $item) {
                    $itemIid = $item['iid'];
                    $itemId = $item['id'];

                    foreach ($variablesToFix as $variable) {
                        if (!empty($item[$variable]) && isset($itemList[$item[$variable]])) {
                            $newId = $itemList[$item[$variable]];
                            $sql = "UPDATE $tblCLpItem SET $variable = $newId WHERE iid = $itemIid";
                            Database::query($sql);
                            var_dump($sql);
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
                                $sql = "UPDATE $tblCLpItem SET path = $newDocumentId WHERE iid = $itemIid";
                                Database::query($sql);
                                var_dump($sql);
                            }
                        }
                    }

                    // c_lp_item_view
                    $sql = "UPDATE c_lp_item_view SET lp_item_id = $itemIid 
                            WHERE c_id = $courseId AND lp_item_id = $itemId";
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
                            var_dump($sql);
                        }
                    }

                    $sql = "UPDATE $tblCLpItem SET lp_id = $lpIid WHERE iid = $itemIid";
                    Database::query($sql);
                    var_dump($sql);
                }

                $sql = "UPDATE $tblCLp SET id = iid WHERE iid = $lpIid";
                Database::query($sql);
                var_dump($sql);

                $sql = "UPDATE c_lp_view SET lp_id = $lpIid WHERE c_id = $courseId AND lp_id = $oldId";
                Database::query($sql);
                var_dump($sql);

                $sql = "UPDATE c_forum_forum SET lp_id = $lpIid WHERE c_id = $courseId AND lp_id = $oldId";
                Database::query($sql);
                var_dump($sql);

                // Update track_exercises
                $sql = "UPDATE track_e_exercises SET orig_lp_id = $lpIid WHERE c_id = $courseId AND orig_lp_id = $oldId";
                Database::query($sql);
                var_dump($sql);
            }
        }
    }
}

echo 'finished';