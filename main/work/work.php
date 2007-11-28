<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.studentpublications
* 	@author Thomas, Hugues, Christophe - original version
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University - ability for course admins to specify wether uploaded documents are visible or invisible by default.
* 	@author Roan Embrechts, code refactoring and virtual course support
* 	@author Frederic Vauthier, directories management
*  	@version $Id: work.php 13804 2007-11-28 06:08:00Z yannoo $
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
$language_file = array('work','document','admin');

// Section (for the tabs)
$this_section=SECTION_COURSES;

// @todo why is this needed?
//session
if(isset($_GET['id_session']))
{
	$_SESSION['id_session'] = $_GET['id_session'];
}

$htmlHeadXtra[] = '<script>

function updateDocumentTitle(value){

	var temp = value.indexOf("/");
	
	//linux path
	if(temp!=-1){
		var temp=value.split("/");
	}
	else{
		var temp=value.split("\\\");
	}
	
	document.getElementById("file_upload").value=temp[temp.length-1];
}
</script>
';

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require('../inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH) . "course.lib.php");
require_once(api_get_path(LIBRARY_PATH) . "debug.lib.inc.php");
require_once(api_get_path(LIBRARY_PATH) . "events.lib.inc.php");
require_once(api_get_path(LIBRARY_PATH) . "security.lib.php");
require_once('work.lib.php');


/*
-----------------------------------------------------------
	Table definitions
-----------------------------------------------------------
*/
$main_course_table 	= Database::get_main_table(TABLE_MAIN_COURSE);
$work_table 		= Database::get_course_table(TABLE_STUDENT_PUBLICATION);
$iprop_table 		= Database::get_course_table(TABLE_ITEM_PROPERTY);

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$tool_name 			= get_lang('StudentPublications');
$user_id = api_get_user_id();
$course_code = $_course['sysCode'];
$is_course_member = $is_courseMember || api_is_platform_admin();
$currentCourseRepositorySys =  api_get_path(SYS_COURSE_PATH) . $_course["path"]."/";
$currentCourseRepositoryWeb =  api_get_path(WEB_COURSE_PATH) . $_course["path"]."/";
$currentUserFirstName       = $_user['firstName'];
$currentUserLastName        = $_user['lastName'];

$authors = $_POST['authors'];
$delete = $_REQUEST['delete'];
$description = $_REQUEST['description'];
$display_tool_options = $_REQUEST['display_tool_options'];
$display_upload_form = $_REQUEST['display_upload_form'];
$edit = $_REQUEST['edit'];
$make_invisible = $_REQUEST['make_invisible'];
$make_visible = $_REQUEST['make_visible'];
$origin = $_REQUEST['origin'];
$submitGroupWorkUrl = $_REQUEST['submitGroupWorkUrl'];
$title = $_REQUEST['title'];
$uploadvisibledisabled = $_REQUEST['uploadvisibledisabled'];
$id = (int) $_REQUEST['id'];

//directories management
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$course_dir   = $sys_course_path.$_course['path'];
$base_work_dir = $course_dir.'/work';
$http_www = api_get_path('WEB_COURSE_PATH').$_course['path'].'/work';
$cur_dir_path = '';
if(isset($_GET['curdirpath']) && $_GET['curdirpath']!='')
{
	//$cur_dir_path = preg_replace('#[\.]+/#','',$_GET['curdirpath']); //escape '..' hack attempts
	//now using common security approach with security lib
	$in_course = Security::check_abs_path($base_work_dir.'/'.$_GET['curdirpath'],$base_work_dir);
	if(!$in_course)
	{
		$cur_dir_path="/";
	}else{
		$cur_dir_path = $_GET['curdirpath'];
	}
}
elseif (isset($_POST['curdirpath']) && $_POST['curdirpath']!='')
{
	//$cur_dir_path = preg_replace('#[\.]+/#','/',$_POST['curdirpath']); //escape '..' hack attempts
	//now using common security approach with security lib
	$in_course = Security::check_abs_path($base_work_dir.'/'.$_POST['curdirpath'],$base_work_dir);
	if(!$in_course)
	{
		$cur_dir_path="/";
	}else{
		$cur_dir_path = $_POST['curdirpath'];
	}
}
else
{
	$cur_dir_path = '/';
}
if($cur_dir_path == '.'){
	$cur_dir_path = '/';
}
$cur_dir_path_url = urlencode($cur_dir_path);


//prepare a form of path that can easily be added at the end of any url ending with "work/"
$my_cur_dir_path = $cur_dir_path;
if($my_cur_dir_path == '/')
{
	$my_cur_dir_path = '';
}
elseif(substr($my_cur_dir_path,-1,1)!='/')
{
	$my_cur_dir_path = $my_cur_dir_path.'/';
}
/*
-----------------------------------------------------------
	Configuration settings
-----------------------------------------------------------
*/
$link_target_parameter = ""; //or e.g. "target=\"_blank\"";
$always_show_tool_options = false;
$always_show_upload_form = false;
if ($always_show_tool_options)
{
	$display_tool_options = true;
}
if ($always_show_upload_form)
{
	$display_upload_form = true;
}

api_protect_course_script();

/*
-----------------------------------------------------------
	More init stuff
-----------------------------------------------------------
*/

if(isset($_POST['cancelForm']) && !empty($_POST['cancelForm']))
{
	header('Location: '.api_get_self()."?origin=$origin");
	exit();
}

if ($_POST['submitWork'] || $submitGroupWorkUrl)
{
	// these libraries are only used for upload purpose
	// so we only include them when necessary
	include_once(api_get_path(INCLUDE_PATH)."lib/fileUpload.lib.php");
	include_once(api_get_path(INCLUDE_PATH)."lib/fileDisplay.lib.php"); // need format_url function
}

// If the POST's size exceeds 8M (default value in php.ini) the $_POST array is emptied
// If that case happens, we set $submitWork to 1 to allow displaying of the error message
// The redirection with header() is needed to avoid apache to show an error page on the next request
if($_SERVER['REQUEST_METHOD'] == 'POST' && !sizeof($_POST))
{
	if(strstr($_SERVER['REQUEST_URI'],'?'))
	{
		header('Location: '.$_SERVER['REQUEST_URI'].'&submitWork=1');
		exit();
	}
	else
	{
		header('Location: '.$_SERVER['REQUEST_URI'].'?submitWork=1');
		exit();
	}
}
//toolgroup comes from group. the but of tis variable is to limit post to the group of the student
if (!api_is_course_admin()){
	if (!empty($_GET['toolgroup']))
	{
		$toolgroup=$_GET['toolgroup'];
		api_session_register('toolgroup');
	}
}
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
if ($origin != 'learnpath')
{
	if($display_upload_form)
	{
		$tool_name = get_lang("UploadADocument");
		$interbreadcrumb[] = array ("url" => "work.php", "name" => get_lang('StudentPublications'));
	}
	if($display_tool_options)
	{
		$tool_name = get_lang("EditToolOptions");
		$interbreadcrumb[] = array ("url" => "work.php", "name" => get_lang('StudentPublications'));
	}
	Display::display_header($tool_name);
}
else
{
	//we are in the learnpath tool
	include api_get_path(INCLUDE_PATH).'reduced_header.inc.php';
}

//stats
event_access_tool(TOOL_STUDENTPUBLICATION);

$is_allowed_to_edit = api_is_allowed_to_edit(); //has to come after display_tool_view_option();
//api_display_tool_title($tool_name);

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

if (isset($_POST['changeProperties']))
{
	$query="UPDATE ".$main_course_table." SET show_score='".$uploadvisibledisabled."' WHERE code='".$_course['sysCode']."'";
	api_sql_query($query,__FILE__,__LINE__);

	$_course['show_score']=$uploadvisibledisabled;
}
else
{
	$query="SELECT * FROM ".$main_course_table." WHERE code=\"".$_course['sysCode']."\"";
	$result=api_sql_query($query,__FILE__,__LINE__);
	$row=mysql_fetch_array($result);
	$uploadvisibledisabled = $row["show_score"];
}


/*
-----------------------------------------------------------
	Introduction section
-----------------------------------------------------------
*/
Display::display_introduction_section(TOOL_STUDENTPUBLICATION);

/*
-----------------------------------------------------------
	COMMANDS SECTION (reserved for course administrator)
-----------------------------------------------------------
*/
if (api_is_allowed_to_edit())
{
	/*-------------------------------------------
				DELETE WORK COMMAND
	-----------------------------------------*/
	if ($delete)
	{
		if ($delete == "all")
		{
			$queryString1 = "SELECT url FROM ".$work_table."";
			$queryString2 = "DELETE FROM  ".$work_table."";
		}
		else
		{
			$queryString1 = "SELECT url FROM  ".$work_table."  WHERE id = '$delete'";
			$queryString2 = "DELETE FROM  ".$work_table."  WHERE id='$delete'";
		}

		$result1 = api_sql_query($queryString1,__FILE__,__LINE__);
		$result2 = api_sql_query($queryString2,__FILE__,__LINE__);

		if ($result1)
		{
			while ($thisUrl = mysql_fetch_array($result1))
			{
				// check the url really points to a file in the work area
				// (some work links can come from groups area...)
				//if (substr (dirname($thisUrl['url']), -4) == "work")
				if(strstr($thisUrl['url'],"work/$my_cur_dir_path")!==false)
				{
					@unlink($currentCourseRepositorySys.$thisUrl['url']);
				}
			}
		}
	}

	/*-------------------------------------------
	           EDIT COMMAND WORK COMMAND
	  -----------------------------------------*/

	if ($edit)
	{
		$sql    = "SELECT * FROM  ".$work_table."  WHERE id='".mysql_real_escape_string($edit)."'";
		$result = api_sql_query($sql,__FILE__,__LINE__);

		if ($result)
		{
			$row = mysql_fetch_array($result);

			$workTitle       = $row ['title'      ];
			$workAuthor      = $row ['author'     ];
			$workDescription = $row ['description'];
			$workUrl         = $row ['url'        ];
		}
	}



	/*-------------------------------------------
		MAKE INVISIBLE WORK COMMAND
	  -----------------------------------------*/

	if ($make_invisible)
	{
		if ($make_invisible == "all")
		{
			$sql = "ALTER TABLE ".$work_table."
			        CHANGE accepted accepted TINYINT(1) DEFAULT '0'";

			api_sql_query($sql,__FILE__,__LINE__);

			$sql = "UPDATE  ".$work_table."
			        SET accepted = 0";

			api_sql_query($sql,__FILE__,__LINE__);
		}
		else
		{
			$sql = "UPDATE  ".$work_table."
			        SET accepted = 0
					WHERE id = '".$make_invisible."'";

			api_sql_query($sql,__FILE__,__LINE__);
		}
	}



	/*-------------------------------------------
		MAKE VISIBLE WORK COMMAND
	  -----------------------------------------*/

	if ($make_visible)
	{
		if ($make_visible == "all")
		{
			$sql = "ALTER TABLE  ".$work_table."
			        CHANGE accepted accepted TINYINT(1) DEFAULT '1'";

			api_sql_query($sql,__FILE__,__LINE__);

			$sql = "UPDATE  ".$work_table."
			        SET accepted = 1";

			api_sql_query($sql,__FILE__,__LINE__);

		}
		else
		{
			$sql = "UPDATE  ".$work_table."
			        SET accepted = 1
					WHERE id = '".$make_visible."'";

			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/*--------------------
	 * Create dir command
	 ---------------------*/
	if(!empty($_REQUEST['create_dir']) && !empty($_REQUEST['new_dir'])){
		//create the directory
		//needed for directory creation
		include_once(api_get_path(LIBRARY_PATH) . "fileUpload.lib.php");
		$added_slash = (substr($cur_dir_path,-1,1)=='/')?'':'/';
		$dir_name = $cur_dir_path.$added_slash.replace_dangerous_char($_POST['new_dir']);
		$created_dir = create_unexisting_work_directory($base_work_dir,$dir_name);
		if($created_dir)
		{
			//Display::display_normal_message("<strong>".$created_dir."</strong> was created!");
			Display::display_normal_message('<span title="'.$created_dir.'">'.get_lang('DirCr').'</span>', false);
			//uncomment if you want to enter the created dir
			//$curdirpath = $created_dir;
			//$curdirpathurl = urlencode($curdirpath);
		}
		else
		{
			Display::display_error_message(get_lang('CannotCreateDir'));
		}
	}
	/* -------------------
	 * Delete dir command
	 --------------------*/
	if(!empty($_REQUEST['delete_dir']))
	{
		del_dir($base_work_dir.'/',$_REQUEST['delete_dir']);
		Display::display_normal_message($_REQUEST['delete_dir'].' '.get_lang('DirDeleted'));
	}
	/* ----------------------
	 * Move file form request
	 ----------------------- */
	if(!empty($_REQUEST['move']))
	{
		$folders = get_subdirs_list($base_work_dir,1);
		Display::display_normal_message(build_move_to_selector($folders,$cur_dir_path,$_REQUEST['move']),false);
	}
	/* ------------------
	 * Move file command
	 ------------------- */
	if (isset($_POST['move_to']) && isset($_POST['move_file']))
	{
		include_once(api_get_path(LIBRARY_PATH) . "/fileManage.lib.php");
		$move_to = $_POST['move_to'];
		if($move_to == '/' or empty($move_to))
		{
			$move_to = '';
		}
		elseif(substr($move_to,-1,1)!='/')
		{
			$move_to = $move_to.'/';
		}

		//security fix: make sure they can't move files that are not in the document table
		if($path = get_work_path($_POST['move_file']))
		{
			//echo "got path $path";
			//Display::display_normal_message('We want to move '.$_POST['move_file'].' to '.$_POST['move_to']);
			if ( move($course_dir.'/'.$path,$base_work_dir.'/'.$move_to) )
			{
				//update db
				update_work_url($_POST['move_file'],'work/'.$move_to);
				//set the current path
				$cur_dir_path = $move_to;
				$cur_dir_path_url = urlencode($move_to);
				Display::display_normal_message(get_lang('DirMv'));
}
			else
			{
				Display::display_error_message(get_lang('Impossible'));
			}
		}
		else
		{
			Display::display_error_message(get_lang('Impossible'));
		}
	}
}
/*
-----------------------------------------------------------
	COMMANDS SECTION (reserved for others - check they're authors each time)
-----------------------------------------------------------
*/
else
{
	$iprop_table = Database::get_course_table(TABLE_ITEM_PROPERTY);
	$user_id = api_get_user_id();
	/*-------------------------------------------
				DELETE WORK COMMAND
	-----------------------------------------*/
	if ($delete)
	{
		if ($delete == "all")
		{
			/*not authorized to this user */
		}
		else
		{
			//Get the author ID for that document from the item_property table
			$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=".mysql_real_escape_string($delete);
			$author_qry = api_sql_query($author_sql,__FILE__,__LINE__);
			if(Database::num_rows($author_qry)==1)
			{
				//we found the current user is the author
				$queryString1 = "SELECT url FROM  ".$work_table."  WHERE id = '$delete'";
				$queryString2 = "DELETE FROM  ".$work_table."  WHERE id='$delete'";
				$result1 = api_sql_query($queryString1,__FILE__,__LINE__);
				$result2 = api_sql_query($queryString2,__FILE__,__LINE__);
				if ($result1)
				{
					api_item_property_update($_course,'work',$delete,get_lang('DocumentDeleted'),$user_id);
					while ($thisUrl = mysql_fetch_array($result1))
					{
						// check the url really points to a file in the work area
						// (some work links can come from groups area...)
						if (substr (dirname($thisUrl['url']), -4) == "work")
						{
							@unlink($currentCourseRepositorySys."work/".$thisWork);
						}
					}
				}
			}
		}
	}
	/*-------------------------------------------
	           EDIT COMMAND WORK COMMAND
	  -----------------------------------------*/
	if ($edit)
	{
		//Get the author ID for that document from the item_property table
		$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=".mysql_real_escape_string($edit);
		$author_qry = api_sql_query($author_sql,__FILE__,__LINE__);
		if(Database::num_rows($author_qry)==1)
		{
			//we found the current user is the author
			$sql    = "SELECT * FROM  ".$work_table."  WHERE id='".$edit."'";
			$result = api_sql_query($sql,__FILE__,__LINE__);

			if ($result)
			{
				$row = mysql_fetch_array($result);

				$workTitle       = $row ['title'      ];
				$workAuthor      = $row ['author'     ];
				$workDescription = $row ['description'];
				$workUrl         = $row ['url'        ];
			}
		}
	}
}

/*
==============================================================================
		FORM SUBMIT PROCEDURE
==============================================================================
*/

$error_message="";

$check = Security::check_token('post'); //check the token inserted into the form
if($_POST['submitWork'] && $is_course_member && $check)
{
	if($_FILES['file']['size'])
	{
		$updir           = $currentCourseRepositorySys.'work/'; //directory path to upload

		// Try to add an extension to the file if it has'nt one
		$new_file_name = add_ext_on_mime(stripslashes($_FILES['file']['name']),$_FILES['file']['type']);

		// Replace dangerous characters
		$new_file_name = replace_dangerous_char($new_file_name,'strict');

		// Transform any .php file in .phps fo security
		$new_file_name = php2phps($new_file_name);
		//filter extension
	    if(!filter_extension($new_file_name))
	    {
	    	Display::display_error_message(get_lang('UplUnableToSaveFileFilteredExtension'));
	    	$succeed = false;
	    }
	    else
		{

			if( ! $title )
			{
				$title = $_FILES['file']['name'];
			}
	
			if ( ! $authors)
			{
				$authors = $currentUserFirstName." ".$currentUserLastName;
			}
	
			// compose a unique file name to avoid any conflict
	
			$new_file_name = uniqid('').$new_file_name;
	
			if (isset($_SESSION['toolgroup']))
			{
				$post_group_id = $_SESSION['toolgroup'];
			}
			else
			{
				$post_group_id = '0';
			}
			//if we come from the group tools the groupid will be saved in $work_table
	
			move_uploaded_file($_FILES['file']['tmp_name'],$updir.$my_cur_dir_path.$new_file_name);
	
			$url = "work/".$my_cur_dir_path.$new_file_name;
			$result = api_sql_query("SHOW FIELDS FROM ".$work_table." LIKE 'sent_date'",__FILE__,__LINE__);
	
			if(!mysql_num_rows($result))
			{
				api_sql_query("ALTER TABLE ".$work_table." ADD sent_date DATETIME NOT NULL");
			}
	
			$sql_add_publication = "INSERT INTO ".$work_table."
			               SET url         = '".$url."',
						       title       = '".$title."',
			                   description = '".$description."',
			                   author      = '".$authors."',
							   active		= '".$active."',
							   accepted		= '".(!$uploadvisibledisabled)."',
							   post_group_id = '".$post_group_id."',
							   sent_date	= NOW()";
	
			api_sql_query($sql_add_publication,__FILE__,__LINE__);
	
	        $Id = mysql_insert_id();
	        api_item_property_update($_course,'work',$Id,get_lang('DocumentAdded'),$user_id);
			$succeed = true;
		}
	}

	/*
	 * SPECIAL CASE ! For a work coming from another area (i.e. groups)
	 */

	elseif ($newWorkUrl)
	{

		$url = str_replace('../../'.$_course['path'].'/','',$newWorkUrl);


		if( ! $title )
		{
			$title = basename($workUrl);
		}

		$result = api_sql_query("SHOW FIELDS FROM ".$work_table." LIKE 'sent_date'",__FILE__,__LINE__);

		if(!mysql_num_rows($result))
		{
			api_sql_query("ALTER TABLE ".$work_table." ADD sent_date DATETIME NOT NULL");
		}

		$sql = "INSERT INTO  ".$work_table."
		        SET url         = '".$url."',
		            title       = '".$title."',
		            description = '".$description."',
		            author      = '".$authors."',
		            sent_date     = NOW()";

		api_sql_query($sql,__FILE__,__LINE__);

		$insertId = mysql_insert_id();
		api_item_property_update($_course,'work',$insertId,get_lang('DocumentAdded'),$user_id);
		$succeed = true;
	}

	/*
	 * SPECIAL CASE ! For a work edited
	 */

	else
	{
		//Get the author ID for that document from the item_property table
		$is_author = false;
		$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=".mysql_real_escape_string($id);
		$author_qry = api_sql_query($author_sql,__FILE__,__LINE__);
		if(Database::num_rows($author_qry)==1)
		{
			$is_author=true;
		}

		if ($id && ($is_allowed_to_edit or $is_author))
		{
			if( ! $title )
			{
				$title = basename($newWorkUrl);
			}

			$sql = "UPDATE  ".$work_table."
			        SET	title       = '".$title."',
			            description = '".$description."',
			            author      = '".$authors."'
			        WHERE id        = '".$id."'";

			api_sql_query($sql,__FILE__,__LINE__);
	        $insertId = $id;
	        api_item_property_update($_course,'work',$insertId,get_lang('DocumentUpdated'),$user_id);
			$succeed = true;
		}
		else
		{
			$error_message = get_lang('TooBig');
		}
	}
	Security::clear_token();//clear the token to prevent re-executing the request with back button
}
if ($_POST['submitWork'] && $succeed &&!$id) //last value is to check this is not "just" an edit
{
	//YW Tis part serve to send a e-mail to the tutors when a new file is sent
	$send = api_get_course_setting('email_alert_manager_on_new_doc');
	if($send>0)
	{
		// Lets predefine some variables. Be sure to change the from address!
		$table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		$table_session = Database::get_main_table(TABLE_MAIN_SESSION);
		$table_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		
		$emailto = array();
		if(empty($_SESSION['id_session']))
		{
			$sql_resp = 'SELECT u.email as myemail FROM '.$table_course_user.' cu, '.$table_user.' u WHERE cu.course_code = '."'".api_get_course_id()."'".' AND cu.status = 1 AND u.user_id = cu.user_id';
			$res_resp = api_sql_query($sql_resp,__FILE__,__LINE__);
			while($row_email = Database::fetch_array($res_resp)){
				if(!empty($row_email['myemail'])){
					$emailto[$row_email['myemail']] = $row_email['myemail'];
				}
			}
		}
		else
		{
			// coachs of the session
			$sql_resp ='SELECT user.email as myemail 
						FROM '.$table_session.' session
						INNER JOIN '.$table_user.' user
							ON user.user_id = session.id_coach
						WHERE session.id = '.intval($_SESSION['id_session']);
			$res_resp = api_sql_query($sql_resp,__FILE__,__LINE__);
			while($row_email = Database::fetch_array($res_resp)){
				if(!empty($row_email['myemail'])){
					$emailto[$row_email['myemail']] = $row_email['myemail'];
				}
			}
			
			//coach of the course
			$sql_resp ='SELECT user.email as myemail 
						FROM '.$table_session_course.' session_course
						INNER JOIN '.$table_user.' user
							ON user.user_id = session_course.id_coach
						WHERE session_course.id_session = '.intval($_SESSION['id_session']);
			$res_resp = api_sql_query($sql_resp,__FILE__,__LINE__);
			while($row_email = Database::fetch_array($res_resp)){
				if(!empty($row_email['myemail'])){
					$emailto[$row_email['myemail']] = $row_email['myemail'];
				}
			}			
			
		}
		if(count($emailto)>0){
			$emailto = implode(',' , $emailto);
			$emailfromaddr = get_setting('emailAdministrator');
			$emailfromname = get_setting('siteName');
			$emailsubject  = "[".get_setting('siteName')."] ";
	
			// The body can be as long as you wish, and any combination of text and variables
	
			//$emailbody=get_lang('SendMailBody').' '.api_get_path(WEB_CODE_PATH)."work/work.php?".api_get_cidreq()." ($title)\n\n".get_setting('administratorName')." ".get_setting('administratorSurname')."\n". get_lang('Manager'). " ".get_setting('siteName')."\nT. ".get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".get_setting('emailAdministrator');
			$emailbody=get_lang('SendMailBody').' '.api_get_path(WEB_CODE_PATH)."work/work.php?".api_get_cidreq()." (".stripslashes($title).")\n\n".get_setting('administratorName')." ".get_setting('administratorSurname')."\n". get_lang('Manager'). " ".get_setting('siteName')."\n" .get_lang('Email') ." : ".get_setting('emailAdministrator');
	
			// Here we are forming one large header line
			// Every header must be followed by a \n except the last
			$emailheaders = "From: ".get_setting('administratorName')." ".get_setting('administratorSurname')." <".get_setting('emailAdministrator').">\n";
			$emailheaders .= "Reply-To: ".get_setting('emailAdministrator');
	
			// Because I predefined all of my variables, this api_send_mail() function looks nice and clean hmm?
			@api_send_mail( $emailto, $emailsubject, $emailbody, $emailheaders);
		}
	}
	$message = get_lang('DocAdd');
    if ($uploadvisibledisabled && !$is_allowed_to_edit)
	{
		$message .= "<br />".get_lang('_doc_unvisible')."<br />";
	}

	//stats
	if(!$Id) { $Id = $insertId; }
    event_upload($Id);
	$submit_success_message	= $message . "<br />\n";
	Display::display_normal_message($submit_success_message,false);
}

//{
	/*=======================================
		 Display links to upload form and tool options
	  =======================================*/

	display_action_links($cur_dir_path,$always_show_tool_options, $always_show_upload_form);

	/*=======================================
		 Display form to upload document
	  =======================================*/

	if($is_course_member)
	{
		if ($display_upload_form || $edit)
		{
			$token = Security::get_token(); //generate token to be used to check validity of request
			if($edit){
				//Get the author ID for that document from the item_property table
				$is_author = false;
				$author_sql = "SELECT * FROM $iprop_table WHERE tool = 'work' AND insert_user_id='$user_id' AND ref=".mysql_real_escape_string($edit);
				$author_qry = api_sql_query($author_sql,__FILE__,__LINE__);
				if(Database::num_rows($author_qry)==1)
				{
					$is_author = true;
				}
			}

			require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
			require_once (api_get_path(LIBRARY_PATH).'fileDisplay.lib.php');
			
			$form = new FormValidator('form','POST',api_get_self()."?curdirpath=$cur_dir_path&origin=$origin",'','enctype="multipart/form-data"');
			
			if(!empty($error_message)) Display::display_error_message($error_message);

			if ($submitGroupWorkUrl) // For user comming from group space to publish his work
			{
				$realUrl = str_replace ($_configuration['root_sys'], $_configuration['root_web'], str_replace("\\", "/", realpath($submitGroupWorkUrl) ) ) ;
				$form->addElement('hidden', 'newWorkUrl', $submitGroupWorkUrl);
				$text_document = &$form->addElement('text', 'document', get_lang("Document"));
				$defaults["document"] = '<a href="'.format_url($submitGroupWorkUrl).'">'.$realUrl.'</a>';
				$text_document->freeze();
			}

			elseif ($edit && ($is_allowed_to_edit or $is_author))
			{
				$workUrl = $currentCourseRepositoryWeb.$workUrl;
				$form->addElement('hidden', 'id', $edit);

				$html='<div class="row">
					<div class="label">'.get_lang("Document").'
					</div>
					<div class="formw">
						<a href="'.$workUrl.'">'.$workUrl.'</a>
					</div>
				</div>';
				$form->addElement('html', $html);
			}

			else // else standard upload option
			{
				$form->addElement('file','file',get_lang('DownloadFile'), 'size="30" onchange="updateDocumentTitle(this.value)"');
			}

			$titleWork=$form->addElement('text', 'title', get_lang("TitleWork"), 'id="file_upload"  style="width: 350px;"');
			$defaults["title"] = ($edit?stripslashes($workTitle):stripslashes($title));

			$titleAuthors=$form->addElement('text', 'authors', get_lang("Authors"), 'style="width: 350px;"');
			
			if(empty($authors))
			{
				$authors=$_user['firstName']." ".$_user['lastName'];
			}
			
			$defaults["authors"] = ($edit?stripslashes($workAuthor):stripslashes($authors));

			$titleAuthors=$form->addElement('textarea', 'description', get_lang("Description"), 'style="width: 350px; height: 60px;"');
			$defaults["description"] = ($edit?stripslashes($workDescription):stripslashes($description));

			$form->addElement('hidden', 'active', 1);
			$form->addElement('hidden', 'accepted', 1);
			$form->addElement('hidden', 'sec_token', $token);
			
			$form->addElement('submit', 'submitWork', get_lang('Ok'));

			

			if($_POST['submitWork'] || $edit)
			{
				$form->addElement('submit', 'cancelForm', get_lang('Cancel'));
			}

			$form->add_real_progress_bar('uploadWork','DownloadFile');

			$form->setDefaults($defaults);
			$form->display();

		}
		//show them the form for the directory name
		if(isset($_REQUEST['createdir']) && $is_allowed_to_edit)
		{
			//create the form that asks for the directory name
			$new_folder_text = '<form action="'.api_get_self().'" method="POST">';
			$new_folder_text .= '<input type="hidden" name="curdirpath" value="'.$cur_dir_path.'"/>';
			$new_folder_text .= get_lang('NewDir') .' ';
			$new_folder_text .= '<input type="text" name="new_dir"/>';
			$new_folder_text .= '<input type="submit" name="create_dir" value="'.get_lang('Ok').'"/>';
			$new_folder_text .= '</form>';
			//show the form
			echo $new_folder_text;
	}
	}
	else
	{
		//the user is not registered in this course
		echo "<p style=\"font-weight:bold\">" . get_lang("MustBeRegisteredUser") . "</p>";
	}

/*
==============================================================================
		Display of tool options
==============================================================================
*/

	if ($display_tool_options)
	{
		display_tool_options($uploadvisibledisabled, $origin,$base_work_dir,$cur_dir_path,$cur_dir_path_url);
	}

/*
==============================================================================
		Display list of student publications
==============================================================================
*/
	if($cur_dir_path =='/')
	{
		$my_cur_dir_path = '';
	}
	else
	{
		$my_cur_dir_path = $cur_dir_path;
	}
	display_student_publications_list($base_work_dir.'/'.$my_cur_dir_path,'work/'.$my_cur_dir_path,$currentCourseRepositoryWeb, $link_target_parameter, $dateFormatLong, $origin);
//}

/*
==============================================================================
		Footer
==============================================================================
*/
if ($origin != 'learnpath')
{
	//we are not in the learning path tool
	Display::display_footer();
}
?>