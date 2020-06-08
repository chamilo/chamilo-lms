<?php
/* For licensing terms, see /license.txt */

/**
 * Sets needed course variables and then jumps to the exercises result page.
 * This intermediate page is needed because the user is not inside a course
 * when visiting the gradebook, and several course scripts rely on these
 * variables.
 * Most code here is ripped from /main/course_home/course_home.php.
 *
 * @author Bert Steppé
 */
require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();
$this_section = SECTION_COURSES;

$gradebook = Security::remove_XSS($_GET['gradebook']);
$session_id = api_get_session_id();
$cidReq = Security::remove_XSS($_GET['cidReq']);
$type = Security::remove_XSS($_GET['type']);
$doExerciseUrl = '';

// no support for hot potatoes
if ($type == LINK_HOTPOTATOES) {
    $exerciseId = $_GET['exerciseId'];
    $path = Security::remove_XSS($_GET['path']);
    $doExerciseUrl = api_get_path(WEB_CODE_PATH).'exercise/showinframes.php?'.http_build_query(
        [
            'session_id' => $session_id,
            'cidReq' => Security::remove_XSS($cidReq),
            'file' => $path,
            'cid' => api_get_course_id(),
            'uid' => api_get_user_id(),
        ]
    );
    header('Location: '.$doExerciseUrl);
    exit;
}

if (!empty($doExerciseUrl)) {
    header('Location: '.$doExerciseUrl);
    exit;
} else {
    $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'
        .http_build_query(['session_id' => $session_id, 'cidReq' => $cidReq]);
    if (isset($_GET['gradebook'])) {
        $url .= '&gradebook=view&exerciseId='.((int) $_GET['exerciseId']);

        // Check if exercise is inserted inside a LP, if that's the case
        $exerciseId = $_GET['exerciseId'];
        $exercise = new Exercise();
        $exercise->read($exerciseId);
        if (!empty($exercise->id)) {
            if ($exercise->exercise_was_added_in_lp) {
                if (!empty($exercise->lpList)) {
                    // If the exercise was added once redirect to the LP
                    $firstLp = $exercise->getLpBySession($session_id);
                    if (isset($firstLp['lp_id'])) {
                        $url = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&'
                            .http_build_query(
                                [
                                    'lp_id' => $firstLp['lp_id'],
                                    'action' => 'view',
                                    'isStudentView' => 'true',
                                ]
                            );
                    }
                }
            } else {
                $url = api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.http_build_query(
                    [
                        'session_id' => $session_id,
                        'cidReq' => $cidReq,
                        'gradebook' => $gradebook,
                        'origin' => '',
                        'learnpath_id' => '',
                        'learnpath_item_id' => '',
                        'exerciseId' => (int) $_GET['exerciseId'],
                    ]
                );
            }
        }
    }

    header('Location: '.$url);
    exit;
}
