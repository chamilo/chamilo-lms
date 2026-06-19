<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AiRequests;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AiRequestsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiRequests::class);
    }

    public function save(AiRequests $request): void
    {
        $this->_em->persist($request);
        $this->_em->flush();
    }

    public function findLatestUnlinkedToolRequestSince(
        int $userId,
        string $toolName,
        string $aiProvider,
        DateTimeInterface $requestedAfter
    ): ?AiRequests {
        return $this->createQueryBuilder('r')
            ->andWhere('r.userId = :userId')
            ->andWhere('r.toolName = :toolName')
            ->andWhere('r.aiProvider = :aiProvider')
            ->andWhere('r.toolItemId IS NULL')
            ->andWhere('r.requestedAt >= :requestedAfter')
            ->setParameter('userId', $userId)
            ->setParameter('toolName', $toolName)
            ->setParameter('aiProvider', $aiProvider)
            ->setParameter('requestedAfter', $requestedAfter)
            ->orderBy('r.requestedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
