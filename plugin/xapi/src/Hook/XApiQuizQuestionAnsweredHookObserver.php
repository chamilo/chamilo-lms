<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\PluginBundle\XApi\ToolExperience\Statement\QuizQuestionAnswered;

/**
 * Class XApiQuizQuestionAnsweredHook.
 */
class XApiQuizQuestionAnsweredHookObserver extends XApiActivityHookObserver implements HookQuizQuestionAnsweredObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function hookQuizQuestionAnswered(HookQuizQuestionAnsweredEventInterface $event)
    {
        $data = $event->getEventData();

        $em = Database::getManager();
        $attemptRepo = $em->getRepository(TrackEAttempt::class);

        $exe = $em->find(TrackEExercises::class, $data['exe_id']);
        $question = $em->find(CQuizQuestion::class, $data['question']['id']);
        $attempt = $attemptRepo->findOneBy(
            [
                'exeId' => $exe->getExeId(),
                'questionId' => $question->getId(),
            ]
        );
        $quiz = $em->find(CQuiz::class, $data['quiz']['id']);

        $quizQuestionAnswered = new QuizQuestionAnswered($attempt, $question, $quiz);

        $statement = $quizQuestionAnswered->generate();

        $this->saveSharedStatement($statement);
    }
}
