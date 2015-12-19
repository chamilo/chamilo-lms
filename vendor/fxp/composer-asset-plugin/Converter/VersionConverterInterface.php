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
 * Interface for the converter for asset syntax version to composer syntax version.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
interface VersionConverterInterface
{
    /**
     * Converts the asset version to composer version.
     *
     * @param string $version The asset version
     *
     * @return string The composer version
     */
    public function convertVersion($version);

    /**
     * Converts the range asset version to range composer version.
     *
     * @param string $range The range asset version
     *
     * @return string The range composer version
     */
    public function convertRange($range);
}
