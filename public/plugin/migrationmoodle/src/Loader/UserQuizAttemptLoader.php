<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserQuizAttemptLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserQuizAttemptLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $view = $this->findViewByQuiz(
            $incomingData['exo_id'],
            $incomingData['user_id'],
            $incomingData['session_id']
        );

        /** @var \DateTime $exeDate */
        $exeDate = clone $incomingData['date'];
        $exeDate->modify("+{$incomingData['duration']} seconds");

        return \Database::insert(
            \Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES),
            [
                'exe_exo_id' => $incomingData['exo_id'],
                'exe_user_id' => $incomingData['user_id'],
                'c_id' => $view['c_id'],
                'status' => $incomingData['status'] === 'finished' ? '' : 'incomplete',
                'session_id' => $incomingData['session_id'],
                'data_tracking' => $incomingData['data_tracking'],
                'start_date' => $incomingData['date']->format('Y-m-d H:i:s'),
                'orig_lp_id' => $view['lp_id'],
                'orig_lp_item_id' => $view['lp_item_id'],
                'orig_lp_item_view_id' => $view['iid'],
                'exe_weighting' => $incomingData['weighting'],
                'user_ip' => '',
                'exe_date' => $exeDate->format('Y-m-d H:i:s'),
                'exe_result' => (float) $incomingData['result'],
                'steps_counter' => 0,
                'exe_duration' => $incomingData['duration'],
                'questions_to_check' => '',
            ]
        );
    }

    /**
     * @param int $quizId
     * @param int $userId
     * @param int $sessionId
     *
     * @throws \Exception
     *
     * @return array
     */
    private function findViewByQuiz($quizId, $userId, $sessionId)
    {
        $query = \Database::query("SELECT lpiv.lp_item_id, lpv.c_id,  lpiv.iid, lpv.lp_id
            FROM c_lp_item_view lpiv
            INNER JOIN c_lp_item lpi ON (lpiv.lp_item_id = lpi.iid AND lpiv.c_id = lpi.c_id)
            INNER JOIN c_lp_view lpv ON (lpv.iid = lpiv.lp_view_id AND lpv.c_id = lpiv.c_id)
            WHERE lpi.path = $quizId AND lpv.user_id = $userId AND lpv.session_id = $sessionId
            LIMIT 1"
        );
        $result = \Database::fetch_assoc($query);

        if (!$result) {
            throw new \Exception("Item view not found for quiz ($quizId) and user ($userId) in session ($sessionId).");
        }

        return $result;
    }
}
