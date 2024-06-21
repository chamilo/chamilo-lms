<?php

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiSharedStatement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiSharedStatement>
 *
 * @method XApiSharedStatement|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiSharedStatement|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiSharedStatement[]    findAll()
 * @method XApiSharedStatement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiSharedStatementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiSharedStatement::class);
    }
}
