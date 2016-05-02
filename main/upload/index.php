<?php
/* For licensing terms, see /license.txt */

/**
* Main script for the documents tool
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

require_once '../inc/global.inc.php';

$_course = api_get_course_info();

$htmlHeadXtra[] = "<script>
function check_unzip() {
	if(document.upload.unzip.checked){
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

$courseDir = $_course['path'] . "/document";
$sys_course_path = api_get_path(SYS_COURSE_PATH);
$base_work_dir = $sys_course_path . $courseDir;
$noPHP_SELF = true;
$max_filled_space = DocumentManager::get_course_quota();

//what's the current path?
if (isset($_REQUEST['curdirpath'])) {
    $path = $_REQUEST['curdirpath'];
} else {
    $path = '/';
}
// set calling tool
if (isset($_REQUEST['tool'])) {
    $my_tool = $_REQUEST['tool'];
    $_SESSION['my_tool'] = $_REQUEST['tool'];
} elseif (!empty($_SESSION['my_tool'])) {
    $my_tool = $_SESSION['my_tool'];
} else {
    $my_tool = 'document';
    $_SESSION['my_tool'] = $my_tool;
}

/**
 * Process
 */
Event::event_access_tool(TOOL_UPLOAD);

/**
 *	Prepare the header
 */
$htmlHeadXtra[] = '<script language="javascript" src="../inc/lib/javascript/upload.js" type="text/javascript"></script>';
$htmlHeadXtra[] = '<script type="text/javascript">
	var myUpload = new upload(0);
</script>';

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
    default :
        require 'form.document.php';
        break;
}
