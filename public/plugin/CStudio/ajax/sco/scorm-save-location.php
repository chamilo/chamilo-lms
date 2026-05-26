<?php

declare(strict_types=1);

/**
 * This file contains the functions used by the Chamidoc and Chamilo lms.
 *
 * @version 18/05/2024
 */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

use ChamiloSession as Session;

require_once __DIR__.'/../../0_dal/dal.global_lib.php';

require_once __DIR__.'/../../0_dal/dal.vdatabase.php';

$VDB = new VirtualDatabase();

if (isset($_GET['id']) || isset($_GET['idteach'])) {
    if (!$VDB->w_api_is_anonymous()) {
        $tableLPVIEW = $VDB->get_course_table(TABLE_LP_VIEW);

        $session_condition = api_get_session_condition(
            api_get_session_id(),
            true,
            false
        );
        $course_id = api_get_course_int_id();

        $l_id = 0;
        $loc = 0;
        $pourcIntVal = 0;
        if (isset($_GET['pour'])) {
            $pourcIntVal = (int) $_GET['pour'];
        }
        if (isset($_GET['idteach'])) {
            $tid = (int) $_GET['idteach'];
            $sqlTeach = "SELECT lp_id FROM plugin_oel_tools_teachdoc WHERE id = $tid ";

            $l_id = (int) $VDB->get_value_by_query($sqlTeach, 'lp_id');
        } else {
            $l_id = (int) $_GET['id'];
            if (isset($_GET['loc'])) {
                $loc = (int) $_GET['loc'];
            }
        }

        $itemViewId = 0;
        $sqlT = " SELECT iid FROM $tableLPVIEW ";
        $sqlT .= " WHERE lp_id = $l_id ";
        $sqlT .= ' AND c_id = '.$course_id;
        $sqlT .= ' AND user_id = '.api_get_user_id().' '.$session_condition;

        $itemViewId = (int) $VDB->get_value_by_query($sqlT, 'iid');

        if ($itemViewId > 0) {
            $tableLPITEMVIEW = $VDB->get_course_table(TABLE_LP_ITEM_VIEW);

            if ($loc > 0 || $pourcIntVal > 0) {
                echo ' ok 1';

                $sql = "UPDATE $tableLPITEMVIEW SET
                lesson_location = '".$loc."'
                WHERE lp_view_id = ".$itemViewId."
                    AND lesson_location != ''
                    AND lesson_location REGEXP '^-?[0-9]+$'
                    AND lesson_location < $loc";
                $VDB->query($sql);

                if ($pourcIntVal < 75) {
                    $sql = "UPDATE $tableLPITEMVIEW SET status = 'incomplete' WHERE lp_view_id = ".$itemViewId.';';
                    $VDB->query($sql);
                }

                if ($pourcIntVal > 0 && $pourcIntVal < 101) {
                    $sqlP = " UPDATE c_lp_view SET progress = $pourcIntVal ";
                    $sqlP .= " WHERE progress < $pourcIntVal AND c_lp_view.iid = ".$itemViewId.';';
                    $VDB->query($sqlP);
                }

                echo 'ok';
            } else {
                if (isset($_GET['idteach'])) {
                    // 'incomplete' 'not attempted'
                    $sql = "UPDATE $tableLPITEMVIEW SET
                    lesson_location = '0',
                    score = 0,
                    status = 'incomplete'
                    WHERE lp_view_id = ".$itemViewId.' ;';
                    // echo $sql;
                    $VDB->query($sql);

                    $sqlP = ' UPDATE c_lp_view SET progress = 0 WHERE c_lp_view.iid = '.$itemViewId.';';
                    $VDB->query($sqlP);

                    Session::erase('lpobject');
                }
            }
        }
    }
}
