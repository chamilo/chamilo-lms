<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CSurvey;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

final class CSurveyRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CSurvey::class);
    }

    public function findAllByCourse(
        Course $course,
        Session $session = null,
        ?string $title = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session);

        $this->addTitleQueryBuilder($title, $qb);

        return $qb;
    }
}
