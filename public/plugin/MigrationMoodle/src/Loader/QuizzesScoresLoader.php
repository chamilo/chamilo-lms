<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class QuizzesScoresLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class QuizzesScoresLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $tblQuizQuestion = \Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tblQuizRelQuestion = \Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
        $tblLpItem = \Database::get_course_table(TABLE_LP_ITEM);

        $sql = "SELECT SUM(ponderation)
            FROM $tblQuizQuestion as quiz_question
            INNER JOIN $tblQuizRelQuestion as quiz_rel_question
            ON quiz_question.iid = quiz_rel_question.question_id
            WHERE
                quiz_rel_question.exercice_id = {$incomingData['quiz_id']}
                AND quiz_rel_question.c_id = {$incomingData['c_id']}";

        $rsQuiz = \Database::query($sql);
        $maxScore = \Database::result($rsQuiz, 0, 0) ?: 0;

        \Database::query("UPDATE $tblLpItem SET max_score = $maxScore WHERE iid = {$incomingData['item_id']}");

        return $incomingData['item_id'];
    }
}
