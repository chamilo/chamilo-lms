<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Traits\NonResourceRepository;
use Chamilo\CourseBundle\Entity\CThematicPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class CThematicPlanRepository extends ServiceEntityRepository
{
    use NonResourceRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CThematicPlan::class);
    }
}
