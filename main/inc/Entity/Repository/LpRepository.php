<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LpRepository
 *
 */
class LpRepository extends EntityRepository
{
    public function getSubscribedStudentsInLP($course_id, $lp_id)
    {
        //$item = $this->getEntityManager('ItemPropertyRepository');
        //return $item->getUsersSubscribedToItem('learnpath', $lp_id, $course_id, 0 ,0);
    }
}