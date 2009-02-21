<?php //$Id: work.php 18626 2009-02-21 14:15:42Z ivantcholakov $
/* For licensing terms, see /dokeos_license.txt */
/**
*	@package dokeos.work
* 	@author Thomas, Hugues, Christophe - original version
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University - ability for course admins to specify wether uploaded documents are visible or invisible by default.
* 	@author Roan Embrechts, code refactoring and virtual course support
* 	@author Frederic Vauthier, directories management
*  	@version $Id: work.php 18626 2009-02-21 14:15:42Z ivantcholakov $
*
* 	@todo refactor more code into functions, use quickforms, coding standards, ...
*/
/**
==============================================================================
 * 	STUDENT PUBLICATIONS MODULE
 *
 * Note: for a more advanced module, see the dropbox tool.
 * This one is easier with less options.
 * This tool is better used for publishing things,
 * sending in assignments is better in the dropbox.
 *
 * GOALS
 * *****
 * Allow student to quickly send documents immediately
 * visible on the course website.
 *
 * The script does 5 things:
 *
 * 	1. Upload documents
 * 	2. Give them a name
 * 	3. Modify data about documents
 * 	4. Delete link to documents and simultaneously remove them
 * 	5. Show documents list to students and visitors
 *
 * On the long run, the idea is to allow sending realvideo . Which means only
 * establish a correspondence between RealServer Content Path and the user's
 * documents path.
 *
 * All documents are sent to the address /$_configuration['root_sys']/$currentCourseID/document/
 * where $currentCourseID is the web directory for the course and $_configuration['root_sys']
 * usually /var/www/html
 *
 *	Modified by Patrick Cool, february 2004:
 *	Allow course managers to specify wether newly uploaded documents should
 *	be visible or unvisible by default
 *	This is ideal for reviewing the uploaded documents before the document
 *	is available for everyone.
 *
 *	note: maybe the form to change the behaviour should go into the course
 *	properties page?
 *	note 2: maybe a new field should be created in the course table for
 *	this behaviour.
 *
 *	We now use the show_score field since this is not used.
 *
==============================================================================
 */

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included
$language_file = array (
    'exercice',
	'work',
	'document',
	'admin'
);
require("../inc/global.inc.php");
// @todo why is this needed?
//session
if (isset ($_GET['id_session'])) {
	$_SESSION['id_session'] = Database::escape_string($_GET['id_session']);
}
isset($_SESSION['id_session'])?$id_session=$_SESSION['id_session']:$id_session=null;
/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require_once 'work.lib.php';
require_once (api_get_path(LIBRARY_PATH) . 'course.lib.php');
require_once (api_get_path(LIBRARY_PATH) . 'debug.lib.inc.php');
require_once (api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
require_once (api_get_path(LIBRARY_PATH) . 'security.lib.php');
require_once (api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php');
require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
require_once(api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH).'text.lib.php');
// Section (for the tabs)
$this_section = SECTION_COURSES;
$ctok = $_SESSION['sec_token'];
$stok = Security::get_token();
$htmlHeadXtra[] = to_javascript_work();

/*
-----------------------------------------------------------
	Table definitions
-----------------------------------------------------------
*/
$main_course_table 	= Database :: get_main_table(TABLE_MAIN_COURSE);
$work_table 		= Database :: get_course_table(TABLE_STUDENT_PUBLICATION);
$iprop_table 		= Database :: get_course_table(TABLE_ITEM_PROPERTY);
$TSTDPUBASG			= Database :: get_course_table(TABLE_STUDENT_PUBLICATION_ASSIGNMENT);
$t_gradebook_link 	= Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_user = Database :: get_main_table(TABLE_MAIN_USER);
$table_session = Database :: get_main_table(TABLE_MAIN_SESSION);
$table_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$tool_name = get_lang('StudentPublications');
$user_id = api_get_user_id();
$course_code = $_course['sysCode'];
$is_course_member = $is_courseMember || api_is_platform_admin();
$currentCourseRepositorySys = api_get_path(SYS_COURSE_PATH) . $_course["path"] . "/";
$currentCourseRepositoryWeb = api_get_path(WEB_COURSE_PATH) . $_course["path"] . "/";
$currentUserFirstName = $_user['firstName'];
$currentUserLastName = $_user['lastName'];

isset($_POST['authors'])?$authors = Database :: escape_string($_POST['authors']):$authors='';
isset($_REQUEST['delete'])?$delete = Database :: escape_string($_REQUEST['delete']):$delete='';
isset($_REQUEST['description'])?$description = Database :: escape_string($_REQUEST['description']):$description='';
isset($_REQUEST['display_tool_options'])?$display_tool_options = $_REQUEST['display_tool_options']:$display_tool_options='';
isset($_REQUEST['display_upload_form'])?$display_upload_form = $_REQUEST['display_upload_form']:$display_upload_form='';
isset($_REQUEST['edit'])?$edit = Database :: escape_string($_REQUEST['edit']):$edit='';
isset($_REQUEST['parent_id'])?$parent_id = Database :: escape_string($_REQUEST['parent_id']):$parent_id='';
isset($_REQUEST['make_invisible'])?$make_invisible = Database :: escape_string($_REQUEST['make_invisible']):$make_invisible='';
isset($_REQUEST['make_visible'])?$make_visible = Database :: escape_string($_REQUEST['make_visible']):$make_visible='';
isset($_REQUEST['origin'])?$origin = Security :: remove_XSS($_REQUEST['origin']):$origin='';
isset($_REQUEST['submitGroupWorkUrl'])?$submitGroupWorkUrl = Security :: remove_XSS($_REQUEST['submitGroupWorkUrl']):$submitGroupWorkUrl='';
isset($_REQUEST['title'])?$title = Database :: escape_string($_REQUEST['title']):$title='';
isset($_REQUEST['uploadvisibledisabled'])?$uploadvisibledisabled = Database :: escape_string($_REQUEST['uploadvisibledisabled']):$uploadvisibledisabled='';
isset($_REQUEST['id'])?$id = strval(intval($_REQUEST['id'])):$id='';

//directories management
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$course_dir = $sys_course_path . $_course['path'];
$base_work_dir = $course_dir . '/work';
$http_www = api_get_path('WEB_COURSE_PATH') . $_course['path'] . '/work';
$cur_dir_path = '';
if (isset ($_GET['curdirpath']) && $_GET['curdirpath'] != '') {
	//$cur_dir_path = preg_replace('#[\.]+/#','',$_GET['curdirpath']); //escape '..' hack attempts
	//now using common security approach with security lib
	$in_course = Security :: check_abs_path($base_work_dir . '/' . $_GET['curdirpath'], $base_work_dir);
	if (!$in_course) {
		$cur_dir_path = "/";
	} else {
		$cur_dir_path = $_GET['curdirpath'];
	}
} elseif (isset ($_POST['curdirpath']) && $_POST['curdirpath'] != '') {
	//$cur_dir_path = preg_replace('#[\.]+/#','/',$_POST['curdirpath']); //escape '..' hack attempts
	//now using common security approach with security lib
	$in_course = Security :: check_abs_path($base_work_dir . '/' . $_POST['curdirpath'], $base_work_dir);
	if (!$in_course) {
		$cur_dir_path = "/";
	} else {
		$cur_dir_path = $_POST['curdirpath'];
	}
} else {
	$cur_dir_path = '/';
}
if ($cur_dir_path == '.') {
	$cur_dir_path = '/';
}
$cur_dir_path_url = urlencode($cur_dir_path);

//prepare a form of path that can easily be added at the end of any url ending with "work/"
$my_cur_dir_path = $cur_dir_path;
if ($my_cur_dir_path == '/') {
	$my_cur_dir_path = '';
} elseif (substr($my_cur_dir_path, -1, 1) != '/') {
	$my_cur_dir_path = $my_cur_dir_path . '/';
}
/*
-----------------------------------------------------------
	Configuration settings
-----------------------------------------------------------
*/
$link_target_parameter = ""; //or e.g. "target=\"_blank\"";
$always_show_tool_options = false;
$always_show_upload_form = false;

if ($always_show_tool_options) {
	$display_tool_options = true;
}
if ($always_show_upload_form) {
	$display_upload_form = true;
}
api_protect_course_script(true);

/*
-----------------------------------------------------------
	More init stuff
-----------------------------------------------------------
*/

if (isset ($_POST['cancelForm']) && !empty ($_POST['cancelForm'])) {
	header('Location: ' . api_get_self() . "?origin=$origin");
	exit ();
}

if (!empty($_POST['submitWork']) || !empty($submitGroupWorkUrl)) {
	// these libraries are only used for upload purpose
	// so we only include them when necessary
	include_once (api_get_path(INCLUDE_PATH) . "lib/fileUpload.lib.php");
	include_once (api_get_path(INCLUDE_PATH) . "lib/fileDisplay.lib.php"); // need format_url function
}
// If the POST's size exceeds 8M (default value in php.ini) the $_POST array is emptied
// If that case happens, we set $submitWork to 1 to allow displaying of the error message
// The redirection with header() is needed to avoid apache to show an error page on the next request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !sizeof($_POST)) {
	if (strstr($_SERVER['REQUEST_URI'], '?')) {
		header('Location: ' . $_SERVER['REQUEST_URI'] . '&submitWork=1');
		exit ();
	} else {
		header('Location: ' . $_SERVER['REQUEST_URI'] . '?submitWork=1');
		exit ();
	}
}
//toolgroup comes from group. the but of tis variable is to limit post to the group of the student
//if (!api_is_course_admin()) {
	if (!empty ($_GET['toolgroup'])) {
		$toolgroup = Database::escape_string($_GET['toolgroup']);
		api_session_register('toolgroup');
	}
