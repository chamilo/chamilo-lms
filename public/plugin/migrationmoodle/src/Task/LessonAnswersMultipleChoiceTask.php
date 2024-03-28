<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Task;

use Chamilo\PluginBundle\MigrationMoodle\Extractor\LoadedCoursesFilterExtractor;
use Chamilo\PluginBundle\MigrationMoodle\Loader\LessonAnswersMultipleChoiceLoader;

/**
 * Class LessonAnswersMultipleChoiceTask.
 *
 * Task to convert Multiple Choice answers from a Moodle lesson page in answers for Unique Answer question for Chamilo.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Task
 */
class LessonAnswersMultipleChoiceTask extends LessonAnswersTrueFalseTask
{
    /**
     * @return array
     */
    public function getExtractConfiguration()
    {
        return [
            'class' => LoadedCoursesFilterExtractor::class,
            'query' => 'SELECT la.id, la.pageid, la.score, la.answer, la.response, l.course
                FROM mdl_lesson_answers la
                INNER JOIN mdl_lesson_pages lp ON (la.pageid = lp.id AND la.lessonid = lp.lessonid)
                INNER JOIN mdl_lesson l ON (lp.lessonid = l.id AND la.lessonid = l.id)
                WHERE lp.qtype = 3 AND lp.qoption = 0
                ORDER BY lp.id',
        ];
    }

    /**
     * @return array
     */
    public function getLoadConfiguration()
    {
        return [
            'class' => LessonAnswersMultipleChoiceLoader::class,
        ];
    }
}
