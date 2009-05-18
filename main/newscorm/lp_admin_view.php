<?php // $Id: index.php 16620 2008-10-25 20:03:54Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue Notre Dame, 152, B-1140 Evere, Belgium, info@dokeos.com
==============================================================================
*/


/**
============================================================================== 
* This is a learning path creation and player tool in Dokeos - previously learnpath_handler.php
*
* @author Patrick Cool
* @author Denes Nagy
* @author Roan Embrechts, refactoring and code cleaning
* @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
* @package dokeos.learnpath
============================================================================== 
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/ 
$this_section=SECTION_COURSES;

api_protect_course_script();

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/ 
//the main_api.lib.php, database.lib.php and display.lib.php
//libraries are included by default

include('learnpath_functions.inc.php');
//include('../resourcelinker/resourcelinker.inc.php');
include('resourcelinker.inc.php');
//rewrite the language file, sadly overwritten by resourcelinker.inc.php
$language_file = "learnpath";

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/ 
$is_allowed_to_edit = api_is_allowed_to_edit();

$tbl_lp = Database::get_course_table('lp');
$tbl_lp_item = Database::get_course_table('lp_item');
$tbl_lp_view = Database::get_course_table('lp_view');

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];
/*
$chapter_id     = $_GET['chapter_id'];
$title          = $_POST['title'];
$description   = $_POST['description'];
$Submititem     = $_POST['Submititem'];
$action         = $_REQUEST['action'];
$id             = (int) $_REQUEST['id'];
$type           = $_REQUEST['type'];
$direction      = $_REQUEST['direction'];
$moduleid       = $_REQUEST['moduleid'];
$prereq         = $_REQUEST['prereq'];
$type           = $_REQUEST['type'];
*/
/*
==============================================================================
		MAIN CODE
==============================================================================
*/
// using the resource linker as a tool for adding resources to the learning path
if ($action=="add" and $type=="learnpathitem")
{
	 $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}
