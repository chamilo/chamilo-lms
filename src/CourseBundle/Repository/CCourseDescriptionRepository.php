<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\Persistence\ManagerRegistry;

final class CCourseDescriptionRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CCourseDescription::class);
    }

    public function findByTypeInCourse(int $type, Course $course, Session $session = null, CGroup $group = null)
    {
        $qb = $this->createQueryBuilder('d');
        $qb
            ->innerJoin('d.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->where('d.descriptionType = :type')
            ->setParameter('type', $type);

        $this->addCourseSessionGroupQueryBuilder($course, $session, $group, $qb);

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
