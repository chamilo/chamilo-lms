<?php

/* For licensing terms, see /license.txt */

/**
 * 	@author Thomas Depraetere
 * 	@author Hugues Peeters
 * 	@author Christophe Gesche
 * 	@author Sebastien Piraux
 * 	@author Toon Keppens (Vi-Host.net)
 *
 * 	@package chamilo.tracking
 */
/**
 * Code
 */
// TODO: Is this file deprecated?

/* INIT SECTION */

$pathopen = isset($_REQUEST['pathopen']) ? $_REQUEST['pathopen'] : null;
// name of the language file that needs to be included
$language_file = "tracking";

require_once '../inc/global.inc.php';
//includes for SCORM and LP
require_once '../newscorm/learnpath.class.php';
require_once '../newscorm/learnpathItem.class.php';
require_once '../newscorm/scorm.class.php';
require_once '../newscorm/scormItem.class.php';

/* Constants and variables */

// regroup table names for maintenance purpose
$TABLETRACK_ACCESS = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
$TABLETRACK_LINKS = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
$TABLETRACK_DOWNLOADS = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
$TABLETRACK_ACCESS_2 = Database::get_statistic_table("track_e_access");
$TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

$TABLECOURSE_LINKS = Database::get_course_table(TABLE_LINK);
$tbl_learnpath_main = Database::get_course_table(TABLE_LP_MAIN);
$tbl_learnpath_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_learnpath_view = Database::get_course_table(TABLE_LP_VIEW);
$tbl_learnpath_item_view = Database::get_course_table(TABLE_LP_ITEM_VIEW);

$course_id = api_get_course_int_id();

$view = $_REQUEST['view'];

if ($view == "0000001")
    $nameTools = get_lang('SynthesisView');
if ($view == "1000000")
    $nameTools = get_lang('CourseStats');
if ($view == "0100000")
    $nameTools = get_lang('CourseAccess');
if ($view == "0010000")
    $nameTools = get_lang('ToolsAccess');
if ($view == "0001000")
    $nameTools = get_lang('LinksAccess');
if ($view == "0000100")
    $nameTools = get_lang('DocumentsAccess');
if ($view == "00000010")
    $nameTools = get_lang('ScormAccess');

$interbreadcrumb[] = array("url" => api_get_self() . "?view=0000000", "name" => get_lang('ToolName'));

include(api_get_path(LIBRARY_PATH) . "statsUtils.lib.inc.php");
include("../resourcelinker/resourcelinker.inc.php");

$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin || api_is_drh();

/* 	MAIN CODE */

$title[0] = get_lang('StatsOfCourse') . " : " . $_course['official_code'];

// check if uid is prof of this group

