<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Custom queries for Page visibility in the public top bar.
 */
final class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    /**
     * Count enabled pages for a given category title, exact locale, and URL.
     */
    public function countByCategoryAndLocale(AccessUrl $url, string $categoryTitle, string $locale): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->innerJoin('p.category', 'c')
            ->andWhere('p.enabled = :enabled')
            ->andWhere('p.url = :url')
            ->andWhere('c.title = :category')
            ->andWhere('p.locale = :locale')
            ->setParameters([
                'enabled'  => true,
                'url'      => $url,
                'category' => $categoryTitle,
                'locale'   => $locale,
            ]);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Count enabled pages for a given category title, matching a locale prefix (e.g., "es%"), and URL.
     */
    public function countByCategoryAndLocalePrefix(AccessUrl $url, string $categoryTitle, string $localePrefix): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->innerJoin('p.category', 'c')
            ->andWhere('p.enabled = :enabled')
            ->andWhere('p.url = :url')
            ->andWhere('c.title = :category')
            ->andWhere('p.locale LIKE :prefix')
            ->setParameters([
                'enabled'  => true,
                'url'      => $url,
                'category' => $categoryTitle,
                'prefix'   => $localePrefix.'%',
            ]);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns true if there is at least one enabled page for that category and URL (locale-agnostic).
     */
    public function anyByCategoryForUrl(AccessUrl $url, string $categoryTitle): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('1')
            ->innerJoin('p.category', 'c')
            ->andWhere('p.enabled = :enabled')
            ->andWhere('p.url = :url')
            ->andWhere('c.title = :category')
            ->setParameters([
                'enabled'  => true,
                'url'      => $url,
                'category' => $categoryTitle,
            ])
            ->setMaxResults(1);

        return (bool) $qb->getQuery()->getOneOrNullResult();
    }

    public function update(Page $page): void
    {
        $this->getEntityManager()->persist($page);
        $this->getEntityManager()->flush();
    }

    public function delete(?Page $page = null): void
    {
        if (null !== $page) {
            $this->getEntityManager()->remove($page);
            $this->getEntityManager()->flush();
        }
    }
}
