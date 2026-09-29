<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\OAuthRefreshToken;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OAuthRefreshToken>
 */
final class OAuthRefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthRefreshToken::class);
    }

    public function findOneByHash(string $hash): ?OAuthRefreshToken
    {
        return $this->createQueryBuilder('token')
            ->andWhere('token.tokenHash = :hash')
            ->setParameter('hash', $hash)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Atomically marks this generation as rotated. Returns false if it was
     * already rotated or revoked (or does not exist) — a conditional UPDATE,
     * not read-then-write, so a race between two refresh attempts against the
     * same token is detectable as reuse rather than silently both succeeding.
     */
    public function rotateAtomically(int $id, DateTime $now): bool
    {
        $affected = $this->createQueryBuilder('token')
            ->update()
            ->set('token.rotatedAt', ':now')
            ->andWhere('token.id = :id')
            ->andWhere('token.rotatedAt IS NULL')
            ->andWhere('token.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute()
        ;

        return 1 === $affected;
    }

    public function revokeFamily(string $grantId, string $reason, DateTime $now): int
    {
        return (int) $this->createQueryBuilder('token')
            ->update()
            ->set('token.revokedAt', ':now')
            ->set('token.revokedReason', ':reason')
            ->andWhere('token.grantId = :grantId')
            ->andWhere('token.revokedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('reason', $reason)
            ->setParameter('grantId', $grantId)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * The "Connected apps" query: one live (non-rotated, non-revoked,
     * non-expired) refresh-token row per grant IS the grant record.
     *
     * @return array<int, OAuthRefreshToken>
     */
    public function findActiveGrantsForUserAndAccessUrl(int $userId, int $accessUrlId, DateTime $now): array
    {
        return $this->createQueryBuilder('token')
            ->andWhere('token.user = :userId')
            ->andWhere('token.accessUrlId = :accessUrlId')
            ->andWhere('token.revokedAt IS NULL')
            ->andWhere('token.rotatedAt IS NULL')
            ->andWhere('token.expiresAt > :now')
            ->andWhere('token.absoluteExpiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('accessUrlId', $accessUrlId)
            ->setParameter('now', $now)
            ->orderBy('token.consentedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Ownership-scoped lookup for the revoke endpoint — never look up a grant
     * by id alone, always scoped to the requesting user and portal.
     */
    public function findActiveGrantByIdForUser(
        string $grantId,
        int $userId,
        int $accessUrlId,
        DateTime $now,
    ): ?OAuthRefreshToken {
        return $this->createQueryBuilder('token')
            ->andWhere('token.grantId = :grantId')
            ->andWhere('token.user = :userId')
            ->andWhere('token.accessUrlId = :accessUrlId')
            ->andWhere('token.revokedAt IS NULL')
            ->andWhere('token.rotatedAt IS NULL')
            ->andWhere('token.expiresAt > :now')
            ->andWhere('token.absoluteExpiresAt > :now')
            ->setParameter('grantId', $grantId)
            ->setParameter('userId', $userId)
            ->setParameter('accessUrlId', $accessUrlId)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function deleteExpired(DateTime $before): int
    {
        return (int) $this->createQueryBuilder('token')
            ->delete()
            ->andWhere('token.absoluteExpiresAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;
    }
}
