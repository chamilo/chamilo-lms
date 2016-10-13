<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache;

use Sonata\Cache\Invalidation\Recorder;

interface CacheManagerInterface
{
    /**
     * Adds a cache service.
     *
     * @param string                $name         A cache name
     * @param CacheAdapterInterface $cacheManager A cache service
     */
    public function addCacheService($name, CacheAdapterInterface $cacheManager);

    /**
     * Gets a cache service by a given name.
     *
     * @param string $name A cache name
     *
     * @return CacheAdapterInterface
     */
    public function getCacheService($name);

    /**
     * Returns related cache services.
     *
     * @return array
     */
    public function getCacheServices();

    /**
     * Returns TRUE whether a cache service identified by id exists.
     *
     * @param string $id
     *
     * @return bool
     */
    public function hasCacheService($id);

    /**
     * Invalidates the cache by the given keys.
     *
     * @param array $keys
     */
    public function invalidate(array $keys);

    /**
     * Sets the recorder.
     *
     * @param Recorder $recorder
     */
    public function setRecorder(Recorder $recorder);

    /**
     * Gets the recorder.
     *
     * @return Recorder
     */
    public function getRecorder();
}
