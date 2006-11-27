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

echo '<form method="POST" action="">';
echo '<img src="../img/powerpoint_big.gif" align="absbottom">&nbsp;&nbsp;<input type="file" name="user_file"><br><br><input type="submit" name="submit" value="'.get_lang('ConvertToLP').'">&nbsp;&nbsp;<img src="../img/scormbuilder.gif" align="absmiddle">';
echo '</form>';


/*
==============================================================================
  FOOTER
==============================================================================
*/
Display::display_footer();

?>