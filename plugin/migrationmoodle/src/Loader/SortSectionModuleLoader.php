<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

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
        // Search the Description document to put it as first item
        $firstItem = \Database::getManager()
            ->getRepository('ChamiloCourseBundle:CLpItem')
            ->findOneBy(
                [
                    'cId' => $incomingData['c_id'],
                    'lpId' => $incomingData['lp_id'],
                    'parentItemId' => 0,
                    'itemType' => TOOL_DOCUMENT,
                ],
                ['iid' => 'ASC']
            );

        $orderList = $incomingData['order_list'];

        if ($firstItem) {
            $orderList = [$firstItem->getId() => 0] + $orderList;
        }

        \learnpath::sortItemByOrderList(
            $orderList,
            $incomingData['c_id']
        );

        return $incomingData['lp_id'];
    }
}
