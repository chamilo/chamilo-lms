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
                'enabled' => true,
                'url' => $url,
                'category' => $categoryTitle,
                'locale' => $locale,
            ])
        ;

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
                'enabled' => true,
                'url' => $url,
                'category' => $categoryTitle,
                'prefix' => $localePrefix.'%',
            ])
        ;

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
                'enabled' => true,
                'url' => $url,
                'category' => $categoryTitle,
            ])
            ->setMaxResults(1)
        ;

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

    public function findPublicLinksByCategoryWithLocaleFallback(
        AccessUrl $accessUrl,
        string $categoryTitle,
        string $locale
    ): array {
        $locale = trim($locale);
        $prefix = '' !== $locale ? substr($locale, 0, 2) : '';

        // Exact locale
        if ('' !== $locale) {
            $items = $this->createQueryBuilder('p')
                ->select('p.title AS title, p.slug AS slug')
                ->innerJoin('p.category', 'c')
                ->andWhere('p.enabled = true')
                ->andWhere('p.url = :url')
                ->andWhere('c.title = :cat')
                ->andWhere('p.locale = :loc')
                ->setParameter('url', $accessUrl)
                ->setParameter('cat', $categoryTitle)
                ->setParameter('loc', $locale)
                ->orderBy('p.title', 'ASC')
                ->getQuery()
                ->getArrayResult()
            ;

            if (!empty($items)) {
                return $items;
            }
        }

        // Prefix locale (e.g. "fr")
        if ('' !== $prefix) {
            $items = $this->createQueryBuilder('p')
                ->select('p.title AS title, p.slug AS slug')
                ->innerJoin('p.category', 'c')
                ->andWhere('p.enabled = true')
                ->andWhere('p.url = :url')
                ->andWhere('c.title = :cat')
                ->andWhere('p.locale LIKE :prefix')
                ->setParameter('url', $accessUrl)
                ->setParameter('cat', $categoryTitle)
                ->setParameter('prefix', $prefix.'%')
                ->orderBy('p.title', 'ASC')
                ->getQuery()
                ->getArrayResult()
            ;

            if (!empty($items)) {
                return $items;
            }
        }

        // Any locale
        return $this->createQueryBuilder('p')
            ->select('p.title AS title, p.slug AS slug')
            ->innerJoin('p.category', 'c')
            ->andWhere('p.enabled = true')
            ->andWhere('p.url = :url')
            ->andWhere('c.title = :cat')
            ->setParameter('url', $accessUrl)
            ->setParameter('cat', $categoryTitle)
            ->orderBy('p.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function findEnabledPageBySlugWithLocaleFallback(
        AccessUrl $accessUrl,
        string $slug,
        ?string $locale = null,
        ?string $defaultLocale = null
    ): ?Page {
        return $this->findEnabledPageWithLocaleFallback(
            $accessUrl,
            $slug,
            null,
            $locale,
            $defaultLocale
        );
    }

    public function findEnabledPageByCategoryWithLocaleFallback(
        AccessUrl $accessUrl,
        string $categoryTitle,
        ?string $locale = null,
        ?string $defaultLocale = null
    ): ?Page {
        return $this->findEnabledPageWithLocaleFallback(
            $accessUrl,
            null,
            $categoryTitle,
            $locale,
            $defaultLocale
        );
    }

    private function findEnabledPageWithLocaleFallback(
        AccessUrl $accessUrl,
        ?string $slug,
        ?string $categoryTitle,
        ?string $locale,
        ?string $defaultLocale
    ): ?Page {
        foreach ($this->buildLocaleFallbackCandidates($locale, $defaultLocale) as $candidate) {
            $qb = $this->createQueryBuilder('p')
                ->andWhere('p.enabled = :enabled')
                ->andWhere('p.url = :url')
                ->setParameter('enabled', true)
                ->setParameter('url', $accessUrl)
                ->orderBy('p.position', 'ASC')
                ->addOrderBy('p.id', 'ASC')
                ->setMaxResults(1)
            ;

            if (null !== $slug) {
                $qb
                    ->andWhere('p.slug = :slug')
                    ->setParameter('slug', $slug)
                ;
            }

            if (null !== $categoryTitle) {
                $qb
                    ->innerJoin('p.category', 'c')
                    ->andWhere('c.title = :categoryTitle')
                    ->setParameter('categoryTitle', $categoryTitle)
                ;
            }

            if ('empty' === $candidate['operator']) {
                $qb
                    ->andWhere('(p.locale IS NULL OR p.locale = :emptyLocale)')
                    ->setParameter('emptyLocale', '')
                ;
            } elseif ('like' === $candidate['operator']) {
                $qb
                    ->andWhere('LOWER(p.locale) LIKE :locale')
                    ->setParameter('locale', $candidate['value'].'%')
                ;
            } else {
                $qb
                    ->andWhere('LOWER(p.locale) = :locale')
                    ->setParameter('locale', $candidate['value'])
                ;
            }

            $page = $qb->getQuery()->getOneOrNullResult();

            if ($page instanceof Page) {
                return $page;
            }
        }

        /*
         * Final fallback: keep the page available even if no locale matches.
         */
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.enabled = :enabled')
            ->andWhere('p.url = :url')
            ->setParameter('enabled', true)
            ->setParameter('url', $accessUrl)
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->setMaxResults(1)
        ;

        if (null !== $slug) {
            $qb
                ->andWhere('p.slug = :slug')
                ->setParameter('slug', $slug)
            ;
        }

        if (null !== $categoryTitle) {
            $qb
                ->innerJoin('p.category', 'c')
                ->andWhere('c.title = :categoryTitle')
                ->setParameter('categoryTitle', $categoryTitle)
            ;
        }

        $page = $qb->getQuery()->getOneOrNullResult();

        return $page instanceof Page ? $page : null;
    }

    /**
     * Build locale candidates compatible with Symfony locales and Chamilo language names.
     *
     * Examples:
     * - es
     * - es_ES
     * - es-ES
     * - spanish
     * - NULL / empty locale
     *
     * @return array<int, array{operator: string, value: string}>
     */
    private function buildLocaleFallbackCandidates(?string $locale, ?string $defaultLocale): array
    {
        $exactCandidates = [];
        $prefixCandidates = [];

        $this->addLocaleCandidateValues($exactCandidates, $prefixCandidates, $locale);
        $this->addLocaleCandidateValues($exactCandidates, $prefixCandidates, $defaultLocale);

        $candidates = [];
        $seen = [];

        foreach ($exactCandidates as $value) {
            $value = strtolower(trim((string) $value));

            if ('' === $value || isset($seen['exact:'.$value])) {
                continue;
            }

            $seen['exact:'.$value] = true;

            $candidates[] = [
                'operator' => 'exact',
                'value' => $value,
            ];
        }

        foreach ($prefixCandidates as $value) {
            $value = strtolower(trim((string) $value));

            if ('' === $value || isset($seen['like:'.$value])) {
                continue;
            }

            $seen['like:'.$value] = true;

            $candidates[] = [
                'operator' => 'like',
                'value' => $value,
            ];
        }

        $candidates[] = [
            'operator' => 'empty',
            'value' => '',
        ];

        return $candidates;
    }

    private function addLocaleCandidateValues(array &$exactCandidates, array &$prefixCandidates, ?string $locale): void
    {
        $locale = strtolower(trim((string) $locale));

        if ('' === $locale) {
            return;
        }

        $localeWithDash = str_replace('_', '-', $locale);
        $localeWithUnderscore = str_replace('-', '_', $locale);
        $shortLocale = substr($locale, 0, 2);

        $exactCandidates[] = $locale;
        $exactCandidates[] = $localeWithDash;
        $exactCandidates[] = $localeWithUnderscore;

        if (2 === \strlen($shortLocale) && ctype_alpha($shortLocale)) {
            $exactCandidates[] = $shortLocale;
            $prefixCandidates[] = $shortLocale;

            $isoToChamiloLanguage = [
                'en' => 'english',
                'es' => 'spanish',
                'fr' => 'french',
                'de' => 'german',
                'it' => 'italian',
                'pt' => 'portuguese',
                'nl' => 'dutch',
            ];

            if (isset($isoToChamiloLanguage[$shortLocale])) {
                $exactCandidates[] = $isoToChamiloLanguage[$shortLocale];
            }
        }
    }
}
