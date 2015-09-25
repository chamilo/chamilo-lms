<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Package;

use Composer\Package\CompletePackageInterface;
use Fxp\Composer\AssetPlugin\Package\Loader\LazyLoaderInterface;

/**
 * Interface for lazy loading package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface LazyPackageInterface extends CompletePackageInterface
{
    /**
     * Sets the lazy loader.
     *
     * @param LazyLoaderInterface $lazyLoader
     */
    public function setLoader(LazyLoaderInterface $lazyLoader);
}
