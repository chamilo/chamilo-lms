<?php

/* For licensing terms, see /license.txt */

/**
 * Platform-wide tracking reports for platform administrators.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true);
if (!$allowToTrack) {
    api_not_allowed(true);
}

$interbreadcrumb[] = [
    'url' => api_is_student_boss() ? '#' : 'index.php',
    'name' => get_lang('Reporting'),
];

$nameTools = get_lang('Admin view');
$this_section = SECTION_TRACKING;

// Extra JS required by some MySpace reports.
$htmlHeadXtra[] = '<script src="'.
    api_get_path(WEB_PUBLIC_PATH).
    'assets/jquery.easy-pie-chart/dist/jquery.easypiechart.js"></script>';
$htmlHeadXtra[] = '<script>
function showHideStudent(el) {
    if ($("#" + el).hasClass("hidden")) {
        $("#" + el).removeClass("hidden");
        $("#" + el + "_").find(".icon_add").addClass("hidden");
        $("#" + el + "_").find(".icon_remove").removeClass("hidden");
    } else {
        $("#" + el).addClass("hidden");
        $("#" + el + "_").find(".icon_add").removeClass("hidden");
        $("#" + el + "_").find(".icon_remove").addClass("hidden");
    }
}
function ShowMoreAuthor(el) {
    if ($(".author_" + el).hasClass("hidden")) {
        $(".author_" + el).removeClass("hidden");
        $(".icon_remove_author_" + el).removeClass("hidden");
        $(".icon_add_author_" + el).addClass("hidden");
    } else {
        $(".author_" + el).addClass("hidden");
        $(".icon_remove_author_" + el).addClass("hidden");
        $(".icon_add_author_" + el).removeClass("hidden");
    }
}
</script>';

// -----------------------------------------------------------------------------
// Request parameters
// -----------------------------------------------------------------------------
$exportCSV = isset($_GET['export']) && 'csv' === $_GET['export'];
if (isset($_GET['export_csv']) && false === $exportCSV) {
    $exportCSV = true;
}

$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$display = isset($_GET['display']) ? Security::remove_XSS($_GET['display']) : null;

if (isset($_POST['display']) && null === $display) {
    $display = Security::remove_XSS($_POST['display']);
}

$webCodePath = api_get_path(WEB_CODE_PATH);

// -----------------------------------------------------------------------------
// CSV exports (direct response, no page rendering)
// -----------------------------------------------------------------------------
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

// -----------------------------------------------------------------------------
// Page rendering
// -----------------------------------------------------------------------------
Display::display_header($nameTools);

// -----------------------------------------------------------------------------
// Toolbar (MySpace main menu + print / CSV actions)
// -----------------------------------------------------------------------------
$actionsLeft = Display::url(
    Display::getMdiIcon(
        'chart-box',
        'ch-tool-icon',
        null,
        32,
        get_lang('View my progress')
    ),
    $webCodePath.'auth/my_progress.php'
);
$actionsLeft .= Display::mySpaceMenu('admin_view');

$actionsRight = Display::url(
    Display::getMdiIcon(
        ActionIcon::PRINT,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Print')
    ),
    'javascript: void(0);',
    ['onclick' => 'javascript: window.print();']
);

$exportableDisplays = ['user', 'session', 'course', 'company', 'learningPath', 'learningPathByItem'];
if ($display && in_array($display, $exportableDisplays, true)) {
    $exportUrl = api_get_self().'?display='.$display.'&export=csv';

    if (!empty($startDate)) {
        $exportUrl .= '&startDate='.urlencode((string) $startDate);
    }
    if (!empty($endDate)) {
        $exportUrl .= '&endDate='.urlencode((string) $endDate);
    }

    $actionsRight .= Display::url(
        Display::getMdiIcon(
            ActionIcon::EXPORT_CSV,
            'ch-tool-icon',
            null,
            ICON_SIZE_MEDIUM,
            get_lang('CSV export')
        ),
        $exportUrl
    );
}

$toolbar = Display::toolbarAction('toolbar-admin', [$actionsLeft, $actionsRight]);

// -----------------------------------------------------------------------------
// Layout wrapper
// -----------------------------------------------------------------------------
echo '<div class="w-full px-4 md:px-8 pb-8 space-y-6">';

// Toolbar row
echo '  <div class="flex flex-wrap gap-2">';
echo        $toolbar;
echo '  </div>';

// Page header
echo '  <div class="space-y-1">';
echo        Display::page_subheader($nameTools);
echo '  </div>';

// Navigation cards + "Current report" helper text
$activeDisplayForCards = (null === $display) ? '__none__' : $display;
$showCurrentReportNote = !empty($display);

echo MySpace::renderAdminReportCardsSection(
    $activeDisplayForCards,
    null,
    $showCurrentReportNote
);

// -----------------------------------------------------------------------------
// Report content area
// -----------------------------------------------------------------------------
if (!empty($display)) {
    echo '  <section class="reporting-admin-card bg-white rounded-xl shadow-sm w-full">';
    echo '      <div class="p-2 md:p-4">';

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
            if (0 === $courseId && !empty($_POST['course_id'])) {
                $courseId = (int) $_POST['course_id'];
                $_GET['course_id'] = $courseId;
            }

            $sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
            if (0 === $sessionId && !empty($_POST['session_id'])) {
                $sessionId = (int) $_POST['session_id'];
                $_GET['session_id'] = $sessionId;
            }

            $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
            if (0 === $studentId && !empty($_POST['student_id'])) {
                $studentId = (int) $_POST['student_id'];
                $_GET['student_id'] = $studentId;
            }

            $perPage = $_GET['tracking_access_overview_per_page'] ?? 20;

            $dates = $_GET['date'] ?? null;
            if (null === $dates && !empty($_POST['date'])) {
                $dates = $_POST['date'];
                $_GET['date'] = $dates;
            }

            MySpace::displayTrackingAccessOverView(
                $courseId,
                $sessionId,
                $studentId,
                $perPage,
                $dates
            );
            break;
    }

    echo '      </div>';
    echo '  </section>';
}

echo '</div>';

Display::display_footer();
