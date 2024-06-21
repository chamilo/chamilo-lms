<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiObject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiObject>
 *
 * @method XApiObject|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiObject|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiObject[]    findAll()
 * @method XApiObject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiObjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiObject::class);
    }
}
