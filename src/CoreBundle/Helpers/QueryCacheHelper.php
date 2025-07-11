<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Helper for caching Doctrine query results.
 *
 * Features:
 * - Automatic generation of cache keys.
 * - Optionally returns cache key along with result (for debugging or manual invalidation).
 * - Supports tagging (if using a TagAware cache adapter).
 *
 * USAGE EXAMPLES:
 *
 * // Run a query with caching:
 * $result = $this->queryCacheHelper->run(
 *     $qb,
 *     'findActiveUsers'
 * );
 *
 * // Run a query with parameters:
 * $result = $this->queryCacheHelper->run(
 *     $qb,
 *     'findByRole',
 *     ['role' => $role, 'keyword' => $keyword]
 * );
 *
 * // Run a query and get cache key for debugging:
 * $result = $this->queryCacheHelper->run(
 *     $qb,
 *     'findByRole',
 *     ['role' => $role, 'keyword' => $keyword],
 *     300,
 *     true // return key
 * );
 *
 * // Run a query with tags:
 * $result = $this->queryCacheHelper->runWithTags(
 *     $qb,
 *     'findByRole',
 *     ['role' => $role, 'keyword' => $keyword],
 *     ['users'],
 *     300
 * );
 *
 * // Invalidate a specific cached query:
 * $this->queryCacheHelper->invalidate('findByRole', [...]);
 *
 * // Invalidate everything with a given tag:
 * $this->queryCacheHelper->invalidateByTag('users');
 */
class QueryCacheHelper
{
    private CacheInterface $cache;
    private int $defaultTtl;

    /**
     * @param CacheInterface $cache      the cache adapter to store results
     * @param int            $defaultTtl default TTL (in seconds) for cached queries
     */
    public function __construct(CacheInterface $cache, int $defaultTtl = 600)
    {
        $this->cache = $cache;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Runs a Doctrine QueryBuilder and caches its results.
     *
     * @param QueryBuilder $qb            the Doctrine QueryBuilder to execute
     * @param string|null  $operationName operation name for generating cache key
     * @param array        $parameters    parameters affecting the query (included in cache key)
     * @param int|null     $ttl           time-to-live for cache in seconds
     * @param bool         $returnKey     whether to return the cache key alongside data
     *
     * @return array|mixed Either:
     *                     - array with keys ['data' => ..., 'cache_key' => ...] if $returnKey is true
     *                     - raw query result otherwise
     */
    public function run(
        QueryBuilder $qb,
        ?string $operationName = null,
        array $parameters = [],
        ?int $ttl = 300,
        bool $returnKey = true
    ) {
        $operationName ??= 'anonymous_query';
        $cacheKey = $this->buildCacheKey($operationName, $parameters);

        $result = $this->cache->get($cacheKey, function (ItemInterface $item) use ($qb, $ttl) {
            $item->expiresAfter($ttl ?? $this->defaultTtl);

            return $qb->getQuery()->getResult();
        });

        if ($returnKey) {
            return [
                'data' => $result,
                'cache_key' => $cacheKey,
            ];
        }

        return $result;
    }

    /**
     * Runs a Doctrine QueryBuilder and caches the result with tags.
     *
     * IMPORTANT: Tagging requires a TagAwareAdapter (e.g. Redis).
     *
     * @return array|mixed
     */
    public function runWithTags(
        QueryBuilder $qb,
        ?string $operationName = null,
        array $parameters = [],
        array $tags = [],
        ?int $ttl = null,
        bool $returnKey = true
    ) {
        $operationName ??= 'anonymous_query';
        $cacheKey = $this->buildCacheKey($operationName, $parameters);

        $result = $this->cache->get($cacheKey, function (ItemInterface $item) use ($qb, $ttl, $tags) {
            $item->expiresAfter($ttl ?? $this->defaultTtl);

            if (!empty($tags)) {
                if (method_exists($item, 'tag')) {
                    $item->tag($tags);
                }
            }

            return $qb->getQuery()->getResult();
        });

        if ($returnKey) {
            return [
                'data' => $result,
                'cache_key' => $cacheKey,
            ];
        }

        return $result;
    }

    /**
     * Invalidates the cache for a specific operation and parameters.
     */
    public function invalidate(string $operationName, array $parameters = []): void
    {
        $cacheKey = $this->buildCacheKey($operationName, $parameters);
        $this->cache->delete($cacheKey);
    }

    /**
     * Invalidates all cached entries associated with a specific tag.
     *
     * Requires a TagAwareAdapter (e.g. Redis) to be configured.
     */
    public function invalidateByTag(string $tag): void
    {
        if (method_exists($this->cache, 'invalidateTags')) {
            $this->cache->invalidateTags([$tag]);
        }
    }

    /**
     * Builds a unique cache key from the operation name and parameters.
     */
    public function buildCacheKey(string $operationName, array $parameters): string
    {
        if (empty($parameters)) {
            return $operationName;
        }

        return $operationName.'_'.md5(json_encode($parameters));
    }

    /**
     * Generates a cache key for an operation, without executing any query.
     * Useful for debugging or manual cache clearing.
     */
    public function getCacheKey(string $operationName, array $parameters = []): string
    {
        return $this->buildCacheKey($operationName, $parameters);
    }
}
