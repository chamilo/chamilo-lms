<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\CatalogueSessionRelAccessUrlRelUsergroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CatalogueSessionRelAccessUrlRelUsergroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CatalogueSessionRelAccessUrlRelUsergroup::class);
    }

    /**
     * Returns the list of session IDs visible for a given access URL and usergroup(s).
     *
     * Rules applied:
     * - If usergroup is NULL => visible to all users on that access URL.
     * - If usergroup is not NULL => visible only to users belonging to that usergroup.
     * - A session can be assigned to multiple usergroups and will appear once if matched.
     */
    public function findSessionIdsByAccessUrlAndUsergroups(int $accessUrlId, array $usergroupIds): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('DISTINCT IDENTITY(a.session) AS session_id')
            ->where('a.accessUrl = :accessUrlId')
            ->setParameter('accessUrlId', $accessUrlId)
        ;

        if (!empty($usergroupIds)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'a.usergroup IS NULL',
                    'a.usergroup IN (:usergroupIds)'
                )
            )
                ->setParameter('usergroupIds', $usergroupIds)
            ;
        }

        return array_column($qb->getQuery()->getResult(), 'session_id');
    }

    /**
     * Checks if there are any session visibility rules defined for a given access URL.
     *
     * If there are no entries, the default behavior is to show all sessions.
     */
    public function hasRecordsForAccessUrl(int $accessUrlId): bool
    {
        return null !== $this->createQueryBuilder('a')
            ->select('1')
            ->where('a.accessUrl = :accessUrlId')
            ->setParameter('accessUrlId', $accessUrlId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