//}

//-------------------------------------------------------------------//

//download of an completed folder
if(isset($_GET['action']) && $_GET['action']=="downloadfolder")
{
	include('downloadfolder.inc.php');
}
//-------------------------------------------------------------------//

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
isset($_GET['gradebook'])?$gradebook=$_GET['gradebook']:$gradebook='';
	
if (!empty($_SESSION['toolgroup'])){
	$_clean['toolgroup']=(int)$_SESSION['toolgroup'];
	$group_properties  = GroupManager :: get_group_properties($_clean['toolgroup']);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['toolgroup'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
	
	$url_dir ='';
		$interbreadcrumb[] = array ('url' => $url_dir,'name' => get_lang('StudentPublications'));
	
		//if (!$display_tool_options  && !$display_upload_form)
		//{
		//------interbreadcrumb for the current directory root path
		$dir_array = explode("/", $cur_dir_path);
		$array_len = count($dir_array);
	
		if ($array_len > 0) {
			$url_dir = 'work.php?&curdirpath=/';
			$interbreadcrumb[] = array (
				'url' => $url_dir,
				'name' => get_lang('HomeDirectory'));
		}
	
		$dir_acum = '';
		for ($i = 0; $i < $array_len; $i++) {
			$url_dir = 'work.php?&curdirpath=' . $dir_acum . $dir_array[$i];
			$interbreadcrumb[] = array (
				'url' => $url_dir,
				'name' => $dir_array[$i]
			);
			$dir_acum .= $dir_array[$i] . '/';
		}
		//	}
	
		if ($display_upload_form) {
			$interbreadcrumb[] = array (
				"url" => "work.php",
				"name" => get_lang('UploadADocument'));
		}
	
		if ($display_tool_options) {		
			$interbreadcrumb[] = array (
				"url" => "work.php",
				"name" => get_lang('EditToolOptions'));
		}
	
	Display :: display_header(null);
	
	
} else {


	if (isset($origin) && $origin != 'learnpath') {
		
		if (isset($_GET['gradebook']) and $_GET['gradebook']=='view'){
			$interbreadcrumb[]= array (
				'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
				'name' => get_lang('Gradebook'));
		}		
		$url_dir ='';
		$interbreadcrumb[] = array ('url' => $url_dir,'name' => get_lang('StudentPublications'));
	
		//if (!$display_tool_options  && !$display_upload_form)
		//{
		//------interbreadcrumb for the current directory root path
		$dir_array = explode("/", $cur_dir_path);
		$array_len = count($dir_array);
	
		if ($array_len > 0) {
			$url_dir = 'work.php?&curdirpath=/';
			$interbreadcrumb[] = array (
				'url' => $url_dir,
				'name' => get_lang('HomeDirectory'));
		}
	
		$dir_acum = '';
		for ($i = 0; $i < $array_len; $i++) {
			$url_dir = 'work.php?&curdirpath=' . $dir_acum . $dir_array[$i];
			$interbreadcrumb[] = array (
				'url' => $url_dir,
				'name' => $dir_array[$i]
			);
			$dir_acum .= $dir_array[$i] . '/';
		}
		//	}
	
		if ($display_upload_form) {
			$interbreadcrumb[] = array (
				"url" => "work.php",
				"name" => get_lang('UploadADocument'));
		}
	
		if ($display_tool_options) {		
			$interbreadcrumb[] = array (
				"url" => "work.php",
				"name" => get_lang('EditToolOptions'));
		}
		//--------------------------------------------------
		Display :: display_header(null);
	} else {
		//we are in the learnpath tool
		include api_get_path(INCLUDE_PATH) . 'reduced_header.inc.php';
	}
}


//stats
event_access_tool(TOOL_STUDENTPUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit(false,true); //has to come after display_tool_view_option();
//api_display_tool_title($tool_name);

/*
==============================================================================
		MAIN CODE
==============================================================================
*/



if (!empty ($_POST['changeProperties'])) {
	$query = "UPDATE " . $main_course_table . " SET show_score='" . $uploadvisibledisabled . "' WHERE code='" . $_course['sysCode'] . "'";
	api_sql_query($query, __FILE__, __LINE__);

	$_course['show_score'] = $uploadvisibledisabled;
} else {
	$query = "SELECT * FROM " . $main_course_table . " WHERE code=\"" . $_course['sysCode'] . "\"";
	$result = api_sql_query($query, __FILE__, __LINE__);
	$row = mysql_fetch_array($result);
	$uploadvisibledisabled = $row["show_score"];
}

// introduction section

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';

Display :: display_introduction_section(TOOL_STUDENTPUBLICATION,'left');

$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

/*
-----------------------------------------------------------
	COMMANDS SECTION (reserved for course administrator)
-----------------------------------------------------------
*/
if (api_is_allowed_to_edit(false,true)) {
	/*-------------------------------------------
				DELETE WORK COMMAND
	-----------------------------------------*/
	if (!empty($delete)) {
		if (isset($delete) && $delete == "all") {
			$queryString1 = "SELECT url FROM " . $work_table . "";
			$queryString2 = "DELETE FROM  " . $work_table . "";
						
		} else {
			$queryString1 = "SELECT url FROM  " . $work_table . "  WHERE id = '$delete'";
			$queryString2 = "DELETE FROM  " . $work_table . "  WHERE id='$delete'";						
		}

		$result1 = api_sql_query($queryString1, __FILE__, __LINE__);
		$result2 = api_sql_query($queryString2, __FILE__, __LINE__);

	}
}

	/*-------------------------------------------
	           EDIT COMMAND WORK COMMAND
	  -----------------------------------------*/
	$qualification_number=0;
	if (!empty($edit)) {		
		$sql = "SELECT * FROM  " . $work_table . "  WHERE id='" . $edit . "'";
		$result = api_sql_query($sql, __FILE__, __LINE__);

		if (!empty($result)) {
			$row = mysql_fetch_array($result);
			$workTitle = $row['title'];
			$workAuthor = $row['author'];
			$workDescription = $row['description'];
			$workUrl = $row['url'];
			$qualification_number = $row['qualification'];
		}
	}

	/*-------------------------------------------
		MAKE INVISIBLE WORK COMMAND
	  -----------------------------------------*/

	if (!empty($make_invisible)) {
		if (isset($make_invisible) && $make_invisible == "all") {
			$sql = "ALTER TABLE " . $work_table . "
						        CHANGE accepted accepted TINYINT(1) DEFAULT '0'";

			api_sql_query($sql, __FILE__, __LINE__);

			$sql = "UPDATE  " . $work_table . "
						        SET accepted = 0";

			api_sql_query($sql, __FILE__, __LINE__);
		} else {
			$sql = "UPDATE  " . $work_table . "
						        SET accepted = 0
								WHERE id = '" . $make_invisible . "'";

			api_sql_query($sql, __FILE__, __LINE__);
		}				
	}

	/*-------------------------------------------
		MAKE VISIBLE WORK COMMAND
	  -----------------------------------------*/

	if (!empty($make_visible)) { 
		if (isset($make_visible) && $make_visible == "all") {
			$sql = "ALTER TABLE  " . $work_table . "
						        CHANGE accepted accepted TINYINT(1) DEFAULT '1'";
			api_sql_query($sql, __FILE__, __LINE__);
			$sql = "UPDATE  " . $work_table . "
						        SET accepted = 1";
			api_sql_query($sql, __FILE__, __LINE__);

		} else {
			$sql = "UPDATE  " . $work_table . "
						        SET accepted = 1
								WHERE id = '" . $make_visible . "'";
			api_sql_query($sql, __FILE__, __LINE__);
		}
				
		// update all the parents in the table item propery		
		$list_id=get_parent_directories($my_cur_dir_path);
		for ($i = 0; $i < count($list_id); $i++) {
			api_item_property_update($_course, 'work', $list_id[$i], 'FolderUpdated', $user_id);								
		}			
	}
	
	/*--------------------
	 * Create dir command
	 ---------------------*/	 
	if (!empty ($_REQUEST['new_dir'])) {
		function get_date_from_select($prefix) {
				return $_POST[$prefix.'_year'].'-'.two_digits($_POST[$prefix.'_month']).'-'.two_digits($_POST[$prefix.'_day']).' '.two_digits($_POST[$prefix.'_hour']).':'.two_digits($_POST[$prefix.'_minute']).':00';
		}
		
		$fexpire= get_date_from_select('expires');
		$fend =  get_date_from_select('ends');
		
			include_once (api_get_path(LIBRARY_PATH) . "fileUpload.lib.php");
			$added_slash = (substr($cur_dir_path, -1, 1) == '/') ? '' : '/';	
				
			$directory =disable_dangerous_file($_POST['new_dir']);
			$directory =replace_accents($_POST['new_dir']);
			$dir_name = $cur_dir_path . $added_slash . replace_dangerous_char($directory);				
			$created_dir = create_unexisting_work_directory($base_work_dir, $dir_name);
			
			// we insert here the directory in the table $work_table		
			$dir_name_sql=''; 
			
			if ($ctok==$_POST['sec_token']) {
				if (!empty($created_dir)) {			
					if ($cur_dir_path=='/') {			
						$dir_name_sql = $created_dir;
					} else {
						$dir_name_sql = '/'.$created_dir;			
					}
					
					//----------------inser into agenda----------------------//
				 	$agenda_id = 0;
				 	if(!empty($_POST['type2']) && isset($_POST['add_to_calendar']) && $_POST['add_to_calendar']==1):
						include_once('../calendar/agenda.inc.php');
						include_once('../resourcelinker/resourcelinker.inc.php');						
						isset($course_info)?$course=$course_info:$course=null;							
						$agenda_id = agenda_add_item($course,$_POST['new_dir'],$_POST['new_dir'],date('Y-m-d H:i:s'),get_date_from_select('ends'),null,0);						 
					endif;
										
					$sql_add_publication = "INSERT INTO " . $work_table . " SET " .			
										   "url         = '". $dir_name_sql ."',
									       title        = '',
						                   description 	= '".Database::escape_string($_POST['description'])."',
						                   author      	= '',
										   active		= '0',
										   accepted		= '1',
										   filetype 	= 'folder',
										   post_group_id = '".$toolgroup."',
										   sent_date	= NOW(),
										   qualification	= '".(($_POST['qualification_value']!='') ? Database::escape_string($_POST['qualification_value']) : '') ."',
										   parent_id	= '',
										   qualificator_id	= '',
										   date_of_qualification	= '0000-00-00 00:00:00',
										   session_id   = ".intval($id_session);
		
					api_sql_query($sql_add_publication, __FILE__, __LINE__);
					
					// add the directory	
					$id = Database::insert_id();
					//Folder created
					api_item_property_update($_course, 'work', $id, 'DirectoryCreated', $user_id);		
					Display :: display_normal_message('<span title="' . $created_dir . '">' . get_lang('DirectoryCreated') . '</span>', false);	
					//Database :: escape_string($_REQUEST['make_visible']);
					//if($_POST['type1']==1)
					//$insert_limite		 	
				 	//----------------inser into student_publication_assignment-------------------//		
					//return something like this: 2008-02-45 00:00:00			
					
					if(!empty($_POST['type1']) || !empty($_POST['type2'])) {
																											
										isset($_POST['enable_calification'])?$enable_calification = (int)$_POST['enable_calification']:$enable_calification=null;
										$sql_add_homework = "INSERT INTO $TSTDPUBASG SET " .			
														   "expires_on         = '".((isset($_POST['type1']) && $_POST['type1']==1) ? get_date_from_select('expires') : '0000-00-00 00:00:00'). "',
													        ends_on        = '".((isset($_POST['type2']) && $_POST['type2']==1) ? get_date_from_select('ends') : '0000-00-00 00:00:00')."',
										                    add_to_calendar  = '$agenda_id',
										                    enable_qualification = '".$enable_calification."',
										                    publication_id = '".$id."'";
										api_sql_query($sql_add_homework, __FILE__, __LINE__);		
									    //api_sql_query($sql_add_publication, __FILE__, __LINE__);
										
										$sql_add_publication = "UPDATE ".$work_table." SET "."has_properties  = ".Database::insert_id().", view_properties = 1 ".' where id = '.$id;
										api_sql_query($sql_add_publication, __FILE__, __LINE__);
								
					} else {
			
										$sql_add_homework = "INSERT INTO $TSTDPUBASG SET " .			
														   "expires_on         = '0000-00-00 00:00:00',
													        ends_on        = '0000-00-00 00:00:00',
										                    add_to_calendar  = '$agenda_id',
										                    enable_qualification = '".(isset($_POST['enable_calification'])?(int)$_POST['enable_calification']:'')."',
										                    publication_id = '".$id."'";
										api_sql_query($sql_add_homework, __FILE__, __LINE__);		
									    //api_sql_query($sql_add_publication, __FILE__, __LINE__);
										
										$sql_add_publication = "UPDATE ".$work_table." SET "."has_properties  = ".Database::insert_id().", view_properties = 0 ".' where id = '.$id;
										api_sql_query($sql_add_publication, __FILE__, __LINE__);
								
					}
				 	
				 	if(isset($_POST['make_calification']) && $_POST['make_calification']==1) {
	
					 	require_once('../gradebook/lib/be/gradebookitem.class.php');
					 	require_once('../gradebook/lib/be/evaluation.class.php');
					 	require_once('../gradebook/lib/be/abstractlink.class.php');
					 	require_once('../gradebook/lib/gradebook_functions.inc.php');
						
						$resource_name = (empty($_POST['qualification_name'])) ? $_POST['new_dir'] : $_POST['qualification_name'];
					 	add_resource_to_course_gradebook(api_get_course_id(), 3, $id, Database::escape_string($resource_name),$_POST['weight'], $_POST['qualification_value'], Database::escape_string($_POST['description']),time(), 1,api_get_session_id());
		
					 	
				 	}				 	
					
					//-----------------end features---------------------------//		
					
					// update all the parents in the table item propery
					$list_id=get_parent_directories($my_cur_dir_path);
											
					for ($i = 0; $i < count($list_id); $i++) {
						api_item_property_update($_course, 'work', $list_id[$i], 'FolderUpdated', $user_id);								
					}			
					//-------------------------------------------------------------------------------
		
					
					//uncomment if you want to enter the created dir
					//$curdirpath = $created_dir;
					//$curdirpathurl = urlencode($curdirpath);
				} else {
					Display :: display_error_message(get_lang('CannotCreateDir'));
				}
			}	
	}
					
	/* -------------------
	 * Delete dir command
	 --------------------*/
	if (!empty ($_REQUEST['delete_dir'])) {		
		$delete_directory=$_REQUEST['delete_dir'];
		$id=$_REQUEST['delete2'];
		del_dir($base_work_dir . '/', $delete_directory,$id);		
		Display :: display_normal_message($delete_directory . ' ' . get_lang('DirDeleted'));
	}
	if (!empty ($_REQUEST['delete2'])) {		
		$delete_2=$_REQUEST['delete2'];
		// gets calendar_id from student_publication_assigment
		$sql = "SELECT add_to_calendar FROM $TSTDPUBASG WHERE publication_id ='$delete_2'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$calendar_id = Database::fetch_row($res);				
		// delete from agenda if it exists
		if (!empty($calendar_id[0])) {
		$t_agenda   = Database::get_course_table(TABLE_AGENDA);
		$sql = "DELETE FROM $t_agenda WHERE id ='".$calendar_id[0]."'";
		api_sql_query($sql,__FILE__,__LINE__);
		}
		$sql2="DELETE FROM $TSTDPUBASG WHERE publication_id ='$delete_2'";
		$result2 = api_sql_query($sql2, __FILE__, __LINE__);		
		$sql3="DELETE FROM $t_gradebook_link WHERE course_code='$course_code' AND ref_id='$delete_2'";
		$result3 = api_sql_query($sql3, __FILE__, __LINE__);
	}
	 
	/* ----------------------
	 * Move file form request
	 ----------------------- */	 
	if (!empty ($_REQUEST['move'])) {		 
		$folders = array();
		$sql = "SELECT url FROM $work_table  WHERE url LIKE '/%' AND post_group_id = '".(empty($_SESSION['toolgroup'])?0:$_SESSION['toolgroup'])."'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		while($folder = Database::fetch_array($res)) {			
		$folders[] = substr($folder['url'],1,(strlen($folder['url'])-1));
		}	
		Display :: display_normal_message(build_work_move_to_selector($folders, $cur_dir_path, $_REQUEST['move']), false);
	}
	/* ------------------
	 * Move file command
	 ------------------- */
	if (isset ($_POST['move_to']) && isset ($_POST['move_file'])) {
		include_once (api_get_path(LIBRARY_PATH) . "/fileManage.lib.php");
		$move_to = $_POST['move_to'];
		if ($move_to == '/' or empty ($move_to)) {
			$move_to = '';
		} elseif (substr($move_to, -1, 1) != '/') {
			$move_to = $move_to . '/';
		}

		//security fix: make sure they can't move files that are not in the document table
		if ($path = get_work_path($_POST['move_file'])) {
			//echo "got path $path";
			//Display::display_normal_message('We want to move '.$_POST['move_file'].' to '.$_POST['move_to']);
			if (move($course_dir . '/' . $path, $base_work_dir . '/' . $move_to)) {
				//update db
				update_work_url($_POST['move_file'], 'work/' . $move_to);
				//set the current path
				$cur_dir_path = $move_to;
				$cur_dir_path_url = urlencode($move_to);
				
				// update all the parents in the table item propery
				$list_id=get_parent_directories($cur_dir_path);						
				for ($i = 0; $i < count($list_id); $i++) {					
					api_item_property_update($_course, 'work', $list_id[$i], 'FolderUpdated', $user_id);								
				}		
				
				Display :: display_normal_message(get_lang('DirMv'));
			} else {
				Display :: display_error_message(get_lang('Impossible'));
			}
		} else {
			Display :: display_error_message(get_lang('Impossible'));
		}
	}

/*-----------------------------------------------------------
	COMMANDS SECTION (reserved for others - check they're authors each time)
-----------------------------------------------------------
*/
else {
	$iprop_table = Database :: get_course_table(TABLE_ITEM_PROPERTY);
	$user_id = api_get_user_id();
	
	/*-------------------------------------------
				DELETE WORK COMMAND
	-----------------------------------------*/
	if ($delete) {
		if ($delete == "all") {
			/*not authorized to this user */
		} else {
			//Get the author ID for that document from the item_property table
			$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=" . mysql_real_escape_string($delete);
			$author_qry = api_sql_query($author_sql, __FILE__, __LINE__);
			
			if (Database :: num_rows($author_qry) == 1) {
				//we found the current user is the author
				$queryString1 = "SELECT url FROM  " . $work_table . "  WHERE id = '$delete'";
				$queryString2 = "DELETE FROM  " . $work_table . "  WHERE id='$delete'";
							
				$result1 = api_sql_query($queryString1, __FILE__, __LINE__);
				$result2 = api_sql_query($queryString2, __FILE__, __LINE__);
				
				if ($result1) {
					api_item_property_update($_course, 'work', $delete, 'DocumentDeleted', $user_id);
					while ($thisUrl = mysql_fetch_array($result1)) {
						// check the url really points to a file in the work area
						// (some work links can come from groups area...)
						if (substr(dirname($thisUrl['url']), -4) == "work") {
							@ unlink($currentCourseRepositorySys . "work/" . $thisWork);
						}
					}
				}
			}
		}
	}
	/*-------------------------------------------
	           EDIT COMMAND WORK COMMAND
	  -----------------------------------------*/
	  
	if ($edit) {		
		//Get the author ID for that document from the item_property table
		$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=" . $edit;
		$author_qry = api_sql_query($author_sql, __FILE__, __LINE__);
		if (Database :: num_rows($author_qry) == 1) {
			//we found the current user is the author
			$sql = "SELECT * FROM  " . $work_table . "  WHERE id='" . $edit . "'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			if ($result ) {
				$row = mysql_fetch_array($result);
				$workTitle = $row['title'];
				$workAuthor = $row['author'];
				$workDescription = $row['description'];
				$workUrl = $row['url'];
				$qualification_number = $row['qualification'];
			}
		}
	}
	
}

