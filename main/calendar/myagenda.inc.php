<?php //$Id: agenda.php 16490 2008-10-10 14:29:52Z elixir_inter $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
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
*/

/**
 * Settings (you may alter this at will
 */
$setting_agenda_link = 'coursecode'; // valid values are coursecode and icon

/**
 *	This function retrieves all the agenda items of all the courses the user is subscribed to
 */
function get_myagendaitems($courses_dbs, $month, $year)
{
	global $_user;
	global $_configuration;
	global $setting_agenda_link;
	

	$items = array ();
	// get agenda-items for every course
	foreach ($courses_dbs as $key => $array_course_info)
	{		
		//databases of the courses
		$TABLEAGENDA = Database :: get_course_table(TABLE_AGENDA, $array_course_info["db"]);
		$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY, $array_course_info["db"]);

		$group_memberships = GroupManager :: get_group_ids($array_course_info["db"], $_user['user_id']);
		// if the user is administrator of that course we show all the agenda items
		if ($array_course_info['status'] == '1')
		{
			//echo "course admin";
			$sqlquery = "SELECT
										DISTINCT agenda.*, item_property.*
										FROM ".$TABLEAGENDA." agenda,
											 ".$TABLE_ITEMPROPERTY." item_property
										WHERE agenda.id = item_property.ref 
										AND MONTH(agenda.start_date)='".$month."'
										AND YEAR(agenda.start_date)='".$year."'
										AND item_property.tool='".TOOL_CALENDAR_EVENT."'
										AND item_property.visibility='1'
										GROUP BY agenda.id
										ORDER BY start_date ";
		}
		// if the user is not an administrator of that course
		else
		{
			//echo "GEEN course admin";
			if (is_array($group_memberships) && count($group_memberships)>0)
			{
				$sqlquery = "SELECT
													agenda.*, item_property.*
													FROM ".$TABLEAGENDA." agenda,
														".$TABLE_ITEMPROPERTY." item_property
													WHERE agenda.id = item_property.ref
													AND MONTH(agenda.start_date)='".$month."'
													AND YEAR(agenda.start_date)='".$year."'
													AND item_property.tool='".TOOL_CALENDAR_EVENT."'
													AND	( item_property.to_user_id='".$_user['user_id']."' OR item_property.to_group_id IN (0, ".implode(", ", $group_memberships).") )
													AND item_property.visibility='1'
													ORDER BY start_date ";
			}
			else
			{
				$sqlquery = "SELECT
													agenda.*, item_property.*
													FROM ".$TABLEAGENDA." agenda,
														".$TABLE_ITEMPROPERTY." item_property
													WHERE agenda.id = item_property.ref
													AND MONTH(agenda.start_date)='".$month."'
													AND YEAR(agenda.start_date)='".$year."'
													AND item_property.tool='".TOOL_CALENDAR_EVENT."'
													AND ( item_property.to_user_id='".$_user['user_id']."' OR item_property.to_group_id='0')
													AND item_property.visibility='1'
													ORDER BY start_date ";
			}
		}

		$result = api_sql_query($sqlquery, __FILE__, __LINE__);
		while ($item = Database::fetch_array($result))
		{
			$agendaday = date("j",strtotime($item['start_date']));
			if(!isset($items[$agendaday])){$items[$agendaday]=array();}
			$time= date("H:i",strtotime($item['start_date']));
			$URL = api_get_path(WEB_PATH)."main/calendar/agenda.php?cidReq=".urlencode($array_course_info["code"])."&amp;day=$agendaday&amp;month=$month&amp;year=$year#$agendaday"; // RH  //Patrick Cool: to highlight the relevant agenda item
			if ($setting_agenda_link == 'coursecode')
			{
				$title=$array_course_info['title'];
				$agenda_link = substr($title, 0, 14);
			}
			else 
			{
				$agenda_link = Display::return_icon('course_home.gif');
			}
			if(!isset($items[$agendaday][$item['start_date']]))
			{
				$items[$agendaday][$item['start_date']] = '';
			}
			$items[$agendaday][$item['start_date']] .= "<i>".$time."</i> <a href=\"$URL\" title=\"".Security::remove_XSS($array_course_info['title'])."\">".$agenda_link."</a>  ".Security::remove_XSS($item['title'])."<br />";
		}
	}
	// sorting by hour for every day
	$agendaitems = array ();
	while (list ($agendaday, $tmpitems) = each($items))
	{
		if(!isset($agendaitems[$agendaday]))
		{
			$agendaitems[$agendaday] = '';
		}
		sort($tmpitems);
		while (list ($key, $val) = each($tmpitems))
		{
			$agendaitems[$agendaday] .= $val;
		}
	}
	//print_r($agendaitems);
	return $agendaitems;
}
/**
 * Show the monthcalender of the given month
 * @param	array	Agendaitems
 * @param	int	Month number
 * @param	int	Year number
 * @param	array	Array of strings containing long week day names (deprecated, you can send an empty array instead)
 * @param	string	The month name
 * @return	void	Direct output
 */
