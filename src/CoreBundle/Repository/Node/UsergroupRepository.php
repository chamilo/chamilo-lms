<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Doctrine\Persistence\ManagerRegistry;

class UsergroupRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usergroup::class);
    }

    /**
     * @param int $userId
     * @param int|array $relationType
     * @param bool $withImage
     * @return array
     */
    public function getGroupsByUser(int $userId, int $relationType = 0, bool $withImage = false): array
    {
        $qb = $this->createQueryBuilder('g')
            ->innerJoin('g.users', 'gu')
            ->where('gu.user = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('g.groupType = :socialClass')
            ->setParameter('socialClass', Usergroup::SOCIAL_CLASS);

        if ($relationType !== 0) {
            if (is_array($relationType)) {
                $qb->andWhere('gu.relationType IN (:relationType)')
                    ->setParameter('relationType', $relationType);
            } else {
                $qb->andWhere('gu.relationType = :relationType')
                    ->setParameter('relationType', $relationType);
            }
        }

        if ($this->getUseMultipleUrl()) {
            $urlId = $this->getCurrentAccessUrlId();
            $qb->innerJoin('g.urls', 'u')
                ->andWhere('u.accessUrl = :urlId')
                ->setParameter('urlId', $urlId);
        }


        $qb->orderBy('g.createdAt', 'DESC');
        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function countMembers(int $usergroupId): int
    {
        $qb = $this->createQueryBuilder('g')
            ->select('count(gu.id)')
            ->innerJoin('g.users', 'gu')
            ->where('g.id = :usergroupId')
            ->setParameter('usergroupId', $usergroupId);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getNewestGroups(int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('g')
            ->select('g, COUNT(gu) AS HIDDEN memberCount')
            ->innerJoin('g.users', 'gu')
            ->where('g.groupType = :socialClass')
            ->setParameter('socialClass', Usergroup::SOCIAL_CLASS)
            ->groupBy('g')
            ->orderBy('g.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($this->getUseMultipleUrl()) {
            $urlId = $this->getCurrentAccessUrlId();
            $qb->innerJoin('g.urls', 'u')
                ->andWhere('u.accessUrl = :urlId')
                ->setParameter('urlId', $urlId);
        }

        return $qb->getQuery()->getResult();
    }


    public function getPopularGroups(int $limit = 6): array
    {
        $qb = $this->createQueryBuilder('g')
            ->select('g, COUNT(gu) as HIDDEN memberCount')
            ->innerJoin('g.users', 'gu')
            ->where('g.groupType = :socialClass')
            ->setParameter('socialClass', Usergroup::SOCIAL_CLASS)
            ->andWhere('gu.relationType IN (:relationTypes)')
            ->setParameter('relationTypes', [
                Usergroup::GROUP_USER_PERMISSION_ADMIN,
                Usergroup::GROUP_USER_PERMISSION_READER,
                Usergroup::GROUP_USER_PERMISSION_HRM
            ])
            ->groupBy('g')
            ->orderBy('memberCount', 'DESC')
            ->setMaxResults($limit);

        if ($this->getUseMultipleUrl()) {
            $urlId = $this->getCurrentAccessUrlId();
            $qb->innerJoin('g.urls', 'u')
                ->andWhere('u.accessUrl = :urlId')
                ->setParameter('urlId', $urlId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findGroupById($id)
    {
        return $this->createQueryBuilder('ug')
            ->where('ug.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchGroups(string $searchTerm): array
    {
        $queryBuilder = $this->createQueryBuilder('g');
        $queryBuilder->where('g.title LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Determines whether to use the multi-URL feature.
     *
     * @return bool True if multi-URLs should be used, false otherwise.
     */
    public function getUseMultipleUrl(): bool
    {
        // TODO: Implement the actual logic to determine if multi-URLs should be used.
        // For now, returning false as a default value.
        return false;
    }

    /**
     * Gets the current access URL ID.
     *
     * @return int The ID of the current access URL.
     */
    public function getCurrentAccessUrlId(): int
    {
        // TODO: Implement the actual logic to obtain the current access URL ID.
        // For now, returning 1 as a default value.
        return 1;
    }


}
