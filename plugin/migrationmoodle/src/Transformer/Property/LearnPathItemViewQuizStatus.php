<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

/**
 * Class LearnPathItemViewQuizStatus.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LearnPathItemViewQuizStatus extends LoadedQuizLookup
{
    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return string
     */
    public function transform(array $data)
    {
        list($mQuizId, $mQuizAttemptState, $mQuizSumGrades) = array_values($data);

        $quizId = parent::transform([$mQuizId]);
        $quiz = $this->findQuiz($quizId);

        if ('finished' === $mQuizAttemptState) {
            if ($quiz->getPassPercentage() > 0 && $mQuizSumGrades > $quiz->getPassPercentage()) {
                return 'passed';
            }

            return 'completed';
        }

        return 'not attempted';
    }

    /**
     * @param int $quizId
     *
     * @throws \Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return \Chamilo\CourseBundle\Entity\CQuiz
     */
    private function findQuiz($quizId = 0)
    {
        $quiz = \Database::getManager()->find('ChamiloCourseBundle:CQuiz', $quizId);

        if (!$quiz) {
            throw new \Exception("Quiz ($quizId) not found.");
        }

        return $quiz;
    }
}
