<?php
/* For license terms, see /license.txt */

use Chamilo\PluginBundle\Entity\LtiProvider\Result;

require_once __DIR__.'/../../../../main/inc/global.inc.php';
require_once __DIR__.'/../../src/LtiProvider.php';

$launch = LtiProvider::create()->launch(true, $_REQUEST['launch_id']);

if (!$launch->hasAgs()) {
    throw new Exception("Don't have grades!");
}

if (!isset($_REQUEST['lti_result_id'])) {
    throw new Exception("Any tool result");
}

$launchData = $launch->getLaunchData();

$courseCode = $_REQUEST['cidReq'];
$courseId = api_get_course_int_id($courseCode);
$toolName = $_REQUEST['lti_tool'];

if (in_array($toolName, ['quiz', 'lp'])) {
    // The launch only grades its own user, in the course and tool it was opened for.
    /** @var Result|null $launchResult */
    $launchResult = Database::getManager()
        ->getRepository(Result::class)
        ->findOneBy(['ltiLaunchId' => $_REQUEST['launch_id']]);

    if (null === $launchResult
        || $launchResult->getUserId() !== api_get_user_id()
        || (int) api_get_course_int_id($launchResult->getCourseCode()) !== (int) $courseId
        || $launchResult->getToolName() !== $toolName
    ) {
        throw new Exception("The score does not match the LTI launch.");
    }

    if ('quiz' == $toolName) {
        $objExercise = new Exercise($courseId);
        $trackInfo = $objExercise->get_stat_track_exercise_info_by_exe_id($_REQUEST['lti_result_id']);
        // The attempt must belong to the current user, course, session and launched test.
        if (empty($trackInfo)
            || (int) $trackInfo['exe_user_id'] !== api_get_user_id()
            || (int) $trackInfo['c_id'] !== (int) $courseId
            || (int) $trackInfo['session_id'] !== api_get_session_id()
            || (int) $trackInfo['exe_exo_id'] !== $launchResult->getToolId()
        ) {
            throw new Exception("Quiz tracking result not found.");
        }
        $score = $trackInfo['exe_result'];
        $weight = $trackInfo['exe_weighting'];
        $timestamp = date(DATE_ISO8601);
    } else {
        $lpId = (int) $_REQUEST['lti_result_id'];
        if ($lpId !== $launchResult->getToolId()) {
            throw new Exception("The learning path does not match the LTI launch.");
        }
        $lpProgress = learnpath::getProgress(
            $lpId,
            api_get_user_id(),
            api_get_course_int_id(),
            api_get_session_id()
        );
        $score = $lpProgress;
        $weight = 100;
        $timestamp = date(DATE_ISO8601);
    }

    $grades = $launch->getAgs();
    $scoreGrade = Packback\Lti1p3\LtiGrade::new()
        ->setScoreGiven($score)
        ->setScoreMaximum($weight)
        ->setTimestamp($timestamp)
        ->setActivityProgress('Completed')
        ->setGradingProgress('FullyGraded')
        ->setUserId($launchData['sub']);
    $grades->putGrade($scoreGrade);

    $plugin = LtiProviderPlugin::create();
    $values = [];
    $values['score'] = $score;
    $values['progress'] = 0;
    $values['duration'] = 0;
    $plugin->saveResult($values, $_REQUEST['launch_id']);

    echo '{"success" : true}';
}