/*
==============================================================================
		FORM SUBMIT PROCEDURE
==============================================================================
*/

$error_message = "";

if ($ctok==$_POST['sec_token']) { //check the token inserted into the form
	if (!empty($_POST['submitWork']) && !empty($is_course_member)) {
		if (!empty($_FILES['file']['size'])) {
			$updir = $currentCourseRepositorySys . 'work/'; //directory path to upload
	
			// Try to add an extension to the file if it has'nt one
			$new_file_name = add_ext_on_mime(stripslashes($_FILES['file']['name']), $_FILES['file']['type']);
	
			// Replace dangerous characters
			$new_file_name = replace_dangerous_char($new_file_name, 'strict');
	
			// Transform any .php file in .phps fo security
			$new_file_name = php2phps($new_file_name);
			//filter extension
			if (!filter_extension($new_file_name)) {
				Display :: display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
				$succeed = false;
			} else {
				if (!$title) {
					$title = $_FILES['file']['name'];
				}
				//if (!$authors) {
				$authors = $currentUserFirstName . " " . $currentUserLastName;
				//}
	
				// compose a unique file name to avoid any conflict
	
				$new_file_name = uniqid('') . $new_file_name;
	
				if (isset ($_SESSION['toolgroup'])) {
					$post_group_id = $_SESSION['toolgroup'];
				} else {
					$post_group_id = '0';
				}
				
				//if we come from the group tools the groupid will be saved in $work_table
	
				@move_uploaded_file($_FILES['file']['tmp_name'], $updir . $my_cur_dir_path . $new_file_name);
	
				$url = "work/" . $my_cur_dir_path . $new_file_name;
							
				$result = api_sql_query("SHOW FIELDS FROM " . $work_table . " LIKE 'sent_date'", __FILE__, __LINE__);
				
				if (!Database::num_rows($result)) {
					api_sql_query("ALTER TABLE " . $work_table . " ADD sent_date DATETIME NOT NULL");
				}			
				$current_date = date('Y-m-d H:i:s');
				
				$parent_id = '';
				$active = '';
				$sql = api_sql_query('SELECT id FROM '.Database::get_course_table(TABLE_STUDENT_PUBLICATION).' WHERE url = '."'/".Database::escape_string($_GET['curdirpath'])."' AND filetype='folder' LIMIT 1");
				if(Database::num_rows($sql) > 0 ) {
					$dir_row = Database::fetch_array($sql);
					$parent_id = $dir_row['id'];
				}				
				$sql_add_publication = "INSERT INTO " . $work_table . " SET " .
										       "url         = '" . $url . "',
										       title       = '" . $title . "',
							                   description = '" . $description . "',
							                   author      = '" . $authors . "',
											   active		= '" . $active . "',
											   accepted		= '" . (api_is_allowed_to_edit()?$uploadvisibledisabled:(!$uploadvisibledisabled)) . "',
											   post_group_id = '" . $post_group_id . "',
											   sent_date	=  '".$current_date ."',
											   parent_id 	=  '".$parent_id ."' ,
	                                           session_id = ".intval($id_session);
											   		
	
				api_sql_query($sql_add_publication, __FILE__, __LINE__);
	
				$Id = Database::insert_id();
				api_item_property_update($_course, 'work', $Id, 'DocumentAdded', $user_id);
				$succeed = true;
				
				// update all the parents in the table item propery
				$list_id=get_parent_directories($my_cur_dir_path);						
				for ($i = 0; $i < count($list_id); $i++) {
					api_item_property_update($_course, 'work', $list_id[$i], 'FolderUpdated', $user_id);								
				}	
				
			}
		} elseif ($newWorkUrl) {	
				if (isset ($_SESSION['toolgroup'])) {
					$post_group_id = $_SESSION['toolgroup'];
				} else {
					$post_group_id = '0';
				}
			
			/*
			 * SPECIAL CASE ! For a work coming from another area (i.e. groups)
			 */	 
			$url = str_replace('../../' . $_course['path'] . '/', '', $newWorkUrl);
	
			if (!$title) {		
				$title = basename($workUrl);
			}
	
			$result = api_sql_query("SHOW FIELDS FROM " . $work_table . " LIKE 'sent_date'", __FILE__, __LINE__);
	
			if (!Database::num_rows($result)) {
				api_sql_query("ALTER TABLE " . $work_table . " ADD sent_date DATETIME NOT NULL");
			}
	
				$sql = "INSERT INTO  " . $work_table . "
					        	SET url        	= '" . $url . "',
					            title       	= '" . $title . "',
					            description 	= '" . $description . "',
					            author      	= '" . $authors . "',					 
							    post_group_id = '".$post_group_id."',
					            sent_date    	= NOW(),
					            session_id = ".intval($id_session);
	
			api_sql_query($sql, __FILE__, __LINE__);
	
			$insertId = Database::insert_id();
			api_item_property_update($_course, 'work', $insertId, 'DocumentAdded', $user_id);
			$succeed = true;
			
			// update all the parents in the table item propery
			$list_id=get_parent_directories($my_cur_dir_path);						
			for ($i = 0; $i < count($list_id); $i++) {
				api_item_property_update($_course, 'work', $list_id[$i], 'FolderUpdated', $user_id);								
			}	

		}

		/*
		 * SPECIAL CASE ! For a work edited
		 */
	
		else {
			//Get the author ID for that document from the item_property table
			$is_author = false;
			if ($id<>'') {
				$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=" . mysql_real_escape_string($id);
				
				$author_qry = api_sql_query($author_sql, __FILE__, __LINE__);
				if (Database :: num_rows($author_qry) == 1) {
					$is_author = true;
				}
			} else {
				Display::display_error_message(get_lang('IsNotPosibleSaveTheDocument'));
			}
			if ($id && ($is_allowed_to_edit or $is_author)) {
				if (!$title) {
					$title = basename($newWorkUrl);
				}
	
				if($is_allowed_to_edit && ($_POST['qualification']!='')) {
					$add_to_update = ',qualificator_id ='."'".api_get_user_id()."',";
					$add_to_update .= 'qualification ='."'".Database::escape_string($_POST['qualification'])."',";
					$add_to_update .= 'date_of_qualification ='."'".date('Y-m-d H:i:s')."'";
				} 
				
				if ((int)$_POST['qualification'] > (int)$_POST['qualification_over']) {				
					Display::display_error_message(get_lang('QualificationMustNotBeMoreThanQualificationOver'));				
				} else {			
					$sql = "UPDATE  " . $work_table . "
					        SET	title       = '" . $title . "',
					            description = '" . $description . "'
					            ".$add_to_update."
					        WHERE id    = '$id'";
				api_sql_query($sql, __FILE__, __LINE__);				
				}																									
							        			
				$insertId = $id;			
				api_item_property_update($_course, 'work', $insertId, 'DocumentUpdated', $user_id);
				$succeed = true;			
			} else {
				$error_message = get_lang('TooBig');
			}
		}
	} 
}
if (!empty($_POST['submitWork']) && !empty($succeed) && !$id) {
	//last value is to check this is not "just" an edit
	//YW Tis part serve to send a e-mail to the tutors when a new file is sent
	$send = api_get_course_setting('email_alert_manager_on_new_doc');
	
	if ($send > 0) {
		// Lets predefine some variables. Be sure to change the from address!		

		$emailto = array ();
		if (empty ($id_session)) {
			$sql_resp = 'SELECT u.email as myemail FROM ' . $table_course_user . ' cu, ' . $table_user . ' u WHERE cu.course_code = ' . "'" . api_get_course_id() . "'" . ' AND cu.status = 1 AND u.user_id = cu.user_id';
			$res_resp = api_sql_query($sql_resp, __FILE__, __LINE__);
			while ($row_email = Database :: fetch_array($res_resp)) {
				if (!empty ($row_email['myemail'])) {
					$emailto[$row_email['myemail']] = $row_email['myemail'];
				}
			}
		} else {
			// coachs of the session
			$sql_resp = 'SELECT user.email as myemail 
									FROM ' . $table_session . ' session
									INNER JOIN ' . $table_user . ' user
										ON user.user_id = session.id_coach
									WHERE session.id = ' . intval($id_session);
			$res_resp = api_sql_query($sql_resp, __FILE__, __LINE__);
			while ($row_email = Database :: fetch_array($res_resp)) {
				if (!empty ($row_email['myemail'])) {
					$emailto[$row_email['myemail']] = $row_email['myemail'];
				}
			}

			//coach of the course
			$sql_resp = 'SELECT user.email as myemail 
									FROM ' . $table_session_course . ' session_course
									INNER JOIN ' . $table_user . ' user
										ON user.user_id = session_course.id_coach
									WHERE session_course.id_session = ' . intval($id_session);
			$res_resp = api_sql_query($sql_resp, __FILE__, __LINE__);
			while ($row_email = Database :: fetch_array($res_resp)) {
				if (!empty ($row_email['myemail'])) {
					$emailto[$row_email['myemail']] = $row_email['myemail'];
				}
			}

		}
		
		if (count($emailto) > 0) {
			$emailto = implode(',', $emailto);
			$emailfromaddr = get_setting('emailAdministrator');
			$emailfromname = get_setting('siteName');
			$emailsubject = "[" . get_setting('siteName') . "] ";
			$sender_name = get_setting('administratorName').' '.get_setting('administratorSurname');
		    $email_admin = get_setting('emailAdministrator');
			// The body can be as long as you wish, and any combination of text and variables
						
			$emailbody = get_lang('SendMailBody').' '.api_get_path(WEB_CODE_PATH)."work/work.php?".api_get_cidreq()."&amp;curdirpath=".$my_cur_dir_path." (" . stripslashes($title) . ")\n\n" . get_setting('administratorName') . " " . get_setting('administratorSurname') . "\n" . get_lang('Manager') . " " . get_setting('siteName') . "\n" . get_lang('Email') . " : " . get_setting('emailAdministrator');

			// Here we are forming one large header line
			// Every header must be followed by a \n except the last															
			@api_mail('', $emailto, $emailsubject, $emailbody, $sender_name,$email_admin);
			
		}
	}
	$message = get_lang('DocAdd');
	if ($uploadvisibledisabled && !$is_allowed_to_edit) {
		$message .= "<br />" . get_lang('_doc_unvisible') . "<br />";
	}

	//stats
	if (!$Id) {
		$Id = $insertId;
	}
	event_upload($Id);
	$submit_success_message = $message . "<br />\n";
	Display :: display_normal_message($submit_success_message, false);
}

