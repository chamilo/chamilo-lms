<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserLearnPathQuizLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathQuizLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $itemView = $this->findViewOfItem(
            $incomingData['item_id'],
            $incomingData['user_id'],
            $incomingData['session_id']
        );
        $itemView
            ->setStartTime($incomingData['start_time'])
            ->setTotalTime($incomingData['total_time'])
            ->setScore($incomingData['score'])
            ->setStatus($incomingData['status']);

        $em = \Database::getManager();
        $em->persist($itemView);
        $em->flush();

        return $itemView->getId();
    }

    /**
     * @param int $itemId
     * @param int $userId
     * @param int $sessionId
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return CLpItemView
     */
    private function findViewOfItem($itemId, $userId, $sessionId)
    {
        /** @var CLpItemView $itemView */
        $itemView = \Database::getManager()
            ->createQuery("SELECT lpiv
                FROM ChamiloCourseBundle:CLpItemView lpiv
                INNER JOIN ChamiloCourseBundle:CLpView lpv WITH (lpv.iid = lpiv.lpViewId AND lpv.cId = lpiv.cId)
                WHERE lpiv.lpItemId = :item_id AND lpv.userId = :user_id AND lpv.sessionId = :session_id")
            ->setMaxResults(1)
            ->setParameters(['item_id' => $itemId, 'user_id' => $userId, 'session_id' => $sessionId])
            ->getOneOrNullResult();

        if (!$itemView) {
            throw new \Exception("Item view not found for item ($itemId) and user ($userId) in session ($sessionId).");
        }

        return $itemView;
    }
}
