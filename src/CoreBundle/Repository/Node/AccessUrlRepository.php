<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class AccessUrlRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessUrl::class);
    }

    /**
     * Select the first access_url ID in the list as a default setting for
     * the creation of new users.
     */
    public function getFirstId(): int
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('MIN (a.id)');

        $q = $qb->getQuery();

        try {
            return (int) $q->getSingleScalarResult();
        } catch (NonUniqueResultException|NoResultException $e) {
            return 0;
        }
    }

    /**
     * @return array<int, AccessUrl>
     */
    public function findByUser(User $user): array
    {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('url');

        return $qb
            ->join('url.users', 'users')
            ->where($qb->expr()->eq('users.user', ':user'))
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getResult()
        ;
    }

    public function getUserActivePortals(User $user): QueryBuilder
    {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('url');

        return $qb
            ->join('url.users', 'users')
            ->where($qb->expr()->eq('users.user', ':user'))
            ->andWhere($qb->expr()->eq('url.active', true))
            ->andWhere($qb->expr()->neq('url.isLoginOnly', true))
            ->setParameter('user', $user->getId())
        ;
    }

    public function getOnlyLoginAccessUrl(): ?AccessUrl
    {
        return $this->findOneBy(['isLoginOnly' => true]);
    }
}
