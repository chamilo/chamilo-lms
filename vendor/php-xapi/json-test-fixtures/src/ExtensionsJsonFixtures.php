<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XApi\Fixtures\Json;

/**
 * JSON encoded xAPI extensions fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ExtensionsJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'Extensions';

    public static function getEmptyExtensions()
    {
        return self::load('empty');
    }

    public static function getTypicalExtensions()
    {
        return self::load('typical');
    }

    public static function getWithObjectValueExtensions()
    {
        return self::load('with_object_value');
    }

    public static function getWithIntegerValueExtensions()
    {
        return self::load('with_integer_value');
    }

    public static function getMultiplePairsExtensions()
    {
        return self::load('multiple_pairs');
    }
}
