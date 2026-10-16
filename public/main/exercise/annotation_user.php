<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;

session_cache_limiter('none');

require_once __DIR__.'/../inc/global.inc.php';

$questionId = (int) ($_GET['question_id'] ?? 0);
$exeId      = (int) ($_GET['exe_id'] ?? 0);
$courseId   = (int) ($_GET['course_id'] ?? 0);

$denyAccess = static function (): void {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'access_denied']);
    exit;
};

$invalidRequest = static function (): void {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_request']);
    exit;
};

if ($questionId <= 0 || $exeId < 0 || $courseId <= 0) {
    $invalidRequest();
}

$currentUser = api_get_user_entity();
if (null === $currentUser) {
    $denyAccess();
}

$questionRepo = Container::getQuestionRepository();
/** @var CQuizQuestion|null $question */
$question = $questionRepo->find($questionId);
if (null === $question) {
    $invalidRequest();
}

$entityManager = Database::getManager();
$authorizationChecker = Container::getAuthorizationChecker();

if ($exeId > 0) {
    $trackExercise = Container::getTrackEExerciseRepository()->find($exeId);
    if (null === $trackExercise
        || $courseId !== (int) $trackExercise->getCourse()->getId()
        || !$authorizationChecker->isGranted('VIEW', $trackExercise)
    ) {
        $denyAccess();
    }

    $quiz = $trackExercise->getQuiz();
    if (null === $quiz) {
        $denyAccess();
    }

    $questionRelation = $entityManager->getRepository(CQuizRelQuestion::class)->findOneBy([
        'quiz' => $quiz,
        'question' => $question,
    ]);
    if (null === $questionRelation) {
        $denyAccess();
    }
} else {
    $isAuthorizedPreview = $authorizationChecker->isGranted('ROLE_ADMIN');
    $questionBelongsToCourse = false;

    foreach ($question->getRelQuizzes() as $questionRelation) {
        $quiz = $questionRelation->getQuiz();
        $resourceLink = $quiz->getFirstResourceLink();
        $course = $resourceLink?->getCourse();

        if (null === $course || $courseId !== (int) $course->getId()) {
            continue;
        }

        $questionBelongsToCourse = true;
        if ($isAuthorizedPreview || $course->hasUserAsTeacher($currentUser)) {
            $isAuthorizedPreview = true;
            break;
        }

        $session = $resourceLink?->getSession();
        if (null !== $session
            && ($session->hasUserAsSessionAdmin($currentUser)
                || $session->hasUserAsGeneralCoach($currentUser)
                || $session->hasCourseCoachInCourse($currentUser, $course))
        ) {
            $isAuthorizedPreview = true;
            break;
        }
    }

    if (!$questionBelongsToCourse || !$isAuthorizedPreview) {
        $denyAccess();
    }
}

$courseInfo = api_get_course_info_by_id($courseId);
if (empty($courseInfo)) {
    $invalidRequest();
}

$webBase = rtrim(api_get_path(WEB_PATH), '/');
$imageUrl = '';
$pictureWidth  = 0;
$pictureHeight = 0;

try {
    $resourceFile = $question?->getResourceNode()?->getResourceFiles()?->first() ?: null;

    if ($resourceFile && method_exists($resourceFile, 'getAsset') && $resourceFile->getAsset()) {
        $assetRepo = Container::getAssetRepository();
        $imageUrl = $webBase . $assetRepo->getAssetUrl($resourceFile->getAsset());
        $pictureWidth  = (int) ($resourceFile->getWidth()  ?? 0);
        $pictureHeight = (int) ($resourceFile->getHeight() ?? 0);
    } else {
        $legacy = (string) $questionRepo->getHotSpotImageUrl($question);
        if ($legacy) {
            $imageUrl = str_starts_with($legacy, 'http')
                ? $legacy
                : $webBase . '/' . ltrim($legacy, '/');
        }
    }
} catch (\Throwable $e) {
}

$data = [
    'use' => 'user',
    'image' => [
        'path'   => $imageUrl,
        'width'  => $pictureWidth,
        'height' => $pictureHeight,
    ],
    'answers' => [
        'paths' => [],
        'texts' => [],
    ],
];

$attemptList = Event::getAllExerciseEventByExeId($exeId);
if (!empty($attemptList) && isset($attemptList[$questionId][0]['answer'])) {
    $raw = (string) $attemptList[$questionId][0]['answer'];
    $answers = explode('|', $raw);
    foreach ($answers as $answer) {
        $parts = explode(')(', $answer);
        $type  = array_shift($parts);

        switch ($type) {
            case 'P':
                $points = [];
                foreach ($parts as $partPoint) {
                    $points[] = Geometry::decodePoint($partPoint);
                }
                $data['answers']['paths'][] = $points;
                break;

            case 'T':
                $text = ['text' => array_shift($parts)];
                $data['answers']['texts'][] = $text + Geometry::decodePoint($parts[0]);
                break;
        }
    }
}

header('Content-Type: application/json');

echo json_encode($data);
