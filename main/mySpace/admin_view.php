<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$exportCSV = isset($_GET['export']) && $_GET['export'] === 'csv' ? true : false;
if (isset($_GET['export_csv']) && $exportCSV == false) {
    // to export learningPath and company
    $exportCSV = true;
}
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
$display = isset($_GET['display']) ? Security::remove_XSS($_GET['display']) : null;

$htmlHeadXtra[] = api_get_jqgrid_js();
$htmlHeadXtra[] = '<script
type="text/javascript"
src="'.api_get_path(WEB_PUBLIC_PATH).'assets/jquery.easy-pie-chart/dist/jquery.easypiechart.js"></script>
<script type="text/javascript">
// show hide a student based on BT#17648
function showHideStudent(el){
    if($("#"+el).hasClass("hidden")){
        $("#"+el).removeClass("hidden");
        $("#"+el+"_").find(".icon_add").addClass("hidden");
        $("#"+el+"_").find(".icon_remove").removeClass("hidden");
    }else{
        $("#"+el).addClass("hidden")
        $("#"+el+"_").find(".icon_add").removeClass("hidden");
        $("#"+el+"_").find(".icon_remove").addClass("hidden");
    }
}
function ShowMoreAuthor(el){
    if($(".author_"+el).hasClass("hidden")){
        $(".author_"+el).removeClass("hidden");
        $(".icon_remove_author_"+el).removeClass("hidden");
        $(".icon_add_author_"+el).addClass("hidden");
    }else{
        $(".author_"+el).addClass("hidden")

        $(".icon_remove_author_"+el).addClass("hidden");
        $(".icon_add_author_"+el).removeClass("hidden");

    }
}
</script>';

// the section (for the tabs)
$this_section = SECTION_TRACKING;

$csv_content = [];
$nameTools = get_lang('MySpace');
$allowToTrack = api_is_platform_admin(true, true);

if (!$allowToTrack) {
    api_not_allowed(true);
}

if ($exportCSV) {
    if ('user' === $display) {
        MySpace::export_tracking_user_overview();
        exit;
    } elseif ('session' === $display) {
        MySpace::export_tracking_session_overview();
        exit;
    } elseif ('course' === $display) {
        MySpace::export_tracking_course_overview();
        exit;
    } elseif ('company' === $display) {
        MySpace::exportCompanyResumeCsv($startDate, $endDate);
        exit;
    } elseif ('learningPath' === $display) {
        MySpace::displayResumeLP($startDate, $endDate, true);
        exit;
    } elseif ('learningPathByItem' === $display) {
        MySpace::displayResumeLpByItem($startDate, $endDate, true);
        exit;
    }
}

Display::display_header($nameTools);
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
    case 'company':
        MySpace::displayResumeCompany($startDate, $endDate);
        break;
    case 'learningPath':
        MySpace::displayResumeLP($startDate, $endDate);
        break;
    case 'learningPathByItem':
        MySpace::displayResumeLpByItem($startDate, $endDate);
        break;
    case 'accessoverview':
        $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
        $sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
        $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;

        MySpace::displayTrackingAccessOverView($courseId, $sessionId, $studentId);
        break;
}

Display::display_footer();
