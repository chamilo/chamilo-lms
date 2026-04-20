<?php

/* For license terms, see /license.txt */

/**
 * Return the answer letters used in multiple-choice exports.
 */
function test2pdf_get_letters(): array
{
    return [
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
}

/**
 * Build the session condition for resource_link in Chamilo 2.
 *
 * In session context, include both:
 * - resources linked specifically to the current session
 * - resources linked only to the base course
 *
 * In non-session context, include only base-course resources.
 */
function test2pdf_get_resource_link_session_condition(int $sessionId, string $alias = 'rl'): string
{
    if ($sessionId > 0) {
        return " AND ($alias.session_id = $sessionId OR $alias.session_id IS NULL)";
    }

    return " AND $alias.session_id IS NULL";
}

/**
 * List exercises available in the current course/session context.
 *
 * @param int $courseId  Course ID
 * @param int $sessionId Session ID
 *
 * @return array
 */
function showExerciseCourse($courseId, $sessionId = 0)
{
    $courseId = (int) $courseId;
    $sessionId = (int) $sessionId;

    $tableQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $tableResourceLink = Database::get_main_table('resource_link');

    $sessionCondition = test2pdf_get_resource_link_session_condition($sessionId, 'rl');

    $sql = "SELECT DISTINCT q.*
            FROM $tableQuiz q
            INNER JOIN $tableResourceLink rl
                ON rl.resource_node_id = q.resource_node_id
            WHERE
                rl.c_id = $courseId
                AND rl.deleted_at IS NULL
                $sessionCondition
            ORDER BY q.title ASC";

    $res = Database::query($sql);
    $items = [];

    while ($row = Database::fetch_assoc($res)) {
        $items[] = $row;
    }

    return $items;
}

/**
 * Get quiz information in the current course/session context.
 *
 * @param int $courseId  Course ID
 * @param int $id        Quiz ID
 * @param int $sessionId Session ID
 *
 * @return array|false
 */
function getInfoQuiz($courseId, $id, $sessionId = 0)
{
    $courseId = (int) $courseId;
    $id = (int) $id;
    $sessionId = (int) $sessionId;

    $tableQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
    $tableResourceLink = Database::get_main_table('resource_link');

    $sessionCondition = test2pdf_get_resource_link_session_condition($sessionId, 'rl');

    $sql = "SELECT DISTINCT q.*
            FROM $tableQuiz q
            INNER JOIN $tableResourceLink rl
                ON rl.resource_node_id = q.resource_node_id
            WHERE
                q.iid = $id
                AND rl.c_id = $courseId
                AND rl.deleted_at IS NULL
                $sessionCondition
            LIMIT 1";

    $res = Database::query($sql);

    return Database::fetch_assoc($res);
}

/**
 * List question IDs for a quiz.
 *
 * @param int $courseId
 * @param int $quizId
 * @param int $sessionId
 *
 * @return array
 */
function getQuestionsFromCourse($courseId, $quizId, $sessionId = 0)
{
    $quizId = (int) $quizId;

    $tableQuizQuestion = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
    $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);

    $sql = "SELECT qr.question_id AS question_id
            FROM $tableQuizQuestion qr
            INNER JOIN $tableQuestion q
                ON q.iid = qr.question_id
            WHERE
                qr.quiz_id = $quizId
                AND q.type IN (1, 2, 3, 9, 10, 11, 12, 14)
            ORDER BY qr.question_order ASC, qr.iid ASC";

    $res = Database::query($sql);
    $items = [];

    while ($row = Database::fetch_assoc($res)) {
        $items[] = $row['question_id'];
    }

    return $items;
}

/**
 * Get question information.
 *
 * @param int $courseId
 * @param int $id
 *
 * @return array|false
 */
function getInfoQuestion($courseId, $id)
{
    $id = (int) $id;

    $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);

    $sql = "SELECT *
            FROM $tableQuestion
            WHERE
                iid = $id
                AND type IN (1, 2, 3, 9, 10, 11, 12, 14)
            LIMIT 1";

    $res = Database::query($sql);

    return Database::fetch_assoc($res);
}

/**
 * List answers by question ID.
 *
 * @param int $courseId
 * @param int $id
 *
 * @return array
 */
function getAnswers($courseId, $id)
{
    $id = (int) $id;

    $tableQuizAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);

    $sql = "SELECT *
            FROM $tableQuizAnswer
            WHERE question_id = $id
            ORDER BY position ASC, iid ASC";

    $res = Database::query($sql);
    $items = [];

    while ($row = Database::fetch_assoc($res)) {
        $items[] = $row;
    }

    return $items;
}

