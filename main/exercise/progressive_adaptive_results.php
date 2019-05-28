<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizDestinationResult;
use ChamiloSession as Session;

/**
 * Shows the exercise results.
 *
 * @author Julio Montoya - Simple exercise result page
 */
require_once __DIR__.'/../inc/global.inc.php';

$hash = isset($_REQUEST['hash']) ? $_GET['hash'] : null;
$origin = api_get_origin();

$showHeaders = !in_array($origin, ['learnpath', 'embeddable']);

if (empty($hash)) {
    api_not_allowed(
        $showHeaders,
        Display::return_message(get_lang('NoData'), 'warning')
    );
}

$em = Database::getManager();

/** @var CQuizDestinationResult $destinationResult */
$destinationResult = $em
    ->getRepository('ChamiloCourseBundle:CQuizDestinationResult')
    ->findOneBy(['hash' => $hash]);

if (empty($destinationResult)) {
    api_not_allowed(
        $showHeaders,
        Display::return_message(get_lang('NoData'), 'warning')
    );
}

$exe = $destinationResult->getExe();
$student = $destinationResult->getUser();
$course = api_get_course_entity($exe->getCId());
$session = api_get_session_entity($exe->getSessionId());

$objExercise = new Exercise($course->getId());
$objExercise->sessionId = $session ? $session->getId() : 0;

if (false === $objExercise->read($exe->getExeExoId())) {
    api_not_allowed(
        $showHeaders,
        Display::return_message(get_lang('NoData'), 'warning')
    );
}

$isAdaptive = EXERCISE_FEEDBACK_TYPE_PROGRESSIVE_ADAPTIVE == $objExercise->selectFeedbackType();

if (!$isAdaptive) {
    api_not_allowed(
        $showHeaders,
        Display::return_message(get_lang('NoData'), 'warning')
    );
}

$message = Session::read('attempt_remaining');
Session::erase('attempt_remaining');

$userInfo = api_get_user_info($student->getId());

$quizzesDir = ExerciseLib::checkQuizzesPath(
    $exe->getExeUserId()
);

if (empty($quizzesDir)) {
    api_not_allowed(
        $showHeaders,
        Display::return_message(get_lang('NoData'), 'warning')
    );
}

// Getting results from the exe_id. This variable also contain all the information about the exercise
$trackExerciseInfo = ExerciseLib::get_exercise_track_exercise_info(
    $exe->getExeId()
);

$content = $objExercise->showExerciseResultHeader($userInfo, $trackExerciseInfo);

$extraFields = api_get_configuration_value('quiz_adaptive_show_extrafields');
$courseFields = [];
$sessionFields = [];

if (isset($extraFields['course'])) {
    $ef = new ExtraField('course');
    $efv = new ExtraFieldValue('course');

    foreach ($extraFields['course'] as $variable) {
        $extraField = $ef->get_handler_field_info_by_field_variable($variable);

        if (false === $extraField) {
            continue;
        }

        $extraValue = $efv->get_values_by_handler_and_field_id($course->getId(), $extraField['id'], true);

        if (false === $extraValue) {
            continue;
        }

        $courseFields[$extraField['display_text']] = $extraValue['value'];
    }
}

if ($session) {
    if (isset($extraFields['session'])) {
        $ef = new ExtraField('session');
        $efv = new ExtraFieldValue('session');

        foreach ($extraFields['session'] as $variable) {
            $extraField = $ef->get_handler_field_info_by_field_variable($variable);

            if (false === $extraField) {
                continue;
            }

            $extraValue = $efv->get_values_by_handler_and_field_id($session->getId(), $extraField['id'], true);

            if (false === $extraValue) {
                continue;
            }

            $sessionExtra['fields'][$extraField['display_text']] = $extraValue['value'];
        }
    }
}

$this_section = SECTION_COURSES;

$view = new Template(get_lang('LevelReachedInQuiz'), $showHeaders, $showHeaders);
$view->assign('results_header', $content);
$view->assign('result', $destinationResult);
$view->assign('course', $course);
$view->assign('course_fields', $courseFields);
$view->assign('session', $session);
$view->assign('session_fields', $sessionFields);
$view->assign('exe_duration', api_format_time($exe->getExeDuration(), 'js'));
$view->assign('qr', $quizzesDir['web'].$destinationResult->getHash().'.png');
$layout = $view->get_template('exercise/progressive_adaptive_results.tpl');
$content = $view->fetch($layout);
$view->assign('content', $content);
$view->display_one_col_template();
