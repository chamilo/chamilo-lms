<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\OAuthAccessToken;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OAuthAccessToken>
 */
final class OAuthAccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthAccessToken::class);
    }

    public function findActiveByHash(string $hash, DateTime $now): ?OAuthAccessToken
    {
        return $this->createQueryBuilder('token')
            ->andWhere('token.tokenHash = :hash')
            ->andWhere('token.revokedAt IS NULL')
            ->andWhere('token.expiresAt > :now')
            ->setParameter('hash', $hash)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function touchLastUsed(OAuthAccessToken $token, DateTime $now): void
    {
        $lastUsedAt = $token->getLastUsedAt();
        if (null !== $lastUsedAt && $lastUsedAt->getTimestamp() > $now->getTimestamp() - 300) {
            return;
        }

        $this->createQueryBuilder('token')
            ->update()
            ->set('token.lastUsedAt', ':now')
            ->andWhere('token.id = :id')
            ->setParameter('now', $now)
            ->setParameter('id', $token->getId())
            ->getQuery()
            ->execute()
        ;

        $token->setLastUsedAt($now);
    }

    public function revokeByGrantId(string $grantId, DateTime $now): int
    {
        return (int) $this->createQueryBuilder('token')
            ->update()
            ->set('token.revokedAt', ':now')
            ->andWhere('token.grantId = :grantId')
            ->andWhere('token.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('grantId', $grantId)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return array<int, OAuthAccessToken>
     */
    public function findActiveForUserAndAccessUrl(int $userId, int $accessUrlId, DateTime $now): array
    {
        return $this->createQueryBuilder('token')
            ->andWhere('token.user = :userId')
            ->andWhere('token.accessUrlId = :accessUrlId')
            ->andWhere('token.revokedAt IS NULL')
            ->andWhere('token.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('accessUrlId', $accessUrlId)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;
    }

    public function deleteExpired(DateTime $before): int
    {
        return (int) $this->createQueryBuilder('token')
            ->delete()
            ->andWhere('token.expiresAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;
    }
}