/*=======================================
	 Display links to upload form and tool options
  =======================================
*/
$has_expired = false;
$has_ended = false;
isset($_GET['curdirpath'])?$curdirpath=Database::escape_string($_GET['curdirpath']):$curdirpath='';
$sql = api_sql_query('SELECT description,id FROM '.Database :: get_course_table(TABLE_STUDENT_PUBLICATION).' WHERE filetype = '."'folder'".' and has_properties != '."''".' and url = '."'/".$curdirpath."'".' LIMIT 1',__FILE__,__LINE__);
$is_special = mysql_num_rows($sql);
if($is_special > 0):
	$is_special = true;
	define('IS_ASSIGNMENT',1);
	$publication = mysql_fetch_array($sql);
	$sql = api_sql_query('SELECT * FROM '.$TSTDPUBASG.' WHERE publication_id = '.(string)$publication['id'].' LIMIT 1',__FILE__,__LINE__);
	$homework = mysql_fetch_array($sql);
	
	if($homework['expires_on']!='0000-00-00 00:00:00' || $homework['ends_on']!='0000-00-00 00:00:00'):		
		$time_now = convert_date_to_number(date('Y-m-d H:i:s'));	
		$time_expires = convert_date_to_number($homework['expires_on']);
		$time_ends = convert_date_to_number($homework['ends_on']);
		$difference = $time_expires - $time_now;
		$difference2 = $time_ends - $time_now;
		if($homework['expires_on']!='0000-00-00 00:00:00' && $difference < 0) $has_expired = true;
		if($homework['ends_on']!='0000-00-00 00:00:00' && $difference2 < 0) $has_ended = true;
		if($homework['expires_on']=='0000-00-00 00:00:00'){ $not_ends_on=true; }
		if (!$not_ends_on) {
			define('ASSIGNMENT_EXPIRES',$time_expires);
		}
		if(!empty($publication['description'])){
			Display :: display_normal_message($publication['description']);
		}
						
		$ends_on = ucfirst(format_locale_date($dateFormatLong,strtotime($homework['ends_on']))).' ';
		$ends_on .= ucfirst(strftime($timeNoSecFormat,strtotime($homework['ends_on'])));
		$expires_on = ucfirst(format_locale_date($dateFormatLong,strtotime($homework['expires_on']))).' ';
		$expires_on .= ucfirst(strftime($timeNoSecFormat,strtotime($homework['expires_on'])));		
		if($has_ended) {
			Display :: display_error_message(get_lang('EndDateAlreadyPassed').' '.$ends_on);	
			display_action_links($cur_dir_path, $always_show_tool_options,true);
		} elseif($has_expired) {
			Display :: display_warning_message(get_lang('ExpiryDateAlreadyPassed').' '.$expires_on);	
			display_action_links($cur_dir_path, $always_show_tool_options,$always_show_upload_form);
		} else {
			if (!$not_ends_on) {
			Display :: display_normal_message(get_lang('ExpiryDateToSendWorkIs').' '.$expires_on);
			}			
			display_action_links($cur_dir_path, $always_show_tool_options, $always_show_upload_form);
		}
	else:
		display_action_links($cur_dir_path, $always_show_tool_options, $always_show_upload_form);
	endif;
