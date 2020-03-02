<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserLearnPathLessonTimerLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathLessonTimerLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        $parentItemView = $this->findViewOfParentItem($incomingData)->setStartTime($incomingData['start_time']);

        $itemView = $this->findViewOfFirstItem($incomingData)->setStartTime($incomingData['start_time']);

        $em->persist($parentItemView);
        $em->persist($itemView);
        $em->flush();

        return $itemView->getId();
    }

    /**
     * @param array $incomingData
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return CLpItemView
     */
    private function findViewOfParentItem(array $incomingData)
    {
        /** @var CLpItemView $parentItemView */
        $parentItemView = \Database::getManager()
            ->createQuery("SELECT lpiv
                FROM ChamiloCourseBundle:CLpItemView lpiv
                INNER JOIN ChamiloCourseBundle:CLpView lpv WITH (lpv.iid = lpiv.lpViewId AND lpv.cId = lpiv.cId)
                WHERE lpiv.lpItemId = :item_id AND lpv.userId = :user_id")
            ->setMaxResults(1)
            ->setParameters(['item_id' => $incomingData['parent_item_id'], 'user_id' => $incomingData['user_id']])
            ->getOneOrNullResult();

        if (!$parentItemView) {
            throw new \Exception("Item dir ({$incomingData['parent_item_id']}) not found.");
        }

        return $parentItemView;
    }

    /**
     * @param array $incomingData
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return CLpItemView
     */
    private function findViewOfFirstItem(array $incomingData)
    {
        /** @var CLpItemView $itemView */
        $itemView = \Database::getManager()
            ->createQuery(
                "SELECT lpiv
                    FROM ChamiloCourseBundle:CLpItemView lpiv
                    INNER JOIN ChamiloCourseBundle:CLpView lpv
                        WITH (lpv.iid = lpiv.lpViewId AND lpv.cId = lpiv.cId)
                    INNER JOIN ChamiloCourseBundle:CLpItem lpi
                        WITH (lpi.lpId = lpv.lpId AND lpi.cId = lpv.cId AND lpi.iid = lpiv.lpItemId)
                    WHERE lpi.itemType = :type
                        AND lpv.userId = :user_id
                        AND lpi.parentItemId = :parent_item_id
                        AND lpv.sessionId = :session_id
                        ORDER BY lpi.displayOrder ASC"
            )
            ->setMaxResults(1)
            ->setParameters(
                [
                    'type' => 'document',
                    'user_id' => $incomingData['user_id'],
                    'parent_item_id' => $incomingData['parent_item_id'],
                    'session_id' => $incomingData['session_id'],
                ]
            )
            ->getOneOrNullResult();

        if (!$itemView) {
            throw new \Exception("Item view not found for item with"
                ." parent item ({$incomingData['parent_item_id']}) and user ({$incomingData['user_id']})");
        }

        return $itemView;
    }
}
