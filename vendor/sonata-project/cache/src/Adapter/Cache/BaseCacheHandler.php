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

use Sonata\Cache\CacheAdapterInterface;
use Sonata\Cache\CacheElement;
use Sonata\Cache\CacheElementInterface;

abstract class BaseCacheHandler implements CacheAdapterInterface
{
    /**
     * @param array $keys
     * @param mixed $data
     *
     * @return CacheElementInterface
     */
    protected function handleGet(array $keys, $data = null): CacheElementInterface
    {
        if ($data instanceof CacheElementInterface) {
            return $data;
        }

        return new CacheElement($keys, null, -1000);
    }
}
