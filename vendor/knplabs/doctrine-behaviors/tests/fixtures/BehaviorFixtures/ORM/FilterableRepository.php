<?php

namespace BehaviorFixtures\ORM;

use Knp\DoctrineBehaviors\ORM\Filterable;
use Doctrine\ORM\EntityRepository;

/**
 * @author     Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class FilterableRepository extends EntityRepository
{
    use Filterable\FilterableRepository;

    public function getILikeFilterColumns()
    {
        return [];
    }

    public function getLikeFilterColumns()
    {
        return ['e:name'];
    }

    public function getEqualFilterColumns()
    {
        return ['e:code'];
    }

    public function getInFilterColumns()
    {
        return [];
    }
}

