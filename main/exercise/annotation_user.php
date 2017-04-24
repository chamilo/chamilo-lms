<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

session_cache_limiter("none");

require_once __DIR__.'/../inc/global.inc.php';

$questionId = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;
$exerciseId = isset($_GET['exe_id']) ? intval($_GET['exe_id']) : 0;

$objQuestion = Question::read($questionId);
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$picturePath = $documentPath.'/images';
$pictureName = $objQuestion->selectPicture();
$pictureSize = getimagesize($picturePath.'/'.$objQuestion->selectPicture());
$pictureWidth = $pictureSize[0];
$pictureHeight = $pictureSize[1];

$data = [
    'use' => 'user',
    'image' => [
        'path' => $objQuestion->selectPicturePath(),
        'width' => $pictureSize[0],
        'height' => $pictureSize[1]
    ],
    'answers' => [
        'paths' => [],
        'texts' => []
    ]
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
                        'text' => array_shift($parts)
                    ];
                    $data['answers']['texts'][] = $text + Geometry::decodePoint($parts[0]);
                    break;
            }
        }
    }
}

header('Content-Type: application/json');

echo json_encode($data);
