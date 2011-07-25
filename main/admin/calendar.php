<?php // $id: $
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*	@author Carlos Vargas
*	This file is the calendar/agenda.php
*/

// name of the language file that needs to be included
$language_file[] = 'admin';
$language_file[] = 'agenda';

// resetting the course id
$cidReset=true;
// including some necessary files
require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
$_SESSION['this_section']=$this_section;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// setting the name of the tool
$tool_name= get_lang('SubscribeCoursesToSession');

$id_session=intval($_GET['id_session']);

if (!api_is_platform_admin()) {
	$sql = 'SELECT session_admin_id FROM '.Database :: get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$id_session;
	$rs = Database::query($sql);
	if(Database::result($rs,0,0)!= api_get_user_id()) {
		api_not_allowed(true);
	}
}

/*
	Libraries
*/
// containing the functions for the agenda tool
require_once 'calendar.lib.php';

/*
  			TREATING THE PARAMETERS
			1. viewing month only or everything
			2. sort ascending or descending
			3. showing or hiding the send-to-specific-groups-or-users form
			4. filter user or group
 */
// 1. show all or show current month?
if (!$_SESSION['show_all_admin']) {
	$_SESSION['show_all_admin']="showall";
}
if (!empty($_GET['action']) and $_GET['action']=="showcurrent")
{
	$_SESSION['show_all_admin']='showcurrent';
}
if (!empty($_GET['action']) and $_GET['action']=="showall")
{
	$_SESSION['show_all_admin']='showall';
}

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
if (!empty($_GET['view']))
{
	$_SESSION['view'] = $_GET['view'];
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

$htmlHeadXtra[] = to_javascript();

// this loads the javascript that is needed for the date popup selection
$htmlHeadXtra[] = "<script src=\"calendar_tbl_change.js\" type=\"text/javascript\" language=\"javascript\"></script>";

// setting the name of the tool
$nameTools = get_lang('GlobalAgenda'); // language variable in trad4all.inc.php

// showing the header if we are not in the learning path, if we are in
// the learning path, we do not include the banner so we have to explicitly
// include the stylesheet, which is normally done in the header
/*if (empty($_GET['origin']) or $_GET['origin'] != 'learnpath')
{*/
Display::display_header($nameTools,'Agenda');
/*}
else
{
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"".$clarolineRepositoryWeb."css/default.css\"/>";
}*/

/* 
  			SETTING SOME VARIABLES
*/
// Variable definitions
// Defining the shorts for the days. We use camelcase because these are arrays of language variables
$DaysShort = api_get_week_days_short();
// Defining the days of the week to allow translation of the days. We use camelcase because these are arrays of language variables
$DaysLong = api_get_week_days_long();
// Defining the months of the year to allow translation of the months. We use camelcase because these are arrays of language variables
$MonthsLong = api_get_months_long();

// Database table definitions
$TABLEAGENDA 			= Database::get_main_table(TABLE_MAIN_SYSTEM_CALENDAR);
$TABLE_ITEM_PROPERTY 	= Database::get_course_table(TABLE_ITEM_PROPERTY);
$tbl_user       		= Database::get_main_table(TABLE_MAIN_USER);
$tbl_courseUser 		= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_group      		= Database::get_course_table(TABLE_GROUP);
$tbl_groupUser  		= Database::get_course_table(TABLE_GROUP_USER);

/* 
  			ACCESS RIGHTS
*/
// permission stuff - also used by loading from global in agenda.inc.php
$is_allowed_to_edit = api_is_allowed_to_edit() OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous());

// insert an anchor (top) so one can jump back to the top of the page
echo "<a name=\"top\"></a>";

/*
		MAIN SECTION
*/

//setting the default year and month
$select_year = '';
$select_month = '';
if(!empty($_GET['year'])) {
	$select_year = (int)$_GET['year'];
}
if(!empty($_GET['month'])) {
	$select_month = (int)$_GET['month'];
}
if (empty($select_year) && empty($select_month)) {
	$today = getdate();
	$select_year = $today['year'];
	$select_month = $today['mon'];
}

echo '<div class="actions">';
if (api_is_allowed_to_edit(false,true) OR (api_get_course_setting('allow_user_edit_agenda') && !api_is_anonymous())) {
	display_student_links();
	display_courseadmin_links();
}

