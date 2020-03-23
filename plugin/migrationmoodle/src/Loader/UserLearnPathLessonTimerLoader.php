<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

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
        $tblItemView = \Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $parentItemView = $this->findViewOfParentItem($incomingData);
        $itemView = $this->findViewOfFirstItem($incomingData);

        \Database::query(
            "UPDATE $tblItemView SET start_time = {$incomingData['start_time']} WHERE iid = {$parentItemView['iid']}"
        );
        \Database::query(
            "UPDATE $tblItemView SET start_time = {$incomingData['start_time']} WHERE iid = {$itemView['iid']}"
        );

        return $itemView['iid'];
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    private function findViewOfParentItem(array $incomingData)
    {
        $parentItemView = \Database::fetch_assoc(
            \Database::query(
                "SELECT lpiv.iid
                FROM c_lp_item_view lpiv
                INNER JOIN c_lp_view lpv ON (lpv.iid = lpiv.lp_view_id AND lpv.c_id = lpiv.c_id)
                WHERE lpiv.lp_item_id = {$incomingData['parent_item_id']}
                    AND lpv.user_id = {$incomingData['user_id']}
                LIMIT 1"
            )
        );

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
        $itemView = \Database::fetch_assoc(
            \Database::query(
                "SELECT lpiv.iid
                FROM c_lp_item_view lpiv
                INNER JOIN c_lp_view lpv
                    ON (lpv.iid = lpiv.lp_view_id AND lpv.c_id = lpiv.c_id)
                INNER JOIN c_lp_item lpi
                    ON (lpi.lp_id = lpv.lp_id AND lpi.c_id = lpv.c_id AND lpi.iid = lpiv.lp_item_id)
                WHERE lpi.item_type = 'document'
                    AND lpv.user_id = {$incomingData['user_id']}
                    AND lpi.parent_item_id = {$incomingData['parent_item_id']}
                    AND lpv.session_id = {$incomingData['session_id']}
                ORDER BY lpi.display_order ASC
                LIMIT 1"
            )
        );

        if (!$itemView) {
            throw new \Exception("Item view not found for item with"." parent item ({$incomingData['parent_item_id']}) and user ({$incomingData['user_id']})");
        }

        return $itemView;
    }
}
