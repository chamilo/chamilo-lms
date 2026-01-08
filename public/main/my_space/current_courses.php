<?php

/* For licensing terms, see /license.txt */

/**
 * Report for current courses followed by the user.
 */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_TRACKING;
$filename = 'reporting';

if (!api_is_allowed_to_create_course()) {
    api_not_allowed(true);
}

$userId = api_get_user_id();
$sessionId = 0; // Only base courses (no session) for this report.

// ------------------------------------------------------------------------
// Common headers/columns (light, can be defined once).
// ------------------------------------------------------------------------
$headers = [
    get_lang('Learning paths'),
    get_lang('Trainers'),
    get_lang('Courses'),
    get_lang('Number of learners'),
    get_lang('Number of learners accessing the course'),
    get_lang('Percentage of learners accessing the course'),
    get_lang('Number of learners who completed all activities (100% progress)'),
    get_lang('Percentage of learners who completed all activities (100% progress)'),
    get_lang('Average number of activities completed per learner'),
    get_lang('Total time spent in the course'),
    get_lang('Average time spent per learner in the course'),
    get_lang('Number of documents in learning path'),
    get_lang('Number of exercises in learning path'),
    get_lang('Number of links in learning path'),
    get_lang('Number of forums in learning path'),
    get_lang('Number of assignments in learning path'),
    get_lang('Number of announcements in course'),
];

$columns = [
    'lp',
    'teachers',
    'course_name',
    'count_students',
    'count_students_accessing',
    'count_students_accessing_percentage',
    'count_students_complete_all_activities_at_50',
    'count_students_complete_all_activities',
    'average_percentage_activities_completed_per_student',
    'total_time_spent',
    'average_time_spent_per_student',
    'learnpath_docs',
    'learnpath_exercises',
    'learnpath_links',
    'learnpath_forums',
    'learnpath_assignments',
    'total_announcements',
];

/**
 * Build report rows for current courses.
 *
 * @param int $userId
 * @param int $sessionId
 *
 * @return array<int, array<string, mixed>>
 */
