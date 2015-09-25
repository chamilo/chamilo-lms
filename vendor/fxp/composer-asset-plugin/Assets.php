<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin;

use Fxp\Composer\AssetPlugin\Type\AssetTypeInterface;

/**
 * Assets definition.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Assets
{
    /**
     * @var array
     */
    protected static $typeClasses = array(
        'npm' => 'Fxp\Composer\AssetPlugin\Type\NpmAssetType',
        'bower' => 'Fxp\Composer\AssetPlugin\Type\BowerAssetType',
    );

    /**
     * @var array
     */
    protected static $registryClasses = array(
        'npm' => 'Fxp\Composer\AssetPlugin\Repository\NpmRepository',
        'bower' => 'Fxp\Composer\AssetPlugin\Repository\BowerRepository',
    );

    /**
     * @var array
     */
    protected static $vcsRepositoryDrivers = array(
        'vcs' => 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository',
        'github' => 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository',
        'git-bitbucket' => 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository',
        'git' => 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository',
        'hg-bitbucket' => 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository',
        'hg' => 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository',
        'perforce' => 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository',
        'svn' => 'Fxp\Composer\AssetPlugin\Repository\AssetVcsRepository',
    );

    /**
     * @var array
     */
    protected static $vcsDrivers = array(
        'github' => 'Fxp\Composer\AssetPlugin\Repository\Vcs\GitHubDriver',
        'git-bitbucket' => 'Fxp\Composer\AssetPlugin\Repository\Vcs\GitBitbucketDriver',
        'git' => 'Fxp\Composer\AssetPlugin\Repository\Vcs\GitDriver',
        'hg-bitbucket' => 'Fxp\Composer\AssetPlugin\Repository\Vcs\HgBitbucketDriver',
        'hg' => 'Fxp\Composer\AssetPlugin\Repository\Vcs\HgDriver',
        'perforce' => 'Fxp\Composer\AssetPlugin\Repository\Vcs\PerforceDriver',
        // svn must be last because identifying a subversion server for sure is practically impossible
        'svn' => 'Fxp\Composer\AssetPlugin\Repository\Vcs\SvnDriver',
    );

    /**
     * Creates asset type.
     *
     * @param string $type
     *
     * @return AssetTypeInterface
     *
     * @throws \InvalidArgumentException When the asset type does not exist
     */
    public static function createType($type)
    {
        if (!isset(static::$typeClasses[$type])) {
            throw new \InvalidArgumentException('The asset type "'.$type.'" does not exist, only "'.implode('", "', static::getTypes()).'" are accepted');
        }

        $class = static::$typeClasses[$type];

        return new $class();
    }

    /**
     * Gets the asset types.
     *
     * @return array
     */
    public static function getTypes()
    {
        return array_keys(static::$typeClasses);
    }

    /**
     * Gets the asset registry repositories.
     *
     * @return array
     */
    public static function getRegistries()
    {
        return static::$registryClasses;
    }

    /**
     * Gets the asset vcs repository drivers.
     *
     * @return array
     */
    public static function getVcsRepositoryDrivers()
    {
        return static::$vcsRepositoryDrivers;
    }

    /**
     * Gets the asset vcs drivers.
     *
     * @return array
     */
    public static function getVcsDrivers()
    {
        return static::$vcsDrivers;
    }
}
