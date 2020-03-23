<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserLearnPathsLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathsLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $tblLp = \Database::get_course_table(TABLE_LP_MAIN);
        $tblLpItem = \Database::get_course_table(TABLE_LP_ITEM);
        $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);
        $tblLpItemView = \Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $tblSrCrU = \Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $resSubscriptions = \Database::query(
            "SELECT c_id, session_id FROM $tblSrCrU WHERE user_id = {$incomingData['user_id']}"
        );

        while ($subscription = \Database::fetch_assoc($resSubscriptions)) {
            $resLps = \Database::query("SELECT iid FROM $tblLp WHERE c_id = {$subscription['c_id']} AND lp_type = 1");

            while ($lp = \Database::fetch_assoc($resLps)) {
                $lpViewId = \Database::insert(
                    $tblLpView,
                    [
                        'c_id' => $subscription['c_id'],
                        'lp_id' => $lp['iid'],
                        'user_id' => $incomingData['user_id'],
                        'view_count' => 1,
                        'session_id' => $subscription['session_id'],
                        'last_item' => 0,
                    ]
                );
                \Database::query("UPDATE $tblLpView SET id = iid WHERE iid = $lpViewId");

                $resItems = \Database::query(
                    "SELECT iid, max_score FROM $tblLpItem
                        WHERE lp_id = {$lp['iid']} ORDER BY parent_item_id ASC, display_order ASC"
                );
                while ($lpItem = \Database::fetch_assoc($resItems)) {
                    $lpItemViewId = \Database::insert(
                        $tblLpItemView,
                        [
                            'c_id' => $subscription['c_id'],
                            'lp_item_id' => $lpItem['iid'],
                            'lp_view_id' => $lpViewId,
                            'view_count' => 1,
                            'status' => 'not attempted',
                            'start_time' => 0,
                            'total_time' => 0,
                            'score' => 0,
                            'max_score' => $lpItem['max_score'],
                        ]
                    );
                    \Database::query("UPDATE $tblLpItemView SET id = iid WHERE iid = $lpItemViewId");
                }
            }
        }

        return $incomingData['user_id'];
    }
}
