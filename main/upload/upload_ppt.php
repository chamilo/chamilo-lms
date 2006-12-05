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
$language_file = "document"; //the document file is loaded because most of the upload vocab relates to the document tool
// global settings initialisation
// also provides access to main api (inc/lib/main_api.lib.php)
include("../inc/global.inc.php");
require_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');

$htmlHeadXtra[] = '<script language="javascript" src="../inc/lib/javascript/upload.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
	var myUpload = new upload(0);
</script>';

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
$nameTools = get_lang("OogieConversionPowerPoint");
Display :: display_header($nameTools);


// check access permissions (edit permission is needed to add a document or a LP)
$is_allowed_to_edit = api_is_allowed_to_edit();

if(!$is_allowed_to_edit){
	api_not_allowed();
}

?>

<img src="../img/oogie.gif"><br>
<span style="color: #999999; font-style: italic; font-size: 15px; font-weight: bold; margin-left: 65px;"><? echo get_lang("WelcomeOogieSubtitle");?></span><br>

<?

$message=get_lang("WelcomeOogieConverter");

echo '<br>';

Display::display_normal_message($message);

if(!empty($errorMessage)){
	Display::display_error_message($errorMessage);
}

echo '<div id="dynamic_div" style="display:block;margin-left:40%;margin-top:10px;height:50px;"></div>';

echo '<div id="upload_form_div" name="form_div" style="display:block;">';

echo '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER['PHP_SELF'].'" onsubmit="myUpload.start(\'dynamic_div\',\'../img/progress_bar.gif\',\''.get_lang("Converting").'\',\'upload_form_div\');">';
echo '<img src="../img/powerpoint_big.gif" align="absbottom">
		&nbsp;&nbsp;<input type="file" name="user_file">
		<br><br>
		<input type="submit" name="convert" value="'.get_lang('ConvertToLP').'">
		&nbsp;&nbsp;
		<img src="../img/scormbuilder.gif" align="absmiddle">';
echo '</form>';

echo '</div>';

echo "<br><br><br><br>";

/*
==============================================================================
  FOOTER
==============================================================================
*/
Display::display_footer();

?>