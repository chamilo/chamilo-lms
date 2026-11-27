<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserQuizAttemptLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserQuizAttemptLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $view = $this->findViewByQuiz(
            $incomingData['exo_id'],
            $incomingData['user_id'],
            $incomingData['session_id']
        );

        /** @var \DateTime $exeDate */
        $exeDate = clone $incomingData['date'];
        $exeDate->modify("+{$incomingData['duration']} seconds");

        $em = \Database::getManager();

        $trackExercise = (new TrackEExercise())
            ->setQuiz($em->getReference(CQuiz::class, (int) $incomingData['exo_id']))
            ->setUser($em->getReference(User::class, (int) $incomingData['user_id']))
            ->setCourse($em->getReference(Course::class, (int) $view['c_id']))
            ->setSession(
                empty($incomingData['session_id'])
                    ? null
                    : $em->getReference(Session::class, (int) $incomingData['session_id'])
            )
            ->setStatus($incomingData['status'] === 'finished' ? '' : 'incomplete')
            ->setDataTracking((string) $incomingData['data_tracking'])
            ->setStartDate(clone $incomingData['date'])
            ->setOrigLpId((int) $view['lp_id'])
            ->setOrigLpItemId((int) $view['lp_item_id'])
            ->setOrigLpItemViewId((int) $view['iid'])
            // exe_weighting and exe_result were renamed max_score and score.
            ->setMaxScore((float) $incomingData['weighting'])
            ->setScore((float) $incomingData['result'])
            ->setUserIp('')
            ->setExeDate($exeDate)
            ->setStepsCounter(0)
            ->setExeDuration((int) $incomingData['duration'])
            ->setQuestionsToCheck('')
        ;

        $em->persist($trackExercise);
        $em->flush();

        return $trackExercise->getExeId();
    }

    /**
     * @param int $quizId
     * @param int $userId
     * @param int $sessionId
     *
     * @throws \Exception
     *
     * @return array
     */
    private function findViewByQuiz($quizId, $userId, $sessionId)
    {
        $qb = \Database::getManager()
            ->createQueryBuilder()
            ->select(
                'IDENTITY(lpiv.item) AS lp_item_id',
                'IDENTITY(lpv.course) AS c_id',
                'lpiv.iid',
                'IDENTITY(lpv.lp) AS lp_id'
            )
            ->from(CLpItemView::class, 'lpiv')
            ->innerJoin('lpiv.item', 'lpi')
            ->innerJoin('lpiv.view', 'lpv')
            ->where('lpi.path = :quizId')
            ->andWhere('lpv.user = :userId')
            ->setParameter('quizId', (string) $quizId)
            ->setParameter('userId', (int) $userId)
            ->setMaxResults(1)
        ;

        // c_lp_view.session_id is NULL for "no session" (it used to be 0).
        if (empty($sessionId)) {
            $qb->andWhere('lpv.session IS NULL');
        } else {
            $qb->andWhere('lpv.session = :sessionId')->setParameter('sessionId', (int) $sessionId);
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        if (!$result) {
            throw new \Exception("Item view not found for quiz ($quizId) and user ($userId) in session ($sessionId).");
        }

        return $result;
    }
}
