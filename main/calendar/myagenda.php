<?php //$Id: myagenda.php 19108 2009-03-17 17:35:50Z ndieschburg $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
	@author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
	@author: Toon Van Hoecke <toon.vanhoecke@ugent.be>, Ghent University
	@author: Eric Remy (initial version)
	@version: 2.2 alpha
	@description: 	this file generates a general agenda of all items of the
					courses the user is registered for
==============================================================================
	version info:
	-------------
	-> version 2.3 : Yannick Warnier, yannick.warnier@dokeos.com 2008
	Added repeated events
	-> version 2.2 : Patrick Cool, patrick.cool@ugent.be, november 2004
	Personal Agenda added. The user can add personal agenda items. The items
	are stored in a dokeos_user database because it is not course or platform
	based. A personal agenda view was also added. This lists all the personal
	agenda items of that user.

	-> version 2.1 : Patrick Cool, patrick.cool@ugent.be, , oktober 2004
	This is the version that works with the Group based Agenda tool.

	-> version 2.0 (alpha): Patrick Cool, patrick.cool@ugent.be, , oktober 2004
	The 2.0 version introduces besides the month view also a week- and day view.
	In the 2.5 (final) version it will be possible for the student to add his/her
	own agenda items. The platform administrator can however decide if the students
	are allowed to do this or not.
	The alpha version only contains the three views. The personal agenda feature is
	not yet completely finished. There are however already some parts of the code
	for adding a personal agenda item present.
	this code was not released in an official dokeos but was only used in the offical
	server of the Ghent University where it underwent serious testing

	-> version 1.5: Toon Van Hoecke, toon.vanhoecke@ugent.be, december 2003

	-> version 1.0: Eric Remy, eremy@rmwc.edu, 6 Oct 2003
	The tool was initially called master-calendar as it collects all the calendar
	items of all the courses one is subscribed to. It was very soon integrated in
	Dokeos as this was a really basic and very usefull tool.

/* ==============================================================================
				  			HEADER
============================================================================== */


// name of the language file that needs to be included
$language_file = 'agenda';
// we are not inside a course, so we reset the course id
$cidReset = true;
// setting the global file that gets the general configuration, the databases, the languages, ...
require('../inc/global.inc.php');
$this_section = SECTION_MYAGENDA;
api_block_anonymous_users();
require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once('agenda.inc.php');
require_once('myagenda.inc.php');
// setting the name of the tool
$nameTools = get_lang('MyAgenda');

// if we come from inside a course and click on the 'My Agenda' link we show a link back to the course
// in the breadcrumbs
if(!empty($_GET['coursePath']))
{
	$course_path = htmlentities(strip_tags($_GET['coursePath']),ENT_QUOTES,$charset);
}
if (!empty ($course_path))
{
	$interbreadcrumb[] = array ('url' => api_get_path(WEB_COURSE_PATH).urlencode($course_path).'/index.php', 'name' => $_GET['courseCode']);
}
// this loads the javascript that is needed for the date popup selection
$htmlHeadXtra[] = "<script src=\"tbl_change.js\" type=\"text/javascript\" language=\"javascript\"></script>";
// showing the header
Display::display_header(get_lang('MyAgenda'));

