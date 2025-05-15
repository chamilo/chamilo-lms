<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LessonAnswersMatchingLoader.
 *
 * Loader to create Matching question answers comming from Matching lesson page.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LessonAnswersMatchingLoader implements LoaderInterface
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

        $answer = new \Answer($incomingData['question_id'], $incomingData['c_id'], $exercise);
        $questionsAnswers = $answer->getAnswers();

        foreach ($questionsAnswers as $questionsAnswer) {
            $answer->createAnswer(
                $questionsAnswer['answer'],
                $questionsAnswer['correct'],
                $questionsAnswer['comment'],
                $questionsAnswer['ponderation'],
                $questionsAnswer['position'],
                $questionsAnswer['hotspot_coordinates'],
                $questionsAnswer['hotspot_type'],
                $questionsAnswer['destination']
            );
        }

        $optionPosition = $question->countAnswers() + 1;

        $answer->createAnswer($incomingData['feedback'], 0, '', 0, $optionPosition);

        $answerPosition = $optionPosition + 1;

        $answer->createAnswer(
            $incomingData['answer'],
            $optionPosition,
            '',
            $incomingData['score'],
            $answerPosition
        );

        $answer->save();

        $question->weighting += $incomingData['score'];
        $question->save($exercise);

        return $question->id;
    }
}
