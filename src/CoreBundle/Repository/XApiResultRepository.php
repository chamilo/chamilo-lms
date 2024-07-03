<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiResult>
 *
 * @method XApiResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiResult[]    findAll()
 * @method XApiResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiResult::class);
    }
}
