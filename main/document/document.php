<?php // $Id: document.php 18969 2009-03-11 18:03:02Z aportugal $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
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
/**
==============================================================================
* Main script for the documents tool
*
* This script allows the user to manage files and directories on a remote http server.
*
* The user can : - navigate through files and directories.
*				 - upload a file
*				 - delete, copy a file or a directory
*				 - edit properties & content (name, comments, html content)
*
* The script is organised in four sections.
*
* 1) Execute the command called by the user
*				Note: somme commands of this section are organised in two steps.
*			    The script always begins with the second step,
*			    so it allows to return more easily to the first step.
*
*				Note (March 2004) some editing functions (renaming, commenting)
*				are moved to a separate page, edit_document.php. This is also
*				where xml and other stuff should be added.
*
* 2) Define the directory to display
*
* 3) Read files and directories from the directory defined in part 2
* 4) Display all of that on an HTML page
*
* @todo eliminate code duplication between
* document/document.php, scormdocument.php
*
* @package dokeos.document
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included
$language_file[] = 'document';
$language_file[] = 'slideshow';

require("../inc/global.inc.php");
$this_section=SECTION_COURSES;

require('document.inc.php');


require('../inc/lib/usermanager.lib.php');

api_protect_course_script(true);

//session
if(isset($_GET['id_session']))
	$_SESSION['id_session'] = $_GET['id_session'];

// Is the document tool visible?
// Check whether the tool is actually visible
$table_course_tool = Database::get_course_table(TABLE_TOOL_LIST, $_course['dbName']);
$tool_sql = 'SELECT visibility FROM ' . $table_course_tool . ' WHERE name = "'. TOOL_DOCUMENT .'" LIMIT 1';
$tool_result = api_sql_query($tool_sql,__FILE__,__LINE__);
$tool_row = mysql_fetch_array($tool_result);
$tool_visibility = $tool_row['visibility'];
if ($tool_visibility == '0' && $to_group_id == '0' && !($is_allowed_to_edit || $group_member_with_upload_rights))
{
 api_not_allowed(true);
}

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name)
{
	if (confirm(\" ". get_lang("AreYouSureToDelete") ." \"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
}
</script>";

/*
-----------------------------------------------------------
	Variables
	- some need defining before inclusion of libraries
-----------------------------------------------------------
*/

//what's the current path?
//we will verify this a bit further down
if(isset($_GET['curdirpath']) && $_GET['curdirpath']!='')
{
	$curdirpath = $_GET['curdirpath'];
}
elseif (isset($_POST['curdirpath']) && $_POST['curdirpath']!='')
{
	$curdirpath = $_POST['curdirpath'];
}
else
{
	$curdirpath = '/';
}
$curdirpathurl = urlencode($curdirpath);

$course_dir   = $_course['path']."/document";
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$course_dir;
$http_www = api_get_path('WEB_COURSE_PATH').$_course['path'].'/document';
$dbl_click_id = 0; // used to avoid double-click
$is_allowed_to_edit = api_is_allowed_to_edit();
$group_member_with_upload_rights = false;

