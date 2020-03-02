<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserLearnPathsLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathsLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        $subscriptionsRepo = $em->getRepository('ChamiloCoreBundle:SessionRelCourseRelUser');
        $learnPathsRepo = $em->getRepository('ChamiloCourseBundle:CLp');
        $lpItemRepo = $em->getRepository('ChamiloCourseBundle:CLpItem');

        $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);
        $tblLpItemView = \Database::get_course_table(TABLE_LP_ITEM_VIEW);

        $userId = $incomingData['user_id'];

        $subscriptions = $subscriptionsRepo->findBy(['user' => $userId]);

        /** @var SessionRelCourseRelUser $subscription */
        foreach ($subscriptions as $subscription) {
            $course = $subscription->getCourse();
            $session = $subscription->getSession();

            $lps = $learnPathsRepo->findBy(['cId' => $course->getId(), 'lpType' => 1]);

            foreach ($lps as $lp) {
                $params = [
                    'c_id' => $course->getId(),
                    'lp_id' => $lp->getId(),
                    'user_id' => $userId,
                    'view_count' => 1,
                    'session_id' => $session->getId(),
                    'last_item' => 0,
                ];
                $lpViewId = \Database::insert($tblLpView, $params);
                \Database::query("UPDATE $tblLpView SET id = iid WHERE iid = $lpViewId");

                $lpItems = $lpItemRepo->findBy(
                    ['lpId' => $lp->getId()],
                    ['parentItemId' => 'ASC', 'displayOrder' => 'ASC']
                );

                foreach ($lpItems as $lpItem) {
                    $params = [
                        'c_id' => $course->getId(),
                        'lp_item_id' => $lpItem->getId(),
                        'lp_view_id' => $lpViewId,
                        'view_count' => 1,
                        'status' => 'not attempted',
                        'start_time' => 0,
                        'total_time' => 0,
                        'score' => 0,
                        'max_score' => $lpItem->getMaxScore(),
                    ];
                    $lpItemViewId = \Database::insert($tblLpItemView, $params);
                    \Database::query("UPDATE $tblLpItemView SET id = iid WHERE iid = $lpItemViewId");
                }
            }
        }

        return $userId;
    }
}
