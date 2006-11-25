<?php // $Id: upload.php 10195 2006-11-25 15:26:00Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
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

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
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

// name of the language file which needs to be included
// 'inc.php' is automatically appended to the file name
$langFile = "document";

// global settings initialisation
// also provides access to main api (inc/lib/main_api.lib.php)
include("../inc/global.inc.php");
include('document.inc.php');

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
<!-- //
function check_unzip() {
	if(document.upload.unzip.checked==true){
	document.upload.if_exists[0].disabled=true;
	document.upload.if_exists[1].checked=true;
	document.upload.if_exists[2].disabled=true;
	}
	else {
	document.upload.if_exists[0].checked=true;
	document.upload.if_exists[0].disabled=false;
	document.upload.if_exists[2].disabled=false;
	}
}
// -->
</script>";

/*
-----------------------------------------------------------
	Variables
	- some need defining before inclusion of libraries
-----------------------------------------------------------
*/
$is_allowed_to_edit = api_is_allowed_to_edit();

$courseDir   = $_course['path']."/document";
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path.$courseDir;
$noPHP_SELF=true;

//this needs cleaning!
if(isset($_SESSION['_gid']) && $_SESSION['_gid']!='') //if the group id is set, check if the user has the right to be here
{
	//needed for group related stuff
	include_once(api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php');
	//get group info
	$group_properties = GroupManager::get_group_properties($_SESSION['_gid']);
	$noPHP_SELF=true;
		
	if($is_allowed_to_edit || GroupManager::is_user_in_group($_user['user_id'],$_SESSION['_gid'])) //only courseadmin or group members allowed
	{
		$to_group_id = $_SESSION['_gid'];
		$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
		$interbreadcrumb[]= array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace'));
	}
	else
	{
		api_not_allowed();
	}
}
elseif($is_allowed_to_edit) //admin for "regular" upload, no group documents
{
	$to_group_id = 0;
	$req_gid = '';
}
else  //no course admin and no group member...
{
	api_not_allowed();
}

//what's the current path?
if(isset($_GET['path']) && $_GET['path']!='')
{
	$path = $_GET['path'];
}
elseif (isset($_POST['curdirpath']))
{
	$path = $_POST['curdirpath'];
}
else 
{
	$path = '/';
}

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/

//many useful functions in main_api.lib.php, by default included

include_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
include_once(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
include_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');

//check the path
//if the path is not found (no document id), set the path to /
if(!DocumentManager::get_document_id($_course,$path))
{
	$path = '/';
}
//group docs can only be uploaded in the group directory
if($to_group_id!=0 && $path=='/')
{
	$path = $group_properties['directory'];
}

//if we want to unzip a file, we need the library
if (isset($_POST['unzip']) && $_POST['unzip'] == 1)
{
	include(api_get_path(LIBRARY_PATH).'pclzip/pclzip.lib.php');
}
/*
-----------------------------------------------------------
	Variables
-----------------------------------------------------------
*/
$max_filled_space = DocumentManager::get_course_quota();

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

$nameTools = get_lang('UplUploadDocument');
$interbreadcrumb[]=array("url"=>"./document.php?curdirpath=".urlencode($path).$req_gid, "name"=> $langDocuments);
Display::display_header($nameTools,"Doc");

if($to_group_id !=0) //add group name after for group documents
{
	$add_group_to_title = ' ('.$group_properties['name'].')';
}
//show the title
api_display_tool_title($nameTools.$add_group_to_title);

/*
-----------------------------------------------------------
	Here we do all the work
-----------------------------------------------------------
*/

//user has submitted a file
if(isset($_FILES['user_upload']))
{
	//echo("<pre>");
	//print_r($_FILES['user_upload']);
	//echo("</pre>");
	
	$upload_ok = process_uploaded_file($_FILES['user_upload']);
	if($upload_ok)
	{
		//file got on the server without problems, now process it
		$new_path = handle_uploaded_document($_course, $_FILES['user_upload'],$base_work_dir,$_POST['curdirpath'],$_user['user_id'],$to_group_id,$to_user_id,$max_filled_space,$_POST['unzip'],$_POST['if_exists']);
    	$new_comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    	$new_title = isset($_POST['title']) ? trim($_POST['title']) : '';
		
    	if ($new_path && ($new_comment || $new_title))
    	if (($docid = DocumentManager::get_document_id($_course, $new_path)))
    	{
        	$table_document = Database::get_course_table(TABLE_DOCUMENT);
        	$ct = '';
        	if ($new_comment) $ct .= ", comment='$new_comment'";
        	if ($new_title)   $ct .= ", title='$new_title'";
        	api_sql_query("UPDATE $table_document SET" . substr($ct, 1) . 
        	    " WHERE id = '$docid'", __FILE__, __LINE__);
    	}
		//check for missing images in html files
		$missing_files = check_for_missing_files($base_work_dir.$_POST['curdirpath'].$new_path);
		if($missing_files)
		{
			//show a form to upload the missing files
			Display::display_normal_message(build_missing_files_form($missing_files,$_POST['curdirpath'],$_FILES['user_upload']['name']));
		}
	}
}
//missing images are submitted
if(isset($_POST['submit_image']))
{
	$number_of_uploaded_images = count($_FILES['img_file']['name']);
	//if images are uploaded
	if ($number_of_uploaded_images > 0)
	{
		//we could also create a function for this, I'm not sure...
		//create a directory for the missing files
		$img_directory = str_replace('.','_',$_POST['related_file']."_files");
		$missing_files_dir = create_unexisting_directory($_course,$_user['user_id'],$to_group_id,$to_user_id,$base_work_dir,$img_directory);
		//put the uploaded files in the new directory and get the paths
		$paths_to_replace_in_file = move_uploaded_file_collection_into_directory($_course, $_FILES['img_file'],$base_work_dir,$missing_files_dir,$_user['user_id'],$to_group_id,$to_user_id,$max_filled_space);
		//open the html file and replace the paths
		replace_img_path_in_html_file($_POST['img_file_path'],$paths_to_replace_in_file,$base_work_dir.$_POST['related_file']);
		//update parent folders
		item_property_update_on_folder($_course,$_POST['curdirpath'],$_user['user_id']);	
	}
}
//they want to create a directory
if(isset($_POST['create_dir']) && $_POST['dirname']!='')
{
	$added_slash = ($path=='/')?'':'/';
	$dir_name = $path.$added_slash.replace_dangerous_char($_POST['dirname']);
	$created_dir = create_unexisting_directory($_course,$_user['user_id'],$to_group_id,$to_user_id,$base_work_dir,$dir_name,$_POST['dirname']);
	if($created_dir)
	{
		//Display::display_normal_message("<strong>".$created_dir."</strong> was created!");
		Display::display_normal_message(get_lang('DirCr'));
		$path = $created_dir;
	}
	else 
	{
		display_error(get_lang('CannotCreateDir'));
	}
}

//tracking not needed here?
//event_access_tool(TOOL_DOCUMENT);

/*============================================================================*/
?>

<?php
//=======================================//
//they want to create a new directory//
//=======================================//

if(isset($_GET['createdir']))
{
	//create the form that asks for the directory name
	$new_folder_text = '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	$new_folder_text .= '<input type="hidden" name="curdirpath" value="'.$path.'"/>';
	$new_folder_text .= get_lang('NewDir') .' ';
	$new_folder_text .= '<input type="text" name="dirname"/>';
	$new_folder_text .= '<input type="submit" name="create_dir" value="'.get_lang('Ok').'"/>';
	$new_folder_text .= '</form>';
	//show the form
	Display::display_normal_message($new_folder_text);
}
else {	//give them a link to create a directory
	?>
	<p><a href="<?php echo $_SERVER['PHP_SELF']; ?>?path=<?php echo $path; ?>&amp;createdir=1"><img src="../img/folder_new.gif" border="0" align="absmiddle" alt ="" /> <?php echo(get_lang('CreateDir'));?></a></p>
	<?php
}
?>

<div id="folderselector">
<?php
//form to select directory
$folders = DocumentManager::get_all_document_folders($_course,$to_group_id,$is_allowed_to_edit);
echo(build_directory_selector($folders,$path,$group_properties['directory']));
?>
</div>

<!-- start upload form -->
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" name="upload" enctype="multipart/form-data">
<!-- <input type="hidden" name="MAX_FILE_SIZE" value="5400"> -->
<input type="hidden" name="curdirpath" value="<?php echo $path; ?>">
<table>
<tr>
<td valign="top">
<?php echo get_lang('File'); ?>
</td>
<td>
<input type="file" name="user_upload"/> 
</td>
</tr>
<?php
if(get_setting('use_document_title')=='true')
{
	?>
    <tr>
    <td><?php echo get_lang('Title');?></td>
    <td><input type="text" size="20" name="title" style="width:300px;"></td>
    </tr>
	<?php 
}
?>
    <tr>
    <td valign="top"><?php echo get_lang('Comment');?></td>
    <td><textarea rows="3" cols="20" name="comment" wrap="virtual" style="width:300px;"></textarea></td>
    </tr>
    <tr>
<td valign="top">
<?php echo get_lang('Options'); ?>
</td>
<td>
- <input type="checkbox" name="unzip" value="1" onclick="check_unzip()"/> <?php echo(get_lang('Uncompress'));?><br/>
- <?php echo (get_lang('UplWhatIfFileExists'));?><br/>
&nbsp;&nbsp;&nbsp;<input type="radio" name="if_exists" value="nothing" title="<?php echo (get_lang('UplDoNothingLong'));?>" checked="checked"/>  <?php echo (get_lang('UplDoNothing'));?><br/>
&nbsp;&nbsp;&nbsp;<input type="radio" name="if_exists" value="overwrite" title="<?php echo (get_lang('UplOverwriteLong'));?>"/> <?php echo (get_lang('UplOverwrite'));?><br/>
&nbsp;&nbsp;&nbsp;<input type="radio" name="if_exists" value="rename" title="<?php echo (get_lang('UplRenameLong'));?>"/> <?php echo (get_lang('UplRename'));?>

</td>
</tr>
</table>

<input type="submit" value="<?php echo(get_lang('Ok'));?>">
</form>
<!-- end upload form -->

 <!-- so they can get back to the documents   --> 	 
 <p><?php echo (get_lang('Back'));?> <?php echo (get_lang('To'));?> <a href="document.php?curdirpath=<?php echo $path; ?>"><?php echo (get_lang('DocumentsOverview'));?></a></p>

<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
