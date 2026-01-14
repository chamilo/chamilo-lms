<?php
/* For license terms, see /license.txt */

/**
 * Additional functions for the Extended Question Pool plugin.
 *
 * @package chamilo.plugin.extendedquestionpool
 *
 * @author Nosolored <desarrollo@nosolored.com>
 */

/**
 * Get the number of occurrences of a question in quizzes
 * @param int $questionId
 * @return int
 */
function getQuestionOcurrences($questionId) {
    $questionQuizTable = 'c_quiz_rel_question';
    if (empty($questionId)) {
        return false;
    }
    $query = "SELECT count(iid) as nbr FROM $questionQuizTable 
                WHERE question_id = '$questionId'";
    $q = Database::query($query);
    $row = Database::fetch_assoc($q);
    $res = $row['nbr'];

    return $res;
}
/**
 * Get the number of failures answers of a question in quizzes
 * @param int $questionId
 * @param int $c_id  course id
 * @return int
 */
function getQuestionFailures($questionId, $c_id = 0) 
{
    $attemptTable = 'track_e_attempt';
    if (empty($questionId)) {
        return false;
    }
    $query = "SELECT count(id) as nbr FROM $attemptTable 
                WHERE question_id = '$questionId'
                AND marks <= 0";
    if (!empty($c_id)) {
        $query .= " AND c_id='$c_id'";
    }
    $q = Database::query($query);
    $row = Database::fetch_assoc($q);
    $res = $row['nbr'];

    return $res;
}
/**
 * Get the number of successes answers of a question in quizzes
 * @param int $questionId
 * @param int $c_id  course id
 * @return int
 */
function getQuestionSuccesses($questionId, $c_id = 0) 
{
    $attemptTable = 'track_e_attempt';
    if (empty($questionId)) {
        return false;
    }
    $query = "SELECT count(id) as nbr FROM $attemptTable 
                WHERE question_id = '$questionId'
                AND marks > 0";
    if (!empty($c_id)) {
        $query .= " AND c_id='$c_id'";
    }
    $q = Database::query($query);
    $row = Database::fetch_assoc($q);
    $res = $row['nbr'];

    return $res;
}
/**
 * Sort array by column
 * @param array $data
 * @param string $col
 * @param bool $asc
 * @return array
 */
function sortByCol(array $data, string $col, bool $asc = true): array {
    usort($data, function ($a, $b) use ($col, $asc) {
        if (!isset($a[$col]) || !isset($b[$col])) {
            return 0; // column doesn't exist, nothing to sort
        }

        if ($a[$col] == $b[$col]) {
            return 0;
        }

        if ($asc) {
            return (strip_tags($a[$col]) < strip_tags($b[$col])) ? -1 : 1;
        } else {
            return (strip_tags($a[$col]) > strip_tags($b[$col])) ? -1 : 1;
        }
    });

    return $data;
}
/**
 * Get answer options for a question
 * @param int $questionId
 * @return string
 */
function getQuestionAnswers(int $questionId) {
    $table = Database::get_course_table(TABLE_QUIZ_ANSWER);
    if (empty($questionId)) {
        return false;
    }
    $res = '';
    $query = "SELECT * FROM $table WHERE question_id=$questionId ORDER BY position";
    $result = Database::query($query);
    while ($row = Database::fetch_assoc($result)) {
        $c = '- ';
        if ($row['correct'] == 1) {
            $c = '# ';
        }
        $answer = html_entity_decode(strip_tags($row['answer']));
        $res .= $c.$answer.chr(13).chr(10);
    }
    return $res;
}