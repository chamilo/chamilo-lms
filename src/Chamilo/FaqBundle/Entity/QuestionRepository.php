<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Class QuestionRepository.
 *
 * @package Genj\FaqBundle\Entity
 */
class QuestionRepository extends EntityRepository
{
    /**
     * @param string $categorySlug
     *
     * @return Question|null
     */
    public function retrieveFirstByCategorySlug($categorySlug)
    {
        $query = $this->createQueryBuilder('q')
            ->join('q.category', 'c')
            ->join('c.translations', 't')
            ->where('t.slug = :categorySlug')
            ->orderBy('q.rank', 'ASC')
            ->setMaxResults(1)
            ->getQuery();

        $query->setParameter('categorySlug', $categorySlug);

        return $query->getOneOrNullResult();
    }

    /**
     * @param string $slug
     *
     * @return Question|null
     */
    public function getQuestionBySlug($slug)
    {
        $query = $this->createQueryBuilder('q')
            ->join('q.translations', 't')
            ->where('t.slug = :slug')
            ->orderBy('q.rank', 'ASC')
            ->setMaxResults(1)
            ->getQuery();

        $query->setParameter('slug', $slug);

        return $query->getOneOrNullResult();
    }
}
