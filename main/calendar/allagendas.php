<?php
/* For licensing terms, see /license.txt */

/**
 * 
 * Get the all events by session/course
 * @author Julio Montoya cleaning code, chamilo code style changes, all agenda feature work with courses and sessions, only admins and rrhh users can see this page 
 * 
 *  
 * @author Carlos Brolo First code submittion  
 */

// name of the language file that needs to be included
$language_file = 'agenda';
// we are not inside a course, so we reset the course id
$cidReset = true;
// setting the global file that gets the general configuration, the databases, the languages, ...
require_once '../inc/global.inc.php';
$this_section = SECTION_MYAGENDA;

require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';

require_once 'agenda.inc.php';
require_once 'myagenda.inc.php';

//This code is not yet stable
//Blocking the access
api_not_allowed();

api_block_anonymous_users();

// setting the name of the tool
$nameTools = get_lang('MyAgenda');

$is_platform_admin = api_is_platform_admin();

$is_drh = api_is_drh();
if (!($is_platform_admin || $is_drh)) {
	api_not_allowed();
}

// if we come from inside a course and click on the 'My Agenda' link we show a link back to the course
// in the breadcrumbs
//remove this if cause it was showing in agenda general
/*if(!empty($_GET['coursePath'])) {
	$course_path = api_htmlentities(strip_tags($_GET['coursePath']),ENT_QUOTES,$charset);
	$course_path = str_replace(array('../','..\\'),array('',''),$course_path);
}
*/
if (!empty ($course_path)) {
	$interbreadcrumb[] = array ('url' => api_get_path(WEB_COURSE_PATH).urlencode($course_path).'/index.php', 'name' => Security::remove_XSS($_GET['courseCode']));
}
// this loads the javascript that is needed for the date popup selection
//$htmlHeadXtra[] = "<script src=\"tbl_change.js\" type=\"text/javascript\" language=\"javascript\"></script>";

// showing the header
Display::display_header(get_lang('MyAgenda'));

