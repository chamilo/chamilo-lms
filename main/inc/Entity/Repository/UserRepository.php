<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 */
class UserRepository extends EntityRepository
{

    public function getUsers($limit = null)
    {
        $qb = $this->createQueryBuilder('u')
                   ->select('u')
                   ->addOrderBy('u.username', 'DESC');


        return $qb;
    }

    public function getSubscribedUsers($limit = null)
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u')
            ->addOrderBy('u.username', 'DESC');
        return $qb;
    }
}