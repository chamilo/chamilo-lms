<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Tests\Fixtures\Repository;

use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;

/**
 * Fixture for assets repository tests.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class MockAssetRepository implements RepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function hasPackage(PackageInterface $package)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function findPackage($name, $version)
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function findPackages($name, $version = null)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getPackages()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $mode = 0)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return 0;
    }
}
