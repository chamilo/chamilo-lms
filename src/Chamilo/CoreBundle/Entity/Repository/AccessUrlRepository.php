<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class AccessUrlRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class AccessUrlRepository extends EntityRepository
{
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
