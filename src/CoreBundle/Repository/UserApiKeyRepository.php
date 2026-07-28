<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\UserApiKey;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserApiKey>
 */
final class UserApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserApiKey::class);
    }

    public function findForUserAndAccessUrl(int $userId, int $accessUrlId, string $service): ?UserApiKey
    {
        return $this->createQueryBuilder('apiKey')
            ->andWhere('apiKey.userId = :userId')
            ->andWhere('apiKey.accessUrlId = :accessUrlId')
            ->andWhere('apiKey.apiService = :service')
            ->setParameter('userId', $userId)
            ->setParameter('accessUrlId', $accessUrlId)
            ->setParameter('service', $service)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findActiveByHashAndAccessUrl(
        string $hash,
        int $accessUrlId,
        string $service,
        DateTime $now,
    ): ?UserApiKey {
        return $this->createQueryBuilder('apiKey')
            ->andWhere('apiKey.apiKey = :hash')
            ->andWhere('apiKey.accessUrlId = :accessUrlId')
            ->andWhere('apiKey.apiService = :service')
            ->andWhere('apiKey.revokedAt IS NULL')
            ->andWhere('(apiKey.validityStartDate IS NULL OR apiKey.validityStartDate <= :now)')
            ->andWhere('(apiKey.validityEndDate IS NULL OR apiKey.validityEndDate > :now)')
            ->setParameter('hash', $hash)
            ->setParameter('accessUrlId', $accessUrlId)
            ->setParameter('service', $service)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function touchLastUsed(UserApiKey $apiKey, DateTime $now): void
    {
        $lastUsedAt = $apiKey->getLastUsedAt();
        if (null !== $lastUsedAt && $lastUsedAt->getTimestamp() > $now->getTimestamp() - 300) {
            return;
        }

        $this->createQueryBuilder('apiKey')
            ->update()
            ->set('apiKey.lastUsedAt', ':now')
            ->andWhere('apiKey.id = :id')
            ->setParameter('now', $now)
            ->setParameter('id', $apiKey->getId())
            ->getQuery()
            ->execute()
        ;

        $apiKey->setLastUsedAt($now);
    }
}
