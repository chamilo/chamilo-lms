<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserLearnPathLessonBranchLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathLessonBranchLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        $item = $em->find('ChamiloCourseBundle:CLpItem', $incomingData['item_id']);

        if (!$item) {
            throw new \Exception("LP item ({$incomingData['item_id']}) not found.");
        }

        $itemView = $this->findViewOfItem($incomingData);

        if ($item->getDisplayOrder() !== 1) {
            $previuousItemView = $this->findViewOfPreviousItem($incomingData);

            $itemView->setStartTime(
                $previuousItemView->getStartTime() + $previuousItemView->getTotalTime()
            );
        }

        $totalTime = $incomingData['end_time'] - $itemView->getStartTime();

        $itemView
            ->setStatus('completed')
            ->setTotalTime($totalTime);

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
    private function findViewOfItem(array $incomingData)
    {
        /** @var CLpItemView $itemView */
        $itemView = \Database::getManager()
            ->createQuery("SELECT lpiv
                FROM ChamiloCourseBundle:CLpItemView lpiv
                INNER JOIN ChamiloCourseBundle:CLpView lpv WITH (lpv.iid = lpiv.lpViewId AND lpv.cId = lpiv.cId)
                WHERE lpiv.lpItemId = :item_id AND lpv.userId = :user_id")
            ->setMaxResults(1)
            ->setParameters(['item_id' => $incomingData['item_id'], 'user_id' => $incomingData['user_id']])
            ->getOneOrNullResult();

        if (!$itemView) {
            throw new \Exception("Item view not found for "
                ."item ({$incomingData['item_id']}) and user ({$incomingData['user_id']}).");
        }

        return $itemView;
    }

    /**
     * @param array $incomingData
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return CLpItemView
     */
    private function findViewOfPreviousItem(array $incomingData)
    {
        /** @var CLpItemView $previuousItemView */
        $previuousItemView = \Database::getManager()
            ->createQuery("SELECT lpiv
                    FROM ChamiloCourseBundle:CLpItemView lpiv
                    INNER JOIN ChamiloCourseBundle:CLpView lpv WITH (lpv.iid = lpiv.lpViewId AND lpv.cId = lpiv.cId)
                    INNER JOIN ChamiloCourseBundle:CLpItem lpi WITH (lpi.iid = lpiv.lpItemId AND lpi.cId = lpiv.cId)
                    WHERE lpi.nextItemId = :item_id AND lpv.userId = :user_id")
            ->setMaxResults(1)
            ->setParameters(['item_id' => $incomingData['item_id'], 'user_id' => $incomingData['user_id']])
            ->getOneOrNullResult();

        if (!$previuousItemView) {
            throw new \Exception("Item view not found for "
                ."previous item ({$incomingData['item_id']}) and user ({$incomingData['user_id']}).");
        }

        return $previuousItemView;
    }
}
