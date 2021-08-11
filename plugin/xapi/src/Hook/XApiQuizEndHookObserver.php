<?php

/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\XApi\ToolExperience\Statement\QuizCompleted;

/**
 * Class XApiQuizEndHookObserver.
 */
class XApiQuizEndHookObserver extends XApiActivityHookObserver implements HookQuizEndObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function hookQuizEnd(HookQuizEndEventInterface $hookEvent)
    {
        $data = $hookEvent->getEventData();
        $em = Database::getManager();

        $exe = $em->find('ChamiloCoreBundle:TrackEExercises', $data['exe_id']);
        $quiz = $em->find('ChamiloCourseBundle:CQuiz', $exe->getExeExoId());

        $quizCompleted = new QuizCompleted($exe, $quiz);

        $statement = $quizCompleted->generate();

        $this->saveSharedStatement($statement);
    }
}
