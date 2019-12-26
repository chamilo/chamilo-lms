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
