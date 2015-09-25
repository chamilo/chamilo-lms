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

use Fxp\Composer\AssetPlugin\Package\Version\VersionParser;

/**
 * Utils for semver converter.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
abstract class SemverUtil
{
    /**
     * Replace the alias version (x or *) by integer.
     *
     * @param string $version
     * @param string $type
     *
     * @return string
     */
    public static function replaceAlias($version, $type)
    {
        $value = '>' === $type ? '0' : '9999999';

        return str_replace(array('x', '*'), $value, $version);
    }

    /**
     * Converts the version metadata.
     *
     * @param string $version
     *
     * @return string
     */
    public static function convertVersionMetadata($version)
    {
        if (preg_match_all(self::createPattern('([a-z]+|(\-|\+)[a-z]+|(\-|\+)[0-9]+)'),
            $version, $matches, PREG_OFFSET_CAPTURE)) {
            list($type, $version, $end) = self::cleanVersion($version, $matches);
            list($version, $patchVersion) = self::matchVersion($version, $type);

            $matches = array();
            $hasPatchNumber = preg_match('/[0-9]+|\.[0-9]+$/', $end, $matches);
            $end = $hasPatchNumber ? $matches[0] : '1';

            if ($patchVersion) {
                $version .= $end;
            }
        }

        return static::cleanWildcard($version);
    }

    /**
     * Creates a pattern with the version prefix pattern.
     *
     * @param string $pattern The pattern without '/'
     *
     * @return string The full pattern with '/'
     */
    public static function createPattern($pattern)
    {
        $numVer = '([0-9]+|x|\*)';
        $numVer2 = '('.$numVer.'\.'.$numVer.')';
        $numVer3 = '('.$numVer.'\.'.$numVer.'\.'.$numVer.')';

        return '/^'.'('.$numVer.'|'.$numVer2.'|'.$numVer3.')'.$pattern.'/';
    }

    /**
     * Clean the wildcard in version.
     *
     * @param string $version The version
     *
     * @return string The cleaned version
     */
    protected static function cleanWildcard($version)
    {
        while (false !== strpos($version, '.x.x')) {
            $version = str_replace('.x.x', '.x', $version);
        }

        return $version;
    }

    /**
     * Clean the raw version.
     *
     * @param string $version The version
     * @param array  $matches The match of pattern asset version
     *
     * @return array The list of $type, $version and $end
     */
    protected static function cleanVersion($version, array $matches)
    {
        $end = substr($version, strlen($matches[1][0][0]));
        $version = $matches[1][0][0].'-';

        $matches = array();
        if (preg_match('/^(\-|\+)/', $end, $matches)) {
            $end = substr($end, 1);
        }

        $matches = array();
        preg_match('/^[a-z]+/', $end, $matches);
        $type = isset($matches[0]) ? VersionParser::normalizeStability($matches[0]) : null;
        $end = substr($end, strlen($type));

        return array($type, $version, $end);
    }

    /**
     * Match the version.
     *
     * @param string $version
     * @param string $type
     *
     * @return array The list of $version and $patchVersion
     */
    protected static function matchVersion($version, $type)
    {
        $patchVersion = true;

        if ('dev' === $type) {
            $patchVersion = false;
        } elseif ('a' === $type) {
            $type = 'alpha';
        } elseif (in_array($type, array('b', 'pre'))) {
            $type = 'beta';
        } elseif (!in_array($type, array('alpha', 'beta', 'RC'))) {
            $type = 'patch';
        }

        $version .= $type;

        return array($version, $patchVersion);
    }
}
