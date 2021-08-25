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
 * JSON encoded xAPI score fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ScoreJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'Score';

    public static function getEmptyScore()
    {
        return self::load('empty_score');
    }

    public static function getTypicalScore()
    {
        return self::load('typical_score');
    }

    public static function getScaledScore()
    {
        return self::load('scaled_score');
    }

    public static function getRawScore()
    {
        return self::load('raw_score');
    }

    public static function getMinScore()
    {
        return self::load('min_score');
    }

    public static function getMaxScore()
    {
        return self::load('max_score');
    }

    public static function getScaledAndRawScore()
    {
        return self::load('scaled_and_raw_score');
    }

    public static function getScaledAndMinScore()
    {
        return self::load('scaled_and_min_score');
    }

    public static function getScaledAndMaxScore()
    {
        return self::load('scaled_and_max_score');
    }

    public static function getRawAndMinScore()
    {
        return self::load('raw_and_min_score');
    }

    public static function getRawAndMaxScore()
    {
        return self::load('raw_and_max_score');
    }

    public static function getMinAndMaxScore()
    {
        return self::load('min_and_max_score');
    }

    public static function getScaledRawAndMinScore()
    {
        return self::load('scaled_raw_and_min_score');
    }

    public static function getScaledRawAndMaxScore()
    {
        return self::load('scaled_raw_and_max_score');
    }

    public static function getRawMinAndMaxScore()
    {
        return self::load('raw_min_and_max_score');
    }

    public static function getAllPropertiesScore()
    {
        return self::load('all_properties_score');
    }
}
