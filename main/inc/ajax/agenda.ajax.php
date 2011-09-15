<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

require_once api_get_path(SYS_CODE_PATH).'calendar/agenda.inc.php';
require_once api_get_path(SYS_CODE_PATH).'calendar/myagenda.inc.php';
require_once api_get_path(SYS_CODE_PATH).'calendar/agenda.lib.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$type   = isset($_REQUEST['type']) && in_array($_REQUEST['type'], array('personal', 'course', 'admin')) ?  $_REQUEST['type'] : 'personal';
$agenda = new Agenda();

switch ($action) {    
	case 'add_event':
		//For now we only save personal events
		echo $agenda->add_event($_REQUEST['start'], $_REQUEST['end'], $_REQUEST['all_day'], $_REQUEST['view'], $type, $_REQUEST['title'], $_REQUEST['content']);
		break;		
	case 'edit_event':
		$id_list 	= explode('_', $_REQUEST['id']);		
		$type 		= $id_list[0];
		$id 		= $id_list[1];		
		$agenda->edit_event($id, $_REQUEST['start'], $_REQUEST['end'], $_REQUEST['all_day'], $_REQUEST['view'], $type ,$_REQUEST['title'], $_REQUEST['content']);
		break;
	case 'delete_event':
		$id_list 	= explode('_', $_REQUEST['id']);
		$type 		= $id_list[0];
		$id 		= $id_list[1];		
		$agenda->delete_event($id, $type);
		
		break;
	case 'move_event':
		$day_delta 		= $_REQUEST['day_delta'];
		$minute_delta 	= $_REQUEST['minute_delta'];
		$type 			= $_REQUEST['type'][0];
		$id 			= explode('_', $_REQUEST['id']);
		$id				= $id[1];
		$agenda->move_event($id, $type, $day_delta, $minute_delta);
		
		break;
	case 'get_events':
		$start 	= $_REQUEST['start'];
		$end 	= $_REQUEST['end'];
					
		$events = $agenda->get_events($start, $end, $type, api_get_user_id(), api_get_course_int_id());
		echo $events;		
		break;
		
    case 'get_user_agenda':
    	//Used in the admin user list 
    	api_protect_admin_script();
    	 
        if (api_is_allowed_to_edit(null, true)) {
            //@todo move this in the agenda class
            $DaysShort  = api_get_week_days_short();
            $MonthsLong = api_get_months_long();
            
            $user_id = intval($_REQUEST['user_id']);    
       	    $my_course_list = CourseManager::get_courses_list_by_user_id($user_id, true);
        	if (!is_array($my_course_list)) {
        		// this is for the special case if the user has no courses (otherwise you get an error)
        		$my_course_list = array();
        	}
        	$today = getdate();
        	$year = (!empty($_GET['year'])? (int)$_GET['year'] : NULL);
        	if ($year == NULL) {
        		$year = $today['year'];
        	}
        	$month = (!empty($_GET['month'])? (int)$_GET['month']:NULL);
        	if ($month == NULL) {
        		$month = $today['mon'];
        	}    
        	$day = (!empty($_GET['day']) ? (int)$_GET['day']:NULL);
        	if ($day == NULL) {
        		$day = $today['mday'];
        	}
        	$monthName = $MonthsLong[$month -1];            
        	    	
        	$agendaitems = get_myagendaitems($user_id, $my_course_list, $month, $year);        	
        	$agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "month_view");
        	
        	if (api_get_setting('allow_personal_agenda') == 'true') {
        		$agendaitems = get_personal_agenda_items($user_id, $agendaitems, $day, $month, $year, $week, "month_view");
        	}
        	display_mymonthcalendar($user_id, $agendaitems, $month, $year, array(), $monthName, false);        	
        }
        break;
    default:
        echo '';
}
exit;