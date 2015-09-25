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
use Composer\Util\ProcessExecutor;

/**
 * Helper for process VCS driver.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class ProcessUtil
{
    /**
     * Get composer information.
     *
     * @param Cache           $cache
     * @param array           $infoCache
     * @param string          $assetType
     * @param ProcessExecutor $process
     * @param string          $identifier
     * @param string          $resource
     * @param string          $cmdGet
     * @param string          $cmdLog
     * @param string          $repoDir
     * @param string          $datetimePrefix
     *
     * @return array The composer
     */
    public static function getComposerInformation(Cache $cache, array &$infoCache,
        $assetType, ProcessExecutor $process, $identifier, $resource, $cmdGet,
        $cmdLog, $repoDir, $datetimePrefix = '')
    {
        $infoCache[$identifier] = Util::readCache($infoCache, $cache, $assetType, $identifier);

        if (!isset($infoCache[$identifier])) {
            $composer = static::doGetComposerInformation($resource, $process, $cmdGet, $cmdLog, $repoDir, $datetimePrefix);

            Util::writeCache($cache, $assetType, $identifier, $composer);
            $infoCache[$identifier] = $composer;
        }

        return $infoCache[$identifier];
    }

    /**
     * Get composer information.
     *
     * @param string          $resource
     * @param ProcessExecutor $process
     * @param string          $cmdGet
     * @param string          $cmdLog
     * @param string          $repoDir
     * @param string          $datetimePrefix
     *
     * @return array The composer
     */
    protected static function doGetComposerInformation($resource, ProcessExecutor $process, $cmdGet, $cmdLog, $repoDir, $datetimePrefix = '')
    {
        $process->execute($cmdGet, $composer, $repoDir);

        if (!trim($composer)) {
            return array('_nonexistent_package' => true);
        }

        $composer = JsonFile::parseJson($composer, $resource);

        return static::addComposerTime($composer, $process, $cmdLog, $repoDir, $datetimePrefix);
    }

    /**
     * Add time in composer.
     *
     * @param array           $composer
     * @param ProcessExecutor $process
     * @param string          $cmd
     * @param string          $repoDir
     * @param string          $datetimePrefix
     *
     * @return array The composer
     */
    protected static function addComposerTime(array $composer, ProcessExecutor $process, $cmd, $repoDir, $datetimePrefix = '')
    {
        if (!isset($composer['time'])) {
            $process->execute($cmd, $output, $repoDir);
            $date = new \DateTime($datetimePrefix.trim($output), new \DateTimeZone('UTC'));
            $composer['time'] = $date->format('Y-m-d H:i:s');
        }

        return $composer;
    }
}
