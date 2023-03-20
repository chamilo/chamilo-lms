<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuiz;
use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_TRACKING;

$course = api_get_course_entity();
$groupId = api_get_group_id();
$session_id = api_get_session_id();
$session = api_get_session_entity($session_id);

$from_myspace = false;
$from = $_GET['from'] ?? null;

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
$export_csv = isset($_GET['export']) && 'csv' == $_GET['export'] ? true : false;

if ($export_csv) {
    if (!empty($session_id)) {
        Session::write('id_session', $session_id);
    }
    ob_start();
}
$csv_content = [];

// Breadcrumbs.
if (isset($_GET['origin']) && 'resume_session' === $_GET['origin']) {
    $interbreadcrumb[] = ['url' => '../admin/index.php', 'name' => get_lang('Administration')];
    $interbreadcrumb[] = ['url' => '../session/session_list.php', 'name' => get_lang('Session list')];
    $interbreadcrumb[] = [
        'url' => '../session/resume_session.php?id_session='.api_get_session_id(),
        'name' => get_lang('Session overview'),
    ];
}

$view = $_REQUEST['view'] ?? '';
$nameTools = get_lang('Reporting');

// Display the header.
Display::display_header($nameTools, 'Tracking');

// getting all the students of the course
if (empty($session_id)) {
    // Registered students in a course outside session.
    $a_students = CourseManager::get_student_list_from_course_code(
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
    $a_students = CourseManager::get_student_list_from_course_code(
        api_get_course_id(),
        true,
        api_get_session_id()
    );
}
$nbStudents = count($a_students);
$student_ids = array_keys($a_students);
$studentCount = count($student_ids);

$left = TrackingCourseLog::actionsLeft('courses', api_get_session_id(), false);

$right = '<a href="javascript: void(0);" onclick="javascript: window.print();">'.
    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';

$right .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&id_session='.api_get_session_id().'&export=csv">
	'.Display::return_icon('export_csv.png', get_lang('CSV export'), '', ICON_SIZE_MEDIUM).'</a>';
$right .= '</span>';

echo Display::toolbarAction('tools', [$left, $right]);

$course_code = api_get_course_id();
$course_id = api_get_course_int_id();

if ($lpReporting) {
    $list = new LearnpathList(null, ['real_id' => $course->getId(), 'code' => $course->getCode()], $session_id);
    $flat_list = $list->get_flat_list();

    if (count($flat_list) > 0) {
        // learning path tracking
        echo '<div class="report_section">';
        echo Display::page_subheader(
            Display::return_icon(
                'scorms.gif',
                get_lang('Progress in courses')
            ).' '.get_lang('Progress in courses')
        );
        echo '<table class="data_table">';
        if ($export_csv) {
            $temp = [get_lang('Progress in courses'), ''];
            $csv_content[] = ['', ''];
            $csv_content[] = $temp;
        }

        foreach ($flat_list as $lp_id => $lp) {
            $lp_avg_progress = 0;
            foreach ($a_students as $student_id => $student) {
                // get the progress in learning pathes
                $lp_avg_progress += Tracking::get_avg_student_progress(
                    $student_id,
                    $course,
                    [$lp_id],
                    $session
                );
            }

            $lp_avg_progress = null;
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
            $temp = [get_lang('NoLearningPath'), ''];
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
            get_lang('Tests score')
        ).' '.get_lang('Tests score')
    );
    echo '<table class="data_table">';

    if ($export_csv) {
        $temp = [get_lang('Progress in courses'), ''];
        $csv_content[] = ['', ''];
        $csv_content[] = $temp;
    }

    $course_path_params = '&cid='.$course_id.'&sid='.$session_id;

    /*$course_id = api_get_course_int_id();
    $sql = "SELECT iid, title FROM $TABLEQUIZ
            WHERE c_id = $course_id AND active <> -1 AND session_id = $session_id";
    $rs = Database::query($sql);*/

    $session = api_get_session_entity($session_id);
    $qb = Container::getQuizRepository()->findAllByCourse($course, $session, null, 2, false);
    /** @var CQuiz[] $exercises */
    $exercises = $qb->getQuery()->getResult();

    if (!empty($exercises)) {
        foreach ($exercises as $exercise) {
            $exerciseId = $exercise->getIid();
            $quiz_avg_score = 0;
            if ($studentCount > 0) {
                foreach ($student_ids as $student_id) {
                    $avg_student_score = Tracking::get_avg_student_exercise_score(
                        $student_id,
                        $course_code,
                        $exerciseId,
                        $session_id
                    );
                    $quiz_avg_score += $avg_student_score;
                }
            }
            $studentCount = (0 == $studentCount || is_null($studentCount) || '' == $studentCount) ? 1 : $studentCount;
            $quiz_avg_score = round(($quiz_avg_score / $studentCount), 2).'%';
            $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?exerciseId='.$exerciseId.$course_path_params;

            echo '<tr><td>';
            echo Display::url(
                $exercise->getTitle(),
                $url
            );
            echo '</td><td align="right">'.$quiz_avg_score.'</td></tr>';
            if ($export_csv) {
                $temp = [$exercise->getTitle(), $quiz_avg_score];
                $csv_content[] = $temp;
            }
        }
    } else {
        echo '<tr><td>'.get_lang('No tests').'</td></tr>';
        if ($export_csv) {
            $temp = [get_lang('No tests', ''), ''];
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
    $course_id,
    $session_id,
    $groupId
);

$count_number_of_threads_by_course = Tracking::count_number_of_threads_by_course(
    $course_id,
    $session_id,
    $groupId
);

$count_number_of_posts_by_course = Tracking::count_number_of_posts_by_course(
    $course_id,
    $session_id,
    $groupId
);

if ($export_csv) {
    $csv_content[] = [get_lang('Forum')];
    $csv_content[] = [get_lang('Forums Number'), $count_number_of_forums_by_course];
    $csv_content[] = [get_lang('Threads number'), $count_number_of_threads_by_course];
    $csv_content[] = [get_lang('Posts number'), $count_number_of_posts_by_course];
}

// Forums tracking.
echo '<div class="report_section">';
echo Display::page_subheader(
    Display::return_icon('forum.gif', get_lang('Forum')).' '.
    get_lang('Forum').'&nbsp;-&nbsp;<a href="../forum/index.php?'.api_get_cidreq().'">'.
    get_lang('See detail').'</a>'
);
echo '<table class="data_table">';
echo '<tr><td>'.get_lang('Forums Number').'</td><td align="right">'.$count_number_of_forums_by_course.'</td></tr>';
echo '<tr><td>'.get_lang('Threads number').'</td><td align="right">'.$count_number_of_threads_by_course.'</td></tr>';
echo '<tr><td>'.get_lang('Posts number').'</td><td align="right">'.$count_number_of_posts_by_course.'</td></tr>';
echo '</table></div>';
echo '<div class="clear"></div>';

// Chat tracking.
if ($showChatReporting) {
    echo '<div class="report_section">';
    echo Display::page_subheader(
        Display::return_icon('chat.gif', get_lang('Chat')).' '.get_lang('Chat')
    );

    echo '<table class="data_table">';
    $chat_connections_during_last_x_days_by_course = Tracking::chat_connections_during_last_x_days_by_course(
        $course_code,
        7,
        $session_id
    );
    if ($export_csv) {
        $csv_content[] = [get_lang('Chat', ''), ''];
        $csv_content[] = [
            sprintf(
                get_lang('Connections to the chat during last %s days', ''),
                '7'
            ),
            $chat_connections_during_last_x_days_by_course,
        ];
    }
    echo '<tr><td>';
    echo sprintf(
        get_lang('Connections to the chat during last %s days'),
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
            get_lang('Tools most used')
        ).' '.get_lang('Tools most used')
    );
    echo '<table class="table table-hover table-striped data_table">';

    $tools_most_used = Tracking::get_tools_most_used_by_course(
        $course_id,
        $session_id
    );

    if ($export_csv) {
        $temp = [get_lang('Tools most used'), ''];
        $csv_content[] = $temp;
    }

    if (!empty($tools_most_used)) {
        foreach ($tools_most_used as $row) {
            echo '<tr>
                    <td>'.get_lang(ucfirst($row['access_tool'])).'</td>
                    <td align="right">'.$row['count_access_tool'].' '.get_lang('clicks').'</td>
                  </tr>';
            if ($export_csv) {
                $temp = [
                    get_lang(ucfirst($row['access_tool']), ''),
                    $row['count_access_tool'].' '.get_lang('clicks', ''),
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
        $link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&num=1#documents_tracking">'.get_lang('See detail').'</a>';
    } else {
        $num = 1000;
        $link = '&nbsp;-&nbsp;<a href="'.api_get_self().'?'.api_get_cidreq().'&num=0#documents_tracking">'.get_lang('View minus').'</a>';
    }

    echo '<a name="documents_tracking" id="a"></a><div class="report_section">';
    echo Display::page_subheader(
        Display::return_icon(
            'documents.gif',
            get_lang('Documents most downloaded')
        ).'&nbsp;'.get_lang('Documents most downloaded').$link
    );

    echo '<table class="table table-hover table-striped data_table">';
    $documents_most_downloaded = Tracking::get_documents_most_downloaded_by_course(
        $course_code,
        $session_id,
        $num
    );

    if ($export_csv) {
        $temp = [get_lang('Documents most downloaded', ''), ''];
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
                    <td align="right">'.$row['count_down'].' '.get_lang('clicks').'</td>
                  </tr>';
            if ($export_csv) {
                $temp = [
                    $row['down_doc_path'],
                    $row['count_down'].' '.get_lang('clicks', ''),
                ];
                $csv_content[] = $temp;
            }
        }
    } else {
        echo '<tr><td>'.get_lang('No document downloaded').'</td></tr>';
        if ($export_csv) {
            $temp = [get_lang('No document downloaded', ''), ''];
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
            get_lang('Links most visited')
        ).'&nbsp;'.get_lang('Links most visited')
    );
    echo '<table class="table table-hover table-striped data_table">';
    $links_most_visited = Tracking::get_links_most_visited_by_course(
        $course_code,
        $session_id
    );

    if ($export_csv) {
        $temp = [get_lang('Links most visited'), ''];
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
            echo '</td><td align="right">'.$row['count_visits'].' '.get_lang('clicks').'</td></tr>';
            if ($export_csv) {
                $temp = [
                    $row['title'],
                    $row['count_visits'].' '.get_lang('clicks'),
                ];
                $csv_content[] = $temp;
            }
        }
    } else {
        echo '<tr><td>'.get_lang('No link visited').'</td></tr>';
        if ($export_csv) {
            $temp = [get_lang('No link visited'), ''];
            $csv_content[] = $temp;
        }
    }
    echo '</table></div>';
    echo '<div class="clear"></div>';
}

// send the csv file if asked
if ($export_csv) {
    ob_end_clean();
    Export:: arrayToCsv($csv_content, 'reporting_course_tools');
    exit;
}

Display::display_footer();
