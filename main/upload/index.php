<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Main script for the documents tool.
 *
 * This script allows the user to manage files and directories on a remote http server.
 *
 * The user can : - upload a file
 *
 * The script respects the strategical split between process and display, so the first
 * part is only processing code (init, process, display preparation) and the second
 * part is only display (HTML)
 *
 * @package chamilo.upload
 */
require_once __DIR__.'/../inc/global.inc.php';

$_course = api_get_course_info();

api_protect_course_script(true);

$htmlHeadXtra[] = "<script>
function check_unzip() {
	if (document.upload.unzip.checked) {
        document.upload.if_exists[0].disabled=true;
        document.upload.if_exists[1].checked=true;
        document.upload.if_exists[2].disabled=true;
	} else {
        document.upload.if_exists[0].checked=true;
        document.upload.if_exists[0].disabled=false;
        document.upload.if_exists[2].disabled=false;
	}
}
</script>";

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

//what's the current path?
$path = '/';
if (isset($_REQUEST['curdirpath'])) {
    $path = $_REQUEST['curdirpath'];
}

$toolFromSession = Session::read('my_tool');

// set calling tool
if (isset($_REQUEST['tool'])) {
    $my_tool = $_REQUEST['tool'];
    Session::write('my_tool', $_REQUEST['tool']);
} elseif (!empty($toolFromSession)) {
    $my_tool = $toolFromSession;
} else {
    $my_tool = 'document';
    Session::write('my_tool', $my_tool);
}

/**
 * Process.
 */
Event::event_access_tool(TOOL_UPLOAD);

/**
 * Now call the corresponding display script, the current script acting like a controller.
 */
switch ($my_tool) {
    case TOOL_LEARNPATH:
        require 'form.scorm.php';
        break;
    //the following cases need to be distinguished later on
    case TOOL_DROPBOX:
    case TOOL_STUDENTPUBLICATION:
    case TOOL_DOCUMENT:
    default:
        require 'form.document.php';
        break;
}
