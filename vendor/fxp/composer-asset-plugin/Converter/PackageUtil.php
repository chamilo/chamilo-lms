<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Converter;

use Composer\Config;
use Composer\IO\NullIO;
use Composer\Repository\Vcs\VcsDriverInterface;
use Fxp\Composer\AssetPlugin\Assets;
use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;
use Fxp\Composer\AssetPlugin\Util\Validator;

/**
 * Utils for package converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class PackageUtil
{
    /**
     * Checks if the version is a URL version.
     *
     * @param AssetTypeInterface $assetType  The asset type
     * @param string             $dependency The dependency
     * @param string             $version    The version
     * @param array              $vcsRepos   The list of new vcs configs
     * @param array              $composer   The partial composer data
     *
     * @return string[] The new dependency and the new version
     */
    public static function checkUrlVersion(AssetTypeInterface $assetType, $dependency, $version, array &$vcsRepos = array(), array $composer)
    {
        if (preg_match('/(\:\/\/)|\@/', $version)) {
            list($url, $version) = static::splitUrlVersion($version);

            if (static::hasUrlDependencySupported($url)) {
                $vcsRepos[] = array(
                    'type' => sprintf('%s-vcs', $assetType->getName()),
                    'url' => $url,
                );
            } else {
                $dependency = static::getUrlFileDependencyName($assetType, $composer, $dependency);
                $vcsRepos[] = array(
                    'type' => 'package',
                    'package' => array(
                        'name' => $assetType->formatComposerName($dependency),
                        'type' => $assetType->getComposerType(),
                        'version' => static::getUrlFileDependencyVersion($assetType, $url, $version),
                        'dist' => array(
                            'url' => $url,
                            'type' => 'file',
                        ),
                    ),
                );
            }
        }

        return array($dependency, $version);
    }

    /**
     * Checks if the version is a alias version.
     *
     * @param AssetTypeInterface $assetType  The asset type
     * @param string             $dependency The dependency
     * @param string             $version    The version
     *
     * @return string[] The new dependency and the new version
     */
    public static function checkAliasVersion(AssetTypeInterface $assetType, $dependency, $version)
    {
        $pos = strpos($version, '#');

        if ($pos > 0 && !preg_match('{[0-9a-f]{40}$}', $version)) {
            $dependency = substr($version, 0, $pos);
            $version = substr($version, $pos);
            $searchVerion = substr($version, 1);

            if (false === strpos($version, '*') && Validator::validateTag($searchVerion, $assetType)) {
                $dependency .= '-'.str_replace('#', '', $version);
            }
        }

        return array($dependency, $version);
    }

    /**
     * Convert the dependency version.
     *
     * @param AssetTypeInterface $assetType  The asset type
     * @param string             $dependency The dependency
     * @param string             $version    The version
     *
     * @return string[] The new dependency and the new version
     */
    public static function convertDependencyVersion(AssetTypeInterface $assetType, $dependency, $version)
    {
        $version = str_replace('#', '', $version);
        $version = empty($version) ? '*' : $version;
        $version = trim($version);
        $searchVersion = str_replace(array(' ', '<', '>', '=', '^', '~'), '', $version);

        // sha version or branch verison
        if (preg_match('{^[0-9a-f]{40}$}', $version)) {
            $version = 'dev-default#'.$version;
        } elseif ('*' !== $version && !Validator::validateTag($searchVersion, $assetType) && !static::depIsRange($version)) {
            $oldVersion = $version;
            $version = 'dev-'.$assetType->getVersionConverter()->convertVersion($version);

            if (!Validator::validateBranch($oldVersion)) {
                $version .= ' || '.$oldVersion;
            }
        }

        return array($dependency, $version);
    }

    /**
     * Converts the simple key of package.
     *
     * @param array  $asset       The asset data
     * @param string $assetKey    The asset key
     * @param array  $composer    The composer data
     * @param string $composerKey The composer key
     */
    public static function convertStringKey(array $asset, $assetKey, array &$composer, $composerKey)
    {
        if (isset($asset[$assetKey])) {
            $composer[$composerKey] = $asset[$assetKey];
        }
    }

    /**
     * Converts the simple key of package.
     *
     * @param array  $asset       The asset data
     * @param string $assetKey    The asset key
     * @param array  $composer    The composer data
     * @param array  $composerKey The array with composer key name and closure
     *
     * @throws \InvalidArgumentException When the 'composerKey' argument of asset packager converter is not an string or an array with the composer key and closure
     */
    public static function convertArrayKey(array $asset, $assetKey, array &$composer, $composerKey)
    {
        if (2 !== count($composerKey)
            || !is_string($composerKey[0]) || !$composerKey[1] instanceof \Closure) {
            throw new \InvalidArgumentException('The "composerKey" argument of asset packager converter must be an string or an array with the composer key and closure');
        }

        $closure = $composerKey[1];
        $composerKey = $composerKey[0];
        $data = isset($asset[$assetKey]) ? $asset[$assetKey] : null;
        $previousData = isset($composer[$composerKey]) ? $composer[$composerKey] : null;
        $data = $closure($data, $previousData);

        if (null !== $data) {
            $composer[$composerKey] = $data;
        }
    }

    /**
     * Split the URL and version.
     *
     * @param string $version The url and version (in the same string)
     *
     * @return string[] The url and version
     */
    protected static function splitUrlVersion($version)
    {
        $pos = strpos($version, '#');

        // number version or empty version
        if (false !== $pos) {
            $url = substr($version, 0, $pos);
            $version = substr($version, $pos);
        } else {
            $url = $version;
            $version = '#';
        }

        return array($url, $version);
    }

    /**
     * Get the name of url file dependency.
     *
     * @param AssetTypeInterface $assetType  The asset type
     * @param array              $composer   The partial composer
     * @param string             $dependency The dependency name
     *
     * @return string The dependency name
     */
    protected static function getUrlFileDependencyName(AssetTypeInterface $assetType, array $composer, $dependency)
    {
        $prefix = isset($composer['name'])
            ? substr($composer['name'], strlen($assetType->getComposerVendorName()) + 1).'-'
            : '';

        return $prefix.$dependency.'-file';
    }

    /**
     * Get the version of url file dependency.
     *
     * @param AssetTypeInterface $assetType The asset type
     * @param string             $url       The url
     * @param string             $version   The version
     *
     * @return string The version
     */
    protected static function getUrlFileDependencyVersion(AssetTypeInterface $assetType, $url, $version)
    {
        if ('#' !== $version) {
            return substr($version, 1);
        }

        if (preg_match('/(\d+)(\.\d+)(\.\d+)?(\.\d+)?/', $url, $match)) {
            return $assetType->getVersionConverter()->convertVersion($match[0]);
        }

        return '0.0.0.0';
    }

    /**
     * Check if url is supported by vcs drivers.
     *
     * @param string $url The url
     *
     * @return bool
     */
    protected static function hasUrlDependencySupported($url)
    {
        $io = new NullIO();
        $config = new Config();

        /* @var VcsDriverInterface $driver */
        foreach (Assets::getVcsDrivers() as $driver) {
            $supported = $driver::supports($io, $config, $url);

            if ($supported) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the version of dependency is a range version.
     *
     * @param string $version
     *
     * @return bool
     */
    protected static function depIsRange($version)
    {
        $version = trim($version);

        return (bool) preg_match('/[\<\>\=\^\~\ ]/', $version);
    }
}
