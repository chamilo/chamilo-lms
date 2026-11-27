<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class SortSectionModuleLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class SortSectionModuleLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        // The top level items of a learning path hang from its root item (parent_item_id 0 no longer exists).
        $rootItem = $em
            ->createQuery(
                'SELECT lpi FROM '.CLpItem::class.' lpi WHERE lpi.lp = :lpId AND lpi.itemType = :type'
            )
            ->setParameter('lpId', (int) $incomingData['lp_id'])
            ->setParameter('type', 'root')
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        if (!$rootItem) {
            throw new \Exception("Root item not found for learning path ({$incomingData['lp_id']}).");
        }

        // Search the Description document to put it as first item
        $firstItem = $em
            ->createQuery(
                'SELECT lpi.iid
                    FROM '.CLpItem::class.' lpi
                    WHERE lpi.lp = :lpId AND lpi.parent = :rootId AND lpi.itemType = :type
                    ORDER BY lpi.iid ASC'
            )
            ->setParameter('lpId', (int) $incomingData['lp_id'])
            ->setParameter('rootId', (int) $rootItem->getIid())
            ->setParameter('type', TOOL_DOCUMENT)
            ->setMaxResults(1)
            ->getOneOrNullResult()
        ;

        $orderList = $incomingData['order_list'];

        if ($firstItem) {
            $orderList = [$firstItem['iid'] => 0] + $orderList;
        }

        // learnpath::sortItemByOrderList() takes the root item and a list of objects with the item id and its parent.
        $items = [];
        foreach (array_keys($orderList) as $itemId) {
            $items[] = (object) ['id' => $itemId, 'parent_id' => 0];
        }

        \learnpath::sortItemByOrderList($rootItem, $items);

        return $incomingData['lp_id'];
    }
}
