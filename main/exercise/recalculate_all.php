<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExercises;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

if (!isset($_REQUEST['exercise'])) {
    api_not_allowed(true);
}

$exerciseId = (int) $_REQUEST['exercise'];

$is_allowedToEdit = api_is_allowed_to_edit(null, true) ||
    api_is_drh() ||
    api_is_student_boss() ||
    api_is_session_admin();

if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

$result = ExerciseLib::get_exam_results_data(
    0,
    0,
    null,
    'asc',
    $exerciseId,
    '',
    false,
    null,
    false,
    false,
    [],
    false,
    false,
    true
);

foreach ($result as $track) {
    /** @var TrackEExercises $trackedExercise */
    $trackedExercise = ExerciseLib::recalculateResult(
        $track['id'],
        $track['user_id'],
        $exerciseId
    );

    if (!$trackedExercise) {
        Display::addFlash(
            Display::return_message(get_lang('BadFormData').'<br>ID: '.$track['id'], 'warning', false)
        );
    }
}

$url = api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'
    .api_get_cidreq()
    ."&exerciseId=$exerciseId";

header("Location: $url");
