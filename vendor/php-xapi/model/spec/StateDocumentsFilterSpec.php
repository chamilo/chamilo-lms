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
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;
use Xabbuh\XApi\Model\Verb;

class StateDocumentsFilterSpec extends ObjectBehavior
{
    function it_does_not_filter_anything_by_default()
    {
        $filter = $this->getFilter();
        $filter->shouldHaveCount(0);
    }

    function it_can_filter_by_activity()
    {
        $this->byActivity(new Activity(IRI::fromString('http://tincanapi.com/conformancetest/activityid')))->shouldReturn($this);

        $filter = $this->getFilter();
        $filter->shouldHaveCount(1);
        $filter->shouldHaveKeyWithValue('activity', 'http://tincanapi.com/conformancetest/activityid');
    }

    function it_can_filter_by_agent()
    {
        $actor = new Agent(InverseFunctionalIdentifier::withMbox(IRI::fromString('mailto:conformancetest@tincanapi.com')));
        $this->byAgent($actor)->shouldReturn($this);

        $filter = $this->getFilter();
        $filter->shouldHaveCount(1);
        $filter->shouldHaveKeyWithValue('agent', $actor);
    }

    function it_can_filter_by_registration()
    {
        $this->byRegistration('foo')->shouldReturn($this);

        $filter = $this->getFilter();
        $filter->shouldHaveCount(1);
        $filter->shouldHaveKeyWithValue('registration', 'foo');
    }

    function it_can_filter_by_timestamp()
    {
        $this->since(\DateTime::createFromFormat(\DateTime::ISO8601, '2013-05-18T05:32:34Z'))->shouldReturn($this);
        $this->getFilter()->shouldHaveKeyWithValue('since', '2013-05-18T05:32:34+00:00');
    }
}
