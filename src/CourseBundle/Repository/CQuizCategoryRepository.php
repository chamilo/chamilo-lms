<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Doctrine\Persistence\ManagerRegistry;

final class CQuizCategoryRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CQuizCategory::class);
    }

    /**
     * @return array
     */
    public function getCategories(int $courseId)
    {
        $query = $this->createQueryBuilder('e');
        $query->where('e.course = :cId');
        $query->setParameter('cId', $courseId);
        $query->orderBy('e.position');

        return $query->getQuery()->getResult();
    }
}
