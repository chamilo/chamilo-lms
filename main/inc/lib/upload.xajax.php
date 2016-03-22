<?php
/**
 * @package chamilo.library
 */
/**
 * Code
 */
include(dirname(__FILE__).'/../global.inc.php');
$xajax_upload = new Xajax();
$xajax_upload -> registerFunction ('updateProgress');
$xajax_upload -> processRequests();

/**
 * This function updates the progress bar
 * @param div_id where the progress bar is displayed
 * @param upload_id the identifier given in the field UPLOAD_IDENTIFIER
 */
function updateProgress($div_id, $upload_id, $waitAfterupload = false) {

	$objResponse = new xajaxResponse();
	$ul_info = uploadprogress_get_info($upload_id);
	$percent = intval($ul_info['bytes_uploaded']*100/$ul_info['bytes_total']);
	if($waitAfterupload && $ul_info['est_sec']<2) {
		$percent = 100;
		$objResponse->addAssign($div_id.'_label' , 'innerHTML', get_lang('UploadFile').' : '.$percent.' %');
		$objResponse->addAssign($div_id.'_waiter_frame','innerHTML', Display::return_icon('progress_bar.gif'));
		$objResponse->addScript('clearInterval("myUpload.__progress_bar_interval")');
	}
	$objResponse->addAssign($div_id.'_label', 'innerHTML', get_lang('UploadFile').' : '.$percent.' %');
	$objResponse->addAssign($div_id.'_filled', 'style.width', $percent.'%');

	return $objResponse;
}
