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

$type = $httpRequest->get('type');
$trackExerciseId = (int) $httpRequest->get('t_exercise');
$questionId = (int) $httpRequest->get('question');
$userId = api_get_user_id();

if (empty($_FILES) || empty($_FILES['audio_blob'])) {
    Display::addFlash(
        Display::return_message(
            get_lang('Upload failed, please check maximum file size limits and folder rights.'),
            'error'
        )
    );
    exit;
}

$em = Container::getEntityManager();
$assetRepo = Container::getAssetRepository();

switch ($type) {
    case Asset::EXERCISE_ATTEMPT:
        $asset = (new Asset())
            ->setCategory(Asset::EXERCISE_ATTEMPT)
            ->setTitle($_FILES['audio_blob']['name'])
        ;

        $asset = $assetRepo->createFromRequest($asset, $_FILES['audio_blob']);

        ChamiloSession::write("oral_expression_asset_$questionId", $asset->getId()->toRfc4122());
        break;

    case Asset::EXERCISE_FEEDBACK:
        $asset = (new Asset())
            ->setCategory(Asset::EXERCISE_FEEDBACK)
            ->setTitle("feedback_$questionId")
        ;

        $asset = $assetRepo->createFromRequest($asset, $_FILES['audio_blob']);

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