//if the group id is set, we show them group documents
if(isset($_SESSION['_gid']) && $_SESSION['_gid']!='')
{
	//needed for group related stuff
	include_once(api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php');
	//get group info
	$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
	$noPHP_SELF=true;
	//let's assume the user cannot upload files for the group
	$group_member_with_upload_rights = false;

	if($group_properties['doc_state']==2) //documents are private
	{
		if($is_allowed_to_edit || GroupManager :: is_user_in_group($_user['user_id'],$_SESSION['_gid'])) //only courseadmin or group members (members + tutors) allowed
		{
			$to_group_id = $_SESSION['_gid'];
			$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
			$interbreadcrumb[]= array ("url"=>"../group/group.php", "name"=> get_lang('Groups'));
			$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
			//they are allowed to upload
			$group_member_with_upload_rights = true;
		}
		else
		{
			$to_group_id = 0;
			$req_gid = '';
		}
	}
	elseif($group_properties['doc_state']==1)  //documents are public
	{
		$to_group_id = $_SESSION['_gid'];
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
		$interbreadcrumb[]= array ("url"=>"../group/group.php", "name"=> get_lang('Groups'));
		$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
		//allowed to upload?
		if($is_allowed_to_edit || GroupManager::is_subscribed($_user['user_id'],$_SESSION['_gid'])) //only courseadmin or group members can upload
		{
			$group_member_with_upload_rights = true;
		}
	}
	else //documents not active for this group
	{
		$to_group_id = 0;
		$req_gid = '';
	}
	$_SESSION['group_member_with_upload_rights'] = $group_member_with_upload_rights;
}
else
{
	$to_group_id = 0;
	$req_gid = '';
}
/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
//the main_api.lib.php, database.lib.php and display.lib.php
//libraries are included by default

include_once(api_get_path(LIBRARY_PATH) . 'fileDisplay.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
include_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'tablesort.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');

 
//-----------------------------------------------------------
//check the path
//if the path is not found (no document id), set the path to /
if(!DocumentManager::get_document_id($_course,$curdirpath))
{
	$curdirpath = '/';
	//urlencoded version
	$curdirpathurl = '%2F';
}
//if they are looking at group documents they can't see the root
if($to_group_id!=0 && $curdirpath=='/')
{
	$curdirpath = $group_properties['directory'];
	$curdirpathurl = urlencode($group_properties['directory']);
}
//-----------------------------------------------------------

// check visibility of the current dir path. Don't show anything if not allowed
if (!(DocumentManager::is_visible($curdirpath, $_course)||$is_allowed_to_edit)){
    api_not_allowed();
}
/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/

$course_quota = DocumentManager::get_course_quota();

/*
==============================================================================
		MAIN SECTION
==============================================================================
*/

//-------------------------------------------------------------------//
if (isset($_GET['action']) && $_GET['action']=="download")
{
	//check if the document is in the database
	if(!DocumentManager::get_document_id($_course,$_GET['id']))
	{
		//file not found!
		header('HTTP/1.0 404 Not Found');
		$error404 = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">';
		$error404 .= '<html><head>';
		$error404 .= '<title>404 Not Found</title>';
		$error404 .= '</head><body>';
		$error404 .= '<h1>Not Found</h1>';
		$error404 .= '<p>The requested URL was not found on this server.</p>';
		$error404 .= '<hr>';
		$error404 .= '</body></html>';
		echo($error404);
		exit;
	}  
	// launch event
	event_download($_GET['id']);
	
    // check visibility of document and paths
    if (!($is_allowed_to_edit || $group_member_with_upload_rights) &&
        !DocumentManager::is_visible($_GET['id'], $_course)){        
        api_not_allowed();
    }

    $doc_url=$_GET['id'];
	$full_file_name = $base_work_dir.$doc_url;
	DocumentManager::file_send_for_download($full_file_name,true);
	exit;
 }
//-------------------------------------------------------------------//

//download of an completed folder
if(isset($_GET['action']) && $_GET['action']=="downloadfolder")
{
	include('downloadfolder.inc.php');
}
//-------------------------------------------------------------------//

// slideshow inititalisation
$_SESSION['image_files_only']='';
$image_files_only='';

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

$interbreadcrumb[]= array ('url'=>'#', 'name'=> get_lang('Document'));

//------interbreadcrumb for the current directory root path

$dir_array=explode("/",$curdirpath);
$array_len=count($dir_array);

if ($array_len >1)
{
	if(empty($_SESSION['_gid']))
	{
		$url_dir='document.php?&curdirpath=/'; 
		$interbreadcrumb[]= array ('url'=>$url_dir, 'name'=> get_lang('HomeDirectory'));
	}
} 

$dir_acum='';
for ($i=0; $i<$array_len;$i++)
{ 
	if($dir_array[$i] =='shared_folder')
	{
		$dir_array[$i]=get_lang('SharedFolder');
	}
	elseif(strstr($dir_array[$i], 'sf_user_'))
	{
		$userinfo=Database::get_user_info_from_id(substr($dir_array[$i], 8));		
		$dir_array[$i]=$userinfo['lastname'].', '.$userinfo['firstname'];
	}

	$url_dir='document.php?&curdirpath='.$dir_acum.$dir_array[$i]; 
	$interbreadcrumb[]= array ('url'=>$url_dir, 'name'=> $dir_array[$i]);
	$dir_acum.=$dir_array[$i].'/';
}

Display::display_header('','Doc');
$is_allowed_to_edit  = api_is_allowed_to_edit();

/*
 * Lib for event log, stats & tracking
 * plus record of the access
 */
event_access_tool(TOOL_DOCUMENT);

/*
==============================================================================
		DISPLAY
==============================================================================
*/
if($to_group_id !=0) //add group name after for group documents
{
	$add_group_to_title = ' ('.$group_properties['name'].')';
}
//api_display_tool_title($tool_name.$add_group_to_title);

/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/

$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '300';
$fck_attribute['ToolbarSet'] = 'Introduction';

if(!empty($_SESSION['_gid']))
{
	Display::display_introduction_section(TOOL_DOCUMENT.$_SESSION['_gid'],'left');
}
else
{
	Display::display_introduction_section(TOOL_DOCUMENT,'left');
}

$fck_attribute = null; // Clearing this global variable immediatelly after it has been used.

/*============================================================================*/

if($is_allowed_to_edit || $group_member_with_upload_rights) // TEACHER ONLY
{
	/*======================================
				MOVE FILE OR DIRECTORY
	  ======================================*/

	if (isset($_GET['move']) && $_GET['move']!='')
	{ 	
		if (!$is_allowed_to_edit)
		{
			if(DocumentManager::check_readonly($_course,$_user['user_id'],$_GET['move']))
			{
				api_not_allowed();	 
			}
		}
		
		if(DocumentManager::get_document_id($_course,$_GET['move']))
		{
			$folders = DocumentManager::get_all_document_folders($_course,$to_group_id,$is_allowed_to_edit || $group_member_with_upload_rights);
			Display::display_normal_message(build_move_to_selector($folders,$_GET['curdirpath'],$_GET['move'],$group_properties['directory']),false);
		}
		
		
	}
 	
	if (isset($_POST['move_to']) && isset($_POST['move_file']))
	{
		if (!$is_allowed_to_edit)
		{
			if(DocumentManager::check_readonly($_course,$_user['user_id'],$_GET['move']))
			{
				api_not_allowed();	
			}
		}
		
		include_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
		//this is needed for the update_db_info function
		//$dbTable = $_course['dbNameGlu']."document";
		$dbTable = Database::get_course_table(TABLE_DOCUMENT);

		//security fix: make sure they can't move files that are not in the document table
		if(DocumentManager::get_document_id($_course,$_POST['move_file']))
		{
			if ( move($base_work_dir.$_POST['move_file'],$base_work_dir.$_POST['move_to']) )
			{
				update_db_info("update", $_POST['move_file'], $_POST['move_to']."/".basename($_POST['move_file']));
				//set the current path
				$curdirpath = $_POST['move_to'];
				$curdirpathurl = urlencode($_POST['move_to']);
				Display::display_confirmation_message(get_lang('DirMv'));
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

	/*======================================
			DELETE FILE OR DIRECTORY
	  ======================================*/

	if ( isset($_GET['delete']) )
	{		
		if (!$is_allowed_to_edit)
		{
			if(DocumentManager::check_readonly($_course,$_user['user_id'],$_GET['delete'],'',true))
			{
				api_not_allowed();
			}
		} 
							
		include_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php');
		 
		if(DocumentManager::delete_document($_course,$_GET['delete'],$base_work_dir))
		{
			Display::display_confirmation_message(get_lang('DocDeleted'));
		}
		else
		{	
			
			Display::display_error_message(get_lang('DocDeleteError'));
		}
		
		
	}

	if( isset($_POST['action']))
	{
		switch($_POST['action'])
		{
			case 'delete':
			
				foreach($_POST['path'] as $index => $path)
				{
					if (!$is_allowed_to_edit)
					{
						if(DocumentManager::check_readonly($_course,$_user['user_id'],$path))
						{
							Display::display_error_message(get_lang('CantDeleteReadonlyFiles'));
							break 2;
						}
					}
				}
			
				foreach($_POST['path'] as $index => $path)				
				{
					if (strcmp($path,'/audio')===0 or strcmp($path,'/flash')===0 or strcmp($path,'/images')===0 or strcmp($path,'/shared_folder')===0 or strcmp($path,'/video')===0) {
						continue;
					} else {
					$delete_document = DocumentManager::delete_document($_course,$path,$base_work_dir);
					}
				}
				if (!empty($delete_document)) {
				Display::display_confirmation_message(get_lang('DocDeleted'));
				}
				break;
		}
	}

	/*======================================
	   		CREATE DIRECTORY
	  ======================================*/

	//create directory with $_POST data
	if(isset($_POST['create_dir']) && $_POST['dirname']!='')
	{
		//needed for directory creation
		include_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
		$post_dir_name=$_POST['dirname'];
		
		if ($post_dir_name=='../' || $post_dir_name=='.' || $post_dir_name=='..') 
		{
			Display::display_error_message(get_lang('CannotCreateDir'));
		}
		else
		{
			$added_slash = ($curdirpath=='/')?'':'/';
			$dir_name = $curdirpath.$added_slash.replace_dangerous_char($post_dir_name);
			$clean_val=disable_dangerous_file($dir_name);
			$clean_val=replace_accents($dir_name);
			$dir_name=$clean_val;
			$dir_check=$base_work_dir.''.$dir_name;
			
			if(!is_dir($dir_check))
			{
				$created_dir = create_unexisting_directory($_course,$_user['user_id'],$to_group_id,$to_user_id,$base_work_dir,$dir_name,$post_dir_name);
				
				if($created_dir)
				{
					Display::display_confirmation_message('<span title="'.$created_dir.'">'.get_lang('DirCr').'</span>',false);
					//uncomment if you want to enter the created dir
					//$curdirpath = $created_dir;
					//$curdirpathurl = urlencode($curdirpath);
				}
				else
				{
					Display::display_error_message(get_lang('CannotCreateDir'));
				}
			}
			else
			{
				Display::display_error_message(get_lang('CannotCreateDir'));
			}
		}
	}
	
	//show them the form for the directory name
	if(isset($_GET['createdir']))
	{
		//create the form that asks for the directory name
		$new_folder_text = '<form action="'.api_get_self().'" method="post">';
		$new_folder_text .= '<input type="hidden" name="curdirpath" value="'.$curdirpath.'" />';
		$new_folder_text .= get_lang('NewDir') .' ';
		$new_folder_text .= '<input type="text" name="dirname" />';
		$new_folder_text .= '<button type="submit" name="create_dir">'.get_lang('CreateFolder').'</button>';
		$new_folder_text .= '</form>';
		//show the form
		Display::display_normal_message($new_folder_text,false);
	}


	/*======================================
	   	  VISIBILITY COMMANDS
	  ======================================*/

	if ((isset($_GET['set_invisible']) && !empty($_GET['set_invisible'])) || (isset($_GET['set_visible']) && !empty($_GET['set_visible'])) AND $_GET['set_visible']<>'*' AND $_GET['set_invisible']<>'*')
	{		
		//make visible or invisible?
		if(isset($_GET['set_visible']))
		{
			$update_id = $_GET['set_visible'];
			$visibility_command = 'visible';
		}
		else
		{
			$update_id = $_GET['set_invisible'];
			$visibility_command = 'invisible';
		} 
		
		if (!$is_allowed_to_edit)	
		{	
			if(DocumentManager::check_readonly($_course,$_user['user_id'],'',$update_id))
			{
				api_not_allowed(); 
			} 
		}
		
		//update item_property to change visibility
		if(api_item_property_update($_course, TOOL_DOCUMENT, $update_id, $visibility_command, $_user['user_id']))
		{
			Display::display_confirmation_message(get_lang("ViMod"));
		}
		else
		{
			Display::display_error_message(get_lang("ViModProb"));
		}

	}
	
	
	/*======================================
	   	  TEMPLATE ACTION
	  ======================================*/
	
	if(isset($_GET['add_as_template']) && !isset($_POST['create_template']))
	{		
		$document_id_for_template = intval($_GET['add_as_template']);
		
		//create the form that asks for the directory name
		$template_text = '<form name="set_document_as_new_template" enctype="multipart/form-data" action="'.api_get_self().'?add_as_template='.$document_id_for_template.'" method="post">';
		$template_text .= '<input type="hidden" name="curdirpath" value="'.$curdirpath.'" />';
		$template_text .= '<table><tr><td>';
		$template_text .= get_lang('TemplateName').' : </td>';
		$template_text .= '<td><input type="text" name="template_title" /></td></tr>';
		$template_text .= '<tr><td>'.get_lang('TemplateDescription').' : </td>';
		$template_text .= '<td><textarea name="template_description"></textarea></td></tr>';
		$template_text .= '<tr><td>'.get_lang('TemplateImage').' : </td>';
		$template_text .= '<td><input type="file" name="template_image" id="template_image" /></td></tr>';
		$template_text .= '</table>';
		$template_text .= '<button type="submit" name="create_template">'.get_lang('Ok').'</button>';
		$template_text .= '</form>';
		//show the form
		Display::display_normal_message($template_text,false);		
	}	
	elseif(isset($_GET['add_as_template']) && isset($_POST['create_template']))
	{		
		$document_id_for_template = intval(Database::escape_string($_GET['add_as_template']));    
			
		$title=Security::remove_XSS($_POST['template_title']);				
		$description = Security::remove_XSS($_POST['template_description']);		
		$course_code = api_get_course_id();
		$user_id = api_get_user_id();
		
		// create the template_thumbnails folder in the upload folder (if needed)
		if(!is_dir(api_get_path(SYS_CODE_PATH).'upload/template_thumbnails/'))
		{
			$perm = api_get_setting('permissions_for_new_directories');
			$perm = octdec(!empty($perm)?$perm:'0770');
			mkdir(api_get_path(SYS_CODE_PATH).'upload/template_thumbnails/',$perm);
		}
		
		// upload the file
		if (!empty($_FILES['template_image']['name']))
		{
			echo 'uploading';
			include_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
			$upload_ok = process_uploaded_file($_FILES['template_image']);
			
			if ($upload_ok)
			{
				// Try to add an extension to the file if it hasn't one
				$new_file_name = $_course['sysCode'].'-'.add_ext_on_mime(stripslashes($_FILES['template_image']['name']), $_FILES['template_image']['type']);	
				
				// upload dir
				$upload_dir = api_get_path(SYS_CODE_PATH).'upload/template_thumbnails/';
				
				// move the uploaded file to the home folder
				$result= @move_uploaded_file($_FILES['template_image']['tmp_name'], $upload_dir.$new_file_name);
			}
	   }	
		
		DocumentManager::set_document_as_template($title, $description, $document_id_for_template, $course_code, $user_id, $new_file_name);		
		Display::display_confirmation_message(get_lang('DocumentSetAsTemplate'));		
	}
		
	if(isset($_GET['remove_as_template']))
	{		
		$document_id_for_template = intval($_GET['remove_as_template']);
		$course_code = api_get_course_id();
		$user_id = api_get_user_id();		
		DocumentManager::unset_document_as_template($document_id_for_template, $course_code, $user_id);		
		Display::display_confirmation_message(get_lang('DocumentUnsetAsTemplate'));		
	}	
} // END is allowed to edit

/*
-----------------------------------------------------------
	GET ALL DOCUMENT DATA FOR CURDIRPATH
-----------------------------------------------------------
*/

$docs_and_folders = DocumentManager::get_all_document_data($_course,$curdirpath,$to_group_id,NULL,$is_allowed_to_edit || $group_member_with_upload_rights);

?>

<?php
$folders = DocumentManager::get_all_document_folders($_course,$to_group_id,$is_allowed_to_edit || $group_member_with_upload_rights);
if($folders===false)
{
	$folders = array();
}

?>

	<?php
	echo '<div class="actions">';


	/* GO TO PARENT DIRECTORY */

	if ($curdirpath!= '/'&& $curdirpath!=$group_properties['directory'])
	{
	?>
		 <a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq();?>&curdirpath=<?php echo urlencode((dirname($curdirpath)=='\\')?'/':dirname($curdirpath)).$req_gid ?>">
				<img src="../img/folder_up.gif" border="0" title="<?php echo get_lang('Up'); ?>" alt="" />
				<?php echo get_lang('Up'); ?></a>&nbsp;
	<?php
	}
	
if(isset($docs_and_folders) && is_array($docs_and_folders))
{
	//echo('<pre>');
	//print_r($docs_and_folders);
	//echo('</pre>');
	//*************************************************************************************************
	//do we need the title field for the document name or not?
	//we get the setting here, so we only have to do it once
	$use_document_title = get_setting('use_document_title');
	//create a sortable table with our data
	$sortable_data = array();
	
	
	while (list ($key, $id) = each($docs_and_folders))
	{
		$row = array ();

		//if the item is invisible, wrap it in a span with class invisible
		$invisibility_span_open = ($id['visibility']==0)?'<span class="invisible">':'';
		$invisibility_span_close = ($id['visibility']==0)?'</span>':'';
		//size (or total size of a directory)		
		$size = $id['filetype']=='folder' ? get_total_folder_size($id['path'],$is_allowed_to_edit) : $id['size'];
		//get the title or the basename depending on what we're using
		if ($use_document_title=='true' AND $id['title']<>'') {
			$document_name=$id['title'];
		} else {
		   $document_name=basename($id['path']);
			//Juan Carlos Ra�a get lastname and firstname when folder is in shared_folder
			//TODO: check if is also necessary (above else)
			if(strstr($document_name, 'sf_user_'))
			{
				$userinfo=Database::get_user_info_from_id(substr($document_name, 8));
				$document_name=$userinfo['lastname'].', '.$userinfo['firstname'];
			}
			elseif(strstr($document_name, 'shared_folder'))
			{
				$document_name=get_lang('SharedFolder');
			}
		}
		//data for checkbox
		if (($is_allowed_to_edit || $group_member_with_upload_rights) AND count($docs_and_folders)>1) {
			$row[] = $id['path'];			
		}
		
		// Show the Owner of the file only in groups				
		$user_link='';	
			
		if(isset($_SESSION['_gid']) && $_SESSION['_gid']!='') {
			if (!empty($id['insert_user_id'])) {
				$user_info=UserManager::get_user_info_by_id($id['insert_user_id']);		
				$user_name=$user_info['firstname'].' '.$user_info['lastname'];
				$user_link='<div class="document_owner">'.get_lang('Owner').': '.display_user_link_document($id['insert_user_id'],$user_name).'</div>';
			}
		}
		
		//icons (clickable)
		//$row[]= build_document_icon_tag($id['filetype'],$id['path']);
		$row[] = create_document_link($http_www,  $document_name,  $id['path'], $id['filetype'],  $size, $id['visibility'], true);

		//document title with hyperlink
		$row[] = create_document_link($http_www,$document_name,$id['path'],$id['filetype'],$size,$id['visibility']).'<br />'.$invisibility_span_open.nl2br(htmlspecialchars($id['comment'],ENT_QUOTES,$charset)).$invisibility_span_close.$user_link;

		//comments => display comment under the document name
		//$row[] = $invisibility_span_open.nl2br(htmlspecialchars($id['comment'])).$invisibility_span_close;
		$display_size = format_file_size($size);
		$row[] = '<span style="display:none;">'.$size.'</span>'.$invisibility_span_open.$display_size.$invisibility_span_close;

		//last edit date
		$last_edit_date=$id['lastedit_date'];		
		$display_date = date_to_str_ago($last_edit_date).'<br><span class="dropbox_date">'.$last_edit_date.'</span>';		
		$row[] = $invisibility_span_open.$display_date.$invisibility_span_close;

		//admins get an edit column
		if ($is_allowed_to_edit || $group_member_with_upload_rights)
		{
			$is_template = (isset($id['is_template'])?$id['is_template']:false);
			// if readonly, check if it the owner of the file or if the user is an admin
			if ($id['insert_user_id'] == $_user['user_id'] || api_is_platform_admin())
			{
				$edit_icons = build_edit_icons($curdirpath,$id['filetype'],$id['path'],$id['visibility'],$key, $is_template,0);
			}
			else
			{
				$edit_icons = build_edit_icons($curdirpath,$id['filetype'],$id['path'],$id['visibility'],$key, $is_template,$id['readonly']);			
			}			
			$row[] = $edit_icons;
		}
		$row[] = $last_edit_date;
		$row[] = $size;
		$total_size=$total_size+$size;
		$sortable_data[] = $row;
		
	}
	//*******************************************************************************************
}
else
{
	$sortable_data='';
	$table_footer='<div style="text-align:center;"><strong>'.get_lang('NoDocsInFolder').'</strong></div>';
}

$column_show=array();	


	if ($is_allowed_to_edit || $group_member_with_upload_rights)
	{
		/* CREATE NEW DOCUMENT OR NEW DIRECTORY / GO TO UPLOAD / DOWNLOAD ZIPPED FOLDER */
		?>
			<!-- create new document or directory -->
			<a href="create_document.php?<?php echo api_get_cidreq();?>&dir=<?php echo $curdirpathurl.$req_gid; ?>"><img src="../img/filenew.gif" border="0" alt="" title="<?php echo get_lang('CreateDoc'); ?>" /></a>
			<a href="create_document.php?<?php echo api_get_cidreq();?>&dir=<?php echo $curdirpathurl.$req_gid; ?>"><?php echo get_lang("CreateDoc"); ?></a>&nbsp;&nbsp;
			<!-- file upload link -->
			<a href="upload.php?<?php echo api_get_cidreq();?>&path=<?php echo $curdirpathurl.$req_gid; ?>"><img src="../img/submit_file.gif" border="0" title="<?php echo get_lang('UplUploadDocument'); ?>" alt="" /></a>
			<a href="upload.php?<?php echo api_get_cidreq();?>&path=<?php echo $curdirpathurl.$req_gid; ?>"><?php echo get_lang('UplUploadDocument'); ?></a>&nbsp;
			<!-- create directory -->
			<a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq();?>&curdirpath=<?php echo $curdirpathurl.$req_gid; ?>&amp;createdir=1"><img src="../img/folder_new.gif" border="0" title="<?php echo get_lang('CreateDir'); ?>" alt ="" /></a>
			<a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq();?>&curdirpath=<?php echo $curdirpathurl.$req_gid; ?>&amp;createdir=1"><?php echo get_lang("CreateDir"); ?></a>&nbsp;
			<a href="quota.php?<?php echo api_get_cidreq();?>"><?php Display::display_icon('statistics.gif', get_lang("ShowCourseQuotaUse")); ?><?php echo get_lang("ShowCourseQuotaUse"); ?></a>
		<?php
	}
	if ($docs_and_folders!=null) {	
		global $total_size;
		if ($total_size!=0){ ?>
	<!-- download zipped folder -->
			<a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq();?>&action=downloadfolder"><img src="../img/zip_save.gif" border="0" title="<?php echo get_lang("Save"); ?> (ZIP)" alt="" /></a>
			<a href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq();?>&action=downloadfolder"><?php echo get_lang("Save"); ?> (ZIP)</a>&nbsp;
<?php
		}
	} 
	// Slideshow by Patrick Cool, May 2004
	include("document_slideshow.inc.php");
	if ($image_present)
	{
		echo "<a href=\"slideshow.php?".api_get_cidreq()."&curdirpath=".$curdirpathurl."\"><img src=\"../img/images_gallery.gif\" border=\"0\" title=\"".get_lang('ViewSlideshow')."\"/>&nbsp;". get_lang('ViewSlideshow') . "</a>";
	}
	echo "</div>";
	echo(build_directory_selector($folders,$curdirpath,(isset($group_properties['directory'])?$group_properties['directory']:array()),true));
//==============================================================================


if (($is_allowed_to_edit || $group_member_with_upload_rights) AND count($docs_and_folders)>1)
{
	$column_show[]=1;
}

$column_show[]=1;
$column_show[]=1;
$column_show[]=1;
$column_show[]=1;

if ($is_allowed_to_edit || $group_member_with_upload_rights)
{
	$column_show[]=1;
}
$column_show[]=0;

$column_order=array();

if ( count($row) == 8 )
{
	$column_order[]=1;
	$column_order[]=2;
	$column_order[]=7;
	$column_order[]=6;
	$column_order[]=5;
} else if ( count($row) == 6 ) {
	$column_order[]=1;
	$column_order[]=2;
	$column_order[]=7;
	$column_order[]=4;	
}

$default_column = $is_allowed_to_edit ? 2 : 1;
$tablename = $is_allowed_to_edit ? 'teacher_table' : 'student_table';
$table = new SortableTableFromArrayConfig($sortable_data,$default_column,20,$tablename,$column_show,$column_order,'ASC');
 
$query_vars['curdirpath'] = $curdirpath;

if(isset($_SESSION['_gid']))
{
	$query_vars['gidReq'] = $_SESSION['_gid'];
}
$query_vars['cidReq'] = api_get_course_id();
$table->set_additional_parameters($query_vars);
$column = 0;

if (($is_allowed_to_edit || $group_member_with_upload_rights) AND count($docs_and_folders)>1)
{
	$table->set_header($column++,'',false);
}
$table->set_header($column++,get_lang('Type'));
$table->set_header($column++,get_lang('Name'));

//$column_header[] = array(get_lang('Comment'),true);  => display comment under the document name
$table->set_header($column++,get_lang('Size'));
$table->set_header($column++,get_lang('Date'));
//admins get an edit column
if ($is_allowed_to_edit || $group_member_with_upload_rights)
{
	$table->set_header($column++,get_lang('Modify'),false);
}

//actions on multiple selected documents
//currently only delete action -> take only DELETE right into account
if (count($docs_and_folders)>1)
{
	if ($is_allowed_to_edit || $group_member_with_upload_rights)
	{
		$form_actions = array();		
		$form_action['delete'] = get_lang('Delete');
		$table->set_form_actions($form_action,'path');
	}
}

$table->display();
if(!empty($table_footer))
{
	echo $table_footer;
}

// footer
Display::display_footer();
?>
