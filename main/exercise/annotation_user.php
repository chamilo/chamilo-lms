<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuizRelQuestion;

session_cache_limiter('none');

require_once __DIR__.'/../inc/global.inc.php';

$questionId = isset($_GET['question_id']) ? (int) $_GET['question_id'] : 0;
$exerciseId = isset($_GET['exe_id']) ? (int) $_GET['exe_id'] : 0;
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

$denyAccess = function () {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'access_denied']);
    exit;
};

$invalidRequest = function () {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'invalid_request']);
    exit;
};

if ($questionId <= 0 || $exerciseId < 0 || $courseId <= 0) {
    $invalidRequest();
}

$currentUserId = (int) api_get_user_id();
if ($currentUserId <= 0) {
    $denyAccess();
}

$courseInfo = api_get_course_info_by_id($courseId);
if (empty($courseInfo)) {
    $invalidRequest();
}

$entityManager = Database::getManager();
$questionRelationRepository = $entityManager->getRepository(CQuizRelQuestion::class);

$canReviewCourse = function ($userId, $attemptOwnerId, $attemptSessionId) use ($courseId) {
    if (api_is_platform_admin()) {
        return true;
    }

    if ((int) CourseManager::getUserInCourseStatus($userId, $courseId) === COURSEMANAGER
        || (bool) CourseManager::get_tutor_in_course_status($userId, $courseId)
    ) {
        return true;
    }

    if ($attemptSessionId > 0) {
        $sessionInfo = SessionManager::fetch($attemptSessionId);
        if (!empty($sessionInfo)) {
            if ((int) $sessionInfo['id_coach'] === $userId
                || (int) $sessionInfo['session_admin_id'] === $userId
            ) {
                return true;
            }

            $courseCoaches = array_map(
                'intval',
                SessionManager::getCoachesByCourseSession($attemptSessionId, $courseId)
            );
            if (in_array($userId, $courseCoaches, true)) {
                return true;
            }
        }
    }

    if (api_is_student_boss()
        && UserManager::userIsBossOfStudent($userId, $attemptOwnerId)
    ) {
        return true;
    }

    return api_is_drh()
        && UserManager::is_user_followed_by_drh($attemptOwnerId, $userId);
};

if ($exerciseId > 0) {
    $trackExerciseInfo = ExerciseLib::get_exercise_track_exercise_info($exerciseId);
    if (empty($trackExerciseInfo)
        || $courseId !== (int) $trackExerciseInfo['c_id']
    ) {
        $denyAccess();
    }

    $attemptOwnerId = (int) $trackExerciseInfo['exe_user_id'];
    $attemptSessionId = (int) $trackExerciseInfo['session_id'];

    if ($currentUserId !== $attemptOwnerId
        && !$canReviewCourse($currentUserId, $attemptOwnerId, $attemptSessionId)
    ) {
        $denyAccess();
    }

    $questionRelation = $questionRelationRepository->findOneBy([
        'cId' => $courseId,
        'questionId' => $questionId,
        'exerciceId' => (int) $trackExerciseInfo['exe_exo_id'],
    ]);
    if (null === $questionRelation) {
        $denyAccess();
    }
} else {
    if (!$canReviewCourse($currentUserId, 0, 0)) {
        $denyAccess();
    }

    $questionRelation = $questionRelationRepository->findOneBy([
        'cId' => $courseId,
        'questionId' => $questionId,
    ]);
    if (null === $questionRelation) {
        $denyAccess();
    }
}

$objQuestion = Question::read($questionId, $courseInfo);
if (empty($objQuestion)) {
    $invalidRequest();
}

$documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
$picturePath = $documentPath.'/images';
$pictureSize = getimagesize($picturePath.'/'.$objQuestion->getPictureFilename());
$pictureWidth = $pictureSize[0];
$pictureHeight = $pictureSize[1];

$data = [
    'use' => 'user',
    'image' => [
        'path' => $objQuestion->selectPicturePath(),
        'width' => $pictureSize[0],
        'height' => $pictureSize[1],
    ],
    'answers' => [
        'paths' => [],
        'texts' => [],
    ],
];

$questionAttempt = Event::getQuestionAttemptByExeIdAndQuestion($exerciseId, $questionId);

if (!empty($questionAttempt['answer'])) {
    $answers = explode('|', $questionAttempt['answer']);
    foreach ($answers as $answer) {
        $parts = explode(')(', $answer);
        $typeProperties = array_shift($parts);
        $properties = explode(';', $typeProperties);

        switch ($properties[0]) {
            case 'P':
                $points = [];
                foreach ($parts as $partPoint) {
                    $points[] = Geometry::decodePoint($partPoint);
                }
                $data['answers']['paths'][] = [
                    'color' => $properties[1] ?? '#FF0000',
                    'points' => $points,
                ];
                break;
            case 'T':
                $text = [
                    'text' => array_shift($parts),
                    'color' => $properties[1] ?? '#FF0000',
                    'fontSize' => $properties[2] ?? '20',
                ];

                $data['answers']['texts'][] = $text + Geometry::decodePoint($parts[0]);
                break;
        }
    }
}

header('Content-Type: application/json');

echo json_encode($data);
