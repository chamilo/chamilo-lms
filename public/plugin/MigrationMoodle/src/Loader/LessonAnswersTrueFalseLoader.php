<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LessonAnswersTrueFalseLoader.
 *
 * Loader for True-False answers from lesson pages.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LessonAnswersTrueFalseLoader implements LoaderInterface
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

        if ($incomingData['is_correct']) {
            $incomingData['score'] = abs($incomingData['score']);

            $question->weighting += $incomingData['score'];
        }

        $answer->createAnswer(
            $incomingData['answer'],
            $incomingData['is_correct'],
            $incomingData['feedback'],
            $incomingData['score'],
            $question->countAnswers() + 1,
            null,
            null,
            CQuizAnswer::DEFAULT_DESTINATION
        );

        $answer->save();
        $question->save($exercise);

        return $question->id;
    }
}
