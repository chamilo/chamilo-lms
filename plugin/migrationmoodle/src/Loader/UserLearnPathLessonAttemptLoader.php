<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

/**
 * Class UserLearnPathLessonAttemptLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserLearnPathLessonAttemptLoader extends UserLearnPathLessonBranchLoader
{
    /**
     * @param array $incomingData
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $em = \Database::getManager();

        $itemViewId = parent::load($incomingData);

        $itemView = $em->find('ChamiloCourseBundle:CLpItemView', $itemViewId);

        if ((bool) $incomingData['is_correct']) {
            $itemView->setScore(
                $itemView->getMaxScore()
            );
        } else {
            $itemView->setScore(0);
        }

        $em->persist($itemView);
        $em->flush();

        return $itemViewId;
    }
}