if ( (! $is_allowed_to_edit) or ($isStudentView) )
{
	error_log('New LP - User not authorized in lp_admin_view.php');
	header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
//from here on, we are admin because of the previous condition, so don't check anymore

$sql_query = "SELECT * FROM $tbl_lp WHERE id = $learnpath_id"; 
$result=api_sql_query($sql_query);
$therow=Database::fetch_array($result); 

//$admin_output = '';
/*
-----------------------------------------------------------
	Course admin section
	- all the functions not available for students - always available in this case (page only shown to admin)
-----------------------------------------------------------
*/ 
/*==================================================
			SHOWING THE ADMIN TOOLS
 ==================================================*/



/*==================================================
	prerequisites setting end
 ==================================================*/		  
if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {	
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}

$interbreadcrumb[]= array ("url"=>"lp_controller.php?action=list", "name"=> get_lang("_learning_path"));

$interbreadcrumb[]= array ("url"=>api_get_self()."?action=admin_view&lp_id=$learnpath_id", "name" => stripslashes("{$therow['name']}"));

//Theme calls
$show_learn_path=true;
$lp_theme_css=$_SESSION['oLP']->get_theme(); 
Display::display_header(null,'Path');
//api_display_tool_title($therow['name']);

$suredel = trim(get_lang('AreYouSureToDelete'));
//$suredelstep = trim(get_lang('AreYouSureToDeleteSteps'));
?>
<script type='text/javascript'>
/* <![CDATA[ */
function stripslashes(str) {
	str=str.replace(/\\'/g,'\'');
	str=str.replace(/\\"/g,'"');
	str=str.replace(/\\\\/g,'\\');
	str=str.replace(/\\0/g,'\0');
	return str;
}
function confirmation(name)
{
	name=stripslashes(name);	
	if (confirm("<?php echo $suredel; ?> " + name + " ?"))
	{
		return true;
	}
	else
	{
		return false;
	}	
}
</script>
<?php

/*
-----------------------------------------------------------
	DISPLAY SECTION
-----------------------------------------------------------
*/

switch($_GET['action'])
{
	case 'edit_item':
		
		if(isset($is_success) && $is_success === true)
		{
			Display::display_confirmation_message(get_lang("_learnpath_item_edited"));
		}
		else
		{
			echo $_SESSION['oLP']->display_edit_item($_GET['id']);
		}
		
		break;
	
	case 'delete_item':
		
		if(isset($is_success) && $is_success === true)
		{
			Display::display_confirmation_message(get_lang("_learnpath_item_deleted"));
		}
		
		break;
}

// POST action handling (uploading mp3, deleting mp3)
if (isset($_POST['save_audio']))
{
	// deleting the audio fragments
	foreach ($_POST as $key=>$value)
	{
		if (substr($key,0,9) == 'removemp3')
		{
			$lp_items_to_remove_audio[] = str_ireplace('removemp3','',$key);
			
			// removing the audio from the learning path item
			$tbl_lp_item = Database::get_course_table('lp_item');
			$in = implode(',',$lp_items_to_remove_audio);
		}
	}
	if (count($lp_items_to_remove_audio)>0)
	{
		$sql 	= "UPDATE $tbl_lp_item SET audio = '' WHERE id IN (".$in.")";
		$result = api_sql_query($sql, __FILE__, __LINE__);
	}		
	
	// uploading the audio files
	foreach ($_FILES as $key=>$value)
	{
		if (substr($key,0,7) == 'mp3file' AND !empty($_FILES[$key]['tmp_name']))
		{
			// the id of the learning path item
			$lp_item_id = str_ireplace('mp3file','',$key);
			
			// create the audio folder if it does not exist yet
			global $_course;
			$filepath = api_get_path('SYS_COURSE_PATH').$_course['path'].'/document/';
			if(!is_dir($filepath.'audio'))
			{
				$perm = api_get_setting('permissions_for_new_directories');
				$perm = octdec(!empty($perm)?$perm:'0770');
				mkdir($filepath.'audio',$perm);
				$audio_id=add_document($_course,'/audio','folder',0,'audio');
				api_item_property_update($_course, TOOL_DOCUMENT, $audio_id, 'FolderCreated', api_get_user_id());				
			}
		
			// check if file already exits into document/audio/			
			$file_name = $_FILES[$key]['name'];
			$file_name=stripslashes($file_name);
			//add extension to files without one (if possible)
			$file_name=add_ext_on_mime($file_name,$_FILES[$key]['type']);
						
			$clean_name = replace_dangerous_char($file_name);
			$clean_name = replace_accents($clean_name);
			//no "dangerous" files
			$clean_name = disable_dangerous_file($clean_name);			

			$check_file_path = api_get_path('SYS_COURSE_PATH').$_course['path'].'/document/audio/'.$clean_name;
						
			if (file_exists($check_file_path)) {
				$file = $clean_name;	
			} else {
				// upload the file in the documents tool
				include_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
				$file_path = handle_uploaded_document($_course, $_FILES[$key],api_get_path('SYS_COURSE_PATH').$_course['path'].'/document','/audio',api_get_user_id(),'','','','','',false);									
								
				// getting the filename only
				$file_components = explode('/',$file_path);
				$file = $file_components[count($file_components)-1];	
			}

			// store the mp3 file in the lp_item table
			$tbl_lp_item = Database::get_course_table('lp_item');
			$sql_insert_audio = "UPDATE $tbl_lp_item SET audio = '".Database::escape_string($file)."' WHERE id = '".Database::escape_string($lp_item_id)."'";
			api_sql_query($sql_insert_audio, __FILE__, __LINE__);			
			
		}
	}
	
	Display::display_confirmation_message(get_lang('ChangesStored'));
}

echo $_SESSION['oLP']->overview();

/*
==============================================================================
		FOOTER 
==============================================================================
*/ 
Display::display_footer();
?>
