<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Traits\Repository\ORM\NestedTreeRepositoryTrait;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Traits\NonResourceRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class CLpItemRepository extends ServiceEntityRepository
{
    use NestedTreeRepositoryTrait;
    use NonResourceRepository;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CLpItem::class);

        $this->initializeTreeRepository($this->getEntityManager(), $this->getClassMetadata());
    }
}
