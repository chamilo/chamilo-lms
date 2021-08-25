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
 * JSON encoded xAPI UUID fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class UuidJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'Uuid';

    public static function getGoodUuid()
    {
        return self::load('good_uuid');
    }

    public static function getBadUuid()
    {
        return self::load('bad_uuid');
    }
}
