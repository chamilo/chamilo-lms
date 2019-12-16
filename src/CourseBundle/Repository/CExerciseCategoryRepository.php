<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Repository\ResourceRepository;

/**
 * Class CExerciseCategoryRepository.
 */
final class CExerciseCategoryRepository extends ResourceRepository
{
    /**
     * Gets the number of values stored in the table (all fields together)
     * for this type of resource.
     *
     * @param int $courseId
     *
     * @return int Number of rows in the table
     */
    public function getCourseCount($courseId)
    {
        $query = $this->getRepository()->createQueryBuilder('e');
        $query->select('count(e.id)');
        $query->where('e.course = :cId');
        $query->setParameter('cId', $courseId);

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $courseId
     *
     * @return array
     */
    public function getCategories($courseId)
    {
        $query = $this->getRepository()->createQueryBuilder('e');
        $query->where('e.course = :cId');
        $query->setParameter('cId', $courseId);
        $query->orderBy('e.position');

        return $query->getQuery()->getResult();
    }
}
