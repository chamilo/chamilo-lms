<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CLpItem;
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
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $tblLpItemView = \Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $item = \Database::getManager()
            ->createQuery('SELECT lpi.displayOrder AS display_order FROM '.CLpItem::class.' lpi WHERE lpi.iid = :itemId')
            ->setParameter('itemId', (int) $incomingData['item_id'])
            ->getOneOrNullResult()
        ;

        if (!$item) {
            throw new \Exception("LP item ({$incomingData['item_id']}) not found.");
        }

        $itemView = $this->findViewOfItem($incomingData);

        $itemViewParams = ['status' => 'completed'];

        if ($item['display_order'] != 1) {
            $previousItemView = $this->findViewOfPreviousItem($incomingData);

            $itemViewParams['start_time'] = $previousItemView['start_time'] + $previousItemView['total_time'];
            $itemView['start_time'] = $itemViewParams['start_time'];
        }

        $itemViewParams['total_time'] = $incomingData['end_time'] - $itemView['start_time'];

        \Database::update(
            $tblLpItemView,
            $itemViewParams,
            ['iid = ?' => [$itemView['iid']]]
        );

        return $itemView['iid'];
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    private function findViewOfItem(array $incomingData)
    {
        $itemView = \Database::getManager()
            ->createQuery(
                'SELECT lpiv.iid, lpiv.startTime AS start_time
                    FROM '.CLpItemView::class.' lpiv
                    INNER JOIN lpiv.view lpv
                    WHERE lpiv.item = :itemId AND lpv.user = :userId'
            )
            ->setParameter('itemId', (int) $incomingData['item_id'])
            ->setParameter('userId', (int) $incomingData['user_id'])
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        if (!$itemView) {
            throw new \Exception("Item view not found for "."item ({$incomingData['item_id']}) and user ({$incomingData['user_id']}).");
        }

        return $itemView;
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    private function findViewOfPreviousItem(array $incomingData)
    {
        // The item before this one is the item whose next_item_id points to it.
        $previousItemView = \Database::getManager()
            ->createQuery(
                'SELECT lpiv.startTime AS start_time, lpiv.totalTime AS total_time
                    FROM '.CLpItemView::class.' lpiv
                    INNER JOIN lpiv.view lpv
                    INNER JOIN lpiv.item lpi
                    WHERE lpi.nextItemId = :itemId AND lpv.user = :userId'
            )
            ->setParameter('itemId', (int) $incomingData['item_id'])
            ->setParameter('userId', (int) $incomingData['user_id'])
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        if (!$previousItemView) {
            throw new \Exception("Item view not found for "."previous item ({$incomingData['item_id']}) and user ({$incomingData['user_id']}).");
        }

        return $previousItemView;
    }
}
