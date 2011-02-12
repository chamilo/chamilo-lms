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

// Name of the language file that needs to be included
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

/**
 *	Here we count 1 kilobyte = 1000 byte, 12 megabyte = 1000 kilobyte.
 */
function display_quota($course_quota, $already_consumed_space) {
	$course_quota_m = round($course_quota / 1000000);
	$already_consumed_space_m = round($already_consumed_space / 1000000);
	
	
	$message = get_lang('MaximumAllowedQuota') . ' <strong>'.$course_quota_m.' megabyte</strong>.<br />';
	$message .= get_lang('CourseCurrentlyUses') . ' <strong>' . $already_consumed_space_m . ' megabyte</strong>.<br />';	
	
	
	$percentage = $already_consumed_space / $course_quota * 100;
	
	$percentage = round($percentage, 1);	

	$other_percentage = $percentage < 100 ? 100 - $percentage : 0;

	// Decide where to place percentage in graph
	if ($percentage >= 50) {
		$text_in_filled = '&nbsp;'.$other_percentage.'%';
		$text_in_unfilled = '';
	} else {
		$text_in_unfilled = '&nbsp;'.$other_percentage.'%';
		$text_in_filled = '';
	}

	// Decide the background colour of the graph
	if ($percentage < 65) {
		$colour = '#00BB00';		// Safe - green
	} elseif ($percentage < 90) {
		$colour = '#ffd400';		// Filling up - yelloworange
	} else {
		$colour = '#DD0000';		// Full - red
	}

	// This is used for the table width: a table of only 100 pixels looks too small
	$visual_percentage = 4 * $percentage;
	$visual_other_percentage = 4 * $other_percentage;

	$message .= get_lang('PercentageQuotaInUse') . ': <strong>'.$percentage.'%</strong>.<br />' .
				get_lang('PercentageQuotaFree') . ': <strong>'.$other_percentage.'%</strong>.<br />';

	$show_percentage = '&nbsp;'.$percentage.'%';
    $message .= '<div style="width: 80%; text-align: center; -moz-border-radius: 5px 5px 5px 5px; border: 1px solid #aaa; background-image: url(\''.api_get_path(WEB_CODE_PATH).'css/'.api_get_visual_theme().'/images/bg-header4.png\');" class="document-quota-bar">'.
                '<div style="width:'.$percentage.'%; background-color: #bbb; border-right:3px groove #bbb; -moz-border-radius:5px;">&nbsp;</div>'.
                '<span style="margin-top: -15px; margin-left:-15px; position: absolute;font-weight:bold;">'.$show_percentage.'</span></div>';
	echo $message;
}

// Actions
echo '<div class="actions">';
// link back to the documents overview
echo '<a href="document.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('DocumentsOverview'),'','32').'</a>';
echo '</div>';

// Getting the course quota
$course_quota = DocumentManager::get_course_quota();

// Setting the full path
$full_path = $baseWorkDir.$courseDir;

// Calculating the total space
$already_consumed_space = documents_total_space($_course);

// Displaying the quota
display_quota($course_quota, $already_consumed_space);

// Display the footer
Display::display_footer();
