<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UsersScormsViewLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UsersScormsViewLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        $sessionId = $this->getUserSubscriptionInSession($incomingData['user_id'], $incomingData['c_id']);

        $lpView = $this->getLpView(
            $incomingData['user_id'],
            $incomingData['lp_id'],
            $incomingData['c_id'],
            $sessionId
        );

        $lpItemView = $this->getLpItemView($lpView, $incomingData['lp_item_id']);

        $itemView = [
            'view_count' => $incomingData['lp_item_view_count'],
            'status' => 'not attempted',
            'start_time' => 0,
            'total_time' => 0,
            'score' => 0,
            'max_score' => 100,
        ];

        foreach (array_keys($itemView) as $key) {
            if (isset($incomingData['item_data'][$key])) {
                $itemView[$key] = $incomingData['item_data'][$key];
            }
        }

        if (!$lpItemView) {
            $lpItemView = (new CLpItemView())
                ->setItem($em->getReference(CLpItem::class, (int) $incomingData['lp_item_id']))
                ->setView($lpView)
            ;
        }

        $lpItemView
            ->setViewCount((int) $itemView['view_count'])
            ->setStatus((string) $itemView['status'])
            ->setStartTime((int) $itemView['start_time'])
            ->setTotalTime((int) $itemView['total_time'])
            ->setScore((float) $itemView['score'])
            ->setMaxScore((string) $itemView['max_score'])
        ;
        $em->persist($lpItemView);

        $lpView
            ->setLastItem((int) $incomingData['lp_item_id'])
            ->setViewCount((int) $incomingData['lp_item_view_count'])
        ;
        $em->flush();

        return $lpView->getIid();
    }

    /**
     * @param int $userId
     * @param int $courseId
     *
     * @throws \Exception
     *
     * @return int
     */
    private function getUserSubscriptionInSession($userId, $courseId)
    {
        $srcru = \Database::getManager()
            ->createQuery(
                'SELECT IDENTITY(srcru.session) AS sessionId
                    FROM '.SessionRelCourseRelUser::class.' srcru
                    WHERE srcru.user = :userId AND srcru.course = :courseId'
            )
            ->setParameter('userId', (int) $userId)
            ->setParameter('courseId', (int) $courseId)
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        if (empty($srcru)) {
            throw new \Exception("Session not found for user ($userId) with course ($courseId)");
        }

        return (int) $srcru['sessionId'];
    }

    /**
     * @param int $userId
     * @param int $lpId
     * @param int $cId
     * @param int $sessionId
     *
     * @return CLpView
     */
    private function getLpView($userId, $lpId, $cId, $sessionId)
    {
        $em = \Database::getManager();

        $lpView = $em
            ->createQuery(
                'SELECT lpv
                    FROM '.CLpView::class.' lpv
                    WHERE lpv.user = :userId AND lpv.lp = :lpId AND lpv.course = :courseId AND lpv.session = :sessionId
                    ORDER BY lpv.viewCount DESC'
            )
            ->setParameter('userId', (int) $userId)
            ->setParameter('lpId', (int) $lpId)
            ->setParameter('courseId', (int) $cId)
            ->setParameter('sessionId', (int) $sessionId)
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        if ($lpView) {
            return $lpView;
        }

        $lpView = (new CLpView())
            ->setCourse($em->getReference(Course::class, (int) $cId))
            ->setLp($em->getReference(CLp::class, (int) $lpId))
            ->setUser($em->getReference(User::class, (int) $userId))
            ->setViewCount(1)
            ->setSession($em->getReference(Session::class, (int) $sessionId))
            ->setLastItem(0)
        ;
        $em->persist($lpView);
        $em->flush();

        return $lpView;
    }

    /**
     * @param int $lpItemId
     *
     * @return CLpItemView|null
     */
    private function getLpItemView(CLpView $lpView, $lpItemId)
    {
        return \Database::getManager()
            ->createQuery(
                'SELECT lpiv
                    FROM '.CLpItemView::class.' lpiv
                    WHERE lpiv.view = :viewId AND lpiv.item = :itemId'
            )
            ->setParameter('viewId', (int) $lpView->getIid())
            ->setParameter('itemId', (int) $lpItemId)
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;
    }
}
