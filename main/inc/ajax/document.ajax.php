<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls for the document upload 
 */
require_once '../global.inc.php';

require_once api_get_path(LIBRARY_PATH).'document.lib.php';

$action = $_REQUEST['a'];
switch($action) {	
	case 'upload_file':
		api_protect_course_script(true);
		
		//User access same as upload.php
		$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
		
		// This needs cleaning!
		if (api_get_group_id()) {   
		    if ($is_allowed_to_edit || GroupManager::is_user_in_group(api_get_user_id(), api_get_group_id())) { // Only courseadmin or group members allowed        
		    } else {
		        exit;
		    }
		} elseif ($is_allowed_to_edit || is_my_shared_folder(api_get_user_id(), $_POST['curdirpath'], api_get_session_id())) {
		} else { // No course admin and no group member...
		    exit;
		}
		
		if (!empty($_FILES)) {		    
		    require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';
		    $file = $_FILES['file'];
		    
		    $result = DocumentManager::upload_document($_FILES, $_POST['curdirpath'], $file['name'], '', 0, 'overwrite', false, false);		    
		    $json = array();
		    $json['name'] = Display::url(api_htmlentities($file['name']), api_htmlentities($result['url']), array('target'=>'_blank'));
		    $json['type'] = api_htmlentities($file['type']);
		    $json['size'] = format_file_size($file['size']);    
		    if (!empty($result) && is_array($result)) {
		        $json['result'] = Display::return_icon('accept.png', get_lang('Uploaded'));    
		    } else {
		        $json['result'] = Display::return_icon('exclamation.png', get_lang('Error'));
		    }
		    echo json_encode($json);    
		}
		break;
	case 'document_preview':
		$course_info = api_get_course_info_by_id($_REQUEST['course_id']);		
		if (!empty($course_info) && is_array($course_info)) {
			echo DocumentManager::get_document_preview($course_info, false, '_blank', $_REQUEST['session_id']);
		}
		break;		
}
exit;