<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Repository;

use Chamilo\FaqBundle\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class QuestionRepository.
 *
 * @package Genj\FaqBundle\Entity
 */
class QuestionRepository
{
    private $repository;

    /**
     * QuestionRepository constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Question::class);
    }

    /**
     * @param string $categorySlug
     *
     * @return Question|null
     */
    public function retrieveFirstByCategorySlug($categorySlug)
    {
        $query = $this->repository->createQueryBuilder('q')
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
        $query = $this->repository->createQueryBuilder('q')
            ->join('q.translations', 't')
            ->where('t.slug = :slug')
            ->orderBy('q.rank', 'ASC')
            ->setMaxResults(1)
            ->getQuery();

        $query->setParameter('slug', $slug);

        return $query->getOneOrNullResult();
    }
}
