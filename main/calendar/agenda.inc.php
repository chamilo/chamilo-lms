<?php //$Id: agenda.inc.php 14723 2008-04-02 15:29:06Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
	@author: Patrick Cool, patrick.cool@UGent.be
	@version: 1.1
	@todo: synchronisation with the function in myagenda.php (for instance: using one function for the mini_calendar
==============================================================================
	Large parts of the code are recycled from the old agenda tool, but I
	reworked it and cleaned the code to make it more readable. The code for
	the small calender on the left is taken from the My Agenda tool.

	Reabability is also the reason why I use the if ($is_allowed_to_edit)
	check for each part of the code. I'm aware that is duplication, but
	it makes the code much easier to read.
==============================================================================
*/

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
// the variables for the days and the months
// Defining the shorts for the days
$DaysShort = array (get_lang("SundayShort"), get_lang("MondayShort"), get_lang("TuesdayShort"), get_lang("WednesdayShort"), get_lang("ThursdayShort"), get_lang("FridayShort"), get_lang("SaturdayShort"));
// Defining the days of the week to allow translation of the days
$DaysLong = array (get_lang("SundayLong"), get_lang("MondayLong"), get_lang("TuesdayLong"), get_lang("WednesdayLong"), get_lang("ThursdayLong"), get_lang("FridayLong"), get_lang("SaturdayLong"));
// Defining the months of the year to allow translation of the months
$MonthsLong = array (get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong"));

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
* Retrieves all the agenda items from the table
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @author Yannick Warnier <yannick.warnier@dokeos.com> - cleanup
* @param integer $month: the integer value of the month we are viewing
* @param integer $year: the 4-digit year indication e.g. 2005
* @return array
*/
function get_calendar_items($month, $year)
{
	global $_user, $_course;
	global $is_allowed_to_edit;

	// database variables
	$TABLEAGENDA=Database::get_course_table(TABLE_AGENDA);
	$TABLE_ITEM_PROPERTY=Database::get_course_table(TABLE_ITEM_PROPERTY);

	$group_memberships=GroupManager::get_group_ids($_course['dbName'], $_user['user_id']);

	if (is_allowed_to_edit() OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
	{
		//echo "course admin";
		// added GROUP BY agenda.id to prevent double display of a message that has been sent to two groups
		$sql="SELECT
			DISTINCT agenda.*, toolitemproperties.*
			FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
			WHERE agenda.id = toolitemproperties.ref   ".
			//$show_all_current.
			" AND MONTH(agenda.start_date)='".$month."' AND YEAR(agenda.start_date)='".$year."'
			AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
			AND toolitemproperties.visibility='1'
			GROUP BY agenda.id ".
			"ORDER BY  start_date ";
			//.$sort;
	}
	// if the user is not an administrator of that course
	else
	{
		//echo "GEEN course admin";
		if (is_array($group_memberships))
		{
			$sql="SELECT
				agenda.*, toolitemproperties.*
				FROM ".$TABLEAGENDA." agenda,	".$TABLE_ITEM_PROPERTY." toolitemproperties
				WHERE agenda.id = toolitemproperties.ref   ".
				//$show_all_current.
				" AND MONTH(agenda.start_date)='".$month."'
				AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
				AND	( toolitemproperties.to_user_id='".$_user['user_id']."' OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).") )
				AND toolitemproperties.visibility='1'"
				."ORDER BY  start_date ";
				//.$sort;
		}
		else
		{
			$sql="SELECT
				agenda.*, toolitemproperties.*
				FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
				WHERE agenda.id = toolitemproperties.ref   ".
				//$show_all_current.
				" AND MONTH(agenda.start_date)='".$month."'
				AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
				AND ( toolitemproperties.to_user_id='".$_user['user_id']."' OR toolitemproperties.to_group_id='0')
				AND toolitemproperties.visibility='1' ".
				"ORDER BY  start_date ";
				//.$sort;
		}
	}
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$data=array();
	while ($row=mysql_fetch_array($result))
	{
		$datum_item=(int)substr($row["start_date"],8,2);
		//$dag_item=date("d",strtotime($datum_item));
		$data[$datum_item][intval($datum_item)][] = $row;
	}
	return $data;
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
function display_minimonthcalendar($agendaitems, $month, $year, $monthName)
{
	global $DaysShort;
	//Handle leap year
	$numberofdays = array (0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	if (($year % 400 == 0) or ($year % 4 == 0 and $year % 100 <> 0))
		$numberofdays[2] = 29;
	//Get the first day of the month
	$dayone = getdate(mktime(0, 0, 0, $month, 1, $year));
	//Start the week on monday
	$startdayofweek = $dayone['wday'] <> 0 ? ($dayone['wday'] - 1) : 6;
	$backwardsURL = api_get_self()."?".api_get_cidreq()."&coursePath=".$_GET['coursePath']."&amp;courseCode=".$_GET['courseCode']."&amp;month=". ($month == 1 ? 12 : $month -1)."&amp;year=". ($month == 1 ? $year -1 : $year);
	$forewardsURL = api_get_self()."?".api_get_cidreq()."&coursePath=".$_GET['coursePath']."&amp;courseCode=".$_GET['courseCode']."&amp;month=". ($month == 12 ? 1 : $month +1)."&amp;year=". ($month == 12 ? $year +1 : $year);

	echo 	"<table id=\"smallcalendar\">\n",
			"<tr class=\"title\">\n",
			"<td width=\"10%\"><a href=\"", $backwardsURL, "\"> &laquo; </a></td>\n",
			"<td width=\"80%\" colspan=\"5\">", $monthName, " ", $year, "</td>\n",
			"<td width=\"10%\"><a href=\"", $forewardsURL, "\"> &raquo; </a></td>\n", "</tr>\n";

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
				$bgcolor = $ii < 5 ? $class="class=\"days_week\"" : $class="class=\"days_weekend\"";
				$dayheader = "$curday";
				if (($curday == $today['mday']) && ($year == $today['year']) && ($month == $today['mon']))
				{
					$dayheader = "$curday";
					$class = "class=\"days_today\"";
				}
				echo "\t<td ".$class.">";
				if ($agendaitems[$curday] <> "")
				{
					echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&action=view&amp;view=day&amp;day=".$curday."&amp;month=".$month."&amp;year=".$year."\">".$dayheader."</a>";
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
* show the calender of the given month
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @param integer $month: the integer value of the month we are viewing
* @param integer $year: the 4-digit year indication e.g. 2005
* @return html code
*/
function display_monthcalendar($month, $year)
{
	global $MonthsLong;
	global $DaysShort;
	global $origin;

	// grabbing all the calendar items for this year and storing it in a array
	$data=get_calendar_items($month,$year);


	//Handle leap year
	$numberofdays = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
	if (($year%400 == 0) or ($year%4==0 and $year%100<>0)) $numberofdays[2] = 29;

	//Get the first day of the month
	$dayone = getdate(mktime(0,0,0,$month,1,$year));
  	//Start the week on monday
	$startdayofweek = $dayone['wday']<>0 ? ($dayone['wday']-1) : 6;

	$backwardsURL = api_get_self()."?".api_get_cidreq()."&origin=$origin&amp;month=".($month==1 ? 12 : $month-1)."&amp;year=".($month==1 ? $year-1 : $year);
	$forewardsURL = api_get_self()."?".api_get_cidreq()."&origin=$origin&amp;month=".($month==12 ? 1 : $month+1)."&amp;year=".($month==12 ? $year+1 : $year);

	   $maand_array_maandnummer=$month-1;

	echo "<table id=\"smallcalendar\">\n",
		"<tr class=\"title\">\n",
		"<td width=\"10%\"><a href=\"",$backwardsURL,"\"> &laquo; </a></td>\n",
		"<td width=\"80%\" colspan=\"5\">",$MonthsLong[$maand_array_maandnummer]," ",$year,"</td>\n",
		"<td width=\"10%\"><a href=\"",$forewardsURL,"\"> &raquo; </a></td>\n",
		"</tr>\n";

	echo "<tr>\n";

	for ($ii=1;$ii<8; $ii++)
	{
	echo "<td class=\"weekdays\" width=\"14%\">",$DaysShort[$ii%7],"</td>\n";
  }

	echo "</tr>\n";
	$curday = -1;
	$today = getdate();
	while ($curday <=$numberofdays[$month])
  	{
	echo "<tr>\n";
    	for ($ii=0; $ii<7; $ii++)
	  	{
	  		if (($curday == -1)&&($ii==$startdayofweek))
			{
	    		$curday = 1;
			}
			if (($curday>0)&&($curday<=$numberofdays[$month]))
			{
				$bgcolor = $ii<5 ? "class=\"alternativeBgLight\"" : "class=\"alternativeBgDark\"";

				$dayheader = "$curday";
				if (key_exists($curday,$data))
				{
					$dayheader="<a href='".api_get_self()."?".api_get_cidreq()."&amp;view=list&amp;origin=$origin&amp;month=$month&amp;year=$year&amp;day=$curday#$curday'>".$curday."</a>"; 
					foreach ($data[$curday] as $key=>$agenda_item)
					{
						foreach ($agenda_item as $key=>$value)
						{
							$dayheader .= '<br /><b>'.substr($value['start_date'],11,8).'</b>';	
							$dayheader .= ' - ';
							$dayheader .= $value['title'];
						}
					}
				}

				if (($curday==$today['mday'])&&($year ==$today['year'])&&($month == $today['mon']))
				{
			echo "<td id=\"today\" ",$bgcolor,"\">".$dayheader." \n";
      }
				else
				{
			echo "<td id=\"days\" ",$bgcolor,"\">".$dayheader." \n";
				}
			echo "</td>\n";

	      		$curday++;
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
* returns all the javascript that is required for easily selecting the target people/groups this goes into the $htmlHeadXtra[] array
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return javascript code
*/
function to_javascript()
{
$Send2All=get_lang("Send2All");


return "<script type=\"text/javascript\" language=\"JavaScript\">

<!-- Begin javascript menu swapper

function move(fbox,	tbox)
{
	var	arrFbox	= new Array();
	var	arrTbox	= new Array();
	var	arrLookup =	new	Array();

	var	i;
	for	(i = 0;	i <	tbox.options.length; i++)
	{
		arrLookup[tbox.options[i].text]	= tbox.options[i].value;
		arrTbox[i] = tbox.options[i].text;
	}

	var	fLength	= 0;
	var	tLength	= arrTbox.length;

	for(i =	0; i < fbox.options.length;	i++)
	{
		arrLookup[fbox.options[i].text]	= fbox.options[i].value;

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

	arrFbox.sort();
	arrTbox.sort();
	fbox.length	= 0;
	tbox.length	= 0;

	var	c;
	for(c =	0; c < arrFbox.length; c++)
	{
		var	no = new Option();
		no.value = arrLookup[arrFbox[c]];
		no.text	= arrFbox[c];
		fbox[c]	= no;
	}
	for(c =	0; c < arrTbox.length; c++)
	{
		var	no = new Option();
		no.value = arrLookup[arrTbox[c]];
		no.text	= arrTbox[c];
		tbox[c]	= no;
	}
}

function validate()
{
	var	f =	document.new_calendar_item;
	f.submit();
	return true;
}

function selectAll(cbList,bSelect,showwarning)
{
	if (cbList.length <	1) {
		alert(\"$Send2All\");
		return;
	}
	for	(var i=0; i<cbList.length; i++)
		cbList[i].selected = cbList[i].checked = bSelect
}

function reverseAll(cbList)
{
	for	(var i=0; i<cbList.length; i++)
	{
		cbList[i].checked  = !(cbList[i].checked)
		cbList[i].selected = !(cbList[i].selected)
	}
}
//	End	-->
</script>";
}


/**
* returns the javascript for setting a filter. This is a jump menu
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return javascript code
*/
function user_group_filter_javascript()
{
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
function get_course_users()
{
global $tbl_user;
global $tbl_courseUser, $tbl_session_course_user;
global $_cid;

// not 100% if this is necessary, this however prevents a notice
if (!isset($courseadmin_filter))
	{$courseadmin_filter='';}

$sql = "SELECT u.user_id uid, u.lastname lastName, u.firstname firstName
		FROM $tbl_user as u, $tbl_courseUser as cu
		WHERE cu.course_code = '".$_cid."'
			AND cu.user_id = u.user_id $courseadmin_filter
		ORDER BY u.lastname, u.firstname";
$result = api_sql_query($sql,__FILE__,__LINE__);
while($user=mysql_fetch_array($result)){
	$users[$user[0]] = $user;
}

if(!empty($_SESSION['id_session'])){
	$sql = "SELECT u.user_id uid, u.lastname lastName, u.firstName firstName
			FROM $tbl_session_course_user AS session_course_user
			INNER JOIN $tbl_user u
				ON u.user_id = session_course_user.id_user
			WHERE id_session='".$_SESSION['id_session']."'
			AND course_code='$_cid'";

	$result = api_sql_query($sql,__FILE__,__LINE__);
	while($user=mysql_fetch_array($result)){
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
function get_course_groups()
{
	global $tbl_group;
	global $tbl_groupUser;
	$group_list = array();
	
	$sql = "SELECT g.id, g.name, COUNT(gu.id) userNb
			        FROM ".$tbl_group." AS g LEFT JOIN ".$tbl_groupUser." gu
			        ON g.id = gu.group_id
			        GROUP BY g.id";
	
	$result = api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
	while ($group_data = mysql_fetch_array($result))
	{
		$group_list [$group_data['id']] = $group_data;
	}
	return $group_list;
}


/**
* this function shows the form for sending a message to a specific group or user.
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return html code
*/
function show_to_form($to_already_selected)
{
$user_list=get_course_users();
$group_list=get_course_groups();

echo "\n<table id=\"recipient_list\" style=\"display: none;\">\n";
	echo "\t<tr>\n";
	// the form containing all the groups and all the users of the course
	echo "\t\t<td>\n";
		construct_not_selected_select_form($group_list,$user_list,$to_already_selected);
	echo "\t\t</td>\n";
	// the buttons for adding or removing groups/users
	echo "\n\t\t<td valign=\"middle\">\n";
	echo "\t\t<input type=\"button\" ",
				"onclick=\"move(this.form.elements[2],this.form.elements[5])\" ",
				"value=\"   &gt;&gt;   \" />",

				"\n\t\t<p>&nbsp;</p>",

				"\n\t\t<input type=\"button\" ",
				"onclick=\"move(this.form.elements[5],this.form.elements[2])\" ",
				"value=\"   &lt;&lt;   \" />";
	echo "\t\t</td>\n";
	echo "\n\t\t<td>\n";
		construct_selected_select_form($group_list,$user_list,$to_already_selected);
	echo "\t\t</td>\n";
	echo "\t</tr>\n";
echo "</table>";
}


/**
* this function shows the form with the user that were not selected
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return html code
*/
function construct_not_selected_select_form($group_list=null, $user_list=null,$to_already_selected=array())
{
	echo "\t\t<select name=\"not_selected_form[]\" size=\"5\" multiple=\"multiple\" style=\"width:200px\">\n";

	// adding the groups to the select form
	if (is_array($group_list))
	{
		foreach($group_list as $this_group)
		{
			//api_display_normal_message("group " . $thisGroup[id] . $thisGroup[name]);
			if (!is_array($to_already_selected) || !in_array("GROUP:".$this_group['id'],$to_already_selected)) // $to_already_selected is the array containing the groups (and users) that are already selected
				{
				echo	"\t\t<option value=\"GROUP:".$this_group['id']."\">",
					"G: ",$this_group['name']," &ndash; " . $this_group['userNb'] . " " . get_lang('Users') .
					"</option>\n";
			}
		}
		// a divider
		echo	"\t\t<option value=\"\">----------------------------------</option>\n";
	}

	// adding the individual users to the select form
	foreach($user_list as $this_user)
	{
		if (!is_array($to_already_selected) || !in_array("USER:".$this_user['uid'],$to_already_selected)) // $to_already_selected is the array containing the users (and groups) that are already selected
		{
			echo	"\t\t<option value=\"USER:",$this_user['uid'],"\">",
				"",$this_user['lastName']," ",$this_user['firstName'],
				"</option>\n";
		}
	}
	echo "\t\t</select>\n";
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
	if (is_array($to_already_selected))
	{
		 $groupuser=separate_users_groups($to_already_selected);
	}
	$groups_to_already_selected=$groupuser['groups'];
	$users_to_already_selected=$groupuser['users'];

	// we load all the groups and all the users into a reference array that we use to search the name of the group / user
	$ref_array_groups=get_course_groups();
	$ref_array_users=get_course_users();

	// we construct the form of the already selected groups / users
	echo "\t\t<select name=\"selectedform[]\" size=\"5\" multiple=\"multiple\" style=\"width:200px\">";
	if(is_array($to_already_selected))
	{
		foreach($to_already_selected as $groupuser)
		{
			list($type,$id)=explode(":",$groupuser);
			if ($type=="GROUP")
			{
				echo "\t\t<option value=\"".$groupuser."\">G: ".$ref_array_groups[$id]['name']."</option>";
			}
			else
			{
				echo "\t\t<option value=\"".$groupuser."\">".$ref_array_users[$id]['lastName']." ".$ref_array_users[$id]['firstName']."</option>";
			}
		}
	}
	echo "</select>\n";
}



/**
* This function stores the Agenda Item in the table calendar_event and updates the item_property table also
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return integer the id of the last added agenda item
*/
function store_new_agenda_item()
{
	global $_user, $_course;
	$TABLEAGENDA = Database::get_course_table(TABLE_AGENDA);
	
	// some filtering of the input data
	$title=strip_tags(trim($_POST['title'])); // no html allowed in the title
	$content=trim($_POST['content']);
	$start_date=(int)$_POST['fyear']."-".(int)$_POST['fmonth']."-".(int)$_POST['fday']." ".(int)$_POST['fhour'].":".(int)$_POST['fminute'].":00";
	$end_date=(int)$_POST['end_fyear']."-".(int)$_POST['end_fmonth']."-".(int)$_POST['end_fday']." ".(int)$_POST['end_fhour'].":".(int)$_POST['end_fminute'].":00";
	
	// store in the table calendar_event
	$sql = "INSERT INTO ".$TABLEAGENDA."
					        (title,content, start_date, end_date)
					        VALUES
					        ('".$title."','".$content."', '".$start_date."','".$end_date."')";
	
	$result = api_sql_query($sql,__FILE__,__LINE__) or die (mysql_error());
	$last_id=mysql_insert_id();
	
	// store in last_tooledit (first the groups, then the users
	$to=$_POST['selectedform'];
	
	if ((!is_null($to))or (!empty($_SESSION['toolgroup']))) // !is_null($to): when no user is selected we send it to everyone
	{
		$send_to=separate_users_groups($to);
		// storing the selected groups
		if (is_array($send_to['groups']))
		{
			foreach ($send_to['groups'] as $group)
			{
				api_item_property_update($_course, TOOL_CALENDAR_EVENT, $last_id,"AgendaAdded", $_user['user_id'], $group,'',$start_date, $end_date);
			}
		}
		// storing the selected users
		if (is_array($send_to['users']))
		{
			foreach ($send_to['users'] as $user)
			{
				api_item_property_update($_course, TOOL_CALENDAR_EVENT, $last_id,"AgendaAdded", $_user['user_id'],'',$user, $start_date,$end_date);
			}
		}
	}
	else // the message is sent to everyone, so we set the group to 0
	{
		api_item_property_update($_course, TOOL_CALENDAR_EVENT, $last_id,"AgendaAdded", $_user['user_id'], '','',$start_date,$end_date);
	}
	// storing the resources
	store_resources($_SESSION['source_type'],$last_id);
	return $last_id;
}

/**
 * Stores the given agenda item as an announcement (unlinked copy)
 * @param	integer		Agenda item's ID
 * @return	integer		New announcement item's ID
 */
function store_agenda_item_as_announcement($item_id){
	$table_agenda = Database::get_course_table(TABLE_AGENDA);
	$table_ann = Database::get_course_table(TABLE_ANNOUNCEMENT);
	//check params
	if(empty($item_id) or $item_id != strval(intval($item_id))){return -1;}
	//get the agenda item
	$sql = "SELECT * FROM $table_agenda WHERE id = '".$item_id."'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if(Database::num_rows($res)>0){
		$row = Database::fetch_array($res);
		//we have the agenda event, copy it
		//get the maximum value for display order in announcement table
		$sql_max = "SELECT MAX(display_order) FROM $table_ann";
		$res_max = api_sql_query($sql_max,__FILE__,__LINE__);
		$row_max = Database::fetch_array($res_max);
		$max = $row_max[0]+1;
		//build the announcement text
		$content = $row['start_date']." - ".$row['end_date']."\n\n".$row['content'];
		//insert announcement

		$sql_ins = "INSERT INTO $table_ann (title,content,end_date,display_order) " .
				"VALUES ('".$row['title']."','$content','".$row['end_date']."','$max')";
		$res_ins = api_sql_query($sql_ins,__FILE__,__LINE__);
		if($res > 0)
		{
			$ann_id = Database::get_last_insert_id();
			//Now also get the list of item_properties rows for this agenda_item (calendar_event)
			//and copy them into announcement item_properties
			$table_props = Database::get_course_table(TABLE_ITEM_PROPERTY);
			$sql_props = "SELECT * FROM $table_props WHERE tool = 'calendar_event' AND ref='$item_id'";
			$res_props = api_sql_query($sql_props,__FILE__,__LINE__);
			if(Database::num_rows($res_props)>0)
			{
				while($row_props = Database::fetch_array($res_props))
				{
					//insert into announcement item_property
					$time = date("Y-m-d H:i:s", time());
					$sql_ins_props = "INSERT INTO $table_props " .
							"(tool, insert_user_id, insert_date, " .
							"lastedit_date, ref, lastedit_type," .
							"lastedit_user_id, to_group_id, to_user_id, " .
							"visibility, start_visible, end_visible)" .
							" VALUES " .
							"('announcement','".$row_props['insert_user_id']."','".$time."'," .
							"'$time','$ann_id','AnnouncementAdded'," .
							"'".$row_props['last_edit_user_id']."','".$row_props['to_group_id']."','".$row_props['to_user_id']."'," .
							"'".$row_props['visibility']."','".$row_props['start_visible']."','".$row_props['end_visible']."')";
					$res_ins_props = api_sql_query($sql_ins_props,__FILE__,__LINE__);
					if($res_ins_props <= 0){
						error_log('SQL Error in '.__FILE__.' at line '.__LINE__.': '.$sql_ins_props);
					}else{
						//copy was a success
						return $ann_id;
					}
				}
			}
		}else{
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
function separate_users_groups($to)
{
foreach($to as $to_item)
	{
	list($type, $id) = explode(':', $to_item);

	switch($type)
		{
		case 'GROUP':
			$grouplist[] =$id;
			break;
		case 'USER':
			$userlist[] =$id;
			break;
		}
	}
$send_to['groups']=$grouplist;
$send_to['users']=$userlist;
return $send_to;
}



/**
* returns all the users and all the groups a specific Agenda item has been sent to
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @return array
*/
function sent_to($tool, $id)
{
global $_course;
$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

$sql="SELECT * FROM $TABLE_ITEM_PROPERTY WHERE tool='".$tool."' AND ref='".$id."'";
$result=api_sql_query($sql,__FILE__,__LINE__);
while ($row=mysql_fetch_array($result))
	{
	// if to_group_id is null then it is sent to a specific user
	// if to_group_id = 0 then it is sent to everybody
	if (!is_null($row['to_group_id']) )
		{
		$sent_to_group[]=$row['to_group_id'];
		//echo $row['to_group_id'];
		}
	// if to_user_id <> 0 then it is sent to a specific user
	if ($row['to_user_id']<>0)
		{
		$sent_to_user[]=$row['to_user_id'];
		}
	}
if (isset($sent_to_group))
	{
	$sent_to['groups']=$sent_to_group;
	}
if (isset($sent_to_user))
	{
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
function sent_to_form($sent_to_array)
{
// we find all the names of the groups
$group_names=get_course_groups();

count($sent_to_array);

// we count the number of users and the number of groups
if (isset($sent_to_array['users']))
	{
	$number_users=count($sent_to_array['users']);
	}
else
	{
	$number_users=0;
	}
if (isset($sent_to_array['groups']))
	{
	$number_groups=count($sent_to_array['groups']);
	}
else
	{
	$number_groups=0;
	}
$total_numbers=$number_users+$number_groups;

// starting the form if there is more than one user/group
if ($total_numbers >1)
	{
	$output="<select name=\"sent to\">\n";
	$output.="<option>".get_lang("SentTo")."</option>";
	// outputting the name of the groups
	if (is_array($sent_to_array['groups']))
		{
		foreach ($sent_to_array['groups'] as $group_id)
			{
			$output.="\t<option value=\"\">G: ".$group_names[$group_id]['name']."</option>\n";
			}
		}
	if (isset($sent_to_array['users']))
	{
		if (is_array($sent_to_array['users']))
			{
			foreach ($sent_to_array['users'] as $user_id)
				{
				$user_info=api_get_user_info($user_id);
				$output.="\t<option value=\"\">".$user_info['firstName']." ".$user_info['lastName']."</option>\n";
				}
			}
	}

	// ending the form
	$output.="</select>\n";
	}
else // there is only one user/group
	{
	if (is_array($sent_to_array['users']))
		{
		$user_info=api_get_user_info($sent_to_array['users'][0]);
		echo $user_info['firstName']." ".$user_info['lastName'];
		}
	if (is_array($sent_to_array['groups']) and $sent_to_array['groups'][0]!==0)
		{
		$group_id=$sent_to_array['groups'][0];
		echo $group_names[$group_id]['name'];
		}
	if (is_array($sent_to_array['groups']) and $sent_to_array['groups'][0]==0)
		{
		echo get_lang("Everybody");
		}
	//.$sent_to_array['groups'][0];
	}

echo $output;
}


/**
* This function displays a dropdown list that allows the course administrator do view the calendar items of one specific group
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function show_group_filter_form()
{
$group_list=get_course_groups();

echo "<select name=\"select\" onchange=\"MM_jumpMenu('parent',this,0)\">";
echo "<option value=\"agenda.php?group=none\">show all groups</option>";
foreach($group_list as $this_group)
	{
	// echo "<option value=\"agenda.php?isStudentView=true&amp;group=".$this_group['id']."\">".$this_group['name']."</option>";
	echo "<option value=\"agenda.php?group=".$this_group['id']."\" ";
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

echo "<select name=\"select\" onchange=\"MM_jumpMenu('parent',this,0)\">";
echo "<option value=\"agenda.php?user=none\">show all users</option>";
foreach($user_list as $this_user)
	{
	// echo "<option value=\"agenda.php?isStudentView=true&amp;user=".$this_user['uid']."\">".$this_user['lastName']." ".$this_user['firstName']."</option>";
	echo "<option value=\"agenda.php?user=".$this_user['uid']."\" ";
	echo ($this_user['uid']==$_SESSION['user'])? " selected":"" ;
	echo ">".$this_user['lastName']." ".$this_user['firstName']."</option>";
	}
echo "</select>";
}



/**
* This function displays a dropdown list that allows the course administrator do view the calendar items of one specific group
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function show_user_group_filter_form()
{
	echo "\n<select name=\"select\" onchange=\"MM_jumpMenu('parent',this,0)\">";
	echo "\n\t<option value=\"agenda.php?user=none\">".get_lang("ShowAll")."</option>";

	// Groups
	echo "\n\t<optgroup label=\"".get_lang("Groups")."\">";
	$group_list=get_course_groups();
	foreach($group_list as $this_group)
	{
		// echo "<option value=\"agenda.php?isStudentView=true&amp;group=".$this_group['id']."\">".$this_group['name']."</option>";
		echo "\n\t\t<option value=\"agenda.php?group=".$this_group['id']."\" ";
		echo ($this_group['id']==$_SESSION['group'])? " selected":"" ;
		echo ">".$this_group['name']."</option>";
	}
	echo "\n\t</optgroup>";

	// Users
	echo "\n\t<optgroup label=\"".get_lang("Users")."\">";
	$user_list=get_course_users();
	foreach($user_list as $this_user)
		{
		// echo "<option value=\"agenda.php?isStudentView=true&amp;user=".$this_user['uid']."\">".$this_user['lastName']." ".$this_user['firstName']."</option>";
		echo "\n\t\t<option value=\"agenda.php?user=".$this_user['uid']."\" ";
		echo ($this_user['uid']==$_SESSION['user'])? " selected":"" ;
		echo ">".$this_user['lastName']." ".$this_user['firstName']."</option>";
		}
	echo "\n\t</optgroup>";
	echo "</select>";
}



/**
* This tools loads all the users and all the groups who have received a specific item (in this case an agenda item)
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function load_edit_users($tool, $id)
{
global $_course;
$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

$sql="SELECT * FROM $TABLE_ITEM_PROPERTY WHERE tool='$tool' AND ref='$id'";
$result=api_sql_query($sql,__FILE__,__LINE__) or die (mysql_error());
while ($row=mysql_fetch_array($result))
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
function change_visibility($tool,$id)
{
	global $_course;
	$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

	$sql="SELECT * FROM $TABLE_ITEM_PROPERTY WHERE tool='".TOOL_CALENDAR_EVENT."' AND ref='$id'";
	$result=api_sql_query($sql,__FILE__,__LINE__) or die (mysql_error());
	$row=mysql_fetch_array($result);

	if ($row['visibility']=='1')
	{
		$sql_visibility="UPDATE $TABLE_ITEM_PROPERTY SET visibility='0' WHERE tool='$tool' AND ref='$id'";
		api_item_property_update($_course,TOOL_CALENDAR_EVENT,$id,"invisible",api_get_user_id());
	}
	else
	{
		$sql_visibility="UPDATE $TABLE_ITEM_PROPERTY SET visibility='1' WHERE tool='$tool' AND ref='$id'";
		api_item_property_update($_course,TOOL_CALENDAR_EVENT,$id,"visible",api_get_user_id());
	}
}



/**
* The links that allows the course administrator to add a new agenda item, to filter on groups or users
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function display_courseadmin_links()
{
	echo "<li><a href='".api_get_self()."?".api_get_cidreq()."&action=add&amp;origin=".$_GET['origin']."'><img src=\"../img/view_more_stats.gif\" alt=\"".get_lang('MoreStats')."\" border=\"0\" /> ".get_lang("AgendaAdd")."</a><br /></li>";
	if (empty ($_SESSION['toolgroup']))
	{
		echo "<li>".get_lang('UserGroupFilter')."<br/>";
		echo "<form name=\"filter\">";
		show_user_group_filter_form();
		echo "</form>";
	}
	echo "</li>";
}



/**
* The links that allows the student AND course administrator to show all agenda items and sort up/down
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function display_student_links()
{
	global $show;
	if ($_SESSION['sort'] == 'DESC')
	{
		echo "<li><a href='".api_get_self()."?".api_get_cidreq()."&sort=asc&amp;origin=".$_GET['origin']."'>".Display::return_icon('calendar_up.gif',get_lang('AgendaSortChronologicallyUp')).' '.get_lang("AgendaSortChronologicallyUp")."</a></li>";
	}
	else
	{
		echo "<li><a href='".api_get_self()."?".api_get_cidreq()."&sort=desc&amp;origin=".$_GET['origin']."'>".Display::return_icon('calendar_down.gif',get_lang('AgendaSortChronologicallyDown')).' '.get_lang("AgendaSortChronologicallyDown")."</a></li>";
	}

	// showing the link to show all items or only those of the current month
	if ($_SESSION['show']=="showcurrent")
	{
		echo "<li><a href='".api_get_self()."?".api_get_cidreq()."&action=showall&amp;origin=".$_GET['origin']."'>".Display::return_icon('calendar_select.gif').' '.get_lang("ShowAll")."</a></li>";
	}
	else
	{
		echo "<li><a href='".api_get_self()."?".api_get_cidreq()."&action=showcurrent&amp;origin=".$_GET['origin']."'>".Display::return_icon('calendar_month.gif').' '.get_lang("ShowCurrent")."</a></li>";
	}
	
	if ($_SESSION['view'] <> 'month')
	{
		echo "\t<li><a href=\"".api_get_self()."?action=view&amp;view=month\"><img src=\"../img/calendar_month.gif\" border=\"0\" alt=\"".get_lang('MonthView')."\" /> ".get_lang('MonthView')."</a></li>\n";
	}
	else 
	{
		echo "\t<li><a href=\"".api_get_self()."?action=view&amp;view=list\"><img src=\"../img/calendar_select.gif\" border=\"0\" alt=\"".get_lang('ListView')."\" /> ".get_lang('ListView')."</a></li>\n";
	}
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
	$id=(int)addslashes($_GET['id']);
	$sql 					= "SELECT * FROM ".$TABLEAGENDA." WHERE id='".$id."'";
	$result					= api_sql_query($sql,__FILE__,__LINE__);
	$entry_to_edit 			= mysql_fetch_array($result);
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
	//echo "<br />IN get_agenda_item".$_SESSION['allow_individual_calendar'];
	return $item;
}
/**
* This is the function that updates an agenda item. It does 3 things
* 1. storethe start_date, end_date, title and message in the calendar_event table
* 2. store the groups/users who this message is meant for in the item_property table
* 3. modify the attachments (if needed)
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function store_edited_agenda_item()
{
	global $_user, $_course;

	// database definitions
	$TABLE_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);

	// STEP 1: editing the calendar_event table
	// 1.a.  some filtering of the input data
	$id=(int)$_POST['id'];
	$title=strip_tags(trim($_POST['title'])); // no html allowed in the title
	$content=trim($_POST['content']);
	$start_date=(int)$_POST['fyear']."-".(int)$_POST['fmonth']."-".(int)$_POST['fday']." ".(int)$_POST['fhour'].":".(int)$_POST['fminute'].":00";
	$end_date=(int)$_POST['end_fyear']."-".(int)$_POST['end_fmonth']."-".(int)$_POST['end_fday']." ".(int)$_POST['end_fhour'].":".(int)$_POST['end_fminute'].":00";
	$to=$_POST['selectedform'];
	// 1.b. the actual saving in calendar_event table
	$edit_result=save_edit_agenda_item($id,$title,$content,$start_date,$end_date);

	// step 2: editing the item_propery table (=delete all and add the new destination users/groups)
	if ($edit_result=true)
	{
		// 2.a. delete everything for the users
		$sql_delete="DELETE FROM ".$TABLE_ITEM_PROPERTY." WHERE ref='$id' AND tool='".TOOL_CALENDAR_EVENT."'";

		$result = api_sql_query($sql_delete,__FILE__,__LINE__) or die (mysql_error());
		// 2.b. storing the new users/groups
		if (!is_null($to)) // !is_null($to): when no user is selected we send it to everyone
		{
			$send_to=separate_users_groups($to);
			// storing the selected groups
			if (is_array($send_to['groups']))
			{
				foreach ($send_to['groups'] as $group)
				{
					api_item_property_update($_course, TOOL_CALENDAR_EVENT, $id,"AgendaModified", $_user['user_id'], $group,'',$start_date, $end_date);
				}
			}
			// storing the selected users
			if (is_array($send_to['users']))
			{
				foreach ($send_to['users'] as $user)
				{
					api_item_property_update($_course, TOOL_CALENDAR_EVENT, $id,"AgendaModified", $_user['user_id'],'',$user, $start_date,$end_date);
				}
			}
		}
		else // the message is sent to everyone, so we set the group to 0
		{
			api_item_property_update($_course, TOOL_CALENDAR_EVENT, $id,"AgendaModified", $_user['user_id'], '','',$start_date,$end_date);
		}

	} //if ($edit_result=true)

	// step 3: update the attachments (=delete all and add those in the session
	update_added_resources("Agenda", $id);

	// return the message;
	Display::display_normal_message(get_lang("EditSuccess"));

}

/**
* This function stores the Agenda Item in the table calendar_event and updates the item_property table also (after an edit)
* @author: Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function save_edit_agenda_item($id,$title,$content,$start_date,$end_date)
{
	$TABLEAGENDA 		= Database::get_course_table(TABLE_AGENDA);

	// store the modifications in the table calendar_event
	$sql = "UPDATE ".$TABLEAGENDA."
								SET title='".$title."',
									content='".$content."',
									start_date='".$start_date."',
									end_date='".$end_date."'
								WHERE id='".$id."'";
	$result = api_sql_query($sql,__FILE__,__LINE__) or die (mysql_error());
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
function delete_agenda_item($id)
{
	global $_course;
	if (is_allowed_to_edit()  OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
		{
		if (isset($_GET['id'])&&$_GET['id']&&isset($_GET['action'])&&$_GET['action']=="delete")
			{
			//$sql = "DELETE FROM ".$TABLEAGENDA." WHERE id='$id'";
			//$sql= "UPDATE ".$TABLE_ITEM_PROPERTY." SET visibility='2' WHERE tool='Agenda' and ref='$id'";
			//$result = api_sql_query($sql,__FILE__,__LINE__) or die (mysql_error());
			$id=(int)addslashes($_GET['id']);
			api_item_property_update($_course,TOOL_CALENDAR_EVENT,$id,"delete");

			// delete the resources that were added to this agenda item
			// 2DO: as we no longer fysically delete the agenda item (to make it possible to 'restore'
			//		deleted items, we should not delete the added resources either.
			// delete_added_resource("Agenda", $id); // -> this is no longer needed as the message is not really deleted but only visibility=2 (only platform admin can see it)

			//resetting the $id;
			$id=null;

			// displaying the result message in the yellow box
			Display::display_normal_message(get_lang("AgendaDeleteSuccess"));
			}	  // if (isset($id)&&$id&&isset($action)&&$action=="delete")
		} // if ($is_allowed_to_edit)

}
/**
* Makes an agenda item visible or invisible for a student
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @param integer id the id of the agenda item we are changing the visibility of
*/
function showhide_agenda_item($id)
{
	global $nameTools;
	/*==================================================
				SHOW / HIDE A CALENDAR ITEM
	  ==================================================*/
	//  and $_GET['isStudentView']<>"false" is added to prevent that the visibility is changed after you do the following:
	// change visibility -> studentview -> course manager view
	if ((is_allowed_to_edit() OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous())) and $_GET['isStudentView']<>"false")
	{
		if (isset($_GET['id'])&&$_GET['id']&&isset($_GET['action'])&&$_GET['action']=="showhide")
		{
			$id=(int)addslashes($_GET['id']);
			change_visibility($nameTools,$id);
			Display::display_normal_message(get_lang("VisibilityChanged"));
		}
	}
}
/**
* Displays all the agenda items
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
* @author Yannick Warnier <yannick.warnier@dokeos.com> - cleanup
*/
function display_agenda_items()
{
	global $TABLEAGENDA;
	global $TABLE_ITEM_PROPERTY;
	global $select_month, $select_year;
	global $DaysShort, $DaysLong, $MonthsLong;
	global $is_courseAdmin;
	global $dateFormatLong, $timeNoSecFormat,$charset, $_user, $_course;

	// getting the group memberships
	$group_memberships=GroupManager::get_group_ids($_course['dbName'],$_user['user_id']);

	// getting the name of the groups
	$group_names=get_course_groups();

	/*--------------------------------------------------
			CONSTRUCT THE SQL STATEMENT
	  --------------------------------------------------*/

	// this is to make a difference between showing everything (all months) or only the current month)
	// $show_all_current is a part of the sql statement
	if ($_SESSION['show']!=="showall")
	{
		$show_all_current=" AND MONTH(start_date)=$select_month AND year(start_date)=$select_year";
	}
	else
	{
		$show_all_current="";
	}

	// by default we use the id of the current user. The course administrator can see the agenda of other users by using the user / group filter
	$user_id=$_user['user_id'];
	if ($_SESSION['user']!==null)
	{
		$user_id=$_SESSION['user'];
	}
	if ($_SESSION['group']!==null)
	{
		$group_id=$_SESSION['group'];
	}
	if ($_SESSION['toolgroup']!==null)
	{
		$group_id=$_SESSION['toolgroup'];
	}
	//echo "user:".$_SESSION['user']."group: ".$_SESSION['group'];
	// A. you are a course admin
	//if ($is_courseAdmin)
	if (is_allowed_to_edit() OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
	{
		// A.1. you are a course admin with a USER filter
		// => see only the messages of this specific user + the messages of the group (s)he is member of.
		if (!empty($_SESSION['user']))
		{
			$group_memberships=GroupManager::get_group_ids($_course['dbName'],$_SESSION['user']);
			if (is_array($group_memberships))
			{
				$sql="SELECT
					agenda.*, toolitemproperties.*
					FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref   ".$show_all_current."
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND	( toolitemproperties.to_user_id=$user_id OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).") )
					AND toolitemproperties.visibility='1'
					ORDER BY start_date ".$_SESSION['sort'];
			}
			else
			{
				$sql="SELECT
					agenda.*, toolitemproperties.*
					FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref   ".$show_all_current."
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND ( toolitemproperties.to_user_id=$user_id OR toolitemproperties.to_group_id='0')
					AND toolitemproperties.visibility='1'
					ORDER BY start_date ".$_SESSION['sort'];
			}
		}
		// A.2. you are a course admin with a GROUP filter
		// => see only the messages of this specific group
		elseif (!empty($_SESSION['group']))
		{
			$sql="SELECT
				agenda.*, toolitemproperties.*
				FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
				WHERE agenda.id = toolitemproperties.ref  ".$show_all_current."
				AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
				AND ( toolitemproperties.to_group_id=$group_id OR toolitemproperties.to_group_id='0')
				AND toolitemproperties.visibility='1'
				GROUP BY toolitemproperties.ref
				ORDER BY start_date ".$_SESSION['sort'];
		}
		// A.3 you are a course admin without any group or user filter
		else
		{
			// A.3.a you are a course admin without user or group filter but WITH studentview
			// => see all the messages of all the users and groups without editing possibilities
			if ($_GET['isStudentView']=='true')
			{
				$sql="SELECT
					agenda.*, toolitemproperties.*
					FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref  ".$show_all_current."
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND toolitemproperties.visibility='1'
					GROUP BY toolitemproperties.ref
					ORDER BY start_date ".$_SESSION['sort'];

			}
			// A.3.b you are a course admin without user or group filter and WTIHOUT studentview (= the normal course admin view)
			// => see all the messages of all the users and groups with editing possibilities
			else
			{
				$sql="SELECT
					agenda.*, toolitemproperties.*
					FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref  ".$show_all_current."
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND ( toolitemproperties.visibility='0' or toolitemproperties.visibility='1')
					GROUP BY toolitemproperties.ref
					ORDER BY start_date ".$_SESSION['sort'];
			}
		}

	} //if (is_allowed_to_edit() OR( api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))

	// B. you are a student
	else
	{
		if (is_array($group_memberships))
		{
			$sql="SELECT
				agenda.*, toolitemproperties.*
				FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
				WHERE agenda.id = toolitemproperties.ref   ".$show_all_current."
				AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
				AND	( toolitemproperties.to_user_id=$user_id OR toolitemproperties.to_group_id IN (0, ".implode(", ", $group_memberships).") )
				AND toolitemproperties.visibility='1'
				ORDER BY start_date ".$_SESSION['sort'];
		}
		else
		{
			if ($_user['user_id'])
			{
				$sql="SELECT
					agenda.*, toolitemproperties.*
					FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref   ".$show_all_current."
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND ( toolitemproperties.to_user_id=$user_id OR toolitemproperties.to_group_id='0')
					AND toolitemproperties.visibility='1'
					ORDER BY start_date ".$_SESSION['sort'];
			}
			else
			{
				$sql="SELECT
					agenda.*, toolitemproperties.*
					FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref   ".$show_all_current."
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND toolitemproperties.to_group_id='0'
					AND toolitemproperties.visibility='1'
					ORDER BY start_date ".$_SESSION['sort'];
			}
		}
	} // you are a student

	//echo "<pre>".$sql."</pre>";
	$result=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
	$number_items=mysql_num_rows($result);


	/*--------------------------------------------------
			DISPLAY: NO ITEMS
	  --------------------------------------------------*/
	if ($number_items==0)
		{
		 echo "<table id=\"agenda_list\" ><tr><td>".get_lang("NoAgendaItems")."</td></tr></table>";
		}

	/*--------------------------------------------------
			DISPLAY: THE ITEMS
	  --------------------------------------------------*/

	$month_bar="";
	$event_list="";
	$counter=0;
	$export_icon = api_get_path('WEB_IMG_PATH').'export.png';
	$export_icon_low = api_get_path('WEB_IMG_PATH').'export_low_fade.png';
	$export_icon_high = api_get_path('WEB_IMG_PATH').'export_high_fade.png';
	while ($myrow=mysql_fetch_array($result))
{
	echo "<table class=\"data_table\">\n";
/*--------------------------------------------------
		display: the month bar
  --------------------------------------------------*/
// Make the month bar appear only once.
	if ($month_bar != date("m",strtotime($myrow["start_date"])).date("Y",strtotime($myrow["start_date"])))
		{
		$month_bar = date("m",strtotime($myrow["start_date"])).date("Y",strtotime($myrow["start_date"]));
			echo "\t<tr>\n\t\t<td class=\"agenda_month_divider\" colspan=\"3\" valign=\"top\">".
			ucfirst(format_locale_date("%B %Y",strtotime($myrow["start_date"]))).
			"</td>\n\t</tr>\n";
		}

/*--------------------------------------------------
 display: the icon, title, destinees of the item
  --------------------------------------------------*/
	echo '<tr>';

	// highlight: if a date in the small calendar is clicked we highlight the relevant items
	$db_date=(int)date("d",strtotime($myrow["start_date"])).date("n",strtotime($myrow["start_date"])).date("Y",strtotime($myrow["start_date"]));
	if ($_GET["day"].$_GET["month"].$_GET["year"] <>$db_date)
	{
		if ($myrow['visibility']=='0')
		{
			$style="data_hidden";
			$stylenotbold="datanotbold_hidden";
			$text_style="text_hidden";
		}
		else
		{
			$style="data";
			$stylenotbold="datanotbold";
			$text_style="text";
		}

	}
	else
	{
		$style="datanow";
		$stylenotbold="datanotboldnow";
		$text_style="textnow";
	}



	echo "\t\t<th>\n";

	// adding an internal anchor
	echo "\t\t\t<a name=\"".(int)date("d",strtotime($myrow["start_date"]))."\"></a>";

	// the icons. If the message is sent to one or more specific users/groups
	// we add the groups icon
	// 2do: if it is sent to groups we display the group icon, if it is sent to a user we show the user icon
	Display::display_icon('agenda.gif', get_lang('Agenda'));
	if ($myrow['to_group_id']!=='0')
	{
		echo "<img src=\"../img/group.gif\" border=\"0\" alt=\"".get_lang('Group')."\"/>";
	}
	echo " ".$myrow['title']."\n";
	echo "\t\t</th>\n";

	// the message has been sent to
	echo "\t\t<th>".get_lang("SentTo").": ";
	$sent_to=sent_to(TOOL_CALENDAR_EVENT, $myrow["ref"]);
	$sent_to_form=sent_to_form($sent_to);
	echo $sent_to_form;
	echo "</th>";

	if (is_allowed_to_edit() OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
	{
		echo '<th>'.get_lang('Modify');	
		echo '</th></tr>';
	}
	

/*--------------------------------------------------
 			display: the title
  --------------------------------------------------*/
	echo "<tr class='row_odd'>";
	echo "\t\t<td>".get_lang("StartTimeWindow").": ";
	echo ucfirst(format_locale_date($dateFormatLong,strtotime($myrow["start_date"])))."&nbsp;&nbsp;&nbsp;";
	echo ucfirst(strftime($timeNoSecFormat,strtotime($myrow["start_date"])))."";
	echo "</td>\n";
	echo "\t\t<td>";
	if ($myrow["end_date"]<>"0000-00-00 00:00:00")
	{
		echo get_lang("EndTimeWindow").": ";
		echo ucfirst(format_locale_date($dateFormatLong,strtotime($myrow["end_date"])))."&nbsp;&nbsp;&nbsp;";
		echo ucfirst(strftime($timeNoSecFormat,strtotime($myrow["end_date"])))."";
	}
	echo "</td>\n";
	
/*--------------------------------------------------
	display: edit delete button (course admin only)
  --------------------------------------------------*/

	
	if (is_allowed_to_edit() OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
	{
		echo '<td align="center">';
		// edit
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$_GET['origin'].'&amp;action=edit&amp;id='.$myrow['id'].'" title="'.get_lang("ModifyCalendarItem").'">';
		echo "<img src=\"../img/edit.gif\" border=\"0\" alt=\"".get_lang("ModifyCalendarItem")."\" /></a>";
		
		echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&origin=".$_GET['origin']."&amp;action=delete&amp;id=".$myrow['id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."')) return false;\"  title=\"".get_lang("Delete")."\">";
		echo "<img src=\"../img/delete.gif\" border=\"0\" alt=\"".get_lang("Delete")."\"/></a>";
		 	 
		echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$_GET['origin'].'&amp;action=announce&amp;id='.$myrow['id'].'" title="'.get_lang("AddAnnouncement").'">';				
		echo "<img src=\"../img/announce_add.gif\" border=\"0\" alt=\"".get_lang("AddAnnouncement")."\"/></a>";
		if ($myrow['visibility']==1)
		{
			$image_visibility="visible";
		}
		else
		{
			$image_visibility="invisible";
		}
		echo 	'<a href="'.api_get_self().'?'.api_get_cidreq().'&origin='.$_GET['origin'].'&amp;action=showhide&amp;id='.$myrow['id'].'" title="'.get_lang("langVisible").'">',
				"<img src=\"../img/".$image_visibility.".gif\" border=\"0\" alt=\"".get_lang("Visible")."\" /></a>";
		echo '</td>';	
	}
	echo '</tr>';

echo '<tr class="row_even">';
	
	if (is_allowed_to_edit() OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
	{
		$td_colspan= '<td colspan="3">';
	}
	else
	{
		$td_colspan= '<td colspan="2">';
	}
	
	
/*--------------------------------------------------
 			display: the content
  --------------------------------------------------*/
	$content = $myrow['content'];
	$content = make_clickable($content);
	$content = text_filter($content);
	echo "<tr class='row_even'>";
	echo $td_colspan;	
	echo '<a class="ical_export" href="ical_export.php?'.api_get_cidreq().'&type=course&id='.$myrow['id'].'&class=confidential" title="'.get_lang('ExportiCalConfidential').'"><img src="'.$export_icon_high.'" alt="'.get_lang('ExportiCalConfidential').'"/></a>';
	echo '<a class="ical_export" href="ical_export.php?'.api_get_cidreq().'&type=course&id='.$myrow['id'].'&class=private" title="'.get_lang('ExportiCalPrivate').'"><img src="'.$export_icon_low.'" alt="'.get_lang('ExportiCalPrivate').'"/></a>';
	echo '<a class="ical_export" href="ical_export.php?'.api_get_cidreq().'&type=course&id='.$myrow['id'].'&class=public" title="'.get_lang('ExportiCalPublic').'"><img src="'.$export_icon.'" alt="'.get_lang('ExportiCalPublic').'"/></a>';
	echo '<a href="#" onclick="javascript:win_print=window.open(\'print.php?id='.$myrow['id'].'\',\'popup\',\'left=100,top=100,width=700,height=500,scrollbars=1,resizable=0\'); win_print.focus(); return false;">'.Display::return_icon('print.gif', get_lang('Print')).'</a>&nbsp;';
	echo $content;
	echo '</td></tr>';

/*--------------------------------------------------
 			display: the added resources
  --------------------------------------------------*/
	if (check_added_resources("Agenda", $myrow["id"]))
	{
		
		echo '<tr>';
		echo $td_colspan;		
		echo "<i>".get_lang("AddedResources")."</i><br/>";
		if ($myrow['visibility']==0)
		{
			$addedresource_style="invisible";
		}
		display_added_resources("Agenda", $myrow["id"], $addedresource_style);
		echo "</td></tr>";
	}


	$event_list.=$myrow['id'].',';

	$counter++;

/*--------------------------------------------------
	display: jump-to-top icon
  --------------------------------------------------*/
	echo $td_colspan;
	echo "<a href=\"#top\"><img src=\"../img/top.gif\" border=\"0\" alt=\"to top\" align=\"right\" /></a></td></tr>";
	echo "</table><br /><br />";
} // end while ($myrow=mysql_fetch_array($result))


if(!empty($event_list))
{
	$event_list=substr($event_list,0,-1);
}
else
{
	$event_list='0';
}

echo "<form name=\"event_list_form\"><input type=\"hidden\" name=\"event_list\" value=\"$event_list\" /></form>";

// closing the layout table
echo "</td>",
	"</tr>",
	"</table>";
}

/**
* Displays only 1 agenda item. This is used when an agenda item is added to the learning path.
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function display_one_agenda_item($agenda_id)
{
	global $TABLEAGENDA;
	global $TABLE_ITEM_PROPERTY;
	global $select_month, $select_year;
	global $DaysShort, $DaysLong, $MonthsLong;
	global $is_courseAdmin;
	global $dateFormatLong, $timeNoSecFormat, $charset;
	global $_user;
	//echo "displaying agenda items";


	// getting the name of the groups
	$group_names=get_course_groups();

	/*--------------------------------------------------
			CONSTRUCT THE SQL STATEMENT
	  --------------------------------------------------*/

	$sql="SELECT agenda.*, toolitemproperties.*
					FROM ".$TABLEAGENDA." agenda, ".$TABLE_ITEM_PROPERTY." toolitemproperties
					WHERE agenda.id = toolitemproperties.ref
					AND toolitemproperties.tool='".TOOL_CALENDAR_EVENT."'
					AND toolitemproperties.visibility='1'
					AND agenda.id='$agenda_id'";
	$result=api_sql_query($sql,__FILE__,__LINE__) or die(mysql_error());
	$number_items=mysql_num_rows($result);
	$myrow=mysql_fetch_array($result); // there should be only one item so no need for a while loop

	/*--------------------------------------------------
			DISPLAY: NO ITEMS
	  --------------------------------------------------*/
	if ($number_items==0)
		{
		 echo "<table id=\"agenda_list\" ><tr><td>".get_lang("NoAgendaItems")."</td></tr></table>";
		}

	/*--------------------------------------------------
			DISPLAY: THE ITEMS
	  --------------------------------------------------*/
	echo "<table id=\"agenda_list\">\n";




	/*--------------------------------------------------
	 DISPLAY : the icon, title, destinees of the item
	  --------------------------------------------------*/
	echo "\t<tr>\n";

	// highlight: if a date in the small calendar is clicked we highlight the relevant items
	$db_date=(int)date("d",strtotime($myrow["start_date"])).date("n",strtotime($myrow["start_date"])).date("Y",strtotime($myrow["start_date"]));
	if ($_GET["day"].$_GET["month"].$_GET["year"] <>$db_date)
	{
		if ($myrow['visibility']=='0')
		{
			$style="data_hidden";
			$stylenotbold="datanotbold_hidden";
			$text_style="text_hidden";
		}
		else
		{
			$style="data";
			$stylenotbold="datanotbold";
			$text_style="text";
		}

	}
	else
	{
		$style="datanow";
		$stylenotbold="datanotboldnow";
		$text_style="textnow";
	}


	echo "\t\t<td class=\"".$style."\">\n";

	// adding an internal anchor
	echo "\t\t\t<a name=\"".(int)date("d",strtotime($myrow["start_date"]))."\"></a>";

	// the icons. If the message is sent to one or more specific users/groups
	// we add the groups icon
	// 2do: if it is sent to groups we display the group icon, if it is sent to a user we show the user icon
	echo Display::return_icon('agenda.gif');
	if ($myrow['to_group_id']!=='0')
		{
		echo Display::return_icon('group.gif'); //"<img src=\"../img/group.gif\" border=\"0\" />";
		}
	echo " ".$myrow['title']."\n";
	echo "\t\t</td>\n";

	// the message has been sent to
	echo "\t\t<td class=\"".$stylenotbold."\">".get_lang("SentTo").": ";
	$sent_to=sent_to(TOOL_CALENDAR_EVENT, $myrow["ref"]);
	$sent_to_form=sent_to_form($sent_to);
	echo $sent_to_form;
	echo "</td>\n\t</tr>\n";

	/*--------------------------------------------------
	 			DISPLAY: the title
	  --------------------------------------------------*/
	echo "\t<tr class=\"".$stylenotbold."\">\n";
	echo "\t\t<td>".get_lang("StartTime").": ";
	echo ucfirst(format_locale_date($dateFormatLong,strtotime($myrow["start_date"])))."&nbsp;&nbsp;&nbsp;";
	echo ucfirst(strftime($timeNoSecFormat,strtotime($myrow["start_date"])))."";
	echo "</td>\n";
	echo "\t\t<td>".get_lang("EndTime").": ";
	echo ucfirst(format_locale_date($dateFormatLong,strtotime($myrow["end_date"])))."&nbsp;&nbsp;&nbsp;";
	echo ucfirst(strftime($timeNoSecFormat,strtotime($myrow["end_date"])))."";
	echo "</td>\n";
	echo "\n\t</tr>\n";

	/*--------------------------------------------------
	 			DISPLAY: the content
	  --------------------------------------------------*/
	$content = $myrow['content'];
	$content = make_clickable($content);
	$content = text_filter($content);
	echo "\t<tr>\n\t\t<td class=\"".$text_style."\" colspan='2'>";
	echo $content;
	echo "</td></tr>";

	/*--------------------------------------------------
	 			DISPLAY: the added resources
	  --------------------------------------------------*/
	if (check_added_resources("Agenda", $myrow["id"]))
		{
		echo "<tr><td colspan='2'>";
		echo "<i>".get_lang("AddedResources")."</i><br/>";
		if ($myrow['visibility']==0)
		{
			$addedresource_style="invisible";
		}
		display_added_resources("Agenda", $myrow["id"], $addedresource_style);
		echo "</td></tr>";
		}

	/*--------------------------------------------------
		DISPLAY: edit delete button (course admin only)
	  --------------------------------------------------*/
	echo "<tr><td>";
	if (is_allowed_to_edit())
		{
		// edit
		echo 	"<a href=\"".api_get_self()."?".api_get_cidreq()."&origin=".$_GET['origin']."&amp;action=edit&amp;id=".$myrow['id']."\">",
				"<img src=\"../img/edit.gif\" border=\"0\" alt=\"".get_lang("ModifyCalendarItem")."\" /></a>",
				"<a href=\"".api_get_self()."?".api_get_cidreq()."&origin=".$_GET['origin']."&amp;action=delete&amp;id=".$myrow['id']."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."')) return false;\">",
				"<img src=\"../img/delete.gif\" border=\"0\" alt=\"".get_lang("Delete")."\" /></a>";
		if ($myrow['visibility']==1)
			{
			$image_visibility="visible";
			}
		else
			{
			$image_visibility="invisible";
			}
		echo 	"<a href=\"".api_get_self()."?".api_get_cidreq()."&origin=".$_GET['origin']."&amp;action=showhide&amp;id=".$myrow['id']."\">",
				"<img src=\"../img/".$image_visibility.".gif\" border=\"0\" alt=\"".get_lang("Visible")."\" /></a><br /><br />";
		}
	echo "</td>";
	echo "</table>";

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
function show_add_form($id = '')
{

	global $MonthsLong;

	// the default values for the forms
	if ($_GET['originalresource'] !== 'no')
	{
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
	}
	else
	{

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
	}


	//	switching the send to all/send to groups/send to users
	if ($_POST['To'])
	{
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
		}


	// if the id is set then we are editing an agenda item
	if (is_int($id))
	{
		//echo "before get_agenda_item".$_SESSION['allow_individual_calendar'];
		$item_2_edit=get_agenda_item($id);
		$title	= $item_2_edit['title'];
		$content= $item_2_edit['content'];
		// start date
		list($datepart, $timepart) = split(" ", $item_2_edit['start_date']);
		list($year, $month, $day) = explode("-", $datepart);
		list($hours, $minutes, $seconds) = explode(":", $timepart);
		// end date
		list($datepart, $timepart) = split(" ", $item_2_edit['end_date']);
		list($end_year, $end_month, $end_day) = explode("-", $datepart);
		list($end_hours, $end_minutes, $end_seconds) = explode(":", $timepart);
		// attachments
		edit_added_resources("Agenda", $id);
		$to=$item_2_edit['to'];
		//echo "<br />after get_agenda_item".$_SESSION['allow_individual_calendar'];
	}
	$content=stripslashes($content);
	$title=stripslashes($title);
	// we start a completely new item, we do not come from the resource linker
	if ($_GET['originalresource']!=="no" and $_GET['action']=="add")
	{

		$_SESSION["formelements"]=null;
		unset_session_resources();
	}
?>

<!-- START OF THE FORM  -->
<form action="<?php echo api_get_self().'?origin='.$_GET['origin'].'&amp;action='.$_GET['action']; ?>" method="post" name="new_calendar_item">
<input type="hidden" name="id" value="<?php if (isset($id)) echo $id; ?>" />
<input type="hidden" name="action" value="<?php if (isset($_GET['action'])) echo $_GET['action']; ?>" />

<table border="0" cellpadding="5" cellspacing="0" width="100%" id="newedit_form">
	<!-- the title -->
	<tr class="title">
		<td colspan="3">
		<span style="font-weight: bold;"><?php echo (isset($id) AND $id<>'')?get_lang('ModifyCalendarItem'):get_lang("AddCalendarItem"); ?></span>
		</td>
	</tr>

	<!--  the select specific users / send to all form -->
	<?php
	if (isset ($_SESSION['toolgroup']))
	{
		echo '<tr id="subtitle">';
		echo '<td colspan="3">';
		echo '<input type="hidden" name="selectedform[0]" value="GROUP:'.$_SESSION['toolgroup'].'"/>' ;
		echo '<input type="hidden" name="To" value="true"/>' ;
		echo '</td>';
		echo '</tr>';

	}
	else
	{

		?>
		<tr class="subtitle">
			<td valign="top" colspan="3">
				<?php
				// this variable defines if the course administrator can send a message to a specific user / group
				// or not
				//echo "<input type=\"submit\" name=\"To\" value=\"".get_lang("SelectGroupsUsers")."\" style=\"float:left\">" ;

				//echo "sessiewaarde: ".$_SESSION['allow_individual_calendar'];
				echo get_lang("SentTo").": ";
				if ((isset($_GET['id'])  && $to=='everyone') || !isset($_GET['id'])){
					echo get_lang("Everybody").'&nbsp;';
				}
				echo '<a href="#" onclick="if(document.getElementById(\'recipient_list\').style.display==\'none\') document.getElementById(\'recipient_list\').style.display=\'block\'; else document.getElementById(\'recipient_list\').style.display=\'none\';">'.get_lang('ModifyRecipientList').'</a>';
				show_to_form($to);
				if (isset($_GET['id']) && $to!='everyone'){
					echo '<script>document.getElementById(\'recipient_list\').style.display=\'block\';</script>';
				}
			?>
			<hr noshade="noshade" color="#cccccc" />
		</td>
	</tr>

	<?php
	}
	?>

	<!-- START date and time -->
	<tr class="subtitle">
		<td>

			<table cellpadding="0" cellspacing="0" border="0" width="100%">

			<tr><td width="110">
				<!-- date: 1 -> 31 -->
				<?php echo get_lang('StartDate').": \n"; ?>
			</td>

			<td>

			<select name="fday" onchange="javascript:document.new_calendar_item.end_fday.value=this.value;">
				<?php
					// small loop for filling all the dates
					// 2do: the available dates should be those of the selected month => february is from 1 to 28 (or 29) and not to 31
					echo "\n";
					foreach (range(1, 31) as $i)
					{
						// values have to have double digits
						$value = ($i <= 9 ? '0'.$i : $i );
						// the current day is indicated with [] around the date
						if ($value==$day)
						{
							echo "\t\t\t\t <option value=\"".$value."\" selected> ".$i." </option>\n";
						}
						else
						{
							echo "\t\t\t\t<option value=\"$value\">$i</option>\n";
						}
					}
					 ?>
			</select>

			<!-- month: january -> december -->
			<select name="fmonth" onchange="javascript:document.new_calendar_item.end_fmonth.value=this.value;">
				<?php
					echo "\n";
					for ($i=1; $i<=12; $i++)
					{
						// values have to have double digits
						if ($i<=9)
						{
							$value="0".$i;
						}
						else
						{
							$value=$i;
						}
						if ($value==$month)
						{
							echo "\t\t\t\t <option value=\"".$value."\" selected>".$MonthsLong[$i-1]."</option>\n";
						}
						else
						{
							echo "\t\t\t\t <option value=\"".$value."\">".$MonthsLong[$i-1]."</option>\n";
						}
					} ?>
			</select>

			<select name="fyear" onchange="javascript:document.new_calendar_item.end_fyear.value=this.value;">
				<option value="<?php echo ($year-1); ?>"><?php echo ($year-1); ?></option>
				<option value="<?php echo $year; ?>" selected="selected"><?php echo $year; ?></option>
				<?php
					echo "\n";
					for ($i=1; $i<=5; $i++)
					{
						$value=$year+$i;
						echo "\t\t\t\t<option value=\"$value\">$value</option>\n";
					} ?>
			</select>
			<a href="javascript:openCalendar('new_calendar_item', 'f')"><img src="../img/calendar_select.gif" border="0" alt="Select"/></a>
			</td></tr></table>
		</td>
		<td>

			<table cellpadding="0" cellspacing="0" border="0" width="100%">

			<tr><td width="110">
				<!-- date: 1 -> 31 -->
				<?php echo get_lang('StartTime').": \n"; ?>
			</td>

			<td>

			<select name="fhour" onchange="javascript:document.new_calendar_item.end_fhour.value=this.value;">
				<option value="--">--</option>
				<?php
					echo "\n";
					foreach (range(1, 24) as $i)
					{
						// values have to have double digits
						$value = ($i <= 9 ? '0'.$i : $i );
						// the current hour is indicated with [] around the hour
						if ($hours==$value)
						{
							echo "\t\t\t\t<option value=\"".$value."\" selected> ".$value." </option>\n";
						}
						else
						{
							echo "\t\t\t\t<option value=\"$value\">$value</option>\n";
						}
					} ?>
			</select>
			<?php echo get_lang('HourMinuteDivider'); ?>
			<select name="fminute" onchange="javascript:document.new_calendar_item.end_fminute.value=this.value;">
				<option value="<?php echo $minutes ?>"><?php echo $minutes; ?></option>
				<option value="--">--</option>
				<?php
					foreach (range(0, 59) as $i)
					{
						// values have to have double digits
						$value = ($i <= 9 ? '0'.$i : $i );
						echo "\t\t\t\t<option value=\"$value\">$value</option>\n";
					} ?>
			</select>
			</td></tr></table>
		</td>
	</tr>
	<!-- END date and time -->

	<tr class="subtitle">
		<td>

			<table cellpadding="0" cellspacing="0" border="0" width="100%">

			<tr><td width="110">
				<!-- date: 1 -> 31 -->
				<?php echo get_lang('EndDate').": \n"; ?>
			</td>

			<td>

			<select name="end_fday">
				<?php
					// small loop for filling all the dates
					// 2do: the available dates should be those of the selected month => february is from 1 to 28 (or 29) and not to 31
					echo "\n";
					foreach (range(1, 31) as $i)
					{
						// values have to have double digits
						$value = ($i <= 9 ? '0'.$i : $i );
						// the current day is indicated with [] around the date
						if ($value==$end_day)
							{ echo "\t\t\t\t <option value=\"".$value."\" selected> ".$i." </option>\n";}
						else
							{ echo "\t\t\t\t <option value=\"".$value."\">".$i."</option>\n"; }
						}?>
				</select>

				<!-- month: january -> december -->
				<select name="end_fmonth">
					<?php
					echo "\n";
					foreach (range(1, 12) as $i)
					{
						// values have to have double digits
						$value = ($i <= 9 ? '0'.$i : $i );
						if ($value==$end_month)
							{ echo "\t\t\t\t <option value=\"".$value."\" selected>".$MonthsLong[$i-1]."</option>\n"; }
						else
							{ echo "\t\t\t\t <option value=\"".$value."\">".$MonthsLong[$i-1]."</option>\n"; }
						}?>
				</select>

				<select name="end_fyear">
					<option value="<?php echo ($end_year-1) ?>"><?php echo ($end_year-1) ?></option>
					<option value="<?php echo $end_year ?>" selected> <?php echo $end_year ?> </option>
					<?php
					echo "\n";
					for ($i=1; $i<=5; $i++)
					{
						$value=$end_year+$i;
						echo "\t\t\t\t<option value=\"$value\">$value</option>\n";
					} ?>
			</select>
			<a href="javascript:openCalendar('new_calendar_item', 'end_f')"><img src="../img/calendar_select.gif" border="0" /></a>
			</td></tr></table>
		</td>

		<td>

			<table cellpadding="0" cellspacing="0" border="0" width="100%">

			<tr><td width="110">
				<!-- date: 1 -> 31 -->
				<?php echo get_lang('EndTime').": \n"; ?>
			</td>

			<td>

			<select name="end_fhour">
				<option value="--">--</option>
				<?php
					echo "\n";
					foreach (range(1, 24) as $i)
					{
						// values have to have double digits
						$value = ($i <= 9 ? '0'.$i : $i );
						// the current hour is indicated with [] around the hour
						if ($end_hours==$value)
							{ echo "\t\t\t\t<option value=\"".$value."\" selected> ".$value." </option>\n"; }
						else
							{ echo "\t\t\t\t<option value=\"".$value."\"> ".$value." </option>\n"; }
					} ?>
			</select>
			<?php echo get_lang('HourMinuteDivider'); ?>
			<select name="end_fminute">
				<option value="<?php echo $end_minutes; ?>"><?php echo $end_minutes; ?></option>
				<option value="--">--</option>
				<?php
					foreach (range(0, 59) as $i)
					{
						// values have to have double digits
						$value = ($i <= 9 ? '0'.$i : $i );
						echo "\t\t\t\t<option value=\"$value\">$value</option>\n";
					} ?>
			</select>
			</td></tr></table>
		</td>
	</tr>

	<tr class="subtitle">
		<td colspan="3" valign="top"><hr noshade="noshade" color="#cccccc" /><?php echo get_lang('ItemTitle'); ?> :
			<!--<div style='margin-left: 80px'><textarea name="title" cols="50" rows="2" wrap="virtual" style="vertical-align:top; width:75%; height:50px;"><?php  if (isset($title)) echo $title; ?></textarea></div>-->
			<input type="text" size="60" name="title" value="<?php  if (isset($title)) echo $title; ?>" />
		</td>
	</tr>

	<tr>
		<td colspan="7">

			<?php
			require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");

			$oFCKeditor = new FCKeditor('content') ;
			$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
			$oFCKeditor->Height		= '175';
			$oFCKeditor->Width		= '100%';
			$oFCKeditor->Value		= $content;
			$oFCKeditor->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";
			$oFCKeditor->ToolbarSet = "Middle";

			$TBL_LANGUAGES = Database::get_main_table(TABLE_MAIN_LANGUAGE);
			$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='".$_SESSION["_course"]["language"]."'";
			$result_sql=api_sql_query($sql);
			$isocode_language=mysql_result($result_sql,0,0);
			$oFCKeditor->Config['DefaultLanguage'] = $isocode_language;

			$return =	$oFCKeditor->CreateHtml();

			echo $return;

 ?>
		</td>
	</tr>
	<!--<?php /* ADDED BY UGENT, Patrick Cool, march 2004 */ ?>
	<tr>
		<td colspan="7">
	    <?php
			//onclick="selectAll(this.form.elements[6],true)"
			if ($_SESSION['allow_individual_calendar']=='show')
				show_addresource_button('onclick="selectAll(this.form.elements[6],true)"');
			else
				show_addresource_button();
			$form_elements=$_SESSION['formelements'];
		?>
		</td>
	</tr>-->
	<?php
	   //if ($_SESSION['addedresource'])
	   echo "\t<tr>\n";
	   echo "\t\t<td colspan=\"7\">\n";
	   echo display_resources(0);
	   $test=$_SESSION['addedresource'];
	   echo "\t\t</td>\n\t</tr>\n";
	   /* END ADDED BY UGENT, Patrick Cool, march 2004 */
	?>

	<tr>
		<td colspan="7">
			<input type="submit" name="submit_event" value="<?php echo get_lang('Ok'); ?>" onclick="selectAll(this.form.elements[5],true)" />
		</td>
	</tr>
</table>
</form>
<p>&nbsp;</p>
<?php
}

function get_agendaitems($month, $year)
{
	global $_user;
	global $_configuration;

	$items = array ();

	//databases of the courses
	$TABLEAGENDA 		= Database :: get_course_table(TABLE_AGENDA);
	$TABLE_ITEMPROPERTY = Database :: get_course_table(TABLE_ITEM_PROPERTY);

	$group_memberships = GroupManager :: get_group_ids(Database::get_current_course_database(), $_user['user_id']);
	// if the user is administrator of that course we show all the agenda items
	if (api_is_allowed_to_edit())
	{
		//echo "course admin";
		$sqlquery = "SELECT
						DISTINCT agenda.*, item_property.*
						FROM ".$TABLEAGENDA." agenda,
							 ".$TABLE_ITEMPROPERTY." item_property
						WHERE agenda.id = item_property.ref   ".$show_all_current."
						AND MONTH(agenda.start_date)='".$month."'
						AND YEAR(agenda.start_date)='".$year."'
						AND item_property.tool='".TOOL_CALENDAR_EVENT."'
						AND item_property.visibility='1'
						GROUP BY agenda.id
						ORDER BY start_date ".$sort;
	}
	// if the user is not an administrator of that course
	else
	{
		//echo "GEEN course admin";
		if (is_array($group_memberships))
		{
			$sqlquery = "SELECT
							agenda.*, item_property.*
							FROM ".$TABLEAGENDA." agenda,
								".$TABLE_ITEMPROPERTY." item_property
							WHERE agenda.id = item_property.ref   ".$show_all_current."
							AND MONTH(agenda.start_date)='".$month."'
							AND YEAR(agenda.start_date)='".$year."'
							AND item_property.tool='".TOOL_CALENDAR_EVENT."'
							AND	( item_property.to_user_id='".$_user['user_id']."' OR item_property.to_group_id IN (0, ".implode(", ", $group_memberships).") )
							AND item_property.visibility='1'
							ORDER BY start_date ".$sort;
		}
		else
		{
			$sqlquery = "SELECT
							agenda.*, item_property.*
							FROM ".$TABLEAGENDA." agenda,
							".$TABLE_ITEMPROPERTY." item_property
							WHERE agenda.id = item_property.ref   ".$show_all_current."
							AND MONTH(agenda.start_date)='".$month."'
							AND YEAR(agenda.start_date)='".$year."'
							AND item_property.tool='".TOOL_CALENDAR_EVENT."'
							AND ( item_property.to_user_id='".$_user['user_id']."' OR item_property.to_group_id='0')
							AND item_property.visibility='1'
							ORDER BY start_date ".$sort;
		}
	}

	$result = api_sql_query($sqlquery, __FILE__, __LINE__);
	while ($item = mysql_fetch_array($result))
	{
		$agendaday = date("j",strtotime($item['start_date']));
		$time= date("H:i",strtotime($item['start_date']));
		$URL = $_configuration['root_web']."main/calendar/agenda.php?cidReq=".urlencode($array_course_info["code"])."&amp;day=$agendaday&amp;month=$month&amp;year=$year#$agendaday"; // RH  //Patrick Cool: to highlight the relevant agenda item
		$items[$agendaday][$item['start_time']] .= "<i>".$time."</i> <a href=\"$URL\" title=\"".$array_course_info["name"]."\">".$array_course_info["visual_code"]."</a>  ".$item['title']."<br />";
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
?>