function display_mymonthcalendar($agendaitems, $month, $year, $weekdaynames=array(), $monthName)
{
	global $DaysShort,$course_path;
	//Handle leap year
	$numberofdays = array (0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if (($year % 400 == 0) or ($year % 4 == 0 and $year % 100 <> 0))
		$numberofdays[2] = 29;
	//Get the first day of the month
	$dayone = getdate(mktime(0, 0, 0, $month, 1, $year));
	//Start the week on monday
	$startdayofweek = $dayone['wday'] <> 0 ? ($dayone['wday'] - 1) : 6;
	$g_cc = (isset($_GET['courseCode'])?$_GET['courseCode']:'');
	$backwardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;action=view&amp;view=month&amp;month=". ($month == 1 ? 12 : $month -1)."&amp;year=". ($month == 1 ? $year -1 : $year);
	$forewardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;action=view&amp;view=month&amp;month=". ($month == 12 ? 1 : $month +1)."&amp;year=". ($month == 12 ? $year +1 : $year);

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
				$bgcolor = $ii < 5 ? $class = "class=\"days_week\"" : $class = "class=\"days_weekend\"";
				$dayheader = "<b>$curday</b><br />";
				if (($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon'])) {
					$dayheader = "<b>$curday - ".get_lang("Today")."</b><br />";
					$class = "class=\"days_today\"";
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
/**
 * Show the mini calender of the given month
 */
function display_myminimonthcalendar($agendaitems, $month, $year, $monthName)
{
	global $DaysShort,$course_path;
	//Handle leap year
	$numberofdays = array (0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if (($year % 400 == 0) or ($year % 4 == 0 and $year % 100 <> 0))
		$numberofdays[2] = 29;
	//Get the first day of the month
	$dayone = getdate(mktime(0, 0, 0, $month, 1, $year));
	//Start the week on monday
	$startdayofweek = $dayone['wday'] <> 0 ? ($dayone['wday'] - 1) : 6;
	$g_cc = (isset($_GET['courseCode'])?$_GET['courseCode']:'');
	$backwardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;month=". ($month == 1 ? 12 : $month -1)."&amp;year=". ($month == 1 ? $year -1 : $year);
	$forewardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;month=". ($month == 12 ? 1 : $month +1)."&amp;year=". ($month == 12 ? $year +1 : $year);

	echo "<table class=\"data_table\">\n", "<tr>\n", "<th width=\"10%\"><a href=\"", $backwardsURL, "\">&#171;</a></th>\n", "<th width=\"80%\" colspan=\"5\">", $monthName, " ", $year, "</th>\n", "<th width=\"10%\"><a href=\"", $forewardsURL, "\">&#187;</a></th>\n", "</tr>\n";

	echo "<tr>\n";
	for ($ii = 1; $ii < 8; $ii ++)
	{
		echo "<td class=\"weekdays\">", $DaysShort[$ii % 7], "</td>\n";
	}
	echo "</tr>\n";
	$curday = -1;
	$today = getdate();
	while ($curday <= $numberofdays[$month])
	{
		echo "<tr>\n";
		for ($ii = 0; $ii < 7; $ii ++)
		{
			if (($curday == -1) && ($ii == $startdayofweek))
			{
				$curday = 1;
			}
			if (($curday > 0) && ($curday <= $numberofdays[$month]))
			{
				$bgcolor = $ii < 5 ? $class = "class=\"days_week\"" : $class = "class=\"days_weekend\"";
				$dayheader = "$curday";
				if (($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon']))
				{
					$dayheader = "$curday";
					$class = "class=\"days_today\"";
				}
				echo "\t<td ".$class.">";
				if (!empty($agendaitems[$curday]))
				{
					echo "<a href=\"".api_get_self()."?action=view&amp;view=day&amp;day=".$curday."&amp;month=".$month."&amp;year=".$year."\">".$dayheader."</a>";
				}
				else
				{
					echo $dayheader;
				}
				// "a".$dayheader." <span class=\"agendaitem\">".$agendaitems[$curday]."</span>\n";
				echo "</td>\n";
				$curday ++;
			}
			else
			{
				echo "<td>&nbsp;</td>\n";
			}
		}
		echo "</tr>\n";
	}
	echo "</table>\n";
}

/**
 * This function shows all the forms that are needed form adding /editing a new personal agenda item
 * when there is no $id passed in the function we are adding a new agenda item, if there is a $id
 * we are editing
 * attention: we have to check that the student is editing an item that belongs to him/her
 */
function show_new_personal_item_form($id = "")
{
	global $year, $MonthsLong;
	global $tbl_personal_agenda;
	global $_user;
	// we construct the default time and date data (used if we are not editing a personal agenda item)
	$today = getdate();
	$day = $today['mday'];
	$month = $today['mon'];
	$year = $today['year'];
	$hours = $today['hours'];
	$minutes = $today['minutes'];
	// if an $id is passed to this function this means we are editing an item
	// we are loading the information here (we do this after everything else
	// to overwrite the default information)
	if ($id <> "")
	{
		$sql = "SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$_user['user_id']."' AND id='".$id."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$aantal = Database::num_rows($result);
		if ($aantal <> 0)
		{
			$row = Database::fetch_array($result);
			$year = substr($row['date'], 0, 4);
			$month = substr($row['date'], 5, 2);
			$day = substr($row['date'], 8, 2);
			$hours = substr($row['date'], 11, 2);
			$minutes = substr($row['date'], 14, 2);
			$title = $row['title'];
			$text = $row['text'];
		}
		else
		{
			return false;
		}
	}
	echo "<form method=\"post\" action=\"myagenda.php?action=add_personal_agenda_item&amp;id=$id\" name=\"newedit_form\">\n";
	echo "<table width=\"100%\" id=\"newedit_form\">\n";
	echo "\t<tr class=\"title\">\n\t\t<td colspan=\"3\"><h4>";
	echo ($_GET['action'] == 'edit_personal_agenda_item') ? get_lang("ModifyPersonalCalendarItem") : get_lang("AddPersonalCalendarItem");
	echo "</h4></td>\n\t</tr>\n";
	echo "\t<tr class=\"subtitle\">\n\t\t<td>\n";
	echo "<!-- date: 1 -> 31 -->\n";
	echo "\t\t".get_lang("Date").": \n";
	// ********** The form containing the days (0->31) ********** \\
	echo "<select name=\"frm_day\">\n";
	// small loop for filling all the dates
	// 2do: the available dates should be those of the selected month => february is from 1 to 28 (or 29) and not to 31
	for ($i = 1; $i <= 31; $i ++)
	{
		// values have to have double digits
		if ($i <= 9)
		{
			$value = "0".$i;
		}
		else
		{
			$value = $i;
		}
		// the current day is indicated with [] around the date
		if ($value == $day)
		{
			echo "\t\t\t\t <option value=\"".$value."\" selected>".$i."</option>\n";
		}
		else
		{
			echo "\t\t\t\t <option value=\"".$value."\">".$i."</option>\n";
		}
	}
	echo "</select>\n\n";
	// ********** The form containing the months (jan->dec) ********** \\
	echo "<!-- month: january -> december -->\n";
	echo "<select name=\"frm_month\">\n";
	for ($i = 1; $i <= 12; $i ++)
	{
		// values have to have double digits
		if ($i <= 9)
		{
			$value = "0".$i;
		}
		else
		{
			$value = $i;
		}
		// the current month is indicated with [] around the month name
		if ($value == $month)
		{
			echo "\t<option value=\"".$value."\" selected>".$MonthsLong[$i -1]."</option>\n";
		}
		else
		{
			echo "\t<option value=\"".$value."\">".$MonthsLong[$i -1]."</option>\n";
		}
	}
	echo "</select>\n\n";
	// ********** The form containing the years ********** \\
	echo "<!-- year -->\n";
	echo "<select name=\"frm_year\">";
	echo "<option value=\"". ($year -1)."\">". ($year -1)."</option>\n";
	echo "<option value=\"".$year."\" selected>".$year."</option>\n";
	for ($i = 1; $i <= 5; $i ++)
	{
		$value = $year + $i;
		echo "\t<option value=\"".$value."\">".$value."</option>\n";
	}
	echo "</select>";
	echo "<a title=\"Kalender\" href=\"javascript:openCalendar('newedit_form', 'frm_')\">".Display::return_icon('calendar_select.gif', get_lang('Select'), array ('style' => 'vertical-align: middle;'))."</a>";
	echo "&nbsp;&nbsp;";
	// ********** The form containing the hours  (00->23) ********** \\
	echo "<!-- time: hour -->\n";
	echo get_lang("Time").": \n";
	echo "<select name=\"frm_hour\">\n";
	for ($i = 1; $i <= 24; $i ++)
	{
		// values have to have double digits
		if ($i <= 9)
		{
			$value = "0".$i;
		}
		else
		{
			$value = $i;
		}
		// the current hour is indicated with [] around the hour
		if ($hours == $value)
		{
			echo "\t\t\t\t<option value=\"".$value."\" selected> ".$value." </option>\n";
		}
		else
		{
			echo "\t\t\t\t<option value=\"".$value."\"> ".$value." </option>\n";
		}
	}
	echo "</select>";
	// ********** The form containing the minutes ********** \\
	echo "<select name=\"frm_minute\">";
	echo "<option value=\"".$minutes."\">".$minutes."</option>";
	echo "<option value=\"00\">00</option>";
	echo "<option value=\"05\">05</option>";
	echo "<option value=\"10\">10</option>";
	echo "<option value=\"15\">15</option>";
	echo "<option value=\"20\">20</option>";
	echo "<option value=\"25\">25</option>";
	echo "<option value=\"30\">30</option>";
	echo "<option value=\"35\">35</option>";
	echo "<option value=\"40\">40</option>";
	echo "<option value=\"45\">45</option>";
	echo "<option value=\"50\">50</option>";
	echo "<option value=\"55\">55</option>";
	echo "</select>";
	echo "</td></tr>";
	// ********** The title field ********** \\
	echo "<tr class=\"subtitle\"><td colspan=\"2\">";
	echo get_lang('Title').': <input type="text" name="frm_title" size="50" value="'.$title.'" />';
	echo "</td></tr>";
	// ********** The text field ********** \\
	echo "<tr><td colspan=\"2\">";
	//api_disp_html_area('frm_content', $text, '300px');
	echo'<textarea name="frm_content" style="width: 450px; height: 100px;">'.$text.'</textarea>';
	echo "</td></tr>";
	// ********** The Submit button********** \\
	echo "<tr><td colspan=\"2\">";
	echo '<button type="submit" class="add" name="Submit" value="'.get_lang('AddEvent').'" >'.get_lang('AddEvent').'</button>';
	echo "</td></tr>";
	echo "</table>\n</form>\n";
}
/**
 * This function shows all the forms that are needed form adding a new personal agenda item
 */
function store_personal_item($day, $month, $year, $hour, $minute, $title, $content, $id = "")
{
	global $tbl_personal_agenda;
	global $_user;
	//constructing the date
	$date = $year."-".$month."-".$day." ".$hour.":".$minute.":00";
	if ($id <> "")
	{ // we are updating
		$sql = "UPDATE ".$tbl_personal_agenda." SET user='".$_user['user_id']."', title='".$title."', text='".$content."', date='".$date."' WHERE id='".$id."'";
	}
	else
	{ // we are adding a new item
		$sql = "INSERT INTO $tbl_personal_agenda (user, title, text, date) VALUES ('".$_user['user_id']."','$title', '$content', '$date')";
	}
	$result = api_sql_query($sql, __FILE__, __LINE__);
}
/**
 * This function finds all the courses (also those of sessions) of the user and returns an array containing the
 * database name of the courses.
 * Xritten by Noel Dieschburg <noel.dieschburg@dokeos.com>
 */

function get_all_courses_of_user()
{
        global $TABLECOURS;
        global $TABLECOURSUSER;
        global $_user;
        $tbl_session_course     = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_course_user= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session                    = Database :: get_main_table(TABLE_MAIN_SESSION);
        $sql_select_courses = "SELECT c.code k, c.visual_code  vc, c.title i, c.tutor_name t, 
                                      c.db_name db, c.directory dir, '5' as status
                                FROM $TABLECOURS c, $tbl_session_course_user srcu
                                WHERE srcu.id_user='".$_user['user_id']."' 
                                AND c.code=srcu.course_code
                                UNION 
                               SELECT c.code k, c.visual_code  vc, c.title i, c.tutor_name t, 
                                      c.db_name db, c.directory dir, cru.status status
                                FROM $TABLECOURS c, $TABLECOURSUSER cru
                                WHERE cru.user_id='".$_user['user_id']."'
                                AND c.code=cru.course_code";
        $result = api_sql_query($sql_select_courses);
        while ($row = Database::fetch_array($result))
        {
                // we only need the database name of the course
                $courses[] = array ("db" => $row['db'], "code" => $row['k'], "visual_code" => $row['vc'], "title" => $row['i'], "directory" => $row['dir'], "status" => $row['status']);
        }
        return $courses;
 }




/**
 * This function finds all the courses of the user and returns an array containing the
 * database name of the courses.
 */
function get_courses_of_user()
{
	global $TABLECOURS;
	global $TABLECOURSUSER;
	global $_user;
	$sql_select_courses = "SELECT course.code k, course.visual_code  vc,
									course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status
			                        FROM    $TABLECOURS       course,
											$TABLECOURSUSER   course_rel_user
			                        WHERE course.code = course_rel_user.course_code
			                        AND   course_rel_user.user_id = '".$_user['user_id']."'";
	$result = api_sql_query($sql_select_courses);
	while ($row = Database::fetch_array($result))
	{
		// we only need the database name of the course
		$courses[] = array ("db" => $row['db'], "code" => $row['k'], "visual_code" => $row['vc'], "title" => $row['i'], "directory" => $row['dir'], "status" => $row['status']);
	}
	return $courses;
}
/**
 * This function retrieves all the personal agenda items and add them to the agenda items found by the other functions.
 */
function get_personal_agenda_items($agendaitems, $day = "", $month = "", $year = "", $week = "", $type)
{
	global $tbl_personal_agenda;
	global $_user;
	global $_configuration;
	// 1. creating the SQL statement for getting the personal agenda items in MONTH view
	if ($type == "month_view" or $type == "") // we are in month view
	{
		$sql = "SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$_user['user_id']."' and MONTH(date)='".$month."' AND YEAR(date) = '".$year."'  ORDER BY date ASC";
	}
	// 2. creating the SQL statement for getting the personal agenda items in WEEK view
	if ($type == "week_view") // we are in week view
	{
		$start_end_day_of_week = calculate_start_end_of_week($week, $year);
		$start_day = $start_end_day_of_week['start']['day'];
		$start_month = $start_end_day_of_week['start']['month'];
		$start_year = $start_end_day_of_week['start']['year'];
		$end_day = $start_end_day_of_week['end']['day'];
		$end_month = $start_end_day_of_week['end']['month'];
		$end_year = $start_end_day_of_week['end']['year'];
		// in sql statements you have to use year-month-day for date calculations
		$start_filter = $start_year."-".$start_month."-".$start_day." 00:00:00";
		$end_filter = $end_year."-".$end_month."-".$end_day." 23:59:59";
		$sql = " SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$_user['user_id']."'
								AND date>='".$start_filter."' AND date<='".$end_filter."'";
	}
	// 3. creating the SQL statement for getting the personal agenda items in DAY view
	if ($type == "day_view") // we are in day view
	{
		// we could use mysql date() function but this is only available from 4.1 and higher
		$start_filter = $year."-".$month."-".$day." 00:00:00";
		$end_filter = $year."-".$month."-".$day." 23:59:59";
		$sql = " SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$_user['user_id']."' AND date>='".$start_filter."' AND date<='".$end_filter."'";
	}
	//echo "day:".$day."/";
	//echo "month:".$month."/";
	//echo "year:".$year."/";
	//echo "week:".$week."/";
	//echo $type."<p>";
	//echo "<pre>".$sql."</pre>";
	
	global $_configuration;
   	$root_url = $_configuration['root_web'];
	if ($_configuration['multiple_access_urls']==true) {
		$access_url_id = api_get_current_access_url_id();				
		if ($access_url_id != -1 ){
			$url = api_get_access_url($access_url_id); 				
			$root_url = $url['url'];
		}		
	}
	
	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($item = Database::fetch_array($result))
	{
		// we break the date field in the database into a date and a time part
		$agenda_db_date = explode(" ", $item[date]);
		$date = $agenda_db_date[0];
		$time = $agenda_db_date[1];
		// we divide the date part into a day, a month and a year
		$agendadate = explode("-", $date);
		$year = intval($agendadate[0]);
		$month = intval($agendadate[1]);
		$day = intval($agendadate[2]);
		// we divide the time part into hour, minutes, seconds
		$agendatime = explode(":", $time);
		$hour = $agendatime[0];
		$minute = $agendatime[1];
		$second = $agendatime[2];
		// if the student has specified a course we a add a link to that course
		if ($item['course'] <> "")
		{
			$url = $root_url."main/calendar/agenda.php?cidReq=".urlencode($item['course'])."&amp;day=$day&amp;month=$month&amp;year=$year#$day"; // RH  //Patrick Cool: to highlight the relevant agenda item
			$course_link = "<a href=\"$url\" title=\"".$item['course']."\">".$item['course']."</a>";
		}
		else
		{
			$course_link = "";
		}
		// Creating the array that will be returned. If we have week or month view we have an array with the date as the key
		// if we have a day_view we use a half hour as index => key 33 = 16h30
		if ($type !== "day_view") // This is the array construction for the WEEK or MONTH view
		{
			$agendaitems[$day] .= "<div><i>$hour:$minute</i> $course_link  <a href=\"myagenda.php?action=view&amp;view=personal&amp;day=$day&amp;month=$month&amp;year=$year&amp;id=".$item['id']."#".$item['id']."\" class=\"personal_agenda\">".$item['title']."</a></div>";
		}
		else // this is the array construction for the DAY view
			{
			$halfhour = 2 * $agendatime['0'];
			if ($agendatime['1'] >= '30')
			{
				$halfhour = $halfhour +1;
			}
			$agendaitems[$halfhour] .= "<div><i>$hour:$minute</i> $course_link  <a href=\"myagenda.php?action=view&amp;view=personal&amp;day=$day&amp;month=$month&amp;year=$year&amp;id=".$item['id']."#".$item['id']."\" class=\"personal_agenda\">".$item['title']."</a></div>";
		}
	}
	//print_r($agendaitems);
	return $agendaitems;
}
/**
 * This function retrieves one personal agenda item returns it.
 * @param	int	The agenda item ID
 * @return 	array	The results of the database query, or null if not found
 */
function get_personal_agenda_item($id)
{
	$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
	$id = Database::escape_string($id);
	// make sure events of the personal agenda can only be seen by the user himself
	$user = api_get_user_id();
	$sql = " SELECT * FROM ".$tbl_personal_agenda." WHERE id=".$id." AND user = ".$user;
	$result = api_sql_query($sql, __FILE__, __LINE__);
	if(Database::num_rows($result)==1)
	{
		$item = Database::fetch_array($result);
	}
	else
	{
		$item = null;
	}
	return $item;
}
/**
 * This function retrieves all the personal agenda items of the user and shows
 * these items in one list (ordered by date and grouped by month (the month_bar)
 */
function show_personal_agenda()
{
	global $tbl_personal_agenda;
	global $MonthsLong, $charset;
	global $_user;
	// The SQL statement that retrieves all the personal agenda items of this user
	$sql = "SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$_user['user_id']."' ORDER BY date DESC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	// variable initialisation
	$month_bar = "";
	// setting the default day, month and year
	if (!$_GET['day'] AND !$_GET['month'] AND !$_GET['year'])
	{
		$today = getdate();
		$year = $today['year'];
		$month = $today['mon'];
		$day = $today['mday'];
	}
	$export_icon = 'export.png';
	$export_icon_low = 'export_low_fade.png';
	$export_icon_high = 'export_high_fade.png';

	// starting the table output
	echo "<table class=\"data_table\">\n";

	if (Database::num_rows($result) > 0)
	{
		while ($myrow = Database::fetch_array($result))
		{
			/*--------------------------------------------------
					display: the month bar
			  --------------------------------------------------*/
			if ($month_bar != date("m", strtotime($myrow["date"])).date("Y", strtotime($myrow["date"])))
			{
				$month_bar = date("m", strtotime($myrow["date"])).date("Y", strtotime($myrow["date"]));
				echo "<tr><th class=\"title\" colspan=\"2\" class=\"month\" valign=\"top\">".$MonthsLong[date("n", strtotime($myrow["date"])) - 1]." ".date("Y", strtotime($myrow["date"]))."</th></tr>\n";
			}
			// highlight: if a date in the small calendar is clicked we highlight the relevant items
			$db_date = (int) date("d", strtotime($myrow["date"])).date("n", strtotime($myrow["date"])).date("Y", strtotime($myrow["date"]));
			if ($_GET["day"].$_GET["month"].$_GET["year"] <> $db_date)
			{
				$style = "data";
				$text_style = "text";
			}
			else
			{
				$style = "datanow";
				$text_style = "text";
			}
			/*--------------------------------------------------
			 			display: date and time
			  --------------------------------------------------*/
			echo "\t<tr>\n\t\t";
			echo '<td class="'.$style.'">';
			// adding an internal anchor
			echo "<a name=\"".$myrow["id"]."\"></a>";
			echo date("d", strtotime($myrow["date"]))." ".$MonthsLong[date("n", strtotime($myrow["date"])) - 1]." ".date("Y", strtotime($myrow["date"]))."&nbsp;";
			echo ucfirst(strftime(get_lang("timeNoSecFormat"), strtotime($myrow["date"])));
			echo "</td>";
			echo '<td class="'.$style.'">';
			echo '<a class="ical_export" href="ical_export.php?type=personal&id='.$myrow['id'].'&class=confidential" title="'.get_lang('ExportiCalConfidential').'">'.Display::return_icon($export_icon_high, get_lang('ExportiCalConfidential')).'</a>';
			echo '<a class="ical_export" href="ical_export.php?type=personal&id='.$myrow['id'].'&class=private" title="'.get_lang('ExportiCalPrivate').'">'.Display::return_icon($export_icon_low, get_lang('ExportiCalPrivate')).'</a>';
			echo '<a class="ical_export" href="ical_export.php?type=personal&id='.$myrow['id'].'&class=public" title="'.get_lang('ExportiCalPublic').'">'.Display::return_icon($export_icon, get_lang('ExportiCalPublic')).'</a>';
			echo "\n\t\t</td>\n\t";
			echo "</tr>";
			/*--------------------------------------------------
			 			display: the title
			  --------------------------------------------------*/
			echo "<tr>";
			echo '<td class="'.$style.'" colspan="2">';
			echo $myrow['title'];
			echo "\n\t\t</td>\n\t";
			echo "</tr>\n";
			/*--------------------------------------------------
			 			display: the content
			  --------------------------------------------------*/
			$content = $myrow['text'];
			$content = make_clickable($content);
			$content = text_filter($content);
			echo "\t<tr>\n\t\t<td class=\"".$text_style."\" colspan='2'>";
			echo $content;
			echo "</td></tr>";
			/*--------------------------------------------------
			 			display: the edit / delete icons
			  --------------------------------------------------*/
			echo "\t<tr>\n\t\t<td class=\"".$text_style."\" colspan='2'>";
			echo "<a href=\"myagenda.php?action=edit_personal_agenda_item&amp;id=".$myrow['id']."\">".Display::return_icon('edit.gif', get_lang('Edit'))."</a>";
			echo "<a href=\"".api_get_self()."?action=delete&amp;id=".$myrow['id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."')) return false;\">".Display::return_icon('delete.gif', get_lang('Delete'))."</a>";
			echo "</td></tr>";
		}
	}
	else
	{
		echo '<tr><td colspan="2">'.get_lang('NoAgendaItems').'</td></tr>';
	}
	echo "</table>\n";
}

/**
 * This function retrieves all the personal agenda items of the given user_id and shows
 * these items in one list (ordered by date and grouped by month (the month_bar)
 * @param int user id
 */
function show_simple_personal_agenda($user_id)
{
	$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
	global $MonthsLong, $charset;
	
	// The SQL statement that retrieves all the personal agenda items of this user
	$sql = "SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$user_id."' ORDER BY date DESC";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	// variable initialisation
	$month_bar = "";
	// setting the default day, month and year
	if (!$_GET['day'] AND !$_GET['month'] AND !$_GET['year']) {
		$today = getdate();
		$year = $today['year'];
		$month = $today['mon'];
		$day = $today['mday'];
	}
	$export_icon = 'export.png';
	$export_icon_low = 'export_low_fade.png';
	$export_icon_high = 'export_high_fade.png';
	$content = '';
	// starting the table output
	if (Database::num_rows($result) > 0) {
		while ($myrow = Database::fetch_array($result)) {
			/*--------------------------------------------------
					display: the month bar
			  --------------------------------------------------*/
			if ($month_bar != date("m", strtotime($myrow["date"])).date("Y", strtotime($myrow["date"]))) {
				$month_bar = date("m", strtotime($myrow["date"])).date("Y", strtotime($myrow["date"]));
				$content.= $MonthsLong[date("n", strtotime($myrow["date"])) - 1]." ".date("Y", strtotime($myrow["date"]));
			}
			// highlight: if a date in the small calendar is clicked we highlight the relevant items
			$db_date = (int) date("d", strtotime($myrow["date"])).date("n", strtotime($myrow["date"])).date("Y", strtotime($myrow["date"]));
			if ($_GET["day"].$_GET["month"].$_GET["year"] <> $db_date) {
				$style = "data";
				$text_style = "text";
			} else {
				$style = "datanow";
				$text_style = "text";
			}
			/*--------------------------------------------------
			 			display: date and time
			  --------------------------------------------------*/					
			// adding an internal anchor			
			$content.= date("d", strtotime($myrow["date"]))." ".$MonthsLong[date("n", strtotime($myrow["date"])) - 1]." ".date("Y", strtotime($myrow["date"]))."&nbsp;";
			$content.= ucfirst(strftime(get_lang("timeNoSecFormat"), strtotime($myrow["date"])));
			
			/*--------------------------------------------------
			 			display: the title
			  --------------------------------------------------*/
			$content.= '<br />';						
			$content.= $myrow['title'];
			$content.= '<br />';
			
			/*--------------------------------------------------
			 			display: the content
			  --------------------------------------------------*/
			  /*
			$content = $myrow['title'];
			$content = make_clickable($content);
			$content = text_filter($content);*/		
			return $content;						
		}
	} else {
		return $content;
	}
	
}

/**
 * This function deletes a personal agenda item
 * There is an additional check to make sure that one cannot delete an item that
 * does not belong to him/her
 */
function delete_personal_agenda($id)
{
	global $tbl_personal_agenda;
	global $_user;
	if ($id <> '')
	{
		$sql = "SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$_user['user_id']."' AND id='".$id."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$aantal = Database::num_rows($result);
		if ($aantal <> 0)
		{
			$sql = "DELETE FROM ".$tbl_personal_agenda." WHERE user='".$_user['user_id']."' AND id='".$id."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
		}
	}
}
/**
 * Get personal agenda items between two dates (=all events from all registered courses)
 * @param	int		user ID of the user
 * @param	string	Optional start date in datetime format (if no start date is given, uses today)
 * @param	string	Optional end date in datetime format (if no date is given, uses one year from now)
 * @return	array	Array of events ordered by start date, in [0]('datestart','dateend','title'),[1]('datestart','dateend','title','link','coursetitle') format, where datestart and dateend are in yyyyMMddhhmmss format.
 * @TODO Implement really personal events (from user DB) and global events (from main DB)
 */
function get_personal_agenda_items_between_dates($user_id, $date_start='', $date_end='') {
	$items = array ();
	if ($user_id != strval(intval($user_id))) { return $items; }
	if (empty($date_start)) { $date_start = date('Y-m-d H:i:s');}
	if (empty($date_end))   { $date_end = date('Y-m-d H:i:s',mktime(0, 0, 0, date("m"),   date("d"),   date("Y")+1));}
	$expr = '/\d{4}-\d{2}-\d{2}\ \d{2}:\d{2}:\d{2}/';
	if(!preg_match($expr,$date_start)) { return $items; }
	if(!preg_match($expr,$date_end)) { return $items; }
	
	// get agenda-items for every course
	$courses = api_get_user_courses($user_id,false);
	foreach ($courses as $id => $course)
	{		
		$c = api_get_course_info($course['code']);
		//databases of the courses
		$t_a = Database :: get_course_table(TABLE_AGENDA, $course['db']);
		$t_ip = Database :: get_course_table(TABLE_ITEM_PROPERTY, $course['db']);

		$group_memberships = GroupManager :: get_group_ids($course['db'], $user_id);
		// if the user is administrator of that course we show all the agenda items
		if ($course['status'] == '1')
		{
			//echo "course admin";
			$sqlquery = "SELECT
										DISTINCT agenda.*, item_property.*
										FROM ".$t_a." agenda,
											 ".$t_ip." item_property
										WHERE agenda.id = item_property.ref 
										AND agenda.start_date>='$date_start'
										AND agenda.end_date<='$date_end'
										AND item_property.tool='".TOOL_CALENDAR_EVENT."'
										AND item_property.visibility='1'
										GROUP BY agenda.id
										ORDER BY start_date ";
		}
		// if the user is not an administrator of that course
		else
		{
			//echo "NOT course admin";
			if (is_array($group_memberships) && count($group_memberships)>0)
			{
				$sqlquery = "SELECT
													agenda.*, item_property.*
													FROM ".$t_a." agenda,
														".$t_ip." item_property
													WHERE agenda.id = item_property.ref
													AND agenda.start_date>='$date_start'
													AND agenda.end_date<='$date_end'
													AND item_property.tool='".TOOL_CALENDAR_EVENT."'
													AND	( item_property.to_user_id='".$user_id."' OR item_property.to_group_id IN (0, ".implode(", ", $group_memberships).") )
													AND item_property.visibility='1'
													ORDER BY start_date ";
			} else {
				$sqlquery = "SELECT
													agenda.*, item_property.*
													FROM ".$t_a." agenda,
														".$t_ip." item_property
													WHERE agenda.id = item_property.ref
													AND agenda.start_date>='$date_start'
													AND agenda.end_date<='$date_end'
													AND item_property.tool='".TOOL_CALENDAR_EVENT."'
													AND ( item_property.to_user_id='".$user_id."' OR item_property.to_group_id='0')
													AND item_property.visibility='1'
													ORDER BY start_date ";
			}
		}

		$result = api_sql_query($sqlquery, __FILE__, __LINE__);
		while ($item = Database::fetch_array($result))
		{
			$agendaday = date("j",strtotime($item['start_date']));
			$URL = api_get_path(WEB_PATH)."main/calendar/agenda.php?cidReq=".urlencode($course["code"])."&amp;day=$agendaday&amp;month=$month&amp;year=$year#$agendaday";
			list($year,$month,$day,$hour,$min,$sec) = split('[-: ]',$item['start_date']);
			$start_date = $year.$month.$day.$hour.$min;
			list($year,$month,$day,$hour,$min,$sec) = split('[-: ]',$item['end_date']);
			$end_date = $year.$month.$day.$hour.$min;
			
			$items[] = array(
				'datestart'=>$start_date,
				'dateend'=>$end_date,
				'title'=>$item['title'],
				'link'=>$URL,
				'coursetitle'=>$c['name'],
			);
		}
	}
	return $items;	
}
?>
