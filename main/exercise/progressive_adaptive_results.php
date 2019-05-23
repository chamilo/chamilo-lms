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

$show_headers = !in_array($origin, ['learnpath', 'embeddable']);

if (empty($hash)) {
    api_not_allowed(
        $show_headers,
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
        $show_headers,
        Display::return_message(get_lang('NoData'), 'warning')
    );
}

$exe = $destinationResult->getExe();
$student = $destinationResult->getUser();

$objExercise = new Exercise();
$objExercise->course_id = $exe->getCId();
$objExercise->sessionId = $exe->getSessionId();

if (false === $objExercise->read($exe->getExeExoId())) {
    api_not_allowed(
        $show_headers,
        Display::return_message(get_lang('NoData'), 'warning')
    );
}

$isAdaptive = EXERCISE_FEEDBACK_TYPE_PROGRESSIVE_ADAPTIVE == $objExercise->selectFeedbackType();

if (!$isAdaptive) {
    api_not_allowed(
        $show_headers,
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
        $show_headers,
        Display::return_message(get_lang('NoData'), 'warning')
    );
}

// Getting results from the exe_id. This variable also contain all the information about the exercise
$trackExerciseInfo = ExerciseLib::get_exercise_track_exercise_info(
    $exe->getExeId()
);

if ($show_headers) {
    $this_section = SECTION_COURSES;

    Display::display_header(get_lang('Result'));
} else {
    Display::display_reduced_header();
}

$content = '';
$content .= $objExercise->showExerciseResultHeader($userInfo, $trackExerciseInfo);
$content .= PHP_EOL;
$content .= '
    <div class="row">
        <div class="col-md-4 text-right">
            '.Display::img($quizzesDir['web'].$destinationResult->getHash().'.png').'
        </div>
        <div class="col-md-8 text-left">
            <p class="lead">'.sprintf(get_lang('LevelReachedX'), $destinationResult->getAchievedLevel()).'</p>
            <p>'.$student->getCompleteNameWithUsername().'</p>
            <p>'.sprintf(get_lang('ResultHashX'), $destinationResult->getHash()).'</p>
        </div>
    </div>
';

echo $content;

if ($show_headers) {
    Display::display_footer();
} else {
    Display::display_reduced_footer();
}
