<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Adapter\Cache;

use Sonata\Cache\CacheAdapterInterface;
use Sonata\Cache\CacheElement;

abstract class BaseCacheHandler implements CacheAdapterInterface
{
    /**
     * @param array $keys
     * @param mixed $data
     *
     * @return CacheElement
     */
    protected function handleGet(array $keys, $data)
    {
        if ($data instanceof CacheElement) {
            return $data;
        }

        return new CacheElement($keys, null, -1000);
    }
}
