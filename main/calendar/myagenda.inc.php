<?php
/* For licensing terms, see /license.txt */
/**
    @author: Julio Montoya <gugli100@gmail.com> BeezNest 2011 Bugfixes
    
    //Original code found in Dok€os
	@author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
	@author: Toon Van Hoecke <toon.vanhoecke@ugent.be>, Ghent University
	@author: Eric Remy (initial version)
	
	@todo create a class and merge with the agenda.inc.php
*/

/**
 * Settings (you may alter this at will
 */
$setting_agenda_link = 'coursecode'; // valid values are coursecode and icon

require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';


/**
 *	This function retrieves all the agenda items of all the courses the user is subscribed to
 */
function get_myagendaitems($user_id, $courses_dbs, $month, $year) {	
	global $setting_agenda_link;
	$user_id = intval($user_id);

	$items = array();
	$my_list = array();
	
	// get agenda-items for every course
	foreach ($courses_dbs as $key => $array_course_info) {
		//databases of the courses
		$TABLEAGENDA = Database :: get_course_table(TABLE_AGENDA, $array_course_info["db_name"]);
		$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY, $array_course_info["db_name"]);

		$group_memberships = GroupManager :: get_group_ids($array_course_info["db_name"], $user_id);
		$course_user_status = CourseManager::get_user_in_course_status($user_id, $array_course_info["code"]);
		// if the user is administrator of that course we show all the agenda items
		if ($course_user_status == '1') {
			//echo "course admin";
			$sqlquery = "SELECT DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
							FROM ".$TABLEAGENDA." agenda,
								 ".$TABLE_ITEMPROPERTY." ip
							WHERE agenda.id = ip.ref
							AND MONTH(agenda.start_date)='".$month."'
							AND YEAR(agenda.start_date)='".$year."'
							AND ip.tool='".TOOL_CALENDAR_EVENT."'
							AND ip.visibility='1'
							GROUP BY agenda.id
							ORDER BY start_date ";
		} else {
			// if the user is not an administrator of that course
			if (is_array($group_memberships) && count($group_memberships)>0) {
				$sqlquery = "SELECT	agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
								FROM ".$TABLEAGENDA." agenda,
									".$TABLE_ITEMPROPERTY." ip
								WHERE agenda.id = ip.ref
								AND MONTH(agenda.start_date)='".$month."'
								AND YEAR(agenda.start_date)='".$year."'
								AND ip.tool='".TOOL_CALENDAR_EVENT."'
								AND	( ip.to_user_id='".$user_id."' OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") )
								AND ip.visibility='1'
								ORDER BY start_date ";
			} else {
				$sqlquery = "SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
								FROM ".$TABLEAGENDA." agenda,
									".$TABLE_ITEMPROPERTY." ip
								WHERE agenda.id = ip.ref
								AND MONTH(agenda.start_date)='".$month."'
								AND YEAR(agenda.start_date)='".$year."'
								AND ip.tool='".TOOL_CALENDAR_EVENT."'
								AND ( ip.to_user_id='".$user_id."' OR ip.to_group_id='0')
								AND ip.visibility='1'
								ORDER BY start_date ";
				}
		}
		$result = Database::query($sqlquery);
		
		while ($item = Database::fetch_array($result, 'ASSOC')) {
			$agendaday = -1;
			if ($item['start_date'] != '0000-00-00 00:00:00') {
				$item['start_date'] = api_get_local_time($item['start_date']);
				$item['start_date_tms']  = api_strtotime($item['start_date']);
				$agendaday = date("j", $item['start_date_tms']);				
			}
			if ($item['end_date'] != '0000-00-00 00:00:00') {
				$item['end_date'] = api_get_local_time($item['end_date']);
			}			
			
			$url  = api_get_path(WEB_CODE_PATH)."calendar/agenda.php?cidReq=".urlencode($array_course_info["code"])."&day=$agendaday&month=$month&year=$year#$agendaday";
			
			$item['url'] = $url;
			$item['course_name'] = $array_course_info['title'];	
			$item['calendar_type'] = 'course';			
			$my_list[$agendaday][] = $item;					
		}
	}
	
	// sorting by hour for every day
	$agendaitems = array ();
	while (list ($agendaday, $tmpitems) = each($items)) {
		if(!isset($agendaitems[$agendaday])) {
			$agendaitems[$agendaday] = '';
		}
		sort($tmpitems);
		while (list ($key, $val) = each($tmpitems)) {
			$agendaitems[$agendaday] .= $val;
		}
	}	
	return $my_list;
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
function display_mymonthcalendar($user_id, $agendaitems, $month, $year, $weekdaynames = array(), $monthName, $show_content = true) {    
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
	
	$prev_icon = Display::return_icon('action_prev.png',get_lang('Previous'));
	$next_icon = Display::return_icon('action_next.png',get_lang('Next'));
	
	$next_month = ($month == 1 ? 12 : $month -1);
	$prev_month = ($month == 12 ? 1 : $month +1);
	
	$next_year = ($month == 1 ? $year -1 : $year);
	$prev_year = ($month == 12 ? $year +1 : $year);
	
	if ($show_content)  {
	    $back_url = Display::url($prev_icon, api_get_self()."?coursePath=".urlencode($course_path)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;action=view&amp;view=month&amp;month=".$next_month."&amp;year=".$next_year);	    
	    $next_url = Display::url($next_icon, api_get_self()."?coursePath=".urlencode($course_path)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;action=view&amp;view=month&amp;month=".$prev_month."&amp;year=".$prev_year);
	} else {
        $back_url = Display::url($prev_icon, '', array('onclick'=>"load_calendar('".$user_id."','".$next_month."', '".$next_year."'); "));	    
	    $next_url = Display::url($next_icon, '', array('onclick'=>"load_calendar('".$user_id."','".$prev_month."', '".$prev_year."'); "));	    
	}

	echo '<table id="agenda_list"><tr>';
	echo '<th width="10%">'.$back_url.'</th>';
	echo '<th width="80%" colspan="5"><br /><h3>'.$monthName." ".$year.'</h3></th>';
	echo '<th width="10%">'.$next_url.'</th>';
		
	echo '</tr>';

	echo '<tr>';
	for ($ii = 1; $ii < 8; $ii ++) {
		echo '<td class="weekdays">'.$DaysShort[$ii % 7].'</td>';
	}
	echo '</tr>';
	
	$curday = -1;
	$today = getdate();
	while ($curday <= $numberofdays[$month]) {
		echo "<tr>";
		for ($ii = 0; $ii < 7; $ii ++) {
			if (($curday == -1) && ($ii == $startdayofweek)) {
				$curday = 1;
			}
			if (($curday > 0) && ($curday <= $numberofdays[$month])) {
				$bgcolor = $class = 'class="days_week"';
				$dayheader = Display::div($curday, array('class'=>'agenda_day'));
				if (($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon'])) {					
					$class = "class=\"days_today\" style=\"width:10%;\"";
				}
				
				echo "<td ".$class.">".$dayheader;
				
				if (!empty($agendaitems[$curday])) {			        
				   $items =  $agendaitems[$curday];
				   $items =  msort($items, 'start_date_tms');
				   
				   foreach($items  as $value) {
				        $value['title'] = Security::remove_XSS($value['title']);
                        $start_time = api_format_date($value['start_date'], TIME_NO_SEC_FORMAT);
                        $end_time = '';
                        
                        if (!empty($value['end_date']) && $value['end_date'] != '0000-00-00 00:00:00') {
                           $end_time    = '-&nbsp;<i>'.api_format_date($value['end_date'], DATE_TIME_FORMAT_LONG).'</i>';
                        }       
                        $complete_time = '<i>'.api_format_date($value['start_date'], DATE_TIME_FORMAT_LONG).'</i>&nbsp;'.$end_time;  
                        $time = '<i>'.$start_time.'</i>';

				        switch($value['calendar_type']) {
                            case 'personal':
                                $bg_color = '#D0E7F4';                                          
                                $icon = Display::return_icon('user.png', get_lang('MyAgenda'), array(), 22);          
                                break;
                            case 'global':
                                $bg_color = '#FFBC89';
                                $icon = Display::return_icon('view_remove.png', get_lang('GlobalEvent'), array(), 22);
                                break;
                            case 'course':
                                $bg_color = '#CAFFAA';
                                if ($show_content) {
                                    $icon = Display::url(Display::return_icon('course.png', $value['course_name'].' '.get_lang('Course'), array(), 22), $value['url']);
                                } else {
                                    $icon = Display::return_icon('course.png', $value['course_name'].' '.get_lang('Course'), array(), 22);
                                }                                                                                  
                                break;              
                            default:
                                break;
                        }   
                        
                        $result = '<div class="rounded_div_agenda" style="background-color:'.$bg_color.';">';
                        
                        if ($show_content) {
    				        
                            //Setting a personal event to green
                            $icon = Display::div($icon, array('style'=>'float:right'));                            
                            
                            //Link to bubble                                                
                            $url = Display::url(cut($value['title'], 40), '#', array('id'=>$value['calendar_type'].'_'.$value['id'], 'class'=>'opener'));                                 
                            $result .= $time.' '.$icon.' '.Display::div($url);
                            
                            //Hidden content
                            $content = Display::div($icon.Display::tag('h1', $value['title']).$complete_time.'<hr />'.Security::remove_XSS($value['content']));
                            
                            //Main div
                            $result .= Display::div($content, array('id'=>'main_'.$value['calendar_type'].'_'.$value['id'], 'class' => 'dialog', 'style' => 'display:none'));
                            $result .= '</div>';                        
                            echo $result;
                                                        
                            //echo Display::div($content, array('id'=>'main_'.$value['calendar_type'].'_'.$value['id'], 'class' => 'dialog'));
                        } else {                        	
                            echo $result .= $icon.'</div>';
                        }
				   }
				}
				echo "</td>";
				$curday ++;
			} else {
				echo "<td></td>";
			}
		}
		echo "</tr>";
	}
	echo "</table>";
}
/**
 * Show the mini calender of the given month
 */
function display_myminimonthcalendar($agendaitems, $month, $year, $monthName) {
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
	$backwardsURL = api_get_self()."?coursePath=".Security::remove_XSS($course_path)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;month=". ($month == 1 ? 12 : $month -1)."&amp;year=". ($month == 1 ? $year -1 : $year);
	$forewardsURL = api_get_self()."?coursePath=".Security::remove_XSS($course_path)."&amp;courseCode=".Security::remove_XSS($g_cc)."&amp;month=". ($month == 12 ? 1 : $month +1)."&amp;year=". ($month == 12 ? $year +1 : $year);

	echo "<table class=\"data_table\">", "<tr>", "<th width=\"10%\"><a href=\"", $backwardsURL, "\">".Display::return_icon('action_prev.png',get_lang('Previous'))."</a></th>";
	echo "<th width=\"80%\" colspan=\"5\">", $monthName, " ", $year, "</th>", "<th width=\"10%\"><a href=\"", $forewardsURL, "\">".Display::return_icon('action_next.png',get_lang('Next'))."</a></th>", "</tr>";

	echo "<tr>";
	for ($ii = 1; $ii < 8; $ii ++)
	{
		echo "<td class=\"weekdays\">", $DaysShort[$ii % 7], "</td>";
	}
	echo "</tr>";
	$curday = -1;
	$today = getdate();
	while ($curday <= $numberofdays[$month])
	{
		echo "<tr>";
		for ($ii = 0; $ii < 7; $ii ++) {
			if (($curday == -1) && ($ii == $startdayofweek))
			{
				$curday = 1;
			}
			if (($curday > 0) && ($curday <= $numberofdays[$month])) {
				$bgcolor = $ii < 5 ? $class = 'class="days_week"' : $class = 'class="days_weekend"';
				$dayheader = "$curday";
				if (($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon'])) {
					$dayheader = "$curday";
					$class = "class=\"days_today\"";
				}
				echo "<td ".$class.">";
				if (!empty($agendaitems[$curday])) {
					echo "<a href=\"".api_get_self()."?action=view&amp;view=day&amp;day=".$curday."&amp;month=".$month."&amp;year=".$year."\">".$dayheader."</a>";
				} else {
					echo $dayheader;
				}
				// "a".$dayheader." <span class=\"agendaitem\">".$agendaitems[$curday]."</span>";
				echo "</td>";
				$curday ++;
			}
			else
			{
				echo "<td>&nbsp;</td>";
			}
		}
		echo "</tr>";
	}
	echo "</table>";
}

/**
 * This function shows all the forms that are needed form adding /editing a new personal agenda item
 * when there is no $id passed in the function we are adding a new agenda item, if there is a $id
 * we are editing
 * attention: we have to check that the student is editing an item that belongs to him/her
 */
function show_new_personal_item_form($id = "") {
	global $year, $MonthsLong;

	$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	// we construct the default time and date data (used if we are not editing a personal agenda item)
	//$today = getdate();
	
	$current_date = api_strtotime(api_get_local_time());
	
	$year = date('Y', $current_date);
	$month = date('m', $current_date);
	$day = date('d', $current_date);	
	$hours = date('H', $current_date);	
	$minutes = date('i', $current_date);
	
	//echo date('Y', $current_date);
	/*
	$day = $today['mday'];
	$month = $today['mon'];
	$year = $today['year'];
	$hours = $today['hours'];
	$minutes = $today['minutes'];*/

	$content=stripslashes($content);
	$title=stripslashes($title);
	// if an $id is passed to this function this means we are editing an item
	// we are loading the information here (we do this after everything else
	// to overwrite the default information)

	if (strlen($id) > 0 && $id != strval(intval($id))) {
		return false; //potential SQL injection
	}

	if ($id != "") {
		$sql = "SELECT date, title, text FROM ".$tbl_personal_agenda." WHERE user='".api_get_user_id()."' AND id='".$id."'";
		$result = Database::query($sql);
		$aantal = Database::num_rows($result);
		if ($aantal != 0) {
			$row	= Database::fetch_array($result);			
			$row['date'] = api_get_local_time($row['date']);
			$year 	= substr($row['date'], 0, 4);
			$month 	= substr($row['date'], 5, 2);
			$day 	= substr($row['date'], 8, 2);
			$hours 	= substr($row['date'], 11, 2);
			$minutes= substr($row['date'], 14, 2);
			
			$title 	= $row['title'];
			$content= $row['text'];
		} else {
			return false;
		}
	}

	echo '<form method="post" action="myagenda.php?action=add_personal_agenda_item&id='.$id.'" name="newedit_form">';
	echo '<div id="newedit_form">';
	echo '<h2>';
	echo ($_GET['action'] == 'edit_personal_agenda_item') ? get_lang("ModifyPersonalCalendarItem") : get_lang("AddPersonalCalendarItem");
	echo '</h2>';
	echo '<div>';

	echo '<br/>';
	echo ''.get_lang("Date").':	';

	// ********** The form containing the days (0->31) ********** \\
	echo '<select name="frm_day">';
	// small loop for filling all the dates
	// 2do: the available dates should be those of the selected month => february is from 1 to 28 (or 29) and not to 31
	for ($i = 1; $i <= 31; $i ++) {
		// values have to have double digits
		if ($i <= 9){
			$value = "0".$i;
		} else {
			$value = $i;
		}
		// the current day is indicated with [] around the date
		if ($value == $day) {
			echo '<option value='.$value.' selected>'.$i.'</option>';
		} else {
			echo '<option value='.$value.'>'.$i.'</option>';
		}
	}
	echo '</select>';
	// ********** The form containing the months (jan->dec) ********** \\
	echo '<!-- month: january -> december -->';
	echo '<select name="frm_month">';
	for ($i = 1; $i <= 12; $i ++) {
		// values have to have double digits
		if ($i <= 9) {
			$value = "0".$i;
		} else {
			$value = $i;
		}
		// the current month is indicated with [] around the month name
		if ($value == $month) {
			echo '<option value='.$value.' selected>'.$MonthsLong[$i -1].'</option>';
		} else {
			echo '<option value='.$value.'>'.$MonthsLong[$i -1].'</option>';
		}
	}
	echo '</select>';
	// ********** The form containing the years ********** \\
	echo '<!-- year -->';
	echo '<select name="frm_year">';
	echo '<option value='. ($year -1).'>'. ($year -1).'</option>';
	echo '<option value='.$year.' selected>'.$year.'</option>';
	for ($i = 1; $i <= 5; $i ++)
	{
		$value = $year + $i;
		echo '<option value='.$value.'>'.$value.'</option>';
	}
	echo '</select>&nbsp;&nbsp;';
	echo "<a title=\"Kalender\" href=\"javascript:openCalendar('newedit_form', 'frm_')\">".Display::return_icon('calendar_select.gif', get_lang('Select'), array ('style' => 'vertical-align: middle;'))."</a>";
	echo '&nbsp;&nbsp;';
	// ********** The form containing the hours  (00->23) ********** \\
	echo '<!-- time: hour -->';
	echo get_lang("Time").': ';
	echo '<select name="frm_hour">';
	for ($i = 1; $i <= 24; $i ++) {
		// values have to have double digits
		if ($i <= 9) {
			$value = "0".$i;
		} else {
			$value = $i;
		}
		// the current hour is indicated with [] around the hour
		if ($hours == $value) {
			echo '<option value='.$value.' selected>'.$value.'</option>';
		} else {
			echo '<option value='.$value.'> '.$value.' </option>';
		}
	}
	echo '</select>';
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
	echo '</select>';
	echo '</div><br/>';
	// ********** The title field ********** \\
	echo '<div>';
	echo ''.get_lang('Title').' : <input type="text" name="frm_title" size="50" value="'.$title.'" />';
	echo '</div>';
	// ********** The text field ********** \\
	echo '<br /><div class="formw">';
	
	require_once api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php";

	$oFCKeditor = new FCKeditor('frm_content') ;

	$oFCKeditor->Width		= '80%';
	$oFCKeditor->Height		= '200';

	if(!api_is_allowed_to_edit(null,true)) {
		$oFCKeditor->ToolbarSet = 'AgendaStudent';
	} else {
		$oFCKeditor->ToolbarSet = 'Agenda';
	}
	$oFCKeditor->Value		= $content;
	$return =	$oFCKeditor->CreateHtml();
	echo $return;
	
	echo '</div>';
	// ********** The Submit button********** \\
	echo '<div>';
	echo '<br /><button type="submit" class="add" name="Submit" value="'.get_lang('AddEvent').'" >'.get_lang('AddEvent').'</button>';
	echo '</div>';
	echo '</div>';
	echo '</form>';
}

/**
 * This function shows all the forms that are needed form adding/editing a new personal agenda item
 * @param date is the time in day
 * @param date is the time in month
 * @param date is the time in year
 * @param date is the time in hour
 * @param date is the time in minute
 * @param string is the agenda title
 * @param string is the content
 * @param int is the id this param is optional, but is necessary if the item require be edited
 */
function store_personal_item($day, $month, $year, $hour, $minute, $title, $content, $id = "") {

	$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	//constructing the date
	$date = $year."-".$month."-".$day." ".$hour.":".$minute.":00";
	
    if (!empty($date)) {
        $date = api_get_utc_datetime($date);
    }
        
	$date = Database::escape_string($date);
	$title = Database::escape_string($title);
	$content = Database::escape_string($content);
	$id = intval($id);
	
	if (!empty($id)) { 
	    // we are updating
		$sql = "UPDATE ".$tbl_personal_agenda." SET user='".api_get_user_id()."', title='".$title."', text='".$content."', date='".$date."' WHERE id= ".$id;
	} else { 
	    // we are adding a new item
		$sql = "INSERT INTO $tbl_personal_agenda (user, title, text, date) VALUES ('".api_get_user_id()."','$title', '$content', '$date')";		
	}
	$result = Database::query($sql);
}

/**
 * This function finds all the courses (also those of sessions) of the user and returns an array containing the
 * database name of the courses.
 * Xritten by Noel Dieschburg <noel.dieschburg@dokeos.com>
 * @todo remove this function and use the CourseManager get_courses_list_by_user_id
 */

function get_all_courses_of_user() {
        $TABLECOURS = Database :: get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_session_course     = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_course_user= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_session                    = Database :: get_main_table(TABLE_MAIN_SESSION);
        $sql_select_courses = "SELECT c.code k, c.visual_code  vc, c.title i, c.tutor_name t,
                                      c.db_name db, c.directory dir, '5' as status
                                FROM $TABLECOURS c, $tbl_session_course_user srcu
                                WHERE srcu.id_user='".api_get_user_id()."'
                                AND c.code=srcu.course_code
                                UNION
                               SELECT c.code k, c.visual_code  vc, c.title i, c.tutor_name t,
                                      c.db_name db, c.directory dir, cru.status status
                                FROM $TABLECOURS c, $TABLECOURSUSER cru
                                WHERE cru.user_id='".api_get_user_id()."'
                                AND c.code=cru.course_code";
        $result = Database::query($sql_select_courses);
        while ($row = Database::fetch_array($result)) {
            // we only need the database name of the course
            $courses[] = array ("db" => $row['db'], "code" => $row['k'], "visual_code" => $row['vc'], "title" => $row['i'], "directory" => $row['dir'], "status" => $row['status']);
        }
        return $courses;
 }




/**
 * This function finds all the courses of the user and returns an array containing the
 * database name of the courses.
 */
function get_courses_of_user() {
	$TABLECOURS = Database :: get_main_table(TABLE_MAIN_COURSE);
	$TABLECOURSUSER = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$sql_select_courses = "SELECT course.code k, course.visual_code  vc,
									course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status
			                        FROM    $TABLECOURS       course,
											$TABLECOURSUSER   course_rel_user
			                        WHERE course.code = course_rel_user.course_code
			                        AND   course_rel_user.user_id = '".api_get_user_id()."'";
	$result = Database::query($sql_select_courses);
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
function get_personal_agenda_items($user_id, $agendaitems, $day = "", $month = "", $year = "", $week = "", $type) {
	$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
	$user_id = intval($user_id);
	
	// 1. creating the SQL statement for getting the personal agenda items in MONTH view
	if ($type == "month_view" or $type == "") {
		// we are in month view
		$sql = "SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$user_id."' and MONTH(date)='".$month."' AND YEAR(date) = '".$year."'  ORDER BY date ASC";
	}
	
	// 2. creating the SQL statement for getting the personal agenda items in WEEK view
	// we are in week view
	if ($type == "week_view") {
		$start_end_day_of_week = calculate_start_end_of_week($week, $year);
		$start_day = $start_end_day_of_week['start']['day'];
		$start_month = $start_end_day_of_week['start']['month'];
		$start_year = $start_end_day_of_week['start']['year'];
		$end_day = $start_end_day_of_week['end']['day'];
		$end_month = $start_end_day_of_week['end']['month'];
		$end_year = $start_end_day_of_week['end']['year'];
		// in sql statements you have to use year-month-day for date calculations
		$start_filter = $start_year."-".$start_month."-".$start_day." 00:00:00";
		$start_filter  = api_get_utc_datetime($start_filter);
		$end_filter = $end_year."-".$end_month."-".$end_day." 23:59:59";
		$end_filter  = api_get_utc_datetime($end_filter);
		$sql = " SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$user_id."' AND date>='".$start_filter."' AND date<='".$end_filter."'";
	}
	// 3. creating the SQL statement for getting the personal agenda items in DAY view
	if ($type == "day_view") {
		// we are in day view
		// we could use mysql date() function but this is only available from 4.1 and higher
		$start_filter = $year."-".$month."-".$day." 00:00:00";
		$start_filter  = api_get_utc_datetime($start_filter);
		$end_filter = $year."-".$month."-".$day." 23:59:59";
		$end_filter  = api_get_utc_datetime($end_filter);	
		$sql = " SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$user_id."' AND date>='".$start_filter."' AND date<='".$end_filter."'";
	}  	

	$result = Database::query($sql);
	while ($item = Database::fetch_array($result, 'ASSOC')) {		
		
		$time_minute 	= api_convert_and_format_date($item['date'], TIME_NO_SEC_FORMAT);
		$item['date']   = api_get_local_time($item['date']);
		$item['start_date_tms']  = api_strtotime($item['date']);
	    $item['content'] = $item['text'];
	    
		// we break the date field in the database into a date and a time part		
		$agenda_db_date = explode(" ", $item['date']);
		$date = $agenda_db_date[0];
		$time = $agenda_db_date[1];
		// we divide the date part into a day, a month and a year
		$agendadate = explode("-", $item['date']);
		$year = intval($agendadate[0]);
		$month = intval($agendadate[1]);
		$day = intval($agendadate[2]);
		// we divide the time part into hour, minutes, seconds
		$agendatime = explode(":", $time);
				
		$hour = $agendatime[0];
		$minute = $agendatime[1];
		$second = $agendatime[2];	
		        
        if ($type == 'month_view') {
            $item['calendar_type']  = 'personal';
            $item['start_date']  	= $item['date'];
            $agendaitems[$day][] 	= $item;
            continue;
        } 
        
		// if the student has specified a course we a add a link to that course
		if ($item['course'] <> "") {
			$url = api_get_path(WEB_CODE_PATH)."calendar/agenda.php?cidReq=".urlencode($item['course'])."&amp;day=$day&amp;month=$month&amp;year=$year#$day"; // RH  //Patrick Cool: to highlight the relevant agenda item
			$course_link = "<a href=\"$url\" title=\"".$item['course']."\">".$item['course']."</a>";
		} else {
			$course_link = "";
		}
		// Creating the array that will be returned. If we have week or month view we have an array with the date as the key
		// if we have a day_view we use a half hour as index => key 33 = 16h30
		if ($type !== "day_view") {
			// This is the array construction for the WEEK or MONTH view

			//Display events in agenda
			$agendaitems[$day] .= "<div><i>$time_minute</i> $course_link <a href=\"myagenda.php?action=view&amp;view=personal&amp;day=$day&amp;month=$month&amp;year=$year&amp;id=".$item['id']."#".$item['id']."\" class=\"personal_agenda\">".$item['title']."</a></div><br />";

		} else {
			// this is the array construction for the DAY view
			$halfhour = 2 * $agendatime['0'];
			if ($agendatime['1'] >= '30') {
				$halfhour = $halfhour +1;
			}
			
			//Display events by list
			$agendaitems[$halfhour] .= "<div><i>$time_minute</i> $course_link <a href=\"myagenda.php?action=view&amp;view=personal&amp;day=$day&amp;month=$month&amp;year=$year&amp;id=".$item['id']."#".$item['id']."\" class=\"personal_agenda\">".$item['title']."</a></div>";
		}
	}
	return $agendaitems;
}
/**
 * This function retrieves one personal agenda item returns it.
 * @param	int	The agenda item ID
 * @return 	array	The results of the database query, or null if not found
 */
function get_personal_agenda_item($id) {
	$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
	$id = Database::escape_string($id);
	// make sure events of the personal agenda can only be seen by the user himself
	$user = api_get_user_id();
	$sql = " SELECT * FROM ".$tbl_personal_agenda." WHERE id=".$id." AND user = ".$user;
	$result = Database::query($sql);
	if(Database::num_rows($result)==1) {
		$item = Database::fetch_array($result);
	} else {
		$item = null;
	}
	return $item;
}
/**
 * This function retrieves all the personal agenda items of the user and shows
 * these items in one list (ordered by date and grouped by month (the month_bar)
 */
function show_personal_agenda() {
	global $MonthsLong, $charset;

	$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	// The SQL statement that retrieves all the personal agenda items of this user
	$sql = "SELECT * FROM ".$tbl_personal_agenda." WHERE user='".api_get_user_id()."' ORDER BY date DESC";
	$result = Database::query($sql);
	// variable initialisation
	$month_bar = "";
	// setting the default day, month and year
	if (!isset($_GET['day']) AND !isset($_GET['month']) AND !isset($_GET['year'])) {
		$today = getdate();
		$year = $today['year'];
		$month = $today['mon'];
		$day = $today['mday'];
	}
	$export_icon = 'export.png';
	$export_icon_low = 'export_low_fade.png';
	$export_icon_high = 'export_high_fade.png';

	// starting the table output
	echo '<table class="data_table">';
	
	$th = Display::tag('th', get_lang('Title'));
    $th .= Display::tag('th', get_lang('Content'));
    $th .= Display::tag('th', get_lang('StartTimeWindow'));
    $th .= Display::tag('th', get_lang('Modify'));
    
    echo Display::tag('tr', $th);

	if (Database::num_rows($result) > 0) {
	    $counter = 0; 
		while ($myrow = Database::fetch_array($result)) {
			/* 	display: the month bar		*/
			if ($month_bar != date("m", strtotime($myrow["date"])).date("Y", strtotime($myrow["date"]))) {
				$month_bar = date("m", strtotime($myrow["date"])).date("Y", strtotime($myrow["date"]));
				//echo "<tr><th class=\"title\" colspan=\"2\" class=\"month\" valign=\"top\">".$MonthsLong[date("n", strtotime($myrow["date"])) - 1]." ".date("Y", strtotime($myrow["date"]))."</th></tr>";
			}
			// highlight: if a date in the small calendar is clicked we highlight the relevant items
			$db_date = (int) date("d", strtotime($myrow["date"])).date("n", strtotime($myrow["date"])).date("Y", strtotime($myrow["date"]));
			/*
			if ($_GET["day"].$_GET["month"].$_GET["year"] <> $db_date) {
				$style = "data";
				$text_style = "text";
			} else {
				$style = "datanow";
				$text_style = "text";
			}*/

		    $class = 'row_even';
            if ($counter % 2) {
                $class = 'row_odd'; 
            }
            
			echo '<tr class="'.$class.'">';			
			echo '<td>';
			/*   display: the title  */
			echo $myrow['title'];
			echo "</td>";		

			// display: the content
            $content = $myrow['text'];
            echo "<td>";
            echo $content;
            echo "</td>";
            

            //display: date and time			
			echo '<td>';
			// adding an internal anchor
			/*echo "<a name=\"".$myrow["id"]."\"></a>";
			echo date("d", strtotime($myrow["date"]))." ".$MonthsLong[date("n", strtotime($myrow["date"])) - 1]." ".date("Y", strtotime($myrow["date"]))."&nbsp;";*/
			
			$myrow["date"] = api_get_local_time($myrow["date"]);
			echo api_format_date($myrow["date"], DATE_TIME_FORMAT_LONG);
			echo "</td>";
			//echo '<td></td>'; //remove when enabling ical
            //echo '<td class="'.$style.'">';
			//echo '<a class="ical_export" href="ical_export.php?type=personal&id='.$myrow['id'].'&class=confidential" title="'.get_lang('ExportiCalConfidential').'">'.Display::return_icon($export_icon_high, get_lang('ExportiCalConfidential')).'</a>';
			//echo '<a class="ical_export" href="ical_export.php?type=personal&id='.$myrow['id'].'&class=private" title="'.get_lang('ExportiCalPrivate').'">'.Display::return_icon($export_icon_low, get_lang('ExportiCalPrivate')).'</a>';
			//echo '<a class="ical_export" href="ical_export.php?type=personal&id='.$myrow['id'].'&class=public" title="'.get_lang('ExportiCalPublic').'">'.Display::return_icon($export_icon, get_lang('ExportiCalPublic')).'</a>';
			//echo "</td>";
			//echo "</tr>";


			/* display: the edit / delete icons */
			echo "<td>";
			echo "<a href=\"myagenda.php?action=edit_personal_agenda_item&amp;id=".$myrow['id']."\">".Display::return_icon('edit.png', get_lang('Edit'), array(), 22)."</a> ";
			echo "<a href=\"".api_get_self()."?action=delete&amp;id=".$myrow['id']."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset))."')) return false;\">".Display::return_icon('delete.png', get_lang('Delete'), array(), 22)."</a>";
			echo "</td></tr>";
			$counter++;
		}
	} else {
		echo '<tr><td colspan="2">'.get_lang('NoAgendaItems').'</td></tr>';
	}
	echo "</table>";
}

/**
 * This function retrieves all the personal agenda items of the given user_id and shows
 * these items in one list (ordered by date and grouped by month (the month_bar)
 * @param int user id
 */
function show_simple_personal_agenda($user_id) {
	global $MonthsLong, $charset;

	$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	// The SQL statement that retrieves all the personal agenda items of this user
	$sql = "SELECT * FROM ".$tbl_personal_agenda." WHERE user='".$user_id."' ORDER BY date DESC";
	$result = Database::query($sql);
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
			$content.= strftime(get_lang("timeNoSecFormat"), strtotime($myrow["date"]));

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
function delete_personal_agenda($id) {
	$tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);

	if ($id != strval(intval($id))) {
		return false; //potential SQL injection
	}

	if ($id <> '')
	{
		$sql = "SELECT * FROM ".$tbl_personal_agenda." WHERE user='".api_get_user_id()."' AND id='".$id."'";
		$result = Database::query($sql);
		$aantal = Database::num_rows($result);
		if ($aantal <> 0)
		{
			$sql = "DELETE FROM ".$tbl_personal_agenda." WHERE user='".api_get_user_id()."' AND id='".$id."'";
			$result = Database::query($sql);
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
    require_once(api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
	foreach ($courses as $id => $course)
	{
		$c = api_get_course_info($course['code']);
		//databases of the courses
		$t_a = Database :: get_course_table(TABLE_AGENDA, $course['db']);
		$t_ip = Database :: get_course_table(TABLE_ITEM_PROPERTY, $course['db']);
        // get the groups to which the user belong
		$group_memberships = GroupManager :: get_group_ids($course['db'], $user_id);
		// if the user is administrator of that course we show all the agenda items
		if ($course['status'] == '1') {
			//echo "course admin";
			$sqlquery = "SELECT ".
						" DISTINCT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref ".
						" FROM ".$t_a." agenda, ".
						$t_ip." ip ".
                        " WHERE agenda.id = ip.ref ".
						" AND agenda.start_date>='$date_start' ".
						" AND agenda.end_date<='$date_end' ".
						" AND ip.tool='".TOOL_CALENDAR_EVENT."' ".
						" AND ip.visibility='1' ".
						" GROUP BY agenda.id ".
						" ORDER BY start_date ";
		} else {
            // if the user is not an administrator of that course, then...
			if (is_array($group_memberships) && count($group_memberships)>0)
			{
				$sqlquery = "SELECT " .
							" agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref ".
                            " FROM ".$t_a." agenda, ".
			                $t_ip." ip ".
                            " WHERE agenda.id = ip.ref ".
							" AND agenda.start_date>='$date_start' ".
							" AND agenda.end_date<='$date_end' ".
							" AND ip.tool='".TOOL_CALENDAR_EVENT."' ".
							" AND	( ip.to_user_id='".$user_id."' OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") ) ".
							" AND ip.visibility='1' ".
							" ORDER BY start_date ";
			} else {
				$sqlquery = "SELECT ".
							" agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref ".
							" FROM ".$t_a." agenda, ".
							$t_ip." ip ".
							" WHERE agenda.id = ip.ref ".
							" AND agenda.start_date>='$date_start' ".
							" AND agenda.end_date<='$date_end' ".
							" AND ip.tool='".TOOL_CALENDAR_EVENT."' ".
							" AND ( ip.to_user_id='".$user_id."' OR ip.to_group_id='0') ".
							" AND ip.visibility='1' ".
							" ORDER BY start_date ";
			}
		}

		$result = Database::query($sqlquery);
		while ($item = Database::fetch_array($result)) {
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
