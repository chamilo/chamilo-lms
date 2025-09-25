<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CWiki;
use Doctrine\Persistence\ManagerRegistry;

final class CWikiRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CWiki::class);
    }

    /**
     * Returns per-user edit counts within a course/group/session scope.
     * It replicates the legacy "GROUP BY user_id" with optional group/session filters.
     *
     * @return array<int, array{userId:int, userIp:string, numEdits:int}>
     */
    public function countEditsByUser(int $courseId, ?int $groupId, ?int $sessionId): array
    {
        $qb = $this->createQueryBuilder('w')
            ->select('w.userId AS userId, w.userIp AS userIp, COUNT(w.iid) AS numEdits')
            ->where('w.cId = :cId')
            ->setParameter('cId', $courseId)
        ;

        // Group filter
        if (null !== $groupId) {
            $qb->andWhere('w.groupId = :gid')->setParameter('gid', $groupId);
        }

        // Session filter
        if (null !== $sessionId) {
            $qb->andWhere('w.sessionId = :sid')->setParameter('sid', $sessionId);
        }

        return $qb
            ->groupBy('w.userId, w.userIp')
            ->orderBy('numEdits', 'DESC')
            ->getQuery()
            ->getArrayResult()
        ;
    }
}
