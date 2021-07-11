<?php
/* For license terms, see /license.txt */
/**
 * Functions.
 *
 * @package chamilo.plugin.test2pdf
 */
$letters = [
    'a',
    'b',
    'c',
    'd',
    'e',
    'f',
    'g',
    'h',
    'i',
    'j',
    'k',
    'l',
    'm',
    'n',
    'o',
    'p',
    'q',
    'r',
    's',
    't',
    'u',
    'v',
    'w',
    'x',
    'y',
    'z',
];

/**
 * List exercises.
 *
 * @param int $courseId  Course ID
 * @param int $sessionId Session ID
 *
 * @throws Exception
 *
 * @return array Results (list of exercise details)
 */
function showExerciseCourse($courseId, $sessionId = 0)
{
    $tableQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $tableLpItem = Database::get_course_table(TABLE_LP_ITEM);
    $courseId = (int) $courseId;
    $sessionId = (int) $sessionId;
    $conditionSession = api_get_session_condition($sessionId, true, true, 'a.session_id');
    $sql = "SELECT a.*
            FROM $tableQuiz a
            LEFT JOIN $tableLpItem b ON a.iid = b.path AND a.c_id = b.c_id
            WHERE a.c_id = $courseId
            AND (a.active = 1 OR (item_type = 'quiz' AND b.c_id = $courseId))
            $conditionSession
            ORDER BY a.title ASC;";
    $res = Database::query($sql);
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

/**
 * List quiz details.
 *
 * @throws Exception
 *
 * @return array Results (list of quiz details)
 */
function getInfoQuiz($courseId, $id)
{
    $courseId = (int) $courseId;
    $id = (int) $id;
    $tableQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $sql = "SELECT * FROM $tableQuiz WHERE iid = $id";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    return $row;
}

/**
 * List question_id.
 *
 * @throws Exception
 *
 * @return array Results (list question ID)
 */
function getQuestionsFromCourse($courseId, $quizId, $sessionId = 0)
{
    $courseId = (int) $courseId;
    $quizId = (int) $quizId;
    $sessionId = (int) $sessionId;

    $tableQuizQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $tableQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $conditionSession = api_get_session_condition($sessionId, true, true, 'q.session_id');

    $sql = "SELECT a.question_id AS question_id
            FROM $tableQuizQuestion a
            INNER JOIN $tableQuestion b ON a.question_id = b.iid
            INNER JOIN $tableQuiz q ON q.iid = a.exercice_id
            WHERE a.c_id = $courseId AND a.exercice_id = $quizId
            AND (b.type IN (1, 2, 9, 10, 11, 12, 14))
            $conditionSession
            ORDER BY question_order ASC;";
    $res = Database::query($sql);
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row['question_id'];
    }

    return $aux;
}

/**
 * List question details.
 *
 * @throws Exception
 *
 * @return array Results (list of question details)
 */
function getInfoQuestion($courseId, $id)
{
    $courseId = (int) $courseId;
    $id = (int) $id;
    $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $sql = "SELECT * FROM $tableQuestion
            WHERE
                iid = $id
            AND (type IN (1, 2, 9, 10, 11, 12, 14))";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    return $row;
}

/**
 * List answer details.
 *
 * @throws Exception
 *
 * @return array Results (list of answer by question_id)
 */
function getAnswers($courseId, $id)
{
    $courseId = (int) $courseId;
    $id = (int) $id;
    $tableQuizAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
    $sql = "SELECT * FROM $tableQuizAnswer
    	    WHERE question_id = $id
    	    ORDER BY position ASC;";
    $res = Database::query($sql);
    $aux = [];
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }

    return $aux;
}

/**
 * Remove all html tag.
 *
 * @param string $string The string to be stripped of HTML
 *
 * @return string clean of html tag
 */
