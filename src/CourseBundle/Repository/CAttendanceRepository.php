<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CAttendance;
use Doctrine\Persistence\ManagerRegistry;

final class CAttendanceRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CAttendance::class);
    }

    /**
     * @return CAttendance[]
     */
    public function getAttendanceListForCourse(Course $course, ?Session $session = null): array
    {
        $queryBuilder = $this->getResourcesByCourse($course, $session, null, null, true, true);
        $queryBuilder->andWhere('resource.active = 1');

        return $queryBuilder->getQuery()->getResult();
    }
}
