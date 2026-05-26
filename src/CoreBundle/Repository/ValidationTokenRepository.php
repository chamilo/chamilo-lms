<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Helpers\ValidationTokenHelper;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ValidationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ValidationToken::class);
    }

    public function save(ValidationToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ValidationToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findRememberMeToken(int $userId, string $hash): ?ValidationToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->andWhere('t.resourceId = :userId')
            ->andWhere('t.hash = :hash')
            ->setParameter('type', ValidationTokenHelper::TYPE_REMEMBER_ME)
            ->setParameter('userId', $userId)
            ->setParameter('hash', $hash)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function deleteExpiredRememberMeTokens(DateTimeInterface $cutoff): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->andWhere('t.type = :type')
            ->andWhere('t.createdAt < :cutoff')
            ->setParameter('type', ValidationTokenHelper::TYPE_REMEMBER_ME)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute()
        ;
    }

    public function deleteRememberMeTokenById(int $id): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->andWhere('t.type = :type')
            ->andWhere('t.id = :id')
            ->setParameter('type', ValidationTokenHelper::TYPE_REMEMBER_ME)
            ->setParameter('id', $id)
            ->getQuery()
            ->execute()
        ;
    }

    public function deleteRememberMeTokensForUser(int $userId): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->andWhere('t.type = :type')
            ->andWhere('t.resourceId = :userId')
            ->setParameter('type', ValidationTokenHelper::TYPE_REMEMBER_ME)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute()
        ;
    }
}
