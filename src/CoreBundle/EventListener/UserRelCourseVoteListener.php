<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\UserRelCourseVote;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;

class UserRelCourseVoteListener
{
    public function postPersist(UserRelCourseVote $vote, LifecycleEventArgs $args): void
    {
        $this->updateCoursePopularity($vote, $args->getEntityManager());
    }

    public function postUpdate(UserRelCourseVote $vote, LifecycleEventArgs $args): void
    {
        $this->updateCoursePopularity($vote, $args->getEntityManager());
    }

    private function updateCoursePopularity(UserRelCourseVote $vote, EntityManagerInterface $entityManager): void
    {
        $course = $vote->getCourse();

        $uniqueUsers = $entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT v.user)')
            ->from(UserRelCourseVote::class, 'v')
            ->where('v.course = :course')
            ->setParameter('course', $course)
            ->getQuery()
            ->getSingleScalarResult();

        $course->setPopularity((int) $uniqueUsers);
        $entityManager->persist($course);
        $entityManager->flush();
    }
}