function removeHtml($string)
{
    $txt = str_replace("<html>", "", $string);
    $txt = str_replace("<head>", "", $txt);
    $txt = str_replace("<title>", "", $txt);
    $txt = str_replace("</title>", "", $txt);
    $txt = str_replace("</head>", "", $txt);
    $txt = str_replace("<body>", "", $txt);
    $txt = str_replace("</body>", "", $txt);
    $txt = str_replace("</html>", "", $txt);
    $txt = strip_tags($txt);
    $txt = str_replace(chr(13).chr(10), "", $txt);
    $txt = str_replace("&nbsp;", " ", $txt);
    $txt = str_replace("&Aacute;", "Á", $txt);
    $txt = str_replace("&aacute;", "á", $txt);
    $txt = str_replace("&Eacute;", "É", $txt);
    $txt = str_replace("&eacute;", "é", $txt);
    $txt = str_replace("&Iacute;", "Í", $txt);
    $txt = str_replace("&iacute;", "í", $txt);
    $txt = str_replace("&Oacute;", "Ó", $txt);
    $txt = str_replace("&oacute;", "ó", $txt);
    $txt = str_replace("&Uacute;", "Ú", $txt);
    $txt = str_replace("&uacute;", "ú", $txt);
    $txt = str_replace("&Ntilde;", "Ñ", $txt);
    $txt = str_replace("&ntilde;", "ñ", $txt);
    $txt = str_replace("&agrave;", "à", $txt);
    $txt = str_replace("&Agrave;", "À", $txt);
    $txt = str_replace("&iexcl;", "¡", $txt);
    $txt = str_replace("&middot;", "·", $txt);
    $txt = str_replace("&Ccedil;", "Ç", $txt);
    $txt = str_replace("&ccedil;", "ç", $txt);
    $txt = str_replace("&quot;", '"', $txt);
    $txt = str_replace("&ordf;", 'ª', $txt);
    $txt = str_replace("&ordm;", 'º', $txt);
    $txt = str_replace("&amp;", '&', $txt);
    $txt = str_replace("&bull;", '•', $txt);
    $txt = str_replace("&iquest;", '¿', $txt);
    $txt = str_replace("&euro;", 'EUR', $txt);
    $txt = str_replace("&uuml;", 'ü', $txt);
    $txt = str_replace("&Uuml;", 'Ü', $txt);
    $txt = str_replace("&uml;", '¨', $txt);

    return $txt;
}

/**
 * Remove all html tag.
 *
 * @param string $string The string to be stripped of accents
 *
 * @return string clean of html tag
 */
function removeQuotes($string)
{
    $txt = str_replace("&nbsp;", " ", $string);
    $txt = str_replace("&Aacute;", "Á", $txt);
    $txt = str_replace("&aacute;", "á", $txt);
    $txt = str_replace("&Eacute;", "É", $txt);
    $txt = str_replace("&eacute;", "é", $txt);
    $txt = str_replace("&Iacute;", "Í", $txt);
    $txt = str_replace("&iacute;", "í", $txt);
    $txt = str_replace("&Oacute;", "Ó", $txt);
    $txt = str_replace("&oacute;", "ó", $txt);
    $txt = str_replace("&Uacute;", "Ú", $txt);
    $txt = str_replace("&uacute;", "ú", $txt);
    $txt = str_replace("&Ntilde;", "Ñ", $txt);
    $txt = str_replace("&ntilde;", "ñ", $txt);
    $txt = str_replace("&quot;", '"', $txt);
    $txt = str_replace("&ordf;", 'ª', $txt);
    $txt = str_replace("&ordm;", 'º', $txt);
    $txt = str_replace("&amp;", '&', $txt);
    $txt = str_replace("&bull;", '•', $txt);
    $txt = str_replace("&iquest; &", '¿', $txt);
    $txt = str_replace("&agrave;", "à", $txt);
    $txt = str_replace("&Agrave;", "À", $txt);
    $txt = str_replace("&iexcl;", "¡", $txt);
    $txt = str_replace("&middot;", "·", $txt);
    $txt = str_replace("&Ccedil;", "Ç", $txt);
    $txt = str_replace("&ccedil;", "ç", $txt);
    $txt = str_replace("&euro;", 'EUR', $txt);
    $txt = str_replace("&uuml;", 'ü', $txt);
    $txt = str_replace("&Uuml;", 'Ü', $txt);
    $txt = str_replace("uml;", '¨', $txt);

    return $txt;
}
