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

/**
 * Converter for bower package to composer package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerPackageConverter extends AbstractPackageConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getMapKeys()
    {
        $assetType = $this->assetType;

        return array(
            'name' => array('name', function ($value) use ($assetType) {
                return $assetType->formatComposerName($value);
            }),
            'type' => array('type', function () use ($assetType) {
                return $assetType->getComposerType();
            }),
            'version' => array('version', function ($value) use ($assetType) {
                return $assetType->getVersionConverter()->convertVersion($value);
            }),
            'version_normalized' => 'version_normalized',
            'description' => 'description',
            'keywords' => 'keywords',
            'license' => 'license',
            'bin' => 'bin',
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapExtras()
    {
        return array(
            'main' => 'bower-asset-main',
            'ignore' => 'bower-asset-ignore',
            'private' => 'bower-asset-private',
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDependency($dependency, $version, array &$vcsRepos = array(), array $composer)
    {
        list($dependency, $version) = $this->checkGithubRepositoryVersion($dependency, $version);

        return parent::convertDependency($dependency, $version, $vcsRepos, $composer);
    }

    /**
     * Checks if the version is a Github alias version of repository.
     *
     * @param string $dependency The dependency
     * @param string $version    The version
     *
     * @return string[] The new dependency and the new version
     */
    protected function checkGithubRepositoryVersion($dependency, $version)
    {
        if (preg_match('/^[A-Za-z0-9\-_]+\/[A-Za-z0-9\-_.]+/', $version)) {
            $pos = strpos($version, '#');
            $pos = false === $pos ? strlen($version) : $pos;
            $realVersion = substr($version, $pos);
            $version = 'git://github.com/'.substr($version, 0, $pos).'.git'.$realVersion;
        }

        return array($dependency, $version);
    }
}