/* ==============================================================================
  						SETTING SOME VARIABLES
============================================================================== */
// setting the database variables
$TABLECOURS = Database :: get_main_table(TABLE_MAIN_COURSE);
$TABLECOURSUSER = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$TABLEAGENDA = Database :: get_course_table(TABLE_AGENDA);
$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);
$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = array (get_lang("SundayShort"), get_lang("MondayShort"), get_lang("TuesdayShort"), get_lang("WednesdayShort"), get_lang("ThursdayShort"), get_lang("FridayShort"), get_lang("SaturdayShort"));
// Defining the days of the week to allow translation of the days
$DaysLong = array (get_lang("SundayLong"), get_lang("MondayLong"), get_lang("TuesdayLong"), get_lang("WednesdayLong"), get_lang("ThursdayLong"), get_lang("FridayLong"), get_lang("SaturdayLong"));
// Defining the months of the year to allow translation of the months
$MonthsLong = array (get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong"));

/*==============================================================================
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
  ============================================================================== */
// 1. The default values. if there is no session yet, we have by default the month view
if (empty($_SESSION['view']))
{
	$_SESSION['view'] = "month";
}
// 2. Storing it in the session. If we change the view by clicking on the links left, we change the session
if (!empty($_GET['view']))
{
	$_SESSION['view'] = $_GET['view'];
}
// 3. The views: (month, week, day, personal)
if ($_SESSION['view'])
{
	switch ($_SESSION['view'])
	{
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
// 4. add personal agenda
if (!empty($_GET['action']) && $_GET['action'] == "add_personal_agenda_item" and !$_POST['Submit'])
{
	$process = "add_personal_agenda_item";
}
if (!empty($_GET['action']) && $_GET['action'] == "add_personal_agenda_item" and $_POST['Submit'])
{
	$process = "store_personal_agenda_item";
}
// 5. edit personal agenda
if (!empty($_GET['action']) && $_GET['action'] == "edit_personal_agenda_item" and !$_POST['Submit'])
{
	$process = "edit_personal_agenda_item";
}
if (!empty($_GET['action']) && $_GET['action'] == "edit_personal_agenda_item" and $_POST['Submit'])
{
	$process = "store_personal_agenda_item";
}
// 6. delete personal agenda
if (!empty($_GET['action']) && $_GET['action'] == "delete" AND $_GET['id'])
{
	$process = "delete_personal_agenda_item";
}
/* ==============================================================================
  						OUTPUT
============================================================================== */
if (isset ($_user['user_id']))
{
	// getting all the courses that this user is subscribed to
	$courses_dbs = get_all_courses_of_user();
	if (!is_array($courses_dbs)) // this is for the special case if the user has no courses (otherwise you get an error)
	{
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
	// Starting the output
	
	echo "\n<div class=\"actions\">\n";
	echo "\t<a href=\"".api_get_self()."?action=view&amp;view=month\">".Display::return_icon('calendar_month.gif', get_lang('MonthView'))." ".get_lang('MonthView')."</a> \n";
	echo "\t<a href=\"".api_get_self()."?action=view&amp;view=week\">".Display::return_icon('calendar_week.gif', get_lang('WeekView'))." ".get_lang('WeekView')."</a> \n";
	echo "\t<a href=\"".api_get_self()."?action=view&amp;view=day\">".Display::return_icon('calendar_day.gif', get_lang('DayView'))." ".get_lang('DayView')."</a> \n";
	if (get_setting('allow_personal_agenda') == 'true')
	{
		echo "\t<a href=\"".api_get_self()."?action=add_personal_agenda_item\">".Display::return_icon('calendar_personal_add.gif', get_lang('AddPersonalItem'))." ".get_lang('AddPersonalItem')."</a> \n";
		echo "\t<a href=\"".api_get_self()."?action=view&amp;view=personal\">".Display::return_icon('calendar_personal.gif', get_lang('ViewPersonalItem'))."  ".get_lang('ViewPersonalItem')."</a> \n";
	}
	echo "</div>\n\n";	
	
	echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
	echo "<tr>";
	// output: the small calendar item on the left and the view / add links
	echo "<td width=\"220\" valign=\"top\">";
	$agendaitems = get_myagendaitems($courses_dbs, $month, $year);
	$agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "month_view");
	if (get_setting('allow_personal_agenda') == 'true')
	{
		$agendaitems = get_personal_agenda_items($agendaitems, $day, $month, $year, $week, "month_view");
	}
	display_myminimonthcalendar($agendaitems, $month, $year, $monthName);

	echo "</td>";
	// the divider
	// OlivierB : the image has a white background, which causes trouble if the portal has another background color. Image should be transparent. ----> echo "<td width=\"20\" background=\"../img/verticalruler.gif\">&nbsp;</td>";
	echo "<td width=\"20\">&nbsp;</td>";
	// the main area: day, week, month view
	echo "<td valign=\"top\">";
	switch ($process)
	{
		case "month_view" :
			$agendaitems = get_myagendaitems($courses_dbs, $month, $year);
			$agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "month_view");
			if (get_setting("allow_personal_agenda") == "true")
			{
				$agendaitems = get_personal_agenda_items($agendaitems, $day, $month, $year, $week, "month_view");
			}
			display_mymonthcalendar($agendaitems, $month, $year, array(), $monthName);
			break;
		case "week_view" :
			$agendaitems = get_week_agendaitems($courses_dbs, $month, $year, $week);
			$agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "week_view");
			if (get_setting("allow_personal_agenda") == "true")
			{
				$agendaitems = get_personal_agenda_items($agendaitems, $day, $month, $year, $week, "week_view");
			}
			display_weekcalendar($agendaitems, $month, $year, array(), $monthName);
			break;
		case "day_view" :
			$agendaitems = get_day_agendaitems($courses_dbs, $month, $year, $day);
			$agendaitems = get_global_agenda_items($agendaitems, $day, $month, $year, $week, "day_view");
			if (get_setting("allow_personal_agenda") == "true")
			{
				$agendaitems = get_personal_agenda_items($agendaitems, $day, $month, $year, $week, "day_view");
			}
			display_daycalendar($agendaitems, $day, $month, $year, array(), $monthName);
			break;
		case "personal_view" :
			show_personal_agenda();
			break;
		case "add_personal_agenda_item" :
			show_new_personal_item_form();
			break;
		case "store_personal_agenda_item" :
			store_personal_item($_POST['frm_day'], $_POST['frm_month'], $_POST['frm_year'], $_POST['frm_hour'], $_POST['frm_minute'], $_POST['frm_title'], $_POST['frm_content'], (int)$_GET['id']);
			if ($_GET['id'])
			{
				echo '<br />';
				Display :: display_normal_message(get_lang("PeronalAgendaItemEdited"));
			}
			else
			{
				echo '<br />';
				Display :: display_normal_message(get_lang("PeronalAgendaItemAdded"));
			}
			show_personal_agenda();
			break;
		case "edit_personal_agenda_item" :
			show_new_personal_item_form((int)$_GET['id']);
			break;
		case "delete_personal_agenda_item" :
			delete_personal_agenda((int)$_GET['id']);
			echo '<br />';
			Display :: display_normal_message(get_lang('PeronalAgendaItemDeleted'));
			show_personal_agenda();
			break;
	}
}
echo "</td></tr></table>";
Display :: display_footer();
?>
