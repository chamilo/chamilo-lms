<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\UserRelCourseVote;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class UserRelCourseVoteListener
{
    public function postPersist(UserRelCourseVote $vote, PostPersistEventArgs $args): void
    {
        $this->updateCoursePopularity($vote, $args->getObjectManager());
    }

    public function postUpdate(UserRelCourseVote $vote, PostUpdateEventArgs $args): void
    {
        $this->updateCoursePopularity($vote, $args->getObjectManager());
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    private function updateCoursePopularity(UserRelCourseVote $vote, EntityManagerInterface $entityManager): void
    {
        $course = $vote->getCourse();

        if (!$course) {
            return;
        }

        $uniqueUsers = (int) $entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT v.user)')
            ->from(UserRelCourseVote::class, 'v')
            ->where('v.course = :course')
            ->setParameter('course', $course->getId())
            ->getQuery()
            ->getSingleScalarResult();

        $course->setPopularity($uniqueUsers);
        $entityManager->persist($course);
        $entityManager->flush();
    }
}
