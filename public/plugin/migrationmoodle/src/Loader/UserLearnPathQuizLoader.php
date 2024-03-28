<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserLearnPathQuizLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathQuizLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $itemView = $this->findViewOfItem(
            $incomingData['item_id'],
            $incomingData['user_id'],
            $incomingData['session_id']
        );

        \Database::update(
            \Database::get_course_table(TABLE_LP_ITEM_VIEW),
            [
                'start_time' => $incomingData['start_time'],
                'total_time' => $incomingData['total_time'],
                'score' => $incomingData['score'],
                'status' => $incomingData['status'],
            ],
            ['iid = ?' => $itemView['iid']]
        );

        return $itemView['iid'];
    }

    /**
     * @param int $itemId
     * @param int $userId
     * @param int $sessionId
     *
     * @throws \Exception
     *
     * @return array
     */
    private function findViewOfItem($itemId, $userId, $sessionId)
    {
        $result = \Database::query(
            "SELECT lpiv.iid
                FROM c_lp_item_view lpiv
                INNER JOIN c_lp_view lpv ON (lpv.iid = lpiv.lp_view_id AND lpv.c_id = lpiv.c_id)
                WHERE lpiv.lp_item_id = $itemId AND lpv.user_id = $userId AND lpv.session_id = $sessionId
                LIMIT 1"
        );
        $itemView = \Database::fetch_assoc($result);

        if (!$itemView) {
            throw new \Exception("Item view not found for item ($itemId) and user ($userId) in session ($sessionId).");
        }

        return $itemView;
    }
}
