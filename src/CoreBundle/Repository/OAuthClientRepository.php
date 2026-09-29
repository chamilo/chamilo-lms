<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\OAuthClient;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OAuthClient>
 */
final class OAuthClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthClient::class);
    }

    public function findActiveByClientIdAndAccessUrl(string $clientId, int $accessUrlId): ?OAuthClient
    {
        return $this->createQueryBuilder('client')
            ->andWhere('client.clientId = :clientId')
            ->andWhere('client.accessUrlId = :accessUrlId')
            ->andWhere('client.revokedAt IS NULL')
            ->setParameter('clientId', $clientId)
            ->setParameter('accessUrlId', $accessUrlId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countRecentRegistrationsByIp(string $ip, DateTime $since): int
    {
        return (int) $this->createQueryBuilder('client')
            ->select('COUNT(client.id)')
            ->andWhere('client.registrationIp = :ip')
            ->andWhere('client.createdAt > :since')
            ->setParameter('ip', $ip)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function touchLastUsed(OAuthClient $client, DateTime $now): void
    {
        $lastUsedAt = $client->getLastUsedAt();
        if (null !== $lastUsedAt && $lastUsedAt->getTimestamp() > $now->getTimestamp() - 300) {
            return;
        }

        $this->createQueryBuilder('client')
            ->update()
            ->set('client.lastUsedAt', ':now')
            ->andWhere('client.id = :id')
            ->setParameter('now', $now)
            ->setParameter('id', $client->getId())
            ->getQuery()
            ->execute()
        ;

        $client->setLastUsedAt($now);
    }

    /**
     * @return array<int, OAuthClient>
     */
    public function findStaleUnusedClients(DateTime $before): array
    {
        return $this->createQueryBuilder('client')
            ->andWhere('client.createdAt < :before')
            ->andWhere('client.lastUsedAt IS NULL')
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult()
        ;
    }
}