if ($is_allowedToTrack) {
    // show all : view must be equal to the sum of all view values (1024+512+...+64)
    // show none : less than the tiniest value
    /* echo "<div>
      [<a href='".api_get_self()."?view=1111111'>".get_lang('ShowAll')."</a>]
      [<a href='".api_get_self()."?view=0000000'>".get_lang('ShowNone')."</a>]
      </div><br>
      "; */

    if (!isset($view))
        $view = "0000000";


    /* 	Reporting */

    $tempView = $view;
    if ($view[6] == '1') {

        $tempView[6] = '0';

        // BEGIN users in this course
        $sql = "SELECT $TABLECOURSUSER.user_i, $table_user.lastname, $table_user.firstname
                    FROM $TABLECOURSUSER, $table_user
                    WHERE $TABLECOURSUSER.course_code = '" . $_cid . "' AND $TABLECOURSUSER.user_id = $table_user.user_id AND $TABLECOURSUSER.relation_type<>" . COURSE_RELATION_TYPE_RRHH . "
                    ORDER BY $table_user.lastname";
        $results = getManyResults3Col($sql);

        //BUGFIX: get visual code instead of real course code. Scormpaths use the visual code... (should be fixed in future versions)
        $sql = "SELECT visual_code FROM $TABLECOURSE WHERE code = '" . $_cid . "'";
        $_course['visual_code'] = getOneResult($sql);


        if (is_array($results)) {
            $line = '';
            $title_line = get_lang('Name') . ";" . get_lang('FirstAccess') . ";" . get_lang('LastAccess') . ";" . get_lang('Visited') . "\n";

            for ($j = 0; $j < count($results); $j++) {
                // BEGIN % visited
                // sum of all items (= multiple learningpaths + SCORM imported paths)
                $sql = "SELECT COUNT(DISTINCT(iv.lp_item_id)) FROM $tbl_learnpath_item_view iv " .
                        "INNER JOIN $tbl_learnpath_view v 
                        ON iv.lp_view_id = v.id " .
                        "WHERE
                        	v.c_id = $course_id AND
                        	iv.c_id = $course_id AND                        	 
                		v.user_id = " . $results[$j][0];
                $total_lpath_items = getOneResult($sql);

                // sum of all completed items (= multiple learningpaths + SCORM imported paths)
                $sql = "SELECT COUNT(DISTINCT(iv.lp_item_id)) " .
                        "FROM $tbl_learnpath_item_view iv " .
                        "INNER JOIN $tbl_learnpath_view v ON iv.lp_view_id = v.id " .
                        "WHERE 
                        	v.c_id = $course_id AND
                        	iv.c_id = $course_id AND
                        	v.user_id = " . $results[$j][0] . " " .
                        "AND (status = 'completed' OR status='passed')";
                $total_lpath_items_completed = getOneResult($sql);

                // calculation & bgcolor setting
                $lpath_pct_completed = empty($total_lpath_items) ? "-" : round(($total_lpath_items_completed / $total_lpath_items) * 100);

                // END % visited
                // BEGIN first/last access
                // first access
                $sql = "SELECT access_date FROM $TABLETRACK_ACCESS_2 WHERE access_user_id = '" . $results[$j][0] . "' AND access_cours_code = '" . $_course['official_code'] . "' AND access_tool = 'learnpath' AND access_session_id = '" . api_get_session_id() . "' ORDER BY access_id ASC LIMIT 1";
                $first_access = getOneResult($sql);
                $first_access = empty($first_access) ? "-" : date('d.m.y', strtotime($first_access));

                // last access
                $sql = "SELECT access_date FROM $TABLETRACK_ACCESS WHERE access_user_id = '" . $results[$j][0] . "' AND access_cours_code = '" . $_course['official_code'] . "' AND access_tool = 'learnpath'";
                $last_access = getOneResult($sql);
                $last_access = empty($last_access) ? "-" : date('d.m.y', strtotime($last_access));
                // END first/last access
                // BEGIN presentation of data
                $line .= $results[$j][1] . " " . $results[$j][2] . ";" . $first_access . ";" . $last_access . ";" . $lpath_pct_completed . "\n";

                // END presentation of data
            }
        } else {
            $line = get_lang('NoResult') . "\n";
        }
    }



    /* 	Main */

    $tempView = $view;
    if ($view[0] == '1') {
        $title[1] = $nameTools;
        $tempView[0] = '0';

        $sql = "SELECT count(*)
                    FROM $TABLECOURSUSER
                    WHERE course_code = '" . $_cid . "' AND relation_type<>" . COURSE_RELATION_TYPE_RRHH . "";
        $count = getOneResult($sql);

        $title_line = get_lang('CountUsers') . " ; " . $count . "\n";
    }


    /* 	Access to this course */
    $tempView = $view;
    if ($view[1] == '1') {

        $tempView[1] = '0';


        $title[1] = get_lang('ConnectionsToThisCourse');
        $title_line = '';
        $line = '';

        //Total
        $sql = "SELECT count(*)
                    FROM $TABLETRACK_ACCESS
                    WHERE access_cours_code = '" . $_cid . "'
                        AND access_tool IS NULL";
        $count = getOneResult($sql);

        $line .= get_lang('CountToolAccess') . " ; " . $count . "\n";

        // last 31 days
        $sql = "SELECT count(*)
                    FROM $TABLETRACK_ACCESS
                    WHERE access_cours_code = '$_cid'
                        AND (access_date > DATE_ADD(CURDATE(), INTERVAL -31 DAY))
                        AND access_tool IS NULL";
        $count = getOneResult($sql);

        $line .= get_lang('Last31days') . " ; " . $count . "\n";

        // last 7 days
        $sql = "SELECT count(*)
                    FROM $TABLETRACK_ACCESS
                    WHERE access_cours_code = '$_cid'
                        AND (access_date > DATE_ADD(CURDATE(), INTERVAL -7 DAY))
                        AND access_tool IS NULL";
        $count = getOneResult($sql);

        $line .= get_lang('Last7days') . " ; " . $count . "\n";

        // today
        $sql = "SELECT count(*)
                    FROM $TABLETRACK_ACCESS
                    WHERE access_cours_code = '$_cid'
                        AND ( access_date > CURDATE() )
                        AND access_tool IS NULL";
        $count = getOneResult($sql);
        $line .= get_lang('Thisday') . " ; " . $count . "\n";
    }



    /* 	Tools */
    $tempView = $view;
    if ($view[2] == '1') {

        $tempView[2] = '0';

        $title[1] = $nameTools;
        $line = '';

        $title_line = get_lang('ToolTitleToolnameColumn') . ";" . get_lang('ToolTitleUsersColumn') . ";" . get_lang('ToolTitleCountColumn') . "\n";

        $sql = "SELECT access_tool, COUNT(DISTINCT access_user_id),count( access_tool )
                FROM $TABLETRACK_ACCESS
                WHERE access_tool IS NOT NULL
                    AND access_cours_code = '$_cid'
                GROUP BY access_tool";

        $results = getManyResults3Col($sql);

        if (is_array($results)) {
            for ($j = 0; $j < count($results); $j++) {
                $line .= $results[$j][0] . "/" . get_lang($results[$j][0]) . ";" . $results[$j][1] . ";" . $results[$j][2] . "\n";
            }
        } else {
            $line = get_lang('NoResult') . "\n";
        }
    }


    /* 	Links */

    $tempView = $view;
    if ($view[3] == '1') {

        $tempView[3] = '0';

        $sql = "SELECT cl.title, cl.url,count(DISTINCT sl.links_user_id), count(cl.title)
                    FROM $TABLETRACK_LINKS AS sl, $TABLECOURSE_LINKS AS cl
                    WHERE
                    	cl.c_id = $course_id AND
                    	sl.links_link_id = cl.id AND 
                    	sl.links_cours_id = '$_cid'
                    GROUP BY cl.title, cl.url";

        $results = getManyResultsXCol($sql, 4);

        $title[1] = $nameTools;
        $line = '';
        $title_line = get_lang('LinksTitleLinkColumn') . ";" . get_lang('LinksTitleUsersColumn') . ";" . get_lang('LinksTitleCountColumn') . "\n";

        if (is_array($results)) {
            for ($j = 0; $j < count($results); $j++) {
                $line .= $results[$j][1] . "'>" . $results[$j][0] . ";" . $results[$j][2] . ";" . $results[$j][3] . "\n";
            }
        } else {
            $line = get_lang('NoResult') . "\n";
        }
    }


    /* 	Documents */

    $tempView = $view;
    if ($view[4] == '1') {

        $tempView[4] = '0';

        $sql = "SELECT down_doc_path, COUNT(DISTINCT down_user_id), COUNT(down_doc_path)
                    FROM $TABLETRACK_DOWNLOADS
                    WHERE down_cours_id = '$_cid'
                    GROUP BY down_doc_path";

        $results = getManyResults3Col($sql);

        $title[1] = $nameTools;
        $line = '';
        $title_line = get_lang('DocumentsTitleDocumentColumn') . ";" . get_lang('DocumentsTitleUsersColumn') . ";" . get_lang('DocumentsTitleCountColumn') . "\n";
        if (is_array($results)) {
            for ($j = 0; $j < count($results); $j++) {
                $line .= $results[$j][0] . ";" . $results[$j][1] . ";" . $results[$j][2] . "\n";
            }
        } else {
            $line = get_lang('NoResult') . "\n";
        }
    }


    /* 	Scorm contents and Learning Path */
    $tempView = $view;
    if ($view[5] == '1') {

        $tempView[5] = '0';

        $sql = "SELECT id, name FROM $tbl_learnpath_main WHERE c_id = $course_id ";
        $result = Database::query($sql);

        $ar = Database::fetch_array($result);

        $title[1] = $nameTools;
        $line = '';
        $title_line = get_lang('ScormContentColumn');

        $scormcontopen = $_REQUEST["scormcontopen"];
        $scormstudentopen = $_REQUEST["scormstudentopen"];

        if (is_array($ar)) {

            while ($ar['id'] != '') {
                $lp_title = stripslashes($ar['name']);
                //echo "<a href='".api_get_self()."?view=".$view."&scormcontopen=".$ar['id']."' class='specialLink'>$lp_title</a>";
                if ($ar['id'] == $scormcontopen) { //have to list the students here
                    $contentId = $ar['id'];
                    $sql2 = "SELECT u.user_id, u.lastname, u.firstname " .
                            "FROM  $tbl_learnpath_view sd " .
                            "INNER JOIN $table_user u " .
                            "ON u.user_id = sd.user_id " .
                            "WHERE sd.c_id = $course_id AND sd.lp_id=$contentId group by u.user_id";
                    //error_log($sql2,0);
                    $result2 = Database::query($sql2);

                    if (Database::num_rows($result2) > 0) {


                        $ar2 = Database::fetch_array($result2);
                        while ($ar2 != '') {

                            if (isset($_REQUEST["scormstudentopen"]) && $ar2['user_id'] == $scormstudentopen) {
                                $line .= $ar['id'] . " " . $ar2['user_id'] . " " . api_get_person_name($ar2['firstname'], $ar2['lastname']);
                            } else {
                                $line .= $ar['id'] . " " . $ar2['user_id'] . " " . api_get_person_name($ar2['firstname'], $ar2['lastname']);
                            }


                            if ($ar2['user_id'] == $scormstudentopen) { //have to list the student's results
                                $studentId = $ar2['user_id'];
                                $sql3 = "SELECT iv.status, iv.score, i.title, iv.total_time " .
                                        "FROM $tbl_learnpath_item i " .
                                        "INNER JOIN $tbl_learnpath_item_view iv ON i.id=iv.lp_item_id " .
                                        "INNER JOIN $tbl_learnpath_view v ON iv.lp_view_id=v.id " .
                                        "WHERE 	i.c_id = $course_id AND 
                                        		iv.c_id = $course_id AND
                                        		v.c_id = $course_id AND
                                				v.user_id=$studentId and v.lp_id=$contentId ORDER BY v.id, i.id";
                                $result3 = Database::query($sql3);
                                $ar3 = Database::fetch_array($result3);
                                $title_line .= get_lang('ScormTitleColumn') . ";" . get_lang('ScormStatusColumn') . ";" . get_lang('ScormScoreColumn') . ";" . get_lang('ScormTimeColumn');
                                while ($ar3['status'] != '') {
                                    require_once '../newscorm/learnpathItem.class.php';
                                    $time = learnpathItem::get_scorm_time('php', $ar3['total_time']);
                                    $line .= $title . ";" . $ar3['status'] . ";" . $ar3['score'] . ";" . $time;
                                    $ar3 = Database::fetch_array($result3);
                                }
                            }
                            $line .= "\n";
                            $ar2 = Database::fetch_array($result2);
                        }

                        $title_line .= "\n";
                    }
                }

                $ar = Database::fetch_array($result);
            }
        }
    }

    /*
     * Export to a CSV file
     * Force the browser to save the file instead of opening it.
     */
    $len = strlen($title_line . $line);
    header('Content-type: application/octet-stream');
    //header('Content-Type: application/force-download');
    header('Content-length: ' . $len);
    $filename = api_html_entity_decode(str_replace(":", "", str_replace(" ", "_", $title[0] . '_' . $title[1] . '.csv')));
    $filename = replace_dangerous_char($filename);
    if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT'])) {
        header('Content-Disposition: filename= ' . $filename);
    } else {
        header('Content-Disposition: attachment; filename= ' . $filename);
    }
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        header('Pragma: ');
        header('Cache-Control: ');
        header('Cache-Control: public'); // IE cannot download from sessions without a cache
    }
    header('Content-Description: ' . $filename);
    header('Content-transfer-encoding: binary');

    echo api_html_entity_decode($title_line, ENT_COMPAT);
    echo api_html_entity_decode($line, ENT_COMPAT);
    exit;
} else {
    api_not_allowed();
}
