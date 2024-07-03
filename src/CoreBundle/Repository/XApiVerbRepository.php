<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiVerb;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiVerb>
 *
 * @method XApiVerb|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiVerb|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiVerb[]    findAll()
 * @method XApiVerb[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiVerbRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiVerb::class);
    }
}
