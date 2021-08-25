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
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\Group;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;

class GroupSpec extends ObjectBehavior
{
    function it_can_be_initialized_without_an_inverse_functional_identifier()
    {
        $this->beConstructedWith(null, 'anonymous group');
        $this->shouldBeAnInstanceOf(Group::class);
    }

    function it_is_an_actor()
    {
        $this->beConstructedWith(null, 'test');
        $this->shouldHaveType(Actor::class);
    }

    function its_properties_can_be_read()
    {
        $iri = InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'));
        $members = array(new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com'))));
        $this->beConstructedWith($iri, 'test', $members);

        $this->getInverseFunctionalIdentifier()->shouldReturn($iri);
        $this->getName()->shouldReturn('test');
        $this->getMembers()->shouldReturn($members);
    }
}
