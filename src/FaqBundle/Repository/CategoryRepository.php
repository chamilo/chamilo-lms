<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Repository;

use Chamilo\FaqBundle\Entity\Category;
use Doctrine\ORM\EntityRepository;

/**
 * Class CategoryRepository.
 */
class CategoryRepository extends EntityRepository
{
    public function retrieveActive()
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->orderBy('c.rank', 'ASC')
            ->getQuery();

        $query->setParameter('isActive', true);

        return $query->execute();
    }

    /**
     * @param string $slug
     */
    public function retrieveActiveBySlug($slug)
    {
        $query = $this->createQueryBuilder('c')
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
     */
    public function getCategoryActiveBySlug($slug)
    {
        $query = $this->createQueryBuilder('c')
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
        $query = $this->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->orderBy('c.rank', 'ASC')
            ->setMaxResults(1)
            ->getQuery();

        $query->setParameter('isActive', true);

        return $query->getOneOrNullResult();
    }
}
