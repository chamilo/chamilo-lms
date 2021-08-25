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

use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\IRL;

/**
 * xAPI account fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class AccountFixtures
{
    public static function getTypicalAccount()
    {
        return new Account('test', IRL::fromString('https://tincanapi.com'));
    }

    public static function getConsumerAccount()
    {
        return new Account('oauth_consumer_x75db', IRL::fromString('https://tincanapi.com/OAuth/Token'));
    }

    public static function getAllPropertiesAccount()
    {
        return new Account('test', IRL::fromString('https://tincanapi.com'));
    }

    public static function getForQueryAccount()
    {
        return new Account('forQuery', IRL::fromString('https://tincanapi.com'));
    }
}
