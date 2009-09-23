<?php //$id: $
/**
 * Process part of the SCORM sub-process for upload. This script MUST BE included by upload/index.php
 * as it prepares most of the variables needed here.
 * @package dokeos.upload
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Process the SCORM package and return to the SCORM tool
 */
$language_file = "scorm";
$cwdir = getcwd();
require('../newscorm/lp_upload.php');
//reinit current working directory as many functions in upload change it
chdir($cwdir);
$error = api_failure::get_last_failure();
if($error=='not_a_learning_path')
{
        $msg = urlencode(get_lang('ScormUnknownPackageFormat'));
		$dialogtype = 'error';
}else{
	if (api_get_setting('search_enabled')=='true') {
        require_once(api_get_path(LIBRARY_PATH) . 'specific_fields_manager.lib.php');
    	$specific_fields = get_specific_field_list();

    	foreach ($specific_fields as $specific_field) {
    		$values = explode(',', trim($_POST[$specific_field['code']]));
    		if ( !empty($values) ) {
    			foreach ($values as $value) {
    				$value = trim($value);
    				if ( !empty($value) ) {
    					add_specific_field_value($specific_field['id'], api_get_course_id(), TOOL_LEARNPATH, $oScorm->lp_id, $value);
    				}
    			}
    		}
    	}
    }
	$msg = urlencode(get_lang('UplUploadSucceeded'));
	$dialogtype = 'confirmation';
}
header('location: ../newscorm/lp_controller.php?action=list&dialog_box='.$msg.'&dialogtype='.$dialogtype);
?>