function display_mymonthcalendar_2($agendaitems, $month, $year, $weekdaynames=array(), $monthName, $session_id) {	
	global $DaysShort, $course_path;
	//Handle leap year
	$numberofdays = array (0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if (($year % 400 == 0) or ($year % 4 == 0 and $year % 100 <> 0))
		$numberofdays[2] = 29;
	//Get the first day of the month
	$dayone = getdate(mktime(0, 0, 0, $month, 1, $year));
	//Start the week on monday
	$startdayofweek = $dayone['wday'] <> 0 ? ($dayone['wday'] - 1) : 6;
	$g_cc = (isset($_GET['courseCode'])?$_GET['courseCode']:'');
	$backwardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&amp;session=".Security::remove_XSS($session_id)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;action=view&amp;view=month&amp;month=". ($month == 1 ? 12 : $month -1)."&amp;year=". ($month == 1 ? $year -1 : $year);
	$forewardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&amp;session=".Security::remove_XSS($session_id)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;action=view&amp;view=month&amp;month=". ($month == 12 ? 1 : $month +1)."&amp;year=". ($month == 12 ? $year +1 : $year);

	echo "<table class=\"data_table\">\n", "<tr>\n", "<th width=\"10%\"><a href=\"", $backwardsURL, "\">&#171;</a></th>\n", "<th width=\"80%\" colspan=\"5\">", $monthName, " ", $year, "</th>\n", "<th width=\"10%\"><a href=\"", $forewardsURL, "\">&#187;</a></th>\n", "</tr>\n";

	echo "<tr>\n";
	for ($ii = 1; $ii < 8; $ii ++)
	{
		echo "<td class=\"weekdays\">", $DaysShort[$ii % 7], "</td>\n";
	}
	echo "</tr>\n";
	$curday = -1;
	$today = getdate();
	while ($curday <= $numberofdays[$month]) {
		echo "<tr>\n";
		for ($ii = 0; $ii < 7; $ii ++) {
			if (($curday == -1) && ($ii == $startdayofweek)) {
				$curday = 1;
			}
			if (($curday > 0) && ($curday <= $numberofdays[$month])) {
				$bgcolor = $ii < 5 ? $class = "class=\"days_week\" style=\"width:10%;\"" : $class = "class=\"days_weekend\" style=\"width:10%;\"";
				$dayheader = "<b>$curday</b><br />";
				if (($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon'])) {
					$dayheader = "<b>$curday - ".get_lang("Today")."</b><br />";
					$class = "class=\"days_today\" style=\"width:10%;\"";
				}
				echo "<td ".$class.">", "".$dayheader;
				if (!empty($agendaitems[$curday])) {
					echo "<span class=\"agendaitem\">".$agendaitems[$curday]."</span>";
				}
				echo "</td>\n";
				$curday ++;
			} else {
				echo "<td>&nbsp;</td>\n";
			}
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
}

function get_agenda_items_by_course_list($course_list, $month, $year, $session_id = 0) {	
	global $setting_agenda_link;
	//echo $sql  = 'SELECT name FROM chamilo_main.class WHERE name = "'.$grado.'" ORDER BY name ASC';
	//$result = Database::query($sql);
	//while ($row = Database::fetch_array($result, 'ASSOC')) {
	
	$agendaitems = array ();	
	$course_name_list = array();
	foreach ($course_list as $course) {
		
		$db_name		= $course['db_name'];
		$code			= $course['code'];
		$title			= $course['title'];
		$course_name_list[] = $title;		
		//$sql2  = 'SELECT code, db_name, title FROM chamilo_main.course WHERE category_code = "'.$course_name.'" ';
//		$courses_dbs = Database::query($sql2);		

		$items = array ();
		// $courses_dbs = array();
		// get agenda-items for every course
		//while($row2 = Database::fetch_array($courses_dbs, 'ASSOC')) {
		//$db_name 	= $row2['db_name'];
		//$code 		= $row2['code'];
		//$title 		= $row2['title'];
		//echo "<center><h2>".$db_name."</h2></center>";		
		
		//databases of the courses
		$TABLEAGENDA 		= Database :: get_course_table(TABLE_AGENDA, $db_name);
		$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY, $db_name);

		//$group_memberships = GroupManager :: get_group_ids($array_course_info["db"], $_user['user_id']);
		// if the user is administrator of that course we show all the agenda items
		$session_condition = '';
		if ($session_id != 0) {
			$session_id = intval($session_id);
			$session_condition = "AND session_id = $session_id"; 
		}
		
		$sqlquery = "SELECT	DISTINCT agenda.*, item_property.*
						FROM ".$TABLEAGENDA." agenda,
							 ".$TABLE_ITEMPROPERTY." item_property
						WHERE agenda.id = item_property.ref
						AND MONTH(agenda.start_date)='".$month."'
						AND YEAR(agenda.start_date)='".$year."'
						AND item_property.tool='".TOOL_CALENDAR_EVENT."'
						AND item_property.visibility='1' $session_condition
						GROUP BY agenda.id
						ORDER BY start_date ";		
		$result = Database::query($sqlquery);
		while ($item = Database::fetch_array($result,'ASSOC')) {
			//var_dump($item);
			//taking the day
			$agendaday = date("j",strtotime($item['start_date']));				
			if(!isset($items[$agendaday])){$items[$agendaday]=array();}
			//taking the time
			$time = date("H:i",strtotime($item['start_date']));
			//var_dump($time );
			$end_time= date("H:i",strtotime($item['end_date']));
			$URL = api_get_path(WEB_PATH)."main/calendar/allagendas.php?cidReq=".urlencode($code)."&amp;sort=asc&amp;view=list&amp;day=$agendaday&amp;month=$month&amp;year=$year#$agendaday"; // RH  //Patrick Cool: to highlight the relevant agenda item
			//var_dump($URL);
			
			if ($setting_agenda_link == 'coursecode') {
				//$title=$array_course_info['title'];
				$agenda_link = api_substr($title, 0, 14);
			} else {
				$agenda_link = Display::return_icon('course_home.gif');
			}
			if(!isset($items[$agendaday][$item['start_date']])) {
				$items[$agendaday][$item['start_date']] = '';
			}
			
			$items[$agendaday][$item['start_date']] .= "".get_lang('StartTimeWindow')."&nbsp;<i>".$time."</i>"."&nbsp;-&nbsp;".get_lang("EndTimeWindow")."&nbsp;<i>".$end_time."</i>&nbsp;";
			$items[$agendaday][$item['start_date']] .= '<br />'."<b><a href=\"$URL\" title=\"".Security::remove_XSS($title)."\">".$agenda_link."</a> </b> ".Security::remove_XSS($item['title'])."<br /> ";
			$items[$agendaday][$item['start_date']] .= '<br/>';
		}
		
		if (is_array($items) && count($items) > 0) {
			while (list ($agendaday, $tmpitems) = each($items)) {
				if(!isset($agendaitems[$agendaday])) {
					$agendaitems[$agendaday] = '';
				}
				sort($tmpitems);
				while (list ($key, $val) = each($tmpitems)) {
					$agendaitems[$agendaday] .= $val;
				}
			}
		}
	}
	echo "<h1>Courses:</h1> <h3>".implode(', ',$course_name_list)."</h3>";
	return $agendaitems;
}

/* 		SETTING SOME VARIABLES		*/

// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = api_get_week_days_short();
// Defining the days of the week to allow translation of the days
$DaysLong = api_get_week_days_long();
// Defining the months of the year to allow translation of the months
$MonthsLong = api_get_months_long();

/*
  			TREATING THE URL PARAMETERS
			1. The default values
			2. storing it in the session
			3. possible view
				3.a Month view
				3.b Week view
				3.c day view
				3.d personal view (only the personal agenda items)
*/
// 1. The default values. if there is no session yet, we have by default the month view
if (empty($_SESSION['view'])) {
	$_SESSION['view'] = 'month';
}
// 2. Storing it in the session. If we change the view by clicking on the links left, we change the session
if (!empty($_GET['view'])) {
	$_SESSION['view'] = Security::remove_XSS($_GET['view']);

}
// 3. The views: (month, week, day, personal)
if ($_SESSION['view']) {
	switch ($_SESSION['view'])	{
		// 3.a Month view
		case "month" :
			$process = "month_view";
			break;
			// 3.a Week view
		case "week" :
			$process = "week_view";
			break;
			// 3.a Day view
		case "day" :
			$process = "day_view";
			break;
			// 3.a Personal view
		case "personal" :
			$process = "personal_view";
			break;
	}
}

$my_course_id  = intval($_GET['course']);
$my_session_id = intval($_GET['session']);

$my_course_list = array();

if(!empty($my_session_id)) {
	$_SESSION['my_course_list'] = array();	
	$my_course_list = array();
} else {
	//echo 'here';
	$my_course_list = $_SESSION['my_course_list'];
	 
	//var_dump($_SESSION['my_course_list'], $my_course_list);	
	
	$my_course_list_keys = array_keys($my_course_list);
	
	//var_dump($my_course_list, $my_course_list_keys);
	if (!in_array($my_course_id, $my_course_list_keys)) {	
		$course_info = api_get_course_info_by_id($my_course_id);
		$_SESSION['my_course_list'][$my_course_id] = $course_info;	
		$my_course_list = $_SESSION['my_course_list'];
		//echo $my_course_id.'added ';
	}
	
	if (isset($_GET['delete_course_option'])) {
		$course_id_to_delete = intval($_GET['delete_course_option']);	
		unset($_SESSION['my_course_list'][$course_id_to_delete]);	
		$my_course_list = $_SESSION['my_course_list'];
	}
	//clean the array
	$my_course_list = array_filter($my_course_list);	
}

/* 	OUTPUT	*/
if (isset ($_user['user_id'])) {
	// getting all the courses that this user is subscribed to
	$courses_dbs = get_all_courses_of_user();
	if (!is_array($courses_dbs)) {
		// this is for the special case if the user has no courses (otherwise you get an error)
		$courses_dbs = array ();
	}
	// setting and/or getting the year, month, day, week
	$today = getdate();
	$year = (!empty($_GET['year'])? (int)$_GET['year'] : NULL);
	if ($year == NULL)
	{
		$year = $today['year'];
	}
	$month = (!empty($_GET['month'])? (int)$_GET['month']:NULL);
	if ($month == NULL)
	{
		$month = $today['mon'];
	}
	$day = (!empty($_GET['day']) ? (int)$_GET['day']:NULL);
	if ($day == NULL)
	{
		$day = $today['mday'];
	}
	$week = (!empty($_GET['week']) ?(int)$_GET['week']:NULL);
	if ($week == NULL)
	{
		$week = date("W");
	}
	// The name of the current Month
	$monthName = $MonthsLong[$month -1];

	if (api_is_platform_admin()) {
		$courses  = array();
		$sessions = SessionManager::get_sessions_list();		
		
	} elseif(api_is_drh()) {
		$courses  = CourseManager::get_courses_followed_by_drh(api_get_user_id());		
		$sessions = SessionManager::get_sessions_followed_by_drh(api_get_user_id());
	}

	echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
	echo '<tr>';
	
	// output: the small calendar item on the left and the view / add links
	echo '<td width="220" valign="top">';
		
		echo '<br />';
		if (count($courses) > 0) {
			echo '<h1>'.get_lang('SelectACourse').'</h1>';
			
			foreach ($courses as $row_course) {	
				$course_code = $row_course['id'];
				$title = $row_course['title'];
				$my_course_list_keys = array_keys($my_course_list);
				if (!in_array($course_code, $my_course_list_keys)) {
					echo '<a href="allagendas.php?course='.$course_code.'">'.$title.'</a><br />';
				} else {
					echo ''.$title.' <a href="allagendas.php?delete_course_option='.$course_code.'">Delete</a><br />';
				}
			}
		}
		if (count($sessions) > 0) {
			echo '<h1>'.get_lang('SelectASession').'</h1>';
			foreach ($sessions as $session) {			
				$id = $session['id'];
				$name = $session['name'];
				echo '<a href="allagendas.php?session='.$id.'">'.$name.'</a><br />'; 
			}
		}
	echo '</td>';
		
	// the divider
	// OlivierB : the image has a white background, which causes trouble if the portal has another background color. Image should be transparent. ----> echo "<td width=\"20\" background=\"../img/verticalruler.gif\">&nbsp;</td>";
	echo "<td width=\"20\">&nbsp;</td>";
	// the main area: day, week, month view
	echo '<td valign="top">';
	
	//@todo hardcoding option
	$process = 'month_view';
	
	switch ($process) {
		case "month_view" :
			$session_id = 0;
			//By courses
			if (is_array($my_course_list) && count($my_course_list) > 0) {
				$course_list = $my_course_list;				
			} else {
				//session
				$course_list = SessionManager::get_course_list_by_session_id($my_session_id);
				$session_id = $my_session_id;				
				echo '<h1>'.$sessions[$session_id]['name'].'</h1>';					
			}
			if (is_array($course_list) && count($course_list) > 0) {
				
				$agendaitems = get_agenda_items_by_course_list($course_list, $month, $year, $session_id);
				//$agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "month_view");
				display_mymonthcalendar_2($agendaitems, $month, $year, array(), $monthName, $session_id);
			} else {
				Display::display_warning_message(get_lang('PleaseSelectACourseOrASessionInTheLeftColumn'));
			}
			break;
	}
}
echo "</td></tr></table>";
Display :: display_footer();