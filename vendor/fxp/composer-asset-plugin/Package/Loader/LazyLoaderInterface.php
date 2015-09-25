<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Package\Loader;

use Fxp\Composer\AssetPlugin\Package\LazyPackageInterface;

/**
 * Interface for lazy loader package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface LazyLoaderInterface
{
    /**
     * Loads the real package.
     *
     * @param LazyPackageInterface $package
     *
     * @return \Composer\Package\CompletePackageInterface|false
     */
    public function load(LazyPackageInterface $package);
}
