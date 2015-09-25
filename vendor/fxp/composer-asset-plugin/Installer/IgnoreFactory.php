<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Installer;

use Composer\Composer;
use Composer\Package\PackageInterface;

/**
 * Factory of ignore manager patterns.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class IgnoreFactory
{
    /**
     * Create a ignore manager.
     *
     * @param Composer         $composer   The composer instance
     * @param PackageInterface $package    The package instance
     * @param string|null      $installDir The custom installation directory
     * @param string|null      $section    The extra section of ignore patterns
     *
     * @return IgnoreManager
     */
    public static function create(Composer $composer, PackageInterface $package, $installDir = null, $section = 'asset-ignore-files')
    {
        $installDir = static::getInstallDir($composer, $package, $installDir);
        $manager = new IgnoreManager($installDir);
        $extra = $composer->getPackage()->getExtra();

        if (isset($extra[$section])) {
            foreach ($extra[$section] as $packageName => $patterns) {
                if ($packageName === $package->getName()) {
                    static::addPatterns($manager, $patterns);
                    break;
                }
            }
        }

        return $manager;
    }

    /**
     * Get the installation directory of the package.
     *
     * @param Composer         $composer   The composer instance
     * @param PackageInterface $package    The package instance
     * @param string|null      $installDir The custom installation directory
     *
     * @return string The installation directory
     */
    protected static function getInstallDir(Composer $composer, PackageInterface $package, $installDir = null)
    {
        if (null === $installDir) {
            $installDir = rtrim($composer->getConfig()->get('vendor-dir'), '/').'/'.$package->getName();
        }

        return rtrim($installDir, '/');
    }

    /**
     * Add ignore file patterns in the ignore manager.
     *
     * @param IgnoreManager $manager  The ignore files manager
     * @param bool|array    $patterns The patterns for ignore files
     */
    protected static function addPatterns(IgnoreManager $manager, $patterns)
    {
        $enabled = false === $patterns ? false : true;
        $manager->setEnabled($enabled);

        if (is_array($patterns)) {
            foreach ($patterns as $pattern) {
                $manager->addPattern($pattern);
            }
        }
    }
}
