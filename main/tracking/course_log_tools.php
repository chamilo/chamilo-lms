<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_TRACKING;

$course_info = api_get_course_info();
$groupId = api_get_group_id();
$session_id = api_get_session_id();
$course_code = api_get_course_id();
$course_id = api_get_course_int_id();

$from_myspace = false;
$from = isset($_GET['from']) ? $_GET['from'] : null;

$this_section = SECTION_COURSES;
if ('myspace' === $from) {
    $from_myspace = true;
    $this_section = 'session_my_space';
}

// Access restrictions.
$is_allowedToTrack = Tracking::isAllowToTrack($session_id);

if (!$is_allowedToTrack) {
    api_not_allowed(true);
    exit;
}

$showChatReporting = true;
$showTrackingReporting = true;
$documentReporting = true;
$linkReporting = true;
$exerciseReporting = true;
$lpReporting = true;

if (!empty($groupId)) {
    $showChatReporting = false;
    $showTrackingReporting = false;
    $documentReporting = false;
    $linkReporting = false;
    $exerciseReporting = false;
    $lpReporting = false;
}

$TABLEQUIZ = Database::get_course_table(TABLE_QUIZ_TEST);

// Starting the output buffering when we are exporting the information.
$export_csv = isset($_GET['export']) && $_GET['export'] === 'csv' ? true : false;
$session_id = intval($_REQUEST['id_session']);

if ($export_csv) {
    if (!empty($session_id)) {
        Session::write('id_session', $session_id);
    }
    ob_start();
}
$csv_content = [];

// Breadcrumbs.
if (isset($_GET['origin']) && $_GET['origin'] === 'resume_session') {
    $interbreadcrumb[] = ['url' => '../admin/index.php', 'name' => get_lang('PlatformAdmin')];
    $interbreadcrumb[] = ['url' => '../session/session_list.php', 'name' => get_lang('SessionList')];
    $interbreadcrumb[] = [
        'url' => '../session/resume_session.php?id_session='.api_get_session_id(),
        'name' => get_lang('SessionOverview'),
    ];
}

$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';
$nameTools = get_lang('Tracking');

Display::display_header($nameTools, 'Tracking');

// getting all the students of the course
if (empty($session_id)) {
    // Registered students in a course outside session.
    $students = CourseManager::get_student_list_from_course_code(
        api_get_course_id(),
        false,
        0,
        null,
        null,
        true,
        api_get_group_id()
    );
} else {
    // Registered students in session.
    $students = CourseManager::get_student_list_from_course_code(
        api_get_course_id(),
        true,
        api_get_session_id()
    );
}
$nbStudents = count($students);
$student_ids = array_keys($students);
$studentCount = count($student_ids);

echo '<div class="actions">';
echo TrackingCourseLog::actionsLeft('courses', api_get_session_id());
echo '<span style="float:right; padding-top:0px;">';
echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'.
    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&id_session='.api_get_session_id().'&export=csv">
	'.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a>';

echo '</span>';
echo '</div>';

if ($lpReporting) {
    $list = new LearnpathList(null, $course_info, $session_id);
    $flat_list = $list->get_flat_list();

    if (count($flat_list) > 0) {
        // learning path tracking
        echo '<div class="report_section">';
        echo Display::page_subheader(
            Display::return_icon(
                'scorms.gif',
                get_lang('AverageProgressInLearnpath')
            ).' '.get_lang('AverageProgressInLearnpath')
        );
        echo '<table class="table table-hover table-striped data_table">';
        if ($export_csv) {
            $temp = [get_lang('AverageProgressInLearnpath', ''), ''];
            $csv_content[] = ['', ''];
            $csv_content[] = $temp;
        }

        foreach ($flat_list as $lp_id => $lp) {
            $lp_avg_progress = 0;
            foreach ($students as $student_id => $student) {
                // get the progress in learning paths.
                $lp_avg_progress += Tracking::get_avg_student_progress(
                    $student_id,
                    $course_code,
                    [$lp_id],
                    $session_id
                );
            }

            if ($studentCount > 0) {
                $lp_avg_progress = $lp_avg_progress / $studentCount;
            }

            // Separated presentation logic.
            if (is_null($lp_avg_progress)) {
                $lp_avg_progress = '0%';
            } else {
                $lp_avg_progress = round($lp_avg_progress, 1).'%';
            }
            echo '<tr><td>'.$lp['lp_name'].'</td><td align="right">'.$lp_avg_progress.'</td></tr>';
            if ($export_csv) {
                $temp = [$lp['lp_name'], $lp_avg_progress];
                $csv_content[] = $temp;
            }
        }
        echo '</table></div>';
    } else {
        if ($export_csv) {
            $temp = [get_lang('NoLearningPath', ''), ''];
            $csv_content[] = $temp;
        }
    }
}

