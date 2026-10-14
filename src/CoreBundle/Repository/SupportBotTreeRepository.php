<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SupportBotTree;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class SupportBotTreeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportBotTree::class);
    }

    public function findPublishedForAccessUrl(AccessUrl $accessUrl): ?SupportBotTree
    {
        $tree = $this->createQueryBuilder('tree')
            ->andWhere('tree.status = :status')
            ->andWhere('IDENTITY(tree.accessUrl) = :accessUrlId')
            ->setParameter('status', SupportBotTree::STATUS_PUBLISHED)
            ->setParameter('accessUrlId', (int) $accessUrl->getId())
            ->orderBy('tree.version', 'DESC')
            ->addOrderBy('tree.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($tree instanceof SupportBotTree) {
            return $tree;
        }

        return $this->createQueryBuilder('tree')
            ->andWhere('tree.status = :status')
            ->andWhere('tree.accessUrl IS NULL')
            ->setParameter('status', SupportBotTree::STATUS_PUBLISHED)
            ->orderBy('tree.version', 'DESC')
            ->addOrderBy('tree.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
