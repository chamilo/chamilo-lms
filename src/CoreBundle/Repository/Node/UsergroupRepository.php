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
    public function getGroupsByUser(int $userId, int $relationType = Usergroup::GROUP_USER_PERMISSION_READER, bool $withImage = false): array
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
        $groups = $query->getResult();

        return $groups;
    }

    public function searchGroupsByQuery(string $query): array
    {
        $qb = $this->createQueryBuilder('g');

        if (!empty($query)) {
            $qb->where('g.title LIKE :query OR g.description LIKE :query')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($this->getUseMultipleUrl()) {
            $urlId = $this->getCurrentAccessUrlId();
            $qb->innerJoin('g.urls', 'u')
                ->andWhere('u.accessUrl = :urlId')
                ->setParameter('urlId', $urlId);
        }

        return $qb->getQuery()->getResult();
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
