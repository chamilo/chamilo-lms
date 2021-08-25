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

use Ramsey\Uuid\Uuid as RamseyUuid;
use Rhumsaa\Uuid\Uuid as RhumsaaUuid;

/**
 * xAPI UUID fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class UuidFixtures
{
    public static function getGoodUuid()
    {
        return '39e24cc4-69af-4b01-a824-1fdc6ea8a3af';
    }

    public static function getBadUuid()
    {
        return 'bad-uuid';
    }

    public static function getUniqueUuid()
    {
        if (class_exists('Rhumsaa\Uuid\Uuid')) {
            return (string) RhumsaaUuid::uuid4();
        }

        return (string) RamseyUuid::uuid4();
    }
}
