<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

$table = Database::get_course_table(TABLE_QUIZ_ANSWER);

$execute = isset($_GET['execute']) ? $_GET['execute'] : null;
$courseId = isset($_GET['c_id']) ? $_GET['c_id'] : null;
$questionId = isset($_GET['question_id']) ? $_GET['question_id'] : null;

$sql2 = "SELECT * FROM $table WHERE answer LIKE '%[%]%'";

if (!empty($courseId)) {
    $courseId = intval($courseId);
    $sql2 .= " AND c_id = $courseId";
    if (!empty($questionId)) {
        $questionId = intval($questionId);
        $sql2 .= " AND question_id = $questionId";
    }
}

var_dump($sql2);

$res2 = Database::query($sql2);

while ($row = Database::fetch_array($res2)) {
    $id = $row['iid'];
    $courseId = $row['c_id'];
    $idAuto = $row['id_auto'];
    $answerOriginal = $row['answer'];

    $answer = $row['answer'];

    $answer = str_replace('â', '&acirc;', $answer);
    $answer = str_replace('à', '&agrave;', $answer);
    $answer = str_replace('é', '&eacute;', $answer);
    $answer = str_replace('ê', '&ecirc;', $answer);
    $answer = str_replace('è', '&egrave;', $answer);
    $answer = str_replace('í', '&iacute;', $answer);
    $answer = str_replace('ì', '&igrave;', $answer);
    $answer = str_replace('ó', '&oacute;', $answer);
    $answer = str_replace('ò', '&ograve;', $answer);
    $answer = str_replace('ù', '&ugrave;', $answer);
    $answer = str_replace('ú', '&uacute', $answer);
    $answer = str_replace('ç', '&ccedil;', $answer);
    $answer = str_replace('À', '&Agrave;', $answer);
    $answer = str_replace('Ç', '&Ccedil;', $answer);

    $answerFixedNotEscape = $answer;

    $answer = Database::escape_string($answer);

    $sql4 = "UPDATE c_quiz_answer SET
             answer = '$answer'
             WHERE id = $id AND c_id = $courseId AND id_auto  = $idAuto ";

    if ($answerOriginal != $answerFixedNotEscape) {
        if (!empty($execute) && $execute == 1) {
            Database::query($sql4);
            echo '<pre>';
            var_dump($sql4);
            echo '</pre>';
            var_dump('executed');
        } else {
            echo "to be executed";
            echo '<pre>';
            var_dump($sql4);
            echo 'Original:<br />';
            echo $answerOriginal;
            echo 'Fixed:<br />';
            echo $answerFixedNotEscape;
            echo '</pre>';
            echo '----------<br />';
        }
    }
}
