<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class QuizzesScoresLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class QuizzesScoresLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        // c_quiz_rel_question has no c_id (the quiz identifies the course) and exercice_id is now quiz_id.
        $maxScore = (float) $em
            ->createQuery(
                'SELECT SUM(question.ponderation)
                    FROM '.CQuizRelQuestion::class.' relQuestion
                    INNER JOIN relQuestion.question question
                    WHERE relQuestion.quiz = :quizId'
            )
            ->setParameter('quizId', (int) $incomingData['quiz_id'])
            ->getSingleScalarResult()
        ;

        $em
            ->createQuery('UPDATE '.CLpItem::class.' lpi SET lpi.maxScore = :maxScore WHERE lpi.iid = :itemId')
            ->setParameter('maxScore', $maxScore)
            ->setParameter('itemId', (int) $incomingData['item_id'])
            ->execute()
        ;

        return $incomingData['item_id'];
    }
}
