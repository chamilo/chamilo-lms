<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$exportCSV = isset($_GET['export']) && $_GET['export'] === 'csv' ? true : false;
$display = isset($_GET['display']) ? Security::remove_XSS($_GET['display']) : null;

$htmlHeadXtra[] = api_get_jqgrid_js();
// the section (for the tabs)
$this_section = SECTION_TRACKING;

$csv_content = array();
$nameTools = get_lang('MySpace');

$is_platform_admin = api_is_platform_admin();
$is_drh = api_is_drh();
$is_session_admin = api_is_session_admin();

if ($exportCSV) {
    if ($display == 'user') {
        MySpace::export_tracking_user_overview();
        exit;
    } elseif ($display == 'session') {
        MySpace::export_tracking_session_overview();
        exit;
    } elseif ($display == 'course') {
        MySpace::export_tracking_course_overview();
        exit;
    }
}

Display :: display_header($nameTools);
echo '<div class="actions">';
echo MySpace::getTopMenu();
echo '</div>';
echo MySpace::getAdminActions();

switch ($display) {
    case 'coaches':
        MySpace::display_tracking_coach_overview($exportCSV);
        break;
    case 'user':
        MySpace::display_tracking_user_overview();
        break;
    case 'session':
        MySpace::display_tracking_session_overview();
        break;
    case 'course':
        MySpace::display_tracking_course_overview();
        break;
    case 'accessoverview':
        $courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
        $sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
        $studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

        MySpace::displayTrackingAccessOverView($courseId, $sessionId, $studentId);
        break;
}

Display::display_footer();

