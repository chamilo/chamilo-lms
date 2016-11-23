<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$isAllowedToEdit = api_is_allowed_to_edit(true, true);

if (!$isAllowedToEdit) {
    api_not_allowed(true);
    exit;
}

if (!isset($_REQUEST['user'], $_REQUEST['exercise'], $_REQUEST['id'])) {
    api_not_allowed(true);
    exit;
}

$em = Database::getManager();

$trackedExercise = $em
    ->getRepository('ChamiloCoreBundle:TrackEExercises')
    ->find(intval($_REQUEST['id']));

if (
    $trackedExercise->getExeUserId() != intval($_REQUEST['user']) ||
    $trackedExercise->getExeExoId() != intval($_REQUEST['exercise'])
) {
    api_not_allowed(true);
    exit;
}

$attemps = $em->getRepository('ChamiloCoreBundle:TrackEAttempt')
    ->findBy([
        'exeId' => $trackedExercise->getExeId(),
        'userId' => $trackedExercise->getExeUserId()
    ]);

$newResult = 0;

foreach ($attemps as $attemp) {
    $questionId = $attemp->getQuestionId();

    $question = $em->find('ChamiloCourseBundle:CQuizQuestion', $questionId);

    if (!$question) {
        continue;
    }

    $answers = $em->getRepository('ChamiloCourseBundle:CQuizAnswer')->findBy([
        'questionId' => $questionId,
        'correct' => 1
    ]);

    $newMarks = 0;

    foreach ($answers as $answer) {
        if ($answer->getId() != $attemp->getAnswer()) {
            continue;
        }

        $newMarks += $answer->getPonderation();
    }

    $newResult += $newMarks;

    $attemp->setMarks($newMarks);

    $em->merge($attemp);
}

$trackedExercise->setExeResult($newResult);

$em->merge($trackedExercise);
$em->flush();
