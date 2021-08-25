<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Adapter\Cache;

use Sonata\Cache\CacheElement;
use Sonata\Cache\CacheElementInterface;
use Sonata\Cache\Exception\UnsupportedException;

class NoopCache extends BaseCacheHandler
{
    /**
     * {@inheritdoc}
     */
    public function flushAll(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = []): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, int $ttl = CacheElement::DAY, array $contextualKeys = []): CacheElementInterface
    {
        return new CacheElement($keys, $data, $ttl, $contextualKeys);
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys): CacheElementInterface
    {
        throw new UnsupportedException('The NoopCache::get() cannot called');
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual(): bool
    {
        return false;
    }
}
