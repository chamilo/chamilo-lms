<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AttemptFeedback;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require_once __DIR__.'/../global.inc.php';
require_once api_get_path(SYS_CODE_PATH).'exercise/oral_expression.class.php';

api_block_anonymous_users();

$httpRequest     = Container::getRequest();
$type            = (int) $httpRequest->get('type');
$trackExerciseId = (int) $httpRequest->get('t_exercise');
$questionId      = (int) $httpRequest->get('question');
$userId          = api_get_user_id();

if (empty($_FILES) || empty($_FILES['audio_blob'])) {
    Display::addFlash(
        Display::return_message(
            get_lang('Upload failed, please check maximum file size limits and folder rights.'),
            'error'
        )
    );
    exit;
}

$fileInfo     = $_FILES['audio_blob'];
$originalName = $fileInfo['name'] ?? 'audio.webm';
$mimeType     = $fileInfo['type'] ?? 'audio/webm';
$errorCode    = $fileInfo['error'] ?? UPLOAD_ERR_OK;

if (UPLOAD_ERR_OK !== $errorCode) {
    Display::addFlash(
        Display::return_message(
            get_lang('Upload failed, please check maximum file size limits and folder rights.'),
            'error'
        )
    );
    exit;
}

$em = Container::getEntityManager();

/** @var ObjectRepository<ResourceType> $resourceTypeRepo */
$resourceTypeRepo = $em->getRepository(ResourceType::class);

/**
 * Create a ResourceNode + ResourceFile from an uploaded file and return the node.
 *
 * @param string               $title
 * @param ResourceType         $resourceType
 * @param array<string, mixed> $fileInfo
 * @param int|null             $userId
 */
$createResourceNodeFromUploadedFile = static function (
    string $title,
    ResourceType $resourceType,
    array $fileInfo,
    ?int $userId = null
) use ($em): ResourceNode {
    $tmpName      = $fileInfo['tmp_name'];
    $originalName = $fileInfo['name'] ?? 'file.bin';
    $mimeType     = $fileInfo['type'] ?? 'application/octet-stream';
    $errorCode    = $fileInfo['error'] ?? UPLOAD_ERR_OK;

    $node = new ResourceNode();
    $node->setTitle($title !== '' ? $title : $originalName);
    $node->setResourceType($resourceType);

    if (null !== $userId && method_exists($node, 'setCreator')) {
        $user = $em->getRepository(User::class)->find($userId);
        if (null !== $user) {
            $node->setCreator($user);
        }
    }

    $em->persist($node);

    // Force a distinct "original_name" for attempt vs feedback.
    // WebRTC uploads often reuse the same client filename for both recordings, which makes
    // attempt and feedback indistinguishable in DB. We build a stable filename from $title.
    $ext = (string) pathinfo((string) $originalName, PATHINFO_EXTENSION);
    $ext = $ext !== '' ? strtolower($ext) : 'webm';

    $safeBaseName = $title !== '' ? $title : (string) pathinfo((string) $originalName, PATHINFO_FILENAME);
    $safeBaseName = api_replace_dangerous_char($safeBaseName);
    $safeBaseName = disable_dangerous_file($safeBaseName);

    $forcedOriginalName = $safeBaseName.'.'.$ext;

    // "true" => test mode, do not enforce HTTP upload checks (consistent with current behavior).
    $uploadedFile = new UploadedFile(
        $tmpName,
        $forcedOriginalName,
        $mimeType,
        $errorCode,
        true
    );

    $resourceFile = new ResourceFile();
    $resourceFile->setResourceNode($node);
    $resourceFile->setFile($uploadedFile);

    $em->persist($resourceFile);
    $em->flush();

    return $node;
};

switch ($type) {
    case OralExpression::RECORDING_TYPE_ATTEMPT:
        // Student oral expression attempt → ResourceType "attempt_file".
        /** @var ResourceType|null $resourceType */
        $resourceType = $resourceTypeRepo->findOneBy(['title' => 'attempt_file']);
        if (null === $resourceType) {
            throw new RuntimeException('ResourceType "attempt_file" not found. Audio recording cannot be stored.');
        }

        $title = "oral_expression_attempt_q{$questionId}_u{$userId}";
        $node  = $createResourceNodeFromUploadedFile($title, $resourceType, $fileInfo, $userId);

        // Keep the session key name for backward compatibility.
        ChamiloSession::write(
            'oral_expression_asset_'.$questionId,
            (string) $node->getId()
        );

        // Store the related exe_id to prevent attaching stale recordings to a different attempt.
        // This also allows re-attaching after the attempt row is deleted/recreated by AJAX saves.
        ChamiloSession::write(
            'oral_expression_asset_exe_id_'.$questionId,
            (string) $trackExerciseId
        );

        break;

    case OralExpression::RECORDING_TYPE_FEEDBACK:
        // Close the session as we don't need it any further
        session_write_close();
        // Teacher feedback → ResourceType "attempt_feedback".
        /** @var ResourceType|null $resourceType */
        $resourceType = $resourceTypeRepo->findOneBy(['title' => 'attempt_feedback']);
        if (null === $resourceType) {
            throw new RuntimeException('ResourceType "attempt_feedback" not found. Audio feedback cannot be stored.');
        }

        $title = "oral_feedback_q{$questionId}_u{$userId}";
        $node  = $createResourceNodeFromUploadedFile($title, $resourceType, $fileInfo, $userId);

        /** @var TrackEExercise|null $exerciseAttempt */
        $exerciseAttempt = Container::getTrackEExerciseRepository()->find($trackExerciseId);
        if (null === $exerciseAttempt) {
            break;
        }

        $attempt = $exerciseAttempt->getAttemptByQuestionId($questionId);
        if (null === $attempt) {
            break;
        }

        $attemptFeedback = new AttemptFeedback();
        $attemptFeedback->setResourceNode($node);
        $attempt->addAttemptFeedback($attemptFeedback);

        $em->persist($attemptFeedback);
        $em->flush();

        break;

    default:
        throw new RuntimeException('Unexpected audio recording type.');
}
