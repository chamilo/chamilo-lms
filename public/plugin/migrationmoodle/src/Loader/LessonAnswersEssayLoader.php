<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LessonAnswersEssayLoader.
 *
 * Loader to create Free Answer question answers comming from Essay lesson page.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LessonAnswersEssayLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $courseInfo = api_get_course_info_by_id($incomingData['c_id']);

        $exercise = new \Exercise($incomingData['c_id']);
        $exercise->read($incomingData['quiz_id']);

        $question = \Question::read($incomingData['question_id'], $courseInfo);

        $question->weighting = $incomingData['score'];
        $question->save($exercise);

        return $question->id;
    }
}
