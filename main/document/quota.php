<?php
/* For licensing terms, see /license.txt */

/**
 *	This script displays info about the course disk use and quota:
 *	how large (in megabytes) is the documents area of the course,
 *	what is the maximum allowed for this course...
 *
 *	@author Roan Embrechts
 *	@package chamilo.document
 */
/**
 * Code
 */

// Name of the language file that needs to be included
exit;
$language_file = 'document';

// Including the global dokeos file
require_once '../inc/global.inc.php';

// Including additional libraries
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';

// Some constants and variables
$courseDir = $_course['path'].'/document';
$maxFilledSpace = DEFAULT_DOCUMENT_QUOTA;

// Breadcrumbs
$interbreadcrumb[] = array('url' => 'document.php','name' => get_lang('ToolDocument'));

// Title of the page
$nameTools = get_lang('DocumentQuota');

// Display the header
Display::display_header($nameTools,'Doc');

/*	FUNCTIONS */


// Actions
echo '<div class="actions">';
// link back to the documents overview
echo '<a href="document.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

// Getting the course quota
$course_quota = DocumentManager::get_course_quota();

// Setting the full path
//$full_path = $baseWorkDir.$courseDir;

// Calculating the total space
$already_consumed_space = DocumentManager::documents_total_space($_course);

// Displaying the quota
DocumentManager::display_quota($course_quota, $already_consumed_space);

// Display the footer
Display::display_footer();
