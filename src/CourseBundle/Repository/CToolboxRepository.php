<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CToolbox;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResourceRepository<CToolbox>
 */
final class CToolboxRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CToolbox::class);
    }

    /**
     * @return CToolbox[]
     */
    public function findAllInContext(Course $course, ?Session $session = null, ?CGroup $group = null): array
    {
        return $this->getResourcesByCourse($course, $session, $group, null, false, true)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneInContext(
        int $id,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null,
    ): ?CToolbox {
        if ($id <= 0) {
            return null;
        }

        $queryBuilder = $this->getResourcesByCourse($course, $session, $group, null, false);
        $queryBuilder
            ->andWhere('resource.iid = :toolboxId')
            ->setParameter('toolboxId', $id)
            ->setMaxResults(1)
        ;

        $result = $queryBuilder->getQuery()->getOneOrNullResult();

        return $result instanceof CToolbox ? $result : null;
    }
}
