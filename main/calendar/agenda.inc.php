<?php
/* For licensing terms, see /license.txt */
/**
 *  @author: Julio Montoya <gugli100@gmail.com> BeezNest 2011  - Lots of fixes - UI improvements, security fixes,
 *	@author: Patrick Cool, patrick.cool@UGent.be
 *	@todo this code should be clean as well as the myagenda.inc.php - jmontoya
 * @package chamilo.calendar
*/
/**
 * Code
 */
// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = api_get_week_days_short();
// Defining the days of the week to allow translation of the days
$DaysLong = api_get_week_days_long();
// Defining the months of the year to allow translation of the months
$MonthsLong = api_get_months_long();

$htmlHeadXtra[] = to_javascript();
$htmlHeadXtra[] = '<script type="text/javascript">
function setFocus(){
    $("#agenda_title").focus();
}
$(document).ready(function () {
    setFocus();
});
</script>';

require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';


/**
* Retrieves all the agenda items from the table
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @author Yannick Warnier <yannick.warnier@dokeos.com> - cleanup
* @author Julio Montoya - Refactoring
* @param integer $month: the integer value of the month we are viewing
* @param integer $year: the 4-digit year indication e.g. 2005
* @return array
*/
function get_calendar_items($select_month, $select_year, $select_day = false) {	
	$course_info  = api_get_course_info();
	$select_month = intval($select_month);
	$select_year  = intval($select_year);
	if ($select_day)
	   $select_day = intval($select_day);
	   	
    if (!empty($select_month) && !empty($select_year)) {       
         
        $show_all_current  = " AND MONTH(start_date) = $select_month AND year(start_date) = $select_year";
        if ($select_day) {
            $show_all_current .= ' AND DAY(start_date) = '.$select_day;
        }       
        $show_all_current_personal  =" AND MONTH(date) = $select_month AND year(date) = $select_year";
        
        if ($select_day) {
            $show_all_current_personal .= ' AND DAY(date) = '.$select_day;
        } 
    } else {
        $show_all_current  = '';
        $show_all_current_personal = '';
    }   

	// database variables
	$TABLEAGENDA         = Database::get_course_table(TABLE_AGENDA);
	$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
	
	$group_memberships = GroupManager::get_group_ids($course_info['real_id'], api_get_user_id());
    $repeats = array();

	/*	CONSTRUCT THE SQL STATEMENT */

	// by default we use the id of the current user. The course administrator can see the agenda of other users by using the user / group filter
	$user_id = api_get_user_id();
	if (isset($_SESSION['user'])) {
		$user_id = intval($_SESSION['user']);
	}	
	
	$group_id = api_get_group_id();
	
    
	$session_condition = api_get_session_condition(api_get_session_id());

	if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous())) {
        // A.1. you are a course admin with a USER filter
        // => see only the messages of this specific user + the messages of the group (s)he is member of.
        
        if (!empty($_SESSION['user'])) {
            $group_memberships = GroupManager::get_group_ids($course_info['real_id'], $_SESSION['user']);

            $show_user =true;
            $new_group_memberships=array();
            foreach($group_memberships as $id) {
                // did i have access to the same
                $has_access = GroupManager::user_has_access(api_get_user_id(),$id,GROUP_TOOL_CALENDAR);
                $result = GroupManager::get_group_properties($id);
                if ($has_access && $result['calendar_state']!='0' ) {
                    $new_group_memberships[]=$id;
                }
            }
            $group_memberships = $new_group_memberships;            

            if (is_array($group_memberships) && count($group_memberships)>0) {
                $sql="SELECT
                    agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                    FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
                    WHERE agenda.id = ip.ref   ".$show_all_current."
                    AND ip.tool='".TOOL_CALENDAR_EVENT."'
                    AND ( ip.to_user_id = $user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") )
                    AND ip.visibility='1'
                    $session_condition";
            } else {
                //AND ( ip.to_user_id=$user_id OR ip.to_group_id='0')
                $sql="SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                    FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
                    WHERE agenda.id = ip.ref   ".$show_all_current."
                    AND ip.tool='".TOOL_CALENDAR_EVENT."'
                    AND ( ip.to_user_id = $user_id )
                    AND ip.visibility='1'
                    $session_condition";
            }
        }
        // A.2. you are a course admin with a GROUP filter
        // => see only the messages of this specific group
        elseif (!empty($_SESSION['group'])) {
            if (!empty($group_id)) {
                $result = GroupManager::get_group_properties($group_id);
                $has_access = GroupManager::user_has_access(api_get_user_id(),$group_id,GROUP_TOOL_CALENDAR);
                //echo '<pre>';print_R($result);

                // lastedit
                if (!$has_access || $result['calendar_state']=='0' ) {
                    $group_id=0;
                }
            }

            $sql="SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
                WHERE agenda.id = ip.ref  ".$show_all_current."
                AND ip.tool='".TOOL_CALENDAR_EVENT."'
                AND ( ip.to_group_id=$group_id)
                AND ip.lastedit_type<>'CalendareventDeleted'
                $session_condition
                GROUP BY ip.ref";
                //removed   - > AND toolitemproperties.visibility='1'
        }
        // A.3 you are a course admin without any group or user filter
        else {
            // A.3.a you are a course admin without user or group filter but WITH studentview
            // => see all the messages of all the users and groups without editing possibilities
            if (isset($_GET['isStudentView']) && $_GET['isStudentView'] =='true') {
                $sql="SELECT
                    agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                    FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
                    WHERE agenda.id = ip.ref  ".$show_all_current."
                    AND ip.tool='".TOOL_CALENDAR_EVENT."'
                    AND ip.visibility='1'
                    $session_condition
                    GROUP BY ip.ref
                    ORDER $sort_item $sort";

            }
            // A.3.b you are a course admin or a student
            else {
                // A.3.b.1 you are a course admin without user or group filter and WITHOUT studentview (= the normal course admin view)
                //  => see all the messages of all the users and groups with editing possibilities

                 if (api_is_course_admin()) {
                     $sql="SELECT
                        agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                        FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
                        WHERE agenda.id = ip.ref  ".$show_all_current."
                        AND ip.tool='".TOOL_CALENDAR_EVENT."'
                        AND ( ip.visibility='0' OR ip.visibility='1')
                        $session_condition
                        GROUP BY ip.ref";
                 } else {
                    // A.3.b.2 you are a student with no group filter possibly showall
                    //when showing all the events we do not show the group events
                    //todo showing ALL events including the groups events that are available
                    $sql="SELECT
                        agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                        FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
                        WHERE agenda.id = ip.ref  ".$show_all_current."
                        AND ip.tool='".TOOL_CALENDAR_EVENT."'
                        AND ( ip.visibility='0' OR ip.visibility='1')
                        $session_condition
                        GROUP BY ip.ref";

                    /*
                    if (is_array($group_memberships) && count($group_memberships)>0)
                    {
                        echo $sql="SELECT
                        agenda.*, toolitemproperties.*
                        FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
                        WHERE agenda.id = toolitemproperties.ref  ".$show_all_current."
                        AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
                        AND toolitemproperties.visibility='1' AND toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).")
                        $session_condition
                        GROUP BY toolitemproperties.ref
                        ORDER BY start_date ".$sort;
                    }
                    else
                    {
                        $sql="SELECT
                        agenda.*, toolitemproperties.*
                        FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
                        WHERE agenda.id = toolitemproperties.ref  ".$show_all_current."
                        AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
                        AND toolitemproperties.visibility='1' AND toolitemproperties.to_group_id='0'
                        $session_condition
                        GROUP BY toolitemproperties.ref
                        ORDER BY start_date ".$sort;
                    }
                    */
                 }
            }
        }
    } //if (is_allowed_to_edit() OR( api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
    // B. you are a student
    else {        
        if (is_array($group_memberships) && count($group_memberships)>0) {
            $sql="SELECT
                agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
                WHERE agenda.id = ip.ref   ".$show_all_current."
                AND ip.tool='".TOOL_CALENDAR_EVENT."'
                AND ( ip.to_user_id=$user_id OR ip.to_group_id IN (0, ".implode(", ", $group_memberships).") )
                AND ip.visibility='1'
                $session_condition";
        } else {
            if (api_get_user_id()) {
                 $sql="SELECT
                    agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                    FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
                    WHERE agenda.id = ip.ref   ".$show_all_current."
                    AND ip.tool='".TOOL_CALENDAR_EVENT."'
                    AND ( ip.to_user_id=$user_id OR ip.to_group_id='0')
                    AND ip.visibility='1'
                    $session_condition";
            } else {
                $sql="SELECT
                    agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
                    FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
                    WHERE agenda.id = ip.ref   ".$show_all_current."
                    AND ip.tool='".TOOL_CALENDAR_EVENT."'
                    AND ip.to_group_id='0'
                    AND ip.visibility='1'
                    $session_condition";
            }
        }
    } // you are a student
    
    $my_events = array();
    $avoid_doubles = array();
    $result = Database::query($sql);
    
    //Course venets
    while($row = Database::fetch_array($result,'ASSOC')) {        
        $row['calendar_type'] = 'course';
        if (!in_array($row['id'], $avoid_doubles)) {            
            if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
            	$row['start_date'] 		= api_get_local_time($row['start_date']);
            	$row['start_date_tms']  = api_strtotime($row['start_date']);            	
            }
            
            if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
            	$row['end_date'] 		= api_get_local_time($row['end_date']);
            	$row['end_date_tms']    = api_strtotime($row['end_date']);            
            }
            
            $my_events[] = $row;
            $avoid_doubles[] = $row['id'];
        }
    }

    //Check my personal calendar items
    if (api_get_setting('allow_personal_agenda') == 'true' && empty($_SESSION['user']) &&  empty($_SESSION['group'])) {
        $tbl_personal_agenda = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
        // 1. creating the SQL statement for getting the personal agenda items in MONTH view
        $sql = "SELECT id, title, text as content , date as start_date, enddate as end_date, parent_event_id FROM ".$tbl_personal_agenda."
                WHERE user='".api_get_user_id()."' ".$show_all_current_personal;
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {           
            $row['calendar_type'] = 'personal';
            if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
            	$row['start_date']     = api_get_local_time($row['start_date']);
            	$row['start_date_tms'] = api_strtotime($row['start_date']);            	
            }
            
            if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
            	$row['end_date'] 		= api_get_local_time($row['end_date']);            
            	$row['end_date_tms'] = api_strtotime($row['end_date']);
            }                        
            $my_events[] = $row;
        }
    }
    
    //Check global agenda events
    if (empty($_SESSION['user']) && empty($_SESSION['group'])) {
        $table_agenda_system = Database :: get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
        $current_access_url_id = api_get_current_access_url_id();
        
        $sql = "SELECT DISTINCT id, title, content , start_date, end_date FROM ".$table_agenda_system."
                WHERE 1=1  ".$show_all_current." AND access_url_id = $current_access_url_id";
        $result=Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if (!empty($row['start_date']) && $row['start_date'] != '0000-00-00 00:00:00') {
            	$row['start_date']    = api_get_local_time($row['start_date']);
            	$row['start_date_tms'] = api_strtotime($row['start_date']);
            }
            
            if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00 00:00:00') {
            	$row['end_date'] 		= api_get_local_time($row['end_date']);
            	$row['end_date_tms'] = api_strtotime($row['end_date']);
            }
            
            $row['calendar_type'] = 'global';
            $my_events[] = $row;
        }
    }    
	return $my_events;
}