function build_current_courses_rows(int $userId, int $sessionId = 0): array
{
    $rows = [];
    $rowIndex = 0;

    $myCourses = CourseManager::get_course_list_of_user_as_course_admin($userId);

    if (empty($myCourses)) {
        return $rows;
    }

    foreach ($myCourses as $course) {
        $courseId = (int) ($course['id'] ?? 0);

        if (0 === $courseId) {
            continue;
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo)) {
            // Safety check: if course info is not found, skip.
            continue;
        }

        $courseCode = $courseInfo['code'] ?? $course['code'];

        // Only show open courses.
        if (0 === (int) $courseInfo['visibility']) {
            continue;
        }

        // ------------------------------------------------------------
        // 1) Teachers list (for display).
        // ------------------------------------------------------------
        $teachers = CourseManager::get_teacher_list_from_course_code($courseCode);
        $teacherNames = [];

        if (!empty($teachers)) {
            foreach ($teachers as $teacher) {
                $teacherNames[] = $teacher['firstname'].' '.$teacher['lastname'];
            }
        }

        // ------------------------------------------------------------
        // 2) Students list (only "real" students, status = STUDENT).
        // ------------------------------------------------------------
        $tmpStudents = CourseManager::get_student_list_from_course_code($courseCode, false);
        $students = [];

        foreach ($tmpStudents as $student) {
            $userInfo = api_get_user_info($student['user_id']);
            if (STUDENT !== (int) $userInfo['status']) {
                continue;
            }

            $students[] = (int) $student['user_id'];
        }

        $studentCount = count($students);
        $studentsIdList = $studentCount > 0 ? implode(',', $students) : '';

        // ------------------------------------------------------------
        // 3) Course tables and main tables.
        // ------------------------------------------------------------
        $tLp = Database::get_course_table(TABLE_LP_MAIN);                // c_lp
        $tLpItem = Database::get_course_table(TABLE_LP_ITEM);            // c_lp_item
        $tNews = Database::get_course_table(TABLE_ANNOUNCEMENT);         // c_announcement
        $tLpView = Database::get_course_table(TABLE_LP_VIEW);            // c_lp_view
        $tLpItemView = Database::get_course_table(TABLE_LP_ITEM_VIEW);   // c_lp_item_view
        $tResourceLink = 'resource_link'; // Main table name for resource links.

        // ------------------------------------------------------------
        // 4) Get LP list for this course (base course, no session).
        //    FIRST TRY: through resource_link (original behaviour).
        // ------------------------------------------------------------
        $sqlLp = "SELECT lp.iid, lp.title
                  FROM $tLp lp
                  INNER JOIN $tResourceLink li
                      ON lp.resource_node_id = li.id
                  WHERE li.c_id = $courseId
                    AND li.session_id = $sessionId";

        $resLp = Database::query($sqlLp);

        if (0 === Database::num_rows($resLp)) {
            // SECOND TRY (fallback): LPs that have tracking entries in c_lp_view.
            $sqlLpFallback = "SELECT DISTINCT lp.iid, lp.title
                              FROM $tLp lp
                              INNER JOIN $tLpView v
                                  ON v.lp_id = lp.iid
                              WHERE v.c_id = $courseId
                                AND (v.session_id = $sessionId OR v.session_id IS NULL)";
            $resLp = Database::query($sqlLpFallback);
        }

        if (0 === Database::num_rows($resLp)) {
            // Course without learning paths: still add one row so the course
            // is visible in the report (all stats = 0).
            $rows[$rowIndex] = [
                'lp' => get_lang('No learning path'),
                'teachers' => !empty($teacherNames) ? implode(', ', $teacherNames) : '',
                'course_name' => $course['title'],
                'count_students' => $studentCount,
                'count_students_accessing' => 0,
                'count_students_accessing_percentage' => 0,
                // Legacy naming: "_at_50" actually stores the count of 100% completed.
                'count_students_complete_all_activities_at_50' => 0,
                // Legacy: this stores the percentage of 100% completed.
                'count_students_complete_all_activities' => 0,
                'average_percentage_activities_completed_per_student' => 0,
                'total_time_spent' => 0,
                'average_time_spent_per_student' => 0,
                'learnpath_docs' => 0,
                'learnpath_exercises' => 0,
                'learnpath_links' => 0,
                'learnpath_forums' => 0,
                'learnpath_assignments' => 0,
                'total_announcements' => 0,
            ];
            $rowIndex++;

            continue;
        }

        // Store LPs in a PHP array to be able to iterate twice.
        $lpRows = [];
        $lpIds = [];

        while ($lpRow = Database::fetch_array($resLp)) {
            $lpId = (int) $lpRow['iid'];
            $lpRows[] = $lpRow;
            $lpIds[] = $lpId;
        }

        if (empty($lpRows)) {
            continue;
        }

        // ------------------------------------------------------------
        // 5) Pre-compute stats per (lp, user) using c_lp_view + c_lp_item_view.
        //    This avoids looping over each student in PHP.
        // ------------------------------------------------------------
        $lpStats = []; // [lp_id => ['count_accessing', 'count_100', 'sum_progress', 'sum_time']]

        if ($studentCount > 0 && !empty($lpIds) && '' !== $studentsIdList) {
            $lpIdList = implode(',', $lpIds);

            // This query returns ONE row per (lp_id, user_id) with:
            // - progress (0..100)
            // - total_time (sum of all item views in that LP for that user)
            $sqlStats = "
                SELECT
                    v.lp_id,
                    v.user_id,
                    COALESCE(MAX(v.progress), 0) AS progress,
                    COALESCE(SUM(iv.total_time), 0) AS total_time
                FROM $tLpView v
                LEFT JOIN $tLpItemView iv
                    ON iv.lp_view_id = v.iid
                WHERE v.c_id = $courseId
                  AND (v.session_id = $sessionId OR v.session_id IS NULL)
                  AND v.lp_id IN ($lpIdList)
                  AND v.user_id IN ($studentsIdList)
                GROUP BY v.lp_id, v.user_id
            ";

            $resStats = Database::query($sqlStats);

            while ($row = Database::fetch_array($resStats)) {
                $lpId = (int) $row['lp_id'];
                $progress = (int) $row['progress'];
                $totalTime = (int) $row['total_time'];

                if (!isset($lpStats[$lpId])) {
                    $lpStats[$lpId] = [
                        'count_accessing' => 0,
                        'count_100' => 0,
                        'sum_progress' => 0,
                        'sum_time' => 0,
                    ];
                }

                // Student is considered "accessing" if there is progress or time.
                if ($progress > 0 || $totalTime > 0) {
                    $lpStats[$lpId]['count_accessing']++;
                }

                // Student completed 100% of the LP.
                if (100 === $progress) {
                    $lpStats[$lpId]['count_100']++;
                }

                $lpStats[$lpId]['sum_progress'] += $progress;
                $lpStats[$lpId]['sum_time'] += $totalTime;
            }
        }

        // ------------------------------------------------------------
        // 6) Build one row per LP with all stats.
        // ------------------------------------------------------------
        foreach ($lpRows as $lpRow) {
            $lpId = (int) $lpRow['iid'];
            $lpTitle = $lpRow['title'];

            // Base row with default values.
            $rows[$rowIndex] = [
                'lp' => '<a href="'.
                    api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?cidReq='.
                    $courseCode.'&amp;action=view&amp;lp_id='.$lpId.
                    '" target="_blank">'.
                    Security::remove_XSS($lpTitle).
                    '</a>',
                'teachers' => !empty($teacherNames) ? implode(', ', $teacherNames) : '',
                'course_name' => $course['title'],
                'count_students' => $studentCount,
                'count_students_accessing' => 0,
                'count_students_accessing_percentage' => 0,
                // Legacy naming kept for compatibility.
                'count_students_complete_all_activities_at_50' => 0,
                'count_students_complete_all_activities' => 0,
                'average_percentage_activities_completed_per_student' => 0,
                'total_time_spent' => 0,
                'average_time_spent_per_student' => 0,
                'learnpath_docs' => 0,
                'learnpath_exercises' => 0,
                'learnpath_links' => 0,
                'learnpath_forums' => 0,
                'learnpath_assignments' => 0,
                'total_announcements' => 0,
            ];

            // 6.a) Apply pre-computed stats for this LP (if any).
            if ($studentCount > 0 && isset($lpStats[$lpId])) {
                $stats = $lpStats[$lpId];

                $countAccessing = (int) $stats['count_accessing'];
                $countComplete100 = (int) $stats['count_100'];
                $sumProgress = (int) $stats['sum_progress'];
                $sumTime = (int) $stats['sum_time'];

                $rows[$rowIndex]['count_students_accessing'] = $countAccessing;

                if ($studentCount > 0) {
                    $rows[$rowIndex]['count_students_accessing_percentage'] =
                        $countAccessing > 0
                            ? round($countAccessing / $studentCount * 100, 0)
                            : 0;

                    // Keep legacy semantics:
                    // - "_at_50" column stores the absolute number of students at 100%.
                    // - "count_students_complete_all_activities" stores the percentage.
                    $rows[$rowIndex]['count_students_complete_all_activities_at_50'] =
                        $countComplete100;

                    $rows[$rowIndex]['count_students_complete_all_activities'] =
                        $countComplete100 > 0
                            ? round($countComplete100 / $studentCount * 100, 0)
                            : 0;

                    // Average progress across all enrolled students (0..100).
                    $rows[$rowIndex]['average_percentage_activities_completed_per_student'] =
                        $sumProgress > 0
                            ? round($sumProgress / $studentCount, 2)
                            : 0;
                }

                if ($sumTime > 0) {
                    $rows[$rowIndex]['total_time_spent'] = api_time_to_hms($sumTime);
                    $rows[$rowIndex]['average_time_spent_per_student'] =
                        api_time_to_hms($sumTime / max($studentCount, 1));
                }
            }

            // 6.b) Count LP items (documents, quizzes, links, forums, assignments).
            $sqlLpItems = "SELECT lpi.item_type
                           FROM $tLpItem lpi
                           WHERE lpi.lp_id = $lpId
                           ORDER BY lpi.item_type";
            $resLpItems = Database::query($sqlLpItems);

            while ($rowItem = Database::fetch_array($resLpItems)) {
                switch ($rowItem['item_type']) {
                    case 'document':
                        $rows[$rowIndex]['learnpath_docs']++;
                        break;
                    case 'quiz':
                        $rows[$rowIndex]['learnpath_exercises']++;
                        break;
                    case 'link':
                        $rows[$rowIndex]['learnpath_links']++;
                        break;
                    case 'forum':
                    case 'thread':
                        $rows[$rowIndex]['learnpath_forums']++;
                        break;
                    case 'student_publication':
                        $rows[$rowIndex]['learnpath_assignments']++;
                        break;
                }
            }

            // 6.c) Announcements count (optional – commented out for perf).
            /*
            $sqlNews = "SELECT COUNT(n.iid) AS total
                        FROM $tNews n
                        WHERE n.c_id = $courseId";
            $resNews = Database::query($sqlNews);
            if ($rowNews = Database::fetch_array($resNews)) {
                $rows[$rowIndex]['total_announcements'] = (int) $rowNews['total'];
            }
            */

            $rowIndex++;
        }
    }

    return $rows;
}

