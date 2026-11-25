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
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $parentItemView = $this->findViewOfParentItem($incomingData);
        $itemView = $this->findViewOfFirstItem($incomingData);

        \Database::getManager()
            ->createQuery(
                'UPDATE '.CLpItemView::class.' lpiv SET lpiv.startTime = :startTime WHERE lpiv.iid IN (:ids)'
            )
            ->setParameter('startTime', (int) $incomingData['start_time'])
            ->setParameter('ids', [(int) $parentItemView['iid'], (int) $itemView['iid']])
            ->execute()
        ;

        return $itemView['iid'];
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    private function findViewOfParentItem(array $incomingData)
    {
        $parentItemView = \Database::getManager()
            ->createQuery(
                'SELECT lpiv.iid
                    FROM '.CLpItemView::class.' lpiv
                    INNER JOIN lpiv.view lpv
                    WHERE lpiv.item = :parentItemId AND lpv.user = :userId'
            )
            ->setParameter('parentItemId', (int) $incomingData['parent_item_id'])
            ->setParameter('userId', (int) $incomingData['user_id'])
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        if (!$parentItemView) {
            throw new \Exception("Item dir ({$incomingData['parent_item_id']}) not found.");
        }

        return $parentItemView;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    private function findViewOfFirstItem(array $incomingData)
    {
        $qb = \Database::getManager()
            ->createQueryBuilder()
            ->select('lpiv.iid')
            ->from(CLpItemView::class, 'lpiv')
            ->innerJoin('lpiv.view', 'lpv')
            ->innerJoin('lpiv.item', 'lpi')
            ->where('lpi.lp = lpv.lp')
            ->andWhere('lpi.itemType = :itemType')
            ->andWhere('lpv.user = :userId')
            ->andWhere('lpi.parent = :parentItemId')
            ->orderBy('lpi.displayOrder', 'ASC')
            ->setParameter('itemType', 'document')
            ->setParameter('userId', (int) $incomingData['user_id'])
            ->setParameter('parentItemId', (int) $incomingData['parent_item_id'])
            ->setMaxResults(1)
        ;

        // c_lp_view.session_id is NULL for "no session" (it used to be 0).
        if (empty($incomingData['session_id'])) {
            $qb->andWhere('lpv.session IS NULL');
        } else {
            $qb->andWhere('lpv.session = :sessionId')->setParameter('sessionId', (int) $incomingData['session_id']);
        }

        $itemView = $qb->getQuery()->getOneOrNullResult();

        if (!$itemView) {
            throw new \Exception("Item view not found for item with"." parent item ({$incomingData['parent_item_id']}) and user ({$incomingData['user_id']})");
        }

        return $itemView;
    }
}
