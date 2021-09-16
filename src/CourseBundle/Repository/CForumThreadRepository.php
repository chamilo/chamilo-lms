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

    public function getForumThread(string $title, Course $course, Session $session = null): ?CForumThread
    {
        $qb = $this->getResourcesByCourse($course, $session);
        $qb
            ->andWhere('resource.threadTitle = :title')
            ->setParameter('title', $title)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllByCourse(
        Course $course,
        Session $session = null,
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
        $posts = $resource->getPosts();
        foreach ($posts as $post) {
            parent::delete($post);
        }

        parent::delete($resource);
    }
}
