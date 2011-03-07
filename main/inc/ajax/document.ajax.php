<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls for the document upload 
 */
require_once '../global.inc.php';
if (api_is_anonymous()){
    exit;
}
if(!empty($_FILES)) {
    require_once api_get_path(LIBRARY_PATH).'document.lib.php';
    require_once api_get_path(LIBRARY_PATH).'fileDisplay.lib.php';
    $result = DocumentManager::upload_document($_FILES, $_POST['curdirpath'], '', '', 0, 'overwrite');
    $file = $_FILES['file'];    
    $json = array();
    $json['name'] = Display::url(api_htmlentities($file['name']), $result['url'], array('target'=>'_blank'));
    $json['type'] = api_htmlentities($file['type']);
    $json['size'] = format_file_size($file['size']);    
    if (!empty($result) && is_array($result)) {
        $json['result'] = Display::return_icon('accept.png', get_lang('Uploaded'));    
    } else {
        $json['result'] = Display::return_icon('exclamation.png', get_lang('Error'));
    }
    echo json_encode($json);    
}
exit;