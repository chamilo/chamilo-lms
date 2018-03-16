<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Repository;

use Chamilo\FaqBundle\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CategoryRepository.
 *
 * @package Genj\FaqBundle\Entity
 */
class CategoryRepository
{
    /** @var \Doctrine\Common\Persistence\ObjectRepository EN */
    private $repository;

    /**
     * CategoryRepository constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Category::class);
    }

    /**
     * @return mixed
     */
    public function retrieveActive()
    {
        $query = $this->repository->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->orderBy('c.rank', 'ASC')
            ->getQuery();

        $query->setParameter('isActive', true);

        return $query->execute();
    }

    /**
     * @param string $slug
     *
     * @return mixed
     */
    public function retrieveActiveBySlug($slug)
    {
        $query = $this->repository->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->andWhere('c.slug = :slug')
            ->orderBy('c.rank', 'ASC')
            ->getQuery();

        $query->setParameter('isActive', true);
        $query->setParameter('slug', $slug);

        return $query->execute();
    }

    /**
     * @param string $slug
     *
     * @return mixed
     */
    public function getCategoryActiveBySlug($slug)
    {
        $query = $this->repository->createQueryBuilder('c')
            ->join('c.translations', 't')
            ->where('c.isActive = :isActive')
            ->andWhere('t.slug = :slug')
            ->getQuery();

        $query->setParameter('isActive', true);
        $query->setParameter('slug', $slug);

        return $query->getOneOrNullResult();
    }

    /**
     * @return Category|null
     */
    public function retrieveFirst()
    {
        $query = $this->repository->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->orderBy('c.rank', 'ASC')
            ->setMaxResults(1)
            ->getQuery();

        $query->setParameter('isActive', true);

        return $query->getOneOrNullResult();
    }
}
