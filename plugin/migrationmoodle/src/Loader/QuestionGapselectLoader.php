<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class QuestionGapselectLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class QuestionGapselectLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $courseInfo = api_get_course_info_by_id($incomingData['c_id']);

        $exercise = new \Exercise($incomingData['c_id']);
        $exercise->read($incomingData['quiz_id']);

        $question = \Question::read($incomingData['question_id'], $courseInfo);
        $question->setTitle(get_lang('FillBlanks'));
        $question->weighting = $incomingData['score'];

        $answer = new \Answer($incomingData['question_id'], $incomingData['c_id'], $exercise);
        $answer->createAnswer($incomingData['answer'], 0, $incomingData['comment'], 0, 1);
        $answer->save();

        $question->save($exercise);

        return $question->id;
    }
}
