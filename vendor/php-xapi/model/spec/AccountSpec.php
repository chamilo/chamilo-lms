<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Xabbuh\XApi\Model;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\Account;
use Xabbuh\XApi\Model\IRL;

class AccountSpec extends ObjectBehavior
{
    function its_properties_can_be_read()
    {
        $this->beConstructedWith('test', IRL::fromString('https://tincanapi.com'));

        $this->getName()->shouldReturn('test');
        $this->getHomePage()->equals(IRL::fromString('https://tincanapi.com'))->shouldReturn(true);
    }

    function it_is_not_equal_to_other_account_if_name_are_not_equal()
    {
        $this->beConstructedWith('foo', IRL::fromString('https://tincanapi.com'));

        $this->equals(new Account('bar', IRL::fromString('https://tincanapi.com')))->shouldReturn(false);
    }

    function it_is_not_equal_to_other_account_if_home_pages_are_not_equal()
    {
        $this->beConstructedWith('test', IRL::fromString('https://tincanapi.com'));

        $this->equals(new Account('test', IRL::fromString('https://tincanapi.com/OAuth/Token')))->shouldReturn(false);
    }

    function it_is_equal_to_other_account_if_all_properties_are_equal()
    {
        $this->beConstructedWith('test', IRL::fromString('https://tincanapi.com'));

        $this->equals(new Account('test', IRL::fromString('https://tincanapi.com')))->shouldReturn(true);
    }
}
