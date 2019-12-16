<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

/**
 * Class AccessUrlRepository.
 */
class AccessUrlRepository extends ResourceRepository
{
    /**
     * Select the first access_url ID in the list as a default setting for
     * the creation of new users.
     *
     * @return mixed
     */
    public function getFirstId()
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->select('MIN (a.id)');
        $q = $qb->getQuery();

        return $q->execute();
    }
}
