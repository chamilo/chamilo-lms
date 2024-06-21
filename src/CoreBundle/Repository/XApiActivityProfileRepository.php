<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiActivityProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiActivityProfile>
 *
 * @method XApiActivityProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiActivityProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiActivityProfile[]    findAll()
 * @method XApiActivityProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiActivityProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiActivityProfile::class);
    }
}
