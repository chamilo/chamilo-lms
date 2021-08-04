<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = 'session_my_space';

$allow = Tracking::isAllowToTrack(api_get_session_id());

if (!$allow) {
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

$course_list = $course_select_list = [];
$html_result = '';
$course_select_list[0] = get_lang('None');

$htmlHeadXtra[] = '
<script type="text/javascript">
function load_courses() {
    document.search_simple.submit();
}
</script>';

$session_id = isset($_REQUEST['session_id']) ? intval($_REQUEST['session_id']) : null;

if (empty($session_id)) {
    $temp_course_list = CourseManager::get_courses_list();
} else {
    $temp_course_list = SessionManager::get_course_list_by_session_id($session_id);
}

foreach ($temp_course_list as $temp_course_item) {
    $course_item = api_get_course_info($temp_course_item['code']);
    $course_select_list[$temp_course_item['code']] = $course_item['title'];
}

//Get session list
$session_list = SessionManager::get_sessions_list([], ['name']);

$my_session_list = [];
$my_session_list[0] = get_lang('None');
foreach ($session_list as $sesion_item) {
    $my_session_list[$sesion_item['id']] = $sesion_item['name'];
}

$form = new FormValidator('search_simple', 'POST', '', '', null);
$form->addElement(
    'select',
    'session_id',
    get_lang('Sessions'),
    $my_session_list,
    ['id' => 'session_id', 'onchange' => 'load_courses();']
);
$form->addElement(
    'select',
    'course_code',
    get_lang('Courses'),
    $course_select_list
);
$form->addButtonFilter(get_lang('Filter'), 'submit_form');

if (!empty($_REQUEST['course_code'])) {
    $course_code = $_REQUEST['course_code'];
} else {
    $course_code = '';
}

if (empty($course_code)) {
    $course_code = 0;
}

$form->setDefaults(['course_code' => (string) $course_code]);

$course_info = api_get_course_info($course_code);

if (!empty($course_info)) {
    $list = new LearnpathList('', $course_info);
    $lp_list = $list->get_flat_list();

    $main_question_list = [];

    foreach ($lp_list as $lp_id => $lp) {
        $exercise_list = Event::get_all_exercises_from_lp(
            $lp_id,
            $course_info['real_id']
        );

        foreach ($exercise_list as $exercise) {
            $my_exercise = new Exercise($course_info['real_id']);
            $my_exercise->read($exercise['path']);
            $question_list = $my_exercise->selectQuestionList();

            $exercise_stats = Event::get_all_exercise_event_from_lp(
                $exercise['path'],
                $course_info['real_id'],
                $session_id
            );

            foreach ($question_list as $question_id) {
                $question_data = Question::read($question_id, $course_info);
                $main_question_list[$question_id] = $question_data;
                $quantity_exercises = 0;
                $question_result = 0;

                foreach ($exercise_stats as $stats) {
                    if (!empty($stats['question_list'])) {
                        foreach ($stats['question_list'] as $my_question_stat) {
                            if ($question_id == $my_question_stat['question_id']) {
                                $question_result = $question_result + $my_question_stat['marks'];
                                $quantity_exercises++;
                            }
                        }
                    }
                }

                if (!empty($quantity_exercises)) {
                    // Score % average
                    $main_question_list[$question_id]->results = ($question_result / ($quantity_exercises));
                } else {
                    $main_question_list[$question_id]->results = 0;
                }

                $main_question_list[$question_id]->quantity = $quantity_exercises;
            }
        }
    }
}

if (!$export_to_xls) {
    Display::display_header(get_lang("MySpace"));
    echo '<div class="actions">';
    if ($global) {
        echo MySpace::getTopMenu();
    } else {
        echo '<div style="float:left; clear:left">
                <a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.
                    get_lang('StudentsTracking').'</a>&nbsp;|
                <a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.
                    get_lang('CourseTracking').'</a>&nbsp;';
        echo '</div>';
    }
    echo '</div>';

    if (api_is_platform_admin()) {
        echo MySpace::getAdminActions();
    }
    echo '<br />';
    echo '<h2>'.get_lang('LPQuestionListResults').'</h2>';

    $form->display();

    if (empty($course_code)) {
        echo Display::return_message(get_lang('PleaseSelectACourse'), 'warning');
    }
}

$course_average = [];
$counter = 0;

if (!empty($main_question_list) && is_array($main_question_list)) {
    $html_result .= '<table  class="table table-hover table-striped data_table">';
    $html_result .= '<tr><th>'.get_lang('Question').
                    Display::return_icon('info3.gif', get_lang('QuestionsAreTakenFromLPExercises'), ['align' => 'absmiddle', 'hspace' => '3px']).'</th>';
    $html_result .= '<th>'.$course_info['visual_code'].' '.get_lang('AverageScore').Display::return_icon('info3.gif', get_lang('AllStudentsAttemptsAreConsidered'), ['align' => 'absmiddle', 'hspace' => '3px']).' </th>';
    $html_result .= '<th>'.get_lang('Quantity').'</th>';

    foreach ($main_question_list as $question) {
        $total_student = 0;
        $counter++;
        $s_css_class = 'row_even';
        if ($counter % 2 == 0) {
            $s_css_class = 'row_odd';
        }
        $html_result .= "<tr class='$s_css_class'>
                            <td >";
        $question_title = trim($question->question);
        if (empty($question_title)) {
            $html_result .= get_lang('Untitled').' '.get_lang('Question').' #'.$question->id;
        } else {
            $html_result .= $question->question;
        }

        $html_result .= "</td>";
        $html_result .= "<td>";
        $html_result .= round($question->results, 2).' / '.$question->weighting;
        $html_result .= "</td>";

        $html_result .= "<td>";
        $html_result .= $question->quantity;
        $html_result .= "</td>";
    }

    $html_result .= "</tr>";
    $html_result .= '</table>';
} else {
    if (!empty($course_code)) {
        echo Display::return_message(get_lang('NoResults'), 'warning');
    }
}

if (!$export_to_xls) {
    echo $html_result;
}

Display::display_footer();
