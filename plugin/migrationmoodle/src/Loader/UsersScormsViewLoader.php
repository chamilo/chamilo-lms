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
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();
        $userSessionSubscription = $em
            ->getRepository('ChamiloCoreBundle:SessionRelCourseRelUser')
            ->findOneBy(
                [
                    'user' => $incomingData['user_id'],
                    'course' => $incomingData['c_id']
                ]
            );

        if (empty($userSessionSubscription)) {
            throw new \Exception(
                "Session not found for user ({$incomingData['user_id']}) with course ({$incomingData['c_id']})"
            );
        }

        $incomingData['session_id'] = $userSessionSubscription->getSession()->getId();
        $incomingData['last_item'] = 0;

        $tblLpView = \Database::get_course_table(TABLE_LP_VIEW);

        $lpviewId = \Database::insert(
            $tblLpView,
            $incomingData
        );

        \Database::query("UPDATE $tblLpView SET id = iid WHERE iid = $lpviewId");

        return $lpviewId;
    }
}
