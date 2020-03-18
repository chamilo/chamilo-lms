<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserScormProgressLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserScormProgressLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $userId = $incomingData['user_id'];

        $sql = "SELECT
                lpv.user_id,
                lpv.session_id,
                lpv.c_id,
                lpv.iid,
                lpv.lp_id,
                CAST((count_item.c_lpi / count_item_view.c_lpiv * 100) AS INT) progress
            FROM c_lp_view lpv
            INNER JOIN (
                SELECT lp_id, COUNT(iid) AS c_lpi
                FROM c_lp_item
                GROUP BY lp_id
            ) count_item ON count_item.lp_id = lpv.lp_id
            INNER JOIN (
                SELECT
                    lp_view_id,
                    CASE
                        WHEN COUNT(lp_item_id) > 0 THEN 1
                        ELSE 0
                    END c_lpiv
                FROM c_lp_item_view
                WHERE status = 'completed'
                GROUP BY lp_view_id
            ) count_item_view ON count_item_view.lp_view_id = lpv.iid
            WHERE lpv.user_id = $userId
            ORDER BY lpv.user_id";

        $statement = \Database::query($sql);

        if (empty($statement)) {
            throw new \Exception("No data to calculate scorm progress for user ($userId).");
        }

        $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);

        while ($row = \Database::fetch_assoc($statement)) {
            $sql = "UPDATE $tblLpView
                SET progress = {$row['progress']}
                WHERE user_id = {$row['user_id']}
                    AND lp_id = {$row['lp_id']}
                    AND c_id = {$row['c_id']}
                    AND session_id = {$row['session_id']}
                    AND iid = {$row['iid']}";

            \Database::query($sql);
        }

        return $userId;
    }
}
