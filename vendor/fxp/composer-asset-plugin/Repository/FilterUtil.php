<?php

/*
 * This file is part of the Fxp Composer Asset Plugin package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Composer\AssetPlugin\Repository;

use Composer\Package\Link;
use Composer\Package\LinkConstraint\LinkConstraintInterface;
use Composer\Package\Package;
use Composer\Package\RootPackageInterface;
use Fxp\Composer\AssetPlugin\Package\Version\VersionParser;

/**
 * Helper for Filter Package of Repository.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FilterUtil
{
    /**
     * Get the link constraint of normalized version.
     *
     * @param string        $normalizedVersion The normalized version
     * @param VersionParser $versionParser     The version parser
     *
     * @return LinkConstraintInterface The constraint
     */
    public static function getVersionConstraint($normalizedVersion, VersionParser $versionParser)
    {
        if (preg_match('/^\d+(\.\d+)(\.\d+)(\.\d+)\-[A-Za-z0-9]+$/', $normalizedVersion)) {
            $normalizedVersion = substr($normalizedVersion, 0, strpos($normalizedVersion, '-'));
        }

        return $versionParser->parseConstraints($normalizedVersion);
    }

    /**
     * Find the stability name with the stability value.
     *
     * @param int $level The stability level.
     *
     * @return string The stability name
     */
    public static function findFlagStabilityName($level)
    {
        $stability = 'dev';

        /* @var string $stabilityName */
        /* @var int    $stabilityLevel */
        foreach (Package::$stabilities as $stabilityName => $stabilityLevel) {
            if ($stabilityLevel === $level) {
                $stability = $stabilityName;
                break;
            }
        }

        return $stability;
    }

    /**
     * Find the lowest stability.
     *
     * @param string[]      $stabilities   The list of stability
     * @param VersionParser $versionParser The version parser
     *
     * @return string The lowest stability
     */
    public static function findInlineStabilities(array $stabilities, VersionParser $versionParser)
    {
        $lowestStability = 'stable';

        foreach ($stabilities as $stability) {
            $stability = $versionParser->normalizeStability($stability);
            $stability = $versionParser->parseStability($stability);

            if (Package::$stabilities[$stability] > Package::$stabilities[$lowestStability]) {
                $lowestStability = $stability;
            }
        }

        return $lowestStability;
    }

    /**
     * Get the minimum stability for the require dependency defined in root package.
     *
     * @param RootPackageInterface $package The root package
     * @param Link                 $require The require link defined in root package
     *
     * @return string The minimum stability defined in root package (in links or global project)
     */
    public static function getMinimumStabilityFlag(RootPackageInterface $package, Link $require)
    {
        $flags = $package->getStabilityFlags();

        if (isset($flags[$require->getTarget()])) {
            return static::findFlagStabilityName($flags[$require->getTarget()]);
        }

        return $package->getPreferStable()
            ? 'stable'
            : $package->getMinimumStability();
    }

    /**
     * Check the extra option.
     *
     * @param RootPackageInterface $package The root package
     * @param string               $name    The extra option name
     *
     * @return bool
     */
    public static function checkExtraOption(RootPackageInterface $package, $name)
    {
        $extra = $package->getExtra();

        return !array_key_exists($name, $extra)
        || true === $extra[$name];
    }
}
