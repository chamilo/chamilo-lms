<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackExercise;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

if (!isset($_REQUEST['user'], $_REQUEST['exercise'], $_REQUEST['id'])) {
    exit;
}

$isAllowedToEdit = api_is_allowed_to_edit(true, true);

if (!$isAllowedToEdit) {
    exit;
}

$studentId = (int) $_REQUEST['user'];
$exerciseId = (int) $_REQUEST['exercise'];
$exeId = (int) $_REQUEST['id'];

/** @var TrackEExercise $trackedExercise */
$trackedExercise = ExerciseLib::recalculateResult(
    $_REQUEST['id'],
    $_REQUEST['user'],
    $_REQUEST['exercise']
);

$totalScore = $trackedExercise->getScore();
$totalWeight = $trackedExercise->getMaxScore();

echo $totalScore.'/'.$totalWeight;