/**
 * Remove all HTML tags.
 *
 * @param string $string The string to be stripped of HTML
 *
 * @return string
 */
function removeHtml($string)
{
    $txt = str_replace('<html>', '', $string);
    $txt = str_replace('<head>', '', $txt);
    $txt = str_replace('<title>', '', $txt);
    $txt = str_replace('</title>', '', $txt);
    $txt = str_replace('</head>', '', $txt);
    $txt = str_replace('<body>', '', $txt);
    $txt = str_replace('</body>', '', $txt);
    $txt = str_replace('</html>', '', $txt);
    $txt = strip_tags($txt);
    $txt = str_replace(chr(13).chr(10), '', $txt);
    $txt = str_replace('&nbsp;', ' ', $txt);
    $txt = str_replace('&Aacute;', 'Á', $txt);
    $txt = str_replace('&aacute;', 'á', $txt);
    $txt = str_replace('&Eacute;', 'É', $txt);
    $txt = str_replace('&eacute;', 'é', $txt);
    $txt = str_replace('&Iacute;', 'Í', $txt);
    $txt = str_replace('&iacute;', 'í', $txt);
    $txt = str_replace('&Oacute;', 'Ó', $txt);
    $txt = str_replace('&oacute;', 'ó', $txt);
    $txt = str_replace('&Uacute;', 'Ú', $txt);
    $txt = str_replace('&uacute;', 'ú', $txt);
    $txt = str_replace('&Ntilde;', 'Ñ', $txt);
    $txt = str_replace('&ntilde;', 'ñ', $txt);
    $txt = str_replace('&agrave;', 'à', $txt);
    $txt = str_replace('&Agrave;', 'À', $txt);
    $txt = str_replace('&iexcl;', '¡', $txt);
    $txt = str_replace('&middot;', '·', $txt);
    $txt = str_replace('&Ccedil;', 'Ç', $txt);
    $txt = str_replace('&ccedil;', 'ç', $txt);
    $txt = str_replace('&quot;', '"', $txt);
    $txt = str_replace('&ordf;', 'ª', $txt);
    $txt = str_replace('&ordm;', 'º', $txt);
    $txt = str_replace('&amp;', '&', $txt);
    $txt = str_replace('&bull;', '•', $txt);
    $txt = str_replace('&iquest;', '¿', $txt);
    $txt = str_replace('&euro;', 'EUR', $txt);
    $txt = str_replace('&uuml;', 'ü', $txt);
    $txt = str_replace('&Uuml;', 'Ü', $txt);

    return str_replace('&uml;', '¨', $txt);
}

/**
 * Replace HTML entities while preserving basic HTML.
 *
 * @param string $string The string to normalize
 *
 * @return string
 */
function removeQuotes($string)
{
    $txt = str_replace('&nbsp;', ' ', $string);
    $txt = str_replace('&Aacute;', 'Á', $txt);
    $txt = str_replace('&aacute;', 'á', $txt);
    $txt = str_replace('&Eacute;', 'É', $txt);
    $txt = str_replace('&eacute;', 'é', $txt);
    $txt = str_replace('&Iacute;', 'Í', $txt);
    $txt = str_replace('&iacute;', 'í', $txt);
    $txt = str_replace('&Oacute;', 'Ó', $txt);
    $txt = str_replace('&oacute;', 'ó', $txt);
    $txt = str_replace('&Uacute;', 'Ú', $txt);
    $txt = str_replace('&uacute;', 'ú', $txt);
    $txt = str_replace('&Ntilde;', 'Ñ', $txt);
    $txt = str_replace('&ntilde;', 'ñ', $txt);
    $txt = str_replace('&quot;', '"', $txt);
    $txt = str_replace('&ordf;', 'ª', $txt);
    $txt = str_replace('&ordm;', 'º', $txt);
    $txt = str_replace('&amp;', '&', $txt);
    $txt = str_replace('&bull;', '•', $txt);
    $txt = str_replace('&iquest; &', '¿', $txt);
    $txt = str_replace('&agrave;', 'à', $txt);
    $txt = str_replace('&Agrave;', 'À', $txt);
    $txt = str_replace('&iexcl;', '¡', $txt);
    $txt = str_replace('&middot;', '·', $txt);
    $txt = str_replace('&Ccedil;', 'Ç', $txt);
    $txt = str_replace('&ccedil;', 'ç', $txt);
    $txt = str_replace('&euro;', 'EUR', $txt);
    $txt = str_replace('&uuml;', 'ü', $txt);
    $txt = str_replace('&Uuml;', 'Ü', $txt);

    return str_replace('&uml;', '¨', $txt);
}
