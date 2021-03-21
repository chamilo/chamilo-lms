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

$userId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : api_get_user_id();
$lpId = $_GET['lp_id'] ?? null;
$lpItemId = $_GET['lp_item_id'] ?? null;
$extendId = $_GET['extend_id'] ?? null;
$extendAttemptId = $_GET['extend_attempt_id'] ?? null;
$extendedAttempt = $_GET['extend_attempt'] ?? null;
$extendedAll = $_GET['extend_all'] ?? null;
$export = isset($_GET['export']) && 'csv' === $_GET['export'];
$allowExtend = $_GET['allow_extend'] ?? 1;

$lpReportType = api_get_setting('lp_show_reduced_report');
$type = 'classic';
if ('true' === $lpReportType) {
    $type = 'simple';
}
$course = api_get_course_entity();
$session = api_get_session_entity();

return Tracking::getLpStats(
    $userId,
    $course,
    $session,
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
