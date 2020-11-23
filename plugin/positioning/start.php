<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);

$plugin = Positioning::create();
if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

if (!api_is_allowed_to_edit()) {
    // Students are redirected to the start_student.php
    api_location(api_get_path(WEB_PLUGIN_PATH).'positioning/start_student.php?'.api_get_cidreq());
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$formToString = '';
$currentUrl = api_get_self().'?'.api_get_cidreq();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$courseInfo = api_get_course_info();

switch ($action) {
    case 'set_initial':
        Display::addFlash(Display::return_message(get_lang('Updated')));
        $plugin->setInitialExercise($id, $courseId, $sessionId);
        api_location($currentUrl);
        break;
    case 'set_final':
        Display::addFlash(Display::return_message(get_lang('Updated')));
        $plugin->setFinalExercise($id, $courseId, $sessionId);
        api_location($currentUrl);
        break;
}

$nameTools = $plugin->get_lang('Positioning');

$htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');
$template = new Template($nameTools);
$url = $currentUrl.'&';
$actions = function ($row) use ($plugin, $url, $courseId, $sessionId) {
    $classInitial = 'btn btn-default';
    if ($plugin->isInitialExercise($row['iid'], $courseId, $sessionId)) {
        $classInitial = 'btn btn-primary disabled';
    }

    $classFinal = 'btn btn-default';
    if ($plugin->isFinalExercise($row['iid'], $courseId, $sessionId)) {
        $classFinal = 'btn btn-primary disabled';
    }

    $actions = Display::url(
        $plugin->get_lang('SelectAsInitialTest'),
        $url.'&action=set_initial&id='.$row['iid'],
        ['class' => $classInitial]
    );
    $actions .= Display::url(
        $plugin->get_lang('SelectAsFinalTest'),
        $url.'&action=set_final&id='.$row['iid'],
        ['class' => $classFinal]
    );

    return $actions;
};

$table = Exercise::exerciseGrid(
    0,
    null,
    null,
    null,
    null,
    false,
    3,
    RESULT_DISABLE_RADAR,
    1,
    $actions,
    true
);

$exercisesToString = '';
if (!empty($table)) {
    if ($table instanceof SortableTableFromArrayConfig) {
        $table->headers = [];
        $table->set_header(0, get_lang('ExerciseName'), false);
        $table->set_header(1, get_lang('QuantityQuestions'), false);
        $table->set_header(2, get_lang('Actions'), false);
        $exerciseList = [];
        foreach ($table->table_data as &$data) {
            $data = [
                $data[1],
                $data[2],
                $data[3],
            ];
        }

        $table->set_form_actions([]);
        $exercisesToString = $table->return_table();
    } else {
        $exercisesToString = Display::return_message(get_lang('NoDataAvailable'), 'warning');
    }
}

$initialData = $plugin->getInitialExercise($courseId, $sessionId);
$users = CourseManager::get_user_list_from_course_code(api_get_course_id(), $sessionId);
$radars = '';

$initialExerciseTitle = '';
if (!empty($users) && $initialData && $initialData['exercise_id']) {
    $users = array_column($users, 'user_id');
    $exerciseId = $initialData['exercise_id'];
    $initialExercise = new Exercise();
    $initialExercise->read($exerciseId);
    $radars = $initialExercise->getRadarsFromUsers($users, [$initialExercise], $courseId, $sessionId);
    $initialExerciseTitle = $initialExercise->get_formated_title();
}

$template->assign(
    'positioning_introduction',
    Display::return_message($plugin->get_lang('PositioningIntroduction'))
);
$template->assign('table', $exercisesToString);
$template->assign('radars', $radars);
$template->assign('initial_exercise', $initialExerciseTitle);
$template->assign('content', $template->fetch('positioning/view/start.tpl'));
$template->display_one_col_template();
