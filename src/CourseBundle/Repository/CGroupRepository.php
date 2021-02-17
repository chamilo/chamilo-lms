<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CGroupRepository.
 */
final class CGroupRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CGroup::class);
    }

    public function findOneByCode(string $code): ?CGroup
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findOneByTitle(string $name): ?CGroup
    {
        return $this->findOneBy(['name' => $name]);
    }
}
