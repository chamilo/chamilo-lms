<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CForumThread;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class CForumThreadRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CForumThread::class);
    }

    public function getForumThread(string $title, Course $course, ?Session $session = null): ?CForumThread
    {
        $qb = $this->getResourcesByCourse($course, $session);
        $qb
            ->andWhere('resource.title = :title')
            ->setParameter('title', $title)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllByCourse(
        Course $course,
        ?Session $session = null,
        ?string $title = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session);

        $this->addTitleQueryBuilder($title, $qb);

        return $qb;
    }

    public function increaseView(CForumThread $thread): void
    {
        $thread->setThreadViews($thread->getThreadViews() + 1);
        $em = $this->getEntityManager();
        $em->persist($thread);
        $em->flush();
    }

    public function delete(ResourceInterface $resource): void
    {
        /** @var CForumThread $resource */
        $forum = $resource->getForum();
        if (null !== $forum) {
            $resource->setParent($forum);
        }

        // Only repair the resource-node hierarchy here; do NOT delete post by post.
        // parent::delete() already removes the whole descendant tree in a SINGLE flush,
        // and that is deliberate — see ResourceRepository::scheduleForRemoval(). One
        // flush per post re-enters ResourceDoctrineListener's own persist-and-flush
        // cycle, and those interleaved flushes make the UnitOfWork report an
        // already-removed post as a new entity through CForumPost::$postParent, so the
        // delete aborted with a 500 on any thread whose posts form a parent chain
        // (a reply, plus a quote of that reply).
        foreach ($resource->getPosts() as $post) {
            $post->setParent($resource);

            foreach ($post->getAttachments() as $attachment) {
                $attachment->setParent($post);
            }
        }

        parent::delete($resource);
    }

    public function getThreadsBySubscriptions(int $userId, int $courseId): array
    {
        $qb = $this->createQueryBuilder('thread')
            ->where('thread.iid IN (
            SELECT fn.threadId
            FROM Chamilo\CourseBundle\Entity\CForumNotification fn
            WHERE fn.cId = :courseId AND fn.userId = :userId
        )')
            ->setParameter('courseId', $courseId)
            ->setParameter('userId', $userId)
        ;

        return $qb->getQuery()->getResult();
    }
}
