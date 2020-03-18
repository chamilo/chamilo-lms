<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonAnswersMultipleAnswerLoader;

/**
 * Class QuestionMultiChoiceMultipleTask.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class QuestionMultiChoiceMultipleTask extends QuestionMultiChoiceSingleTask
{
    /**
     * {@inheritdoc}
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => "SELECT
                    qa.id,
                    qa.question,
                    qa.answer,
                    qa.feedback,
                    (qa.fraction * qq.defaultmark) score,
                    IF (qa.fraction > 0, TRUE, FALSE) is_correct,
                    q.id quizid,
                    q.course
                FROM mdl_question_answers qa
                INNER JOIN mdl_question qq ON qa.question = qq.id
                INNER JOIN mdl_qtype_multichoice_options qo ON qq.id = qo.questionid
                INNER JOIN mdl_quiz_slots qs ON qq.id = qs.questionid
                INNER JOIN mdl_quiz q ON qs.quizid = q.id
                WHERE qq.qtype = 'multichoice'
                    AND qo.single = 0",
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LessonAnswersMultipleAnswerLoader::class,
        ];
    }
}
