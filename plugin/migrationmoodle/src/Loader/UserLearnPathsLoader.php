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
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        $subscriptionsRepo = $em->getRepository('ChamiloCoreBundle:SessionRelCourseRelUser');
        $learnPathsRepo = $em->getRepository('ChamiloCourseBundle:CLp');

        $userId = $incomingData['user_id'];

        $subscriptions = $subscriptionsRepo->findBy(['user' => $userId]);

        foreach ($subscriptions as $subscription) {
            $course = $subscription->getCourse();

            $lps = $learnPathsRepo->findBy(['cId' => $course->getId(), 'lpType' => 1]);

            foreach ($lps as $lp) {
                new \learnpath(
                    $course->getCode(),
                    $lp->getId(),
                    $userId
                );
            }
        }

        return $userId;
    }
}
