<?php
/* For licensing terms, see /license.txt */
/**
 * Action controller for the upload process. The display scripts (web forms) redirect
 * the process here to do what needs to be done with each file.
 * @package chamilo.upload
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
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'document.lib.php';


$form_style= '
<style>
.convert_button{
	background: url("../img/icons/22/learnpath.png") 0px 0px no-repeat;
	padding: 2px 0px 2px 22px;
}
#dynamic_div_container{float:left;margin-right:10px;}
#dynamic_div_waiter_container{float:left;}
</style>';

$htmlHeadXtra[] = '<script language="javascript" src="../inc/lib/javascript/upload.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
	var myUpload = new upload(0);
</script>';
$htmlHeadXtra[] = $form_style;

if(isset($_POST['convert'])){
	$cwdir = getcwd();
	if(isset($_FILES['user_file'])) {
		$allowed_extensions = array('odp','sxi','ppt','pps','sxd','pptx');
		if(in_array(strtolower(pathinfo($_FILES['user_file']['name'],PATHINFO_EXTENSION)),$allowed_extensions)) {
			require('../newscorm/lp_upload.php');
			if(isset($o_ppt) && $first_item_id != 0){
				if (api_get_setting('search_enabled')=='true') {
                    require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');
                    $specific_fields = get_specific_field_list();
                    foreach ($specific_fields as $specific_field) {
    					$values = explode(',', trim($_POST[$specific_field['code']]));
    					if ( !empty($values) ) {
    						foreach ($values as $value) {
    							$value = trim($value);
    							if ( !empty($value) ) {
    								add_specific_field_value($specific_field['id'], api_get_course_id(), TOOL_LEARNPATH, $o_ppt->lp_id, $value);
    							}
    						}
    					}
    				}
                }
				header('Location: ../newscorm/lp_controller.php?'.api_get_cidreq().'&lp_id='.$o_ppt->lp_id.'&action=view_item&id='.$first_item_id);
			} else {
				if(!empty($o_ppt->error))
					$errorMessage = $o_ppt->error;
				else
					$errorMessage = get_lang('OogieUnknownError');
			}
		} else {
			$errorMessage = get_lang('OogieBadExtension');
		}
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
$message = get_lang("WelcomeOogieConverter");

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

$alt_text = '';
$attribute_list = '';
echo '<div style="'.$s_style.'"><div style="float:left; margin-right:10px;"><img src="'.api_get_path(WEB_IMG_PATH)."message_normal.gif".'" alt="'.$alt_text.'" '.$attribute_list.'  /></div><div style="margin-left: 43px">'.$message.'</div></div>';

if(!empty($errorMessage)) {	
	echo '<div style="'.$s_style_error.'"><div style="float:left; margin-right:10px;"><img src="'.api_get_path(WEB_IMG_PATH)."message_error.gif".'" alt="'.$alt_text.'" '.$attribute_list.'  /></div><div style="margin-left: 43px">'.$errorMessage.'</div></div>';
}

$form = new FormValidator('upload_ppt', 'POST', '', '');
$form->addElement('header',get_lang("WelcomeOogieSubtitle"));

// build the form
$div_upload_limit = get_lang('UploadMaxSize').' : '.ini_get('post_max_size');
$form -> addElement ('file', 'user_file', array('<img src="../img/powerpoint_big.gif" />', $div_upload_limit));
$form -> addElement ('checkbox', 'take_slide_name','', get_lang('TakeSlideName'));
if (api_get_setting('search_enabled')=='true') {
    require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');
    $specific_fields = get_specific_field_list();
    $form -> addElement ('checkbox', 'index_document','', get_lang('SearchFeatureDoIndexDocument'));        
    $form -> addElement ('select_language', 'language', get_lang('SearchFeatureDocumentLanguage'));    
    foreach ($specific_fields as $specific_field) {
        $form -> addElement ('text', $specific_field['code'], $specific_field['name'].' : ');
    }
}

$form -> addElement ('style_submit_button', 'convert', get_lang('ConvertToLP'), 'class="convert_button"');

$form -> addElement ('hidden', 'ppt2lp', 'true');

$form -> add_real_progress_bar(md5(rand(0,10000)), 'user_file', 1, true);
$defaults = array('take_slide_name'=>'checked="checked"','index_document'=>'checked="checked"');
$form->setDefaults($defaults);
// display the form
$form -> display();

/*
  FOOTER
*/
Display::display_footer();