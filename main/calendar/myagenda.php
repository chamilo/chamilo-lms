<?php
/* For licensing terms, see /license.txt */

// name of the language file that needs to be included
$language_file = 'agenda';
// we are not inside a course, so we reset the course id
$cidReset = true;
// setting the global file that gets the general configuration, the databases, the languages
require_once '../inc/global.inc.php';
$this_section = SECTION_MYAGENDA;
unset($_SESSION['this_section']);//for hmtl editor repository

api_block_anonymous_users();
require_once 'agenda.inc.php';
require_once 'myagenda.inc.php';
// setting the name of the tool
$nameTools = get_lang('MyAgenda');

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
$htmlHeadXtra[] = to_javascript();
$htmlHeadXtra[] = "<script src=\"tbl_change.js\" type=\"text/javascript\" language=\"javascript\"></script>";
$htmlHeadXtra[] = "<script>
$(function() {  
    $(\".dialog\").dialog(\"destroy\");        
    $(\".dialog\").dialog({
            autoOpen: false,
            show: \"blind\",                
            resizable: false,
            height:300,
            width:550,
            modal: true
     });
    $(\".opener\").click(function() {
        var my_id = $(this).attr('id');
        var big_image = '#main_' + my_id;
        $( big_image ).dialog(\"open\");
        return false;
    });
});
</script>
";

// showing the header
Display::display_header(get_lang('MyAgenda'));


//	SETTING SOME VARIABLES

// setting the database variables
$TABLECOURS 		= Database :: get_main_table(TABLE_MAIN_COURSE);
$TABLECOURSUSER 	= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$TABLEAGENDA 		= Database :: get_course_table(TABLE_AGENDA);
$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);
$tbl_personal_agenda= Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

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
	4. add personal agenda
	5. edit personal agenda
	6. delete personal agenda
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
	switch ($_SESSION['view']) {
		// 3.a Month view
		case "month" :
			$process = 'month_view';
			break;
			// 3.a Week view
		case "week" :
			$process = 'week_view';
			break;
			// 3.a Day view
		case "day" :
			$process = 'day_view';
			break;
			// 3.a Personal view
		case "personal" :
			$process = 'personal_view';
			break;
	}
}
// 4. add personal agenda
if (!empty($_GET['action']) && $_GET['action'] == 'add_personal_agenda_item' and !$_POST['Submit']) {
	$process = "add_personal_agenda_item";
}

if (!empty($_REQUEST['action']) && $_REQUEST['action'] == "add_personal_agenda_item" and $_POST['Submit']) {
	$process = "store_personal_agenda_item";
}
// 5. edit personal agenda
if (!empty($_GET['action']) && $_GET['action'] == 'edit_personal_agenda_item' and !$_POST['Submit']) {
	$process = "edit_personal_agenda_item";
}
if (!empty($_GET['action']) && $_GET['action'] == 'edit_personal_agenda_item'  and $_POST['Submit']) {
	$process = "store_personal_agenda_item";
}
// 6. delete personal agenda
if (!empty($_GET['action']) && $_GET['action'] == "delete" AND $_GET['id']) {
	$process = "delete_personal_agenda_item";
}

