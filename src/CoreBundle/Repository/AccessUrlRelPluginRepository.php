<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrlRelPlugin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessUrlRelPlugin>
 */
class AccessUrlRelPluginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessUrlRelPlugin::class);
    }

    public function findOneByPluginName(string $pluginTitle, int $accessUrlId): ?AccessUrlRelPlugin
    {
        return $this->createQueryBuilder('rel')
            ->join('rel.plugin', 'p')
            ->andWhere('p.title = :pluginTitle')
            ->andWhere('rel.url = :accessUrlId')
            ->setParameter('pluginTitle', $pluginTitle)
            ->setParameter('accessUrlId', $accessUrlId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
