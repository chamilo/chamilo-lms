<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiActivityState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiActivityState>
 *
 * @method XApiActivityState|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiActivityState|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiActivityState[]    findAll()
 * @method XApiActivityState[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiActivityStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiActivityState::class);
    }
}
