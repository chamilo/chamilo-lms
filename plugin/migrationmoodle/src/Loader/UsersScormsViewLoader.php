<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UsersScormsViewLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UsersScormsViewLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);
        $tblLpItemView = \Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $sessionId = $this->getUserSubscriptionInSession($incomingData['user_id'], $incomingData['c_id']);

        $lpViewId = $this->getLpView(
            $incomingData['user_id'],
            $incomingData['lp_id'],
            $incomingData['c_id'],
            $sessionId
        );

        $lpItemViewId = $this->getLpItemView($lpViewId, $incomingData['lp_item_id']);

        $itemView = [
            'c_id' => $incomingData['c_id'],
            'lp_item_id' => $incomingData['lp_item_id'],
            'lp_view_id' => $lpViewId,
            'view_count' => $incomingData['lp_item_view_count'],
            'status' => 'not attempted',
            'start_time' => 0,
            'total_time' => 0,
            'score' => 0,
            'max_score' => 100,
        ];

        foreach (array_keys($itemView) as $key) {
            if (isset($incomingData['item_data'][$key])) {
                $itemView[$key] = $incomingData['item_data'][$key];
            }
        }

        if (empty($lpItemViewId)) {
            $lpItemViewId = \Database::insert($tblLpItemView, $itemView);
            \Database::query("UPDATE $tblLpItemView SET id = iid WHERE iid = $lpItemViewId");
        } else {
            \Database::update($tblLpItemView, $itemView, ['iid = ?' => [$lpItemViewId]]);
        }

        \Database::query(
            "UPDATE $tblLpView
            SET last_item = {$incomingData['lp_item_id']},
                view_count = {$incomingData['lp_item_view_count']}
            WHERE iid = $lpViewId"
        );

        return $lpViewId;
    }

    /**
     * @param int $userId
     * @param int $courseId
     *
     * @throws \Exception
     *
     * @return int
     */
    private function getUserSubscriptionInSession($userId, $courseId)
    {
        $srcru = \Database::select(
            'session_id',
            \Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER),
            [
                'where' => [
                    'user_id = ? AND c_id = ?' => [$userId, $courseId],
                ],
            ],
            'first'
        );

        if (empty($srcru)) {
            throw new \Exception("Session not found for user ($userId) with course ($courseId)");
        }

        return $srcru['session_id'];
    }

    /**
     * @param int $userId
     * @param int $lpId
     * @param int $cId
     * @param int $sessionId
     *
     * @return int
     */
    private function getLpView($userId, $lpId, $cId, $sessionId)
    {
        $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);

        $lpView = \Database::select(
            'iid',
            $tblLpView,
            [
                'where' => [
                    'user_id = ? AND lp_id = ? AND c_id = ? AND session_id = ?' => [
                        $userId,
                        $lpId,
                        $cId,
                        $sessionId,
                    ],
                ],
                'order' => 'view_count DESC',
            ],
            'first'
        );

        if (empty($lpView)) {
            $lpView = [
                'c_id' => $cId,
                'lp_id' => $lpId,
                'user_id' => $userId,
                'view_count' => 1,
                'session_id' => $sessionId,
                'last_item' => 0,
            ];

            $lpViewId = \Database::insert($tblLpView, $lpView);
            \Database::query("UPDATE $tblLpView SET id = iid WHERE iid = $lpViewId");

            return $lpViewId;
        }

        return $lpView['iid'];
    }

    /**
     * @param int $lpViewId
     * @param int $lpItemId
     *
     * @return int
     */
    private function getLpItemView($lpViewId, $lpItemId)
    {
        $lpItemView = \Database::fetch_assoc(
            \Database::query("SELECT iid FROM c_lp_item_view WHERE lp_view_id = $lpViewId AND lp_item_id = $lpItemId")
        );

        if (empty($lpItemView)) {
            return 0;
        }

        return $lpItemView['iid'];
    }
}
