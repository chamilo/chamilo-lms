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
        $msg = urlencode(get_lang('UnknownPackageFormat'));
}else{
        $msg = urlencode(get_lang('UplUploadSucceeded'));
}
header('location: ../newscorm/lp_controller.php?action=list&dialog_box='.$msg);
?>