<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiInternalLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiInternalLog>
 *
 * @method XApiInternalLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiInternalLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiInternalLog[]    findAll()
 * @method XApiInternalLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiInternalLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiInternalLog::class);
    }
}
