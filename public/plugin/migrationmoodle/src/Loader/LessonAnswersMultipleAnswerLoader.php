<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class LessonAnswersMultipleAnswerLoader.
 *
 * Loader to create Unique Answer question comming from Multiple Choice lesson page.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class LessonAnswersMultipleAnswerLoader implements LoaderInterface
{
    /**
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

        $incomingData['score'] = abs($incomingData['score']);

        if (!$incomingData['is_correct']) {
            $incomingData['score'] = -$incomingData['score'];
        }

        if ($incomingData['score'] > 0) {
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
