<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository\Vcs;

use Composer\Cache;
use Composer\Json\JsonFile;
use Composer\Repository\Vcs\VcsDriverInterface;

/**
 * Helper for VCS driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Util
{
    /**
     * Check if the identifier is an SHA.
     *
     * @param string $identifier The identifier
     *
     * @return bool
     */
    public static function isSha($identifier)
    {
        return (bool) preg_match('{[a-f0-9]{40}}i', $identifier);
    }

    /**
     * @param array  $cacheCode  The cache code
     * @param Cache  $cache      The cache filesystem
     * @param string $type       The asset type
     * @param string $identifier The identifier
     * @param bool   $force      Force the read
     *
     * @return array|null
     */
    public static function readCache(array $cacheCode, Cache $cache, $type, $identifier, $force = false)
    {
        if (array_key_exists($identifier, $cacheCode)) {
            return $cacheCode[$identifier];
        }

        $data = null;
        if (self::isSha($identifier) || $force) {
            $res = $cache->read($type.'-'.$identifier);

            if ($res) {
                $data = JsonFile::parseJson($res);
            }
        }

        return $data;
    }

    /**
     * @param Cache  $cache      The cache
     * @param string $type       The asset type
     * @param string $identifier The identifier
     * @param array  $composer   The data composer
     * @param bool   $force      Force the write
     */
    public static function writeCache(Cache $cache, $type, $identifier, array $composer, $force = false)
    {
        if (self::isSha($identifier) || $force) {
            $cache->write($type.'-'.$identifier, json_encode($composer));
        }
    }

    /**
     * Add time in composer.
     *
     * @param array              $composer    The composer
     * @param string             $resourceKey The composer key
     * @param string             $resource    The resource url
     * @param VcsDriverInterface $driver      The vcs driver
     * @param string             $method      The method for get content
     *
     * @return array The composer
     */
    public static function addComposerTime(array $composer, $resourceKey, $resource, VcsDriverInterface $driver, $method = 'getContents')
    {
        if (!isset($composer['time'])) {
            $ref = new \ReflectionClass($driver);
            $meth = $ref->getMethod($method);
            $meth->setAccessible(true);

            $commit = JsonFile::parseJson((string) $meth->invoke($driver, $resource), $resource);
            $keys = explode('.', $resourceKey);

            while (!empty($keys)) {
                $commit = $commit[$keys[0]];
                array_shift($keys);
            }

            $composer['time'] = $commit;
        }

        return $composer;
    }
}