if ($exerciseReporting) {
    // Exercises tracking.
    echo '<div class="report_section">';
    echo Display::page_subheader(
        Display::return_icon(
            'quiz.png',
            get_lang('AverageResultsToTheExercices')
        ).' '.get_lang('AverageResultsToTheExercices')
    );
    echo '<table class="table table-hover table-striped data_table">';
    $course_id = api_get_course_int_id();
    $sql = "SELECT iid, title FROM $TABLEQUIZ
            WHERE c_id = $course_id AND active <> -1 AND session_id = $session_id";
    $rs = Database::query($sql);

    if ($export_csv) {
        $temp = [get_lang('AverageProgressInLearnpath'), ''];
        $csv_content[] = ['', ''];
        $csv_content[] = $temp;
    }

    $course_path_params = '&cidReq='.$course_code.'&id_session='.$session_id;

    if (Database::num_rows($rs) > 0) {
        while ($quiz = Database::fetch_array($rs)) {
            $quiz_avg_score = 0;
            if ($studentCount > 0) {
                foreach ($student_ids as $student_id) {
                    $avg_student_score = Tracking::get_avg_student_exercise_score(
                        $student_id,
                        $course_code,
                        $quiz['iid'],
                        $session_id
                    );
                    $quiz_avg_score += $avg_student_score;
                }
            }
            $studentCount = ($studentCount == 0 || is_null($studentCount) || $studentCount == '') ? 1 : $studentCount;
            $quiz_avg_score = round(($quiz_avg_score / $studentCount), 2).'%';
            $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?exerciseId='.$quiz['iid'].$course_path_params;

            echo '<tr><td>';
            echo Display::url(
                $quiz['title'],
                $url
            );
            echo '</td><td align="right">'.$quiz_avg_score.'</td></tr>';
            if ($export_csv) {
                $temp = [$quiz['title'], $quiz_avg_score];
                $csv_content[] = $temp;
            }
        }
    } else {
        echo '<tr><td>'.get_lang('NoExercises').'</td></tr>';
        if ($export_csv) {
            $temp = [get_lang('NoExercises', ''), ''];
            $csv_content[] = $temp;
        }
    }
    echo '</table></div>';
    echo '<div class="clear"></div>';
}

$filterByUsers = [];
if (!empty($groupId)) {
    $filterByUsers = $student_ids;
}

$count_number_of_forums_by_course = Tracking::count_number_of_forums_by_course(
    $course_code,
    $session_id,
    $groupId
);

$count_number_of_threads_by_course = Tracking::count_number_of_threads_by_course(
    $course_code,
    $session_id,
    $groupId
);

$count_number_of_posts_by_course = Tracking::count_number_of_posts_by_course(
    $course_code,
    $session_id,
    $groupId
);

if ($export_csv) {
    $csv_content[] = [get_lang('Forum')];
    $csv_content[] = [get_lang('ForumForumsNumber'), $count_number_of_forums_by_course];
    $csv_content[] = [get_lang('ForumThreadsNumber'), $count_number_of_threads_by_course];
    $csv_content[] = [get_lang('ForumPostsNumber'), $count_number_of_posts_by_course];
}

// Forums tracking.
echo '<div class="report_section">';
echo Display::page_subheader(
    Display::return_icon('forum.gif', get_lang('Forum')).' '.
    get_lang('Forum').'&nbsp;-&nbsp;<a href="../forum/index.php?'.api_get_cidreq().'">'.
    get_lang('SeeDetail').'</a>'
);
echo '<table class="table table-hover table-striped data_table">';
echo '<tr><td>'.get_lang('ForumForumsNumber').'</td><td align="right">'.$count_number_of_forums_by_course.'</td></tr>';
echo '<tr><td>'.get_lang('ForumThreadsNumber').'</td><td align="right">'.$count_number_of_threads_by_course.'</td></tr>';
echo '<tr><td>'.get_lang('ForumPostsNumber').'</td><td align="right">'.$count_number_of_posts_by_course.'</td></tr>';
echo '</table></div>';
echo '<div class="clear"></div>';

// Chat tracking.
if ($showChatReporting) {
    echo '<div class="report_section">';
    echo Display::page_subheader(
        Display::return_icon('chat.gif', get_lang('Chat')).' '.get_lang('Chat')
    );

    echo '<table class="table table-hover table-striped data_table">';
    $chat_connections_during_last_x_days_by_course = Tracking::chat_connections_during_last_x_days_by_course(
        $course_code,
        7,
        $session_id
    );
    if ($export_csv) {
        $csv_content[] = [get_lang('Chat', ''), ''];
        $csv_content[] = [
            sprintf(
                get_lang('ChatConnectionsDuringLastXDays', ''),
                '7'
            ),
            $chat_connections_during_last_x_days_by_course,
        ];
    }
    echo '<tr><td>';
    echo sprintf(
        get_lang('ChatConnectionsDuringLastXDays'),
        '7'
    );
    echo '</td><td align="right">'.$chat_connections_during_last_x_days_by_course.'</td></tr>';

    echo '</table></div>';
    echo '<div class="clear"></div>';
}

