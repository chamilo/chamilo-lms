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
        $firstItem = \Database::select(
            'iid',
            \Database::get_course_table(TABLE_LP_ITEM),
            [
                'where' => [
                    'c_id = ? AND lp_id = ? AND parent_item_id = ? AND item_type = ?' => [
                        $incomingData['c_id'],
                        $incomingData['lp_id'],
                        0,
                        TOOL_DOCUMENT,
                    ],
                ],
                'order' => 'iid ASC',
            ],
            'first'
        );

        $orderList = $incomingData['order_list'];

        if ($firstItem) {
            $orderList = [$firstItem['iid'] => 0] + $orderList;
        }

        \learnpath::sortItemByOrderList(
            $orderList,
            $incomingData['c_id']
        );

        return $incomingData['lp_id'];
    }
}