echo '</div><br /><br />';
echo '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>';

// THE LEFT PART
if (empty($_GET['origin']) or $_GET['origin']!='learnpath') {
	echo '<td width="220" height="19" valign="top">';
	// the small calendar
	$MonthName = $MonthsLong[$select_month -1];
	$agenda_items=get_calendar_items($select_month,$select_year);
	if (api_get_setting('display_mini_month_calendar') == 'true') {
		display_minimonthcalendar($agenda_items, $select_month,$select_year, $MonthName);
	}
	if (api_get_setting('display_upcoming_events') == 'true') {
		display_upcoming_events();
	}
	echo '</td>';
	echo '<td width="20" background="../img/verticalruler.gif">&nbsp;</td>';
}


// THE RIGHT PART
echo '<td valign="top">';
echo '<div class="sort" style="float:right">';

echo '</div>';
if (api_is_allowed_to_edit(false,true)) {
	switch ($_GET['action']) {
		case "add":            
            if ($_POST['submit_event']) {		        
			    $event_start    = (int) $_POST['fyear'].'-'.(int) $_POST['fmonth'].'-'.(int) $_POST['fday'].' '.(int) $_POST['fhour'].':'.(int) $_POST['fminute'].':00';
                $event_stop     = (int) $_POST['end_fyear'].'-'.(int) $_POST['end_fmonth'].'-'.(int) $_POST['end_fday'].' '.(int) $_POST['end_fhour'].':'.(int) $_POST['end_fminute'].':00';
                
				$id = agenda_add_item($_POST['title'],$_POST['content'],$event_start,$event_stop);           
				display_agenda_items();
			} else {
				show_add_form();
			}
			break;

		case "edit":
			if( ! (api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, intval($_REQUEST['id']) ) ) )
			{ // a coach can only delete an element belonging to his session
				if ($_POST['submit_event']) {		
			        $my_id_attach = (int)$_REQUEST['id_attach'];
					$my_file_comment = Database::escape_string($_REQUEST['file_comment']);
					store_edited_agenda_item($my_id_attach,$my_file_comment).'hola test';
					display_agenda_items();
				} else {
					$id=(int)$_GET['id'];
					show_add_form($id);
				}
			} else {
				display_agenda_items();
			}
			break;

		case "delete":
			$id=(int)$_GET['id'];
			if( ! (api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, $id ) ) )
			{ // a coach can only delete an element belonging to his session
				if (api_is_allowed_to_edit()  && !api_is_anonymous()) {
					if (!empty($id)) {
						$res_del = delete_agenda_item($id);
						if ($res_del) {
							Display::display_normal_message(get_lang("AgendaDeleteSuccess"));
						}
					}
				}
			}
			display_agenda_items();
			break;

		case "showhide":
			$id=(int)$_GET['id'];
			if( ! (api_is_course_coach() && !api_is_element_in_the_session(TOOL_AGENDA, $id ) ) )
			{ // a coach can only delete an element belonging to his session
				showhide_agenda_item($id);
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
			display_agenda_items();
			break;
		case "delete_attach": //delete attachment file
			$id_attach = (int)$_GET['id_attach'];
			if (!empty($id_attach)) {
				delete_attachment_file($id_attach);
			}
			display_agenda_items();
			break;

	}
}

// this is for students and whenever the courseaministrator has not chosen any action. It is in fact the default behaviour
if (!$_GET['action'] OR $_GET['action']=="showall"  OR $_GET['action']=="showcurrent" OR $_GET['action']=="view") {
	if ($_GET['origin'] != 'learnpath') {
		if (!$_SESSION['view'] OR $_SESSION['view'] <> 'month') {
            if(!empty($_GET['agenda_id'])) {
                 display_one_agenda_item((int)$_GET['agenda_id']);
            } else {
			     display_agenda_items();
            }
		} else {
			display_monthcalendar($select_month, $select_year);
		}
	} else {
		display_one_agenda_item((int)$_GET['agenda_id']);
	}
}
echo "&nbsp;</td></tr></table>";

/*
		FOOTER
*/
// The footer is displayed only if we are not in the learnpath
if ($_GET['origin'] != 'learnpath') {
	Display::display_footer();
}