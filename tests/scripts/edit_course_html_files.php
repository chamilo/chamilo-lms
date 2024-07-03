<?php
/* For licensing terms, see /license.txt */

/**
 * Goes through all HTML files of the courses directory and replaces
 * the first string by the second string.
 * This is useful when a portal was installed under one URL and then
 * changed URL (or port), to ensure documents are not pointing to the
 * previous URL.
 * If enabled ($processQuiz = true), it will also process c_quiz,
 * c_quiz_question and c_quiz_answer.
 * This script is designed to be run from the browser, so maybe you
 * need to move it to an executable folder and change the first require.
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
exit;
require __DIR__.'/../../main/inc/global.inc.php';
api_protect_admin_script();

// Replace quiz description, question description and answers as well ?
$processQuiz = false;

// Search string (do not use #)
$search = 'http://www.domain1.com/';
$replace = 'https://domain2.com/';

$dir = api_get_path(SYS_COURSE_PATH);
$courses = scandir($dir);
$startTime = time();
$i = 0;
foreach ($courses as $courseDir) {
    if (substr($courseDir, 0, 1) === '.') {
        continue;
    }
    exec('find '.$dir.$courseDir.'/document/ -type f -name "*.html" -exec sed -i '."'s#$search#$replace#g' {} +");
    //print('find '.$dir.$courseDir.'/document/ -type f -name "*.html" -exec sed -i '."'s/'.$search.'/'.$replace.'/g' {} +<br />");
    $i++;
}
echo "Replaced '$search' by '$replace' in document/ folders of $i courses.<br />".PHP_EOL;

if ($processQuiz) {
    echo "Processing exercises now...<br />".PHP_EOL;
    $quizCount = 0;
    $questionCount = 0;
    $answerCount = 0;
    $tableQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $sql = "SELECT iid, title, description FROM $tableQuiz WHERE title LIKE '%$search%' OR description LIKE '%$search%' ORDER BY iid";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        while ($row = Database::fetch_assoc($res)) {
            $title = preg_replace('#'.$search.'#',$replace, $row['title']);
            $description = preg_replace('#'.$search.'#',$replace, $row['description']);
            $sqlReplace = "UPDATE $tableQuiz SET title = '$title', description = '$description' WHERE iid = ".$row['iid'];
            try {
                $resReplace = Database::query($sqlReplace);
            } catch (Exception $e) {
                echo "Error executing $sqlReplace <br />".PHP_EOL;
                Database::handleError($e);
            }
            $quizCount++;
        }
    }
    $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $sql = "SELECT iid, question, description FROM $tableQuestion WHERE question LIKE '%$search%' OR description LIKE '%$search%' ORDER BY iid";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        while ($row = Database::fetch_assoc($res)) {
            $question = preg_replace('#'.$search.'#',$replace, $row['question']);
            $description = preg_replace('#'.$search.'#',$replace, $row['description']);
            $sqlReplace = "UPDATE $tableQuestion SET question = '$question', description = '$description' WHERE iid = ".$row['iid'];
            try {
                $resReplace = Database::query($sqlReplace);
            } catch (Exception $e) {
                echo "Error executing $sqlReplace <br />".PHP_EOL;
                Database::handleError($e);
            }
            $questionCount++;
        }
    }
    $tableAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
    $sql = "SELECT iid, answer, comment FROM $tableAnswer WHERE answer LIKE '%$search%' OR comment LIKE '%$search%' ORDER BY iid";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        while ($row = Database::fetch_assoc($res)) {
            $answer = preg_replace('#'.$search.'#',$replace, $row['answer']);
            $comment = preg_replace('#'.$search.'#',$replace, $row['comment']);
            $sqlReplace = "UPDATE $tableAnswer SET answer = '$answer', comment = '$comment' WHERE iid = ".$row['iid'];
            try {
                $resReplace = Database::query($sqlReplace);
            } catch (Exception $e) {
                echo "Error executing $sqlReplace <br />".PHP_EOL;
                Database::handleError($e);
            }
            $answerCount++;
        }
    }
    echo "Updated $quizCount quizzes, $questionCount questions and $answerCount answers.<br />".PHP_EOL;
}
$totalTime = time() - $startTime;
echo "Done (took $totalTime seconds)<br />".PHP_EOL;
