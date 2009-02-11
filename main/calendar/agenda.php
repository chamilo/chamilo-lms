<?php //$Id: agenda.php 18446 2009-02-11 20:41:40Z ivantcholakov $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003-2005 Ghent University (UGent)
	Copyright (c) various contributors

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
*/
/*
==============================================================================
	   INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array('agenda','group');

// use anonymous mode when accessing this course tool
$use_anonymous = true;

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
require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

if (!empty($addresources)) // When the "Add Resource" button is clicked we store all the form data into a session
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

if (!empty($_GET['view']))
{
	$_SESSION['view'] = $_GET['view'];
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
if (!empty($_GET['action']) and $_GET['action']=="showcurrent")
{
	$_SESSION['show']="showcurrent";
}
if (!empty($_GET['action']) and $_GET['action']=="showall")
{
	$_SESSION['show']="showall";
}
//echo $_SESSION['show'];

// 2. sorting order (ASC or DESC)
if (empty($_GET['sort']) and empty($_SESSION['sort']))
{
	$_SESSION['sort']="DESC";
}
if (!empty($_GET['sort']) and $_GET['sort']=="asc")
{
	$_SESSION['sort']="ASC";
}
if (!empty($_GET['sort']) and $_GET['sort']=="desc")
{
	$_SESSION['sort']="DESC";
}

// 3. showing or hiding the send-to-specific-groups-or-users form
$setting_allow_individual_calendar=true;
if (empty($_POST['To']) and empty($_SESSION['allow_individual_calendar']))
{
	$_SESSION['allow_individual_calendar']="hide";
}
$allow_individual_calendar_status=$_SESSION['allow_individual_calendar'];
if (!empty($_POST['To']) and ($allow_individual_calendar_status=="hide"))
{
	$_SESSION['allow_individual_calendar']="show";
}
if (!empty($_GET['sort']) and ($allow_individual_calendar_status=="show"))
{
	$_SESSION['allow_individual_calendar']="hide";
}

// 4. filter user or group
if (!empty($_GET['user']) or !empty($_GET['group']))
{
	$_SESSION['user']=(int)$_GET['user'];
	$_SESSION['group']=(int)$_GET['group'];
}
if ((!empty($_GET['user']) and $_GET['user']=="none") or (!empty($_GET['group']) and $_GET['group']=="none"))
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
if (!empty($_GET['isStudentView']) and $_GET['isStudentView']=="false")
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
if ($_GET['toolgroup']){
	$_clean['toolgroup']=(int)$_GET['toolgroup'];
	$group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['toolgroup'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');	
	Display::display_header($nameTools,'Agenda');
	
} elseif (empty($_GET['origin']) or $_GET['origin'] != 'learnpath') {
	Display::display_header($nameTools,'Agenda');
} else {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$clarolineRepositoryWeb."css/default.css\"/>";
}

/* ==============================================================================
  			TRACKING
==============================================================================  */
require_once '../inc/lib/events.lib.inc.php';
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
// permission stuff - also used by loading from global in agenda.inc.php
$is_allowed_to_edit = api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous());

/* ==============================================================================
  			TITLE
============================================================================== */
// Displaying the title of the tool
//api_display_tool_title($nameTools);

// tool introduction

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';

Display::display_introduction_section(TOOL_CALENDAR_EVENT);

$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

// insert an anchor (top) so one can jump back to the top of the page
echo '<a name="top"></a>';

/*
==============================================================================
		MAIN SECTION
==============================================================================
*/

//setting the default year and month
$select_year = '';
$select_month = '';
if(!empty($_GET['year']))
{
	$select_year = (int)$_GET['year'];
}
if(!empty($_GET['month']))
{
	$select_month = (int)$_GET['month'];
}
if (empty($select_year) && empty($select_month))
{
	$today = getdate();
	$select_year = $today['year'];
	$select_month = $today['mon'];
}

echo '<div class="actions">';
display_student_links();
if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
{
	display_courseadmin_links();
}
echo '</div><br />';


echo '<table width="100%" border="0" cellspacing="0" cellpadding="0">'
		. '<tr>';

// THE LEFT PART
if (empty($_GET['origin']) or $_GET['origin']!='learnpath')
{
	echo '<td width="220" height="19" valign="top">';
	// the small calendar
	$MonthName = $MonthsLong[$select_month -1];
	$agenda_items=get_calendar_items($select_month,$select_year);
	if (api_get_setting('display_mini_month_calendar') == 'true')
	{
		display_minimonthcalendar($agenda_items, $select_month,$select_year, $MonthName);
	}
	/*if (api_get_setting('display_upcoming_events') == 'true') {
		display_upcoming_events();
	}*/
	echo '</td>';
	echo '<td width="20" background="../img/verticalruler.gif">&nbsp;</td>';
}

/*
//TODO: This fragment of code is obsolete and is to be deleted. Ivan Tcholakov.
//TODO: meet this fckeditor code and agenda.inc.php fckeditor code
$fck_attribute['Config']['CreateDocumentDir'] = api_get_path('WEB_COURSE_PATH').$_course['path'].'/document/';
$fck_attribute['Config']['CreateDocumentWebDir'] = api_get_path('WEB_COURSE_PATH').$_course['path'].'/document/';

if(isset($_SESSION['_course']) && $_SESSION['_course']['path']!='')
{
	$upload_path = api_get_path(REL_COURSE_PATH).$_SESSION['_course']['path'].'/document/';
}
else
{
	$upload_path = api_get_path(REL_PATH).'main/default_course_document/'; //TODO: check if this is correct or better main/upload/num_user/my_files
}
*/

