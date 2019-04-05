<?php
/* For licensing terms, see /license.txt */

session_cache_limiter('none');

require_once __DIR__.'/../inc/global.inc.php';

$questionId = isset($_GET['question_id']) ? (int) $_GET['question_id'] : 0;
$exerciseId = isset($_GET['exe_id']) ? (int) $_GET['exe_id'] : 0;
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$courseInfo = api_get_course_info_by_id($courseId);

if (empty($courseInfo)) {
    return '';
}

$objQuestion = Question::read($questionId, $courseInfo);
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

$attemptList = Event::getAllExerciseEventByExeId($exerciseId);

if (!empty($attemptList) && isset($attemptList[$questionId])) {
    $questionAttempt = $attemptList[$questionId][0];
    if (!empty($questionAttempt['answer'])) {
        $answers = explode('|', $questionAttempt['answer']);
        foreach ($answers as $answer) {
            $parts = explode(')(', $answer);
            $type = array_shift($parts);

            switch ($type) {
                case 'P':
                    $points = [];
                    foreach ($parts as $partPoint) {
                        $points[] = Geometry::decodePoint($partPoint);
                    }
                    $data['answers']['paths'][] = $points;
                    break;
                case 'T':
                    $text = [
                        'text' => array_shift($parts),
                    ];
                    $data['answers']['texts'][] = $text + Geometry::decodePoint($parts[0]);
                    break;
            }
        }
    }
}

header('Content-Type: application/json');

echo json_encode($data);
