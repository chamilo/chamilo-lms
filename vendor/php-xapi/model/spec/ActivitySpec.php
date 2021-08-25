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
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\StatementObject;

class ActivitySpec extends ObjectBehavior
{
    function it_is_an_xapi_object()
    {
        $this->beConstructedWith(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));
        $this->shouldHaveType(StatementObject::class);
    }

    function it_is_equal_with_other_activity_if_ids_are_equal_and_definitions_are_missing()
    {
        $this->beConstructedWith(IRI::fromString('http://tincanapi.com/conformancetest/activityid'));

        $this->equals(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid')))->shouldReturn(true);
    }
}
