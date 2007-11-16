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
$language_file[] = "document"; //the document file is loaded because most of the upload vocab relates to the document tool
$language_file[] = "learnpath";
$language_file[] = "scormdocument";
// global settings initialisation
// also provides access to main api (inc/lib/main_api.lib.php)
include("../inc/global.inc.php");
require_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
		

$form_style= '
<style>
.row {
	width: 200px;
}
</style>';

$htmlHeadXtra[] = '<script language="javascript" src="../inc/lib/javascript/upload.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
	var myUpload = new upload(0);
</script>';
$htmlHeadXtra[] = $form_style;

if(isset($_POST['convert'])){
	$cwdir = getcwd();
	require('../newscorm/lp_upload.php');
	if(isset($o_ppt) && $first_item_id != 0){
		header('Location: ../newscorm/lp_controller.php?'.api_get_cidreq().'&lp_id='.$o_ppt->lp_id.'&action=view_item&id='.$first_item_id);
	}
	else {
		$errorMessage = get_lang('Ppt2lpError');
	}
}

event_access_tool(TOOL_UPLOAD);


// check access permissions (edit permission is needed to add a document or a LP)
$is_allowed_to_edit = api_is_allowed_to_edit();

if(!$is_allowed_to_edit){
	api_not_allowed(true);
}

$interbreadcrumb[]= array ("url"=>"../newscorm/lp_controller.php?action=list", "name"=> get_lang("Doc"));
$nameTools = get_lang("OogieConversionPowerPoint");
Display :: display_header($nameTools);


?>

<img src="../img/oogie.gif"><br>
<span style="color: #ff6434; font-size: 16px; font-family: Arial; margin-left: 10px;"><?php echo get_lang("WelcomeOogieSubtitle");?></span><br>

<?php

$message=get_lang("WelcomeOogieConverter");

echo '<br>';

$s_style="border-width: 1px;
		 border-style: solid;
		 margin-left: 0;
		 margin-top: 10px;
		 margin-bottom: 0px;
		 min-height: 30px;
		 padding: 5px;
		 position: relative;
		 width: 500px;
		 background-color: #E5EDF9;
		 border-color: #4171B5;
		 color: #000;";

$s_style_error="border-width: 1px;
		 border-style: solid;
		 margin-left: 0;
		 margin-top: 10px;
		 margin-bottom: 10px;
		 min-height: 30px;
		 padding: 5px;
		 position: relative;
		 width: 500px;
		 background-color: #FFD1D1;
		 border-color: #FF0000;
		 color: #000;";

//Display::display_normal_message($message);

echo '<div style="'.$s_style.'"><div style="float:left; margin-right:10px;"><img src="'.api_get_path(WEB_IMG_PATH)."message_normal.gif".'" alt="'.$alt_text.'" '.$attribute_list.'  /></div><div style="margin-left: 43px">'.$message.'</div></div>';

if(!empty($errorMessage)){
	//Display::display_error_message($errorMessage);
	echo '<div style="'.$s_style_error.'"><div style="float:left; margin-right:10px;"><img src="'.api_get_path(WEB_IMG_PATH)."message_error.gif".'" alt="'.$alt_text.'" '.$attribute_list.'  /></div><div style="margin-left: 43px">'.$errorMessage.'</div></div>';
}
echo '
<style>
.row{
	width:90%;
}
div.row div.label {
	width: 0%;
}

div.row div.formw {
	width: 100%;
}
.convert_button{
	background: url("../img/scorm.gif") 0px 0px no-repeat;
	padding: 2px 0px 2px 22px;
}

</style>';
$form = new FormValidator('update_course', 'POST', '', '', 'style="margin: 0;"');

// build the form

$form -> addElement ('html','<br>');

$group = array();
$group[] = FormValidator::createElement ('image','ppt_img','../img/powerpoint_big.gif','align="absbottom"');
$group[] = FormValidator::createElement ('file', 'user_file',null);
$group[] = FormValidator::createElement ('submit', 'convert', get_lang('ConvertToLP'), 'class="convert_button"');
$form -> addGroup($group);

$form -> addElement('html','<br /><div style="margin:7px;">'.get_lang('UploadMaxSize').' : '.ini_get('post_max_size').'</div>');

$form -> addElement ('hidden', 'ppt2lp', 'true');

$form -> add_real_progress_bar('ppt2lp', 'qf_group_1', 1, true);


// display the form
$form -> display();

/*
==============================================================================
  FOOTER
==============================================================================
*/
Display::display_footer();

?>