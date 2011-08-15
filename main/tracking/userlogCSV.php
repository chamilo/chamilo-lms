<?php
/* For licensing terms, see /license.txt */

// TODO: Is this file deprecated?

/**
 * @package chamilo.tracking
 * @todo clean code - structure is unclear and difficult to modify
 */
/**
 * Code
 */

/*	INIT SECTION */

$uInfo = $_REQUEST['uInfo'];
$view = $_REQUEST['view'];
// name of the language file that needs to be included
$language_file = 'tracking';

require_once '../inc/global.inc.php';

// Roles and rights system
$user_id = api_get_user_id();
$course_id = api_get_course_id();

/*
$role_id = RolesRights::get_local_user_role_id($user_id, $course_id);
$location_id = RolesRights::get_course_tool_location_id($course_id, TOOL_TRACKING);
$is_allowed = RolesRights::is_allowed_which_rights($role_id, $location_id);

//block users without view right
RolesRights::protect_location($role_id, $location_id);
*/
//YW Hack security to quick fix RolesRights bug
$is_allowed = true;

/* Libraries */

require_once api_get_path(LIBRARY_PATH).'statsUtils.lib.inc.php';
require_once api_get_path(SYS_CODE_PATH).'resourcelinker/resourcelinker.inc.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php';

/* Header */

/*
$interbreadcrumb[]= array ("url"=>"../group/group.php", "name"=> get_lang('BredCrumpGroups'));
$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=$_gid", "name"=> get_lang('BredCrumpGroupSpace'));
*/

if($uInfo)
{
    $interbreadcrumb[]= array ("url"=>"../user/userInfo.php?uInfo=$uInfo", "name"=> get_lang('BredCrumpUsers'));
}

$nameTools = get_lang('ToolName');

/*	Constants and variables */

$is_allowedToTrack = $is_courseAdmin;
$is_course_member = CourseManager::is_user_subscribed_in_real_or_linked_course($user_id, $course_id);

// Database Table Definitions
$TABLECOURSUSER	        	= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLEUSER	        		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_course_user 	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_session 				= Database::get_main_table(TABLE_MAIN_SESSION);

$TABLECOURSE_GROUPSUSER 	= Database::get_course_table(TABLE_GROUP_USER);


if(api_get_setting('use_session_mode') == "true") {
    $sql = "SELECT 1
            FROM $tbl_session_course_user AS session_course_user
            INNER JOIN $tbl_session AS session
                ON session_course_user.id_session = session.id
                AND ((date_start<=NOW()
                AND date_end>=NOW())
                OR (date_start='0000-00-00' AND date_end='0000-00-00'))
            WHERE id_session='".$_SESSION['id_session']."' AND course_code='$_cid'";
    //echo $sql;
    $result=Database::query($sql);
    if(!Database::num_rows($result)){
        $disabled = true;
    }
}

$tbl_learnpath_main = Database::get_course_table(TABLE_LP_MAIN);
$tbl_learnpath_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_learnpath_view = Database::get_course_table(TABLE_LP_VIEW);
$tbl_learnpath_item_view = Database::get_course_table(TABLE_LP_ITEM_VIEW);

$documentPath=api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

// The variables for the days and the months
$DaysShort = api_get_week_days_short();
$DaysLong = api_get_week_days_long();
$MonthsLong = api_get_months_long();
$MonthsShort = api_get_months_short();

//$is_allowedToTrack = $is_groupTutor; // allowed to track only user of one group
//$is_allowedToTrackEverybodyInCourse = $is_allowed[EDIT_RIGHT]; // allowed to track all students in course
//YW hack security to fix RolesRights bug
$is_allowedToTrack = true; // allowed to track only user of one group
$is_allowedToTrackEverybodyInCourse = $is_allowedToTrack; // allowed to track all students in course

/*	MAIN SECTION */

$title[0]='';
$title[1]='';
$line='';
$title_line='';