else:
	display_action_links($cur_dir_path, $always_show_tool_options, $always_show_upload_form);
endif;
/*=======================================
	 Display form to upload document
  =======================================*/

if ($is_course_member) {
	if (($display_upload_form || $edit)&&!$has_ended) {
		if ($edit) {
			//Get the author ID for that document from the item_property table
			$is_author = false;
			$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=" . $edit;
			$author_qry = api_sql_query($author_sql, __FILE__, __LINE__);
			if (Database :: num_rows($author_qry) == 1) {
				$is_author = true;
			}
		}

		//require_once (api_get_path(LIBRARY_PATH) . 'formvalidator/FormValidator.class.php');
		require_once (api_get_path(LIBRARY_PATH) . 'fileDisplay.lib.php');

		$form = new FormValidator('form', 'POST', api_get_self() . "?curdirpath=" . rtrim(Security :: remove_XSS($cur_dir_path),'/') . "&gradebook=".$_GET['gradebook']."&origin=$origin", '', 'enctype="multipart/form-data"');

		if (!empty ($error_message)) {
			Display :: display_error_message($error_message);
		}
		if ($submitGroupWorkUrl) {
			// For user comming from group space to publish his work
			$realUrl = str_replace($_configuration['root_sys'], $_configuration['root_web'], str_replace("\\", "/", realpath($submitGroupWorkUrl)));
			$form->addElement('hidden', 'newWorkUrl', $submitGroupWorkUrl);
			$text_document = & $form->addElement('text', 'document', get_lang("Document"));
			$defaults["document"] = '<a href="' . format_url($submitGroupWorkUrl) . '">' . $realUrl . '</a>';
			$text_document->freeze();
		} elseif ($edit && ($is_allowed_to_edit or $is_author)) {
			$workUrl = $currentCourseRepositoryWeb . $workUrl;
			$form->addElement('hidden', 'id', $edit);

			$html = '<div class="row">
								<div class="label">' . get_lang("Document") . '
								</div>
								<div class="formw">
									<a href="' . $workUrl . '">' . get_lang("ClickHereToDownloadTheFile") . '</a>
								</div>
					</div>';
			$form->addElement('html', $html);
		} else {
			// else standard upload option 
			$form->addElement('file', 'file', get_lang('DownloadFile'), 'size="40" onchange="updateDocumentTitle(this.value)"');
		}

		$titleWork = $form->addElement('text', 'title', get_lang("TitleWork"), 'id="file_upload"  style="width: 350px;"');
		$defaults["title"] = ($edit ? stripslashes($workTitle) : stripslashes($title));

		//Removed to avoid incoherences
		//$titleAuthors = $form->addElement('text', 'authors', get_lang("Authors"), 'style="width: 350px;"');

		//if (empty ($authors)) {
		$authors = $_user['firstName'] . " " . $_user['lastName'];
		//}

		//$defaults["authors"] = ($edit ? stripslashes($workAuthor) : stripslashes($authors));
		$titleAuthors = $form->addElement('textarea', 'description', get_lang("Description"), 'style="width: 350px; height: 60px;"');
		$defaults["description"] = ($edit ? stripslashes($workDescription) : stripslashes($description));
				
		if($is_allowed_to_edit && !empty($edit) && !empty($parent_id)) {			
			// Get qualification from parent_id that'll allow the validation qualification over
			$sql = "SELECT qualification FROM $work_table WHERE id='$parent_id'";			
			$result = api_sql_query($sql,__FILE__,__LINE__);
			$row = Database::fetch_array($result);
			$qualification_over = $row['qualification'];
			$form->addElement('text', 'qualification', get_lang('Qualification'),'size="10"');
			$form->addElement('html','<div style="margin-left:20%">'.get_lang('QualificationOver').'&nbsp;:&nbsp;'.$qualification_over.'</div>');
			$form->addElement('hidden', 'qualification_over', $qualification_over);
		}		
		
		$defaults['qualification'] = $qualification_number;//($edit ? stripslashes($qualification_number) : stripslashes($qualification_number));		
		$form->addElement('hidden', 'active', 1);
		$form->addElement('hidden', 'accepted', 1);
		$form->addElement('hidden', 'sec_token', $stok);
		
		// fix the Ok button when we see the tool in the learn path
		if ($origin== 'learnpath') {
			$form->addElement('html', '<div style="margin-left:137px">');		
			$form->addElement('submit', 'submitWork', get_lang('Ok'));		
			$form->addElement('html', '</div>');
		} else {
			$form->addElement('submit', 'submitWork', get_lang('Ok'));
		}
		
		if (!empty($_POST['submitWork']) || $edit) {
			$form->addElement('submit', 'cancelForm', get_lang('Cancel'));
		}

		$form->add_real_progress_bar('uploadWork', 'DownloadFile');
		$form->setDefaults($defaults);
		echo '<br /><br />';
		$form->display();			
	
			

	}
	
function make_select($name,$values,$checked='') {
	$output = '<select name="'.$name.'" id="'.$name.'">';
 	foreach($values as $key => $value) {
 		$output .= '<option value="'.$key.'" '.(($checked==$key)?'selected="selected"':'').'>'.$value.'</option>';
 	}
 	$output .= '</select>';	
 	return $output;
}
	
function make_checkbox($name,$checked='') {
		return '' .
			'<input type="checkbox" value="1" name="'.$name.'" '.((!empty($checked))?'checked="checked"':'').'/>';
	}

function draw_date_picker($prefix,$default='') {
	//$default = 2008-10-01 10:00:00
	if(empty($default)) {
	$default = date('Y-m-d H:i:s');	
	}
	$parts = split(' ',$default);
	list($d_year,$d_month,$d_day) = split('-',$parts[0]);
	list($d_hour,$d_minute) = split(':',$parts[1]);
	
	$month_list = array(
	1=>get_lang('JanuaryLong'),
	2=>get_lang('FebruaryLong'),
	3=>get_lang('MarchLong'),
	4=>get_lang('AprilLong'),
	5=>get_lang('MayLong'),
	6=>get_lang('JuneLong'),
	7=>get_lang('JulyLong'),
	8=>get_lang('AugustLong'),
	9=>get_lang('SeptemberLong'),
	10=>get_lang('OctoberLong'),
	11=>get_lang('NovemberLong'),
	12=>get_lang('DecemberLong')
	);
		
	$minute = range(10,59);
	array_unshift($minute,'00','01','02','03','04','05','06','07','08','09');
	$date_form = make_select($prefix.'_day', array_combine(range(1,31),range(1,31)), $d_day);
	$date_form .= make_select($prefix.'_month', $month_list, $d_month);
	$date_form .= make_select($prefix.'_year', array( $d_year=> $d_year, $d_year+1=>$d_year+1), $d_year).'&nbsp;&nbsp;&nbsp;&nbsp;';
	$date_form .= make_select($prefix.'_hour', array_combine(range(1,24),range(1,24)), $d_hour).' : ';
	$date_form .= make_select($prefix.'_minute', $minute, $d_minute);
	return $date_form;
}

	//show them the form for the directory name
	if (isset ($_REQUEST['createdir']) && $is_allowed_to_edit) {
		//create the form that asks for the directory name
		$new_folder_text = '<br /><br /><form name="form1"  method="POST">';
		$new_folder_text .= '<input type="hidden" name="curdirpath" value="' . Security :: remove_XSS($cur_dir_path) . '"/>';
		$new_folder_text .= '<input type="hidden" name="sec_token" value="'.$stok.'" />';
		$new_folder_text .= '<div id="msg_error1" style="display:none;color:red"></div>';
		$new_folder_text .= get_lang('NewDir') . ' ';		
		$new_folder_text .= '<input type="text" name="new_dir" onfocus="document.getElementById(\'msg_error1\').style.display=\'none\';"/>';
		$new_folder_text .= '<input type="button" name="create_dir" onClick="validate();" value="' . get_lang('Ok') . '"/>';
		//new additional fields inside the "if condition" just to agroup
		if(true):

		$addtext = '<div style="padding:10px">'.get_lang('Description').'<br /><textarea name="description" rows="4" cols="70"></textarea></div>';
				
		$addtext .= '<div style="align:left"> <div class="label">&nbsp;</div> <div class="formw"> <a href="javascript://" onclick=" return plus();"><span id="plus">&nbsp;<img src="../img/nolines_plus.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'</span></a>';
		$addtext .='</div> </div>';
			
		// Random questions
		$addtext .='<br /><div id="options" style="display: none;">';		


		$addtext .= '<div style="padding:10px">';		
		$addtext .= '<fieldset style="padding:5px"><legend>'.get_lang('QualificationOfAssignment').'</legend>';				
		$addtext .= '<table cellspacing="0" cellpading="0" border="0"><tr>';
		$addtext .= '<td colspan="2">&nbsp;&nbsp;'.get_lang('QualificationNumberOver').'&nbsp;';		
		$addtext .= '<input type="text" name="qualification_value" value="" size="5"/></td><tr><td colspan="2">';		
		$addtext .= '<input type="checkbox" value="1" name="make_calification" onclick="if(this.checked==true){document.getElementById(\'option1\').style.display=\'block\';}else{document.getElementById(\'option1\').style.display=\'none\';}"/>'.get_lang('MakeQualifiable').'</td></tr><tr>';								
		$addtext .= '<td colspan="2"><div id="option1" style="display:none">';
		$addtext .= '<div id="msg_error_weight" style="display:none;color:red"></div>';
		$addtext .=	'&nbsp;&nbsp;'.get_lang('WeightInTheGradebook').'&nbsp;';				
		$addtext .= '<input type="text" name="weight" value="" size="5" onfocus="document.getElementById(\'msg_error_weight\').style.display=\'none\';"/></div></td></tr>';
		$addtext .= '</tr></table>';				
		$addtext .= '</fieldset><br />';		
		$addtext .= '<fieldset style="padding:5px"><legend>'.get_lang('DatesAvailables').'</legend>';
		$addtext .= '* <input type="checkbox" value="1" name="type1" onclick="if(this.checked==true){document.getElementById(\'option2\').style.display=\'block\';}else{document.getElementById(\'option2\').style.display=\'none\';}"/>'.get_lang('EnableExpiryDate').'';		
		$addtext .= '&nbsp;&nbsp;&nbsp;<span id="msg_error2" style="display:none;color:red"></span>';		
		$addtext .= '&nbsp;&nbsp;&nbsp;<span id="msg_error3" style="display:none;color:red"></span>';	
		$addtext .= '<div id="option2" style="padding:4px;display:none">&nbsp;&nbsp;';			
		$addtext .= draw_date_picker('expires').'</div>';				
		$addtext .= '<br />* <input type="checkbox" value="1" name="type2" onclick="if(this.checked==true){document.getElementById(\'option3\').style.display=\'block\';}else{document.getElementById(\'option3\').style.display=\'none\';}"/>'.get_lang('EnableEndDate').'';		
		$addtext .= '<div id="option3" style="padding:4px;display:none">';
		$addtext .= '&nbsp;&nbsp;&nbsp;<div id="msg_error4" style="display:none;color:red"></div>';
		$addtext .= draw_date_picker('ends').'<br />';
		$addtext .= '&nbsp;&nbsp;'.make_checkbox('add_to_calendar').get_lang('AddToCalendar').'</div>';
		$addtext .= '</fieldset>';				
		$addtext .= '</div>';
		$addtext .= '</div>';		
		$new_folder_text .= $addtext;
		endif;
		
		$new_folder_text .= '<input type="button" name="create_dir" onClick="validate();" value="' . get_lang('Ok') . '"/>';
		
		$new_folder_text .= '<br /><br /></form>';
		//show the form
		echo $new_folder_text;
	}
} else {
	//the user is not registered in this course
	echo "<p style=\"font-weight:bold\">" . get_lang("MustBeRegisteredUser") . "</p>";
}

