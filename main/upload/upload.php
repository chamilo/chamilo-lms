<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Action controller for the upload process. The display scripts (web forms) redirect
 * the process here to do what needs to be done with each file.
 *
 * @package chamilo.upload
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$toolFromSession = Session::read('my_tool');

// return to index if no tool is set
if (empty($toolFromSession)) {
    header('location:index.php');
    exit;
}

// check access permissions (edit permission is needed to add a document or a LP)
$is_allowed_to_edit = api_is_allowed_to_edit();

if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

/**
 * Redirect to the correct script to handle this type of upload.
 */
switch ($toolFromSession) {
    case TOOL_LEARNPATH:
        require 'upload.scorm.php';
        break;
    //the following cases need to be distinguished later on
    case TOOL_DROPBOX:
    case TOOL_STUDENTPUBLICATION:
    case TOOL_DOCUMENT:
    default:
        //require 'upload.document.php';
        break;
}
