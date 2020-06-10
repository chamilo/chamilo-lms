<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Script;

/**
 * Class UserScormsProgressScript.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Script
 */
class UserScormsProgressScript extends BaseScript
{
    public function process()
    {
        $itemsInLp = $this->countItemsInLp();

        foreach ($this->getUsersAndLps() as $lpView) {
            $userId = $lpView['user_id'];
            $lpId = $lpView['lp_id'];
            $cId = $lpView['c_id'];
            $lpViewId = $lpView['iid'];

            $completedItems = $this->countCompletedItems($userId, $lpId, $cId);

            if (empty($completedItems) || empty($itemsInLp[$lpId])) {
                continue;
            }

            if ($completedItems > $itemsInLp[$lpId]) {
                $progress = 100;
            } else {
                $progress = (int) ($completedItems / $itemsInLp[$lpId] * 100);
            }

            \Database::query(
                "UPDATE c_lp_view
                    SET progress = $progress
                    WHERE user_id = $userId
                        AND lp_id = $lpId
                        AND c_id = $cId
                        AND iid = $lpViewId"
            );

            $this->showMessage("Updated: c_lp_view $lpViewId with progress $progress.");
        }
    }

    /**
     * @return array
     */
    private function countItemsInLp()
    {
        $tblItem = \Database::get_course_table(TABLE_LP_ITEM);
        $tblLp = \Database::get_course_table(TABLE_LP_MAIN);

        $result = \Database::query(
            "SELECT lpi.lp_id, COUNT(lpi.iid) AS c_lpi
                FROM $tblItem lpi
                INNER JOIN $tblLp lp ON lpi.lp_id = lp.iid
                WHERE lpi.item_type = 'sco' AND lp.lp_type = 2
                GROUP BY lpi.lp_id"
        );

        $data = [];

        while ($row = \Database::fetch_assoc($result)) {
            $data[$row['lp_id']] = $row['c_lpi'];
        }

        return $data;
    }

    /**
     * @return \Generator
     */
    private function getUsersAndLps()
    {
        $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);
        $tblLp = \Database::get_course_table(TABLE_LP_MAIN);

        $result = \Database::query(
            "SELECT lpv.iid, lpv.lp_id, lpv.user_id, lpv.c_id
                FROM $tblLpView lpv
                INNER JOIN $tblLp lp ON lpv.lp_id = lp.iid
                WHERE lp.lp_type = 2"
        );

        while ($row = \Database::fetch_assoc($result)) {
            if (!$this->isLoadedUser($row['user_id']) ||
                !$this->isMigratedScorm($row['lp_id'])
            ) {
                continue;
            }

            yield $row;
        }
    }

    /**
     * @param int $userId
     * @param int $lpId
     * @param int $cId
     *
     * @return int
     */
    private function countCompletedItems($userId, $lpId, $cId)
    {
        $tblItemView = \Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);
        $tblLp = \Database::get_course_table(TABLE_LP_MAIN);

        $result = \Database::query("SELECT
                lpiv.lp_view_id,
                lpiv.c_id,
                COUNT(lpiv.lp_item_id) c_lpiv
            FROM $tblItemView lpiv
            INNER JOIN $tblLpView lpv ON (lpiv.lp_view_id = lpv.iid AND lpiv.c_id = lpv.c_id)
            INNER JOIN $tblLp lp ON (lpv.lp_id = lp.iid AND lpv.c_id = lp.c_id)
            WHERE lpiv.status = 'completed'
                AND lpv.user_id = $userId
                AND lp.iid = $lpId
                AND lp.c_id = $cId
                AND lp.lp_type = 2
            GROUP BY lpiv.lp_view_id, lpiv.lp_item_id");

        if (\Database::num_rows($result) == 0) {
            return 0;
        }

        $count = 0;

        while ($row = \Database::fetch_assoc($result)) {
            $count += (int) $row['c_lpiv'];
        }

        return $count;
    }
}