// OUTPUT
if (isset($_user['user_id'])) {
	// getting all the courses that this user is subscribed to
	//$courses_dbs = get_all_courses_of_user();
	$my_course_list = CourseManager::get_courses_list_by_user_id(api_get_user_id(), true);
	
	if (!is_array($my_course_list)) {
		// this is for the special case if the user has no courses (otherwise you get an error)
		$my_course_list = array();
	}
	// setting and/or getting the year, month, day, week
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
	$week = (!empty($_GET['week']) ?(int)$_GET['week']:NULL);
	if ($week == NULL) {
		$week = date("W");
	}
	// The name of the current Month
	$monthName = $MonthsLong[$month -1];
	// Starting the output

	echo "<div class=\"actions\">";
	echo "<a href=\"".api_get_self()."?action=view&amp;view=month\">".Display::return_icon('month.png', get_lang('MonthView'),'',ICON_SIZE_MEDIUM)."</a>";
	echo "<a href=\"".api_get_self()."?action=view&amp;view=week\">".Display::return_icon('7days.png', get_lang('WeekView'),'',ICON_SIZE_MEDIUM)."</a> ";
	echo "<a href=\"".api_get_self()."?action=view&amp;view=day\">".Display::return_icon('1day.png', get_lang('DayView'),'',ICON_SIZE_MEDIUM)."</a> ";
	if (api_get_setting('allow_personal_agenda') == 'true') {
		echo "<a href=\"".api_get_self()."?action=add_personal_agenda_item\">".Display::return_icon('new_user_event.png', get_lang('AddPersonalItem'),'',ICON_SIZE_MEDIUM)."</a> ";
		echo "<a href=\"".api_get_self()."?action=view&amp;view=personal\">".Display::return_icon('personal_calendar.png', get_lang('ViewPersonalItem'),'',ICON_SIZE_MEDIUM)."</a> ";
	}
	echo "</div>";

	$agendaitems = get_myagendaitems(api_get_user_id(), $my_course_list, $month, $year);	
	$agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "month_view");
	
	if (api_get_setting('allow_personal_agenda') == 'true') {
		$agendaitems = get_personal_agenda_items(api_get_user_id(), $agendaitems, $day, $month, $year, $week, "month_view");
	}
	
	if ($process != 'month_view') {
	    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
    	echo "<tr>";
    	// output: the small calendar item on the left and the view / add links
    	echo "<td width=\"220\" valign=\"top\">";
	    display_myminimonthcalendar($agendaitems, $month, $year, $monthName);
	    echo "</td>";
    	// the divider
    	// OlivierB : the image has a white background, which causes trouble if the portal has another background color. Image should be transparent. ----> echo "<td width=\"20\" background=\"../img/verticalruler.gif\">&nbsp;</td>";
    	echo "<td width=\"8\">&nbsp;</td>";
    	// the main area: day, week, month view
    	echo "<td valign=\"top\">";   
	}
	
	switch ($process) {
		case 'month_view' :			
			display_mymonthcalendar(api_get_user_id(), $agendaitems, $month, $year, array(), $monthName);
			break;
		case 'week_view' :
			$agendaitems = get_week_agendaitems($my_course_list, $month, $year, $week);						
			$agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "week_view");			
			if (api_get_setting("allow_personal_agenda") == "true") {
				$agendaitems = get_personal_agenda_items(api_get_user_id(), $agendaitems, $day, $month, $year, $week, "week_view");
			}
			display_weekcalendar($agendaitems, $month, $year, array(), $monthName);
			break;
		case 'day_view' :
			$agendaitems = get_day_agendaitems($my_course_list, $month, $year, $day);								
			$agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "day_view");			
			if (api_get_setting('allow_personal_agenda') == 'true') {
				$agendaitems = get_personal_agenda_items(api_get_user_id(), $agendaitems, $day, $month, $year, $week, "day_view");				
			}
			display_daycalendar($agendaitems, $day, $month, $year, array(), $monthName);
			break;
		case 'personal_view' :
			show_personal_agenda();
			break;
		case 'add_personal_agenda_item' :
			show_new_personal_item_form();
			break;
		case 'store_personal_agenda_item' :
			store_personal_item($_POST['frm_day'], $_POST['frm_month'], $_POST['frm_year'], $_POST['frm_hour'], $_POST['frm_minute'], $_POST['frm_title'], $_POST['frm_content'], $_GET['id']);
			if ($_GET['id']) {
				echo '<br />';
				Display :: display_normal_message(get_lang("PeronalAgendaItemEdited"));
			} else {
				echo '<br />';
				Display :: display_normal_message(get_lang("PeronalAgendaItemAdded"));
			}
			show_personal_agenda();
			break;
		case 'edit_personal_agenda_item' :
			show_new_personal_item_form((int)$_GET['id']);
			break;
		case 'delete_personal_agenda_item' :
			delete_personal_agenda((int)$_GET['id']);
			echo '<br />';
			Display :: display_normal_message(get_lang('PeronalAgendaItemDeleted'));
			show_personal_agenda();
			break;
	}
}
if ($process != 'month_view') {
    echo '</td></tr></table>';
}
Display :: display_footer();