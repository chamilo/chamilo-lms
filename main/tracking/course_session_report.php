<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;
$this_section = 'session_my_space';
$is_allowedToTrack = Tracking::isAllowToTrack($session_id);

if (!$is_allowedToTrack) {
    api_not_allowed(true);
}

$export_to_xls = false;
if (isset($_GET['export'])) {
    $export_to_xls = true;
}
if (api_is_platform_admin()) {
    $global = true;
} else {
    $global = false;
}
$global = true;

if (empty($session_id)) {
    $session_id = 1;
}

$form = new FormValidator('search_simple', 'POST', '', '', null, false);

//Get session list
$session_list = SessionManager::get_sessions_list([], ['name']);
$my_session_list = [];
foreach ($session_list as $sesion_item) {
    $my_session_list[$sesion_item['id']] = $sesion_item['name'];
}
if (count($session_list) == 0) {
    $my_session_list[0] = get_lang('None');
}
$form->addElement('select', 'session_id', get_lang('Sessions'), $my_session_list);
$form->addButtonFilter(get_lang('Filter'));

if (!empty($_REQUEST['score'])) {
    $filter_score = intval($_REQUEST['score']);
} else {
    $filter_score = 70;
}
if (!empty($_REQUEST['session_id'])) {
    $session_id = intval($_REQUEST['session_id']);
} else {
    $session_id = 0;
}

if (empty($session_id)) {
    $session_id = key($my_session_list);
}
$form->setDefaults(['session_id' => $session_id]);
$course_list = SessionManager::get_course_list_by_session_id($session_id);

if (!$export_to_xls) {
    Display::display_header(get_lang("MySpace"));
    echo '<div class="actions">';

    if ($global) {
        echo MySpace::getTopMenu();
    } else {
        echo '<div style="float:left; clear:left">
                <a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.get_lang('StudentsTracking').'</a>&nbsp;|
                <a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.get_lang('CourseTracking').'</a>&nbsp;';
        echo '</div>';
    }
    echo '</div>';

    if (api_is_platform_admin()) {
        echo MySpace::getAdminActions();
    }

    echo '<h2>'.get_lang('LPExerciseResultsBySession').'</h2>';
    $form->display();
    echo Display::return_message(get_lang('StudentScoreAverageIsCalculatedBaseInAllLPsAndAllAttempts'));
}

$users = SessionManager::get_users_by_session($session_id);
$course_average = $course_average_counter = [];

$counter = 0;
$main_result = [];
// Getting course list
foreach ($course_list as $current_course) {
    $course_info = api_get_course_info($current_course['code']);
    $_course = $course_info;
    $attempt_result = [];

    // Getting LP list
    $list = new LearnpathList('', $course_info, $session_id);
    $lp_list = $list->get_flat_list();

    // Looping LPs
    foreach ($lp_list as $lp_id => $lp) {
        $exercise_list = Event::get_all_exercises_from_lp($lp_id, $course_info['real_id']);
        // Looping Chamilo Exercises in LP
        foreach ($exercise_list as $exercise) {
            $exercise_stats = Event::get_all_exercise_event_from_lp(
                $exercise['path'],
                $course_info['real_id'],
                $session_id
            );
            // Looping Exercise Attempts
            foreach ($exercise_stats as $stats) {
                $attempt_result[$stats['exe_user_id']]['result'] += $stats['exe_result'] / $stats['exe_weighting'];
                $attempt_result[$stats['exe_user_id']]['attempts']++;
            }
        }
    }
    $main_result[$current_course['code']] = $attempt_result;
}

$total_average_score = 0;
$total_average_score_count = 0;
$html_result = '';
if (!empty($users) && is_array($users)) {
    $html_result .= '<table  class="table table-hover table-striped data_table">';
    $html_result .= '<tr><th>'.get_lang('User').'</th>';
    foreach ($course_list as $item) {
        $html_result .= '<th>'.$item['title'].'<br /> '.get_lang('AverageScore').' %</th>';
    }
    $html_result .= '<th>'.get_lang('AverageScore').' %</th>';
    $html_result .= '<th>'.get_lang('LastConnexionDate').'</th></tr>';

    foreach ($users as $user) {
        $total_student = 0;
        $counter++;
        $s_css_class = 'row_even';
        if ($counter % 2 == 0) {
            $s_css_class = 'row_odd';
        }
        $html_result .= "<tr class='$s_css_class'>
                            <td >";
        $html_result .= $user['firstname'].' '.$user['lastname'];
        $html_result .= "</td>";

        // Getting course list
        $counter = 0;
        $total_result_by_user = 0;
        foreach ($course_list as $current_course) {
            $total_course = 0;
            $html_result .= "<td>";
            $result = '-';
            if (isset($main_result[$current_course['code']][$user['user_id']])) {
                $user_info_stat = $main_result[$current_course['code']][$user['user_id']];
                if (!empty($user_info_stat['result']) && !empty($user_info_stat['attempts'])) {
                    $result = round(
                        $user_info_stat['result'] / $user_info_stat['attempts'] * 100,
                        2
                    );
                    $total_course += $result;
                    $total_result_by_user += $result;
                    $course_average[$current_course['code']] += $total_course;
                    $course_average_counter[$current_course['code']]++;
                    $result = $result.' ('.$user_info_stat['attempts'].' '.get_lang('Attempts').')';
                    $counter++;
                }
            }

            $html_result .= $result;
            $html_result .= "</td>";
        }
        if (empty($counter)) {
            $total_student = '-';
        } else {
            $total_student = $total_result_by_user / $counter;
            $total_average_score += $total_student;
            $total_average_score_count++;
        }
        $string_date = Tracking::get_last_connection_date($user['user_id'], true);
        $html_result .= "<td>$total_student</td><td>$string_date</td></tr>";
    }

    $html_result .= "<tr><th>".get_lang('AverageScore')."</th>";
    $total_average = 0;
    $counter = 0;
    foreach ($course_list as $course_item) {
        if (!empty($course_average_counter[$course_item['code']])) {
            $average_per_course = round(
                $course_average[$course_item['code']] / ($course_average_counter[$course_item['code']] * 100) * 100,
                2
            );
        } else {
            $average_per_course = 0;
        }
        if (!empty($average_per_course)) {
            $counter++;
        }
        $total_average = $total_average + $average_per_course;
        $html_result .= "<td>$average_per_course</td>";
    }
    if (!empty($total_average_score_count)) {
        $total_average = round($total_average_score / ($total_average_score_count * 100) * 100, 2);
    } else {
        $total_average = '-';
    }

    $html_result .= '<td>'.$total_average.'</td>';
    $html_result .= "<td>-</td>";
    $html_result .= "</tr>";
    $html_result .= '</table>';
} else {
    echo Display::return_message(get_lang('NoResults'), 'warning');
}

if (!$export_to_xls) {
    echo $html_result;
}

Display::display_footer();
