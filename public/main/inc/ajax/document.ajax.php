<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * Responses to AJAX calls for the document upload.
 */
require_once __DIR__.'/../global.inc.php';

$repo = Container::getDocumentRepository();

$action = $_REQUEST['a'];
switch ($action) {
    case 'get_dir_size':
        api_protect_course_script(true);
        $path = isset($_GET['path']) ? $_GET['path'] : '';
        $isAllowedToEdit = api_is_allowed_to_edit();
        $size = $repo->getFolderSize(api_get_course_int_id(), $path);

        echo format_file_size($size);
        break;
    case 'get_document_quota':
        // Getting the course quota
        $courseQuota = DocumentManager::get_course_quota();

        // Calculating the total space
        $total = $repo->getTotalSpace(api_get_course_int_id());

        // Displaying the quota
        echo DocumentManager::displaySimpleQuota($courseQuota, $total);
        break;
    case 'document_preview':
        $courseInfo = api_get_course_info_by_id($_REQUEST['course_id']);
        if (!empty($courseInfo) && is_array($courseInfo)) {
            echo DocumentManager::get_document_preview(
                $courseInfo,
                false,
                '_blank',
                $_REQUEST['session_id']
            );
        }
        break;
    case 'document_destination':
        //obtained the bootstrap-select selected value via ajax
        $dirValue = isset($_POST['dirValue']) ? $_POST['dirValue'] : null;
        echo Security::remove_XSS($dirValue);
        break;
}
exit;
