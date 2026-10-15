<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\OAuthClient;
use Chamilo\CoreBundle\Entity\OAuthClientConsent;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OAuthClientConsent>
 */
final class OAuthClientConsentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthClientConsent::class);
    }

    public function findFirstGrantedAt(int $userId, int $clientId): ?DateTime
    {
        $consent = $this->createQueryBuilder('consent')
            ->andWhere('consent.user = :userId')
            ->andWhere('consent.client = :clientId')
            ->setParameter('userId', $userId)
            ->setParameter('clientId', $clientId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $consent instanceof OAuthClientConsent ? $consent->getFirstGrantedAt() : null;
    }

    public function recordFirstGrant(User $user, OAuthClient $client, DateTime $when): void
    {
        if (null === $user->getId() || null === $client->getId()) {
            return;
        }

        if (null !== $this->findFirstGrantedAt((int) $user->getId(), (int) $client->getId())) {
            return;
        }

        $consent = (new OAuthClientConsent())
            ->setUser($user)
            ->setClient($client)
            ->setFirstGrantedAt($when)
        ;

        $this->getEntityManager()->persist($consent);
    }
}
