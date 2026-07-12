<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CWiki;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
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
        $queryBuilder = $this->createQueryBuilder('w')
            ->select('w.userId AS userId, w.userIp AS userIp, COUNT(w.iid) AS numEdits')
            ->where('w.cId = :courseId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
        ;

        if (null !== $groupId) {
            $queryBuilder
                ->andWhere('w.groupId = :groupId')
                ->setParameter('groupId', $groupId, Types::INTEGER)
            ;
        }

        if (null !== $sessionId) {
            $queryBuilder
                ->andWhere('w.sessionId = :sessionId')
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ;
        }

        return $queryBuilder
            ->groupBy('w.userId, w.userIp')
            ->orderBy('COUNT(w.iid)', 'DESC')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function findFirstVersionInContext(
        int $courseId,
        string $reflink,
        int $groupId,
        int $sessionId,
    ): ?CWiki {
        $queryBuilder = $this->createQueryBuilder('w')
            ->andWhere('w.cId = :courseId')
            ->andWhere('w.reflink = :reflink')
            ->andWhere('COALESCE(w.groupId, 0) = :groupId')
            ->andWhere('COALESCE(w.sessionId, 0) = :sessionId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('reflink', $reflink, Types::STRING)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->orderBy('w.version', 'ASC')
            ->addOrderBy('w.iid', 'ASC')
            ->setMaxResults(1)
        ;

        /** @var CWiki|null */
        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findLatestVersionInContext(
        int $courseId,
        int $pageId,
        int $groupId,
        int $sessionId,
    ): ?CWiki {
        $queryBuilder = $this->createQueryBuilder('w')
            ->andWhere('w.cId = :courseId')
            ->andWhere('w.pageId = :pageId')
            ->andWhere('COALESCE(w.groupId, 0) = :groupId')
            ->andWhere('COALESCE(w.sessionId, 0) = :sessionId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('pageId', $pageId, Types::INTEGER)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->orderBy('w.version', 'DESC')
            ->addOrderBy('w.iid', 'DESC')
            ->setMaxResults(1)
        ;

        /** @var CWiki|null */
        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function reflinkExistsInContext(
        int $courseId,
        string $reflink,
        int $groupId,
        int $sessionId,
    ): bool {
        $count = $this->createQueryBuilder('w')
            ->select('COUNT(w.iid)')
            ->andWhere('w.cId = :courseId')
            ->andWhere('w.reflink = :reflink')
            ->andWhere('COALESCE(w.groupId, 0) = :groupId')
            ->andWhere('COALESCE(w.sessionId, 0) = :sessionId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('reflink', $reflink, Types::STRING)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $count > 0;
    }

    public function findContextAddLock(int $courseId, int $groupId, int $sessionId): int
    {
        $queryBuilder = $this->createQueryBuilder('w')
            ->select('w.addlock AS addlock')
            ->andWhere('w.cId = :courseId')
            ->andWhere('COALESCE(w.groupId, 0) = :groupId')
            ->andWhere('COALESCE(w.sessionId, 0) = :sessionId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->orderBy('w.version', 'ASC')
            ->addOrderBy('w.iid', 'ASC')
            ->setMaxResults(1)
        ;

        /** @var array{addlock:int|string}|null $row */
        $row = $queryBuilder->getQuery()->getOneOrNullResult();

        return null === $row ? 1 : (int) $row['addlock'];
    }

    /**
     * @param array<int, string> $reflinks
     *
     * @return array<int, string>
     */
    public function findExistingReflinks(
        int $courseId,
        array $reflinks,
        int $groupId,
        int $sessionId,
    ): array {
        if ([] === $reflinks) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('w')
            ->select('DISTINCT w.reflink AS reflink')
            ->andWhere('w.cId = :courseId')
            ->andWhere('w.reflink IN (:reflinks)')
            ->andWhere('COALESCE(w.groupId, 0) = :groupId')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('reflinks', $reflinks, ArrayParameterType::STRING)
            ->setParameter('groupId', $groupId, Types::INTEGER)
        ;

        if ($sessionId > 0) {
            $queryBuilder
                ->andWhere('(COALESCE(w.sessionId, 0) = 0 OR w.sessionId = :sessionId)')
                ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('COALESCE(w.sessionId, 0) = 0');
        }

        /** @var array<int, array{reflink:string}> $rows */
        $rows = $queryBuilder->getQuery()->getArrayResult();

        return array_values(array_map(
            static fn (array $row): string => (string) $row['reflink'],
            $rows,
        ));
    }
}
