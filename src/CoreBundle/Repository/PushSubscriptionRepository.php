<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\PushSubscription;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PushSubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PushSubscription::class);
    }

    /**
     * Find all subscriptions for a specific user.
     *
     * @return PushSubscription[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a subscription by its endpoint.
     */
    public function findOneByEndpoint(string $endpoint): ?PushSubscription
    {
        return $this->createQueryBuilder('p')
            ->where('p.endpoint = :endpoint')
            ->setParameter('endpoint', $endpoint)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Remove all subscriptions for a user (e.g. on logout).
     */
    public function removeAllByUser(User $user): void
    {
        $qb = $this->createQueryBuilder('p');
        $qb->delete()
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Remove a subscription by endpoint.
     */
    public function removeByEndpoint(string $endpoint): void
    {
        $qb = $this->createQueryBuilder('p');
        $qb->delete()
            ->where('p.endpoint = :endpoint')
            ->setParameter('endpoint', $endpoint)
            ->getQuery()
            ->execute();
    }
}
