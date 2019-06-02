<?php
/* For licensing terms, see /license.txt */

exit;

require_once __DIR__ . '/../../main/inc/global.inc.php';

$sql = 'SELECT iid, c_id, question 
        FROM c_quiz_question 
        WHERE iid not in (SELECT question_id from c_quiz_rel_question) 
        ORDER BY iid';

$result = Database::query($sql);
$data = Database::store_result($result);
$counter = 1;
$totalQuestions = count($data);
echo 'Questions to delete: '.$totalQuestions.PHP_EOL;
foreach ($data as $row) {
    $courseInfo = api_get_course_info_by_id($row['c_id']);
    $question = Question::read($row['iid'], $courseInfo);
    if (empty($question->exerciseList)) {
        $question->delete(1);
    }
    echo 'Deleting question '.$counter.'/'.$totalQuestions.' -  #'.$row['iid'].PHP_EOL;
    $counter++;
}