// check if uid is tutor of this group
if( ( $is_allowedToTrack || $is_allowedToTrackEverybodyInCourse))
{
    if(!$uInfo && !isset($uInfo) )
    {
        /*
        *		Display list of user of this group
         */

        if( $is_allowedToTrackEverybodyInCourse )
        {
            // if user can track everybody : list user of course
            if(api_get_setting('use_session_mode')) {
                $sql = "SELECT count(user_id)
                            FROM $TABLECOURSUSER
                            WHERE course_code = '$_cid' AND relation_type<>".COURSE_RELATION_TYPE_RRHH."";
            }
            else {
                $sql = "SELECT count(id_user)
                            FROM $tbl_session_course_user
                            WHERE course_code = '$_cid'";
            }
        }
        else
        {
            // if user can only track one group : list users of this group
            $sql = "SELECT count(user)
                        FROM $TABLECOURSE_GROUPSUSER
                        WHERE group_id = '$_gid'";
        }
        $userGroupNb = getOneResult($sql);
        $step = 25; // number of student per page
        if ($userGroupNb > $step)
        {
            if(!isset($offset))
            {
                    $offset=0;
            }

            $next     = $offset + $step;
            $previous = $offset - $step;

            $navLink = "";

            if ($previous >= 0)
            {
            }


            if ($next < $userGroupNb)
            {
            }

        }
        else
        {
            $offset = 0;
        }

        echo $navLink;

    if (!settype($offset, 'integer') || !settype($step, 'integer')) die('Offset or step variables are not integers.');	//sanity check of integer vars
        if( $is_allowedToTrackEverybodyInCourse )
        {
            // list of users in this course
            $sql = "SELECT u.user_id, u.firstname,u.lastname
                        FROM $TABLECOURSUSER cu , $TABLEUSER u
                        WHERE cu.user_id = u.user_id AND cu.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                            AND cu.course_code = '$_cid'
                        LIMIT $offset,$step";
        }
        else
        {
            // list of users of this group
            $sql = "SELECT u.user_id, u.firstname,u.lastname
                        FROM $TABLECOURSE_GROUPSUSER gu , $TABLEUSER u
                        WHERE gu.user_id = u.user_id
                            AND gu.group_id = '$_gid'
                        LIMIT $offset,$step";
        }
        $list_users = getManyResults3Col($sql);
        for($i = 0 ; $i < sizeof($list_users) ; $i++)
        {
    }

    }
    else // if uInfo is set
    {
        /*
        *		Informations about student uInfo
         */
        // these checks exists for security reasons, neither a prof nor a tutor can see statistics of a user from
        // another course, or group
        if( $is_allowedToTrackEverybodyInCourse )
        {
            // check if user is in this course
            $tracking_is_accepted = $is_course_member;
            $tracked_user_info = Database::get_user_info_from_id($uInfo);
            $title[0]=$tracked_user_info[1].'_'.$tracked_user_info[2];
        }
        else
        {
            // check if user is in the group of this tutor
            $sql = "SELECT u.firstname,u.lastname, u.email
                        FROM $TABLECOURSE_GROUPSUSER gu , $TABLEUSER u
                        WHERE gu.user_id = u.user_id
                            AND gu.group_id = '$_gid'
                            AND u.user_id = '$uInfo'";
            $query = Database::query($sql);
            $tracked_user_info = @Database::fetch_assoc($query);
            if(is_array($tracked_user_info)) $tracking_is_accepted = true;

               $title[0] = $tracked_user_info['firstname'].'_'.$tracked_user_info['lastname'];
        }

        if ($tracking_is_accepted)
        {
            $tracked_user_info['email'] == '' ? $mail_link = get_lang('NoEmail') : $mail_link = Display::encrypted_mailto_link($tracked_user_info['email']);

            if(!isset($view))
            {
                $view ='0000000';
            }
            //Logins
            list($title_line1, $line1) = TrackingUserLogCSV::display_login_tracking_info($view, $uInfo, $_cid);

            //Exercise results
            list($title_line2, $line2) = TrackingUserLogCSV::display_exercise_tracking_info($view, $uInfo, $_cid);

            //Student publications uploaded
            list($title_line3, $line3) = TrackingUserLogCSV::display_student_publications_tracking_info($view, $uInfo, $_cid);

            //Links usage
            list($title_line4, $line4) = TrackingUserLogCSV::display_links_tracking_info($view, $uInfo, $_cid);

            //Documents downloaded
            list($title_line5, $line5) = TrackingUserLogCSV::display_document_tracking_info($view, $uInfo, $_cid);

            $title_line = $title_line1.$title_line2.$title_line3.$title_line4.$title_line5;
            $line= $line1.$line2.$line3.$line4.$line5;
        }
        else
        {
            echo get_lang('ErrorUserNotInGroup');
        }


        /*
         *		Scorm contents and Learning Path
         */
         //TODO: scorm tools is in work and the logs will change in few days...
        /*if(substr($view,5,1) == '1')
        {
            $new_view = substr_replace($view,'0',5,1);
            $title[1]=get_lang('ScormContentColumn');
            $line ='';
            $sql = "SELECT id, name FROM $tbl_learnpath_main";
            $result=Database::query($sql);
            $ar=Database::fetch_array($result);

          if (is_array($ar))
            {
                while ($ar['id'] != '') {
                    $lp_title = stripslashes($ar['name']);
                    echo "<tr><td>";
                    echo "<a href='".api_get_self()."?view=".$view."&scormcontopen=".$ar['id']."&uInfo=$uInfo' class='specialLink'>$lp_title</a>";
                    echo "</td></tr>";
                    if ($ar['id']==$scormcontopen) { //have to list the students here
                            $contentId=$ar['id'];
                            $sql3 = "SELECT iv.status, iv.score, i.title, iv.total_time " .
                                    "FROM $tbl_learnpath_item i " .
                                    "INNER JOIN $tbl_learnpath_item_view iv ON i.id=iv.lp_item_id " .
                                    "INNER JOIN $tbl_learnpath_view v ON iv.lp_view_id=v.id " .
                                    "WHERE (v.user_id=$uInfo and v.lp_id=$contentId) ORDER BY v.id, i.id";
                               $result3=Database::query($sql3);
                               $ar3=Database::fetch_array($result3);
                            if (is_array($ar3)) {
                                $title_line=get_lang('ScormTitleColumn').";".get_lang('ScormStatusColumn').";".get_lang('ScormScoreColumn').";".get_lang('ScormTimeColumn')."\n";

                                   while ($ar3['status'] != '') {
                                    require_once('../newscorm/learnpathItem.class.php');
                                    $time = learnpathItem::get_scorm_time('php',$ar3['total_time']);
                                       $line .= $title.';'.$ar3['status'].';'.$ar3['score'].';'.$time."\n";
                                       $ar3=Database::fetch_array($result3);
                                   }
                            } else {
                                $line .= get_lang('ScormNeverOpened');
                            }
                       }
                    $ar=Database::fetch_array($result);
                }

            }
            else
            {
                $noscorm=true;
            }

            if ($noscorm) {
                $line=get_lang('NoResult');
            }
         }
        else
        {
            $new_view = substr_replace($view,'1',5,1);
        }*/

    }
     /*
     *		Export to a CSV file
     *		force the browser to save the file instead of opening it
      */

    $len = strlen($title_line.$line);
    header('Content-type: application/octet-stream');
    //header('Content-Type: application/force-download');
    header('Content-length: '.$len);
    $filename = html_entity_decode(str_replace(":","",str_replace(" ","_", $title[0].'_'.$title[1].'.csv')));
    $filename = replace_dangerous_char($filename);
    if(preg_match("/MSIE 5.5/",$_SERVER['HTTP_USER_AGENT']))
    {
        header('Content-Disposition: filename= '.$filename);
    }
    else
    {
        header('Content-Disposition: attachment; filename= '.$filename);
    }
    if(strpos($_SERVER['HTTP_USER_AGENT'],'MSIE'))
    {
        header('Pragma: ');
        header('Cache-Control: ');
        header('Cache-Control: public'); // IE cannot download from sessions without a cache
    }
    header('Content-Description: '.$filename);
    header('Content-transfer-encoding: binary');

    echo api_html_entity_decode($title_line, ENT_QUOTES, $charset);
    echo api_html_entity_decode($line, ENT_QUOTES, $charset);
    exit;


}
// not allowed
else
{
    api_not_allowed();
}
