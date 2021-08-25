<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\DataFixtures;

use Xabbuh\XApi\Model\Score;

/**
 * xAPI score fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ScoreFixtures
{
    public static function getEmptyScore()
    {
        return new Score();
    }

    public static function getTypicalScore()
    {
        return new Score(1);
    }

    public static function getScaledScore()
    {
        return new Score(1);
    }

    public static function getRawScore()
    {
        return new Score(null, 100);
    }

    public static function getMinScore()
    {
        return new Score(null, null, 0);
    }

    public static function getMaxScore()
    {
        return new Score(null, null, null, 100);
    }

    public static function getScaledAndRawScore()
    {
        return new Score(1, 100);
    }

    public static function getScaledAndMinScore()
    {
        return new Score(1, null, 0);
    }

    public static function getScaledAndMaxScore()
    {
        return new Score(1, null, null, 100);
    }

    public static function getRawAndMinScore()
    {
        return new Score(null, 100, 0);
    }

    public static function getRawAndMaxScore()
    {
        return new Score(null, 100, null, 100);
    }

    public static function getMinAndMaxScore()
    {
        return new Score(null, null, 0, 100);
    }

    public static function getScaledRawAndMinScore()
    {
        return new Score(1, 100, 0);
    }

    public static function getScaledRawAndMaxScore()
    {
        return new Score(1, 100, null, 100);
    }

    public static function getRawMinAndMaxScore()
    {
        return new Score(null, 100, 0, 100);
    }

    public static function getAllPropertiesScore()
    {
        return new Score(1, 100, 0, 100);
    }
}
