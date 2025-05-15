<?php

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CGroupRelUsergroup;
use Doctrine\Persistence\ManagerRegistry;
use Chamilo\CoreBundle\Repository\ResourceRepository;

class CGroupRelUsergroupRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CGroupRelUsergroup::class);
    }
}
