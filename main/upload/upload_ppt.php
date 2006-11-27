<?php // $Id$
/**
 * Action controller for the upload process. The display scripts (web forms) redirect
 * the process here to do what needs to be done with each file.
 * @package dokeos.upload
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * First, initialise the script 
 */
// name of the language file which needs to be included
// 'inc.php' is automatically appended to the file name
$langFile = "document"; //the document file is loaded because most of the upload vocab relates to the document tool
// global settings initialisation
// also provides access to main api (inc/lib/main_api.lib.php)
include("../inc/global.inc.php");
require_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');


if(isset($_POST['convert'])){
	$cwdir = getcwd();
	require('../newscorm/lp_upload.php');
	if(isset($o_ppt)){
		header('Location: ../newscorm/lp_controller.php?'.api_get_cidreq().'&action=build&lp_id='.$o_ppt->lp_id);
	}
	else {
		$errorMessage = get_lang('Ppt2lpError');
	}
}

event_access_tool(TOOL_UPLOAD);



$interbreadcrumb[]= array ("url"=>"../newscorm/lp_controller.php?action=list", "name"=> get_lang(TOOL_LEARNPATH));
$nameTools = get_lang("FileUpload");
Display :: display_header($nameTools);


// check access permissions (edit permission is needed to add a document or a LP)
$is_allowed_to_edit = api_is_allowed_to_edit();

if(!$is_allowed_to_edit){
	api_not_allowed();
}

?>

<img src="../img/oogie.gif">

<?

$message="Welcome to Oogie PowerPoint converter<br>1. Browse your hard disk to find any .ppt or .odp file<br>2. Upload it to Oogie. It will tranform it into a Scorm learning path.<br>3. You will then be allowed to add audio comments on each slide and inserts test between slides for evaluation";

Display::display_normal_message($message);

echo '<br><br>';

if(!empty($errorMessage)){
	Display::display_error_message($errorMessage);
}

echo '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER['PHP_SELF'].'">';
echo '<img src="../img/powerpoint_big.gif" align="absbottom">
		&nbsp;&nbsp;<input type="file" name="user_file">
		<br><br>
		<input type="submit" name="convert" value="'.get_lang('ConvertToLP').'">
		&nbsp;&nbsp;
		<img src="../img/scormbuilder.gif" align="absmiddle">';
echo '</form>';


/*
==============================================================================
  FOOTER
==============================================================================
*/
Display::display_footer();

?>