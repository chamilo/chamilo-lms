<?php

/* For licensing terms, see /license.txt */

/**
 * This script displays statistics on the current learning path (scorm)
 * This script must be included by lp_controller.php to get basic initialisation.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
require_once __DIR__.'/../inc/global.inc.php';

// When origin is not set that means that the lp_stats are viewed from the "man running" icon
if (!isset($origin)) {
    $origin = 'learnpath';
}

$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : api_get_session_id();
$courseCode = isset($_GET['course']) ? $_GET['course'] : api_get_course_id();
$userId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : api_get_user_id();
$lpId = isset($_GET['lp_id']) ? $_GET['lp_id'] : null;
$lpItemId = isset($_GET['lp_item_id']) ? $_GET['lp_item_id'] : null;
$extendId = isset($_GET['extend_id']) ? $_GET['extend_id'] : null;
$extendAttemptId = isset($_GET['extend_attempt_id']) ? $_GET['extend_attempt_id'] : null;
$extendedAttempt = isset($_GET['extend_attempt']) ? $_GET['extend_attempt'] : null;
$extendedAll = isset($_GET['extend_all']) ? $_GET['extend_all'] : null;
$export = isset($_GET['export']) && 'csv' === $_GET['export'];
$allowExtend = isset($_GET['allow_extend']) ? $_GET['allow_extend'] : 1;

$lpReportType = api_get_setting('lp_show_reduced_report');
$type = 'classic';
if ('true' === $lpReportType) {
    $type = 'simple';
}
$courseInfo = api_get_course_info($courseCode);
$output = Tracking::getLpStats(
    $userId,
    $courseInfo,
    $sessionId,
    $origin,
    $export,
    $lpId,
    $lpItemId,
    $extendId,
    $extendAttemptId,
    $extendedAttempt,
    $extendedAll,
    $type,
    $allowExtend
);

// Origin = tracking means that teachers see that info in the Reporting tool
if ($origin !== 'tracking') {
    Display::display_reduced_header();
    $output .= '</body></html>';
}

return $output;
