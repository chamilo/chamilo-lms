<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UsersScormsViewLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UsersScormsViewLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);
        $tblLpItemView = \Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $session = $this->getUserSubscriptionInSession($incomingData['user_id'], $incomingData['c_id']);

        $lpViewId = $this->getLpView(
            $incomingData['user_id'],
            $incomingData['lp_id'],
            $incomingData['c_id'],
            $session->getId()
        );

        $itemView = [
            'c_id' => $incomingData['c_id'],
            'lp_item_id' => $incomingData['lp_item_id'],
            'lp_view_id' => $lpViewId,
            'view_count' => $incomingData['lp_item_view_count'],
            'status' => 'not attempted',
            'start_time' => time(),
            'total_time' => 0,
            'score' => 0,
            'max_score' => null,
        ];

        foreach (array_keys($itemView) as $key) {
            if (isset($incomingData['item_data'][$key])) {
                $itemView[$key] = $incomingData['item_data'][$key];
            }
        }

        $lpItemViewId = \Database::insert($tblLpItemView, $itemView);
        \Database::query("UPDATE $tblLpItemView SET id = iid WHERE iid = $lpItemViewId");

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
     * @return Session
     */
    private function getUserSubscriptionInSession($userId, $courseId)
    {
        $subscription = \Database::getManager()
            ->getRepository('ChamiloCoreBundle:SessionRelCourseRelUser')
            ->findOneBy(['user' => $userId, 'course' => $courseId]);

        if (empty($subscription)) {
            throw new \Exception(
                "Session not found for user ($userId) with course ($courseId)"
            );
        }

        return $subscription->getSession();
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
        $lpView = \Database::getManager()
            ->getRepository('ChamiloCourseBundle:CLpView')
            ->findOneBy(
                [
                    'userId' => $userId,
                    'lpId' => $lpId,
                    'cId' => $cId,
                    'sessionId' => $sessionId
                ],
                ['viewCount' => 'DESC']
            );

        if (empty($lpView)) {
            $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);

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

        return $lpView->getId();
    }
}
