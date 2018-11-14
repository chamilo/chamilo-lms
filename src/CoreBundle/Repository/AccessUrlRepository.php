<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class AccessUrlRepository.
 *
 * @package Chamilo\CoreBundle\Repository
 */
class AccessUrlRepository extends ServiceEntityRepository
{
    /**
     * AccessUrlRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessUrl::class);
    }

    /**
     * Select the first access_url ID in the list as a default setting for
     * the creation of new users.
     *
     * @return mixed
     */
    public function getFirstId()
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('MIN (a.id)');
        $q = $qb->getQuery();

        return $q->execute();
    }
}
