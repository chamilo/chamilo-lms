<?php

namespace ChamiloLMS\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;

/**
 * CQuizQuestion
 *
 */
class CQuizQuestionRepository extends EntityRepository
{
    /**
     * Get all questions per category
     *
     * @param int $categoryId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQuestionsByCategory($categoryId)
    {
        // Setting alias for this class "q" = CQuizQuestion.
        $qb = $this->createQueryBuilder('q');

        // Select all question information.
        $qb->select('q');

        // Inner join with the table c_quiz_question_rel_category.
        $qb->innerJoin('q.quizQuestionRelCategoryList', 'c');

        // Inner join with question extra fields.
        //$qb->innerJoin('q.extraFields', 'e');

        $wherePart = $qb->expr()->andx();
        $wherePart->add($qb->expr()->eq('c.categoryId', $categoryId));

        $qb->where($wherePart);
        //echo $qb->getQuery()->getSQL()."<br>";
        return $qb;
    }
}
