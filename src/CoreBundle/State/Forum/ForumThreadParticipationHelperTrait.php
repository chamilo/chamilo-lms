<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\ForumParticipationSettingsHelper;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Requires ForumStateHelperTrait in the using class (isTeacher()).
 */
trait ForumThreadParticipationHelperTrait
{
    /**
     * Whether the current user only sees the first post of the threads of this course until they
     * posted in them. Admins, teachers, session coaches and group tutors moderate the forum, so they
     * are never restricted.
     */
    private function areThreadRepliesHiddenUntilParticipation(
        ForumParticipationSettingsHelper $participationSettings,
        Security $security,
        Course $course,
    ): bool {
        if (!$participationSettings->areRepliesHiddenUntilParticipation($course)) {
            return false;
        }

        return !$this->isTeacher($security) && !$security->isGranted('ROLE_CURRENT_COURSE_GROUP_TEACHER');
    }

    /**
     * Posts waiting for moderation count too: the user did take part in the thread.
     */
    private function countUserPostsInThread(EntityManagerInterface $entityManager, CForumThread $thread, User $user): int
    {
        return (int) $entityManager->createQueryBuilder()
            ->select('COUNT(p.iid)')
            ->from(CForumPost::class, 'p')
            ->andWhere('IDENTITY(p.thread) = :threadId')
            ->andWhere('IDENTITY(p.user) = :userId')
            ->setParameter('threadId', $thread->getIid(), Types::INTEGER)
            ->setParameter('userId', $user->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
