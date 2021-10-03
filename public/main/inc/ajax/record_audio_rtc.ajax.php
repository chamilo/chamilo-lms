<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\AttemptFeedback;
use Chamilo\CoreBundle\Entity\TrackExercise;
use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../global.inc.php';

api_block_anonymous_users();

$httpRequest = Request::createFromGlobals();

/** @var UploadedFile $audioBlob */
$audioBlob = $httpRequest->files->get('audio_blob');
$type = $httpRequest->get('type');
$trackExerciseId = (int) $httpRequest->get('t_exercise');
$questionId = (int) $httpRequest->get('question');

if (empty($audioBlob)) {
    Display::addFlash(
        Display::return_message(
            get_lang('Upload failed, please check maximum file size limits and folder rights.'),
            'error'
        )
    );
    exit;
}

$em = Container::getEntityManager();

switch ($type) {
    case Asset::EXERCISE_ATTEMPT:
        $asset = (new Asset())
            ->setCategory(Asset::EXERCISE_ATTEMPT)
            ->setTitle(time().uniqid('tea'))
            ->setFile($audioBlob)
        ;

        $em->persist($asset);
        $em->flush();

        ChamiloSession::write("oral_expression_asset_$questionId", $asset->getId()->toRfc4122());
        break;

    case Asset::EXERCISE_FEEDBACK:
        $asset = (new Asset())
            ->setCategory(Asset::EXERCISE_FEEDBACK)
            ->setTitle(time().uniqid('tea'))
            ->setFile($audioBlob)
        ;

        $em->persist($asset);
        $em->flush();

        $attemptFeedback = (new AttemptFeedback())
            ->setAsset($asset);

        /** @var TrackExercise $exeAttempt */
        $exeAttempt = Container::getTrackExerciseRepository()->find($trackExerciseId);
        $attempt = $exeAttempt->getAttemptByQuestionId($questionId);

        if (null === $attempt) {
            exit;
        }

        $attempt->addAttemptFeedback($attemptFeedback);

        $em->persist($attemptFeedback);
        $em->flush();
        break;
    default:
        throw new \Exception('Unexpected value');
}
