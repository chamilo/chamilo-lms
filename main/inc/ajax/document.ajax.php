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
    DocumentManager::upload_document($_FILES, $_POST['curdirpath'], '', '', 0);
    $file = $_FILES['file'];
    echo '{"name":"'.$file['name'].'","type":"'.$file['type'].'","size":"'.$file['size'].'"}';
}
exit;