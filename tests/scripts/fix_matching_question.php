<?php

/* For licensing terms, see /license.txt */

exit;

error_reporting(0);

require_once __DIR__.'/../../main/inc/global.inc.php';

$sql = 'SELECT * FROM course';
$result = Database::query($sql);

while ($row = Database::fetch_array($result, 'ASSOC')) {
    $courseId = $row['id'];
    $courseInfo = api_get_course_info_by_id($courseId);

    echo 'Course: '.$courseId.' - '.$courseInfo['title'].PHP_EOL.PHP_EOL;

    $exerciseList = ExerciseLib::get_all_exercises_for_course_id(
        $courseInfo,
        0,
        $courseId,
        false
    );

    foreach ($exerciseList as $exerciseItem) {
        $exercise = new Exercise($courseId);
        $exercise->read($exerciseItem['iid']);

        echo '    iid:'.$exercise->iid.' id:'.$exercise->id.'- '.$exercise->title.PHP_EOL;
        $questionList = $exercise->getQuestionList();
        foreach ($questionList as $questionId) {
            $sql = "SELECT * FROM c_quiz_question WHERE type = 4 AND id = $questionId ";
            $resultQuestion = Database::query($sql);
            if (Database::num_rows($resultQuestion) == 0) {
                echo '     Nothing'.PHP_EOL;
                continue;
            }

            while ($row = Database::fetch_array($resultQuestion, 'ASSOC')) {
                $iid = $row['iid'];
                $id = $row['id'];
                $courseId = $row['c_id'];

                $sql = "SELECT * FROM c_quiz_answer WHERE c_id = $courseId AND question_id = $id AND correct = 0";
                $resultAnswer = Database::query($sql);
                $options = [];
                while ($answer = Database::fetch_array($resultAnswer, 'ASSOC')) {
                    $options[$answer['id_auto']] = $answer;
                }

                $sql = "SELECT * FROM c_quiz_answer WHERE c_id = $courseId AND question_id = $id AND correct <> 0";
                $resultAnswer = Database::query($sql);
                $correct = [];
                while ($answer = Database::fetch_array($resultAnswer, 'ASSOC')) {
                    $correct[$answer['id_auto']] = $answer['correct'];
                }

                $fix = '';
                foreach ($correct as $correctId => $correctValue) {
                    if (!in_array($correctValue, array_keys($options))) {
                        $fix.= "        Fix $correctId".PHP_EOL;
                        $fix.= "        Fix $sql".PHP_EOL;
                    }
                }

                if (!empty($fix)){
                    echo "    Question iid: $iid, id: $id".PHP_EOL;
                    echo $fix;
                }
            }
        }
    }
}

