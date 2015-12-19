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
 * Helper for bitbucket VCS driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BitbucketUtil
{
    /**
     * Get composer information.
     *
     * @param Cache              $cache      The cache
     * @param array              $infoCache  The code cache
     * @param string             $scheme     The scheme
     * @param array              $repoConfig The repository config
     * @param string             $identifier The identifier
     * @param string             $owner      The owner of repository
     * @param string             $repository The repository name
     * @param VcsDriverInterface $driver     The vcs driver
     * @param string             $method     The method of vcs driver for get contents
     *
     * @return array The composer
     */
    public static function getComposerInformation(Cache $cache, array &$infoCache, $scheme,
        array $repoConfig, $identifier, $owner, $repository, VcsDriverInterface $driver, $method = 'getContents')
    {
        $infoCache[$identifier] = Util::readCache($infoCache, $cache, $repoConfig['asset-type'], $identifier);

        if (!isset($infoCache[$identifier])) {
            $resource = $scheme.'://bitbucket.org/'.$owner.'/'.$repository.'/raw/'.$identifier.'/'.$repoConfig['filename'];
            $composer = static::getComposerContent($resource, $identifier, $scheme, $owner, $repository, $driver, $method);

            Util::writeCache($cache, $repoConfig['asset-type'], $identifier, $composer);
            $infoCache[$identifier] = $composer;
        }

        return $infoCache[$identifier];
    }

    /**
     * Gets content of composer information.
     *
     * @param string             $resource   The resource
     * @param string             $identifier The identifier
     * @param string             $scheme     The scheme
     * @param string             $owner      The owner
     * @param string             $repository The repository
     * @param VcsDriverInterface $driver     The vcs driver
     * @param string             $method     The method for get content
     *
     * @return array
     */
    protected static function getComposerContent($resource, $identifier, $scheme, $owner, $repository, $driver, $method)
    {
        try {
            $ref = new \ReflectionClass($driver);
            $meth = $ref->getMethod($method);
            $meth->setAccessible(true);

            $composer = $meth->invoke($driver, $resource);
        } catch (\Exception $e) {
            $composer = false;
        }

        if ($composer) {
            $composer = (array) JsonFile::parseJson((string) $composer, $resource);
            $composer = static::formatComposerContent($composer, $identifier, $scheme, $owner, $repository, $driver, $method);

            return $composer;
        }

        return array('_nonexistent_package' => true);
    }

    /**
     * Format composer content.
     *
     * @param array              $composer   The composer
     * @param string             $identifier The identifier
     * @param string             $scheme     The scheme
     * @param string             $owner      The owner
     * @param string             $repository The repository
     * @param VcsDriverInterface $driver     The vcs driver
     * @param string             $method     The method for get content
     *
     * @return array
     */
    protected static function formatComposerContent(array $composer, $identifier, $scheme, $owner, $repository, $driver, $method)
    {
        $resource = $scheme.'://api.bitbucket.org/1.0/repositories/'.$owner.'/'.$repository.'/changesets/'.$identifier;
        $composer = Util::addComposerTime($composer, 'timestamp', $resource, $driver, $method);

        return $composer;
    }
}