// Tools tracking.
if ($showTrackingReporting) {
    echo '<div class="report_section">';
    echo Display::page_subheader(
        Display::return_icon(
            'acces_tool.gif',
            get_lang('ToolsMostUsed')
        ).' '.get_lang('ToolsMostUsed')
    );
    echo '<table class="table table-hover table-striped data_table">';

    $tools_most_used = Tracking::get_tools_most_used_by_course(
        $course_id,
        $session_id
    );

    if ($export_csv) {
        $temp = [get_lang('ToolsMostUsed'), ''];
        $csv_content[] = $temp;
    }

    if (!empty($tools_most_used)) {
        foreach ($tools_most_used as $row) {
            echo '<tr>
                    <td>'.get_lang(ucfirst($row['access_tool'])).'</td>
                    <td align="right">'.$row['count_access_tool'].' '.get_lang('Clicks').'</td>
                  </tr>';
            if ($export_csv) {
                $temp = [
                    get_lang(ucfirst($row['access_tool']), ''),
                    $row['count_access_tool'].' '.get_lang('Clicks', ''),
                ];
                $csv_content[] = $temp;
            }
        }
    }

    echo '</table></div>';
    echo '<div class="clear"></div>';
}

if ($documentReporting) {
    // Documents tracking.
    if (!isset($_GET['num']) || empty($_GET['num'])) {
        $num = 3;
        $link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&num=1#documents_tracking">'.get_lang('SeeDetail').'</a>';
    } else {
        $num = 1000;
        $link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&num=0#documents_tracking">'.get_lang('ViewMinus').'</a>';
    }

    echo '<a name="documents_tracking" id="a"></a><div class="report_section">';
    echo Display::page_subheader(
        Display::return_icon(
            'documents.gif',
            get_lang('DocumentsMostDownloaded')
        ).'&nbsp;'.get_lang('DocumentsMostDownloaded').$link
    );

    echo '<table class="table table-hover table-striped data_table">';
    $documents_most_downloaded = Tracking::get_documents_most_downloaded_by_course(
        $course_code,
        $session_id,
        $num
    );

    if ($export_csv) {
        $temp = [get_lang('DocumentsMostDownloaded', ''), ''];
        $csv_content[] = ['', ''];
        $csv_content[] = $temp;
    }

    if (!empty($documents_most_downloaded)) {
        foreach ($documents_most_downloaded as $row) {
            echo '<tr>
                    <td>';
            echo Display::url(
                $row['down_doc_path'],
                api_get_path(
                    WEB_CODE_PATH
                ).'document/show_content.php?file='.$row['down_doc_path'].$course_path_params
            );
            echo '</td>
                    <td align="right">'.$row['count_down'].' '.get_lang('Clicks').'</td>
                  </tr>';
            if ($export_csv) {
                $temp = [
                    $row['down_doc_path'],
                    $row['count_down'].' '.get_lang('Clicks', ''),
                ];
                $csv_content[] = $temp;
            }
        }
    } else {
        echo '<tr><td>'.get_lang('NoDocumentDownloaded').'</td></tr>';
        if ($export_csv) {
            $temp = [get_lang('NoDocumentDownloaded', ''), ''];
            $csv_content[] = $temp;
        }
    }
    echo '</table></div>';
    echo '<div class="clear"></div>';
}

if ($linkReporting) {
    // links tracking
    echo '<div class="report_section">';
    echo Display::page_subheader(
        Display::return_icon(
            'link.gif',
            get_lang('LinksMostClicked')
        ).'&nbsp;'.get_lang('LinksMostClicked')
    );
    echo '<table class="table table-hover table-striped data_table">';
    $links_most_visited = Tracking::get_links_most_visited_by_course(
        $course_code,
        $session_id
    );

    if ($export_csv) {
        $temp = [get_lang('LinksMostClicked'), ''];
        $csv_content[] = ['', ''];
        $csv_content[] = $temp;
    }

    if (!empty($links_most_visited)) {
        foreach ($links_most_visited as $row) {
            echo '<tr><td>';
            echo Display::url(
                $row['title'].' ('.$row['url'].')',
                $row['url']
            );
            echo '</td><td align="right">'.$row['count_visits'].' '.get_lang('Clicks').'</td></tr>';
            if ($export_csv) {
                $temp = [
                    $row['title'],
                    $row['count_visits'].' '.get_lang('Clicks', ''),
                ];
                $csv_content[] = $temp;
            }
        }
    } else {
        echo '<tr><td>'.get_lang('NoLinkVisited').'</td></tr>';
        if ($export_csv) {
            $temp = [get_lang('NoLinkVisited'), ''];
            $csv_content[] = $temp;
        }
    }
    echo '</table></div>';
    echo '<div class="clear"></div>';
}

// send the csv file if asked
if ($export_csv) {
    ob_end_clean();
    Export::arrayToCsv($csv_content, 'reporting_course_tools');
    exit;
}

Display::display_footer();
