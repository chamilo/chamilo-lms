<?php //$Id: agenda.php 11607 2007-03-16 13:18:47Z elixir_julian $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003-2005 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/*
==============================================================================
	   INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = 'agenda';

// setting the global file that gets the general configuration, the databases, the languages, ...
include('../inc/global.inc.php');

//session
if(isset($_GET['id_session']))
{
	$_SESSION['id_session'] = $_GET['id_session'];
}

$this_section=SECTION_COURSES;

//error_reporting(E_ALL);
require (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');

/* ==============================================================================
  			ACCESS RIGHTS
============================================================================== */
// notice for unauthorized people.
api_protect_course_script();

/*
-----------------------------------------------------------
	Resource linker (Patrick Cool, march 2004)
-----------------------------------------------------------
*/
$_SESSION['source_type'] = 'Agenda';
include('../resourcelinker/resourcelinker.inc.php');
if ($addresources) // When the "Add Resource" button is clicked we store all the form data into a session
{
$form_elements= array ('day'=>$_POST['fday'], 'month'=>$_POST['fmonth'], 'year'=>$_POST['fyear'], 'hour'=>$_POST['fhour'], 'minutes'=>$_POST['fminute'],
						'end_day'=>$_POST['end_fday'], 'end_month'=>$_POST['end_fmonth'], 'end_year'=>$_POST['end_fyear'], 'end_hours'=>$_POST['end_fhour'], 'end_minutes'=>$_POST['end_fminute'],
						'title'=>stripslashes($_POST['title']), 'content'=>stripslashes($_POST['content']), 'id'=>$_POST['id'], 'action'=>$_POST['action'], 'to'=>$_POST['selectedform']);
$_SESSION['formelements']=$form_elements;
if($id) // this is to correctly handle edits
	{$action="edit";}
//print_r($form_elements);
header('Location: '.api_get_path(WEB_CODE_PATH)."resourcelinker/resourcelinker.php?source_id=1&action=$action&id=$id&originalresource=no");
exit;
}

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
// containing the functions for the agenda tool
include "agenda.inc.php";
// some debug functions
include($includePath."/lib/debug.lib.inc.php");

/*==============================================================================
  			TREATING THE PARAMETERS
			1. viewing month only or everything
			2. sort ascending or descending
			3. showing or hiding the send-to-specific-groups-or-users form
			4. filter user or group
  ============================================================================== */
// 1. show all or show current month?
if (!$_SESSION['show'])
{
	$_SESSION['show']="showall";
}
if ($_GET['action']=="showcurrent")
{
	$_SESSION['show']="showcurrent";
}
if ($_GET['action']=="showall")
{
	$_SESSION['show']="showall";
}
//echo $_SESSION['show'];

// 2. sorting order (ASC or DESC)
if (!$_GET['sort'] and !$_SESSION['sort'])
{
	$_SESSION['sort']="DESC";
}
if ($_GET['sort']=="asc")
{
	$_SESSION['sort']="ASC";
}
if ($_GET['sort']=="desc")
{
	$_SESSION['sort']="DESC";
}

// 3. showing or hiding the send-to-specific-groups-or-users form
$setting_allow_individual_calendar=true;
if (!$_POST['To'] and !$_SESSION['allow_individual_calendar'])
{
	$_SESSION['allow_individual_calendar']="hide";
}
$allow_individual_calendar_status=$_SESSION['allow_individual_calendar'];
if ($_POST['To'] and ($allow_individual_calendar_status=="hide"))
{
	$_SESSION['allow_individual_calendar']="show";
}
if ($_POST['To'] and ($allow_individual_calendar_status=="show"))
{
	$_SESSION['allow_individual_calendar']="hide";
}

// 4. filter user or group
if ($_GET['user'] or $_GET['group'])
{
	$_SESSION['user']=(int)$_GET['user'];
	$_SESSION['group']=(int)$_GET['group'];
}
if ($_GET['user']=="none" or $_GET['group']=="none")
{
	api_session_unregister("user");
	api_session_unregister("group");
	}
if (!$is_courseAdmin){
	if (!empty($_GET['toolgroup'])){
		//$_SESSION['toolgroup']=$_GET['toolgroup'];
		$toolgroup=$_GET['toolgroup'];
		api_session_register('toolgroup');
		}
	}
	//It comes from the group tools. If it's define it overwrites $_SESSION['group']
if ($_GET['isStudentView']=="false")
{
	api_session_unregister("user");
	api_session_unregister("group");
}

$htmlHeadXtra[] = to_javascript();

$htmlHeadXtra[] = user_group_filter_javascript();
// this loads the javascript that is needed for the date popup selection
$htmlHeadXtra[] = "<script src=\"tbl_change.js\" type=\"text/javascript\" language=\"javascript\"></script>";

// setting the name of the tool
$nameTools = get_lang('Agenda'); // language variable in trad4all.inc.php

// showing the header if we are not in the learning path, if we are in
// the learning path, we do not include the banner so we have to explicitly
// include the stylesheet, which is normally done in the header
if ($_GET['origin'] != 'learnpath')
{
	Display::display_header($nameTools,'Agenda');
}
else
{
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$clarolineRepositoryWeb."css/default.css\"/>";
	}

/* ==============================================================================
  			TRACKING
==============================================================================  */
include('../inc/lib/events.lib.inc.php');
event_access_tool(TOOL_CALENDAR_EVENT);

/* ==============================================================================
  			SETTING SOME VARIABLES
============================================================================== */
// Variable definitions
$dateNow 			= format_locale_date($dateTimeFormatLong);
// Defining the shorts for the days. We use camelcase because these are arrays of language variables
$DaysShort = array (get_lang("SundayShort"), get_lang("MondayShort"), get_lang("TuesdayShort"), get_lang("WednesdayShort"), get_lang("ThursdayShort"), get_lang("FridayShort"), get_lang("SaturdayShort"));
// Defining the days of the week to allow translation of the days. We use camelcase because these are arrays of language variables
$DaysLong = array (get_lang("SundayLong"), get_lang("MondayLong"), get_lang("TuesdayLong"), get_lang("WednesdayLong"), get_lang("ThursdayLong"), get_lang("FridayLong"), get_lang("SaturdayLong"));
// Defining the months of the year to allow translation of the months. We use camelcase because these are arrays of language variables
$MonthsLong = array (get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong"));

// Database table definitions
$TABLEAGENDA 			= Database::get_course_table(TABLE_AGENDA);
$TABLE_ITEM_PROPERTY 	= Database::get_course_table(TABLE_ITEM_PROPERTY);
$tbl_user       		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_courseUser 		= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_group      		= Database::get_course_table(TABLE_GROUP);
$tbl_groupUser  		= Database::get_course_table(TABLE_GROUP_USER);
$tbl_session_course_user= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);



/* ==============================================================================
  			ACCESS RIGHTS
============================================================================== */
// permission stuff
$is_allowedToEdit 	= is_allowed_to_edit();
$is_allowed_to_edit = is_allowed_to_edit();

/* ==============================================================================
  			TITLE
============================================================================== */
// Displaying the title of the tool
//api_display_tool_title($nameTools);

// tool introduction
Display::display_introduction_section(TOOL_CALENDAR_EVENT);

// insert an anchor (top) so one can jump back to the top of the page
echo "<a name=\"top\"></a>";

/*
==============================================================================
		MAIN SECTION
==============================================================================
*/

//setting the default year and month
$select_year = (int)$_GET['year'];
$select_month = (int)$_GET['month'];
if (($select_year==NULL) && ($select_month==NULL))
{
	$today = getdate();
	$select_year = $today['year'];
	$select_month = $today['mon'];
}



echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">"
		. "<tr>";

// THE LEFT PART
if ($_GET['origin']!='learnpath')
{
	echo '<td width="220" height="19" valign="top">';
	// the small calendar
	$MonthName = $MonthsLong[$select_month -1];
	$agenda_items=get_kalender_items($select_month,$select_year);
	display_minimonthcalendar($agenda_items, $select_month,$select_year, $MonthName);
	// the links for adding, filtering, showall, ...
	echo "<ul id=\"agenda_select\">";
	if (is_allowed_to_edit())
	{
		display_courseadmin_links();
	}
	display_student_links();
	echo "</ul>";
	echo "</td>";
	echo "<td width=\"20\" background=\"../img/verticalruler.gif\">&nbsp;</td>";
}

$fck_attribute['Width'] = '600';
$fck_attribute['Height'] = '400';
$fck_attribute['ToolbarSet'] = 'Middle';


// THE RIGHT PART
echo "<td valign=\"top\">";

if (is_allowed_to_edit())
{
	switch ($_GET['action'])
	{
		case "add":

			if ($_POST['submit_event'])
			{
				store_new_agenda_item();
				display_agenda_items();
			}
			else
			{
				show_add_form();
			}
			break;

		case "edit":
			if ($_POST['submit_event'])
			{
					store_edited_agenda_item();
					display_agenda_items();
			}
			else
			{
					$id=(int)$_GET['id'];
					show_add_form($id);
			}
			break;

		case "delete":
			$id=(int)$_GET['id'];
			delete_agenda_item($id);
			display_agenda_items();
			break;

		case "showhide":
			$id=(int)$_GET['id'];
			showhide_agenda_item($id);
			display_agenda_items();
			break;
		case "announce": //copying the agenda item into an announcement
			$id=(int)$_GET['id'];
			$ann_id = store_agenda_item_as_announcement($id);
			$tool_group_link = (isset($_SESSION['toolgroup'])?'&toolgroup='.$_SESSION['toolgroup']:'');
			Display::display_normal_message('Copied as announcement: <a href="../announcements/announcements.php?id='.$ann_id.$tool_group_link.'">New announcement</a>', false);
			display_agenda_items();
	}
}


// this is for students and whenever the courseaministrator has not chosen any action. It is in fact the default behaviour
if (!$_GET['action'] OR $_GET['action']=="showall"  OR $_GET['action']=="showcurrent" OR $_GET['action']=="view")
{
	if ($_GET['origin'] != 'learnpath')
	{
		display_agenda_items();
	}
	else
	{
		display_one_agenda_item((int)$_GET['agenda_id']);
	}
}
echo "&nbsp;</td></tr></table>";

/*
==============================================================================
		FOOTER
==============================================================================
*/
// The footer is displayed only if we are not in the learnpath
if ($_GET['origin'] != 'learnpath')
{
	Display::display_footer();
}
?>