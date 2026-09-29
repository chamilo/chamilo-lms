<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\OAuthAuthorizationCode;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OAuthAuthorizationCode>
 */
final class OAuthAuthorizationCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthAuthorizationCode::class);
    }

    public function findOneByHash(string $hash): ?OAuthAuthorizationCode
    {
        return $this->createQueryBuilder('code')
            ->andWhere('code.codeHash = :hash')
            ->setParameter('hash', $hash)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Atomically marks the code as used. Returns false if it was already used
     * (or does not exist) — a conditional UPDATE, not read-then-write, so two
     * concurrent /token calls against the same code cannot both succeed.
     */
    public function consumeAtomically(int $id, DateTime $now): bool
    {
        $affected = $this->createQueryBuilder('code')
            ->update()
            ->set('code.usedAt', ':now')
            ->andWhere('code.id = :id')
            ->andWhere('code.usedAt IS NULL')
            ->setParameter('now', $now)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute()
        ;

        return 1 === $affected;
    }

    public function deleteExpired(DateTime $before): int
    {
        return (int) $this->createQueryBuilder('code')
            ->delete()
            ->andWhere('code.expiresAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;
    }
}
