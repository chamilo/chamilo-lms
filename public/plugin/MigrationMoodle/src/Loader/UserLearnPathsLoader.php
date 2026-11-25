<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
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
        $em = \Database::getManager();

        $user = $em->find(User::class, (int) $incomingData['user_id']);

        if (!$user) {
            throw new \Exception("User ({$incomingData['user_id']}) not found.");
        }

        $subscriptions = $em->createQuery(
            'SELECT IDENTITY(srcru.course) AS courseId, IDENTITY(srcru.session) AS sessionId
                FROM '.SessionRelCourseRelUser::class.' srcru
                WHERE srcru.user = :userId'
        )
            ->setParameter('userId', (int) $incomingData['user_id'])
            ->getArrayResult()
        ;

        foreach ($subscriptions as $subscription) {
            // A learning path belongs to a course through the links of its resource node (c_lp has no c_id).
            $lps = $em->createQuery(
                'SELECT DISTINCT lp
                    FROM '.CLp::class.' lp
                    INNER JOIN lp.resourceNode rn
                    INNER JOIN rn.resourceLinks rl
                    WHERE IDENTITY(rl.course) = :courseId AND lp.lpType = 1'
            )
                ->setParameter('courseId', (int) $subscription['courseId'])
                ->getResult()
            ;

            foreach ($lps as $lp) {
                $lpView = (new CLpView())
                    ->setCourse($em->getReference(Course::class, (int) $subscription['courseId']))
                    ->setLp($lp)
                    ->setUser($user)
                    ->setViewCount(1)
                    ->setSession($em->getReference(Session::class, (int) $subscription['sessionId']))
                    ->setLastItem(0)
                ;
                $em->persist($lpView);

                $lpItems = $em->createQuery(
                    'SELECT lpi
                        FROM '.CLpItem::class.' lpi
                        WHERE lpi.lp = :lpId
                        ORDER BY lpi.parent ASC, lpi.displayOrder ASC'
                )
                    ->setParameter('lpId', (int) $lp->getIid())
                    ->getResult()
                ;

                foreach ($lpItems as $lpItem) {
                    $lpItemView = (new CLpItemView())
                        ->setItem($lpItem)
                        ->setView($lpView)
                        ->setViewCount(1)
                        ->setStatus('not attempted')
                        ->setStartTime(0)
                        ->setTotalTime(0)
                        ->setScore(0)
                    ;

                    if (null !== $lpItem->getMaxScore()) {
                        $lpItemView->setMaxScore((string) $lpItem->getMaxScore());
                    }

                    $em->persist($lpItemView);
                }

                $em->flush();
            }
        }

        return $incomingData['user_id'];
    }
}
