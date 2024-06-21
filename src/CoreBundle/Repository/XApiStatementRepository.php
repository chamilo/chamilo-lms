<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiStatement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiStatement>
 *
 * @method XApiStatement|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiStatement|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiStatement[]    findAll()
 * @method XApiStatement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiStatementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiStatement::class);
    }
}
