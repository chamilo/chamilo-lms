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
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Model\Group;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;

class AgentSpec extends ObjectBehavior
{
    function it_is_an_actor()
    {
        $iri = InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'));
        $this->beConstructedWith($iri);
        $this->shouldHaveType(Actor::class);
    }

    function its_properties_can_be_read()
    {
        $iri = InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'));
        $this->beConstructedWith($iri, 'test');

        $this->getInverseFunctionalIdentifier()->shouldReturn($iri);
        $this->getName()->shouldReturn('test');
    }

    function it_is_not_equal_to_a_group()
    {
        $this->beConstructedWith(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));

        $this->equals(new Group(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'))))->shouldReturn(false);
    }

    function it_is_not_equal_to_an_activity()
    {
        $this->beConstructedWith(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));

        $this->equals(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid')))->shouldReturn(false);
    }
}
