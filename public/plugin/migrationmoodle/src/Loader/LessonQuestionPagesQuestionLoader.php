<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LessonQuestionPagesQuestionLoader.
 *
 * Loader for create a question to be added in a quiz according the transformed data
 * coming from a moodle's lesson question page.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LessonQuestionPagesQuestionLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $exercise = new \Exercise($incomingData['c_id']);
        $exercise->read($incomingData['quiz_id']);

        $question = \Question::getInstance($incomingData['question_type']);
        $question->course = api_get_course_info_by_id($incomingData['c_id']);
        $question->updateTitle($incomingData['question_title']);
        $question->updateLevel(1);
        $question->updateCategory(0);
        $question->save($exercise);

        $exercise->addToList($question->id);
        $exercise->update_question_positions();

        return $question->id;
    }
}