// THE RIGHT PART
echo '<td valign="top">';
echo '<div class="sort" style="float:left">';
echo '</div>';
if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous()))
{
	switch ($_GET['action'])
	{
		case "add":			
            if(isset($_POST['ical_submit']))
            {
                $course_info = api_get_course_info();                
                if ($_FILES['ical_import']['type']=='text/calendar') {
                agenda_import_ical($course_info,$_FILES['ical_import']);
                } else {
                	Display::display_error_message(get_lang('IsNotiCalFormatFile'));
                }
                if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
				}
                display_agenda_items();
            }
			elseif ($_POST['submit_event'])
			{
		     $course_info = api_get_course_info();
			    $event_start    = (int) $_POST['fyear'].'-'.(int) $_POST['fmonth'].'-'.(int) $_POST['fday'].' '.(int) $_POST['fhour'].':'.(int) $_POST['fminute'].':00';
                $event_stop     = (int) $_POST['end_fyear'].'-'.(int) $_POST['end_fmonth'].'-'.(int) $_POST['end_fday'].' '.(int) $_POST['end_fhour'].':'.(int) $_POST['end_fminute'].':00';
				$id = agenda_add_item($course_info,$_POST['title'],$_POST['content'],$event_start,$event_stop,$_POST['selectedform'],false,$_POST['file_comment']);
                if(!empty($_POST['repeat']))
                {
                	$end_y = intval($_POST['repeat_end_year']);
                    $end_m = intval($_POST['repeat_end_month']);
                    $end_d = intval($_POST['repeat_end_day']);
                    $end   = mktime(23, 59, 59, $end_m, $end_d, $end_y);
                    $res = agenda_add_repeat_item($course_info,$id,$_POST['repeat_type'],$end,$_POST['selectedform'],$_POST['file_comment']);
                } 
                if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
				}               
				display_agenda_items();
			}
			else
			{
				show_add_form();
			}
			break;

		case "edit":
			if( ! (api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, intval($_REQUEST['id']) ) ) )
			{ // a coach can only delete an element belonging to his session
				if ($_POST['submit_event'])
				{		$my_id_attach = (int)$_REQUEST['id_attach'];
						$my_file_comment = Database::escape_string($_REQUEST['file_comment']);
						store_edited_agenda_item($my_id_attach,$my_file_comment);
						if (api_get_setting('display_upcoming_events') == 'true') {
							display_upcoming_events();
						}
						display_agenda_items();
				}
				else
				{
						$id=(int)$_GET['id'];
						show_add_form($id);
				}
			}
			else
			{
				if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
				}
				display_agenda_items();
			}
			break;

		case "delete":
			$id=(int)$_GET['id'];
			if( ! (api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, $id ) ) )
			{ // a coach can only delete an element belonging to his session
				delete_agenda_item($id);
			}
				if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
				}
				display_agenda_items();
			break;

		case "showhide":
			$id=(int)$_GET['id'];
			if( ! (api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, $id ) ) )
			{ // a coach can only delete an element belonging to his session
				showhide_agenda_item($id);
			}
			if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
			}
			display_agenda_items();
			break;
		case "announce": //copying the agenda item into an announcement
			$id=(int)$_GET['id'];
			if( ! (api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, $id ) ) )
			{ // a coach can only delete an element belonging to his session
				$ann_id = store_agenda_item_as_announcement($id);
				$tool_group_link = (isset($_SESSION['toolgroup'])?'&toolgroup='.$_SESSION['toolgroup']:'');
				echo '<br />';
				Display::display_normal_message(get_lang('CopiedAsAnnouncement').'<a href="../announcements/announcements.php?id='.$ann_id.$tool_group_link.'">'.get_lang('NewAnnouncement').'</a>', false);
			}
			if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
			}
			display_agenda_items();
			break;
		case "delete_attach": //delete attachment file
			$id_attach = (int)$_GET['id_attach'];
			if (!empty($id_attach)) {
				delete_attachment_file($id_attach);
			}
			if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
			}
			display_agenda_items();
			break;

	}
}
// this is for students and whenever the courseaministrator has not chosen any action. It is in fact the default behaviour
if (!$_GET['action'] || $_GET['action']=="showall"  || $_GET['action']=="showcurrent" || $_GET['action']=="view")
{
	if ($_GET['origin'] != 'learnpath')
	{
		if (!$_SESSION['view'] || $_SESSION['view'] <> 'month')
		{
            if(!empty($_GET['agenda_id']))
            {
                 display_one_agenda_item((int)$_GET['agenda_id']);
            }
            else
            {
            	if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
				}
			     display_agenda_items();
            }
		}
        else 
        {        	
        		if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
				}
				display_monthcalendar($select_month, $select_year);	
        	
		}
	}
	else
	{
		if (api_get_setting('display_upcoming_events') == 'true') {
					display_upcoming_events();
		}
		display_one_agenda_item((int)$_GET['agenda_id']);
	}
}
echo '&nbsp;</td></tr></table>';

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
