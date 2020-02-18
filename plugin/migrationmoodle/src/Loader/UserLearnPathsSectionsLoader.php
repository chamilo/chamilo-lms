<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class UserLearnPathsSectionsLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathsSectionsLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        $lpViewRepo = $em->getRepository('ChamiloCourseBundle:CLpView');
        $lpItemViewRepo = $em->getRepository('ChamiloCourseBundle:CLpItemView');

        $lpView = $lpViewRepo->findOneBy(['userId' => $incomingData['user_id'], 'lpId' => $incomingData['lp_id']]);

        if (empty($lpView)) {
            throw new \Exception(
                "LP view not found for user ({$incomingData['user_id']}) and LP ({$incomingData['lp_id']})"
            );
        }

        /** @var CLpItemView $lpItemView */
        $lpItemView = $lpItemViewRepo->findOneBy(
            [
                'lpViewId' => $lpView->getId(),
                'lpItemId' => $incomingData['item_id']
            ]
        );

        if (empty($lpItemView)) {
            throw new \Exception(
                "LP item view not found for view ({$lpView->getId()}) and item ({$incomingData['item_id']})"
            );
        }

        $lpItemView
            ->setMaxScore('100')
            ->setStartTime($incomingData['start_time'])
            ->setTotalTime($incomingData['total_time']);

        $em->persist($lpItemView);
        $em->flush();

        return $lpItemView->getId();
    }
}
