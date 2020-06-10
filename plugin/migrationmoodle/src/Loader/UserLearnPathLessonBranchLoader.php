<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

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
        $tblLpItem = \Database::get_course_table(TABLE_LP_ITEM);
        $tblLpItemView = \Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $item = \Database::fetch_assoc(
            \Database::query("SELECT display_order FROM $tblLpItem WHERE iid = {$incomingData['item_id']}")
        );

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
        $itemView = \Database::fetch_assoc(
            \Database::query(
                "SELECT lpiv.iid, lpiv.start_time
                FROM c_lp_item_view lpiv
                INNER JOIN c_lp_view lpv ON (lpv.iid = lpiv.lp_view_id AND lpv.c_id = lpiv.c_id)
                WHERE lpiv.lp_item_id = {$incomingData['item_id']} AND lpv.user_id = {$incomingData['user_id']}
                LIMIT 1"
            )
        );

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
        $result = \Database::query(
            "SELECT lpiv.start_time, lpiv.total_time
                FROM c_lp_item_view lpiv
                INNER JOIN c_lp_view lpv ON (lpv.iid = lpiv.lp_view_id AND lpv.c_id = lpiv.c_id)
                INNER JOIN c_lp_item lpi ON (lpi.iid = lpiv.lp_item_id AND lpi.c_id = lpiv.c_id)
                WHERE lpi.next_item_id = {$incomingData['item_id']} AND lpv.user_id = {$incomingData['user_id']}
                LIMIT 1"
        );
        $previousItemView = \Database::fetch_assoc($result);

        if (!$previousItemView) {
            throw new \Exception("Item view not found for "."previous item ({$incomingData['item_id']}) and user ({$incomingData['user_id']}).");
        }

        return $previousItemView;
    }
}