/**
* show the mini calender of the given month
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @param array an array containing all the agenda items for the given month
* @param integer $month: the integer value of the month we are viewing
* @param integer $year: the 4-digit year indication e.g. 2005
* @param string $monthName: the language variable for the mont name
* @return html code
* @todo refactor this so that $monthName is no longer needed as a parameter
*/
function display_minimonthcalendar($agenda_items, $month, $year) {
	global $DaysShort, $MonthsLong;
	if (empty($month)) {
	    $month = intval(date('m'));
	}
	if (empty($year)) {
        $month = date('Y');   
	}
	
	$month_name = $MonthsLong[$month -1];   
	
	 
	//Handle leap year
	$numberofdays = array (0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if (($year % 400 == 0) or ($year % 4 == 0 and $year % 100 <> 0))
		$numberofdays[2] = 29;
	//Get the first day of the month
	$dayone = getdate(mktime(0, 0, 0, $month, 1, $year));
	//Start the week on monday
	$startdayofweek = $dayone['wday'] <> 0 ? ($dayone['wday'] - 1) : 6;
	$backwardsURL = api_get_self()."?".api_get_cidreq()."&coursePath=".(empty($_GET['coursePath'])?'':Security::remove_XSS($_GET['coursePath']))."&courseCode=".(empty($_GET['courseCode'])?'':Security::remove_XSS($_GET['courseCode']))."&month=". ($month == 1 ? 12 : $month -1)."&year=". ($month == 1 ? $year -1 : $year);
	$forewardsURL = api_get_self()."?".api_get_cidreq()."&coursePath=".(empty($_GET['coursePath'])?'':Security::remove_XSS($_GET['coursePath']))."&courseCode=".(empty($_GET['courseCode'])?'':Security::remove_XSS($_GET['courseCode']))."&month=". ($month == 12 ? 1 : $month +1)."&year=". ($month == 12 ? $year +1 : $year);
    
	$month_link = Display::url($month_name. " ".$year, api_get_self()."?".api_get_cidreq()."&month=". ($month)."&year=".$year);
	
	$new_items = array();
	foreach($agenda_items as $item) {
	    $day   = intval(substr($item["start_date"],8,2));
	    $my_month = intval(substr($item["start_date"],5,2));	   
	    if ($my_month == $month) { 
	       $new_items[$day][] = 1;
	    }	    
	}
	
	$agenda_items = $new_items;
		
	echo 	"<table class=\"data_table\">",
			"<tr>",
			"<th width=\"10%\"><a href=\"", $backwardsURL, "\">".Display::return_icon('action_prev.png',get_lang('Previous'))." </a></th>",
			"<th width=\"80%\" colspan=\"5\">".$month_link. "</th>",
			"<th  width=\"10%\"><a href=\"", $forewardsURL, "\"> ".Display::return_icon('action_next.png',get_lang('Next'))." </a></th></tr>";
	echo "<tr>";
	for ($ii = 1; $ii < 8; $ii ++) {
		echo "<td class=\"weekdays\">", $DaysShort[$ii % 7], "</td>";
	}
	echo "</tr>";
	$curday = -1;
	$today = getdate();
	while ($curday <= $numberofdays[$month]) {
		echo "<tr>";
		for ($ii = 0; $ii < 7; $ii ++) {
			if (($curday == -1) && ($ii == $startdayofweek)) {
				$curday = 1;
			}
			if (($curday > 0) && ($curday <= $numberofdays[$month])) {
				$bgcolor = $ii < 5 ? $class="class=\"days_week\"" : $class="class=\"days_weekend\"";
				$dayheader = "$curday";
				if (($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon'])) {
					$dayheader = "$curday";
					$class = "class=\"days_today\"";
				}					
				echo "<td ".$class.">";
				if (!empty($agenda_items[$curday])) {
                    echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&amp;sort=asc&amp;toolgroup=".api_get_group_id()."&amp;action=view&amp;view=day&amp;day=".$curday."&amp;month=".$month."&amp;year=".$year."#".$curday."\">".$dayheader."</a>";					
				} else {
					echo $dayheader;
				}
				// "a".$dayheader." <span class=\"agendaitem\">".$agenda_items[$curday]."</span>";
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
* show the calender of the given month
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @author Julio Montoya - adding some nice styles
* @param integer $month: the integer value of the month we are viewing
* @param integer $year: the 4-digit year indication e.g. 2005
* @return html code
*/
function display_monthcalendar($month, $year, $agenda_items) {
	global $MonthsLong;
	global $DaysShort;
	global $origin;	

	//Handle leap year
	$numberofdays = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
	if (($year%400 == 0) or ($year%4==0 and $year%100<>0)) $numberofdays[2] = 29;

	//Get the first day of the month
	$dayone = getdate(mktime(0,0,0,$month,1,$year));
	
  	//Start the week on monday
	$startdayofweek = $dayone['wday']<>0 ? ($dayone['wday']-1) : 6;

	$backwardsURL = api_get_self()."?".api_get_cidreq()."&view=".Security::remove_XSS($_GET['view'])."&origin=$origin&amp;month=".($month==1 ? 12 : $month-1)."&amp;year=".($month==1 ? $year-1 : $year);
	$forewardsURL = api_get_self()."?".api_get_cidreq()."&view=".Security::remove_XSS($_GET['view'])."&origin=$origin&amp;month=".($month==12 ? 1 : $month+1)."&amp;year=".($month==12 ? $year+1 : $year);

	$new_month = $month-1;

	echo '<table id="agenda_list">';
	echo '<tr>';
	echo '<th width="10%"><a href="'.$backwardsURL.'">'.Display::return_icon('action_prev.png',get_lang('Previous'), array(), 32).'</a></th>';
	echo '<th width="80%" colspan="5"><br /><h3>'.$MonthsLong[$new_month].' '.$year.'</h3></th>';
	echo '<th width="10%"><a href="'.$forewardsURL.'"> '.Display::return_icon('action_next.png',get_lang('Next'), array(), 32).'</a></th>';
	echo '</tr>';

	echo '<tr>';
	for ($ii=1;$ii<8; $ii++) {
		echo '<td class="weekdays" width="14%">'.$DaysShort[$ii%7].'</td>';
	}

	echo '</tr>';
	$curday = -1;
	$today = getdate();	
	
	$new_items = array();
    foreach($agenda_items as $item) {
        $day   	  = intval(substr($item["start_date"],8,2));        
        $my_month = intval(substr($item["start_date"],5,2));       
        if ($my_month == $month) { 
            $new_items[$day][] = $item;
        }       
    }            
    $agenda_items = $new_items;

	while ($curday <= $numberofdays[$month]) {
		echo '<tr>';
			//week
	    	for ($ii=0; $ii<7; $ii++) {
		  		if (($curday == -1) && ($ii==$startdayofweek)) {
		    		$curday = 1;
				}
				if (($curday>0) && ($curday<=$numberofdays[$month])) {

					$bgcolor = $ii<5 ? 'class="row_odd"' : 'class="row_even"';
					$dayheader = Display::div($curday, array('class'=>'agenda_day'));

					if (key_exists($curday, $agenda_items)) {				    
					    $dayheader = Display::div($curday, array('class'=>'agenda_day'));
					    $events_in_day = msort($agenda_items[$curday], 'start_date_tms');					    
						foreach ($events_in_day as $value) {
						    
    						$some_content = false;				
    						$month_start_date = (int)substr($value['start_date'],5,2);    							
    						    
    						if ($month == $month_start_date) {
    							$some_content = true;
    
    							$start_time = api_format_date($value['start_date'], TIME_NO_SEC_FORMAT);
    							$end_time = '';
    							if (!empty($value['end_date']) && $value['end_date'] != '0000-00-00 00:00:00') {
    							   $end_time 	= '-&nbsp;<i>'.api_format_date($value['end_date'],DATE_TIME_FORMAT_LONG).'</i>';
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
                                        $value['course_name'] = isset($value['course_name']) ? $value['course_name'] : null;
                                        $value['url'] = isset($value['url']) ? $value['url'] : null;
                                        $icon = Display::url(Display::return_icon('course.png', $value['course_name'].' '.get_lang('Course'), array(), 22), $value['url']);                                                                            
                                        break;              
    							    default:
    							        break;				            
    							}
    							$icon = Display::div($icon, array('style'=>'float:right'));
    							
    							//Setting a personal event to green
    							$dayheader.= '<div class="rounded_div_agenda" style="background-color:'.$bg_color.';">';
    							
    						    //Link to bubble                                                
    							$url = Display::url(cut($value['title'], 40), '#', array('id'=>$value['calendar_type'].'_'.$value['id'], 'class'=>'opener'));									
    							$dayheader .= $time.' '.$icon.' '.Display::div($url);
    							
    							//Hidden content
    							$content = Display::div($icon.Display::tag('h1', $value['title']).$complete_time.'<hr />'.$value['content']);
    							
    							//Main div
    							$dayheader .= Display::div($content, array('id'=>'main_'.$value['calendar_type'].'_'.$value['id'], 'class' => 'dialog', 'style' => 'display:none'));
    							
    							$dayheader .= '</div>';
    						}
    						
    						//Do not show links with no content
    						if (!$some_content) {
    							$dayheader = Display::div($curday, array('class'=>'agenda_day'));
    						}						    
						}
					}
					if (($curday==$today['mday']) && ($year ==$today['year'])&&($month == $today['mon'])) {
						echo '<td class="days_today"'.$bgcolor.' style="height:122px;width:10%">'.$dayheader;
	      			} else {
	      			    $class = 'days_week';	      	      			    
						echo '<td class="'.$class.'" '.$bgcolor.' style="height:122px;width:10%">'.$dayheader;						
					}
					echo '</td>';
		      		$curday++;
			} else {
				echo '<td>&nbsp;</td>';
			}
		}
		echo '</tr>';
    } // end while
	echo '</table>';
}


/**
* returns all the javascript that is required for easily selecting the target people/groups this goes into the $htmlHeadXtra[] array
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return javascript code
*/
function to_javascript() {    
return "<script type=\"text/javascript\" language=\"javascript\">
$(function() {  
    //js used when generating images on the fly see function Tracking::show_course_detail()
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

<!-- Begin javascript menu swapper

function move(fbox,	tbox)
{
    // @todo : change associative arrays arrLookup and arrLookupTitle that use firstname/lastnam as key
    // so, pb with homonyms
	var	arrFbox	= new Array();
	var	arrTbox	= new Array();
	var	arrLookup =	new	Array();
	var	arrLookupTitle =	new	Array();

	var	i;
	for	(i = 0;	i <	tbox.options.length; i++)
	{
		arrLookup[tbox.options[i].text]	= tbox.options[i].value;
		arrLookupTitle[tbox.options[i].text] = tbox.options[i].title;
		arrTbox[i] = tbox.options[i].text;
	}

	var	fLength	= 0;
	var	tLength	= arrTbox.length;

	for(i =	0; i < fbox.options.length;	i++)
	{
		arrLookup[fbox.options[i].text]	= fbox.options[i].value;
		arrLookupTitle[fbox.options[i].text] = fbox.options[i].title;

		if (fbox.options[i].selected &&	fbox.options[i].value != \"\")
		{
			arrTbox[tLength] = fbox.options[i].text;
			tLength++;
		}
		else
		{
			arrFbox[fLength] = fbox.options[i].text;
			fLength++;
		}
	}

	//arrFbox.sort();
	//arrTbox.sort()

	var arrFboxGroup = new Array();
	var arrFboxUser = new Array();
	var prefix_x;

	for (x = 0; x < arrFbox.length; x++) {
		prefix_x = arrFbox[x].substring(0,2);
		if (prefix_x == 'G:') {
			arrFboxGroup.push(arrFbox[x]);
		} else {
			arrFboxUser.push(arrFbox[x]);
		}
	}

	arrFboxGroup.sort();
	arrFboxUser.sort();
	arrFbox = arrFboxGroup.concat(arrFboxUser);

	var arrTboxGroup = new Array();
	var arrTboxUser = new Array();
	var prefix_y;

	for (y = 0; y < arrTbox.length; y++) {
		prefix_y = arrTbox[y].substring(0,2);
		if (prefix_y == 'G:') {
			arrTboxGroup.push(arrTbox[y]);
		} else {
			arrTboxUser.push(arrTbox[y]);
		}
	}

	arrTboxGroup.sort();
	arrTboxUser.sort();
	arrTbox = arrTboxGroup.concat(arrTboxUser);

	fbox.length	= 0;
	tbox.length	= 0;

	var	c;
	for(c =	0; c < arrFbox.length; c++)
	{
		var	no = new Option();
		no.value = arrLookup[arrFbox[c]];
		no.text	= arrFbox[c];
		no.title = arrLookupTitle[arrFbox[c]];
		fbox[c]	= no;
	}
	for(c =	0; c < arrTbox.length; c++)
	{
		var	no = new Option();
		no.value = arrLookup[arrTbox[c]];
		no.text	= arrTbox[c];
		no.title = arrLookupTitle[arrTbox[c]];
		tbox[c]	= no;
	}
}

function checkDate(month, day, year)
{
  var monthLength =
    new Array(31,28,31,30,31,30,31,31,30,31,30,31);

  if (!day || !month || !year)
    return false;

  // check for bisestile year
  if (year/4 == parseInt(year/4))
    monthLength[1] = 29;

  if (month < 1 || month > 12)
    return false;

  if (day > monthLength[month-1])
    return false;

  return true;
}

function mktime() {
    var no, ma = 0, mb = 0, i = 0, d = new Date(), argv = arguments, argc = argv.length;
    d.setHours(0,0,0); d.setDate(1); d.setMonth(1); d.setYear(1972);
    var dateManip = {
        0: function(tt){ return d.setHours(tt); },
        1: function(tt){ return d.setMinutes(tt); },
        2: function(tt){ set = d.setSeconds(tt); mb = d.getDate() - 1; return set; },
        3: function(tt){ set = d.setMonth(parseInt(tt)-1); ma = d.getFullYear() - 1972; return set; },
        4: function(tt){ return d.setDate(tt+mb); },
        5: function(tt){ return d.setYear(tt+ma); }
    };
    for( i = 0; i < argc; i++ ){
        no = parseInt(argv[i]*1);
        if (isNaN(no)) {
            return false;
        } else {
            // arg is number, lets manipulate date object
            if(!dateManip[i](no)){
                // failed
                return false;
            }
        }
    }
    return Math.floor(d.getTime()/1000);
}

function validate() {
	var	f =	document.new_calendar_item;
	f.submit();
	return true;
}

function selectAll(cbList,bSelect,showwarning) {
		var start_day = document.new_calendar_item.fday.value;
		var start_month = document.new_calendar_item.fmonth.value;
		var start_year = document.new_calendar_item.fyear.value;
		var start_hour = document.new_calendar_item.fhour.value;
		var start_minute = document.new_calendar_item.fminute.value;
		var start_date = mktime(start_hour,start_minute,0,start_month,start_day,start_year)

		var ends_day = document.new_calendar_item.end_fday.value;
		var ends_month = document.new_calendar_item.end_fmonth.value;
		var ends_year = document.new_calendar_item.end_fyear.value;
		var ends_hour = document.new_calendar_item.end_fhour.value;
		var ends_minute = document.new_calendar_item.end_fminute.value;
		var ends_date = mktime(ends_hour,ends_minute,0,ends_month,ends_day,ends_year)

		msg_err1 = document.getElementById(\"err_date\");
		msg_err2 = document.getElementById(\"err_start_date\");
		msg_err3 = document.getElementById(\"err_end_date\");
		msg_err4 = document.getElementById(\"err_title\");
		
		
		var error = false;

		if (start_date > ends_date) {
			if ($('#empty_end_date').is(':checked')) {
				msg_err1.innerHTML=\"\";
				msg_err2.innerHTML=\"\";
				msg_err3.innerHTML=\"\";
				
			} else {
				error = true; 
				msg_err1.style.display =\"block\";
				msg_err1.innerHTML=\"".get_lang('EndDateCannotBeBeforeTheStartDate')."\";
				msg_err2.innerHTML=\"\";
				msg_err3.innerHTML=\"\";
			}
		} 
		
		if (!checkDate(start_month,start_day,start_year)) {
			msg_err2.style.display =\"block\";
			msg_err2.innerHTML=\"".get_lang('InvalidDate')."\";
			msg_err1.innerHTML=\"\";
			msg_err3.innerHTML=\"\";
			error = true;
		} 
		
		if (!checkDate(ends_month,ends_day,ends_year)) {
			msg_err3.style.display =\"block\";
			msg_err3.innerHTML=\"".get_lang('InvalidDate')."\";
			msg_err1.innerHTML=\"\";msg_err2.innerHTML=\"\";
			error = true;
		} 
		
		if (document.new_calendar_item.title.value == '') {
			msg_err4.style.display =\"block\";
			msg_err4.innerHTML=\"".get_lang('FieldRequired')."\";
			msg_err1.innerHTML=\"\";msg_err2.innerHTML=\"\";msg_err3.innerHTML=\"\";
			error = true;
		} 
		if (error == false) {
            if (cbList) { 
    			if (cbList.length <	1) {
    				if (!confirm(\"".get_lang('Send2All')."\")) {
    					return false;
    				}
    			}
    			for	(var i=0; i<cbList.length; i++)
    			cbList[i].selected = cbList[i].checked = bSelect;
			}
			document.new_calendar_item.submit();
		}
		
		

}

function reverseAll(cbList)
{
	for	(var i=0; i<cbList.length; i++)
	{
		cbList[i].checked  = !(cbList[i].checked)
		cbList[i].selected = !(cbList[i].selected)
	}
}

function plus_attachment() {
				if (document.getElementById('options').style.display == 'none') {
					document.getElementById('options').style.display = 'block';
					document.getElementById('plus').innerHTML='&nbsp;<img style=\"vertical-align:middle;\" src=\"../img/div_hide.gif\" alt=\"\" />&nbsp;".get_lang('AddAnAttachment')."';
				} else {
				document.getElementById('options').style.display = 'none';
				document.getElementById('plus').innerHTML='&nbsp;<img style=\"vertical-align:middle;\" src=\"../img/div_show.gif\" alt=\"\" />&nbsp;".get_lang('AddAnAttachment')."';
				}
}

function plus_repeated_event() {
				if (document.getElementById('options2').style.display == 'none') {
					document.getElementById('options2').style.display = 'block';
					document.getElementById('plus2').innerHTML='&nbsp;<img style=\"vertical-align:middle;\" src=\"../img/div_hide.gif\" alt=\"\" />&nbsp;".get_lang('RepeatEvent')."';
				} else {
				document.getElementById('options2').style.display = 'none';
				document.getElementById('plus2').innerHTML='&nbsp;<img style=\"vertical-align:middle;\" src=\"../img/div_show.gif\" alt=\"\" />&nbsp;".get_lang('RepeatEvent')."';
				}
}

/*
function plus_ical() {
				if (document.getElementById('icalform').style.display == 'none') {
					document.getElementById('icalform').style.display = 'block';
					document.getElementById('plusical').innerHTML='';
				}
}
*/

//	End	-->
</script>";
}


/**
* returns the javascript for setting a filter. This is a jump menu
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return javascript code
*/
function user_group_filter_javascript() {
	return "<script language=\"JavaScript\" type=\"text/JavaScript\">
	<!--
	function MM_jumpMenu(targ,selObj,restore){
	  eval(targ+\".location='\"+selObj.options[selObj.selectedIndex].value+\"'\");
	  if (restore) selObj.selectedIndex=0;
	}
	//-->
	</script>
	";
}


/**
* this function gets all the users of the current course
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return array: associative array where the key is the id of the user and the value is an array containing
			the first name, the last name, the user id
*/
function get_course_users() {
	global $_cid;

	$tbl_user       		= Database::get_main_table(TABLE_MAIN_USER);
	$tbl_courseUser 		= Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_session_course_user= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

	// not 100% if this is necessary, this however prevents a notice
	if (!isset($courseadmin_filter))
		{$courseadmin_filter='';}
    // 
	$order_clause = api_sort_by_first_name() ? ' ORDER BY u.firstname, u.lastname' : ' ORDER BY u.lastname, u.firstname';
	$sql = "SELECT u.user_id uid, u.lastname lastName, u.firstname firstName, u.username
			FROM $tbl_user as u, $tbl_courseUser as cu
			WHERE cu.course_code = '".api_get_course_id()."'
			AND cu.user_id = u.user_id $courseadmin_filter".$order_clause;
	$result = Database::query($sql);
	while($user=Database::fetch_array($result)){
		$users[$user[0]] = $user;
	}
	
    $session_id = api_get_session_id();
	if (!empty($session_id)) {
	    $users = array();
		$sql = "SELECT u.user_id uid, u.lastname lastName, u.firstName firstName, u.username
				FROM $tbl_session_course_user AS session_course_user
				INNER JOIN $tbl_user u ON u.user_id = session_course_user.id_user
				WHERE id_session = ".$session_id."
				AND course_code  = '".api_get_course_id()."'";

		$result = Database::query($sql);
		while($user=Database::fetch_array($result)){
			$users[$user[0]] = $user;
		}
	}
	return $users;

}


/**
* this function gets all the groups of the course
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return array
*/
function get_course_groups() {
	$group_list = array();
	$group_list = CourseManager::get_group_list_of_course(api_get_course_id(), api_get_session_id());
	return $group_list;
}


/**
* this function shows the form for sending a message to a specific group or user.
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return html code
*/
function show_to_form($to_already_selected) {
	/*$user_list     = get_course_users();
	$group_list    = get_course_groups();*/
    $order = 'lastname';
    if (api_is_western_name_order) {
        $order = 'firstname';    
    } 
    
    $user_list  = CourseManager::get_user_list_from_course_code(api_get_course_id(), api_get_session_id(), null, $order);    
    $group_list = CourseManager::get_group_list_of_course(api_get_course_id(), api_get_session_id());
    
    construct_not_selected_select_form($group_list, $user_list, $to_already_selected);
}



/**
* this function shows the form with the user that were not selected
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return html code
*/
function construct_not_selected_select_form($group_list=null, $user_list=null, $to_already_selected=array()) {    
	echo '<select data-placeholder="'.get_lang('Select').'" style="width:350px;" class="chzn-select" id="selected_form_id" name="selected_form[]" multiple="multiple">';

	// adding the groups to the select form
  echo	'<option value="everyone">'.get_lang('Everyone').'</option>';
  
	if (isset($to_already_selected) && $to_already_selected==='everyone') {		
	} else {
		if (is_array($group_list)) {
            echo '<optgroup label="'.get_lang('Groups').'">';
			foreach($group_list as $this_group) {
				//api_display_normal_message("group " . $thisGroup[id] . $thisGroup[name]);
				if (!is_array($to_already_selected) || !in_array("GROUP:".$this_group['id'],$to_already_selected)) {
                     // $to_already_selected is the array containing the groups (and users) that are already selected
					    echo	"<option value=\"GROUP:".$this_group['id']."\">",
						"G: ",$this_group['name']," &ndash; " . $this_group['userNb'] . " " . get_lang('Users') .
						"</option>";
				}
			}
			// a divider
		}
		echo	"</optgroup>";
		// adding the individual users to the select form
        if (!empty($user_list)) {
            echo '<optgroup label="'.get_lang('Users').'">';
            foreach($user_list as $this_user) {
                // $to_already_selected is the array containing the users (and groups) that are already selected
                if (!is_array($to_already_selected) || !in_array("USER:".$this_user['user_id'],$to_already_selected)) {
                    $username = api_htmlentities(sprintf(get_lang('LoginX'), $this_user['username']), ENT_QUOTES);
                    $user_info = api_get_person_name($this_user['firstname'], $this_user['lastname']).' ('.$this_user['username'].')';
                    echo "<option title='$username' value='USER:".$this_user['user_id']."'>$user_info</option>";
                }            
            }
            echo "</optgroup>";
        }
	}
    echo "</select>";
}

/**
* This function shows the form with the user that were selected
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return html code
*/
function construct_selected_select_form($group_list=null, $user_list=null,$to_already_selected)
{
	// we separate the $to_already_selected array (containing groups AND users into
	// two separate arrays
	if (is_array($to_already_selected)) {
		 $groupuser=separate_users_groups($to_already_selected);
	}
	$groups_to_already_selected=$groupuser['groups'];
	$users_to_already_selected=$groupuser['users'];

	// we load all the groups and all the users into a reference array that we use to search the name of the group / user
	$ref_array_groups   = get_course_groups();
	$ref_array_users    = get_course_users();
	// we construct the form of the already selected groups / users
	echo "<select id=\"selected_form2\" name=\"selectedform2[]\" size=\"5\" multiple=\"multiple\" style=\"width:200px\">";
	if(is_array($to_already_selected))
	{
		$select_options_group = array();
		$select_options_user = array();
		$select_options_groupuser = array();
		foreach($to_already_selected as $groupuser)
		{
			list($type,$id)=explode(":",$groupuser);
			if ($type=="GROUP")
			{
				$select_options_group[] = "<option value=\"".$groupuser."\">G: ".$ref_array_groups[$id]['name']."</option>";
				//echo "<option value=\"".$groupuser."\">G: ".$ref_array_groups[$id]['name']."</option>";
			}
			else
			{
                $username = api_htmlentities(sprintf(get_lang('LoginX'), $ref_array_users[$id]['username']), ENT_QUOTES);
			    $user_info = api_get_person_name($ref_array_users[$id]['firstName'], $ref_array_users[$id]['lastName']);
				$select_options_user[] = "<option title='$username' value='".$groupuser."'>$user_info</option>";
				//echo "<option value=\"".$groupuser."\">".api_get_person_name($ref_array_users[$id]['firstName'], $ref_array_users[$id]['lastName'])."</option>";
			}
		}
		$select_options_group[] = "<option value=\"\">--------------------------------------------</option>";
		$select_options_groupuser = array_merge($select_options_group,$select_options_user);

		foreach($select_options_groupuser as $select_options) {
			echo $select_options;
		}
	} else {
			if($to_already_selected=='everyone'){
				// adding the groups to the select form
				if (is_array($group_list))
				{
					foreach($group_list as $this_group)
					{
						//api_display_normal_message("group " . $thisGroup[id] . $thisGroup[name]);
						if (!is_array($to_already_selected) || !in_array("GROUP:".$this_group['id'],$to_already_selected)) // $to_already_selected is the array containing the groups (and users) that are already selected
							{
							echo	"<option value=\"GROUP:".$this_group['id']."\">",
								"G: ",$this_group['name']," &ndash; " . $this_group['userNb'] . " " . get_lang('Users') .
								"</option>";
						}
					}
				}
				echo	"<option value=\"\">--------------------------------------------</option>";
				// adding the individual users to the select form
				foreach($user_list as $this_user)
				{
					if (!is_array($to_already_selected) || !in_array("USER:".$this_user['uid'],$to_already_selected)) // $to_already_selected is the array containing the users (and groups) that are already selected
					{
						echo	"<option value=\"USER:",$this_user['uid'],"\">",
							"",api_get_person_name($this_user['firstName'], $this_user['lastName']),
							"</option>";
					}
				}
			}
	}
	echo "</select>";
}



/**
* This function stores the Agenda Item in the table calendar_event and updates the item_property table also
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return integer the id of the last added agenda item
*/
function store_new_agenda_item() {
	global $_course;
	$TABLEAGENDA 	 = Database::get_course_table(TABLE_AGENDA);
    $t_agenda_repeat = Database::get_course_Table(TABLE_AGENDA_REPEAT);
    
    $course_id = api_get_course_int_id();    

	// some filtering of the input data
	$title		= trim($_POST['title']); // no html allowed in the title
	$content	= trim($_POST['content']);
	$start_date	= (int)$_POST['fyear']."-".(int)$_POST['fmonth']."-".(int)$_POST['fday']." ".(int)$_POST['fhour'].":".(int)$_POST['fminute'].":00";
	$end_date	= (int)$_POST['end_fyear']."-".(int)$_POST['end_fmonth']."-".(int)$_POST['end_fday']." ".(int)$_POST['end_fhour'].":".(int)$_POST['end_fminute'].":00";
	
	
	$title		= Database::escape_string($title);
	$content 	= Database::escape_string($content);
	$start_date = Database::escape_string($start_date);
	$end_date   = Database::escape_string($end_date);
	
	if ($_POST['empty_end_date'] == 'on') {
		$end_date = "0000-00-00 00:00:00";
	}

	// store in the table calendar_event
	$sql = "INSERT INTO ".$TABLEAGENDA." (c_id, title,content, start_date, end_date)
			VALUES ($course_id, '".$title."','".$content."', '".$start_date."','".$end_date."')";
	$result 	= Database::query($sql);
	$last_id 	= Database::insert_id();

	// store in last_tooledit (first the groups, then the users
	$to=$_POST['selectedform'];

	if ((!is_null($to)) || (!empty($_SESSION['toolgroup']))) // !is_null($to): when no user is selected we send it to everyone
	{
		//$send_to=separate_users_groups($to);
		$send_to=separate_users_groups(explode('|', $to));
		// storing the selected groups
		if (is_array($send_to['groups'])) {
			foreach ($send_to['groups'] as $group) {
				api_item_property_update($_course, TOOL_CALENDAR_EVENT, $last_id,"AgendaAdded", api_get_user_id(), $group,'',$start_date, $end_date);
			}
		}
		// storing the selected users
		if (is_array($send_to['users'])) {
			foreach ($send_to['users'] as $user) {
				api_item_property_update($_course, TOOL_CALENDAR_EVENT, $last_id,"AgendaAdded", api_get_user_id(),'',$user, $start_date,$end_date);
			}
		}
	}
	else // the message is sent to everyone, so we set the group to 0
	{
		api_item_property_update($_course, TOOL_CALENDAR_EVENT, $last_id,"AgendaAdded", api_get_user_id(), '','',$start_date,$end_date);
	}
	// storing the resources
	store_resources($_SESSION['source_type'],$last_id);
    $course_id = api_get_course_int_id();

    //if repetitive, insert element into agenda_repeat table
    if(!empty($_POST['repeat']) && !empty($_POST['repeat_type'])) {
    	if(!empty($_POST['repeat_end_year']) && !empty($_POST['repeat_end_month']) && !empty($_POST['repeat_end_day'])) {
        	$end_y = intval($_POST['repeat_end_year']);
            $end_m = intval($_POST['repeat_end_month']);
            $end_d = intval($_POST['repeat_end_day']);
            $end = mktime((int)$_POST['fhour'],(int)$_POST['fminute'],0,$end_m,$end_d,$end_y);
            $now = time();
            $type = Database::escape_string($_POST['repeat_type']);

        	if ($end > $now && in_array($type,array('daily','weekly','monthlyByDate','monthlyByDay','monthlyByDayR','yearly'))) {
        	   $sql = "INSERT INTO $t_agenda_repeat (c_id, cal_id, cal_type, cal_end)" .
                    " VALUES ($course_id, $last_id,'$type',$end)";
               $res = Database::query($sql);
            }
        }
    }
	return $last_id;
}

/**
 * Stores the given agenda item as an announcement (unlinked copy)
 * @param	integer		Agenda item's ID
 * @return	integer		New announcement item's ID
 */
function store_agenda_item_as_announcement($item_id){
	$table_agenda  = Database::get_course_table(TABLE_AGENDA);
	$table_ann     = Database::get_course_table(TABLE_ANNOUNCEMENT);
	//check params
	if(empty($item_id) or $item_id != strval(intval($item_id))) {return -1;}
	//get the agenda item

	$item_id = Database::escape_string($item_id);
	$sql = "SELECT * FROM $table_agenda WHERE id = ".$item_id;
	$res = Database::query($sql);
    $course_id = api_get_course_int_id();
    
	if (Database::num_rows($res)>0) {
		$row = Database::fetch_array($res);
		
		//we have the agenda event, copy it
		//get the maximum value for display order in announcement table
		$sql_max = "SELECT MAX(display_order) FROM $table_ann WHERE c_id = $course_id ";
		$res_max = Database::query($sql_max);
		$row_max = Database::fetch_array($res_max);
		$max = intval($row_max[0])+1;		
		//build the announcement text
		$content = $row['content'];
		//insert announcement
        $session_id = api_get_session_id();
        
        
		$sql_ins = "INSERT INTO $table_ann (c_id, title,content,end_date,display_order,session_id) " .
					"VALUES ($course_id, '".Database::escape_string($row['title'])."','".Database::escape_string($content)."','".Database::escape_string($row['end_date'])."','$max','$session_id')";
		$res_ins = Database::query($sql_ins);
		if ($res > 0) {
			$ann_id = Database::insert_id();
			//Now also get the list of item_properties rows for this agenda_item (calendar_event)
			//and copy them into announcement item_properties
			$table_props = Database::get_course_table(TABLE_ITEM_PROPERTY);
			$sql_props = "SELECT * FROM $table_props WHERE tool = 'calendar_event' AND ref='$item_id'";
			$res_props = Database::query($sql_props);
			if(Database::num_rows($res_props)>0) {
				while($row_props = Database::fetch_array($res_props)) {
					//insert into announcement item_property
					$time = api_get_utc_datetime();
					$sql_ins_props = "INSERT INTO $table_props " .
							"(c_id, tool, insert_user_id, insert_date, " .
							"lastedit_date, ref, lastedit_type," .
							"lastedit_user_id, to_group_id, to_user_id, " .
							"visibility, start_visible, end_visible)" .
							" VALUES " .
							"($course_id, 'announcement','".$row_props['insert_user_id']."','".$time."'," .
							"'$time','$ann_id','AnnouncementAdded'," .
							"'".$row_props['last_edit_user_id']."','".$row_props['to_group_id']."','".$row_props['to_user_id']."'," .
							"'".$row_props['visibility']."','".$row_props['start_visible']."','".$row_props['end_visible']."')";
					$res_ins_props = Database::query($sql_ins_props);
					if($res_ins_props <= 0){
						return -1;
					} else {
						//copy was a success
						return $ann_id;
					}
				}
			}
		} else {
			return -1;
		}
	}
	return -1;
}

/**
* This function separates the users from the groups
* users have a value USER:XXX (with XXX the dokeos id
* groups have a value GROUP:YYY (with YYY the group id)
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return array
*/
function separate_users_groups($to) {
	$grouplist = array();
    $userlist  = array();
    $send_to = null;
    
    $send_to['everyone']  = false;
    
	if (is_array($to) && count($to)>0) {
        foreach ($to as $to_item) {
         
            list($type, $id) = explode(':', $to_item);
            switch($type) {
                case 'everyone':
                    $send_to['everyone']  = true;
                case 'GROUP':
                    $grouplist[] =$id;
                    break;
                case 'USER':
                    $userlist[] =$id;
                    break;
            }
        }
        $send_to['groups']  = $grouplist;
        $send_to['users']   = $userlist;
    }
    return $send_to;
}



/**
* returns all the users and all the groups a specific Agenda item has been sent to
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return array
*/
function sent_to($tool, $id) {
	$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$tool  = Database::escape_string($tool);
	$id    = Database::escape_string($id);

	$sql   = "SELECT * FROM $TABLE_ITEM_PROPERTY WHERE tool='".$tool."' AND ref='".$id."'";
	$result=Database::query($sql);
	while ($row=Database::fetch_array($result)) {
		// if to_group_id is null then it is sent to a specific user
		// if to_group_id = 0 then it is sent to everybody
		if (!is_null($row['to_group_id']) ) {
			$sent_to_group[]=$row['to_group_id'];
			//echo $row['to_group_id'];
		}
		// if to_user_id <> 0 then it is sent to a specific user
		if ($row['to_user_id']<>0) {
			$sent_to_user[]=$row['to_user_id'];
		}
	}
	if (isset($sent_to_group)) {
		$sent_to['groups']=$sent_to_group;
	}
	if (isset($sent_to_user)) {
		$sent_to['users']=$sent_to_user;
	}
	return $sent_to;
}



/**
* constructs the form to display all the groups and users the message has been sent to
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @param  array $sent_to_array: a 2 dimensional array containing the groups and the users
*				the first level is a distinction between groups and users: $sent_to_array['groups'] and $sent_to_array['users']
*				$sent_to_array['groups'] (resp. $sent_to_array['users']) is also an array containing all the id's of the
*				groups (resp. users) who have received this message.
* @return html
*/
function sent_to_form($sent_to_array) {
	// we find all the names of the groups
	$group_names = get_course_groups();	
	
	// we count the number of users and the number of groups
	if (isset($sent_to_array['users'])) {
		$number_users=count($sent_to_array['users']);
	} else {
		$number_users=0;
	}
	if (isset($sent_to_array['groups'])) {
		$number_groups=count($sent_to_array['groups']);
	} else {
		$number_groups=0;
	}
	$total_numbers = $number_users + $number_groups;
	$output = array();
	// starting the form if there is more than one user/group
	if ($total_numbers > 1 ) {    	
    	//$output.="<option>".get_lang("SentTo")."</option>";
    	// outputting the name of the groups
    	if (is_array($sent_to_array['groups'])) {
    		foreach ($sent_to_array['groups'] as $group_id) {
    		    if (isset($group_names[$group_id]['name'])) {
                    $output[]= $group_names[$group_id]['name'];
    		    }
            }
        }
    	if (isset($sent_to_array['users'])) {
    		if (is_array($sent_to_array['users'])) {
    			foreach ($sent_to_array['users'] as $user_id) {
    			    // @todo add username as tooltip - is this fucntion still used ?
    				// $user_info= api_get_user_info($user_id);
                    // $username = api_htmlentities(sprintf(get_lang('LoginX'), $user_info['username']), ENT_QUOTES);
    				$output[] = api_get_person_name($user_info['firstName'], $user_info['lastName']);
                }
            }
    	}    
	} else {
	    // there is only one user/group
		if (is_array($sent_to_array['users'])) {
		    // @todo add username as tooltip - is this fucntion still used ?
			// $user_info = api_get_user_info($sent_to_array['users'][0]);
            // $username = api_htmlentities(sprintf(get_lang('LoginX'), $user_info['username']), ENT_QUOTES);
			$output[]= api_get_person_name($user_info['firstName'], $user_info['lastName']);
		}
		if (is_array($sent_to_array['groups']) and $sent_to_array['groups'][0]!==0) {
			$group_id = $sent_to_array['groups'][0];
			$output[]= $group_names[$group_id]['name'];
		}
		if (is_array($sent_to_array['groups']) and $sent_to_array['groups'][0]==0) {
			$output[]= get_lang("Everybody");
		}
	}

    if (!empty($output)) {        
        $output = array_filter($output);        
        if (count($output) > 0) {
            $output = implode(', ', $output);
        }
        return $output;
    }
}


/**
* This function displays a dropdown list that allows the course administrator do view the calendar items of one specific group
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function show_group_filter_form() {
	$group_list = get_course_groups();

	echo "<select name=\"select\" onchange=\"javascript: MM_jumpMenu('parent',this,0)\">";
	echo "<option value=\"agenda.php?group=none\">".get_lang('ShowAll')."</option>";
	foreach($group_list as $this_group) {
		echo "<option value=\"agenda.php?action=view&group=".$this_group['id']."\" ";
		echo ($this_group['id']==$_SESSION['group'])? " selected":"" ;
		echo ">".$this_group['name']."</option>";
	}
	echo "</select>";
}



/**
* This function displays a dropdown list that allows the course administrator do view the calendar items of one specific group
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function show_user_filter_form()
{
	$user_list=get_course_users();

	echo "<select name=\"select\" onchange=\"javascript: MM_jumpMenu('parent',this,0)\">";
	echo "<option value=\"agenda.php?user=none\">".get_lang('ShowAll')."</option>";
	foreach($user_list as $this_user) {
		// echo "<option value=\"agenda.php?isStudentView=true&amp;user=".$this_user['uid']."\">".api_get_person_name($this_user['firstName'], $this_user['lastName'])."</option>";
		echo "<option value=\"agenda.php?action=view&user=".$this_user['uid']."\" ";
		echo ($this_user['uid']==$_SESSION['user'])? " selected":"" ;
		echo ">".api_get_person_name($this_user['firstName'], $this_user['lastName'])."</option>";
		}
	echo "</select>";
}



/**
* This function displays a dropdown list that allows the course administrator do view the calendar items of one specific group
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function show_user_group_filter_form() {    
	echo "<select name=\"select\" onchange=\"javascript: MM_jumpMenu('parent',this,0)\">";	
    echo "<option value=\"agenda.php?user=none&action=view\">".get_lang("ShowAll")."</option>";
    
	// Groups	
	$group_list = get_course_groups();

	$group_available_to_access =array();
    $option = '';
	if (!empty($group_list)) {	    
	    $option = "<optgroup label=\"".get_lang("Groups")."\">";
		foreach($group_list as $this_group) {
			// echo "<option value=\"agenda.php?isStudentView=true&amp;group=".$this_group['id']."\">".$this_group['name']."</option>";
			$has_access = GroupManager::user_has_access(api_get_user_id(),$this_group['id'],GROUP_TOOL_CALENDAR);
			$result = GroupManager::get_group_properties($this_group['id']);

			if ($result['calendar_state']!='0') {
				$group_available_to_access[]=$this_group['id'];
			}

			// lastedit
			if ($has_access || $result['calendar_state']=='1') {
				$option.= "<option value=\"agenda.php?action=view&group=".$this_group['id']."\" ";
				$option.= ($this_group['id']==$_SESSION['group'])? " selected":"" ;
				$option.=  ">".$this_group['name']."</option>";
			}
		}
		$option.= "</optgroup>";
	}
	
	echo $option;
	
	// Users
	
	$user_list = get_course_users();
	if (!empty($user_list)) {
	    echo "<optgroup label=\"".get_lang("Users")."\">";	
    	foreach($user_list as $this_user) {
    		echo "<option value=\"agenda.php?action=view&user=".$this_user['uid']."\" ";
    		echo (isset($_SESSION['user']) && $this_user['uid']==$_SESSION['user']) ? " selected":"" ;
    		echo ">".api_get_person_name($this_user['firstName'], $this_user['lastName'])."</option>";
    	}
    	echo "</optgroup>";
	}	
	echo "</select>";
}



/**
* This tools loads all the users and all the groups who have received a specific item (in this case an agenda item)
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function load_edit_users($tool, $id) {
	$tool=Database::escape_string($tool);
	$id=Database::escape_string($id);
	$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

	$sql="SELECT * FROM $TABLE_ITEM_PROPERTY WHERE tool='$tool' AND ref='$id'";
	$result=Database::query($sql) or die (Database::error());
	while ($row=Database::fetch_array($result))
		{
		$to_group=$row['to_group_id'];
		switch ($to_group)
			{
			// it was send to one specific user
			case null:
				$to[]="USER:".$row['to_user_id'];
				break;
			// it was sent to everyone
			case 0:
				 return "everyone";
				 exit;
				break;
			default:
				$to[]="GROUP:".$row['to_group_id'];
			}
		}
	return $to;
}


/**
* This functions swithes the visibility a course resource using the visible field in 'last_tooledit' values: 0 = invisible
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function change_visibility($tool,$id,$visibility)
{
	global $_course;
	$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$tool=Database::escape_string($tool);
	$id=Database::escape_string($id);
    /*
	$sql="SELECT * FROM $TABLE_ITEM_PROPERTY WHERE tool='".TOOL_CALENDAR_EVENT."' AND ref='$id'";
	$result=Database::query($sql) or die (Database::error());
	$row=Database::fetch_array($result);
	*/
	if ($visibility == 0) {
		$sql_visibility="UPDATE $TABLE_ITEM_PROPERTY SET visibility='0' WHERE tool='$tool' AND ref='$id'";
		api_item_property_update($_course,TOOL_CALENDAR_EVENT,$id,"invisible",api_get_user_id());
	} else {
		$sql_visibility="UPDATE $TABLE_ITEM_PROPERTY SET visibility='1' WHERE tool='$tool' AND ref='$id'";
		api_item_property_update($_course,TOOL_CALENDAR_EVENT,$id,"visible",api_get_user_id());
	}
}



/**
* The links that allows the course administrator to add a new agenda item, to filter on groups or users
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function display_courseadmin_links() {
	if (!isset($_GET['action'])) {
		$actions = "<a href='agenda_js.php?type=course&".api_get_cidreq()."'>".Display::return_icon('calendar_na.png', get_lang('Agenda'),'',ICON_SIZE_MEDIUM)."</a>";
	} else {
		$actions = "<a href='agenda_js.php?type=course&".api_get_cidreq()."'>".Display::return_icon('calendar.png', get_lang('Agenda'),'',ICON_SIZE_MEDIUM)."</a>";
	}
	$actions .= "<a href='agenda.php?".api_get_cidreq()."&amp;sort=asc&amp;toolgroup=".api_get_group_id()."&action=add&amp;view=".(($_SESSION['view']=='month')?"list":Security::remove_XSS($_SESSION['view'])."&amp;origin=".Security::remove_XSS($_GET['origin']))."'>".Display::return_icon('new_event.png', get_lang('AgendaAdd'),'',ICON_SIZE_MEDIUM)."</a>";
	$actions .= "<a href='agenda.php?".api_get_cidreq()."&action=importical&amp;view=".(($_SESSION['view']=='month')?"list":Security::remove_XSS($_SESSION['view'])."&amp;origin=".Security::remove_XSS($_GET['origin']))."'>".Display::return_icon('import_calendar.png', get_lang('ICalFileImport'),'',ICON_SIZE_MEDIUM)."</a>";
	
	return $actions;
	/*
	if (empty ($_SESSION['toolgroup'])) {
		echo get_lang('SentTo');
		echo "&nbsp;&nbsp;<form name=\"filter\" style=\"display:inline;\">";
		show_user_group_filter_form();
		echo "</form> ";
	}*/
}



/**
* The links that allows the student AND course administrator to show all agenda items and sort up/down
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @author Julio Montoya removing options, changing to a simple agenda tool
*/
function display_student_links() {
	global $show;
	$month = isset($_GET['month']) ? intval($_GET['month']) : null;
	$year  = isset($_GET['year']) ? intval($_GET['year']) : null;
	
    $day_url = '&month='.$month.'&year='.$year;
	if ($_SESSION['view'] <> 'month') {	    
		echo "<a href=\"".api_get_self()."?action=view".$day_url."&toolgroup=".api_get_group_id()."&amp;view=month\">".Display::return_icon('month_empty.png', get_lang('MonthView'),'',ICON_SIZE_MEDIUM)."</a> ";
	} else {
		echo "<a href=\"".api_get_self()."?action=view".$day_url."&toolgroup=".api_get_group_id()."&amp;view=list\">".Display::return_icon('week.png', get_lang('ListView'),'',ICON_SIZE_MEDIUM)."</a> ";
	}	
	$day_url = '&month='.date('m').'&year='.date('Y').'&view='.Security::remove_XSS($_GET['view']);
	$today_url = api_get_self()."?action=view".$day_url."&toolgroup=".api_get_group_id();
	echo Display::url(get_lang('Today'), $today_url, array('class'=>'a_button white medium'));
	
	//@todo Add next events and all events?  ...
	 
	//echo Display::url(get_lang('AllEvents'), $all_url, array('class'=>'a_button white medium'));
	//echo Display::url(get_lang('Next events'), $all_url, array('class'=>'a_button white medium'));
}



/**
* get all the information of the agenda_item from the database
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @param integer the id of the agenda item we are getting all the information of
* @return an associative array that contains all the information of the agenda item. The keys are the database fields
*/
function get_agenda_item($id)
{
	global $TABLEAGENDA;
    $t_agenda_repeat = Database::get_course_table(TABLE_AGENDA_REPEAT);
    $id=Database::escape_string($id);
    $item = array();
	if(empty($id)) {
        $id=intval(Database::escape_string(($_GET['id'])));
    } else {
    	$id = (int) $id;
    }
    $course_id = api_get_course_int_id();
    if(empty($id)){return $item;}
	$sql 			    = "SELECT * FROM ".$TABLEAGENDA." WHERE id='".$id."' AND c_id = $course_id ";
	$result					= Database::query($sql);
	$entry_to_edit 			= Database::fetch_array($result);
	$item['title']			= $entry_to_edit["title"];
	$item['content']		= $entry_to_edit["content"];
	$item['start_date']		= $entry_to_edit["start_date"];
	$item['end_date']		= $entry_to_edit["end_date"];
	$item['to']				= load_edit_users(TOOL_CALENDAR_EVENT, $id);
	// if the item has been sent to everybody then we show the compact to form
	if ($item['to']=="everyone")
	{
		$_SESSION['allow_individual_calendar']="hide";
	}
	else
	{
		$_SESSION['allow_individual_calendar']="show";
	}
    $item['repeat'] = false;
    $sql = "SELECT * FROM $t_agenda_repeat WHERE cal_id = $id";
    $res = Database::query($sql);
    if(Database::num_rows($res)>0)
    {
        //this event is repetitive
        $row = Database::fetch_array($res);
        $item['repeat'] = true;
        $item['repeat_type'] = $row['cal_type'];
        $item['repeat_end'] = $row['cal_end'];
        $item['repeat_frequency'] = $row['cal_frequency']; //unused in 1.8.5 RC1 - will be used later to say if once every 2 or 3 weeks, for example
        $item['repeat_days'] = $row['cal_days']; //unused in 1.8.5 RC1 - will be used later
    }
    //TODO - add management of repeat exceptions
	return $item;
}

/**
* This is the function that updates an agenda item. It does 3 things
* 1. storethe start_date, end_date, title and message in the calendar_event table
* 2. store the groups/users who this message is meant for in the item_property table
* 3. modify the attachments (if needed)
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @author Julio Montoya Adding UTC support
*/
function store_edited_agenda_item($id_attach, $file_comment) {
	global $_course;

	// database definitions
	$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

	// STEP 1: editing the calendar_event table
	// 1.a.  some filtering of the input data
	$id            = (int)$_POST['id'];
	$title         = strip_tags(trim($_POST['title'])); // no html allowed in the title
	$content       = trim($_POST['content']);
	$start_date    = (int)$_POST['fyear']."-".(int)$_POST['fmonth']."-".(int)$_POST['fday']." ".(int)$_POST['fhour'].":".(int)$_POST['fminute'].":00";
	$end_date      = (int)$_POST['end_fyear']."-".(int)$_POST['end_fmonth']."-".(int)$_POST['end_fday']." ".(int)$_POST['end_fhour'].":".(int)$_POST['end_fminute'].":00";
	$to            = $_POST['selectedform'];
	
	$start_date    = api_get_utc_datetime($start_date);
	$end_date      = api_get_utc_datetime($end_date);
	
	if ($_POST['empty_end_date'] == 'on') {
		$end_date = "0000-00-00 00:00:00";
	}
	
	// 1.b. the actual saving in calendar_event table
	$edit_result  = save_edit_agenda_item($id, $title, $content, $start_date, $end_date);
	 
	if (empty($id_attach)) {
		add_agenda_attachment_file($file_comment, $id);
	} else {
		edit_agenda_attachment_file($file_comment,$id,$id_attach);
	}

	// step 2: editing the item_propery table (=delete all and add the new destination users/groups)
	if ($edit_result=true) {
		// 2.a. delete everything for the users
		$sql_delete="DELETE FROM ".$TABLE_ITEM_PROPERTY." WHERE ref='$id' AND tool='".TOOL_CALENDAR_EVENT."'";

		$result = Database::query($sql_delete) or die (Database::error());
		// 2.b. storing the new users/groups
		if (!is_null($to)) // !is_null($to): when no user is selected we send it to everyone
		{
			$send_to=separate_users_groups($to);
			// storing the selected groups
			if (is_array($send_to['groups'])) {
				foreach ($send_to['groups'] as $group)
				{
					api_item_property_update($_course, TOOL_CALENDAR_EVENT, $id,"AgendaModified", api_get_user_id(), $group,'',$start_date, $end_date);
				}
			}
			// storing the selected users
			if (is_array($send_to['users'])) {
				foreach ($send_to['users'] as $user) {
					api_item_property_update($_course, TOOL_CALENDAR_EVENT, $id,"AgendaModified", api_get_user_id(),'',$user, $start_date,$end_date);
				}
			}
		} else {
		    // the message is sent to everyone, so we set the group to 0
			api_item_property_update($_course, TOOL_CALENDAR_EVENT, $id,"AgendaModified", api_get_user_id(), '','',$start_date,$end_date);
		}

	} //if ($edit_result=true)

	// step 3: update the attachments (=delete all and add those in the session
	update_added_resources("Agenda", $id);

	// return the message;
	Display::display_confirmation_message(get_lang("EditSuccess"));
}


/**
* This function stores the Agenda Item in the table calendar_event and updates the item_property table also (after an edit)
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function save_edit_agenda_item($id,$title,$content,$start_date,$end_date) {
	$TABLEAGENDA= Database::get_course_table(TABLE_AGENDA);
	$id			= Database::escape_string($id);
	$title		= Database::escape_string($title);
	$content 	= Database::escape_string($content);
	$start_date	= Database::escape_string($start_date);
	$end_date	= Database::escape_string($end_date);

	// store the modifications in the table calendar_event
	$sql = "UPDATE ".$TABLEAGENDA."
			SET title		='".$title."',
				content		='".$content."',
				start_date	='".$start_date."',
				end_date	='".$end_date."'
			WHERE id='".$id."'";
	$result = Database::query($sql);
	return true;
}

/**
* This is the function that deletes an agenda item.
* The agenda item is no longer fycically deleted but the visibility in the item_property table is set to 2
* which means that it is invisible for the student AND course admin. Only the platform administrator can see it.
* This will in a later stage allow the platform administrator to recover resources that were mistakenly deleted
* by the course administrator
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @param integer the id of the agenda item wa are deleting
*/
function delete_agenda_item($id) {
	global $_course;
	$id=Database::escape_string($id);
	if (api_is_allowed_to_edit(false,true)  OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous())) {
		if (!empty($_GET['id']) && isset($_GET['action']) && $_GET['action']=="delete") {
		    $t_agenda     = Database::get_course_table(TABLE_AGENDA);
            $t_agenda_r   = Database::get_course_table(TABLE_AGENDA_REPEAT);
            $id=intval($_GET['id']);
            $sql = "SELECT * FROM $t_agenda_r WHERE cal_id = $id";
            $res = Database::query($sql);
            if(Database::num_rows($res)>0) {
            	$sql_children = "SELECT * FROM $t_agenda WHERE parent_event_id = $id";
                $res_children = Database::query($sql_children);
                if(Database::num_rows($res_children)>0) {
                    while ($row_child = Database::fetch_array($res_children)) {
                        api_item_property_update($_course,TOOL_CALENDAR_EVENT,$row_child['id'],'delete',api_get_user_id());
                    }
                }
                $sql_del = "DELETE FROM $t_agenda_r WHERE cal_id = $id";
                $res_del = Database::query($sql_del);
            }
			//$sql = "DELETE FROM ".$TABLEAGENDA." WHERE id='$id'";
			//$sql= "UPDATE ".$TABLE_ITEM_PROPERTY." SET visibility='2' WHERE tool='Agenda' and ref='$id'";
			//$result = Database::query($sql) or die (Database::error());
			api_item_property_update($_course,TOOL_CALENDAR_EVENT,$id,'delete',api_get_user_id());

			// delete the resources that were added to this agenda item
			// 2DO: as we no longer fysically delete the agenda item (to make it possible to 'restore'
			//		deleted items, we should not delete the added resources either.
			// delete_added_resource("Agenda", $id); // -> this is no longer needed as the message is not really deleted but only visibility=2 (only platform admin can see it)

			//resetting the $id;
			$id=null;

			// displaying the result message in the yellow box
			Display::display_confirmation_message(get_lang("AgendaDeleteSuccess"));
		}	  // if (isset($id)&&$id&&isset($action)&&$action=="delete")
	} // if ($is_allowed_to_edit)

}
/**
* Makes an agenda item visible or invisible for a student
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @param integer id the id of the agenda item we are changing the visibility of
*/
function showhide_agenda_item($id) {
	global $nameTools;
	/*
				SHOW / HIDE A CALENDAR ITEM
	*/
	//  and $_GET['isStudentView']<>"false" is added to prevent that the visibility is changed after you do the following:
	// change visibility -> studentview -> course manager view
	if ((api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous())) and $_GET['isStudentView']<>"false") {
		if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action']=="showhide") {
			$id=(int)addslashes($_GET['id']);
			if (isset($_GET['next_action']) && $_GET['next_action'] == strval(intval($_GET['next_action']))) {
				$visibility = $_GET['next_action'];
				change_visibility($nameTools,$id,$visibility);
				Display::display_confirmation_message(get_lang("VisibilityChanged"));
			}
		}
	}
}
/**
* Displays all the agenda items
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @author Yannick Warnier <yannick.warnier@beeznest.com> - cleanup
* @author Julio Montoya <gugli100@gmail.com> - Refactoring
*/
function display_agenda_items($agenda_items, $day = false) {
    global $charset;
    if (isset($day) && $day) {
        $new_items = array();
        foreach($agenda_items as $item) {
            if (substr($item['start_date'],8,2) == $day) {            
                $new_items[] = $item;
            }            
        }
        $agenda_items = $new_items;
    }
    
    if (isset($_GET['sort']) &&  $_GET['sort'] == 'asc') {
        $sort_inverse = 'desc';
        $sort = 'asc';
    }  else {
        $sort_inverse = 'asc';
        $sort = 'desc';    
    }
    
    if (isset($_GET['col']) &&  $_GET['col'] == 'end') {    
        $sort_item = 'end_date_tms';
        $col = 'end';        
    } else {
        $sort_item = 'start_date_tms';
        $col = 'start';
    }

    $agenda_items = msort($agenda_items, $sort_item, $sort);
    
    //DISPLAY: NO ITEMS
    if (empty($agenda_items)) {
        echo Display::display_warning_message(get_lang('NoAgendaItems'));
    } else {                
        echo '<table class="data_table">';
        $th = Display::tag('th', get_lang('Title'));

        $month = isset($_GET['month']) ? intval($_GET['month']) : null;
        $year  = isset($_GET['year']) ? intval($_GET['year']) : null;
        $day   = isset($_GET['day']) ?  intval($_GET['day']) : null;
        
        
        $url = api_get_self().'?'.api_get_cidreq().'&month='.$month.'&year='.$year.'&day='.$day; 
    
        $th .= Display::tag('th', Display::url(get_lang('StartTimeWindow'), $url.'&sort='.$sort_inverse.'&col=start'));
        $th .= Display::tag('th', Display::url(get_lang('EndTimeWindow'), $url.'&sort='.$sort_inverse.'&col=end'));
        
        if ((api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))) {
			$th .= Display::tag('th', get_lang('Modify'));
        }
       
        echo Display::tag('tr', $th); 
        $counter = 0;
       	foreach ($agenda_items as $myrow) {       	    
        	$is_repeated = !empty($myrow['parent_event_id']);	      
                		
    		$class = 'row_even';
            if ($counter % 2) {
                $class = 'row_odd'; 
            }
            /*	display: the icon, title, destinees of the item	*/
        	echo '<tr class="'.$class.'">';
            	
            //Title
        	echo "<td>";
        	$attach_icon = '';
        	
        	// attachment list
            $attachment_list = get_attachment($myrow['id']);
            
        	if (!empty($attachment_list)) {
                $attach_icon = ' '.Display::return_icon('attachment.gif', get_lang('Attachment'));
        	}        	
        	$title_class = '';
        	if (isset($myrow['visibility']) && $myrow['visibility'] == 0) {
                $title_class = 'invisible';     
        	}
        	
        	switch($myrow['calendar_type']) {
        	    case 'global':
        	        $icon_type = Display::return_icon('view_remove.png', get_lang('GlobalEvent'), array(), 22);
        	        echo $icon_type.' '.$myrow['title'].$attach_icon;
        	        break;
        	    case 'personal':
        	        $icon_type = Display::return_icon('user.png', get_lang('   '), array(), 22);
        	        echo $icon_type.' '.$myrow['title'].$attach_icon;
                    break;    
        	    case 'course':
        	        $icon_type = Display::return_icon('course.png',get_lang('Course'), array(), 22);
        	        $agenda_url = api_get_path(WEB_CODE_PATH).'calendar/agenda.php?agenda_id='.$myrow['id'].'&action=view';
        	        echo Display::url($icon_type.' '.$myrow['title'].$attach_icon, $agenda_url, array('class' => $title_class));
                    break;
        	}        	 
        	echo '</td>';   	
        	    
            //Start date    
        	echo '<td>';
        	if (!empty($myrow['start_date']) && $myrow['start_date'] != '0000-00-00 00:00:00') {  
        	   echo api_format_date($myrow['start_date']);
        	}
        	echo '</td>';    
        	
        	//End date
            echo '<td>';
            if (!empty($myrow['end_date']) && $myrow['end_date'] != '0000-00-00 00:00:00') {    
                echo api_format_date($myrow['end_date']);
            }
            echo '</td>';
        	
            /*Display: edit delete button (course admin only) */
    		if (!$is_repeated && (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous())) && $myrow['calendar_type'] == 'course') {
    		    echo '<td align="center">';
        		if (!(api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, $myrow['id']))) {
                    
        			// a coach can only delete an element belonging to his session
    				$mylink = api_get_self().'?'.api_get_cidreq().'&origin='.Security::remove_XSS($_GET['origin']).'&id='.$myrow['id'].'&sort='.$sort.'&col='.$col.'&';
    	    	
    	    		// edit
        			echo '<a href="'.$mylink.api_get_cidreq()."&toolgroup=".Security::remove_XSS($_GET['toolgroup']).'&action=edit&id_attach='.$attachment_list['id'].'" title="'.get_lang("ModifyCalendarItem").'">';
    	    		echo Display::return_icon('edit.png', get_lang('ModifyCalendarItem'),'',ICON_SIZE_SMALL)."</a>";
    
        			echo '<a href="'.$mylink.api_get_cidreq()."&toolgroup=".Security::remove_XSS($_GET['toolgroup']).'&action=announce" title="'.get_lang("AddAnnouncement").'">';
        			echo Display::return_icon('new_announce.png', get_lang('AddAnnouncement'), array (),ICON_SIZE_SMALL)."</a> ";
    
    	    		if ($myrow['visibility'] == 1) {
    	    			$image_visibility = "visible";
    					$text_visibility = get_lang("Hide");
    					$next_action = 0;
    	    		} else {
    	    			$image_visibility = "invisible";
    					$text_visibility = get_lang("Show");
    					$next_action = 1;
    	    		}
        			echo '<a href="'.$mylink.api_get_cidreq().'&toolgroup='.Security::remove_XSS($_GET['toolgroup']).'&action=showhide&next_action='.$next_action.'" title="'.$text_visibility.'">'.Display::return_icon($image_visibility.'.png', $text_visibility,'',ICON_SIZE_SMALL).'</a> ';    			
        			echo "<a href=\"".$mylink.api_get_cidreq()."&toolgroup=".Security::remove_XSS($_GET['toolgroup'])."&action=delete\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."')) return false;\"  title=\"".get_lang("Delete")."\"> ";
                    echo Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL)."&nbsp;</a>";
    			}
    			    
    	    	$mylink = 'ical_export.php?'.api_get_cidreq().'&type=course&id='.$myrow['id'];
    			//echo '<a class="ical_export" href="'.$mylink.'&class=confidential" title="'.get_lang('ExportiCalConfidential').'">'.Display::return_icon($export_icon_high, get_lang('ExportiCalConfidential')).'</a> ';
    	    	//echo '<a class="ical_export" href="'.$mylink.'&class=private" title="'.get_lang('ExportiCalPrivate').'">'.Display::return_icon($export_icon_low, get_lang('ExportiCalPrivate')).'</a> ';
    	    	//echo '<a class="ical_export" href="'.$mylink.'&class=public" title="'.get_lang('ExportiCalPublic').'">'.Display::return_icon($export_icon, get_lang('ExportiCalPublic')).'</a> ';
    		    echo '<a href="#" onclick="javascript:win_print=window.open(\'print.php?id='.$myrow['id'].'\',\'popup\',\'left=100,top=100,width=700,height=500,scrollbars=1,resizable=0\'); win_print.focus(); return false;">'.Display::return_icon('printer.png', get_lang('Print'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
    		    echo '</td>';    		      
    		} else {    		    
                if ($is_repeated && (api_is_allowed_to_edit(false,true) || api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous() ))  {                    
                    echo '<td align="center">';                    
                    echo get_lang('RepeatedEvent'),' <a href="',api_get_self(),'?',api_get_cidreq(),'&agenda_id=',$myrow['parent_event_id'],'" alt="',get_lang('RepeatedEventViewOriginalEvent'),'">',get_lang('RepeatedEventViewOriginalEvent'),'</a>';
                    echo '</td>';                  
                }                     
                if ((api_is_allowed_to_edit(false,true) || (api_get_course_setting('allow_user_edit_agenda')&& !api_is_anonymous()) ) && ($myrow['calendar_type'] == 'personal' OR $myrow['calendar_type'] == 'global') ) {
                    echo '<td align="center">';
                    echo '</td>';
                }               
    		}
        	$counter++;            
        	echo "</tr>";        	
        } // end while ($myrow=Database::fetch_array($result))
        echo "</table><br /><br />";
    }
    
    if(!empty($event_list)) {
    	$event_list=api_substr($event_list,0,-1);
    } else {
    	$event_list='0';
    }
    echo "<form name=\"event_list_form\"><input type=\"hidden\" name=\"event_list\" value=\"$event_list\" /></form>";

    // closing the layout table
    echo "</td>",
    	"</tr>",
    	"</table>";
}

/**
 * Show a list with all the attachments according to the post's id
 * @param the post's id
 * @return array with the post info
 * @author Christian Fasanando
 * @version November 2008, dokeos 1.8.6
 */

function get_attachment($agenda_id, $course_id = null) {
	$agenda_table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
    if (empty($course_id)) {
        $course_id = api_get_course_int_id();
    } else {
        $course_id = intval($course_id);
    }
	$agenda_id=Database::escape_string($agenda_id);
	$row=array();
	$sql = 'SELECT id,path, filename,comment FROM '. $agenda_table_attachment.' WHERE c_id = '.$course_id.' AND agenda_id = '.(int)$agenda_id.'';
	$result=Database::query($sql);
	if (Database::num_rows($result)!=0) {
		$row=Database::fetch_array($result);
	}
	return $row;
}

/**
* Displays only 1 agenda item. This is used when an agenda item is added to the learning path.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function display_one_agenda_item($agenda_id) {
	global $TABLEAGENDA;
	global $TABLE_ITEM_PROPERTY;
	global $select_month, $select_year;
	global $DaysShort, $DaysLong, $MonthsLong;
	global $is_courseAdmin;
	global $dateFormatLong, $timeNoSecFormat, $charset;

	// getting the name of the groups
	$group_names = get_course_groups();
	
	$agenda_id = intval($agenda_id);
    if (!(api_is_allowed_to_edit(false,true) || (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))) {        
        $visibility_condition = " AND ip.visibility='1' ";                
    }
        
	$sql = "SELECT agenda.*, ip.visibility, ip.to_group_id, ip.insert_user_id, ip.ref
			FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." ip
			WHERE agenda.id = ip.ref
			AND ip.tool='".TOOL_CALENDAR_EVENT."'
			$visibility_condition
			AND agenda.id='$agenda_id'";
			
	$result=Database::query($sql);
	$number_items=Database::num_rows($result);
	$myrow = Database::fetch_array($result,'ASSOC'); // there should be only one item so no need for a while loop

    $sql_rep = "SELECT * FROM $TABLEAGENDA WHERE id = $agenda_id AND parent_event_id IS NOT NULL AND parent_event_id !=0";
    $res_rep = Database::query($sql_rep);
    $repeat = false;
    $repeat_id = 0;
    if (Database::num_rows($res_rep) > 0) {
        $repeat=true;
        $row_rep = Database::fetch_array($res_rep);
        $repeat_id = $row_rep['parent_event_id'];
    }

    // DISPLAY: NO ITEMS
	if ($number_items==0) {
		Display::display_warning_message(get_lang("NoAgendaItems"));
		return false;
	}

	// DISPLAY: THE ITEMS
	echo "<table id=\"data_table\" class=\"data_table\">";

	// DISPLAY : the icon, title, destinees of the item
	$myrow["start_date"] = api_get_local_time($myrow["start_date"]);

	// highlight: if a date in the small calendar is clicked we highlight the relevant items
	$db_date = (int)api_format_date($myrow["start_date"], "%d").intval(api_format_date($myrow["start_date"], "%m")).api_format_date($myrow["start_date"], "%Y");
    if ($_GET["day"].$_GET["month"].$_GET["year"] <> $db_date) {
		if ($myrow['visibility']=='0') {
			$style="data_hidden";
			$stylenotbold="datanotbold_hidden";
			$text_style="text_hidden";
		} else {
			$style="data";
			$stylenotbold="datanotbold";
			$text_style="text";
		}
	} else {
		$style="datanow";
		$stylenotbold="datanotboldnow";
		$text_style="textnow";
	}
	echo Display::tag('h2', $myrow['title']);	
	echo "<tr>";
		
	if (api_is_allowed_to_edit(false,true)) {
		if (!(api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, $myrow['id']))) {
		    
		    // a coach can only delete an element belonging to his session
		    // DISPLAY: edit delete button (course admin only)
            $export_icon = '../img/export.png';
            $export_icon_low = '../img/export_low_fade.png';
            $export_icon_high = '../img/export_high_fade.png';
            
            echo '<th style="text-align:right">';
            if (!$repeat && api_is_allowed_to_edit(false,true)) {
                // edit
                $mylink = api_get_self()."?".api_get_cidreq()."&origin=".Security::remove_XSS($_GET['origin'])."&id=".$myrow['id'];
                if (!empty($_GET['agenda_id'])) {
                    // rather ugly hack because the id parameter is already set above but below we set it again
                    $mylink .= '&agenda_id='.Security::remove_XSS($_GET['agenda_id']).'&id='.Security::remove_XSS($_GET['agenda_id']);
                }                
                if ($myrow['visibility'] == 1) {
                    $image_visibility="visible";
                    $next_action = 0;
                } else {
                    $image_visibility="invisible";
                    $next_action = 1;
                }
                
                echo '<a href="'.$mylink.'&action=showhide&next_action='.$next_action.'">'.Display::return_icon($image_visibility.'.png', get_lang('Visible'),'',ICON_SIZE_SMALL).'</a>';
                
                echo    "<a href=\"".$mylink."&action=edit\">",
                        Display::return_icon('edit.png', get_lang('ModifyCalendarItem'),'',ICON_SIZE_SMALL), "</a>",
                        "<a href=\"".$mylink."&action=delete\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."')) return false;\">",
                        Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL),"</a>";
                        
            }
            $mylink = 'ical_export.php?'.api_get_cidreq().'&type=course&id='.$myrow['id'];
            //echo '<a class="ical_export" href="'.$mylink.'&class=confidential" title="'.get_lang('ExportiCalConfidential').'">'.Display::return_icon($export_icon_high, get_lang('ExportiCalConfidential')).'</a> ';
            //echo '<a class="ical_export" href="'.$mylink.'&class=private" title="'.get_lang('ExportiCalPrivate').'">'.Display::return_icon($export_icon_low, get_lang('ExportiCalPrivate')).'</a> ';
            //echo '<a class="ical_export" href="'.$mylink.'&class=public" title="'.get_lang('ExportiCalPublic').'">'.Display::return_icon($export_icon, get_lang('ExportiCalPublic')).'</a> ';
            echo '<a href="javascript: void(0);" onclick="javascript:win_print=window.open(\'print.php?id='.$myrow['id'].'\',\'popup\',\'left=100,top=100,width=700,height=500,scrollbars=1,resizable=0\'); win_print.focus(); return false;">'.Display::return_icon('printer.png', get_lang('Print'),'',ICON_SIZE_SMALL).'</a>&nbsp;';
            echo "</th>";               
        }
	}
	
	// title
	echo "<tr class='row_odd'>";
	echo '<td colspan="2">'.get_lang("StartTime").": ";
	echo api_format_date($myrow['start_date']);
	echo "</td>";
	echo "</td>";
	echo "<tr class='row_odd'>";
	echo '<td colspan="2">'.get_lang("EndTime").": ";
	echo api_convert_and_format_date($myrow['end_date']);
	echo "</td>";


    
	// Content
	$content = $myrow['content'];
	$content = make_clickable($content);
	$content = text_filter($content);

    echo '<tr class="row_even">';
    echo '<td '.(api_is_allowed_to_edit()?'colspan="3"':'colspan="2"'). '>';
    echo $content;
    echo '</td></tr>';
    
    //Attachments
    $attachment_list = get_attachment($agenda_id);
    
    if (!empty($attachment_list)) {
        echo '<tr class="row_even"><td colspan="2">';    
        $realname=$attachment_list['path'];
        $user_filename=$attachment_list['filename'];
        $full_file_name = 'download.php?file='.$realname;
        echo Display::return_icon('attachment.gif',get_lang('Attachment'));
        echo '<a href="'.$full_file_name.'"> '.$user_filename.'</a>';
         if (api_is_allowed_to_edit()) {
            echo '&nbsp;&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.Security::remove_XSS($_GET['origin']).'&action=delete_attach&id_attach='.$attachment_list['id'].'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a><br />';
        }
        echo '<br /><span class="forum_attach_comment" >'.$attachment_list['comment'].'</span>';
        echo '</td></tr>';           
    }
            
    // the message has been sent to
    echo '<tr>';
    echo "<td class='announcements_datum'>".get_lang("SentTo").": ";
    $sent_to=sent_to(TOOL_CALENDAR_EVENT, $myrow["ref"]);
    $sent_to_form=sent_to_form($sent_to);
    echo $sent_to_form;
    echo "</td></tr>";
    
    if ($repeat) {
        echo '<tr>';
        echo '<td colspan="2">';
        echo get_lang('RepeatedEvent').' <a href="',api_get_self(),'?',api_get_cidreq(),'&agenda_id=',$repeat_id,'" alt="',get_lang('RepeatedEventViewOriginalEvent'),'">',get_lang('RepeatedEventViewOriginalEvent'),'</a>';
        echo '</td>';
        echo '</tr>';
    }   
    

	/* Added resources */
	if (check_added_resources("Agenda", $myrow["id"])) {
		echo "<tr><td colspan='3'>";
		echo "<i>".get_lang("AddedResources")."</i><br/>";
		if ($myrow['visibility'] == 0 ) {
			$addedresource_style="invisible";
		}
		display_added_resources("Agenda", $myrow["id"], $addedresource_style);
		echo "</td></tr>";
	}

	// closing the layout table
	echo "</td>",
		"</tr>",
		"</table>";
}


/**
* Show the form for adding a new agenda item. This is the same function that is used whenever we are editing an
* agenda item. When the id parameter is empty (default behaviour), then we show an empty form, else we are editing and
* we have to retrieve the information that is in the database and use this information in the forms.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @param integer id, the id of the agenda item we are editing. By default this is empty which means that we are adding an
*		 agenda item.
*/
function show_add_form($id = '') {

	global $MonthsLong;
	$htmlHeadXtra[] = to_javascript();
	// the default values for the forms
	if ($_GET['originalresource'] !== 'no') {
		$day	= date('d');
		$month	= date('m');
		$year	= date('Y');
		$hours	= 9;
		$minutes= '00';

		$end_day	= date('d');
		$end_month	= date('m');
		$end_year	= date('Y');
		$end_hours	= 17;
		$end_minutes= '00';
        $repeat = false;
	} else {
		// we are coming from the resource linker so there might already have been some information in the form.
		// When we clicked on the button to add resources we stored every form information into a session and now we
		// are doing the opposite thing: getting the information out of the session and putting it into variables to
		// display it in the forms.
		$form_elements=$_SESSION['formelements'];
		$day=$form_elements['day'];
		$month=$form_elements['month'];
		$year=$form_elements['year'];
		$hours=$form_elements['hour'];
		$minutes=$form_elements['minutes'];
		$end_day=$form_elements['end_day'];
		$end_month=$form_elements['end_month'];
		$end_year=$form_elements['end_year'];
		$end_hours=$form_elements['end_hours'];
		$end_minutes=$form_elements['end_minutes'];
		$title=$form_elements['title'];
		$content=$form_elements['content'];
		$id=$form_elements['id'];
		
		$to=$form_elements['to'];
        $repeat = $form_elements['repeat'];
	}

	//	switching the send to all/send to groups/send to users
	if (isset($_POST['To']) && $_POST['To']) {
		$day			= $_POST['fday'];
		$month			= $_POST['fmonth'];
		$year			= $_POST['fyear'];
		$hours			= $_POST['fhour'];
		$minutes		= $_POST['fminute'];
		$end_day		= $_POST['end_fday'];
		$end_month		= $_POST['end_fmonth'];
		$end_year		= $_POST['end_fyear'];
		$end_hours		= $_POST['end_fhour'];
		$end_minutes	= $_POST['end_fminute'];
		$title 			= $_POST['title'];
		$content		= $_POST['content'];
		// the invisible fields
		$action			= $_POST['action'];
		$id				= $_POST['id'];
		$repeat         = !empty($_POST['repeat'])?true:false;
	}
		
	$default_no_empty_end_date = 0;
	
	// if the id is set then we are editing an agenda item
	if (!empty($id)) { 
		//echo "before get_agenda_item".$_SESSION['allow_individual_calendar'];
		$item_2_edit = get_agenda_item($id);		

		$title	= $item_2_edit['title'];
		$content= $item_2_edit['content'];

		// start date
		if ($item_2_edit['start_date'] != '0000-00-00 00:00:00') {
			$item_2_edit['start_date'] = api_get_local_time($item_2_edit['start_date']);
			list($datepart, $timepart) = split(" ", $item_2_edit['start_date']);
			list($year, $month, $day)  = explode("-", $datepart);
			list($hours, $minutes, $seconds) = explode(":", $timepart);
		}

		// end date
		if ($item_2_edit['end_date'] != '0000-00-00 00:00:00') {
			$item_2_edit['end_date'] = api_get_local_time($item_2_edit['end_date']);
		
			list($datepart, $timepart) = split(" ", $item_2_edit['end_date']);
			list($end_year, $end_month, $end_day) = explode("-", $datepart);
			
			list($end_hours, $end_minutes, $end_seconds) = explode(":", $timepart);
		} elseif($item_2_edit['end_date'] == '0000-00-00 00:00:00') {
			$default_no_empty_end_date = 1;
		}
		// attachments
		edit_added_resources("Agenda", $id);
		$to=$item_2_edit['to'];
	}
	$content	= stripslashes($content);
	$title		= stripslashes($title);
	// we start a completely new item, we do not come from the resource linker
	if (isset($_GET['originalresource']) && $_GET['originalresource']!=="no" and $_GET['action']=="add") {
		$_SESSION["formelements"]=null;
		unset_session_resources();
	}
	$origin = isset($_GET['origin']) ? Security::remove_XSS($_GET['origin']) : null;
?>

	<!-- START OF THE FORM  -->

	<form enctype="multipart/form-data"  action="<?php echo api_get_self().'?origin='.$origin.'&'.api_get_cidreq()."&sort=asc&toolgroup=".Security::remove_XSS($_GET['toolgroup']).'&action='.Security::remove_XSS($_GET['action']); ?>" method="post" name="new_calendar_item">
	<input type="hidden" name="id" value="<?php if (isset($id)) echo $id; ?>" />
	<input type="hidden" name="action" value="<?php if (isset($_GET['action'])) echo $_GET['action']; ?>" />
	<input type="hidden" name="id_attach" value="<?php echo isset($_REQUEST['id_attach']) ? intval($_REQUEST['id_attach']) : null; ?>" />
	<input type="hidden" name="sort" value="asc" />
	<input type="hidden" name="submit_event" value="ok" />
	<?php
	// The form title
	if (isset($id) AND $id<>'') {
		$form_title = get_lang('ModifyCalendarItem');
	} else {
		$form_title = get_lang('AddCalendarItem');
	}
	echo '<legend>'.$form_title.'</legend>';
	
	// the title of the agenda item
	echo 	'<div class="row">
							<div class="label">
								<span class="form_required">*</span>'.get_lang('ItemTitle').'
							</div>
							<div class="formw">
								<div id="err_title" style="display:none;color:red"></div>
								<input type="text" id="agenda_title" size="50" name="title" value="';
	if (isset($title)) echo $title;
	echo				'" />
							</div>
						</div>';
	
	
	
	// selecting the users / groups
    $group_id = api_get_group_id();
	if (isset ($group_id) && !empty($group_id)) {
		echo '<input type="hidden" name="selected_form[0]" value="GROUP:'.$group_id.'"/>' ;
		echo '<input type="hidden" name="To" value="true"/>' ;
	} else {
		echo '<div class="row">
					<div class="label">
						'.Display::return_icon('group.png', get_lang('To'), array ('align' => 'absmiddle'),ICON_SIZE_SMALL).' '.get_lang('To').'</a>
					</div>
					<div class="formw">';
		/*if ((isset($_GET['id'])  && $to=='everyone') || !isset($_GET['id'])) {
			echo get_lang('Everybody').'&nbsp;';
		}*/
		show_to_form($to);
		/*if (isset($_GET['id']) && $to!='everyone') {
			echo '<script>document.getElementById(\'recipient_list\').style.display=\'block\';</script>';
		}*/
		echo '</div>
				</div>';
	}
	
	// start date and time
	echo '<div class="row">';
	echo '<div class="label">'.get_lang('StartDate').'</div>
				<div class="formw">
					<div id="err_date" style="display:none;color:red"></div>
					<div id="err_start_date" style="display:none;color:red"></div>';
	?>		
				<select name="fday" onchange="javascript:document.new_calendar_item.end_fday.value=this.value;">
				<?php
				// small loop for filling all the dates
				// 2do: the available dates should be those of the selected month => february is from 1 to 28 (or 29) and not to 31

				foreach (range(1, 31) as $i) {
					// values have to have double digits
					$value = ($i <= 9 ? '0'.$i : $i );
					// the current day is indicated with [] around the date
					if ($value==$day) {
						echo "<option value=\"".$value."\" selected> ".$i." </option>";
					} else {
						echo "<option value=\"$value\">$i</option>";
					}
				}
			 ?>
				</select>
				<select name="fmonth" onchange="javascript:document.new_calendar_item.end_fmonth.value=this.value;">
				<?php
				for ($i=1; $i<=12; $i++) {
					// values have to have double digits
					if ($i<=9) {
						$value="0".$i;
					} else {
						$value=$i;
					}
					if ($value==$month) {
						echo "<option value=\"".$value."\" selected>".$MonthsLong[$i-1]."</option>";
					} else {
						echo "<option value=\"".$value."\">".$MonthsLong[$i-1]."</option>";
					}
				} ?>
				</select>
				<select name="fyear" onchange="javascript:document.new_calendar_item.end_fyear.value=this.value;">
					<option value="<?php echo ($year-1); ?>"><?php echo ($year-1); ?></option>
						<option value="<?php echo $year; ?>" selected="selected"><?php echo $year; ?></option>
						<?php
							for ($i=1; $i<=5; $i++) {
								$value=$year+$i;
								echo "<option value=\"$value\">$value</option>";
							} ?>
				</select>

				<a href="javascript:openCalendar('new_calendar_item', 'f')">
					<?php Display::display_icon('calendar_select.gif', get_lang('Select'), array ('style' => 'vertical-align: middle;')); ?>
				</a>
				
				&nbsp;<?php echo get_lang('StartTime').": "; ?>&nbsp;
					<select name="fhour" onchange="javascript:document.new_calendar_item.end_fhour.value=this.value;">
						<!-- <option value="--">--</option> -->
						<?php
						foreach (range(0, 23) as $i) {
							// values have to have double digits
							$value = ($i <= 9 ? '0'.$i : $i );
							// the current hour is indicated with [] around the hour
							if ($hours==$value) {
								echo "<option value=\"".$value."\" selected> ".$value." </option>";
							} else {
								echo "<option value=\"$value\">$value</option>";
							}
						} ?>
					</select>
					<select name="fminute" onchange="javascript:document.new_calendar_item.end_fminute.value=this.value;">
						<!-- <option value="<?php echo $minutes ?>"><?php echo $minutes; ?></option> -->
						<!-- <option value="--">--</option> -->
						<?php
							foreach (range(0, 59) as $i) {
								// values have to have double digits
								$value = ($i <= 9 ? '0'.$i : $i );
								if ($minutes == $value) {
									echo "<option value=\"".$value."\" selected> ".$value." </option>";
								} else {
									echo "<option value=\"$value\">$value</option>";
								}
							} ?>
					</select>
	<?php
	echo 	'	</div>
			</div>';
	
	echo '<script>
				$(function() {				
					$("#empty_end_date").click(function(){
						if ($("#empty_end_date").is(":checked")) {
							$("#end_date_span").hide();
						} else {
							$("#end_date_span").show();
						}
					});';						
	if (isset($item_2_edit['end_date']) && $item_2_edit['end_date'] == '0000-00-00 00:00:00') {
		echo '$("#end_date_span").hide();';
	} echo '
		});
		</script>';

	// end date and time
	echo '<span id="end_date_span">';
	echo 	'<div class="row">
				<div class="label">
					'.get_lang('EndDate').'
				</div>
				<div class="formw">
					<div id="err_end_date" style="display:none;color:red"></div>';
	?>	
	
						<select id="end_fday" name="end_fday">
							<?php
								// small loop for filling all the dates
								// 2do: the available dates should be those of the selected month => february is from 1 to 28 (or 29) and not to 31

								foreach (range(1, 31) as $i) {
									// values have to have double digits
									$value = ($i <= 9 ? '0'.$i : $i );
									// the current day is indicated with [] around the date
									if ($value==$end_day)
										{ echo "<option value=\"".$value."\" selected> ".$i." </option>";}
									else
										{ echo "<option value=\"".$value."\">".$i."</option>"; }
									}?>
						</select>
							<!-- month: january -> december -->
						<select id="end_fmonth" name="end_fmonth">
								<?php
								foreach (range(1, 12) as $i) {
									// values have to have double digits
									$value = ($i <= 9 ? '0'.$i : $i );
									if ($value==$end_month)
										{ echo "<option value=\"".$value."\" selected>".$MonthsLong[$i-1]."</option>"; }
									else
										{ echo "<option value=\"".$value."\">".$MonthsLong[$i-1]."</option>"; }
									}?>
						</select>
						<select  id="end_fyear" name="end_fyear">
								<option value="<?php echo ($end_year-1) ?>"><?php echo ($end_year-1) ?></option>
								<option value="<?php echo $end_year ?>" selected> <?php echo $end_year ?> </option>
								<?php
								for ($i=1; $i<=5; $i++) {
									$value=$end_year+$i;
									echo "<option value=\"$value\">$value</option>";
								} ?>
						</select>
					<a href="javascript:openCalendar('new_calendar_item', 'end_f')">
						<?php echo Display::span(Display::return_icon('calendar_select.gif', get_lang('Select'), array('style' => 'vertical-align: middle;')), array ('id'=>'end_date_calendar_icon')); ?>
					</a>
					&nbsp;<?php echo get_lang('EndTime').": "; ?>&nbsp;

						<select id="end_fhour" name="end_fhour">
							<!-- <option value="--">--</option> -->
							<?php
								foreach (range(0, 23) as $i) {
									// values have to have double digits
									$value = ($i <= 9 ? '0'.$i : $i );
									// the current hour is indicated with [] around the hour
									if ($end_hours==$value)
										{ echo "<option value=\"".$value."\" selected> ".$value." </option>"; }
									else
										{ echo "<option value=\"".$value."\"> ".$value." </option>"; }
								} ?>
						</select>

						<select id="end_fminute" name="end_fminute">
							<!-- <option value="<?php echo $end_minutes; ?>"><?php echo $end_minutes; ?></option> -->
							<!-- <option value="--">--</option> -->
							<?php
								foreach (range(0, 59) as $i) {
									// values have to have double digits
									//$value = ($i <= 9 ? '0'.$i : $i );
									$value = ($i <= 9 ? '0'.$i : $i );
									if ($end_minutes == $value) {
										echo "<option value=\"".$value."\" selected> ".$value." </option>";
									} else {
										echo "<option value=\"$value\">$value</option>";
									}
								} ?>
						</select>
					
	<?php
	echo 	'	</div>
			</div>	</span>';
	
	
	// Repeating the calendar item
	if (empty($id)) {
		//only show repeat fields when adding, not for editing an calendar item
		echo '<div class="row">
					<div class="label">					
					</div>
					<div class="formw">
					<a href="javascript://" onclick="return plus_repeated_event();"><span id="plus2">
	                       <img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('RepeatEvent').'</span>
	                    </a>';
		?>
						<table id="options2" style="display: none;">
						<tr>
							<td><input id="repeat_id" type="checkbox" name="repeat" <?php echo ($repeat?'checked="checked"':'');?>/>
                                <label for="repeat_id"><?php echo get_lang('RepeatEvent');?></label></td>
							<td>                                
                            </td>
				    	</tr>
				    	<tr>
				    		<td><label for="repeat_type"><?php echo get_lang('RepeatType');?></label></td>
							<td>
						        <select name="repeat_type">
						          <option value="daily"><?php echo get_lang('RepeatDaily');?></option>
						          <option value="weekly"><?php echo get_lang('RepeatWeekly');?></option>
						          <option value="monthlyByDate"><?php echo get_lang('RepeatMonthlyByDate');?></option>
						          <!--option value="monthlyByDay"><?php echo get_lang('RepeatMonthlyByDay');?></option>
						          <option value="monthlyByDayR"><?php echo get_lang('RepeatMonthlyByDayR');?></option-->
						          <option value="yearly"><?php echo get_lang('RepeatYearly');?></option>
						        </select>
				      </td>
				    </tr>
				    <tr>
						<td><label for="repeat_end_day"><?php echo get_lang('RepeatEnd');?></label></td>
				        <td>
				            <select name="repeat_end_day">
				            <?php
				                    // small loop for filling all the dates
				                    // 2do: the available dates should be those of the selected month => february is from 1 to 28 (or 29) and not to 31
	
				                    foreach (range(1, 31) as $i) {
				                        // values have to have double digits
				                        $value = ($i <= 9 ? '0'.$i : $i );
				                        // the current day is indicated with [] around the date
				                        if ($value==$end_day)
				                            { echo "<option value=\"".$value."\" selected> ".$i." </option>";}
				                        else
				                            { echo "<option value=\"".$value."\">".$i."</option>"; }
				                        }?>
				                </select>
				                <!-- month: january -> december -->
				                <select name="repeat_end_month">
				                    <?php
				                    foreach (range(1, 12) as $i) {
				                        // values have to have double digits
				                        $value = ($i <= 9 ? '0'.$i : $i );
				                        if ($value==$end_month+1)
				                            { echo '<option value="',$value,'" selected="selected">',$MonthsLong[$i-1],"</option>"; }
				                        else
				                            { echo '<option value="',$value,'">',$MonthsLong[$i-1],"</option>"; }
				                        }?>
				                </select>
				                <select name="repeat_end_year">
				                    <option value="<?php echo ($end_year-1) ?>"><?php echo ($end_year-1) ?></option>
				                    <option value="<?php echo $end_year ?>" selected> <?php echo $end_year ?> </option>
				                    <?php
				                    for ($i=1; $i<=5; $i++) {
				                        $value=$end_year+$i;
				                        echo "<option value=\"$value\">$value</option>";
				                    } ?>
				            </select>
							<a href="javascript:openCalendar('new_calendar_item', 'repeat_end_')">
								<?php Display::display_icon('calendar_select.gif', get_lang('Select'), array ('style' => 'vertical-align: middle;')); ?>
							</a>
							</td>
					    </tr>
				    </table>
	<?php
		echo '		</div>
				</div>';
	    }//only show repeat fields if adding, not if editing
	    
	// the main area of the agenda item: the wysiwyg editor
	echo '	<div class="row">
				<div class="label" >
					<span class="form_required">*</span>'.get_lang('Description').'
				</div>
				<div class="formw">';
			/*require_once api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php";
			$oFCKeditor = new FCKeditor('content') ;
			$oFCKeditor->Width		= '100%';
			$oFCKeditor->Height		= '200';
			if(!api_is_allowed_to_edit(null,true)) {
				$oFCKeditor->ToolbarSet = 'AgendaStudent';
			} else {
				$oFCKeditor->ToolbarSet = 'Agenda';
			}
			$oFCKeditor->Value		= $content;
			$return =	$oFCKeditor->CreateHtml();
			echo $return;*/
            echo '<textarea cols="50" rows="4" name="content">'.$content.'</textarea>';
	echo '</div>
			</div>';

	// the added resources
	/*echo '	<div class="row">
				<div class="label">
					'.get_lang('AddedResources').'
				</div>
				<div class="formw">';
			if ($_SESSION['allow_individual_calendar']=='show')
				show_addresource_button('onclick="selectAll(this.form.elements[6],true)"');
			else
				show_addresource_button();
			$form_elements=$_SESSION['formelements'];
	   echo display_resources(0);
	   $test=$_SESSION['addedresource'];
	echo '		</div>
			</div>';
			*/

	// File attachment
	echo '	<div class="row">
				<div class="label"><label for="file_name">'.get_lang('AddAnAttachment').'&nbsp;</label></div>
				<div class="formw">							      
                    <input type="file" name="user_upload"/>  '.get_lang('Comment').' <input name="file_comment" type="text" size="20" />
                </div>
             </div>';


	


    // the submit button for storing the calendar item
    echo '		<div class="row">
					<div class="label">
			 </div>
					<div class="formw">';
		if(isset($_GET['id']) ) {
		$class='save';
			$text=get_lang('ModifyEvent');
		} else {
		$class='add';
			$text=get_lang('AgendaAdd');
		}
	echo '<button class="'.$class.'" type="button" name="name" onclick="selectAll(document.getElementById(\'selected_form\'),true)">'.$text.'</button>';
	echo '			</div>
				</div>';
		?>
	</form>
<?php
}

function get_agendaitems($month, $year) {
	$items = array ();
	$month = Database::escape_string($month);
	$year = Database::escape_string($year);

	//databases of the courses
	$TABLEAGENDA 		= Database :: get_course_table(TABLE_AGENDA);
	$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);
	$course_info = api_get_course_info();
	$group_memberships = GroupManager :: get_group_ids($course_info['real_id'], api_get_user_id());
	// if the user is administrator of that course we show all the agenda items
	if (api_is_allowed_to_edit(false,true)) {
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
							AND	( item_property.to_user_id='".api_get_user_id()."' OR item_property.to_group_id IN (0, ".implode(", ", $group_memberships).") )
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
							AND ( item_property.to_user_id='".api_get_user_id()."' OR item_property.to_group_id='0')
							AND item_property.visibility='1'
							ORDER BY start_date ";
		}
	}

	$mycourse = api_get_course_info();
    $result = Database::query($sqlquery);
    
	while ($item = Database::fetch_array($result)) {
		$agendaday_string = api_convert_and_format_date($item['start_date'], "%d", date_default_timezone_get());
		$agendaday = intval($agendaday_string);
		$time = api_convert_and_format_date($item['start_date'], TIME_NO_SEC_FORMAT);
		$URL = api_get_path(WEB_CODE_PATH).'calendar/agenda.php?cidReq='.$mycourse['id']."&day=$agendaday&month=$month&year=$year#$agendaday"; // RH  //Patrick Cool: to highlight the relevant agenda item
		$items[$agendaday][$item['start_time']] .= '<i>'.$time.'</i> <a href="'.$URL.'" title="'.$mycourse['name'].'">'.$mycourse['official_code'].'</a> '.$item['title'].'<br />';
	}

	// sorting by hour for every day
	$agendaitems = array ();
	while (list ($agendaday, $tmpitems) = each($items))
	{
		sort($tmpitems);
		while (list ($key, $val) = each($tmpitems))
		{
			$agendaitems[$agendaday] .= $val;
		}
	}
	return $agendaitems;
}

function display_upcoming_events() {
	
	$number_of_items_to_show = (int)api_get_setting('number_of_upcoming_events');

	//databases of the courses
	$TABLEAGENDA 		= Database :: get_course_table(TABLE_AGENDA);
	$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);
    $mycourse   		= api_get_course_info();
    $myuser     		= api_get_user_info();
    $session_id 		= api_get_session_id();
    
    $course_id = $mycourse['real_id'];


	$group_memberships = GroupManager :: get_group_ids($mycourse['real_id'], $myuser['user_id']);
	// if the user is administrator of that course we show all the agenda items
	if (api_is_allowed_to_edit(false,true)) {
		//echo "course admin";
		$sqlquery = "SELECT
						DISTINCT agenda.*, item_property.*
						FROM ".$TABLEAGENDA." agenda,
							 ".$TABLE_ITEMPROPERTY." item_property
						WHERE 
						agenda.c_id = $course_id AND 
                        item_property.c_id = $course_id AND  
                        agenda.id = item_property.ref
						AND item_property.tool='".TOOL_CALENDAR_EVENT."'
						AND item_property.visibility='1'
						AND agenda.start_date > NOW()
						AND session_id = '".$session_id."'
						GROUP BY agenda.id
						ORDER BY start_date ";
	}
	// if the user is not an administrator of that course
	else  {
		//echo "GEEN course admin";
		if (is_array($group_memberships) and count($group_memberships)>0) {
			$sqlquery = "SELECT
							agenda.*, item_property.*
							FROM ".$TABLEAGENDA." agenda,
								".$TABLE_ITEMPROPERTY." item_property
							WHERE
							agenda.c_id = $course_id AND 
                            item_property.c_id = $course_id AND   
							agenda.id = item_property.ref
							AND item_property.tool='".TOOL_CALENDAR_EVENT."'
							AND	( item_property.to_user_id='".$myuser['user_id']."' OR item_property.to_group_id IN (0, ".implode(", ", $group_memberships).") )
							AND item_property.visibility='1'
							AND agenda.start_date > NOW()
							AND session_id = '".$session_id."'
							ORDER BY start_date ";
		} else {
			$sqlquery = "SELECT
							agenda.*, item_property.*
							FROM ".$TABLEAGENDA." agenda,
							".$TABLE_ITEMPROPERTY." item_property
							WHERE
							agenda.c_id = $course_id AND 
							item_property.c_id = $course_id AND   
							agenda.id = item_property.ref
							AND item_property.tool='".TOOL_CALENDAR_EVENT."'
							AND ( item_property.to_user_id='".$myuser['user_id']."' OR item_property.to_group_id='0')
							AND item_property.visibility='1'
							AND agenda.start_date > NOW()
							AND session_id = '".$session_id."'
							ORDER BY start_date ";
		}
	}
	$result = Database::query($sqlquery);
	$counter = 0;
	
	if (Database::num_rows($result) > 0 ) {
    	echo '<h4>'.get_lang('UpcomingEvent').'</h4><br />';    	
    	while ($item = Database::fetch_array($result,'ASSOC')) {
    		if ($counter < $number_of_items_to_show) {
    			echo api_get_local_time($item['start_date']),' - ',$item['title'],'<br />';
    			$counter++;
    		}
    	}
	}
}

/**
 * This function calculates the startdate of the week (monday)
 * and the enddate of the week (sunday)
 * and returns it as an array
 * @todo check if this function is correct
 */
function calculate_start_end_of_week($week_number, $year) {
	// determine the start and end date
	// step 1: we calculate a timestamp for a day in this week
	//@todo Why ($week_number - 1) ?
	//$random_day_in_week = mktime(0, 0, 0, 1, 1, $year) + ($week_number-1) * (7 * 24 * 60 * 60); // we calculate a random day in this week
	$random_day_in_week = mktime(0, 0, 0, 1, 1, $year) + ($week_number) * (7 * 24 * 60 * 60); // we calculate a random day in this week
	// step 2: we which day this is (0=sunday, 1=monday, ...)
	$number_day_in_week = date('w', $random_day_in_week);
	// step 3: we calculate the timestamp of the monday of the week we are in
	$start_timestamp = $random_day_in_week - (($number_day_in_week -1) * 24 * 60 * 60);
	// step 4: we calculate the timestamp of the sunday of the week we are in
	$end_timestamp = $random_day_in_week + ((7 - $number_day_in_week +1) * 24 * 60 * 60) - 3600;
	// step 5: calculating the start_day, end_day, start_month, end_month, start_year, end_year
	$start_day = date('j', $start_timestamp);
	$start_month = date('n', $start_timestamp);
	$start_year = date('Y', $start_timestamp);
	$end_day = date('j', $end_timestamp);
	$end_month = date('n', $end_timestamp);
	$end_year = date('Y', $end_timestamp);
	$start_end_array['start']['day'] = $start_day;
	$start_end_array['start']['month'] = $start_month;
	$start_end_array['start']['year'] = $start_year;
	$start_end_array['end']['day'] = $end_day;
	$start_end_array['end']['month'] = $end_month;
	$start_end_array['end']['year'] = $end_year;
	return $start_end_array;
}
/**
 * Show the mini calendar of the given month
 */
function display_daycalendar($agendaitems, $day, $month, $year, $weekdaynames, $monthName) {
	global $DaysShort, $DaysLong, $course_path;
	global $MonthsLong;
	global $query;

	// timestamp of today
	$today = mktime();
	$nextday = $today + (24 * 60 * 60);
	$previousday = $today - (24 * 60 * 60);
	// the week number of the year
	$week_number = date("W", $today);
	// if we moved to the next / previous day we have to recalculate the $today variable
	if (isset($_GET['day'])) {
		$today = mktime(0, 0, 0, $month, $day, $year);
		$nextday = $today + (24 * 60 * 60);
		$previousday = $today - (24 * 60 * 60);
		$week_number = date("W", $today);
	}

	// calculating the start date of the week
	// the date of the monday of this week is the timestamp of today minus
	// number of days that have already passed this week * 24 hours * 60 minutes * 60 seconds
	$current_day = date("j", $today); // Day of the month without leading zeros (1 to 31) of today
	$day_of_the_week = date("w", $today); // Numeric representation of the day of the week	0 (for Sunday) through 6 (for Saturday) of today

	// we are loading all the calendar items of all the courses for today
	echo "<table class=\"data_table\">";
	// the forward and backwards url
	$course_code = isset($_GET['courseCode']) ? Security::remove_XSS($_GET['courseCode']) : null;
	$backwardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&courseCode=".$course_code."&action=view&view=day&day=".date("j", $previousday)."&month=".date("n", $previousday)."&year=".date("Y", $previousday);
	$forewardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&courseCode=".$course_code."&action=view&view=day&day=".date("j", $nextday)."&month=".date("n", $nextday)."&year=".date("Y", $nextday);
	// The title row containing the day
	echo "<tr>", "<th width=\"10%\"><a href=\"", $backwardsURL, "\">".Display::return_icon('action_prev.png',get_lang('Previous'))."</a></th>", "<th>";
	echo $DaysLong[$day_of_the_week]." ".date("j", $today)." ".$MonthsLong[date("n", $today) - 1]." ".date("Y", $today);
	echo "</th>";
	echo "<th width=\"10%\"><a href=\"", $forewardsURL, "\">".Display::return_icon('action_next.png',get_lang('Next'))."</a></th>";
	echo "</tr>";
	
	// From  0 to 5h
	$class = "class=\"row_even\"";
	echo "<tr $class>";
	echo ("<td valign=\"top\" width=\"75\">0:00 ".get_lang("HourShort")." - 4:30 ".get_lang("HourShort")."</td>");
	echo "<td $class valign=\"top\" colspan=\"2\">";
	for ($i = 0; $i < 10; $i ++) {
		if (isset($agendaitems[$i])) {
			if (is_array($agendaitems[$i])) {
				foreach ($agendaitems[$i] as $key => $value) {
					echo $value;
				}
			} else {
				echo $agendaitems[$i];
			}
		}
	}
	echo "</td>";
	echo "</tr>";

	// the rows for each half an hour
	for ($i = 10; $i < 48; $i ++) {
		if ($i % 2 == 0) {
			$class = "class=\"row_even\"";
		} else {
			$class = "class=\"row_odd\"";
		}
		echo "<tr $class>";
		echo "";
		if ($i % 2 == 0) {
			echo ("<td valign=\"top\" width=\"75\">". (($i) / 2)." ".get_lang("HourShort")." 00</td>");
		} else {
			echo ("<td valign=\"top\" width=\"75\">". ((($i) / 2) - (1 / 2))." ".get_lang("HourShort")." 30</td>");
		}
		echo "<td $class valign=\"top\" colspan=\"2\">";
		if (isset($agendaitems[$i])) {
			if (is_array($agendaitems[$i])) {
				foreach ($agendaitems[$i] as $key => $value) {
					echo $value;
				}
			} else {
				echo $agendaitems[$i];
			}
		}
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
}
/**
 *	Display the weekly view of the calendar
 */
function display_weekcalendar($agendaitems, $month, $year, $weekdaynames, $monthName) {

	global $DaysShort,$course_path;
	global $MonthsLong;

	// timestamp of today
	$today = time();
	$day_of_the_week = date("w", $today);
	$thisday_of_the_week = date("w", $today);
	// the week number of the year
	$week_number = date("W", $today);
	$thisweek_number = $week_number;
	// if we moved to the next / previous week we have to recalculate the $today variable

	if (!isset($_GET['week'])) {
		$week_number = date("W", $today);
	} else {
		$week_number = intval($_GET['week']);
	}
	
	// calculating the start date of the week
	// the date of the monday of this week is the timestamp of today minus
	// number of days that have already passed this week * 24 hours * 60 minutes * 60 seconds
	$current_day = date("j", $today); // Day of the month without leading zeros (1 to 31) of today
	$day_of_the_week = date("w", $today); // Numeric representation of the day of the week	0 (for Sunday) through 6 (for Saturday) of today


	//Using the same script to calculate the start/end of a week
	$start_end = calculate_start_end_of_week($week_number, $year);

	$timestamp_first_date_of_week = mktime(0, 0, 0, $start_end['start']['month'], $start_end['start']['day'], $start_end['start']['year']);
	$timestamp_last_date_of_week  = mktime(0, 0, 0, $start_end['end']['month'], $start_end['end']['day'], $start_end['end']['year']);

	$backwardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&courseCode=".api_get_course_id()."&action=view&view=week&week=". ($week_number -1);
	$forewardsURL = api_get_self()."?coursePath=".urlencode($course_path)."&courseCode=".api_get_course_id()."&action=view&view=week&week=". ($week_number +1);

	echo '<table class="data_table">';
	// The title row containing the the week information (week of the year (startdate of week - enddate of week)
	echo '<tr>';
	echo "<th width=\"10%\"><a href=\"", $backwardsURL, "\">".Display::return_icon('action_prev.png',get_lang('Previous'))."</a></th>";
	echo "<th colspan=\"5\">".get_lang("Week")." ".$week_number;
	echo " (".$DaysShort['1']." ".date("j", $timestamp_first_date_of_week)." ".$MonthsLong[date("n", $timestamp_first_date_of_week) - 1]." ".date("Y", $timestamp_first_date_of_week)." - ".$DaysShort['0']." ".date("j", $timestamp_last_date_of_week)." ".$MonthsLong[date("n", $timestamp_last_date_of_week) - 1]." ".date("Y", $timestamp_last_date_of_week).')';
	echo "</th>";
	echo "<th width=\"10%\"><a href=\"", $forewardsURL, "\">".Display::return_icon('action_next.png',get_lang('Next'))."</a></th>", "</tr>";
	// The second row containing the short names of the days of the week
	echo "<tr>";

	//Printing the week days

	// this is the Day of the month without leading zeros (1 to 31) of the monday of this week
	$tmp_timestamp = $timestamp_first_date_of_week;
	for ($ii = 1; $ii < 8; $ii ++) {
		$is_today = ($ii == $thisday_of_the_week AND (!isset($_GET['week']) OR $_GET['week']==$thisweek_number));
		echo "<td class=\"weekdays\">";
		if ($is_today) {
			echo "<font color=#CC3300>";
		}
		echo $DaysShort[$ii % 7]." ".date("j", $tmp_timestamp)." ".$MonthsLong[date("n", $tmp_timestamp) - 1];
		if ($is_today) {
			echo "</font>";
		}
		echo "</td>";
		// we 24 hours * 60 minutes * 60 seconds to the $tmp_timestamp
		$array_tmp_timestamp[] = $tmp_timestamp;
		$tmp_timestamp = $tmp_timestamp + (24 * 60 * 60);
	}
	echo "</tr>";

	// The table cells containing all the entries for that day
	echo "<tr>";
	$counter = 0;

	foreach ($array_tmp_timestamp as $key => $value) {
		if ($counter < 5) {
			$class = "class=\"days_week\"";
		} else {
			$class = "class=\"days_weekend\"";
		}
		if ($counter == $thisday_of_the_week -1 AND (!isset($_GET['week']) OR $_GET['week']==$thisweek_number)) {
			$class = "class=\"days_today\"";
		}
		echo "<td ".$class.">";
		$data = isset($agendaitems[date('j', $value)]) ? $agendaitems[date('j', $value)] : null;
		echo "<span class=\"agendaitem\">".$data."&nbsp;</span> ";
		echo "</td>";
		$counter ++;
	}
	echo "</tr>";
	echo "</table>";
}
/**
 * Show the monthcalender of the given month
 */
function get_day_agendaitems($courses_dbs, $month, $year, $day) {
	global $setting_agenda_link;

	$items = array ();

	// get agenda-items for every course
	//$query=Database::query($sql_select_courses);
	foreach ($courses_dbs as $key => $array_course_info) {
		//echo $array_course_info['db'];
		//databases of the courses
		$TABLEAGENDA 		= Database :: get_course_table(TABLE_AGENDA);
		$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);

		// getting all the groups of the user for the current course
		$group_memberships = GroupManager :: get_group_ids($array_course_info['real_id'], api_get_user_id());
		$course_user_status = CourseManager::get_user_in_course_status(api_get_user_id(), $array_course_info['code']);
		
		
		$start_filter  = $year."-".$month."-".$day." 00:00:00";
		$start_filter  = api_get_utc_datetime($start_filter);
		$end_filter    = $year."-".$month."-".$day." 23:59:59";
		$end_filter    = api_get_utc_datetime($end_filter);
		
		
		// if the user is administrator of that course we show all the agenda items
		if ($course_user_status == '1') {
			//echo "course admin";
			$sqlquery = "SELECT
							DISTINCT agenda.*, item_property.*
							FROM ".$TABLEAGENDA." agenda,
								".$TABLE_ITEMPROPERTY." item_property
							WHERE agenda.id = item_property.ref
							AND start_date>='".$start_filter."' AND start_date<='".$end_filter."'							
							AND item_property.tool='".TOOL_CALENDAR_EVENT."'
							AND item_property.visibility='1'
							GROUP BY agenda.id
							ORDER BY start_date ";
		}
		// if the user is not an administrator of that course
		else {
			//echo "course admin";
			if (is_array($group_memberships) && count($group_memberships)>0) {
				$sqlquery = "SELECT
								agenda.*, item_property.*
								FROM ".$TABLEAGENDA." agenda,
									".$TABLE_ITEMPROPERTY." item_property
								WHERE agenda.id = item_property.ref
								AND start_date>='".$start_filter."' AND start_date<='".$end_filter."'
								AND item_property.tool='".TOOL_CALENDAR_EVENT."'
								AND	( item_property.to_user_id='".api_get_user_id()."' OR item_property.to_group_id IN (0, ".implode(", ", $group_memberships).") )
								AND item_property.visibility='1'
								ORDER BY start_date ";
			} else {
				$sqlquery = "SELECT
								agenda.*, item_property.*
								FROM ".$TABLEAGENDA." agenda,
									".$TABLE_ITEMPROPERTY." item_property
								WHERE agenda.id = item_property.ref
								AND start_date>='".$start_filter."' AND start_date<='".$end_filter."'
								AND item_property.tool='".TOOL_CALENDAR_EVENT."'
								AND ( item_property.to_user_id='".api_get_user_id()."' OR item_property.to_group_id='0')
								AND item_property.visibility='1'
								ORDER BY start_date ";
			}
		}

		$result = Database::query($sqlquery);
		while ($item = Database::fetch_array($result)) {
			// in the display_daycalendar function we use $i (ranging from 0 to 47) for each halfhour
			// we want to know for each agenda item for this day to wich halfhour it must be assigned
			$item['start_date'] = api_get_local_time($item['start_date']);
			$time_minute 		= api_format_date($item['start_date'], TIME_NO_SEC_FORMAT);			

			list ($datepart, $timepart) = explode(" ", $item['start_date']);
			list ($year, $month, $day) = explode("-", $datepart);
			list ($hours, $minutes, $seconds) = explode(":", $timepart);

			$halfhour =2* $hours;
			if ($minutes >= '30') {
				$halfhour = $halfhour +1;
			}

			if ($setting_agenda_link == 'coursecode') {
				$title=$array_course_info['title'];
				$agenda_link = cut($title,14,true);
			} else {
				$agenda_link = Display::return_icon('course_home.png','&nbsp;','',ICON_SIZE_SMALL);
			}
			$URL = api_get_path(WEB_CODE_PATH).'calendar/agenda.php?cidReq='.urlencode($array_course_info["code"])."&day=$day&month=$month&year=$year#$day"; // RH  //Patrick Cool: to highlight the relevant agenda item

			$items[$halfhour][] .= "<i>$time_minute</i> <a href=\"$URL\" title=\"".$array_course_info['title']."\">".$agenda_link."</a>  ".$item['title']."<br />";
		}
	}

	// sorting by hour for every day
	$agendaitems = array();
	while (list($agendaday, $tmpitems) = each($items)) {
		sort($tmpitems);
		while (list($key,$val) = each($tmpitems))
		{
			$agendaitems[$agendaday].=$val;
		}
	}
	return $agendaitems;
}
/**
 * Return agenda items of the week
 */
function get_week_agendaitems($courses_dbs, $month, $year, $week = '') {
	global $setting_agenda_link;

	$TABLEAGENDA 		= Database :: get_course_table(TABLE_AGENDA);
	$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);

	$items = array ();
	// The default value of the week
	if ($week == '') {
		$week_number = date("W", time());
	} else {
		$week_number = $week;
	}

	$start_end = calculate_start_end_of_week($week_number, $year);

	$start_filter 	= $start_end['start']['year']."-".$start_end['start']['month']."-".$start_end['start']['day']." 00:00:00";
	$start_filter  = api_get_utc_datetime($start_filter);
	$end_filter 	= $start_end['end']['year']."-".$start_end['end']['month']."-".$start_end['end']['day']." 23:59:59";
	$end_filter  = api_get_utc_datetime($end_filter);

	// get agenda-items for every course
	foreach ($courses_dbs as $key => $array_course_info) {
		//databases of the courses
		$TABLEAGENDA = Database :: get_course_table(TABLE_AGENDA);
		$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);

		// getting all the groups of the user for the current course
		$group_memberships = GroupManager :: get_group_ids($array_course_info["real_id"], api_get_user_id());
		
		$user_course_status = CourseManager::get_user_in_course_status(api_get_user_id(),$array_course_info["code"]);

		// if the user is administrator of that course we show all the agenda items
		if ($user_course_status == '1') {
			//echo "course admin";
			$sqlquery = "SELECT
							DISTINCT a.*, i.*
							FROM ".$TABLEAGENDA." a,
								".$TABLE_ITEMPROPERTY." i
							WHERE a.id = i.ref
							AND a.start_date>='".$start_filter."' AND a.start_date<='".$end_filter."'
							AND i.tool='".TOOL_CALENDAR_EVENT."'
							AND i.visibility='1'
							GROUP BY a.id
							ORDER BY a.start_date";
		}
		// if the user is not an administrator of that course
		else {
			//echo "GEEN course admin";
			if (is_array($group_memberships) && count($group_memberships)>0) {
				$sqlquery = "SELECT
									a.*, i.*
									FROM ".$TABLEAGENDA." a,
										 ".$TABLE_ITEMPROPERTY." i
									WHERE a.id = i.ref
									AND a.start_date>='".$start_filter."' AND a.start_date<='".$end_filter."'
									AND i.tool='".TOOL_CALENDAR_EVENT."'
									AND	( i.to_user_id='".api_get_user_id()."' OR i.to_group_id IN (0, ".implode(", ", $group_memberships).") )
									AND i.visibility='1'
									ORDER BY a.start_date";
			} else {
				$sqlquery = "SELECT
									a.*, i.*
									FROM ".$TABLEAGENDA." a,
										 ".$TABLE_ITEMPROPERTY." i
									WHERE a.id = i.ref
									AND a.start_date>='".$start_filter."' AND a.start_date<='".$end_filter."'
									AND i.tool='".TOOL_CALENDAR_EVENT."'
									AND ( i.to_user_id='".api_get_user_id()."' OR i.to_group_id='0')
									AND i.visibility='1'
									ORDER BY a.start_date";
			}
		}
		//echo "<pre>".$sqlquery."</pre>";
		// $sqlquery = "SELECT * FROM $agendadb WHERE (DAYOFMONTH(day)>='$start_day' AND DAYOFMONTH(day)<='$end_day')
		//				AND (MONTH(day)>='$start_month' AND MONTH(day)<='$end_month')
		//				AND (YEAR(day)>='$start_year' AND YEAR(day)<='$end_year')";
		//var_dump($sqlquery);
		$result = Database::query($sqlquery);
		while ($item = Database::fetch_array($result)) {
			$agendaday_string = api_convert_and_format_date($item['start_date'], "%d", date_default_timezone_get());
			$agendaday = intval($agendaday_string);
			$start_time = api_convert_and_format_date($item['start_date'], TIME_NO_SEC_FORMAT);
			$end_time    = api_convert_and_format_date($item['end_date'], DATE_TIME_FORMAT_LONG);

			if ($setting_agenda_link == 'coursecode') {
				$title=$array_course_info['title'];
				$agenda_link = cut($title, 14, true);
			} else {
				$agenda_link = Display::return_icon('course_home.png','&nbsp;','',ICON_SIZE_SMALL);
			}

			$URL = api_get_path(WEB_CODE_PATH)."calendar/agenda.php?cidReq=".urlencode($array_course_info["code"])."&day=$agendaday&month=$month&year=$year#$agendaday"; // RH  //Patrick Cool: to highlight the relevant agenda item
			//Display the events in agenda
			$content  = "<i>$start_time - $end_time</i> <a href=\"$URL\" title=\"".$array_course_info["title"]."\"> <br />".$agenda_link."</a>";
			$content .= "<div>".$item['title']."</div><br>";
			$items[$agendaday][$item['start_date']] .= $content;
		}
	}

	$agendaitems = array ();
	// sorting by hour for every day
	while (list ($agendaday, $tmpitems) = each($items)) {
		sort($tmpitems);
		while (list ($key, $val) = each($tmpitems)) {
			$agendaitems[$agendaday] .= $val;
		}
	}

	return $agendaitems;
}
/**
 * Get repeated events of a course between two dates (timespan of a day).
 * Returns an array containing the events
 * @param   string  Course info array (as returned by api_get_course_info())
 * @param	int		UNIX timestamp of span start. Defaults 0, later transformed into today's start
 * @param	int		UNIX timestamp. Defaults to 0, later transformed into today's end
 * @param   array   A set of parameters to alter the SQL query
 * @return	array	[int] => [course_id,parent_event_id,start_date,end_date,title,description]
 */
function get_repeated_events_day_view($course_info,$start=0,$end=0,$params)
{
	$events = array();
	//initialise all values
	$y=0;
	$m=0;
	$d=0;
    //block $end if higher than 2038 -- PHP doesn't go past that
    if($end>2145934800){$end = 2145934800;}
	if($start == 0 or $end == 0)
	{
		$y=date('Y');
		$m=date('m');
		$d=date('j');
	}
	if($start==0)
	{
		$start = mktime(0,0,0,$m,$d,$y);
	}
	$db_start = date('Y-m-d H:i:s',$start);
	if($end==0)
	{
		$end = mktime(23,59,59,$m,$d,$y);
	}
	//$db_end = date('Y-m-d H:i:s',$end);

	$t_cal = Database::get_course_table(TABLE_AGENDA,$course_info['dbName']);
	$t_cal_repeat = Database::get_course_table(TABLE_AGENDA_REPEAT,$course_info['dbName']);
    $t_ip = Database::get_course_table(TABLE_ITEM_PROPERTY,$course_info['dbName']);
	$sql = "SELECT c.id, c.title, c.content, " .
			" UNIX_TIMESTAMP(c.start_date) as orig_start, UNIX_TIMESTAMP(c.end_date) as orig_end, " .
			" cr.cal_type, cr.cal_end " .
			" FROM $t_cal c, $t_cal_repeat cr, $t_ip as item_property " .
			" WHERE cr.cal_end >= $start " .
			" AND cr.cal_id = c.id " .
            " AND item_property.ref = c.id ".
            " AND item_property.tool = '".TOOL_CALENDAR_EVENT."' ".
			" AND c.start_date <= '$db_start' "
            .(!empty($params['conditions'])?$params['conditions']:'')
            .(!empty($params['groupby'])?' GROUP BY '.$params['groupby']:'')
            .(!empty($params['orderby'])?' ORDER BY '.$params['orderby']:'');
	$res = Database::query($sql);
	if(Database::num_rows($res)>0)
	{
		while($row = Database::fetch_array($res))
		{
			$orig_start = $row['orig_start'];
			$orig_end = $row['orig_end'];
			$repeat_type = $row['cal_type'];
			switch($repeat_type)
			{
				case 'daily':
					//we are in the daily view, so if this element is repeated daily and
					//the repetition is still active today (which is a condition of the SQL query)
					//then the event happens today. Just build today's timestamp for start and end
					$time_orig_h = date('H',$orig_start);
					$time_orig_m = date('i',$orig_start);
					$time_orig_s = date('s',$orig_start);
					$int_time = (($time_orig_h*60)+$time_orig_m)*60+$time_orig_s; //time in seconds since 00:00:00
					$span = $orig_end - $orig_start; //total seconds between start and stop of original event
					$current_start =$start + $int_time; //unixtimestamp start of today's event
					$current_stop = $start+$int_time+$span; //unixtimestamp stop of today's event
					$events[] = array($course_info['id'],$row['id'],$current_start,$current_stop,$row['title'],$row['content']);
					break;
				case 'weekly':
					$time_orig = date('Y/n/W/j/N/G/i/s',$orig_start);
					list($y_orig,$m_orig,$w_orig,$d_orig,$dw_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
					$time_now = date('Y/n/W/j/N/G/i/s',$end);
					list($y_now,$m_now,$w_now,$d_now,$dw_now,$h_now,$n_now,$s_now) = split('/',$time_now);
					if((($y_now>$y_orig) OR (($y_now == $y_orig) && ($w_now>$w_orig))) && ($dw_orig == $dw_now))
					{ //if the event is after the original (at least one week) and the day of the week is the same
					  $time_orig_end = date('Y/n/W/j/N/G/i/s',$orig_end);
					  list($y_orig_e,$m_orig_e,$w_orig_e,$d_orig_e,$dw_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);
					  $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
					}
					break;
				case 'monthlyByDate':
					$time_orig = date('Y/n/j/G/i/s',$orig_start);
					list($y_orig,$m_orig,$d_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
					$time_now = date('Y/n/j/G/i/s',$end);
					list($y_now,$m_now,$d_now,$h_now,$n_now,$s_now) = split('/',$time_now);
					if((($y_now>$y_orig) OR (($y_now == $y_orig) && ($m_now>$m_orig))) && ($d_orig == $d_now))
					{
					  $time_orig_end = date('Y/n/j/G/i/s',$orig_end);
					  list($y_orig_e,$m_orig_e,$d_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);
					  $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
					}
					break;
				case 'monthlyByDayR':
					//not implemented yet
					break;
				case 'monthlyByDay':
					//not implemented yet
					break;
				case 'yearly':
					$time_orig = date('Y/n/j/z/G/i/s',$orig_start);
					list($y_orig,$m_orig,$d_orig,$dy_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
					$time_now = date('Y/n/j/z/G/i/s',$end);
					list($y_now,$m_now,$d_now,$dy_now,$h_now,$n_now,$s_now) = split('/',$time_now);
					if(($y_now>$y_orig) && ($dy_orig == $dy_now))
					{
					  $time_orig_end = date('Y/n/j/G/i/s',$orig_end);
					  list($y_orig_e,$m_orig_e,$d_orig_e,$dy_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);
					  $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
					}
					break;
				default:
					break;
			}
		}
	}
	return $events;
}
/**
 * (This function is not been use in the code)
 * Get repeated events of a course between two dates (timespan of a week).
 * Returns an array containing the events
 * @param	string	Course info array (as returned by api_get_course_info())
 * @param	int		UNIX timestamp of span start. Defaults 0, later transformed into today's start
 * @param	int		UNIX timestamp. Defaults to 0, later transformed into today's end
 * @param   array   A set of parameters to alter the SQL query
 * @return	array	[int] => [course_id,parent_event_id,start_date,end_date,title,description]
 */
function get_repeated_events_week_view($course_info,$start=0,$end=0,$params)
{
	$events = array();
    //block $end if higher than 2038 -- PHP doesn't go past that
    if($end>2145934800){$end = 2145934800;}
	//initialise all values
	$y=0;
	$m=0;
	$d=0;
	if($start == 0 or $end == 0)
	{
		$time = time();
		$dw = date('w',$time);
		$week_start = $time - (($dw-1)*86400);
		$y = date('Y',$week_start);
		$m = date('m',$week_start);
		$d = date('j',$week_start);
		$w = date('W',$week_start);
	}
	if($start==0)
	{
		$start = mktime(0,0,0,$m,$d,$y);
	}
	$db_start = date('Y-m-d H:i:s',$start);
	if($end==0)
	{
		$end = $start+(86400*7)-1; //start of week, more 7 days, minus 1 second to get back to the previoyus day
	}
	//$db_end = date('Y-m-d H:i:s',$end);

	$t_cal = Database::get_course_table(TABLE_AGENDA,$course_info['dbName']);
	$t_cal_repeat = Database::get_course_table(TABLE_AGENDA_REPEAT,$course_info['dbName']);
    $t_ip = Database::get_course_table(TABLE_ITEM_PROPERTY,$course_info['dbName']);
    $sql = "SELECT c.id, c.title, c.content, " .
            " UNIX_TIMESTAMP(c.start_date) as orig_start, UNIX_TIMESTAMP(c.end_date) as orig_end, " .
            " cr.cal_type, cr.cal_end " .
            " FROM $t_cal c, $t_cal_repeat cr, $t_ip as item_property " .
            " WHERE cr.cal_end >= $start " .
            " AND cr.cal_id = c.id " .
            " AND item_property.ref = c.id ".
            " AND item_property.tool = '".TOOL_CALENDAR_EVENT."' ".
            " AND c.start_date <= '$db_start' "
            .(!empty($params['conditions'])?$params['conditions']:'')
            .(!empty($params['groupby'])?' GROUP BY '.$params['groupby']:'')
            .(!empty($params['orderby'])?' ORDER BY '.$params['orderby']:'');
	$res = Database::query($sql);
	if(Database::num_rows($res)>0)
	{
		while($row = Database::fetch_array($res))
		{
			$orig_start = $row['orig_start'];
			$orig_end = $row['orig_end'];
			$repeat_type = $row['cal_type'];
			switch($repeat_type)
			{
				case 'daily':
					$time_orig_h = date('H',$orig_start);
					$time_orig_m = date('i',$orig_start);
					$time_orig_s = date('s',$orig_start);
					$int_time = (($time_orig_h*60)+$time_orig_m)*60+$time_orig_s; //time in seconds since 00:00:00
					$span = $orig_end - $orig_start; //total seconds between start and stop of original event
					for($i=0;$i<7;$i++)
					{
						$current_start = $start + ($i*86400) + $int_time; //unixtimestamp start of today's event
						$current_stop = $start + ($i*86400) + $int_time + $span; //unixtimestamp stop of today's event
						$events[] = array($course_info['id'],$row['id'],$current_start,$current_stop,$row['title'],$row['content']);
					}
					break;
				case 'weekly':
					$time_orig = date('Y/n/W/j/N/G/i/s',$orig_start);
					list($y_orig,$m_orig,$w_orig,$d_orig,$dw_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
					$time_now = date('Y/n/W/j/N/G/i/s',$end);
					list($y_now,$m_now,$w_now,$d_now,$dw_now,$h_now,$n_now,$s_now) = split('/',$time_now);
					if((($y_now>$y_orig) OR (($y_now == $y_orig) && ($w_now>$w_orig))))
					{ //if the event is after the original (at least one week) and the day of the week is the same
					  $time_orig_end = date('Y/n/W/j/N/G/i/s',$orig_end);
					  list($y_orig_e,$m_orig_e,$w_orig_e,$d_orig_e,$dw_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);
					  $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
					}
					break;
				case 'monthlyByDate':
					$time_orig = date('Y/n/W/j/G/i/s',$orig_start);
					list($y_orig,$m_orig,$w_orig,$d_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
					$time_now = date('Y/n/W/j/G/i/s',$end);
					list($y_now,$m_now,$w_now,$d_now,$h_now,$n_now,$s_now) = split('/',$time_now);
					$event_repetition_time = mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now);
					if((($y_now>$y_orig) OR (($y_now == $y_orig) && ($m_now>$m_orig))) && ($start<$event_repetition_time && $event_repetition_time<$end))
					{ //if the event is after the original (at least one month) and the original event's day is between the first day of the week and the last day of the week
					  $time_orig_end = date('Y/n/j/G/i/s',$orig_end);
					  list($y_orig_e,$m_orig_e,$d_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);
					  $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
					}
					break;
				case 'monthlyByDayR':
					//not implemented yet
					break;
				case 'monthlyByDay':
					//not implemented yet
					break;
				case 'yearly':
					$time_orig = date('Y/n/j/z/G/i/s',$orig_start);
					list($y_orig,$m_orig,$d_orig,$dy_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
					$time_now = date('Y/n/j/z/G/i/s',$end);
					list($y_now,$m_now,$d_now,$dy_now,$h_now,$n_now,$s_now) = split('/',$time_now);
					$event_repetition_time = mktime($h_orig,$n_orig,$s_orig,$m_orig,$d_orig,$y_now);
					if((($y_now>$y_orig) && ($start<$event_repetition_time && $event_repetition_time<$end)))
					{
					  $time_orig_end = date('Y/n/j/G/i/s',$orig_end);
					  list($y_orig_e,$m_orig_e,$d_orig_e,$dy_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);
					  $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
					}
					break;
				default:
					break;
			}
		}
	}
	return $events;
}
/**
 * Get repeated events of a course between two dates (timespan of a month).
 * Returns an array containing the events
 * @param   string  Course info array (as returned by api_get_course_info())
 * @param	int		UNIX timestamp of span start. Defaults 0, later transformed into today's start
 * @param	int		UNIX timestamp. Defaults to 0, later transformed into today's end
 * @param   array   A set of parameters to alter the SQL query
 * @return	array	[int] => [course_id,parent_event_id,start_date,end_date,title,description]
 */
function get_repeated_events_month_view($course_info,$start=0,$end=0,$params)
{
	$events = array();
    //block $end if higher than 2038 -- PHP doesn't go past that
    if($end>2145934800){$end = 2145934800;}
	//initialise all values
	$y=0;
	$m=0;
	$d=0;
	if($start == 0 or $end == 0)
	{
		$time = time();
		$y = date('Y');
		$m = date('m');
	}
	if($start==0)
	{
		$start = mktime(0,0,0,$m,1,$y);
	}
	$db_start = date('Y-m-d H:i:s',$start);
	if($end==0)
	{
		if($m==12)
		{
			$end = mktime(0,0,0,1,1,$y+1)-1; //start of next month, minus 1 second to get back to the previoyus day
		}
		else
		{
			$end = mktime(0,0,0,$m+1,1,$y)-1;
		}
	}
	//$db_end = date('Y-m-d H:i:s',$end);

	$t_cal = Database::get_course_table(TABLE_AGENDA,$course_info['dbName']);
	$t_cal_repeat = Database::get_course_table(TABLE_AGENDA_REPEAT,$course_info['dbName']);
    $t_ip = Database::get_course_table(TABLE_ITEM_PROPERTY,$course_info['dbName']);
    $sql = "SELECT c.id, c.title, c.content, " .
            " UNIX_TIMESTAMP(c.start_date) as orig_start, UNIX_TIMESTAMP(c.end_date) as orig_end, " .
            " cr.cal_type, cr.cal_end " .
            " FROM $t_cal c, $t_cal_repeat cr, $t_ip as item_property " .
            " WHERE cr.cal_end >= $start " .
            " AND cr.cal_id = c.id " .
            " AND item_property.ref = c.id ".
            " AND item_property.tool = '".TOOL_CALENDAR_EVENT."' ".
            " AND c.start_date <= '$db_start' "
            .(!empty($params['conditions'])?$params['conditions']:'')
            .(!empty($params['groupby'])?' GROUP BY '.$params['groupby']:'')
            .(!empty($params['orderby'])?' ORDER BY '.$params['orderby']:'');
	$res = Database::query($sql);
	if(Database::num_rows($res)>0)
	{
		while($row = Database::fetch_array($res))
		{
			$orig_start = $row['orig_start'];
			$orig_end = $row['orig_end'];
			$repeat_type = $row['cal_type'];
			switch($repeat_type)
			{
				case 'daily':
					$time_orig_h = date('H',$orig_start);
					$time_orig_m = date('i',$orig_start);
					$time_orig_s = date('s',$orig_start);
					$month_last_day = date('d',$end);
					$int_time = (($time_orig_h*60)+$time_orig_m)*60+$time_orig_s; //time in seconds since 00:00:00
					$span = $orig_end - $orig_start; //total seconds between start and stop of original event
					for($i=0;$i<$month_last_day;$i++)
					{
						$current_start = $start + ($i*86400) + $int_time; //unixtimestamp start of today's event
						$current_stop = $start + ($i*86400) + $int_time + $span; //unixtimestamp stop of today's event
						$events[] = array($course_info['id'],$row['id'],$current_start,$current_stop,$row['title'],$row['content']);
					}
					break;
				case 'weekly':
					//A weekly repeated event is very difficult to catch in a month view,
					//because weeks start before or at the same time as the first day of the month
					//The same can be said for the end of the month.
					// The idea is thus to get all possible events by enlarging the scope of
					// the month to get complete weeks covering the complete month, and then take out
					// the events that start before the 1st ($start) or after the last day of the month ($end)
					$time_orig = date('Y/n/W/j/N/G/i/s',$orig_start);
					list($y_orig,$m_orig,$w_orig,$d_orig,$dw_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
                    $time_orig_end = date('Y/n/W/j/N/G/i/s',$orig_end);
                    list($y_orig_e,$m_orig_e,$w_orig_e,$d_orig_e,$dw_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);

					$time_now = date('Y/n/W/j/N/G/i/s',$end);
					list($y_now,$m_now,$w_now,$d_now,$dw_now,$h_now,$n_now,$s_now) = split('/',$time_now);

					$month_first_week = date('W',$start);
					$month_last_week = date('W',$end);

					if(($y_now>$y_orig) OR (($y_now == $y_orig) && ($w_now>$w_orig)))
					{ //if the event is after the original (at least one week) and the day of the week is the same
						for($i=$month_first_week;$i<=$month_last_week;$i++)
						{
						  //the "day of the week" of repetition is the same as the $dw_orig,
                          //so to get the "day of the month" from the "day of the week", we have
                          //to get the first "day of the week" for this week and add the number
                          //of days (in seconds) to reach the $dw_orig
                          //example: the first week spans between the 28th of April (Monday) to the
                          // 4th of May (Sunday). The event occurs on the 2nd day of each week.
                          // This means the event occurs on 29/4, 6/5, 13/5, 20/5 and 27/5.
                          // We want to get all of these, and then reject 29/4 because it is out
                          // of the month itself.

                          //First, to get the start time of the first day of the month view (even if
                          // the day is from the past month), we get the month start date (1/5) and
                          // see which day of the week it is, and subtract the number of days necessary
                          // to get back to the first day of the week.
                          $month_first_day_weekday = date('N',$start);
                          $first_week_start = $start - (($month_first_day_weekday-1)*86400);

                          //Second, we add the week day of the original event, so that we have an
                          // absolute time that represents the first repetition of the event in
                          // our 4- or 5-weeks timespan
                          $first_event_repeat_start = $first_week_start + (($dw_orig-1)*86400) + ($h_orig*3600) + ($n_orig*60) + $s_orig;

                          //Third, we start looping through the repetitions and see if they are between
                          // $start and $end
					      for($i = $first_event_repeat_start; $i<=$end; $i+=604800)
                          {
                          	if($start<$i && $i<$end)
                            {
                               list($y_repeat,$m_repeat,$d_repeat,$h_repeat,$n_repeat,$s_repeat) = split('/',date('Y/m/j/H/i/s',$i));
                               $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
                            }
                          }
						}
					}
					break;
				case 'monthlyByDate':
					$time_orig = date('Y/n/W/j/G/i/s',$orig_start);
					list($y_orig,$m_orig,$w_orig,$d_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
					$time_now = date('Y/n/W/j/G/i/s',$end);
					list($y_now,$m_now,$w_now,$d_now,$h_now,$n_now,$s_now) = split('/',$time_now);
					$event_repetition_time = mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now);
					if(($y_now>$y_orig) OR (($y_now == $y_orig) && ($m_now>$m_orig)))
					{ //if the event is after the original (at least one month) and the original event's day is between the first day of the week and the last day of the week
					  $time_orig_end = date('Y/n/j/G/i/s',$orig_end);
					  list($y_orig_e,$m_orig_e,$d_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);
					  $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
					}
					break;
				case 'monthlyByDayR':
					//not implemented yet
					break;
				case 'monthlyByDay':
					//not implemented yet
					break;
				case 'yearly':
					$time_orig = date('Y/n/j/z/G/i/s',$orig_start);
					list($y_orig,$m_orig,$d_orig,$dy_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
					$time_now = date('Y/n/j/z/G/i/s',$end);
					list($y_now,$m_now,$d_now,$dy_now,$h_now,$n_now,$s_now) = split('/',$time_now);
					$event_repetition_time = mktime($h_orig,$n_orig,$s_orig,$m_orig,$d_orig,$y_now);
					if((($y_now>$y_orig) && ($start<$event_repetition_time && $event_repetition_time<$end)))
					{
					  $time_orig_end = date('Y/n/j/G/i/s',$orig_end);
					  list($y_orig_e,$m_orig_e,$d_orig_e,$dy_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);
					  $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
					}
					break;
				default:
					break;
			}
		}
	}
	return $events;
}
/**
 * Get repeated events of a course between two dates (1 year timespan). Used for the list display.
 * This is virtually unlimited but by default it shortens to 100 years from now (even a birthday shouldn't be useful more than this time - except for turtles)
 * Returns an array containing the events
 * @param   string  Course info array (as returned by api_get_course_info())
 * @param   int     UNIX timestamp of span start. Defaults 0, later transformed into today's start
 * @param   int     UNIX timestamp. Defaults to 0, later transformed into today's end
 * @param   array   A set of parameters to alter the SQL query
 * @return  array   [int] => [course_id,parent_event_id,start_date,end_date,title,description]
 */
function get_repeated_events_list_view($course_info,$start=0,$end=0,$params)
{
    $events = array();
    //block $end if higher than 2038 -- PHP doesn't go past that
    if($end>2145934800){$end = 2145934800;}
    //initialise all values
    $y=0;
    $m=0;
    $d=0;
    if(empty($start) or empty($end))
    {
        $time = time();
        $y = date('Y');
        $m = date('m');
    }
    if(empty($start))
    {
        $start = mktime(0, 0, 0, $m, 1, $y);
    }
    $db_start = date('Y-m-d H:i:s', $start);
    if(empty($end))
    {
        $end = mktime(0, 0, 0, 1, 1, 2037);
    }
    //$db_end = date('Y-m-d H:i:s',$end);

    $t_cal = Database::get_course_table(TABLE_AGENDA,$course_info['dbName']);
    $t_cal_repeat = Database::get_course_table(TABLE_AGENDA_REPEAT,$course_info['dbName']);
    $t_ip = Database::get_course_table(TABLE_ITEM_PROPERTY,$course_info['dbName']);
    $sql = "SELECT c.id, c.title, c.content, " .
            " UNIX_TIMESTAMP(c.start_date) as orig_start, UNIX_TIMESTAMP(c.end_date) as orig_end, " .
            " cr.cal_type, cr.cal_end " .
            " FROM $t_cal c, $t_cal_repeat cr, $t_ip as item_property " .
            " WHERE cr.cal_end >= $start " .
            " AND cr.cal_id = c.id " .
            " AND item_property.ref = c.id ".
            " AND item_property.tool = '".TOOL_CALENDAR_EVENT."' ".
            " AND c.start_date <= '$db_start' "
            .(!empty($params['conditions'])?$params['conditions']:'')
            .(!empty($params['groupby'])?' GROUP BY '.$params['groupby']:'')
            .(!empty($params['orderby'])?' ORDER BY '.$params['orderby']:'');
    $res = Database::query($sql);
    if(Database::num_rows($res)>0)
    {
        while($row = Database::fetch_array($res))
        {
            $orig_start = $row['orig_start'];
            $orig_end = $row['orig_end'];
            $repeat_type = $row['cal_type'];
            $repeat_end = $row['cal_end'];
            switch($repeat_type)
            {
                case 'daily':
                    $time_orig_h = date('H',$orig_start);
                    $time_orig_m = date('i',$orig_start);
                    $time_orig_s = date('s',$orig_start);
                    $span = $orig_end - $orig_start; //total seconds between start and stop of original event
                    for($i=$orig_start+86400;($i<$end && $i<=$repeat_end);$i+=86400)
                    {
                        $current_start = $i; //unixtimestamp start of today's event
                        $current_stop = $i + $span; //unixtimestamp stop of today's event
                        $events[] = array($course_info['id'],$row['id'],$current_start,$current_stop,$row['title'],$row['content']);
                    }
                    break;
                case 'weekly':
                    //A weekly repeated event is very difficult to catch in a month view,
                    // because weeks start before or at the same time as the first day of the month
                    //The same can be said for the end of the month.
                    // The idea is thus to get all possible events by enlarging the scope of
                    // the month to get complete weeks covering the complete month, and then take out
                    // the events that start before the 1st ($start) or after the last day of the month ($end)
                    $time_orig = date('Y/n/W/j/N/G/i/s',$orig_start);
                    list($y_orig,$m_orig,$w_orig,$d_orig,$dw_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
                    $time_orig_end = date('Y/n/W/j/N/G/i/s',$orig_end);
                    list($y_orig_e,$m_orig_e,$w_orig_e,$d_orig_e,$dw_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);

                    $time_now = date('Y/n/W/j/N/G/i/s',$end);
                    list($y_now,$m_now,$w_now,$d_now,$dw_now,$h_now,$n_now,$s_now) = split('/',$time_now);
                    if($w_now==52)
                    {
                        ++$y_now;
                        $w_now=1;
                    }
                    else
                    {
                        ++$w_now;
                    }
                    $month_first_week = date('W',$start);
                    $total_weeks = ((date('Y',$end)-$y_orig)-1)*52;
                    $month_last_week = $month_first_week + $total_weeks;

                    if(($y_now>$y_orig) OR (($y_now == $y_orig) && ($w_now>$w_orig)))
                    { //if the event is after the original (at least one week) and the day of the week is the same
                        //for($i=$month_first_week;($i<=$month_last_week && $i<1000);$i++)
                        //{


                          /*
                           The "day of the week" of repetition is the same as the $dw_orig,
                           so to get the "day of the month" from the "day of the week", we have
                           to get the first "day of the week" for this week and add the number
                           of days (in seconds) to reach the $dw_orig
                          example: the first week spans between the 28th of April (Monday) to the
                           4th of May (Sunday). The event occurs on the 2nd day of each week.
                           This means the event occurs on 29/4, 6/5, 13/5, 20/5 and 27/5.
                           We want to get all of these, and then reject 29/4 because it is out
                           of the month itself.
                          First, to get the start time of the first day of the month view (even if
                           the day is from the past month), we get the month start date (1/5) and
                           see which day of the week it is, and subtract the number of days necessary
                           to get back to the first day of the week.
                          */
                          $month_first_day_weekday = date('N',$start);
                          $first_week_start = $start - (($month_first_day_weekday-1)*86400);

                          //Second, we add the week day of the original event, so that we have an
                          // absolute time that represents the first repetition of the event in
                          // our 4- or 5-weeks timespan
                          $first_event_repeat_start = $first_week_start + (($dw_orig-1)*86400) + ($h_orig*3600) + ($n_orig*60) + $s_orig;

                          //Third, we start looping through the repetitions and see if they are between
                          // $start and $end
                          for($i = $first_event_repeat_start; ($i<=$end && $i<=$repeat_end); $i+=604800)
                          {
                            if($start<$i && $i<=$end && $i<=$repeat_end)
                            {
                               list($y_repeat,$m_repeat,$d_repeat,$h_repeat,$n_repeat,$s_repeat) = split('/',date('Y/m/j/H/i/s',$i));
                               $new_start_time = mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now);
                               $new_stop_time = mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now);
                               $events[] = array($course_info['id'], $row['id'], $new_start_time, $new_stop_time, $row['title'], $row['content']);
                            }
                            $time_now = date('Y/n/W/j/N/G/i/s',$i+604800);
                            list($y_now,$m_now,$w_now,$d_now,$dw_now,$h_now,$n_now,$s_now) = split('/',$time_now);
                          }
                        //}
                    }
                    break;
                case 'monthlyByDate':
                    $time_orig = date('Y/n/W/j/G/i/s',$orig_start);
                    list($y_orig,$m_orig,$w_orig,$d_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);

                    $time_now = date('Y/n/W/j/G/i/s',$start);
                    list($y_now,$m_now,$w_now,$d_now,$h_now,$n_now,$s_now) = split('/',$time_now);
                    //make sure we are one month ahead (to avoid being the same month as the original event)
                    if($m_now==12)
                    {
                        ++$y_now;
                        $m_now = 1;
                    }
                    else
                    {
                        ++$m_now;
                    }

                    $time_orig_end = date('Y/n/j/G/i/s',$orig_end);
                    list($y_orig_e,$m_orig_e,$d_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);

                    $event_repetition_time = mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now);
                    $diff = $orig_end - $orig_start;
                    while((($y_now>$y_orig) OR (($y_now == $y_orig) && ($m_now>$m_orig))) && ($event_repetition_time < $end) && ($event_repetition_time < $repeat_end))
                    { //if the event is after the original (at least one month) and the original event's day is between the first day of the week and the last day of the week
                      $new_start_time = mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now);
                      $new_stop_time = mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now);
                      $events[] = array($course_info['id'],$row['id'],$new_start_time,$new_stop_time,$row['title'],$row['content']);
                      if($m_now==12)
                      {
                      	++$y_now;
                        $m_now = 1;
                      }
                      else
                      {
                        ++$m_now;
                      }
                      $event_repetition_time = mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now);
                    }
                    break;
                case 'monthlyByDayR':
                    //not implemented yet
                    break;
                case 'monthlyByDay':
                    //not implemented yet
                    break;
                case 'yearly':
                    $time_orig = date('Y/n/j/z/G/i/s',$orig_start);
                    list($y_orig,$m_orig,$d_orig,$dy_orig,$h_orig,$n_orig,$s_orig) = split('/',$time_orig);
                    $time_now = date('Y/n/j/z/G/i/s',$end);
                    list($y_now,$m_now,$d_now,$dy_now,$h_now,$n_now,$s_now) = split('/',$time_now);
                    $event_repetition_time = mktime($h_orig,$n_orig,$s_orig,$m_orig,$d_orig,$y_now);
                    while((($y_now>$y_orig) && ($start<$event_repetition_time && $event_repetition_time<$end && $event_repetition_time<$repeat_end)))
                    {
                      $time_orig_end = date('Y/n/j/G/i/s',$orig_end);
                      list($y_orig_e,$m_orig_e,$d_orig_e,$dy_orig_e,$h_orig_e,$n_orig_e,$s_orig_e) = split('/',$time_orig_end);
                      $events[] = array($course_info['id'],$row['id'],mktime($h_orig,$n_orig,$s_orig,$m_now,$d_orig,$y_now),mktime($h_orig_e,$n_orig_e,$s_orig_e,$m_now,$d_orig_e,$y_now),$row['title'],$row['content']);
                      ++$y_now;
                      $event_repetition_time = mktime($h_orig,$n_orig,$s_orig,$m_orig,$d_orig,$y_now);
                    }
                    break;
                default:
                    break;
            }
        }
    }
    return $events;
}
/**
 * Tells if an agenda item is repeated
 * @param   string  Course database
 * @param   int     The agenda item
 * @return  boolean True if repeated, false otherwise
 */
function is_repeated_event($id,$course=null)
{
	if(empty($course))
    {
    	$course_info = api_get_course_info();
        $course = $course_info['dbName'];
    }
    $id = (int) $id;
	$t_agenda_repeat = Database::get_course_table(TABLE_AGENDA_REPEAT,$course);
    $sql = "SELECT * FROM $t_agenda_repeat WHERE cal_id = $id";
    $res = Database::query($sql);
    if(Database::num_rows($res)>0)
    {
    	return true;
    }
    return false;
}
/**
 * Adds x weeks to a UNIX timestamp
 * @param   int     The timestamp
 * @param   int     The number of weeks to add
 * @return  int     The new timestamp
 */
function add_week($timestamp,$num=1)
{
    return $timestamp + $num*604800;
}
/**
 * Adds x months to a UNIX timestamp
 * @param   int     The timestamp
 * @param   int     The number of years to add
 * @return  int     The new timestamp
 */
function add_month($timestamp,$num=1)
{
	list($y, $m, $d, $h, $n, $s) = split('/',date('Y/m/d/h/i/s',$timestamp));
    if($m+$num>12)
    {
    	$y += floor($num/12);
        $m += $num%12;
    }
    else
    {
        $m += $num;
    }
    return mktime($h, $n, $s, $m, $d, $y);
}
/**
 * Adds x years to a UNIX timestamp
 * @param   int     The timestamp
 * @param   int     The number of years to add
 * @return  int     The new timestamp
 */
function add_year($timestamp,$num=1)
{
    list($y, $m, $d, $h, $n, $s) = split('/',date('Y/m/d/h/i/s',$timestamp));
    return mktime($h, $n, $s, $m, $d, $y+$num);
}
/**
 * Adds an agenda item in the database. Similar to store_new_agenda_item() except it takes parameters
 * @param   array   Course info
 * @param   string  Event title
 * @param   string  Event content/description
 * @param   string  Start date
 * @param   string  End date
 * @param   array   List of groups to which this event is added
 * @param   int     Parent id (optional)
 * @return  int     The new item's DB ID
 */
function agenda_add_item($course_info, $title, $content, $db_start_date, $db_end_date, $to = array(), $parent_id = null, $file_comment='') {	
    $user_id    = api_get_user_id();

    // database table definitions
    $t_agenda                = Database::get_course_table(TABLE_AGENDA);    
    $item_property 			 = Database::get_course_table(TABLE_ITEM_PROPERTY);

    // some filtering of the input data    
	$title          = Database::escape_string($title);
	$content        = Database::escape_string($content);
	$db_start_date  = api_get_utc_datetime($db_start_date);
    $start_date     = Database::escape_string($db_start_date);
    if (!empty($db_end_date)) {
        $db_end_date = api_get_utc_datetime($db_end_date);        
    }    
    $end_date       = Database::escape_string($db_end_date);
    $id_session     = api_get_session_id();
    $course_id      = api_get_course_int_id();
    $group_id       = api_get_group_id();

    // check if exists in calendar_event table and if it is not deleted!
    $sql = "SELECT * FROM $t_agenda agenda, $item_property item_property
    			WHERE
    			agenda.c_id                  = $course_id AND
    			item_property.c_id           = $course_id AND   
    			agenda.title                 = '$title'
    			AND agenda.content           = '$content'
    			AND agenda.start_date        = '$start_date'
    			AND agenda.end_date          = '$end_date' ".(!empty($parent_id)? "
    			AND agenda.parent_event_id   = '$parent_id'":"")."
    			AND agenda.session_id        = '$id_session'
    			AND item_property.tool       = '".TOOL_CALENDAR_EVENT."'
    			AND item_property.ref        = agenda.id
    			AND item_property.visibility <> 2";
    
    $result = Database::query($sql);
    $count  = Database::num_rows($result);
    if ($count > 0) {
    	return false;
    }
    $course_id = api_get_course_int_id();
    
    $sql = "INSERT INTO ".$t_agenda." (c_id, title,content, start_date, end_date".(!empty($parent_id)?',parent_event_id':'').", session_id)
            VALUES($course_id, '".$title."','".$content."', '".$start_date."','".$end_date."'".(!empty($parent_id)?','.((int)$parent_id):'').", '".$id_session."')";
    
    $result  = Database::query($sql);
    $last_id = Database::insert_id();

    // add a attachment file in agenda

    add_agenda_attachment_file($file_comment, $last_id);

    // store in last_tooledit (first the groups, then the users
    
    if (!empty($to)) {        
        $send_to = separate_users_groups($to);        
        // storing the selected groups
        if (is_array($send_to['groups'])) {
            foreach ($send_to['groups'] as $group) {
                api_item_property_update($course_info, TOOL_CALENDAR_EVENT, $last_id, "AgendaAdded", $user_id, $group, 0, $start_date, $end_date);
                $done = true;
            }
        }
        // storing the selected users
        if (is_array($send_to['users'])) {
            foreach ($send_to['users'] as $user) {
                api_item_property_update($course_info, TOOL_CALENDAR_EVENT, $last_id, "AgendaAdded", $user_id,0,$user, $start_date,$end_date);
                $done = true;
            }
        }
        if (isset($send_to['everyone']) && $send_to['everyone']) {
            api_item_property_update($course_info, TOOL_CALENDAR_EVENT, $last_id, "AgendaAdded", $user_id, 0, 0, $start_date, $end_date);    
        }
    }

    // storing the resources
    if (!empty($_SESSION['source_type']) && !empty($last_id)) {
        store_resources($_SESSION['source_type'], $last_id);
    }
    return $last_id;
}

/**
 * This function delete a attachment file by id
 * @param integer attachment file Id
 *
 */
function delete_attachment_file($id_attach) {
	global $_course;
	$agenda_table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
	$id_attach = intval($id_attach);

	$sql="DELETE FROM $agenda_table_attachment WHERE id = ".$id_attach;
	$result=Database::query($sql);
	$last_id_file=Database::insert_id();
	// update item_property
	api_item_property_update($_course, 'calendar_event_attachment', $id_attach ,'AgendaAttachmentDeleted', api_get_user_id());
	if (!empty($result)) {
	   Display::display_confirmation_message(get_lang("AttachmentFileDeleteSuccess"));
	}
}

/**
 * This function add a attachment file into agenda
 * @param string  a comment about file
 * @param int last id from calendar table
 *
 */
function add_agenda_attachment_file($file_comment,$last_id) {

	global $_course;
	$agenda_table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
	$last_id = intval($last_id);
	// Storing the attachments
    if(!empty($_FILES['user_upload']['name'])) {
		$upload_ok = process_uploaded_file($_FILES['user_upload']);
	}

	if (!empty($upload_ok)) {
			$courseDir   = $_course['path'].'/upload/calendar';
			$sys_course_path = api_get_path(SYS_COURSE_PATH);
			$updir = $sys_course_path.$courseDir;

			// Try to add an extension to the file if it hasn't one
			$new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);
			// user's file name
			$file_name =$_FILES['user_upload']['name'];

			if (!filter_extension($new_file_name))  {
				Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
			} else {
				$new_file_name = uniqid('');
				$new_path=$updir.'/'.$new_file_name;
				$result= @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
				$safe_file_comment= Database::escape_string($file_comment);
				$safe_file_name = Database::escape_string($file_name);
				$safe_new_file_name = Database::escape_string($new_file_name);
                $course_id = api_get_course_int_id();
				// Storing the attachments if any
				if ($result) {
					$sql='INSERT INTO '.$agenda_table_attachment.'(c_id, filename,comment, path,agenda_id,size) '.
						 "VALUES ($course_id,  '".$safe_file_name."', '".$safe_file_comment."', '".$safe_new_file_name."' , '".$last_id."', '".intval($_FILES['user_upload']['size'])."' )";
					$result=Database::query($sql);
					$message.=' / '.get_lang('FileUploadSucces').'<br />';

					$last_id_file=Database::insert_id();
					api_item_property_update($_course, 'calendar_event_attachment', $last_id_file ,'AgendaAttachmentAdded', api_get_user_id());

				}
			}
		}
}
/**
 * This function edit a attachment file into agenda
 * @param string  a comment about file
 * @param int Agenda Id
 *  @param int attachment file Id
 */
function edit_agenda_attachment_file($file_comment, $agenda_id, $id_attach) {

	global $_course;
	$agenda_table_attachment = Database::get_course_table(TABLE_AGENDA_ATTACHMENT);
	// Storing the attachments

    if(!empty($_FILES['user_upload']['name'])) {
		$upload_ok = process_uploaded_file($_FILES['user_upload']);
	}

	if (!empty($upload_ok)) {
			$courseDir   = $_course['path'].'/upload/calendar';
			$sys_course_path = api_get_path(SYS_COURSE_PATH);
			$updir = $sys_course_path.$courseDir;

			// Try to add an extension to the file if it hasn't one
			$new_file_name = add_ext_on_mime(stripslashes($_FILES['user_upload']['name']), $_FILES['user_upload']['type']);
			// user's file name
			$file_name =$_FILES['user_upload']['name'];

			if (!filter_extension($new_file_name))  {
				Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
			} else {
				$new_file_name = uniqid('');
				$new_path=$updir.'/'.$new_file_name;
				$result= @move_uploaded_file($_FILES['user_upload']['tmp_name'], $new_path);
				$safe_file_comment= Database::escape_string($file_comment);
				$safe_file_name = Database::escape_string($file_name);
				$safe_new_file_name = Database::escape_string($new_file_name);
				$safe_agenda_id = (int)$agenda_id;
				$safe_id_attach = (int)$id_attach;
				// Storing the attachments if any
				if ($result) {
					$sql="UPDATE $agenda_table_attachment SET filename = '$safe_file_name', comment = '$safe_file_comment', path = '$safe_new_file_name', agenda_id = '$safe_agenda_id', size ='".intval($_FILES['user_upload']['size'])."'
						   WHERE id = '$safe_id_attach'";
					$result=Database::query($sql);
					api_item_property_update($_course, 'calendar_event_attachment', $safe_id_attach ,'AgendaAttachmentUpdated', api_get_user_id());

				}
			}
		}
}

/**
 * Adds a repetitive item to the database
 * @param   array   Course info
 * @param   int     The original event's id
 * @param   string  Type of repetition
 * @param   int     Timestamp of end of repetition (repeating until that date)
 * @param   array   Original event's destination (users list)
 * @param 	string  a comment about a attachment file into agenda
 * @return  boolean False if error, True otherwise
 */
function agenda_add_repeat_item($course_info, $orig_id, $type, $end, $orig_dest, $file_comment='') {
	$t_agenda   = Database::get_course_table(TABLE_AGENDA);
    $t_agenda_r = Database::get_course_table(TABLE_AGENDA_REPEAT);
	
	$course_id = $course_info['real_id'];

    $sql = 'SELECT title, content, start_date as sd, end_date as ed FROM '. $t_agenda.' WHERE c_id = '.$course_id.' AND id ="'.intval($orig_id).'" ';
    $res = Database::query($sql);
    if(Database::num_rows($res)!==1){return false;}
    $row = Database::fetch_array($res);
    $orig_start = api_strtotime(api_get_local_time($row['sd']));
    $orig_end   = api_strtotime(api_get_local_time($row['ed']));
        
    $diff           = $orig_end - $orig_start;
    $orig_title     = $row['title'];
    $orig_content   = $row['content'];
    $now            = time();
    $type           = Database::escape_string($type);
    $end            = intval($end);
    
    if (1<=$end && $end<=500) {
    	//we assume that, with this type of value, the user actually gives a count of repetitions
        //and that he wants us to calculate the end date with that (particularly in case of imports from ical)
        switch($type) {
            case 'daily':
                $end = $orig_start + (86400*$end);
                break;
            case 'weekly':
                $end = add_week($orig_start,$end);
                break;
            case 'monthlyByDate':
                $end = add_month($orig_start,$end);
                break;
            case 'monthlyByDay':
                //TODO
                break;
            case 'monthlyByDayR':
                //TODO
                break;
            case 'yearly':
                $end = add_year($orig_start,$end);
                break;
        }
    }
    
    if ($end > $now
        && in_array($type,array('daily','weekly','monthlyByDate','monthlyByDay','monthlyByDayR','yearly'))) {
       $sql = "INSERT INTO $t_agenda_r (c_id, cal_id, cal_type, cal_end) VALUES ($course_id, '$orig_id','$type',$end)";
       $res = Database::query($sql);
        switch($type) {
            case 'daily':
                for($i = $orig_start + 86400; ($i <= $end); $i += 86400) {
                    $res = agenda_add_item($course_info, $orig_title, $orig_content, date('Y-m-d H:i:s', $i), date('Y-m-d H:i:s', $i+$diff), $orig_dest, $orig_id,$file_comment);
                }
                break;
            case 'weekly':
                for($i = $orig_start + 604800; ($i <= $end); $i += 604800) {
                    $res = agenda_add_item($course_info, $orig_title, $orig_content, date('Y-m-d H:i:s', $i), date('Y-m-d H:i:s', $i+$diff), $orig_dest, $orig_id,$file_comment);
                }
                break;
            case 'monthlyByDate':
                $next_start = add_month($orig_start);
                while($next_start <= $end) {
                    $res = agenda_add_item($course_info, $orig_title, $orig_content, date('Y-m-d H:i:s', $next_start), date('Y-m-d H:i:s', $next_start+$diff), $orig_dest, $orig_id,$file_comment);
                    $next_start = add_month($next_start);
                }
                break;
            case 'monthlyByDay':
                //not yet implemented
                break;
            case 'monthlyByDayR':
                //not yet implemented
                break;
            case 'yearly':
                $next_start = add_year($orig_start);
                while($next_start <= $end) {
                    $res = agenda_add_item($course_info, $orig_title, $orig_content, date('Y-m-d H:i:s', $next_start), date('Y-m-d H:i:s', $next_start+$diff), $orig_dest, $orig_id,$file_comment);
                    $next_start = add_year($next_start);
                }
                break;
        }
    }
	return true;
}
/**
 * Import an iCal file into the database
 * @param   array   Course info
 * @return  boolean True on success, false otherwise
 */
function agenda_import_ical($course_info,$file) {
	require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
   	$charset = api_get_system_encoding();
    $filepath = api_get_path(SYS_ARCHIVE_PATH).$file['name'];
    if (!@move_uploaded_file($file['tmp_name'],$filepath)) {
    	error_log('Problem moving uploaded file: '.$file['error'].' in '.__FILE__.' line '.__LINE__);
    	return false;
    }
    require_once api_get_path(LIBRARY_PATH).'icalcreator/iCalcreator.class.php';

    $ical = new vcalendar();
    $ical->setConfig('directory', dirname($filepath) );
    $ical->setConfig('filename', basename($filepath) );
    $return = $ical->parse();

    //we need to recover: summary, description, dtstart, dtend, organizer, attendee, location (=course name),
/*
    $ve = $ical->getComponent(VEVENT);

    $ttitle	= $ve->getProperty('summary');
    $title	= api_convert_encoding($ttitle,$charset,'UTF-8');

    $tdesc	= $ve->getProperty('description');
    $desc	= api_convert_encoding($tdesc,$charset,'UTF-8');

    $start_date	= $ve->getProperty('dtstart');
    $start_date_string = $start_date['year'].'-'.$start_date['month'].'-'.$start_date['day'].' '.$start_date['hour'].':'.$start_date['min'].':'.$start_date['sec'];


    $ts 	 = $ve->getProperty('dtend');
    if ($ts) {
    	$end_date_string = $ts['year'].'-'.$ts['month'].'-'.$ts['day'].' '.$ts['hour'].':'.$ts['min'].':'.$ts['sec'];
    } else {
    	//Check duration if dtend does not exist
    	$duration 	  = $ve->getProperty('duration');
    	if ($duration) {
    		$duration = $ve->getProperty('duration');
    		$duration_string = $duration['year'].'-'.$duration['month'].'-'.$duration['day'].' '.$duration['hour'].':'.$duration['min'].':'.$duration['sec'];
    		$start_date_tms = mktime(intval($start_date['hour']), intval($start_date['min']), intval($start_date['sec']), intval($start_date['month']), intval($start_date['day']), intval($start_date['year']));
    		//$start_date_tms = mktime(($start_date['hour']), ($start_date['min']), ($start_date['sec']), ($start_date['month']), ($start_date['day']), ($start_date['year']));
    		//echo date('d-m-Y - h:i:s', $start_date_tms);

    		$end_date_string = mktime(intval($start_date['hour']) +$duration['hour'], intval($start_date['min']) + $duration['min'], intval($start_date['sec']) + $duration['sec'], intval($start_date['month']) + $duration['month'], intval($start_date['day'])+$duration['day'], intval($start_date['year']) + $duration['year']);
    		$end_date_string = date('Y-m-d H:i:s', $end_date_string);
    		//echo date('d-m-Y - h:i:s', $end_date_string);
    	}
    }


    //echo $start_date.' - '.$end_date;
    $organizer	 = $ve->getProperty('organizer');
    $attendee 	 = $ve->getProperty('attendee');
    $course_name = $ve->getProperty('location');
    //insert the event in our database
    //var_dump($title,$desc,$start_date,$end_date);
    $id = agenda_add_item($course_info,$title,$desc,$start_date_string,$end_date_string,$_POST['selectedform']);
    

    $repeat = $ve->getProperty('rrule');
    if(is_array($repeat) && !empty($repeat['FREQ'])) {
    	$trans = array('DAILY'=>'daily','WEEKLY'=>'weekly','MONTHLY'=>'monthlyByDate','YEARLY'=>'yearly');
        $freq = $trans[$repeat['FREQ']];
        $interval = $repeat['INTERVAL'];
        if(isset($repeat['UNTIL']) && is_array($repeat['UNTIL'])) {
            $until = mktime(23,59,59,$repeat['UNTIL']['month'],$repeat['UNTIL']['day'],$repeat['UNTIL']['year']);
            $res = agenda_add_repeat_item($course_info,$id,$freq,$until,$_POST['selectedform']);
        }*/
    $eventcount = 0;
    while (true) {
    	//we need to recover: summary, description, dtstart, dtend, organizer, attendee, location (=course name),
    
    	$ve = $ical->getComponent(VEVENT, $eventcount);    	
    	if (!$ve)
    	break;
    
    	$ttitle	= $ve->getProperty('summary');
    	$title	= api_convert_encoding($ttitle,$charset,'UTF-8');
    
    	$tdesc	= $ve->getProperty('description');
    	$desc	= api_convert_encoding($tdesc,$charset,'UTF-8');
    
    	$start_date	= $ve->getProperty('dtstart');
    	$start_date_string = $start_date['year'].'-'.$start_date['month'].'-'.$start_date['day'].' '.$start_date['hour'].':'.$start_date['min'].':'.$start_date['sec'];
    
    
    	$ts 	 = $ve->getProperty('dtend');
    	if ($ts) {
    		$end_date_string = $ts['year'].'-'.$ts['month'].'-'.$ts['day'].' '.$ts['hour'].':'.$ts['min'].':'.$ts['sec'];
    	} else {
    		//Check duration if dtend does not exist
    		$duration 	  = $ve->getProperty('duration');
    		if ($duration) {
    			$duration = $ve->getProperty('duration');
    			$duration_string = $duration['year'].'-'.$duration['month'].'-'.$duration['day'].' '.$duration['hour'].':'.$duration['min'].':'.$duration['sec'];
    			$start_date_tms = mktime(intval($start_date['hour']), intval($start_date['min']), intval($start_date['sec']), intval($start_date['month']), intval($start_date['day']), intval($start_date['year']));
    			//$start_date_tms = mktime(($start_date['hour']), ($start_date['min']), ($start_date['sec']), ($start_date['month']), ($start_date['day']), ($start_date['year']));
    			//echo date('d-m-Y - h:i:s', $start_date_tms);
    
    			$end_date_string = mktime(intval($start_date['hour']) +$duration['hour'], intval($start_date['min']) + $duration['min'], intval($start_date['sec']) + $duration['sec'], intval($start_date['month']) + $duration['month'], intval($start_date['day'])+$duration['day'], intval($start_date['year']) + $duration['year']);
    			$end_date_string = date('Y-m-d H:i:s', $end_date_string);
    			//echo date('d-m-Y - h:i:s', $end_date_string);
    		}
    	}
    		
    	//echo $start_date.' - '.$end_date;
    	$organizer	 = $ve->getProperty('organizer');
    	$attendee 	 = $ve->getProperty('attendee');
    	$course_name = $ve->getProperty('location');
    	//insert the event in our database
    	//var_dump($title,$desc,$start_date,$end_date);
    	$id = agenda_add_item($course_info,$title,$desc,$start_date_string,$end_date_string,$attendee);
    	
    	$repeat = $ve->getProperty('rrule');
    	if(is_array($repeat) && !empty($repeat['FREQ'])) {
		    $trans = array('DAILY'=>'daily','WEEKLY'=>'weekly','MONTHLY'=>'monthlyByDate','YEARLY'=>'yearly');
			$freq = $trans[$repeat['FREQ']];
	    	$interval = $repeat['INTERVAL'];
	    	if (isset($repeat['UNTIL']) && is_array($repeat['UNTIL'])) {
	    		$until = mktime(23,59,59,$repeat['UNTIL']['month'],$repeat['UNTIL']['day'],$repeat['UNTIL']['year']);
	    		                $res = agenda_add_repeat_item($course_info,$id,$freq,$until,$attendee);
	    	}
	    	
	    	//TODO: deal with count
	    	if(!empty($repeat['COUNT'])) {
	    		$count = $repeat['COUNT'];
	    		$res = agenda_add_repeat_item($course_info,$id,$freq,$count,$attendee);
	    	}    		
	    }
		$eventcount++;    	
    }
    return true;
}

/**
 * This function retrieves one personal agenda item returns it.
 * @param	array	The array containing existing events. We add to this array.
 * @param	int		Day
 * @param	int		Month
 * @param	int		Year (4 digits)
 * @param	int		Week number
 * @param	string	Type of view (month_view, week_view, day_view)
 * @return 	array	The results of the database query, or null if not found
 */
function get_global_agenda_items($agendaitems, $day = "", $month = "", $year = "", $week = "", $type) {
	$tbl_global_agenda= Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);    
	$month = Database::escape_string($month);
	$year  = Database::escape_string($year);
	$week  = Database::escape_string($week);
	$day   = Database::escape_string($day);
	// 1. creating the SQL statement for getting the personal agenda items in MONTH view
	
	$current_access_url_id = api_get_current_access_url_id();	

	if ($type == "month_view" or $type == "") {
		// We are in month view
		$sql = "SELECT * FROM ".$tbl_global_agenda." WHERE MONTH(start_date)='".$month."' AND YEAR(start_date) = '".$year."'  AND access_url_id = $current_access_url_id ORDER BY start_date ASC";
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
		$start_filter  = api_get_utc_datetime($start_filter);
		
		$end_filter = $end_year."-".$end_month."-".$end_day." 23:59:59";
		$end_filter  = api_get_utc_datetime($end_filter);
		$sql = " SELECT * FROM ".$tbl_global_agenda." WHERE start_date>='".$start_filter."' AND start_date<='".$end_filter."' AND  access_url_id = $current_access_url_id ";
	}
	// 3. creating the SQL statement for getting the personal agenda items in DAY view
	if ($type == "day_view") // we are in day view
	{
		// we could use mysql date() function but this is only available from 4.1 and higher
		$start_filter = $year."-".$month."-".$day." 00:00:00";
		$start_filter  = api_get_utc_datetime($start_filter);
		
		$end_filter = $year."-".$month."-".$day." 23:59:59";
		$end_filter  = api_get_utc_datetime($end_filter);
		$sql = " SELECT * FROM ".$tbl_global_agenda." WHERE start_date>='".$start_filter."' AND start_date<='".$end_filter."'  AND  access_url_id = $current_access_url_id";
	}

	$result = Database::query($sql);

	while ($item = Database::fetch_array($result)) {
		
		if ($item['start_date'] != '0000-00-00 00:00:00') {
			$item['start_date'] = api_get_local_time($item['start_date']);
			$item['start_date_tms']  = api_strtotime($item['start_date']);
		}
		if ($item['end_date'] != '0000-00-00 00:00:00') {
			$item['end_date'] = api_get_local_time($item['end_date']);
		}		
	    
		// we break the date field in the database into a date and a time part
		$agenda_db_date = explode(" ", $item['start_date']);
		$date = $agenda_db_date[0];
		$time = $agenda_db_date[1];
		// we divide the date part into a day, a month and a year
		$agendadate = explode("-", $date);
		$year  = intval($agendadate[0]);
		$month = intval($agendadate[1]);
		$day   = intval($agendadate[2]);
		// we divide the time part into hour, minutes, seconds
		$agendatime = explode(":", $time);
		$hour = $agendatime[0];
		$minute = $agendatime[1];
		$second = $agendatime[2];		
	      
	    if ($type == 'month_view') {
	        $item['calendar_type'] = 'global';
            $agendaitems[$day][] = $item;
            continue;
        }	
		
		$start_time = api_format_date($item['start_date'], TIME_NO_SEC_FORMAT);
		$end_time = '';
		if ($item['end_date'] != '0000-00-00 00:00:00') {
            $end_time = ' - '.api_format_date($item['end_date'], DATE_TIME_FORMAT_LONG);
		}
		
		// if the student has specified a course we a add a link to that course
		if ($item['course'] <> "") {	
			$url = api_get_path(WEB_CODE_PATH)."admin/agenda.php?cidReq=".urlencode($item['course'])."&day=$day&month=$month&year=$year#$day"; // RH  //Patrick Cool: to highlight the relevant agenda item
			$course_link = "<a href=\"$url\" title=\"".$item['course']."\">".$item['course']."</a>";
		} else {
			$course_link = "";
		}
		// Creating the array that will be returned. If we have week or month view we have an array with the date as the key
		// if we have a day_view we use a half hour as index => key 33 = 16h30
		if ($type !== "day_view") {
		    // This is the array construction for the WEEK or MONTH view
		    
			//Display the Agenda global in the tab agenda (administrator)
			$agendaitems[$day] .= "<i>$start_time $end_time</i>&nbsp;-&nbsp;";
			$agendaitems[$day] .= "<b>".get_lang('GlobalEvent')."</b>";
			$agendaitems[$day] .= "<div>".$item['title']."</div><br>";
		} else {
			// this is the array construction for the DAY view
			$halfhour = 2 * $agendatime['0'];
			if ($agendatime['1'] >= '30') {
				$halfhour = $halfhour +1;
			}
			if (!is_array($agendaitems[$halfhour]))
	        	$content = $agendaitems[$halfhour];
			$agendaitems[$halfhour] = $content."<div><i>$hour:$minute</i> <b>".get_lang('GlobalEvent'). ":  </b>".$item['title']."</div>";
			    
		}
	}
	return $agendaitems;
}

function display_ical_import_form() {
	echo '<div class="row"><div class="form_header">'.get_lang('ICalFileImport').'</div></div>';
	echo '<form enctype="multipart/form-data"  action="'.api_get_self().'?origin='.Security::remove_XSS($_GET['origin']).'&action='.Security::remove_XSS($_GET['action']).'" method="post" name="frm_import_ical">';
	echo '<div class="row">
				<div class="label">
					<span class="form_required">*</span> '.get_lang('ICalFileImport').'
				</div>
				<div class="formw"><input type="file" name="ical_import"/>
				</div>
			</div>';
	echo '<div class="row">
				<div class="label">
				</div>
				<div class="formw">
					<button class="save" type="submit" name="ical_submit" value="'.get_lang('Import').'">'.get_lang('Import').'</button>
				</div>
			</div>';
	echo '</form>';
}