/*
==============================================================================
		Display of tool options
==============================================================================
*/
if ($display_tool_options) {
	display_tool_options($uploadvisibledisabled, $origin, $base_work_dir, $cur_dir_path, $cur_dir_path_url);
}

/*
==============================================================================
		Display list of student publications
==============================================================================
*/
if ($cur_dir_path == '/') {
	$my_cur_dir_path = '';
} else {
	$my_cur_dir_path = $cur_dir_path;
}

if (!$display_upload_form && !$display_tool_options) {
	$add_query = '';	
	$sql = "SELECT concat(user.firstname,' ',user.lastname) FROM $table_user user, $table_course_user course_user
			  WHERE course_user.user_id=user.user_id AND course_user.course_code='".api_get_course_id()."' AND course_user.status='1'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$admin_course = '';
	while($row = Database::fetch_row($res)) {
		$admin_course .='\''.$row[0].'\','; 	
	}
	if(!$is_allowed_to_edit && $is_special==true) { 
		$add_query = ' AND author IN('.$admin_course.'\''.$_user['firstName'].' '.$_user['lastName'].'\')';
	}
	if($is_allowed_to_edit && $is_special==true) { 	
	
		if (!empty($_REQUEST['filter'])) {
			switch($_REQUEST['filter']) {
			 case 1: 
					$add_query = ' AND qualification = '."''";
					break;
		 	 case 2:  
					$add_query = ' AND qualification != '."''";
					break;
			 case 3:  
					$add_query = ' AND sent_date < '."'".$homework['expires_on']."'";
					break;
			 default:
			 		$add_query = '';
			}	
		}
		isset($_GET['cidreq'])?$cidreq = Security::Remove_XSS($_GET['cidreq']):$cidreq='';
		isset($_GET['curdirpath'])?$curdirpath = Security::Remove_XSS($_GET['curdirpath']):$curdirpath='';
		isset($_REQUEST['filter'])?$filter = (int)$_REQUEST['filter']:$filter='';
		$form_filter = '<form method="post" action="'.api_get_self().'?cidReq='.$cidreq.'&curdirpath='.$curdirpath.'">';
		$form_filter .= make_select('filter',array(0=>get_lang('SelectAFilter'),1=>get_lang('FilterByNotRevised'),2=>get_lang('FilterByRevised'),3=>get_lang('FilterByNotExpired')),$filter);
		$form_filter .= '<input type="submit" value="'.get_lang('FilterAssignments').'"</form>';
		echo $form_filter;
	} 
	display_student_publications_list($base_work_dir . '/' . $my_cur_dir_path, 'work/' . $my_cur_dir_path, $currentCourseRepositoryWeb, $link_target_parameter, $dateFormatLong, $origin,$add_query);
}


/*
==============================================================================
		Footer
==============================================================================
*/

if ($origin != 'learnpath') {
	//we are not in the learning path tool
	Display :: display_footer();
}
