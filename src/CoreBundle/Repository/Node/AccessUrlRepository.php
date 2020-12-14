<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class AccessUrlRepository.
 */
class AccessUrlRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessUrl::class);
    }

    /**
     * Select the first access_url ID in the list as a default setting for
     * the creation of new users.
     */
    public function getFirstId()
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('MIN (a.id)');
        $q = $qb->getQuery();

        return $q->execute();
    }
}
