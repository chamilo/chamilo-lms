<?php
/**
 * Xajax action to handle the real progress bar for an upload
 * @author Eric Marguin
 */

include("../global.inc.php");
require_once api_get_path(LIBRARY_PATH).'xajax/xajax.inc.php';	
$xajax_upload = new Xajax();
$xajax_upload -> registerFunction ('updateProgress');
$xajax_upload -> processRequests();

/**
 * This function updates the progress bar
 * @param div_id where the progress bar is displayed
 * @param upload_id the identifier given in the field UPLOAD_IDENTIFIER
 */
function updateProgress($div_id, $upload_id){
	
	$objResponse = new XajaxResponse();
	$ul_info = uploadprogress_get_info($upload_id);
	$percent = intval($ul_info['bytes_uploaded']*100/$ul_info['bytes_total']);
	$objResponse -> addAssign($div_id.'_label' , 'innerHTML', get_lang('Uploading').' : '.$percent.' %');
	$objResponse -> addAssign($div_id.'_filled' , 'style.width', $percent.'%');
	
	
	return $objResponse;
	
}




?>
