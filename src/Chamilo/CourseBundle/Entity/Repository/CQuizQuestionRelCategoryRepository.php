<?php

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

/**
 * CQuizQuestionRelCategoryRepository
 *
 */
class CQuizQuestionRelCategoryRepository extends EntityRepository
{
    public function getCountQuestionByCategory($categoryId)
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.categoryId = :categoryId')
            ->setParameters(array('categoryId' => $categoryId))
            ->getQuery()
            ->getSingleScalarResult();
    }
}
