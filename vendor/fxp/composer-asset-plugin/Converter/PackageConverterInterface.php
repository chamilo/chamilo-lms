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
 * Interface for the converter for asset package to composer package.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface PackageConverterInterface
{
    /**
     * Converts the asset data package to composer data package.
     *
     * @param array $data     The asset data package
     * @param array $vcsRepos The vcs repositories created
     *
     * @return array The composer data package
     */
    public function convert(array $data, array &$vcsRepos = array());
}
