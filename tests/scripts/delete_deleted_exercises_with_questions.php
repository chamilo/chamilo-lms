<?php
/* For licensing terms, see /license.txt */

/**
 * This script cleans up tests and questions that were deleted from the
 * platform interface but were left in the database for recovery purposes.
 */
exit;

require_once __DIR__ . '/../../main/inc/global.inc.php';

$sql = 'SELECT iid, c_id, title
        FROM c_quiz
        WHERE active = -1
        ORDER BY iid';

$result = Database::query($sql);
$data = Database::store_result($result);
$counter = 1;
$total = count($data);
echo 'Exercises to delete: '.$total.PHP_EOL;

foreach ($data as $row) {
    $id = $row['iid'];
    $courseId = $row['c_id'];
    //$exercise = new Exercise($courseId);
    //$exercise->read($id);

    $courseInfo = api_get_course_info_by_id($row['c_id']);

    $sql = "SELECT question_id, c_id
            FROM c_quiz_rel_question
            WHERE exercice_id = $id
            ORDER BY iid";
    $result = Database::query($sql);
    $questions = Database::store_result($result);
    $totalQuestions = count($questions);

    echo PHP_EOL.'-------';
    echo PHP_EOL.'Deleting exercise "'.$row['title'].'" -  #'.$row['iid'].PHP_EOL;
    echo '-------'.PHP_EOL.PHP_EOL;

    if (!empty($questions)) {
        $counter = 1;
        foreach ($questions as $questionData) {
            $questionId = $questionData['question_id'];
            // Check if question is used in another exercise:
            $sql = "SELECT count(iid)
                    FROM c_quiz_rel_question
                    WHERE exercice_id != $id AND question_id = $questionId";
            $result = Database::query($sql);
            $dataQuestion = Database::fetch_array($result);

            $count = $dataQuestion['count'];
            if (empty($count)) {
                $question = Question::read($questionId, $courseInfo);
                $question->delete();
                echo 'Deleting question '.$counter.'/'.$totalQuestions.' -  #'.$questionId.PHP_EOL;
            } else {
                echo 'Cannot delete question, it\'s been used by another exercise'.$counter.'/'.$totalQuestions.' -  #'.$questionId.PHP_EOL;
            }
            $counter++;
        }
    }

    $sql = "DELETE FROM c_quiz WHERE iid = $id";
    $result = Database::query($sql);

    $sql = "DELETE FROM c_item_property WHERE ref = $id AND c_id = $courseId and tool = 'quiz' ";
    $result = Database::query($sql);
}
