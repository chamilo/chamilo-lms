<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);

$plugin = Positioning::create();
if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$currentUrl = api_get_self().'?'.api_get_cidreq();
$courseId = api_get_course_int_id();
$courseCode = api_get_course_id();
$sessionId = api_get_session_id();
$currentUserId = api_get_user_id();

$initialData = $plugin->getInitialExercise($courseId, $sessionId);
$finalData = $plugin->getFinalExercise($courseId, $sessionId);

$initialExerciseTitle = '';
$radar = '';
$initialResults = null;
$exercisesToRadar = [];
$exercisesToRadarLabel = [];
$initialExercise = null;

if ($initialData) {
    $exerciseId = $initialData['exercise_id'];
    $initialExercise = new Exercise();
    $initialExercise->read($exerciseId);
    $initialResults = Event::getExerciseResultsByUser(
        $currentUserId,
        $initialData['exercise_id'],
        $courseId,
        $sessionId
    );

    $initialExerciseTitle = $initialExercise->get_formated_title();
    if (empty($initialResults)) {
        $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&exerciseId='.$exerciseId;
        $initialExerciseTitle = Display::url($initialExercise->get_formated_title(), $url);
    }
}

$course = api_get_course_entity($courseId);
$session = $sessionId ? api_get_session_entity($sessionId) : null;

$studentAverage = Tracking::get_avg_student_progress(
    $currentUserId,
    $course,
    [],
    $session
);

$averageToUnlock = (float) $plugin->get('average_percentage_to_unlock_final_exercise');

$finalExerciseTitle = '';
if ($finalData) {
    $exerciseId = $finalData['exercise_id'];
    $finalExercise = new Exercise();
    $finalExercise->read($exerciseId);
    $finalResults = Event::getExerciseResultsByUser(
        api_get_user_id(),
        $finalData['exercise_id'],
        $courseId,
        $sessionId
    );

    $finalExerciseTitle = $finalExercise->get_formated_title();
    if (!empty($initialResults)) {
        if ($studentAverage >= $averageToUnlock) {
            $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&exerciseId='.$exerciseId;
            if (empty($finalResults)) {
                $finalExerciseTitle = Display::url($finalExercise->get_formated_title(), $url);
            }
        }
        $exercisesToRadar[] = $finalExercise;
        $exercisesToRadarLabel[] = $plugin->get_lang('FinalTest');
    }
}
// Set the initial results as second series to make sure it appears on top
$lpUrlAndProgress = $studentAverage.'%';
if (!empty($initialExercise)) {
    $exercisesToRadar[] = $initialExercise;
    $exercisesToRadarLabel[] = $plugin->get_lang('InitialTest');
    if (!empty($initialResults)) {
        $lpUrlAndProgress = '<a href="'.api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'">'.$studentAverage.'%</a>';
    }
}

$radars = null;
if ($initialExercise instanceof Exercise) {
    $radars = $initialExercise->getRadarsFromUsers(
        [$currentUserId],
        $exercisesToRadar,
        $exercisesToRadarLabel,
        $courseId,
        $sessionId
    );
}

$nameTools = $plugin->get_lang('Positioning');

$template = new Template($nameTools);

$template->assign('initial_exercise', $initialExerciseTitle);
$template->assign('final_exercise', $finalExerciseTitle);
$template->assign('average_percentage_to_unlock_final_exercise', $averageToUnlock);
$template->assign('lp_url_and_progress', $lpUrlAndProgress);
$template->assign('radars', $radars);
$template->assign('content', $template->fetch('Positioning/view/start_student.tpl'));
$template->display_one_col_template();
