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

use Composer\Package\PackageInterface;

/**
 * Installer for bower packages.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class BowerInstaller extends AssetInstaller
{
    /**
     * {@inheritdoc}
     */
    protected function addIgnorePatterns(IgnoreManager $manager, PackageInterface $package)
    {
        $extra = $package->getExtra();

        if (!empty($extra['bower-asset-ignore'])) {
            $manager->doAddPattern('!bower.json');

            foreach ($extra['bower-asset-ignore'] as $pattern) {
                $manager->addPattern($pattern);
            }
        }
    }
}
