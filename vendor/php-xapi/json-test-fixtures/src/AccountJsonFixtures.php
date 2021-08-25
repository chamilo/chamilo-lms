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
 * JSON encoded xAPI account fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class AccountJsonFixtures extends JsonFixtures
{
    const DIRECTORY = 'Account';

    public static function getTypicalAccount()
    {
        return self::load('typical');
    }

    public static function getConsumerAccount()
    {
        return self::load('consumer');
    }

    public static function getAllPropertiesAccount()
    {
        return self::load('all_properties');
    }

    public static function getForQueryAccount()
    {
        return self::load('for_query');
    }
}