// ------------------------------------------------------------------------
// AJAX endpoint: heavy work happens here, returns JSON.
// ------------------------------------------------------------------------
if (isset($_GET['ajax'])) {
    $rows = build_current_courses_rows($userId, $sessionId);

    $dataRows = [];
    foreach ($rows as $rowData) {
        $row = [];
        foreach ($columns as $key) {
            $row[] = $rowData[$key] ?? '';
        }
        $dataRows[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode(
        [
            'rows' => $dataRows,
            'message' => empty($dataRows) ? get_lang('No data available') : '',
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

// ------------------------------------------------------------------------
// Export to XLS: still uses the heavy builder, but only on demand.
// ------------------------------------------------------------------------
if (isset($_GET['export'])) {
    $rows = build_current_courses_rows($userId, $sessionId);

    if (!empty($rows[0])) {
        $firstRow = [];
        foreach ($columns as $key) {
            $firstRow[] = $rows[0][$key] ?? '';
        }

        $list = [
            0 => $headers,
            1 => $firstRow,
        ];
        Export::arrayToXls($list, $filename);
        exit;
    }
}

// ------------------------------------------------------------------------
// Normal HTML request: renders the layout and delegates heavy work to JS.
// ------------------------------------------------------------------------
$pageTitle = get_lang('Current courses report');
$pageSubtitle = get_lang('This table summarizes learner progress and activity per learning path in your current courses.');

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Reporting')];
Display::display_header($pageTitle);

// ------------------------------------------------------------------------
// Toolbar (MySpace main menu + Back / Print / Export actions).
// ------------------------------------------------------------------------
$actionsLeft = Display::url(
    Display::getMdiIcon(
        'chart-box',
        'ch-tool-icon',
        null,
        32,
        get_lang('View my progress')
    ),
    api_get_path(WEB_CODE_PATH).'auth/my_progress.php'
);
$actionsLeft .= Display::mySpaceMenu('current_courses');

$actionsRight = Display::url(
    Display::getMdiIcon(
        ActionIcon::BACK,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Back')
    ),
    api_get_path(WEB_CODE_PATH).'my_space/index.php'
);

$actionsRight .= Display::url(
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

$actionsRight .= Display::url(
    Display::getMdiIcon(
        ActionIcon::EXPORT_SPREADSHEET,
        'ch-tool-icon',
        null,
        ICON_SIZE_MEDIUM,
        get_lang('Current courses report')
    ),
    api_get_path(WEB_CODE_PATH).'my_space/current_courses.php?export=1'
);

echo Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight]);

// ------------------------------------------------------------------------
// Page heading (title + subtitle).
// ------------------------------------------------------------------------
echo '<div class="mt-4 mb-3">';
echo '  <h2 class="h4 mb-1">'.$pageTitle.'</h2>';
echo '  <p class="text-muted mb-0">'.$pageSubtitle.'</p>';
echo '</div>';

// Summary KPI cards.
echo '<div id="current-courses-summary" class="row mb-3">';
echo '  <div class="col-6 col-md-3 mb-2">';
echo '      <div class="card shadow-sm h-100 border-0">';
echo '          <div class="card-body text-center p-2">';
echo '              <div class="small text-muted">'.get_lang('Total number of courses').'</div>';
echo '              <div id="current-courses-kpi-courses" class="h4 mb-0">-</div>';
echo '          </div>';
echo '      </div>';
echo '  </div>';
echo '  <div class="col-6 col-md-3 mb-2">';
echo '      <div class="card shadow-sm h-100 border-0">';
echo '          <div class="card-body text-center p-2">';
echo '              <div class="small text-muted">'.get_lang('Total learners').'</div>';
echo '              <div id="current-courses-kpi-learners" class="h4 mb-0">-</div>';
echo '          </div>';
echo '      </div>';
echo '  </div>';
echo '  <div class="col-6 col-md-3 mb-2">';
echo '      <div class="card shadow-sm h-100 border-0">';
echo '          <div class="card-body text-center p-2">';
echo '              <div class="small text-muted">'.get_lang('Average access rate').'</div>';
echo '              <div id="current-courses-kpi-access" class="h4 mb-0">-</div>';
echo '          </div>';
echo '      </div>';
echo '  </div>';
echo '  <div class="col-6 col-md-3 mb-2">';
echo '      <div class="card shadow-sm h-100 border-0">';
echo '          <div class="card-body text-center p-2">';
echo '              <div class="small text-muted">'.get_lang('Average completion rate').'</div>';
echo '              <div id="current-courses-kpi-completion" class="h4 mb-0">-</div>';
echo '          </div>';
echo '      </div>';
echo '  </div>';
echo '</div>';

// Table with a centered loading row.
$colspan = count($headers);

echo '<div class="table-responsive">';
echo '<table id="current-courses-table" class="table table-hover table-striped data_table">';
echo '<thead><tr>';
foreach ($headers as $header) {
    echo '<th>'.$header.'</th>';
}
echo '</tr></thead>';
echo '<tbody id="current-courses-tbody">';
echo '<tr id="current-courses-loading-row">';
echo '<td colspan="'.$colspan.'" class="text-center">';
echo '<div class="spinner-border" role="status" aria-hidden="true"></div>';
echo '<span class="ms-2">'.get_lang('Loading').'...</span>';
echo '</td>';
echo '</tr>';
echo '</tbody>';
echo '</table>';
echo '</div>';

// Simple JS loader that calls the AJAX endpoint, fills the table and updates KPIs.
$ajaxUrl = api_get_path(WEB_CODE_PATH).'my_space/current_courses.php?ajax=1';
$noDataText = addslashes(get_lang('No data available'));
$errorText = addslashes(get_lang('Error loading courses'));

echo '<script>
document.addEventListener("DOMContentLoaded", function () {
    var table = document.getElementById("current-courses-table");
    var tbody = document.getElementById("current-courses-tbody");
    var loadingRow = document.getElementById("current-courses-loading-row");

    var kpiCourses = document.getElementById("current-courses-kpi-courses");
    var kpiLearners = document.getElementById("current-courses-kpi-learners");
    var kpiAccess = document.getElementById("current-courses-kpi-access");
    var kpiCompletion = document.getElementById("current-courses-kpi-completion");

    if (!table || !tbody || !loadingRow) {
        return;
    }

    // Helper: update loading row text and style.
    function setLoadingText(text, isError) {
        var td = loadingRow.querySelector("td");
        if (!td) {
            return;
        }
        td.textContent = text;
        td.classList.toggle("text-danger", !!isError);
    }

    // Helper: update KPI cards based on loaded rows.
    function updateKpis(rows) {
        if (!rows || !rows.length) {
            if (kpiCourses) { kpiCourses.textContent = "0"; }
            if (kpiLearners) { kpiLearners.textContent = "0"; }
            if (kpiAccess) { kpiAccess.textContent = "0%"; }
            if (kpiCompletion) { kpiCompletion.textContent = "0%"; }
            return;
        }

        var indexCourseName = 2; // course_name
        var indexStudents = 3;   // count_students
        var indexAccessPct = 5;  // count_students_accessing_percentage
        var indexCompletePct = 7; // count_students_complete_all_activities

        var courseSet = {};
        var totalLearners = 0;
        var sumAccess = 0;
        var sumCompletion = 0;
        var countRows = 0;

        rows.forEach(function (row) {
            var courseName = row[indexCourseName] || "";
            if (courseName) {
                courseSet[courseName] = true;
            }

            var learners = parseFloat(row[indexStudents]) || 0;
            totalLearners += learners;

            var accessPct = parseFloat(row[indexAccessPct]) || 0;
            var completionPct = parseFloat(row[indexCompletePct]) || 0;

            sumAccess += accessPct;
            sumCompletion += completionPct;
            countRows++;
        });

        var totalCourses = Object.keys(courseSet).length;
        var avgAccess = countRows ? (sumAccess / countRows) : 0;
        var avgCompletion = countRows ? (sumCompletion / countRows) : 0;

        if (kpiCourses) {
            kpiCourses.textContent = String(totalCourses);
        }
        if (kpiLearners) {
            kpiLearners.textContent = String(totalLearners);
        }
        if (kpiAccess) {
            kpiAccess.textContent = avgAccess.toFixed(1) + "%";
        }
        if (kpiCompletion) {
            kpiCompletion.textContent = avgCompletion.toFixed(1) + "%";
        }
    }

    fetch("'.$ajaxUrl.'")
        .then(function (response) {
            if (!response.ok) {
                throw new Error("HTTP " + response.status);
            }
            return response.json();
        })
        .then(function (data) {
            if (!data || !Array.isArray(data.rows) || data.rows.length === 0) {
                setLoadingText(data && data.message ? data.message : "'.$noDataText.'", false);
                updateKpis([]);
                return;
            }

            // Remove loading row before appending data rows.
            if (loadingRow && loadingRow.parentNode) {
                loadingRow.parentNode.removeChild(loadingRow);
            }

            data.rows.forEach(function (row) {
                var tr = document.createElement("tr");
                row.forEach(function (cell) {
                    var td = document.createElement("td");
                    td.innerHTML = cell;
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });

            updateKpis(data.rows);
        })
        .catch(function (e) {
            setLoadingText("'.$errorText.'", true);
            updateKpis([]);
            if (window.console && console.error) {
                console.error(e);
            }
        });
});
</script>';

Display::display_footer();
